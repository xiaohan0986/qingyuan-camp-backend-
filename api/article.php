<?php
/**
 * 文章管理 API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';

// 加载数据库配置并创建连接
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handle_list($pdo);
        break;
    case 'detail':
        handle_detail($pdo);
        break;
    case 'view':
        handle_view($pdo);
        break;
    case 'create':
        handle_create($pdo);
        break;
    case 'update':
        handle_update($pdo);
        break;
    case 'delete':
        handle_delete($pdo);
        break;
    case 'set_status':
        handle_set_status($pdo);
        break;
    case 'categories':
        handle_categories($pdo);
        break;
    case 'authors':
        handle_authors($pdo);
        break;
    case 'add_category':
        handle_add_category($pdo);
        break;
    case 'update_category':
        handle_update_category($pdo);
        break;
    case 'delete_category':
        handle_delete_category($pdo);
        break;
    case 'toggle_recommend':
        handle_toggle_recommend($pdo);
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
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '未知操作'], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取文章列表
 */
function handle_list($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $page_size = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
    $offset = ($page - 1) * $page_size;
    
    $keyword = $_GET['keyword'] ?? '';
    $author = $_GET['author'] ?? '';
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $where = ['1=1'];
    $params = [];
    
    if ($keyword) {
        $where[] = '(title LIKE ? OR summary LIKE ? OR content LIKE ?)';
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
    }
    
    if ($author) {
        $where[] = 'author = ?';
        $params[] = $author;
    }
    
    if ($category) {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = (int)$status;
    }
    
    $where_sql = implode(' AND ', $where);
    
    // 获取总数
    $count_sql = "SELECT COUNT(*) as count FROM articles WHERE {$where_sql}";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch()['count'];
    
    // 获取数据
    $sql = "SELECT * FROM articles WHERE {$where_sql} ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    // 强制 LIMIT/OFFSET 为整数类型，避免 PDO 默认绑定为字符串导致 SQL 错误
    $bindParams = array_merge($params, [$page_size, $offset]);
    foreach ($bindParams as $i => $val) {
        $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $list = $stmt->fetchAll();
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $page_size,
                'total' => $total,
                'total_pages' => ceil($total / $page_size)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取文章详情
 */
function handle_detail($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        http_response_code(404);
        echo json_encode(['code' => 404, 'message' => '文章不存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    echo json_encode(['code' => 200, 'message' => 'success', 'data' => $article], JSON_UNESCAPED_UNICODE);
}

/**
 * 增加文章阅读量
 */
function handle_view($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('UPDATE articles SET view_count = view_count + 1 WHERE id = ?');
    $stmt->execute([$id]);
    
    echo json_encode(['code' => 0, 'message' => '阅读量已更新'], JSON_UNESCAPED_UNICODE);
}

/**
 * 创建文章
 */
function handle_create($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (!$title || !$content) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '标题和内容不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // cover_image: store the full JSON array of all cover URLs
    $cover = $_POST['cover_image'] ?? '';
    
    try {
        // Include tags column in INSERT
        $sql = "INSERT INTO articles (title, category_id, cover, summary, content, author, status, tags, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            (int)($_POST['category'] ?? 0),
            $cover,
            $_POST['summary'] ?? '',
            $content,
            $_POST['author'] ?? '',
            (int)($_POST['status'] ?? 1),
            $_POST['tags'] ?? ''
        ]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode(['code' => 200, 'message' => '创建成功', 'data' => ['id' => $id]], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '创建失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 更新文章
 */
function handle_update($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (!$title || !$content) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '标题和内容不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // cover_image: store the full JSON array of all cover URLs
    $cover = $_POST['cover_image'] ?? '';
    
    try {
        // Use correct column names from the actual DB schema
        $sql = "UPDATE articles SET 
                title = ?, category_id = ?, cover = ?, summary = ?, 
                content = ?, author = ?, tags = ?, status = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            (int)($_POST['category'] ?? 0),
            $cover,
            $_POST['summary'] ?? '',
            $content,
            $_POST['author'] ?? '',
            $_POST['tags'] ?? '',
            (int)($_POST['status'] ?? 1),
            $id
        ]);
        
        echo json_encode(['code' => 200, 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '更新失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 删除文章
 */
function handle_delete($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    
    echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 设置文章状态
 */
function handle_set_status($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('UPDATE articles SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$status, $id]);
    
    echo json_encode(['code' => 200, 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取作者列表
 */
function handle_authors($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT author FROM articles WHERE author IS NOT NULL AND author != '' ORDER BY author");
        $authors = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['code' => 200, 'message' => 'success', 'data' => $authors], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '获取作者失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 获取分类列表
 */
function handle_categories($pdo) {
    $allCategories = [];
    $existingCategoryNames = [];
    
    // 检查分类表是否存在
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'article_categories'");
        if ($stmt->rowCount() > 0) {
            // 从分类表获取，带文章数量统计
            $stmt = $pdo->prepare('
                SELECT 
                    c.id,
                    c.name,
                    c.color,
                    c.sort_order,
                    COUNT(a.id) as article_count
                FROM article_categories c
                LEFT JOIN articles a ON c.name = a.category
                GROUP BY c.id, c.name, c.color, c.sort_order
                ORDER BY c.sort_order ASC, c.id ASC
            ');
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categories as $cat) {
                $allCategories[] = $cat;
                $existingCategoryNames[] = $cat['name'];
            }
        }
    } catch (Exception $e) {
        // 表不存在，继续下面的逻辑
    }
    
    // 获取 articles 表中所有不同的 category
    $stmt = $pdo->prepare('SELECT DISTINCT category FROM articles WHERE category IS NOT NULL AND category != "" ORDER BY category ASC');
    $stmt->execute();
    $articleCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 添加在文章中使用但不在分类表中的分类
    foreach ($articleCategories as $catName) {
        if (!in_array($catName, $existingCategoryNames)) {
            // 统计该分类的文章数量
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category = ?');
            $stmt->execute([$catName]);
            $count = $stmt->fetch()['count'];
            
            $allCategories[] = [
                'id' => 0,
                'name' => $catName,
                'color' => '#d9d9d9', // 默认灰色
                'sort_order' => 999,
                'article_count' => $count, // 实际文章数量
                'is_legacy' => true // 标记为旧分类
            ];
        }
    }
    
    // 重新排序：分类表的在前，旧分类在后
    usort($allCategories, function($a, $b) {
        if ($a['sort_order'] != $b['sort_order']) {
            return $a['sort_order'] - $b['sort_order'];
        }
        return ($a['name'] ?? '') <=> ($b['name'] ?? '');
    });
    
    echo json_encode(['success' => true, 'data' => $allCategories]);
}

/**
 * 添加分类
 */
function handle_add_category($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $color = trim($input['color'] ?? '#667eea');
    
    if (!$name) {
        echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
        return;
    }
    
    try {
        // 检查是否已存在
        $stmt = $pdo->prepare('SELECT id FROM article_categories WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '分类已存在']);
            return;
        }
        
        // 获取最大排序值
        $stmt = $pdo->query('SELECT MAX(sort_order) as max_order FROM article_categories');
        $row = $stmt->fetch();
        $sort_order = ($row['max_order'] ?? 0) + 1;
        
        // 插入新分类
        $stmt = $pdo->prepare('INSERT INTO article_categories (name, color, sort_order) VALUES (?, ?, ?)');
        $stmt->execute([$name, $color, $sort_order]);
        
        echo json_encode(['success' => true, 'message' => '添加成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
    }
}

/**
 * 更新分类
 */
function handle_update_category($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $color = trim($input['color'] ?? '#667eea');
    
    if (!$id || !$name) {
        echo json_encode(['success' => false, 'message' => '参数错误']);
        return;
    }
    
    try {
        // 检查名称是否与其他分类重复
        $stmt = $pdo->prepare('SELECT id FROM article_categories WHERE name = ? AND id != ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '分类名称已存在']);
            return;
        }
        
        // 更新分类
        $stmt = $pdo->prepare('UPDATE article_categories SET name = ?, color = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $color, $id]);
        
        // 同步更新 articles 表中的分类名称
        $stmt = $pdo->prepare('UPDATE articles SET category = ? WHERE category = (SELECT name FROM article_categories WHERE id = ?)');
        $stmt->execute([$name, $id]);
        
        echo json_encode(['success' => true, 'message' => '更新成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
    }
}

/**
 * 删除分类
 */
function handle_delete_category($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '请求方法错误']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => '参数错误']);
        return;
    }
    
    try {
        // 获取分类名称
        $stmt = $pdo->prepare('SELECT name FROM article_categories WHERE id = ?');
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            echo json_encode(['success' => false, 'message' => '分类不存在']);
            return;
        }
        
        // 检查是否有文章使用该分类
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category = ?');
        $stmt->execute([$category['name']]);
        $row = $stmt->fetch();
        
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => '该分类下存在 ' . $row['count'] . ' 篇文章，无法删除']);
            return;
        }
        
        // 删除分类
        $stmt = $pdo->prepare('DELETE FROM article_categories WHERE id = ?');
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
    }
}

/**
 * 切换文章推荐状态
 */
function handle_toggle_recommend($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $id = (int)($_POST['id'] ?? 0);
    $isRecommend = (int)($_POST['is_recommend'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查 is_recommend 字段是否存在，不存在则添加
    try {
        $stmt = $pdo->prepare("ALTER TABLE articles ADD COLUMN IF NOT EXISTS is_recommend TINYINT(1) DEFAULT 0 COMMENT '是否推荐'");
        $stmt->execute();
    } catch (Exception $e) {
        // 字段可能已存在，忽略错误
    }
    
    $stmt = $pdo->prepare('UPDATE articles SET is_recommend = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$isRecommend, $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id, 'is_recommend' => $isRecommend]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 添加收藏
 */
function handle_favorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    $article_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    
    if (!$user_id || !$article_id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查是否已收藏
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND article_id = ?');
    $stmt->execute([$user_id, 'article', $article_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['code' => 200, 'message' => '已收藏'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 添加收藏
    $stmt = $pdo->prepare('INSERT INTO user_favorites (user_id, type, article_id, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, 'article', $article_id]);
    
    echo json_encode(['code' => 200, 'message' => '收藏成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 取消收藏
 */
function handle_unfavorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    $article_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    
    if (!$user_id || !$article_id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND type = ? AND article_id = ?');
    $stmt->execute([$user_id, 'article', $article_id]);
    
    echo json_encode(['code' => 200, 'message' => '已取消收藏'], JSON_UNESCAPED_UNICODE);
}

/**
 * 检查收藏状态
 */
function handle_check_favorite($pdo) {
    $user_id = (int)($_GET['user_id'] ?? 0);
    $article_id = (int)($_GET['id'] ?? 0);
    
    if (!$user_id || !$article_id) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '缺少参数'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND article_id = ?');
    $stmt->execute([$user_id, 'article', $article_id]);
    
    $is_favorite = (bool)$stmt->fetch();
    
    echo json_encode([
        'code' => 200,
        'is_favorite' => $is_favorite
    ], JSON_UNESCAPED_UNICODE);
}
