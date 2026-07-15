<?php
/**
 * 装修 - 首页背景图 API
 * 
 * 功能：
 * 1. GET  - 获取所有背景图列表
 * 2. POST (upload) - 上传背景图并存入 OSS
 * 3. POST (set_active) - 设置某张图为当前首页背景
 * 4. POST (delete) - 删除背景图（OSS + DB）
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/oss.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// 检查登录
$admin = Auth::user();
if (empty($admin['id'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// 自动建表（仅首次访问时）
ensureTableExists($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleList($conn);
        break;
    case 'upload':
        handleUpload($conn);
        break;
    case 'set_active':
        handleSetActive($conn);
        break;
    case 'delete':
        handleDelete($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
}

/**
 * 确保 decoration_bg_images 表存在
 */
function ensureTableExists($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'decoration_bg_images'");
    if ($result->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `decoration_bg_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `image_url` VARCHAR(500) NOT NULL COMMENT 'OSS/完整图片URL',
            `file_key` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'OSS file key',
            `is_active` TINYINT(1) DEFAULT 0 COMMENT '是否当前选中',
            `sort_order` INT DEFAULT 0 COMMENT '排序',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修-首页背景图'";
        $conn->query($sql);
    }
}

/**
 * 获取所有背景图
 */
function handleList($conn) {
    $rows = fetchAll($conn, "SELECT * FROM decoration_bg_images ORDER BY sort_order ASC, id DESC");
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
}

/**
 * 上传背景图
 */
function handleUpload($conn) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
        return;
    }

    $file = $_FILES['file'];
    
    // 验证图片类型
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMime)) {
        echo json_encode(['success' => false, 'message' => '仅支持 JPG/PNG/GIF/WebP 格式图片']);
        return;
    }

    // 限制大小 10MB
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => '图片大小不能超过 10MB']);
        return;
    }

    // 生成文件名
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $subDir  = date('Y-m');
    $fileName = 'decoration_bg_' . time() . '_' . uniqid() . '.' . $ext;
    
    $uploadBase = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
    $localDir = $uploadBase . DIRECTORY_SEPARATOR . 'decoration' . DIRECTORY_SEPARATOR . $subDir;
    if (!is_dir($localDir)) {
        mkdir($localDir, 0755, true);
    }
    $localPath = $localDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $localPath)) {
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
        return;
    }

    // 上传 OSS
    $imageUrl = '';
    $fileKey  = '';
    $ossEnabled = defined('OSS_ENABLED') && OSS_ENABLED;

    if ($ossEnabled) {
        try {
            require_once __DIR__ . '/../../includes/SimpleOSSClient.php';
            $ossClient = SimpleOSSClient::getInstance();
            $objectName = 'decoration/bg/' . $subDir . '/' . $fileName;
            $ossUrl = $ossClient->uploadFile($localPath, $objectName);
            $imageUrl = $ossUrl;
            $fileKey  = $objectName;
        } catch (Exception $e) {
            error_log('背景图 OSS 上传失败: ' . $e->getMessage());
        }
    }

    // 没走 OSS 或 OSS 失败时用本地 URL
    if (empty($imageUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $imageUrl = $protocol . '://' . $host . '/uploads/decoration/' . $subDir . '/' . $fileName;
    }

    // 写入 DB
    $stmt = $conn->prepare("INSERT INTO decoration_bg_images (image_url, file_key, is_active, sort_order) VALUES (?, ?, 0, 0)");
    $stmt->bind_param('ss', $imageUrl, $fileKey);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => '上传成功',
        'data'    => [
            'id'         => $id,
            'image_url'  => $imageUrl,
            'file_key'   => $fileKey,
            'is_active'  => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 设置选中背景图
 */
function handleSetActive($conn) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '参数错误']);
        return;
    }

    $conn->query("UPDATE decoration_bg_images SET is_active = 0");
    $stmt = $conn->prepare("UPDATE decoration_bg_images SET is_active = 1 WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => '设置成功']);
}

/**
 * 删除背景图
 */
function handleDelete($conn) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '参数错误']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM decoration_bg_images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => '记录不存在']);
        return;
    }

    // 尝试删除 OSS 文件
    if (!empty($row['file_key'])) {
        try {
            require_once __DIR__ . '/../../includes/SimpleOSSClient.php';
            $ossClient = SimpleOSSClient::getInstance();
            if ($ossClient->isAvailable()) {
                $ossClient->deleteFile($row['file_key']);
            }
        } catch (Exception $e) {
            error_log('删除 OSS 文件失败: ' . $e->getMessage());
        }
    }

    $stmt = $conn->prepare("DELETE FROM decoration_bg_images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => '删除成功']);
}

/**
 * fetchAll 辅助（兼容不同 Database 版本）
 */
function fetchAll($conn, $sql, $params = []) {
    $rows = [];
    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : 's';
            $values[] = $p;
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    }
    return $rows;
}
