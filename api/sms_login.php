<?php
/**
 * 短信验证码登录 API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'code' => 405,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 数据库配置
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
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败'
    ]);
    exit;
}

// 获取请求参数
$input = json_decode(file_get_contents('php://input'), true);
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$code = isset($input['code']) ? trim($input['code']) : '';

// 验证参数
if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '请输入正确的手机号码'
    ]);
    exit;
}

if (empty($code) || strlen($code) !== 6) {
    echo json_encode([
        'code' => 400,
        'message' => '请输入正确的验证码'
    ]);
    exit;
}

try {
    // 验证验证码
    $stmt = $pdo->prepare("
        SELECT * FROM sms_codes 
        WHERE phone = ? 
        AND code = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$phone, $code]);
    $smsCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$smsCode) {
        echo json_encode([
            'code' => 400,
            'message' => '验证码错误或已过期'
        ]);
        exit;
    }
    
    // 验证成功，删除验证码
    $deleteStmt = $pdo->prepare("DELETE FROM sms_codes WHERE id = ?");
    $deleteStmt->execute([$smsCode['id']]);
    
    // 查询用户是否已存在
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
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
        
        // 老用户登录，更新 openid
        if (!empty($input['wechat_id'])) {
            try {
                $updateWechatStmt = $pdo->prepare("UPDATE mini_program_users SET wechat_id = ?, updated_at = NOW() WHERE id = ?");
                $updateWechatStmt->execute([$input['wechat_id'], $user['id']]);
                $user['wechat_id'] = $input['wechat_id'];
            } catch (PDOException $e) {
                // 尝试 users 表
                try {
                    $updateWechatStmt = $pdo->prepare("UPDATE users SET wechat_id = ?, updated_at = NOW() WHERE id = ?");
                    $updateWechatStmt->execute([$input['wechat_id'], $user['id']]);
                    $user['wechat_id'] = $input['wechat_id'];
                } catch (PDOException $e2) {
                    // 忽略错误
                }
            }
        }
        
        // 更新最后登录时间
        $updateStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // 生成 token
        $token = bin2hex(random_bytes(32));
        
        // 保存 token 到数据库
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(100) NOT NULL,
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
                    'nickname' => $user['nickname'] ?? '用户',
                    'avatar' => $user['avatar'] ?? '',
                    'wechat_id' => $user['wechat_id'] ?? '',
                    'real_name' => $user['real_name'] ?? '',
                    'gender' => $user['gender'] ?? 0,
                    'age' => $user['age'] ?? 0,
                    'education' => $user['education'] ?? ''
                ]
            ]
        ]);
        exit;
    }
    
    // 用户不存在，创建新用户
    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $nickname = '用户' . substr($phone, 7, 4);
    
    // 获取微信 openid（从小程序登录）
    $wechatId = $input['wechat_id'] ?? '';
    
    // 优先使用 mini_program_users 表
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO mini_program_users (phone, username, password, nickname, wechat_id, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $insertStmt->execute([$phone, $phone, $passwordHash, $nickname, $wechatId]);
    } catch (PDOException $e) {
        // 如果 mini_program_users 表不存在，使用 users 表
        $insertStmt = $pdo->prepare("
            INSERT INTO users (phone, password, nickname, wechat_id, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $insertStmt->execute([$phone, $passwordHash, $nickname, $wechatId]);
    }
    
    $userId = $pdo->lastInsertId();
    
    $user = [
        'id' => $userId,
        'phone' => $phone,
        'nickname' => $nickname,
        'wechat_id' => $wechatId
    ];
    
    // 更新最后登录时间
    $updateStmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // 生成 token（简单实现，生产环境建议使用 JWT）
    $token = bin2hex(random_bytes(32));
    
    // 保存 token 到数据库（可选，用于 token 验证）
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
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
        'message' => 'success',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'phone' => $user['phone'],
                'nickname' => $user['nickname'] ?? '用户',
                'avatar' => $user['avatar'] ?? '',
                'wechat_id' => $user['wechat_id'] ?? '',
                'real_name' => $user['real_name'] ?? '',
                'gender' => $user['gender'] ?? 0,
                'age' => $user['age'] ?? 0,
                'education' => $user['education'] ?? ''
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '登录失败，请稍后重试'
    ]);
    exit;
}
