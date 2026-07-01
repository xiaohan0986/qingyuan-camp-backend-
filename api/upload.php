<?php
/**
 * 文件上传 API
 * 支持岗位所需材料和附件文件上传
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// 加载项目引导文件
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/oss.php';

// 允许的 MIME 类型
$allowedMimeTypes = [
    // 图片
    'image/jpeg' => '.jpg',
    'image/png' => '.png',
    'image/gif' => '.gif',
    'image/webp' => '.webp',
    // 文档
    'application/pdf' => '.pdf',
    'application/msword' => '.doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
    'application/vnd.ms-excel' => '.xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
    'text/plain' => '.txt',
];

// 最大文件大小（10MB）
$maxFileSize = 10 * 1024 * 1024;

// 上传目录
$uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'positions';

// 确保上传目录存在
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 创建子目录（按日期）
$subDir = date('Y-m');
$uploadSubDir = $uploadDir . DIRECTORY_SEPARATOR . $subDir;
if (!is_dir($uploadSubDir)) {
    mkdir($uploadSubDir, 0755, true);
}

$response = [
    'code' => 500,
    'message' => '上传失败',
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('仅支持 POST 请求');
    }
    
    // 检查是否有上传的文件
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '文件超过 PHP 上传限制 (upload_max_filesize=' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单 MAX_FILE_SIZE 限制',
            UPLOAD_ERR_PARTIAL => '文件仅部分上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP 扩展阻止了文件上传'
        ];
        $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($errorMessages[$errorCode] ?? '上传错误');
    }
    
    $file = $_FILES['file'];
    
    // 检查文件大小
    if ($file['size'] > $maxFileSize) {
        throw new Exception('文件大小不能超过 10MB');
    }
    
    // 检查文件类型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!array_key_exists($mimeType, $allowedMimeTypes)) {
        throw new Exception('不支持的文件类型：' . $mimeType);
    }
    
    // 生成安全的文件名
    $extension = $allowedMimeTypes[$mimeType];
    $fileName = uniqid('file_') . '_' . time() . $extension;
    $filePath = $uploadSubDir . DIRECTORY_SEPARATOR . $fileName;
    
    // 调试日志
    error_log('Upload debug: tmp_name=' . $file['tmp_name'] . ', filePath=' . $filePath);
    error_log('Upload debug: tmp_exists=' . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
    error_log('Upload debug: dir_exists=' . (is_dir($uploadSubDir) ? 'yes' : 'no'));
    error_log('Upload debug: dir_writable=' . (is_writable($uploadSubDir) ? 'yes' : 'no'));
    
    // 移动上传的文件
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $lastError = error_get_last();
        error_log('Upload error: move_uploaded_file failed. Last error: ' . ($lastError ? $lastError['message'] : 'unknown'));
        throw new Exception('文件保存失败。错误：' . ($lastError ? $lastError['message'] : '未知'));
    }
    
    // 验证文件是否真的保存成功
    if (!file_exists($filePath)) {
        error_log('Upload error: file not found after move: ' . $filePath);
        throw new Exception('文件保存后验证失败：文件不存在');
    }
    
    // 生成相对路径（用于数据库存储）
    $relativePath = 'uploads/positions/' . $subDir . '/' . $fileName;
    
    // 尝试上传到 OSS
    $ossUrl = null;
    if (defined('OSS_ENABLED') && OSS_ENABLED) {
        try {
            require_once __DIR__ . '/../includes/SimpleOSSClient.php';
            $ossClient = SimpleOSSClient::getInstance();
            // OSS 对象名：articles/2026/06/28/filename.jpg
            $objectName = 'articles/' . $subDir . '/' . $fileName;
            $ossUrl = $ossClient->uploadFile($filePath, $objectName);
        } catch (Exception $e) {
            error_log('OSS upload failed: ' . $e->getMessage() . ', using local fallback');
        }
    }
    
    // 生成完整 URL
    require_once __DIR__ . '/../config/paths.php';
    if ($ossUrl) {
        $fullUrl = $ossUrl;
        $relativePath = $ossUrl; // Full OSS URL for database storage
    } else {
        $fullUrl = BASE_URL . '/' . $relativePath;
    }
    
    // 获取文件信息
    $fileInfo = [
        'file_name' => $file['name'], // 原始文件名
        'file_path' => $relativePath, // 相对路径（数据库存储）
        'file_url' => $fullUrl, // 完整 URL（前端访问）
        'file_size' => $file['size'], // 文件大小（字节）
        'mime_type' => $mimeType, // MIME 类型
        'extension' => $extension, // 文件扩展名
        'upload_time' => date('Y-m-d H:i:s') // 上传时间
    ];
    
    $response = [
        'code' => 200,
        'message' => '上传成功',
        'data' => $fileInfo
    ];
    
} catch (Exception $e) {
    $response = [
        'code' => 500,
        'message' => $e->getMessage(),
        'data' => null
    ];
    
    // 记录错误日志
    error_log('文件上传失败：' . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
