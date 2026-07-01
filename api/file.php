<?php
/**
 * 文件管理 API
 * 支持文件上传、删除、列表获取
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Storage.php';

// 获取存储配置
$storageConfig = require __DIR__ . '/../config/storage.php';
$storage = new Storage($storageConfig);

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'upload':
        handleUpload($storage);
        break;
    case 'delete':
        handleDelete($storage);
        break;
    case 'list':
        handleList($storage);
        break;
    case 'info':
        handleInfo($storage);
        break;
    default:
        echo json_encode(['code' => 400, 'message' => '未知操作']);
}

/**
 * 处理文件上传
 */
function handleUpload($storage) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['code' => 405, 'message' => '请求方法不允许']);
        return;
    }
    
    // 检查文件
    if (!isset($_FILES['file'])) {
        echo json_encode(['code' => 400, 'message' => '未找到上传文件']);
        return;
    }
    
    $file = $_FILES['file'];
    
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 中 upload_max_filesize 限制',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单 MAX_FILE_SIZE 限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP 扩展阻止了文件上传'
        ];
        echo json_encode([
            'code' => 400,
            'message' => '上传失败：' . ($errors[$file['error']] ?? '未知错误')
        ]);
        return;
    }
    
    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes) && !in_array(pathinfo($file['name'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'])) {
        echo json_encode(['code' => 400, 'message' => '不支持的文件类型']);
        return;
    }
    
    // 验证文件大小（10MB）
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['code' => 400, 'message' => '文件大小超过 10MB 限制']);
        return;
    }
    
    // 获取上传参数
    $subDir = isset($_POST['dir']) ? trim($_POST['dir'], '/') : '';
    $filename = isset($_POST['filename']) ? $_POST['filename'] : null;
    
    // 如果未指定文件名，生成 8 位英文的文件名（直接保存到 images 根目录）
    if ($filename === null) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        // 生成 8 位随机英文字母
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $filename = '';
        for ($i = 0; $i < 8; $i++) {
            $filename .= $chars[rand(0, 25)];
        }
        $filename .= '.' . $ext;
    }
    
    // 执行上传
    $result = $storage->upload($file['tmp_name'], $filename, $subDir);
    
    if ($result['success']) {
        // 保存到数据库（可选）
        saveFileToDatabase($result);
        
        echo json_encode([
            'code' => 0,
            'message' => '上传成功',
            'data' => [
                'url' => $result['url'],
                'filename' => $result['filename'],
                'size' => $result['size'] ?? 0
            ]
        ]);
    } else {
        echo json_encode([
            'code' => 500,
            'message' => '上传失败：' . ($result['error'] ?? '未知错误')
        ]);
    }
}

/**
 * 处理文件删除
 */
