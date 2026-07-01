<?php
/**
 * 微信一键登录 API
 * 
 * 功能：
 * 1. 通过微信 code 获取用户 openid
 * 2. 检查用户是否已注册
 * 3. 已注册：直接登录
 * 4. 未注册：自动注册
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['code' => 405, 'message' => 'Method Not Allowed']);
    exit;
}

// 引入数据库配置
require_once __DIR__ . '/../config/database.php';

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败：' . $e->getMessage()
    ]);
    exit;
}

// 获取输入数据
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$phone = $input['phone'] ?? '';  // 可选，用于检查手机号是否已注册
$userInfo = $input['userInfo'] ?? [];

if (empty($code)) {
    echo json_encode([
        'code' => 400,
        'message' => '缺少微信 code'
    ]);
    exit;
}

// 微信小程序配置
// ⚠️ 请替换为实际的小程序 AppID 和 AppSecret
$appId = 'wxa1d87433a193a38b'; // 小程序 AppID
$appSecret = 'c575f21feca581fecf27b0267c72d015'; // 小程序 AppSecret

// 1. 通过 code 获取 openid
$loginUrl = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";

$loginResponse = file_get_contents($loginUrl);
$loginData = json_decode($loginResponse, true);

if (!isset($loginData['openid'])) {
    echo json_encode([
        'code' => 500,
        'message' => '微信登录失败：' . ($loginData['errmsg'] ?? '未知错误')
    ]);
    exit;
}

$openid = $loginData['openid'];
$sessionKey = $loginData['session_key'] ?? '';

// 2. 如果传递了手机号，检查手机号是否已注册
if (!empty($phone)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // 手机号已注册，直接登录
            if ($user['status'] == 0) {
                echo json_encode([
                    'code' => 403,
                    'message' => '该账号已被禁用，请联系客服'
                ]);
                exit;
            }
            
            // 更新微信 openid（如果未绑定）
            if (empty($user['wechat_openid'])) {
                $updateStmt = $pdo->prepare("UPDATE mini_program_users SET wechat_openid = ?, wechat_session_key = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$openid, $sessionKey, $user['id']]);
            }
            
            // 同步 openid 到 users 表（强制同步）
            try {
                $checkUserStmt = $pdo->prepare("SELECT id, openid FROM users WHERE phone = ? LIMIT 1");
                $checkUserStmt->execute([$phone]);
                $userRecord = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userRecord) {
                    // users 表有记录
                    if (empty($userRecord['openid'])) {
                        // openid 为空 → 写入
                        $syncStmt = $pdo->prepare("UPDATE users SET openid = ? WHERE id = ?");
                        $syncStmt->execute([$openid, $userRecord['id']]);
                        error_log("同步 openid 成功：phone={$phone}, openid={$openid}");
                    } else {
                        // openid 已有 → 跳过（不覆盖）
                        error_log("users.openid 已有值，跳过：phone={$phone}, openid={$userRecord['openid']}");
                    }
                } else {
                    // users 表没有记录 → 创建新记录
                    $tempPassword = bin2hex(random_bytes(16));
                    $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                    $createUserStmt = $pdo->prepare("
                        INSERT INTO users (phone, username, password, nickname, openid, role, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
                    ");
                    $createUserStmt->execute([
                        $phone,
                        $phone,
                        $passwordHash,
                        $user['nickname'] ?? '微信用户',
                        $openid
                    ]);
                    error_log("创建 users 记录成功：phone={$phone}, openid={$openid}, user_id={$pdo->lastInsertId()}");
                }
            } catch (PDOException $e) {
                error_log('同步 openid 失败：' . $e->getMessage());
            }
            
            // 更新最后登录时间
            $updateLoginStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
            $updateLoginStmt->execute([$user['id']]);
            
            // 生成 JWT token
            require_once __DIR__ . '/../includes/JWT.php';
            $token = JWT::generate([
                'user_id' => $user['id'],
                'openid' => $openid,
                'phone' => $user['phone'],
                'role' => $user['role'] ?? 1
            ], 30);
            
            // 保存 token
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        token VARCHAR(255) NOT NULL,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_token (token),
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                
                $tokenStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                $tokenStmt->execute([$user['id'], $token]);
            } catch (PDOException $e) {
                error_log('保存 token 失败：' . $e->getMessage());
            }
            
            echo json_encode([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'phone' => $user['phone'],
                        'nickname' => $user['nickname'] ?? '微信用户',
                        'avatar' => $user['avatar'] ?? '',
                        'wechat_id' => $user['wechat_id'] ?? '',
                        'gender' => $user['gender'] ?? 0
                    ],
                    'openid' => $openid,  // 添加 openid 字段
                    'sessionKey' => $sessionKey  // 添加 sessionKey 字段
                ]
            ]);
            exit;
            
        } else {
            // 手机号未注册，返回 openid 供注册使用
            echo json_encode([
                'code' => 400,
                'message' => '未注册',
                'data' => [
                    'openid' => $openid,
                    'sessionKey' => $sessionKey
                ]
            ]);
            exit;
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'code' => 500,
            'message' => '服务器错误：' . $e->getMessage()
        ]);
        exit;
    }
}

// 3. 未传递手机号，检查 openid 是否已注册
try {
    $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE wechat_openid = ? LIMIT 1");
    $stmt->execute([$openid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // 用户已存在，检查状态
        if ($user['status'] == 0) {
            echo json_encode([
                'code' => 403,
                'message' => '该账号已被禁用，请联系客服'
            ]);
            exit;
        }
        
        // 更新用户信息（昵称、头像）
        if (!empty($userInfo['nickname'])) {
            $updateStmt = $pdo->prepare("UPDATE mini_program_users SET nickname = ?, avatar = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$userInfo['nickname'], $userInfo['avatar'] ?? '', $user['id']]);
            $user['nickname'] = $userInfo['nickname'];
            $user['avatar'] = $userInfo['avatar'] ?? '';
        }
        
        // 更新最后登录时间
        $updateLoginStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
        $updateLoginStmt->execute([$user['id']]);
        
        // 生成 JWT token（30 天有效期）
        require_once __DIR__ . '/../includes/JWT.php';
        $token = JWT::generate([
            'user_id' => $user['id'],
            'openid' => $openid,
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'] ?? 1
        ], 30);
        
        // 可选：保存 token 到数据库用于后台管理（JWT 本身不需要数据库存储）
        // 这里保留原有逻辑以便后台查看登录记录
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token (token),
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            $tokenStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
            $tokenStmt->execute([$user['id'], $token]);
        } catch (PDOException $e) {
            error_log('保存 token 失败：' . $e->getMessage());
        }
        
        echo json_encode([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'phone' => $user['phone'] ?? '',
                    'nickname' => $user['nickname'] ?? '微信用户',
                    'avatar' => $user['avatar'] ?? '',
                    'wechat_id' => $user['wechat_id'] ?? '',
                    'gender' => $user['gender'] ?? 0
                ]
            ]
        ]);
        exit;
        
    } else {
        // 用户不存在，返回 openid 让前端跳转手机号验证
        echo json_encode([
            'code' => 400,
            'message' => '未注册',
            'data' => [
                'openid' => $openid,
                'sessionKey' => $sessionKey
            ]
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage()
    ]);
    exit;
}
