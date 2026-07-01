<?php
/**
 * 企业微信客服配置 API
 * 获取客服配置信息
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dbConfig = require_once __DIR__ . '/../config/database.php';

try {
    $db = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // 从数据库读取启用的客服配置
    try {
        $stmt = $db->query("SELECT `service_name`, `service_url`, `corp_id`, `enabled` FROM `customer_service_config` WHERE `enabled` = 1 ORDER BY sort_order, id LIMIT 1");
    } catch (PDOException $e) {
        // 如果 corp_id 字段不存在，只查询基本字段
        $stmt = $db->query("SELECT `service_name`, `service_url`, `enabled` FROM `customer_service_config` WHERE `enabled` = 1 ORDER BY id LIMIT 1");
    }
    $config = $stmt->fetch();
    
    if ($config) {
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'enabled' => intval($config['enabled']),
                'service_name' => $config['service_name'],
                'service_url' => $config['service_url'],
                'corp_id' => $config['corp_id'] ?? 'ww85276673d0f341a1'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // 数据库没有配置，返回默认值
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'enabled' => 0,
                'service_name' => '',
                'service_url' => ''
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (PDOException $e) {
    error_log("读取客服配置失败：" . $e->getMessage());
    echo json_encode([
        'code' => 500,
        'message' => '数据库错误',
        'data' => []
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
