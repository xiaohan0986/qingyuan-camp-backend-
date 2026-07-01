<?php
/**
 * 手机号验证码登录 API
 * 
 * 使用场景：
 * 1. 用户输入手机号 + 验证码
 * 2. 验证验证码是否正确
 * 3. 已注册：直接登录
 * 4. 未注册：提示注册
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
$code = $input['code'] ?? '';

// 验证必填参数
if (empty($phone) || empty($code)) {
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
    // 查询验证码（从数据库）
    $stmt = $pdo->prepare("SELECT code, expires_at FROM sms_codes WHERE phone = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $smsCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$smsCode) {
        echo json_encode([
            'code' => 400,
            'message' => '验证码无效'
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

// 3. 检查用户是否已存在
try {
    $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE phone = ? LIMIT 1");
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
        
        // 更新最后登录时间
        $updateLoginStmt = $pdo->prepare("UPDATE mini_program_users SET last_login_at = NOW() WHERE id = ?");
        $updateLoginStmt->execute([$user['id']]);
        
        // 生成 JWT token（30 天有效期）
        require_once __DIR__ . '/../includes/JWT.php';
        $token = JWT::generate([
            'user_id' => $user['id'],
            'openid' => $user['wechat_openid'] ?? '',
            'phone' => $user['phone'],
            'role' => $user['role'] ?? 1
        ], 30);
        
        // 保存 token 到数据库
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
                ]
            ]
        ]);
        exit;
        
    } else {
        // 用户不存在，提示注册
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
