<?php
/**
 * 获取用户订阅状态
 * 
 * 从数据库读取用户是否已订阅进度通知
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败',
        'data' => ['subscribed' => false]
    ]);
    exit;
}

// 获取手机号参数
$phone = $_GET['phone'] ?? '';

if (empty($phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '缺少手机号参数',
        'data' => ['subscribed' => false]
    ]);
    exit;
}

try {
    // 查询订阅状态
    $stmt = $pdo->prepare("
        SELECT `subscribed`, `subscribe_time`, `last_push_time`, `push_count`
        FROM `user_subscriptions`
        WHERE `phone` = ? AND `subscribe_type` = 'progress_notify'
        LIMIT 1
    ");
    $stmt->execute([$phone]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // 有记录，返回订阅状态
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'subscribed' => (bool)$subscription['subscribed'],
                'subscribe_time' => $subscription['subscribe_time'],
                'last_push_time' => $subscription['last_push_time'],
                'push_count' => $subscription['push_count']
            ]
        ]);
    } else {
        // 无记录，返回未订阅
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'subscribed' => false
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '查询失败：' . $e->getMessage(),
        'data' => ['subscribed' => false]
    ]);
}
