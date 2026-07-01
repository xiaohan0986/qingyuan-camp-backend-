<?php
/**
 * 微信一键登录注册 API
 * 
 * 使用场景：
 * 1. 用户点击微信一键登录
 * 2. 后端检查未注册
 * 3. 前端弹出微信授权获取头像昵称
 * 4. 用户确认授权后，自动使用当前手机号注册
 * 5. 无需验证码，直接注册成功
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
$phone = $input['phone'] ?? ''; // 当前手机号（从小程序获取）
$openid = $input['openid'] ?? '';
$sessionKey = $input['sessionKey'] ?? '';
$userInfo = $input['userInfo'] ?? [];
$skills = $input['skills'] ?? []; // 特长列表（可选）
$intended_countries = $input['intended_countries'] ?? []; // 意向国家（可选）

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

// 2. 检查手机号是否已注册
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
                    $userInfo['nickname'] ?? '微信用户',
                    $openid
                ]);
                error_log("创建 users 记录成功：phone={$phone}, openid={$openid}, user_id={$pdo->lastInsertId()}");
            }
        } catch (PDOException $e) {
            error_log('同步 openid 失败：' . $e->getMessage());
        }
        
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
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $existingUser['id'],
                    'phone' => $existingUser['phone'],
                    'nickname' => $userInfo['nickname'] ?? $existingUser['nickname'],
                    'avatar' => $userInfo['avatar'] ?? $existingUser['avatar'],
                    'wechat_id' => $existingUser['wechat_id'] ?? '',
                    'gender' => $userInfo['gender'] ?? $existingUser['gender']
                ]
            ]
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '查询用户失败：' . $e->getMessage()
    ]);
    exit;
}

// 3. 创建新用户
try {
    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $nickname = $userInfo['nickname'] ?? '微信用户';
    
    // 将偏好数组转为 JSON 字符串
    $skillsJson = !empty($skills) ? json_encode($skills, JSON_UNESCAPED_UNICODE) : null;
    $countriesJson = !empty($intended_countries) ? json_encode($intended_countries, JSON_UNESCAPED_UNICODE) : null;
    
    $insertStmt = $pdo->prepare("
        INSERT INTO mini_program_users (phone, username, password, nickname, wechat_openid, wechat_session_key, avatar, gender, skills, intended_countries, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    
    $insertStmt->execute([
        $phone,
        $phone, // 用户名为手机号
        $passwordHash,
        $nickname,
        $openid,
        $sessionKey,
        $userInfo['avatar'] ?? '',
        $userInfo['gender'] ?? 0,
        $skillsJson,
        $countriesJson
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // 同步 openid 到 users 表（创建对应的后台用户）
    try {
        // 检查是否已存在同手机号的 users 用户
        $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $checkUserStmt->execute([$phone]);
        $userRecord = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userRecord) {
            // 已存在，只有当 openid 为空时才更新
            if (empty($userRecord['openid'])) {
                $syncStmt = $pdo->prepare("UPDATE users SET openid = ? WHERE id = ?");
                $syncStmt->execute([$openid, $userRecord['id']]);
            }
        } else {
            // 不存在，创建新的 users 用户
            $createUserStmt = $pdo->prepare("
                INSERT INTO users (phone, username, password, nickname, openid, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
            ");
            $tempPassword = bin2hex(random_bytes(16));
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $createUserStmt->execute([
                $phone,
                $phone,
                $passwordHash,
                $nickname,
                $openid  // 直接写入 openid
            ]);
        }
    } catch (PDOException $e) {
        error_log('同步 users 表失败：' . $e->getMessage());
    }
    
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
                'wechat_id' => '',
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
