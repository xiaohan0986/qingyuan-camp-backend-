<?php
/**
 * 根据手机号获取用户 OpenID
 * 
 * 用于后台 reports2.php 页面显示客户 OpenID
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只接受 GET 请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// 获取参数（支持 phone 或 user_id）
$phone = $_GET['phone'] ?? '';
$user_id = $_GET['user_id'] ?? '';

try {
    // 从 users 表查询 OpenID
    if (!empty($user_id)) {
        // 通过 user_id 查询
        $stmt = $pdo->prepare("
            SELECT id, phone, openid, nickname 
            FROM users 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
    } else if (!empty($phone)) {
        // 通过手机号查询
        $stmt = $pdo->prepare("
            SELECT id, phone, openid, nickname 
            FROM users 
            WHERE phone = ? 
            LIMIT 1
        ");
        $stmt->execute([$phone]);
    } else {
        echo json_encode([
            'code' => 400,
            'message' => '缺少手机号或 user_id 参数'
        ]);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'user_id' => $user['id'],
                'phone' => $user['phone'],
                'openid' => $user['openid'] ?? '',
                'nickname' => $user['nickname'] ?? ''
            ]
        ]);
    } else {
        echo json_encode([
            'code' => 404,
            'message' => '用户不存在',
            'data' => null
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '查询失败：' . $e->getMessage()
    ]);
    exit;
}
