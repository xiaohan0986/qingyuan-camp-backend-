<?php
/**
 * 添加国家标签 API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 直接使用数据库配置
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=qianwutong",
        "qianwutong",
        "hb098634",
        [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // 只允许 POST 请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法不允许');
    }
    
    // 获取参数
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    
    if (empty($country)) {
        throw new Exception('国家名称不能为空');
    }
    
    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT id FROM country_tags WHERE country_name = ?");
    $stmt->execute([$country]);
    
    if ($stmt->fetch()) {
        throw new Exception('该国家已存在');
    }
    
    // 获取最大排序值
    $stmt = $pdo->query("SELECT MAX(sort_order) as max_sort FROM country_tags");
    $maxSort = $stmt->fetchColumn() ?: 0;
    
    // 插入新标签
    $stmt = $pdo->prepare("
        INSERT INTO country_tags (country_name, sort_order, is_active, created_at, updated_at)
        VALUES (?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([$country, $maxSort + 1]);
    
    echo json_encode([
        'code' => 200,
        'message' => '添加成功',
        'data' => [
            'country_name' => $country,
            'sort_order' => $maxSort + 1
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
