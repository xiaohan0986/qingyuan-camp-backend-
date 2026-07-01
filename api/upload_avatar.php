<?php
/**
 * 头像上传 API
 * 支持两种操作：
 * 1. GET/POST action=get_token: 获取上传凭证
 * 2. POST: 直接上传头像文件
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载项目引导文件
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
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'get_token') {
        getUploadToken($pdo);
    } else {
        uploadAvatar($pdo);
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function getUploadToken($pdo) {
    $token = 'avatar_' . time() . '_' . uniqid();
    $rootPath = rtrim(ROOT_PATH, '/') . '/';
    $uploadDir = $rootPath . 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'token' => $token,
            'uploadUrl' => 'https://www.gofong.com/api/upload_avatar.php',
            'imageUrl' => 'https://www.gofong.com/uploads/avatars/' . $token . '.jpg'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function uploadAvatar($pdo) {
    if (!isset($_FILES['file']) && !isset($_FILES['avatar'])) {
        echo json_encode(['code' => 400, 'message' => '没有上传文件或上传失败'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $file = $_FILES['file'] ?? $_FILES['avatar'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_OK => '无错误',
            UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 的 upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单的 MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP 扩展阻止了文件上传'
        ];
        
        echo json_encode([
            'code' => 400,
            'message' => '上传失败：' . ($errors[$file['error']] ?? '未知错误'),
            'error_code' => $file['error']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode([
            'code' => 400,
            'message' => '只支持 JPG、PNG、GIF 和 WebP 格式',
            'file_type' => $file['type']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'code' => 400,
            'message' => '文件大小不能超过 5MB',
            'file_size' => $file['size']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $rootPath = rtrim(ROOT_PATH, '/') . '/';
    $uploadDir = $rootPath . 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        $typeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        $extension = $typeToExt[$file['type']] ?? 'jpg';
    }
    
    $fileName = 'avatar_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode([
            'code' => 500,
            'message' => '文件保存失败',
            'error' => error_get_last()['message'] ?? '未知错误'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $imageUrl = 'https://www.gofong.com/uploads/avatars/' . $fileName;
    echo json_encode([
        'code' => 200,
        'message' => '上传成功',
        'data' => [
            'url' => $imageUrl,
            'filename' => $fileName,
            'size' => $file['size']
        ]
    ], JSON_UNESCAPED_UNICODE);
}
