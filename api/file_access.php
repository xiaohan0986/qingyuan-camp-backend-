<?php
/**
 * 客户文件预览和下载 API
 * 提供安全的文件访问，防止直接访问上传目录
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once __DIR__ . '/../bootstrap.php';

// 加载数据库配置
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
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$file_id = (int)($_GET['file_id'] ?? 0);

if ($action === 'get_url' && $file_id) {
    // 获取文件访问 URL
    $stmt = $pdo->prepare("SELECT * FROM customer_documents WHERE id = :id");
    $stmt->execute([':id' => $file_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doc) {
        echo json_encode(['code' => 404, 'message' => '文件不存在'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查文件是否存在
    $filePath = ROOT_PATH . $doc['file_path'];
    if (!file_exists($filePath)) {
        echo json_encode(['code' => 404, 'message' => '文件不存在于服务器'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 生成访问 URL（通过 download.php）
    $url = 'download.php?action=preview&file_id=' . $file_id;
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'url' => $url,
            'file_name' => $doc['file_name'],
            'file_size' => $doc['file_size'],
            'mime_type' => mime_content_type($filePath)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    echo json_encode(['code' => 400, 'message' => '无效的请求'], JSON_UNESCAPED_UNICODE);
}
