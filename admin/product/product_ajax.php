<?php
/**
 * 商品管理 AJAX 接口
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] <= 0) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$admin = [
    'id' => $_SESSION['admin_id'] ?? 0,
    'username' => $_SESSION['admin_username'] ?? '',
    'name' => $_SESSION['admin_name'] ?? '',
    'role' => $_SESSION['admin_role'] ?? 'admin'
];

$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 上传视频
if ($action === 'upload_video') {
    try {
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('视频上传失败，请重试');
        }
        
        $file = $_FILES['video'];
        
        // 验证文件类型
        $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('只支持 MP4、WebM 或 OGG 格式的视频');
        }
        
        // 验证文件大小（最大 100MB）
        if ($file['size'] > 100 * 1024 * 1024) {
            throw new Exception('视频大小不能超过 100MB');
        }
        
        // 创建上传目录
        $uploadDir = ROOT_PATH . '/uploads/videos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 生成文件名
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'video_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('保存失败');
        }
        
        // 返回相对路径
        $url = 'uploads/videos/' . $filename;
        
        echo json_encode([
            'success' => true,
            'message' => '上传成功',
            'data' => ['url' => $url]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// 上传视频封面
if ($action === 'upload_video_cover') {
    try {
        if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('封面上传失败，请重试');
        }
        
        $file = $_FILES['cover'];
        
        // 验证文件类型
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('只支持 JPG、PNG 或 GIF 格式');
        }
        
        // 验证文件大小（最大 5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('封面大小不能超过 5MB');
        }
        
        // 创建上传目录
        $uploadDir = ROOT_PATH . '/uploads/videos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 生成文件名
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'cover_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('保存失败');
        }
        
        // 返回相对路径
        $url = 'uploads/videos/' . $filename;
        
        echo json_encode([
            'success' => true,
            'message' => '上传成功',
            'data' => ['url' => $url]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// 上传图片
if ($action === 'upload_image') {
    try {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('图片上传失败，请重试');
        }
        
        $file = $_FILES['image'];
        
        // 验证文件类型
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('只支持 JPG、PNG 或 GIF 格式');
        }
        
        // 验证文件大小（最大 5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('图片大小不能超过 5MB');
        }
        
        // 创建上传目录（按日期）
        $uploadDir = ROOT_PATH . '/uploads/products/' . date('Ymd');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 生成文件名
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('保存失败');
        }
        
        // 返回相对路径
        $url = '/uploads/products/' . date('Ymd') . '/' . $filename;
        
        echo json_encode([
            'success' => true,
            'message' => '上传成功',
            'data' => ['url' => $url]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// 批量上架/下架
if ($action === 'batch_status') {
    try {
        $ids = trim($_POST['ids'] ?? '');
        $status = intval($_POST['status'] ?? 0);
        
        if (empty($ids)) {
            throw new Exception('请选择商品');
        }
        
        $idArray = array_filter(array_map('intval', explode(',', $ids)), function($v) { return $v > 0; });
        if (empty($idArray)) {
            throw new Exception('商品 ID 无效');
        }
        
        $placeholders = implode(',', array_fill(0, count($idArray), '?'));
        $db->query("UPDATE products SET status = ? WHERE id IN ($placeholders)", array_merge([$status], $idArray));
        
        echo json_encode(['success' => true, 'message' => '操作成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 批量删除
if ($action === 'batch_delete') {
    try {
        $ids = trim($_POST['ids'] ?? '');
        
        if (empty($ids)) {
            throw new Exception('请选择商品');
        }
        
        $idArray = array_filter(array_map('intval', explode(',', $ids)), function($v) { return $v > 0; });
        if (empty($idArray)) {
            throw new Exception('商品 ID 无效');
        }
        
        $placeholders = implode(',', array_fill(0, count($idArray), '?'));
        $db->query("DELETE FROM products WHERE id IN ($placeholders)", $idArray);
        
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}



// 获取商品分类列表
if ($action === 'get_categories') {
    $cats = $db->fetchAll("SELECT id, name FROM product_categories ORDER BY sort ASC, id ASC");
    echo json_encode(['success' => true, 'data' => $cats]);
    exit;
}

// 获取商品详情
if ($action === 'get_detail') {
    try {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID 无效');
        $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) throw new Exception('商品不存在');
        // 获取绑定的门店
        $stores = $db->fetchAll("SELECT s.name, s.id FROM store_products sp INNER JOIN stores s ON s.id = sp.store_id WHERE sp.product_id = ?", [$id]);
        $product['bound_stores'] = $stores;
        $product['store_names'] = implode('、', array_column($stores, 'name'));
        echo json_encode(['success' => true, 'data' => $product]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 保存商品（新增/编辑）
if ($action === 'save') {
    try {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) throw new Exception('商品名称不能为空');
        $data = [
            'name' => $name,
            'category_id' => intval($_POST['category_id'] ?? 0),
            'type' => intval($_POST['type'] ?? 1),
            'price' => floatval($_POST['price'] ?? 0),
            'member_price' => floatval($_POST['member_price'] ?? 0),
            'original_price' => floatval($_POST['original_price'] ?? 0),
            'cost_price' => floatval($_POST['cost_price'] ?? 0),
            'stock' => intval($_POST['stock'] ?? 0),
            'weight' => floatval($_POST['weight'] ?? 0),
            'stock_method' => intval($_POST['stock_method'] ?? 1),
            'limit_buy' => intval($_POST['limit_buy'] ?? 0),
            'limit_buy_num' => intval($_POST['limit_buy_num'] ?? 0),
            'sort' => intval($_POST['sort'] ?? 0),
            'status' => intval($_POST['status'] ?? 1),
            'product_code' => trim($_POST['product_code'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'images' => trim($_POST['images'] ?? '[]'),
            'freight_template_id' => intval($_POST['freight_template_id'] ?? 0),
            'selling_points' => trim($_POST['selling_points'] ?? ''),
            'services' => trim($_POST['services'] ?? ''),
            'initial_sales' => intval($_POST['initial_sales'] ?? 0),
            'member_discount' => intval($_POST['member_discount'] ?? 0),
            'video_url' => trim($_POST['video_url'] ?? ''),
            'video_cover' => trim($_POST['video_cover'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        require_once __DIR__ . '/../../includes/SimpleOSSClient.php';
        if ($id > 0) {
            // 更新
            $data['id'] = $id;
            $db->update('products', $data, 'id = :id', ['id' => $id]);
        } else {
            // 新增
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = $db->insert('products', $data);
        }
        echo json_encode(['success' => true, 'message' => '保存成功', 'id' => $id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 单个切换状态（兼容 index.php 调用）
if ($action === 'toggle_status') {
    try {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        if ($id <= 0) throw new Exception('ID 无效');
        $db->query("UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
        echo json_encode(['success' => true, 'message' => '状态更新成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 删除商品
if ($action === 'delete') {
    try {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID 无效');
        $db->query("DELETE FROM products WHERE id = ?", [$id]);
        $db->query("DELETE FROM store_products WHERE product_id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


// 删除服务端文件
if ($action === 'delete_file') {
    try {
        $url = trim($_POST['url'] ?? '');
        if (empty($url)) throw new Exception('URL 不能为空');
        $filePath = ROOT_PATH . '/' . ltrim($url, '/');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => '未知操作']);
