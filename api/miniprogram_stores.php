<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 微信小程序 API - 门店列表
 * 支持小程序端的数据请求
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 数据库配置
require_once __DIR__ . '/../config/database.php';

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败',
        'error' => $e->getMessage()
    ]);
    exit;
}

// 获取请求参数
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 10;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$country = isset($_GET['country']) ? trim($_GET['country']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';

switch ($action) {
    case 'list':
        getList($pdo, $page, $pageSize, $keyword, $country, $city);
        break;
    
    case 'detail':
        // 修复：正确处理 ID 参数，支持字符串转数字
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $id = is_numeric($id) ? intval($id) : 0;
        getDetail($pdo, $id);
        break;
    
    case 'favorite':
        handle_favorite($pdo);
        break;
    
    case 'unfavorite':
        handle_unfavorite($pdo);
        break;
    
    case 'check_favorite':
        handle_check_favorite($pdo);
        break;
    
    default:
        echo json_encode([
            'code' => 400,
            'message' => '未知操作'
        ]);
}

/**
 * 获取门店列表
 */
function getList($pdo, $page, $pageSize, $keyword, $country, $city) {
    $offset = ($page - 1) * $pageSize;
    
    // 构建查询条件
    $where = ['1=1'];
    $params = [];
    
    if ($keyword) {
        $where[] = '(name LIKE :keyword OR address LIKE :keyword)';
        $params[':keyword'] = "%{$keyword}%";
    }
    
    if ($country) {
        $where[] = 'country = :country';
        $params[':country'] = $country;
    }
    
    if ($city) {
        $where[] = 'city = :city';
        $params[':city'] = $city;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 查询总数
    $countSql = "SELECT COUNT(*) as total FROM stores WHERE {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 查询数据
    $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM positions p WHERE p.store_id = s.id AND p.status = 1) as position_count
            FROM stores s
            WHERE {$whereClause}
            ORDER BY s.sort ASC, s.id DESC
            LIMIT {$offset}, {$pageSize}";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化数据
    $list = [];
    foreach ($stores as $store) {
        // 处理头像路径
        $avatarUrl = '/images/store_avatar_1.png';
        if (!empty($store['avatar'])) {
            if (strpos($store['avatar'], 'http') === 0) {
                $avatarUrl = $store['avatar'];
            } else {
                $avatarUrl = str_replace('/www.gofong.com/', '' . BASE_URL . '/', $store['avatar']);
            }
        }
        
        $list[] = [
            'id' => $store['id'],
            'name' => $store['name'] ?: '未知门店',
            'phone' => $store['phone'] ?: '',
            'country' => $store['country'] ?: '',
            'city' => $store['city'] ?: '',
            'address' => $store['address'] ?: '',
            'avatar' => $avatarUrl,
            'description' => $store['description'] ?: '',
            'positionCount' => (int)$store['position_count'],
            'status' => (int)$store['status'],
            'distance' => '' // 暂时不计算距离
        ];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'list' => $list,
            'total' => intval($total),
            'page' => $page,
            'pageSize' => $pageSize,
            'hasMore' => ($page * $pageSize) < $total
        ]
    ]);
}

/**
 * 获取门店详情
 */
function getDetail($pdo, $id) {
    if ($id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '无效的门店 ID'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM stores WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo json_encode([
            'code' => 404,
            'message' => '门店不存在'
        ]);
        return;
    }
    
    // 获取在招岗位数量
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM positions WHERE store_id = :id AND status = 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $positionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // 获取在招岗位列表
    $stmt = $pdo->prepare("SELECT id, title, salary_range FROM positions WHERE store_id = :id AND status = 1 LIMIT 10");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $positionList = [];
    foreach ($positions as $pos) {
        $positionList[] = [
            'id' => $pos['id'],
            'title' => $pos['title'],
            'salary' => $pos['salary_range'] ?: '面议'
        ];
    }
    
    // 处理头像路径
    $avatarUrl = '';
    if (!empty($store['avatar'])) {
        if (strpos($store['avatar'], 'http') === 0) {
            $avatarUrl = $store['avatar'];
        } else {
            // 数据库路径是 /www.gofong.com/uploads/...，需要替换为 https://www.gofong.com/uploads/...
            $avatarUrl = str_replace('/www.gofong.com/', 'https://www.gofong.com/', $store['avatar']);
        }
    }
    
    // 处理照片路径（数据库存储的是文件名，需要组合完整路径）
    $photosUrl = [];
    if (!empty($store['environment_images'])) {
        $envImages = json_decode($store['environment_images'], true);
        if (is_array($envImages)) {
            foreach ($envImages as $img) {
                // 如果已经是完整 URL，直接使用
                if (strpos($img, 'http') === 0) {
                    $photosUrl[] = $img;
                }
                // 如果是文件名（不包含路径），组合完整路径
                else if (strpos($img, '/') === false) {
                    $photosUrl[] = 'https://www.gofong.com/uploads/store_environments/' . $img;
                }
                // 如果是旧格式的路径（包含 /uploads/）
                else {
                    $photosUrl[] = str_replace('/www.gofong.com/', 'https://www.gofong.com/', $img);
                }
            }
        }
    }
    
    // 调试：输出原始数据
    error_log('Store data: ' . print_r($store, true));
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'id' => $store['id'],
            'name' => $store['name'],
            'phone' => $store['phone'],
            'country' => $store['country'],
            'city' => $store['city'],
            'address' => $store['address'],
            'description' => $store['description'],
            'avatar' => $avatarUrl,
            'photos' => $photosUrl,
            'positionCount' => (int)$positionCount,
            'status' => (int)$store['status'],
            'positions' => $positionList,
            'latitude' => isset($store['latitude']) ? $store['latitude'] : 'NOT_IN_DB',
            'longitude' => isset($store['longitude']) ? $store['longitude'] : 'NOT_IN_DB'
        ]
    ]);
}

/**
 * 添加门店收藏
 */
function handle_favorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误']);
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    $type = isset($_GET['type']) ? $_GET['type'] : 'store';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    // 检查是否已收藏
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND store_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['code' => 0, 'message' => '已收藏']);
        return;
    }
    
    // 添加收藏
    $stmt = $pdo->prepare('INSERT INTO user_favorites (user_id, type, store_id, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, $type, $id]);
    
    echo json_encode(['code' => 0, 'message' => '收藏成功']);
}

/**
 * 取消门店收藏
 */
function handle_unfavorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误']);
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    $type = isset($_GET['type']) ? $_GET['type'] : 'store';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    $stmt = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND type = ? AND store_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    echo json_encode(['code' => 0, 'message' => '已取消收藏']);
}

/**
 * 检查门店收藏状态
 */
function handle_check_favorite($pdo) {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'store';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND store_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    $is_favorite = (bool)$stmt->fetch();
    
    echo json_encode([
        'code' => 0,
        'is_favorite' => $is_favorite
    ]);
}
