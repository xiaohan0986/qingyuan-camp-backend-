<?php
/**
 * 商品图片上传接口
 */

// 清空输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 延长执行时间（OSS 上传可能很慢）
set_time_limit(180);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/oss.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/OSSClient.php';
require_once __DIR__ . '/../../includes/SimpleOSSClient.php';

Auth::check();

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

// 支持上传 image 或 video 字段
$file = $_FILES['image'] ?? $_FILES['video'] ?? null;

if (!$file) {
    echo json_encode(['success' => false, 'message' => '没有接收到文件']);
    exit;
}

// 检查文件上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 中 upload_max_filesize 限制',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单 MAX_FILE_SIZE 限制',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => 'PHP 扩展阻止了文件上传',
    ];
    $errorCode = $file['error'];
    $errorMsg = $uploadErrors[$errorCode] ?? '文件上传失败 (错误码：' . $errorCode . ')';
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

// 判断是图片还是视频
$isVideo = isset($_FILES['video']);

// 验证文件类型
$allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];

if ($isVideo) {
    if (!in_array($file['type'], $allowedVideoTypes)) {
        echo json_encode(['success' => false, 'message' => '只允许上传 MP4/WebM/OGG 格式的视频']);
        exit;
    }
    // 验证视频大小（100MB）
    $maxSize = 100 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => '视频大小不能超过 100MB']);
        exit;
    }
} else {
    if (!in_array($file['type'], $allowedImageTypes)) {
        echo json_encode(['success' => false, 'message' => '只允许上传 JPG/PNG/GIF/WebP 格式的图片']);
        exit;
    }
    // 验证图片大小（5MB）
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => '图片大小不能超过 5MB']);
        exit;
    }
}

// 创建上传目录
$uploadDir = $isVideo 
    ? __DIR__ . '/../../uploads/videos/' 
    : __DIR__ . '/../../uploads/products/';
    
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成文件名
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = ($isVideo ? 'video_' : 'product_') . date('Ymd') . '_' . uniqid() . '.' . $ext;
$filepath = $uploadDir . $filename;

// 生成 OSS 对象名称
$objectName = ($isVideo ? 'videos/' : 'products/') . date('Ym/d/') . $filename;

// 上传到 OSS 或本地
$ossClient = OSSClient::getInstance();

if ($ossClient->isAvailable()) {
    // 上传到 OSS
    $fileUrl = $ossClient->uploadFile($file['tmp_name'], $objectName);
    $uploadMethod = 'OSS';
    $returnUrl = ($isVideo ? 'videos/' : 'products/') . date('Ym/d/') . $filename;
    $previewUrl = $fileUrl;
} else {
    // 保存到本地
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        // Don't throw since we're outside a try block - use error msg
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
        exit;
    }
    $fileUrl = '/' . ($isVideo ? 'uploads/videos/' : 'uploads/products/') . $filename;
    $uploadMethod = 'Local';
    $returnUrl = $fileUrl;
    $previewUrl = $fileUrl;
}

// 记录日志
$db->insert('admin_logs', [
    'admin_id' => Auth::user()['id'],
    'action' => $isVideo ? '上传视频' : '上传图片',
    'detail' => ($isVideo ? '视频' : '图片') . "：$filename (存储方式：$uploadMethod)",
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'created_at' => date('Y-m-d H:i:s')
]);

echo json_encode([
    'success' => true,
    'message' => '上传成功',
    'url' => $returnUrl,  // 文件 key 或本地路径
    'file_key' => $returnUrl,  // 明确字段名
    'preview_url' => $previewUrl,  // 完整 URL 用于立即预览
    'filename' => $filename,
    'size' => $file['size'],
    'type' => $isVideo ? 'video' : 'image',
    'storage' => $uploadMethod
]);
