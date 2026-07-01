<?php
/**
 * 客户文件下载和预览
 * 通过此脚本安全地访问上传的文件
 */
require_once __DIR__ . '/bootstrap.php';

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
    die('数据库连接失败');
}

$action = $_GET['action'] ?? 'download';
$file_id = (int)($_GET['file_id'] ?? 0);

if (!$file_id) {
    die('文件 ID 不能为空');
}

// 获取文件信息
    $stmt = $pdo->prepare("SELECT id, customer_id, doc_type, file_path, file_name, original_name, file_size, mime_type, created_at FROM customer_documents WHERE id = :id");
$stmt->execute([':id' => $file_id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die('文件不存在');
}

// 构建文件路径
$filePath = ROOT_PATH . $doc['file_path'];

if (!file_exists($filePath)) {
    die('文件不存在于服务器');
}

// 获取文件 MIME 类型
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

// 如果是预览模式且是图片，直接在浏览器显示
if ($action === 'preview' && strpos($mimeType, 'image/') === 0) {
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    readfile($filePath);
    exit;
}

// 下载模式
// 设置下载头
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . urlencode($doc['file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0');

// 如果是图片，也允许在浏览器中预览
if (strpos($mimeType, 'image/') === 0) {
    header('Content-Disposition: inline; filename="' . urlencode($doc['file_name']) . '"');
}

readfile($filePath);
