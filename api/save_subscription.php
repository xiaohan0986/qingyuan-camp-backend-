<?php
/**
 * 保存用户订阅状态
 * 
 * 用户点击允许订阅后，保存到数据库
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
        'message' => '数据库连接失败'
    ]);
    exit;
}

// 获取参数
$phone = $_POST['phone'] ?? '';
$template_id = $_POST['template_id'] ?? '';
$subscribed = $_POST['subscribed'] ?? 1; // 1=已订阅，0=取消订阅

if (empty($phone)) {
    echo json_encode([
        'code' => 400,
        'message' => '缺少手机号参数'
    ]);
    exit;
}

try {
    // 检查是否已有记录
    $checkStmt = $pdo->prepare("SELECT `id` FROM `user_subscriptions` WHERE `phone` = ? AND `subscribe_type` = 'progress_notify'");
    $checkStmt->execute([$phone]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // 更新记录
        $stmt = $pdo->prepare("
            UPDATE `user_subscriptions`
            SET 
                `subscribed` = ?,
                `template_id` = ?,
                `subscribe_time` = CASE WHEN ? = 1 THEN NOW() ELSE `subscribe_time` END,
                `updated_at` = NOW()
            WHERE `id` = ?
        ");
        $stmt->execute([$subscribed, $template_id, $subscribed, $existing['id']]);
        
        $action = $subscribed ? '订阅' : '取消订阅';
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'action' => $action,
                'phone' => $phone
            ]
        ]);
    } else {
        // 插入新记录
        $stmt = $pdo->prepare("
            INSERT INTO `user_subscriptions`
            (`phone`, `subscribe_type`, `template_id`, `subscribed`, `subscribe_time`, `created_at`, `updated_at`)
            VALUES
            (?, 'progress_notify', ?, ?, CASE WHEN ? = 1 THEN NOW() ELSE NULL END, NOW(), NOW())
        ");
        $stmt->execute([$phone, $template_id, $subscribed, $subscribed]);
        
        $action = $subscribed ? '订阅' : '取消订阅';
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'action' => $action,
                'phone' => $phone,
                'new_record' => true
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '保存失败：' . $e->getMessage()
    ]);
}
