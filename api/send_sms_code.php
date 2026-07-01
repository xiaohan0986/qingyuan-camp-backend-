<?php
/**
 * 发送短信验证码 API
 * 使用阿里云短信服务
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

// 验证手机号
if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '请输入正确的手机号码'
    ]);
    exit;
}

// 检查是否频繁发送（60 秒内只能发送一次）
try {
    $stmt = $pdo->prepare("SELECT * FROM sms_codes WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$phone]);
    $lastCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastCode) {
        echo json_encode([
            'code' => 429,
            'message' => '发送过于频繁，请稍后再试'
        ]);
        exit;
    }
} catch (PDOException $e) {
    // 如果表不存在，继续执行
}

// 生成 6 位验证码
$code = sprintf('%06d', mt_rand(0, 999999));

// 阿里云短信配置（从 .env 读取）
$accessKeyId = env('SMS_ACCESS_KEY_ID', '');
$accessKeySecret = env('SMS_ACCESS_KEY_SECRET', '');
$signName = env('SMS_SIGN_NAME', '青园营地');
$templateCode = env('SMS_TEMPLATE_CODE', 'SMS_501911015'); // 验证码模板

// 检查 AccessKey 是否已配置
if (empty($accessKeyId) || empty($accessKeySecret)) {
    echo json_encode([
        'code' => 500,
        'message' => '短信服务未配置'
    ]);
    exit;
}

// 调用阿里云短信 API
$result = sendAliyunSms($accessKeyId, $accessKeySecret, $signName, $templateCode, $phone, ['code' => $code]);

if ($result['success']) {
    // 保存验证码到数据库
    try {
        // 检查表是否存在，不存在则创建
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sms_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(20) NOT NULL,
                code VARCHAR(10) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_phone (phone),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // 插入验证码
        $stmt = $pdo->prepare("INSERT INTO sms_codes (phone, code, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$phone, $code]);
        
        // 清理 5 分钟前的旧验证码
        $pdo->exec("DELETE FROM sms_codes WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        
    } catch (PDOException $e) {
        error_log('保存验证码失败：' . $e->getMessage());
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'expire_in' => 300 // 5 分钟有效期
        ]
    ]);
} else {
    echo json_encode([
        'code' => 500,
        'message' => $result['message'] ?: '发送失败，请稍后重试'
    ]);
}

/**
 * 发送阿里云短信
 */
function sendAliyunSms($accessKeyId, $accessKeySecret, $signName, $templateCode, $phone, $templateParam) {
    $apiUrl = 'https://dysmsapi.aliyuncs.com/';
    
    $params = [
        'AccessKeyId' => $accessKeyId,
        'Action' => 'SendSms',
        'Format' => 'JSON',
        'Version' => '2017-05-25',
        'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureVersion' => '1.0',
        'SignatureNonce' => uniqid('', true),
        'PhoneNumbers' => $phone,
        'SignName' => $signName,
        'TemplateCode' => $templateCode,
        'TemplateParam' => json_encode($templateParam)
    ];
    
    // 生成签名
    ksort($params);
    $stringToSign = 'POST&%2F&' . urlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
    
    $params['Signature'] = $signature;
    
    // 发送请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => '网络请求失败：' . $error
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['Code']) && $result['Code'] === 'OK') {
        return [
            'success' => true,
            'message' => '发送成功',
            'bizId' => $result['BizId'] ?? null
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['Message'] ?? '发送失败',
            'code' => $result['Code'] ?? 'UNKNOWN_ERROR'
        ];
    }
}
