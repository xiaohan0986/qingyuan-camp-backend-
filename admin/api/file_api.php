<?php
/**
 * 商品图片/视频 API 接口
 * 
 * 功能：
 * 1. 生成预览签名 URL（图片/视频）
 * 2. 删除文件（鉴权 + OSS 删除）
 * 
 * 安全原则：
 * - 前端永远不暴露 AK/SK
 * - 所有敏感操作走后端
 * - 签名 URL 有时效性
 */

// 关闭错误输出（避免污染 JSON 响应）
error_reporting(0);
ini_set('display_errors', 0);

// 设置 JSON 响应头（必须在任何输出之前）
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/oss.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/OSSClient.php';

// 检查登录状态（使用 Auth::user() 而不是 Auth::check()，避免重定向）
$admin = Auth::user();
if (empty($admin['id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_preview_url':
        // 生成预览签名 URL
        getPreviewUrl($db);
        break;
        
    case 'delete_file':
        // 删除文件
        deleteFile($db);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}

/**
 * 生成预览签名 URL
 */
function getPreviewUrl($db) {
    $fileKey = $_GET['file_key'] ?? '';
    $type = $_GET['type'] ?? 'image'; // image 或 video
    $expires = intval($_GET['expires'] ?? 3600); // 默认 1 小时有效期
    
    if (empty($fileKey)) {
        echo json_encode(['success' => false, 'message' => '文件 key 不能为空']);
        return;
    }
    
    // 鉴权：检查用户是否有权限查看该文件
    $admin = Auth::user();
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => '未登录']);
        return;
    }
    
    // 检查文件是否存在于数据库中（防止未授权访问）
    // 简化版：只要是登录的管理员都可以查看
    
    try {
        $ossClient = OSSClient::getInstance();
        
        if ($ossClient->isAvailable()) {
            // OSS 模式：生成签名 URL
            $url = generateSignedUrl($fileKey, $expires);
            $storage = 'OSS';
        } else {
            // 本地模式：直接返回 URL
            $url = getLocalFileUrl($fileKey);
            $storage = 'Local';
        }
        
        echo json_encode([
            'success' => true,
            'url' => $url,
            'type' => $type,
            'expires' => $expires,
            'storage' => $storage
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '生成预览 URL 失败：' . $e->getMessage()
        ]);
    }
}

/**
 * 生成 OSS 签名 URL
 */
function generateSignedUrl($fileKey, $expires = 3600) {
    // 添加上传目录前缀（检测 file_key 是否已经包含前缀）
    $uploadDir = defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : 'shopauba';
    
    // 检测 file_key 是否已经包含 uploadDir 前缀
    if (strpos($fileKey, $uploadDir . '/') === 0) {
        // 已经包含前缀，直接使用
        $objectName = $fileKey;
    } else {
        // 不包含前缀，添加前缀
        $objectName = rtrim($uploadDir, '/') . '/' . ltrim($fileKey, '/');
    }
    
    $accessKeyId = OSS_ACCESS_KEY_ID;
    $accessKeySecret = OSS_ACCESS_KEY_SECRET;
    $endpoint = OSS_ENDPOINT;
    $bucket = OSS_BUCKET;
    
    // 构建 URL
    $protocol = strpos($endpoint, 'https') === 0 ? 'https' : 'http';
    $host = str_replace(['https://', 'http://'], '', $endpoint);
    $baseUrl = $protocol . '://' . $bucket . '.' . $host . '/' . $objectName;
    
    // 生成签名参数
    $expiration = time() + $expires;
    
    // 构建签名字符串（OSS 标准格式）
    // 格式：VERB + \n + Content-MD5 + \n + Content-Type + \n + Expires + \n + CanonicalizedResource
    $stringToSign = "GET\n\n\n{$expiration}\n/{$bucket}/{$objectName}";
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret, true));
    
    // 构建完整 URL
    $signedUrl = $baseUrl . '?OSSAccessKeyId=' . urlencode($accessKeyId) 
               . '&Expires=' . $expiration 
               . '&Signature=' . urlencode($signature);
    
    return $signedUrl;
}

/**
 * 获取本地文件 URL
 */
function getLocalFileUrl($fileKey) {
    $host = 'http://' . $_SERVER['HTTP_HOST'];
    return $host . '/' . ltrim($fileKey, '/');
}

/**
 * 删除文件
 */
function deleteFile($db) {
    // 只接受 POST 请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请使用 POST 请求']);
        return;
    }
    
    $fileKey = $_POST['file_key'] ?? '';
    $fileType = $_POST['file_type'] ?? 'image'; // image 或 video
    
    if (empty($fileKey)) {
        echo json_encode(['success' => false, 'message' => '文件 key 不能为空']);
        return;
    }
    
    // 鉴权（核心安全步骤）
    $admin = Auth::user();
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => '未登录，无权限删除']);
        return;
    }
    
    // 检查是否是管理员或有权限
    // 简化版：只要是管理员就可以删除
    // 生产环境建议：检查文件所有者或管理员角色
    
    try {
        $ossClient = OSSClient::getInstance();
        
        if ($ossClient->isAvailable()) {
            // OSS 模式：调用 OSS SDK 删除
            // 添加上传目录前缀（检测 file_key 是否已经包含前缀）
            $uploadDir = defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : 'shopauba';
            
            // 检测 file_key 是否已经包含 uploadDir 前缀
            if (strpos($fileKey, $uploadDir . '/') === 0) {
                // 已经包含前缀，直接使用
                $objectName = $fileKey;
            } else {
                // 不包含前缀，添加前缀
                $objectName = rtrim($uploadDir, '/') . '/' . ltrim($fileKey, '/');
            }
            
            $result = $ossClient->deleteFile($objectName);
            
            if ($result) {
                // 删除数据库记录（如果需要）
                // 这里可以根据业务需求决定是否删除数据库记录
                
                echo json_encode([
                    'success' => true,
                    'message' => '删除成功',
                    'file_key' => $fileKey,
                    'object_name' => $objectName,
                    'storage' => 'OSS'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'OSS 删除失败',
                    'file_key' => $fileKey,
                    'object_name' => $objectName
                ]);
            }
        } else {
            // 本地模式：删除本地文件
            $uploadPath = __DIR__ . '/../../';
            $filePath = $uploadPath . $fileKey;
            
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    echo json_encode([
                        'success' => true,
                        'message' => '删除成功',
                        'file_key' => $fileKey,
                        'storage' => 'Local'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => '文件删除失败'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '文件不存在'
                ]);
            }
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '删除失败：' . $e->getMessage()
        ]);
    }
}
