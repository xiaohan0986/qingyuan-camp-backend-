<?php
/**
 * 用户收藏 API
 * 获取用户的收藏列表（岗位、文章、门店）
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'list':
        handle_list($pdo);
        break;
    
    case 'delete':
        handle_delete($pdo);
        break;
    
    default:
        echo json_encode([
            'code' => 400,
            'message' => '未知操作'
        ]);
}

/**
 * 获取收藏列表
 */
function handle_list($pdo) {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'position';
    
    if (!$user_id) {
        echo json_encode(['code' => 400, 'message' => '缺少用户 ID']);
        return;
    }
    
    $list = [];
    
    try {
        if ($type === 'position') {
            // 获取岗位收藏
            $stmt = $pdo->prepare('
                SELECT uf.id as favorite_id, uf.created_at, 
                       p.id, p.title, p.salary_range, p.city, p.country, p.industry
                FROM user_favorites uf
                LEFT JOIN positions p ON uf.position_id = p.id
                WHERE uf.user_id = ? AND uf.type = ?
                ORDER BY uf.created_at DESC
            ');
            $stmt->execute([$user_id, 'position']);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($type === 'article') {
            // 获取文章收藏
            $stmt = $pdo->prepare('
                SELECT uf.id as favorite_id, uf.created_at,
                       a.id, a.title, a.summary, a.category, a.created_at as publish_time
                FROM user_favorites uf
                LEFT JOIN articles a ON uf.article_id = a.id
                WHERE uf.user_id = ? AND uf.type = ?
                ORDER BY uf.created_at DESC
            ');
            $stmt->execute([$user_id, 'article']);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($type === 'store') {
            // 获取门店收藏
            $stmt = $pdo->prepare('
                SELECT uf.id as favorite_id, uf.created_at,
                       s.id, s.name, s.address, s.city, s.country, s.phone
                FROM user_favorites uf
                LEFT JOIN stores s ON uf.store_id = s.id
                WHERE uf.user_id = ? AND uf.type = ?
                ORDER BY uf.created_at DESC
            ');
            $stmt->execute([$user_id, 'store']);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'code' => 500,
            'message' => '查询失败：' . $e->getMessage()
        ]);
        return;
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $list
    ]);
}

/**
 * 删除收藏
 */
function handle_delete($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误']);
        return;
    }
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'position';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    $field = $type . '_id';
    $stmt = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND type = ? AND ' . $field . ' = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    echo json_encode([
        'code' => 0,
        'message' => '已取消收藏'
    ]);
}
