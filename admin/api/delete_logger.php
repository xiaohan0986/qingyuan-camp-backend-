<?php
/**
 * 记录删除请求的详细日志
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/oss.php';

// 记录所有删除请求
$logFile = __DIR__ . '/delete_log.txt';
$logEntry = date('Y-m-d H:i:s') . " - 删除请求\n";
$logEntry .= "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
$logEntry .= "action: " . ($_GET['action'] ?? 'N/A') . "\n";
$logEntry .= "file_key: " . ($_POST['file_key'] ?? 'N/A') . "\n";
$logEntry .= "file_type: " . ($_POST['file_type'] ?? 'N/A') . "\n";
$logEntry .= "OSS_UPLOAD_DIR: " . (defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : 'N/A') . "\n";
$logEntry .= "计算后的 objectName: " . (rtrim(OSS_UPLOAD_DIR, '/') . '/' . ltrim($_POST['file_key'] ?? '', '/')) . "\n";
$logEntry .= str_repeat('-', 80) . "\n";

file_put_contents($logFile, $logEntry, FILE_APPEND);

// 继续处理删除
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => '删除请求已记录',
    'file_key' => $_POST['file_key'] ?? '',
    'object_name' => rtrim(OSS_UPLOAD_DIR, '/') . '/' . ltrim($_POST['file_key'] ?? '', '/'),
], JSON_UNESCAPED_UNICODE);
