<?php
/**
 * 检查手机号是否已注册 API
 * 
 * 使用场景：
 * 1. 微信未注册用户输入手机号和验证码
 * 2. 调用此接口检查手机号是否已注册
 * 3. 已注册：直接登录
 * 4. 未注册：提示完善头像昵称
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
$code = $input['code'] ?? '';
$openid = $input['openid'] ?? '';
$sessionKey = $input['sessionKey'] ?? '';

// 验证必填参数
if (empty($phone) || empty($code) || empty($openid)) {
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

// 2. 验证短信验证码
try {
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
    error_log('验证码验证失败：' . $e->getMessage());
    echo json_encode([
        'code' => 500,
        'message' => '验证码验证失败'
    ]);
    exit;
}

// 3. 检查手机号是否已注册
try {
    $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // 手机号已注册
        
        // 如果已绑定其他微信，不允许绑定
        if (!empty($existingUser['wechat_openid']) && $existingUser['wechat_openid'] !== $openid) {
            echo json_encode([
                'code' => 400,
                'message' => '该手机号已绑定其他微信账号'
            ]);
            exit;
        }
        
        // 更新微信 openid（如果未绑定或绑定的是当前微信）
        $updateStmt = $pdo->prepare("
            UPDATE mini_program_users 
            SET wechat_openid = ?, wechat_session_key = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([
            $openid,
            $sessionKey,
            $existingUser['id']
        ]);
        
        // 生成 token
        $token = bin2hex(random_bytes(32));
        
        // 保存 token
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
                    'nickname' => $existingUser['nickname'],
                    'avatar' => $existingUser['avatar'],
                    'gender' => $existingUser['gender'] ?? 0
                ]
            ]
        ]);
        exit;
        
    } else {
        // 手机号未注册，需要完善头像昵称
        echo json_encode([
            'code' => 400,
            'message' => '未注册'
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
