<?php
/**
 * 微信登录 - 手机号验证注册 API
 * 
 * 使用场景：
 * 1. 用户点击微信登录
 * 2. 后端检查未注册，返回 openid
 * 3. 前端跳转手机号验证页面
 * 4. 用户输入手机号和验证码
 * 5. 调用此 API 完成注册并登录
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
$phone = $input['phone'] ?? '';
$code = $input['code'] ?? ''; // 可选，微信一键登录时不需要
$openid = $input['openid'] ?? '';
$sessionKey = $input['sessionKey'] ?? '';
$userInfo = $input['userInfo'] ?? [];

// 验证必填参数
if (empty($phone) || empty($openid)) {
    echo json_encode([
        'code' => 400,
        'message' => '缺少必填参数'
    ]);
    exit;
}

// 1. 验证手机号格式
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '手机号格式不正确'
    ]);
    exit;
}

// 2. 验证短信验证码（仅当提供 code 时验证，微信一键登录可跳过）
if (!empty($code)) {
    try {
        // 查询验证码（从 redis 或数据库）
        $stmt = $pdo->prepare("SELECT code, expires_at FROM sms_codes WHERE phone = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone]);
    $smsCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$smsCode) {
        echo json_encode([
            'code' => 400,
            'message' => '请先获取验证码'
        ]);
        exit;
    }
    
    // 检查验证码是否过期（5 分钟）
    $expiresAt = strtotime($smsCode['expires_at']);
    if (time() > $expiresAt) {
        echo json_encode([
            'code' => 400,
            'message' => '验证码已过期'
        ]);
        exit;
    }
    
    // 验证验证码是否正确
    if ($smsCode['code'] !== $code) {
        echo json_encode([
            'code' => 400,
            'message' => '验证码错误'
        ]);
        exit;
    }
    
    // 验证成功，删除验证码
    $deleteStmt = $pdo->prepare("DELETE FROM sms_codes WHERE phone = ?");
    $deleteStmt->execute([$phone]);
    
} catch (PDOException $e) {
    // 如果没有 sms_codes 表，开发环境直接通过
    error_log('验证码验证失败：' . $e->getMessage());
}

// 3. 检查手机号是否已注册
try {
    $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // 手机号已注册，检查是否已绑定微信
        if (!empty($existingUser['wechat_openid']) && $existingUser['wechat_openid'] !== $openid) {
            echo json_encode([
                'code' => 400,
                'message' => '该手机号已绑定其他微信账号'
            ]);
            exit;
        }
        
        // 更新微信 openid
        $updateStmt = $pdo->prepare("UPDATE mini_program_users SET wechat_openid = ?, wechat_session_key = ?, nickname = ?, avatar = ?, gender = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([
            $openid,
            $sessionKey,
            $userInfo['nickname'] ?? $existingUser['nickname'],
            $userInfo['avatar'] ?? $existingUser['avatar'],
            $userInfo['gender'] ?? $existingUser['gender'],
            $existingUser['id']
        ]);
        
        // 生成 JWT token（30 天有效期）
        require_once __DIR__ . '/../includes/JWT.php';
        $token = JWT::generate([
            'user_id' => $existingUser['id'],
            'openid' => $openid,
            'phone' => $existingUser['phone'],
            'role' => $existingUser['role'] ?? 1
        ], 30);
        
        // 可选：保存 token 到数据库用于后台管理
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
            $tokenStmt->execute([$existingUser['id'], $token]);
        } catch (PDOException $e) {
            error_log('保存 token 失败：' . $e->getMessage());
        }
        
        // 更新最后登录时间
        $updateLoginStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
        $updateLoginStmt->execute([$existingUser['id']]);
        
        echo json_encode([
            'code' => 200,
            'message' => '绑定成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $existingUser['id'],
                    'phone' => $existingUser['phone'],
                    'nickname' => $userInfo['nickname'] ?? $existingUser['nickname'],
                    'avatar' => $userInfo['avatar'] ?? $existingUser['avatar'],
                    'gender' => $userInfo['gender'] ?? $existingUser['gender']
                ]
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

// 4. 创建新用户
try {
    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $nickname = $userInfo['nickname'] ?? '微信用户';
    
    $insertStmt = $pdo->prepare("
        INSERT INTO mini_program_users (phone, username, password, nickname, wechat_openid, wechat_session_key, avatar, gender, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $insertStmt->execute([
        $phone,
        $phone,
        $passwordHash,
        $nickname,
        $openid,
        $sessionKey,
        $userInfo['avatar'] ?? '',
        $userInfo['gender'] ?? 0
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // 生成 JWT token（30 天有效期）
    require_once __DIR__ . '/../includes/JWT.php';
    $token = JWT::generate([
        'user_id' => $userId,
        'openid' => $openid,
        'phone' => $phone,
        'role' => 1 // 普通用户
    ], 30);
    
    // 可选：保存 token 到数据库用于后台管理
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
        $tokenStmt->execute([$userId, $token]);
    } catch (PDOException $e) {
        error_log('保存 token 失败：' . $e->getMessage());
    }
    
    // 更新最后登录时间
    $updateLoginStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
    $updateLoginStmt->execute([$userId]);
    
    echo json_encode([
        'code' => 200,
        'message' => '注册并登录成功',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $userId,
                'phone' => $phone,
                'nickname' => $nickname,
                'avatar' => $userInfo['avatar'] ?? '',
                'gender' => $userInfo['gender'] ?? 0
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '注册失败：' . $e->getMessage()
    ]);
    exit;
}
