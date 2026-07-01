<?php
/**
 * 获取用户 OpenID
 * 
 * 接收小程序 wx.login() 返回的 code，换取用户 OpenID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');

// 从数据库读取小程序配置
try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $dbPdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_appid'");
    $appId = $stmt->fetchColumn();
    
    $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_secret'");
    $appSecret = $stmt->fetchColumn();
    
    if (empty($appId) || empty($appSecret)) {
        echo json_encode([
            'code' => 500,
            'message' => '请先在后台配置小程序 APPID 和 SECRET'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败：' . $e->getMessage()
    ]);
    exit;
}

// 获取 code（支持 GET、POST 和 JSON 格式）
$code = '';

// 尝试从 POST 获取
if (isset($_POST['code'])) {
    $code = $_POST['code'];
}
// 尝试从 GET 获取
elseif (isset($_GET['code'])) {
    $code = $_GET['code'];
}
// 尝试从 JSON body 获取
else {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (isset($jsonData['code'])) {
            $code = $jsonData['code'];
        }
    }
}

if (empty($code)) {
    echo json_encode([
        'code' => 400,
        'message' => '缺少 code 参数',
        'debug' => [
            'POST' => $_POST,
            'GET' => $_GET,
            'rawInput' => $rawInput ?? ''
        ]
    ]);
    exit;
}

// 调用微信接口获取 OpenID
$url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";

$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['openid'])) {
    $openid = $data['openid'];
    $sessionKey = $data['session_key'];
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'openid' => $openid,
            'session_key' => $sessionKey
        ]
    ]);
} else {
    echo json_encode([
        'code' => 500,
        'message' => '获取 OpenID 失败：' . ($data['errmsg'] ?? '未知错误'),
        'data' => $data
    ]);
}
