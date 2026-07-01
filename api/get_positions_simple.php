<?php
/**
 * 获取岗位列表（简化版 - 供小程序使用）
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';

// 加载数据库配置并创建连接
$config = require_once CONFIG_PATH . 'database.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        ),
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 获取所有启用的岗位（去重）
    $stmt = $pdo->query("
        SELECT DISTINCT title, country, category 
        FROM positions 
        WHERE status = 1 
        AND title IS NOT NULL 
        AND title != ''
        ORDER BY country, category, title
    ");
    
    $positions = $stmt->fetchAll();
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'positions' => $positions
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