function handleDelete($storage) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['code' => 405, 'message' => '请求方法不允许']);
        return;
    }
    
    // 支持通过 ID、filename 或 path 删除
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $path = isset($_POST['path']) ? $_POST['path'] : '';
    
    if (!empty($filename)) {
        // 通过文件名删除物理文件
        $filePath = 'D:/phpstudy_pro/WWW/www.gofong.com/images/' . $filename;
        
        if (!file_exists($filePath)) {
            echo json_encode(['code' => 404, 'message' => '文件不存在']);
            return;
        }
        
        if (unlink($filePath)) {
            // 尝试删除数据库记录
            try {
                $config = require __DIR__ . '/../config/database.php';
                $pdo = new PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                    $config['username'],
                    $config['password']
                );
                $stmt = $pdo->prepare("DELETE FROM files WHERE filename = ?");
                $stmt->execute([$filename]);
            } catch (PDOException $e) {
                // 数据库错误不影响删除
            }
            
            echo json_encode([
                'code' => 0,
                'message' => '删除成功'
            ]);
        } else {
            echo json_encode([
                'code' => 500,
                'message' => '删除失败：无法删除文件'
            ]);
        }
    } elseif ($id > 0) {
        // 通过 ID 删除：从数据库获取文件信息
        $config = require __DIR__ . '/../config/database.php';
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password']
            );
            
            // 获取文件记录
            $stmt = $pdo->prepare("SELECT id, filename, url FROM files WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                echo json_encode(['code' => 404, 'message' => '文件不存在']);
                return;
            }
            
            // 从 URL 提取文件路径
            $urlPath = parse_url($file['url'], PHP_URL_PATH);
            $filePath = 'D:/phpstudy_pro/WWW' . str_replace('/www.gofong.com/', '/', $urlPath);
            
            // 删除物理文件
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // 删除数据库记录
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'code' => 0,
                'message' => '删除成功'
            ]);
            
        } catch (PDOException $e) {
            echo json_encode([
                'code' => 500,
                'message' => '数据库错误：' . $e->getMessage()
            ]);
        }
    } elseif (!empty($path)) {
        // 通过路径删除（旧方式）
        $result = $storage->delete($path);
        
        if ($result['success']) {
            deleteFileFromDatabase($path);
            
            echo json_encode([
                'code' => 0,
                'message' => '删除成功'
            ]);
        } else {
            echo json_encode([
                'code' => 500,
                'message' => '删除失败：' . ($result['error'] ?? '未知错误')
            ]);
        }
    } else {
        echo json_encode(['code' => 400, 'message' => '文件 ID 或路径不能为空']);
    }
}

/**
 * 处理文件列表
 */
function handleList($storage) {
    $subDir = isset($_GET['dir']) ? trim($_GET['dir'], '/') : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    $files = $storage->listFiles($subDir);
    
    // 按类型筛选
    if ($type !== 'all') {
        $files = array_filter($files, function($file) use ($type) {
            return $file['type'] === $type;
        });
    }
    
    // 按时间排序（最新的在前）
    usort($files, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'files' => array_values($files),
            'total' => count($files)
        ]
    ]);
}

/**
 * 处理文件信息
 */
function handleInfo($storage) {
    $path = isset($_GET['path']) ? $_GET['path'] : '';
    
    if (empty($path)) {
        echo json_encode(['code' => 400, 'message' => '文件路径不能为空']);
        return;
    }
    
    $config = $storage->getConfig();
    $filePath = $config['root'] . '/' . $path;
    
    if (!file_exists($filePath)) {
        echo json_encode(['code' => 404, 'message' => '文件不存在']);
        return;
    }
    
    $fileInfo = [
        'name' => basename($filePath),
        'path' => $path,
        'url' => rtrim($config['url'], '/') . '/' . $path,
        'size' => filesize($filePath),
        'mtime' => filemtime($filePath),
        'type' => mime_content_type($filePath)
    ];
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $fileInfo
    ]);
}

/**
 * 保存文件信息到数据库
 */
function saveFileToDatabase($result) {
    try {
        $config = require __DIR__ . '/../config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password']
        );
        
        $sql = "INSERT INTO files (filename, filepath, url, size, type, created_at) 
                VALUES (:filename, :filepath, :url, :size, :type, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':filename' => $result['filename'],
            ':filepath' => $result['path'],
            ':url' => $result['url'],
            ':size' => $result['size'] ?? 0,
            ':type' => pathinfo($result['filename'], PATHINFO_EXTENSION)
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        // 记录日志但不影响上传
        error_log('保存文件到数据库失败：' . $e->getMessage());
        return false;
    }
}

/**
 * 从数据库删除文件记录
 */
function deleteFileFromDatabase($path) {
    try {
        $config = require __DIR__ . '/../config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password']
        );
        
        $sql = "DELETE FROM files WHERE filepath = :filepath OR url = :url";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':filepath' => $path,
            ':url' => $path
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('从数据库删除文件记录失败：' . $e->getMessage());
        return false;
    }
}
