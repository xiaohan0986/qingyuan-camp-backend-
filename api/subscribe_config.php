<?php
/**
 * 获取订阅消息配置
 * 
 * 返回订阅消息模板 ID 和配置信息（从数据库读取）
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
        'message' => '数据库连接失败：' . $e->getMessage(),
        'data' => [
            'enabled' => '0',
            'template_id' => '',
            'auto_subscribe' => '0'
        ]
    ]);
    exit;
}

// 从数据库读取配置
try {
    $stmt = $pdo->prepare("
        SELECT `config_key`, `config_value` 
        FROM `system_config` 
        WHERE `config_key` IN ('miniprogram_appid', 'subscribe_template_id', 'auto_subscribe')
    ");
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $appId = $configs['miniprogram_appid'] ?? '';
    $templateId = $configs['subscribe_template_id'] ?? '';
    $autoSubscribe = $configs['auto_subscribe'] ?? '1'; // 默认开启
    
    // 返回配置信息
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'enabled' => $appId && $templateId ? '1' : '0',
            'template_id' => $templateId,
            'template_name' => '办理进度通知',
            'auto_subscribe' => $autoSubscribe,
            'appid' => $appId,
            'subscribe_page' => 'pages/progress/progress'
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '查询配置失败：' . $e->getMessage(),
        'data' => [
            'enabled' => '0',
            'template_id' => '',
            'auto_subscribe' => '0'
        ]
    ]);
}
