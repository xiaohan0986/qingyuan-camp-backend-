<?php
/**
 * 删除图片 API - 本地版本
 * 删除数据库记录和物理文件
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    // 连接数据库（线上数据库）
    $pdo = new PDO(
        "mysql:host=8.140.224.53;port=3306;dbname=qianwutong",
        "qianwutong",
        "hb098634",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
    );
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $ids = $input['ids'] ?? [];
    
    // 支持单个或多个删除
    if ($id) {
        $ids = [$id];
    }
    
    if (empty($ids)) {
        throw new Exception('缺少文件 ID');
    }
    
    // 批量删除
    $deletedCount = 0;
    $deletedFiles = [];
    
    foreach ($ids as $id) {
        $id = intval($id);
        if (!$id) continue;
        
        // 查询文件信息
        $stmt = $pdo->prepare("SELECT * FROM dx_images WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            continue; // 文件不存在，跳过
        }
        
        // 删除物理文件（本地路径）
        $physicalPath = __DIR__ . '/../uploads/' . basename($file['file_path']);
        if (file_exists($physicalPath)) {
            if (!unlink($physicalPath)) {
                error_log("删除文件失败：$physicalPath");
            }
        }
        
        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM dx_images WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $deletedCount++;
        $deletedFiles[] = $file['filename'];
    }
    
    echo json_encode([
        'code' => 200,
        'message' => '删除成功',
        'data' => [
            'deleted_count' => $deletedCount,
            'files' => $deletedFiles,
        ],
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
