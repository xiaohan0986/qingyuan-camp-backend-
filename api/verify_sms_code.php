<?php
/**
 * 验证短信验证码 API
 * 
 * 用于验证用户输入的验证码是否正确
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

// 验证手机号格式
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '手机号格式不正确'
    ]);
    exit;
}

// 验证短信验证码
try {
    // 查询验证码
    $stmt = $pdo->prepare("SELECT code, created_at FROM sms_codes WHERE phone = ? ORDER BY id DESC LIMIT 1");
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
    $createdAt = strtotime($smsCode['created_at']);
    $expiresAt = $createdAt + 300; // 5 分钟 = 300 秒
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
    
    echo json_encode([
        'code' => 200,
        'message' => '验证成功'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage()
    ]);
    exit;
}
