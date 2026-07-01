<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 微信小程序 API - 岗位列表
 * 支持小程序端的数据请求
 */

header('Content-Type: application/json');
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
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$minSalary = isset($_GET['minSalary']) ? floatval($_GET['minSalary']) : 0;
$maxSalary = isset($_GET['maxSalary']) ? floatval($_GET['maxSalary']) : 0;

switch ($action) {
    case 'list':
        getList($pdo, $page, $pageSize, $keyword, $location, $minSalary, $maxSalary);
        break;
    
    case 'detail':
        getDetail($pdo, isset($_GET['id']) ? intval($_GET['id']) : 0);
        break;
    
    case 'view':
        addView($pdo, isset($_GET['id']) ? intval($_GET['id']) : 0);
        break;
    
    case 'hot':
        getHotPositions($pdo, isset($_GET['limit']) ? intval($_GET['limit']) : 4);
        break;
    
    case 'companies':
        getRecommendCompanies($pdo);
        break;
    
    case 'salesmen_list':
        getSalesmenList($pdo, $page, $pageSize, $keyword, $_GET['level'] ?? '');
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
 * 获取岗位列表
 */
function getList($pdo, $page, $pageSize, $keyword, $location, $minSalary, $maxSalary) {
    $offset = ($page - 1) * $pageSize;
    
    // 构建查询条件 - 与后台保持一致，显示所有岗位
    $where = ['1=1']; // 始终为真的条件，避免 WHERE 子句为空
    $params = [];
    
    if ($keyword) {
        $where[] = '(title LIKE :keyword OR description LIKE :keyword)';
        $params[':keyword'] = "%{$keyword}%";
    }
    
    if ($location) {
        $where[] = '(country = :location OR city = :location)';
        $params[':location'] = $location;
    }
    
    // 薪资筛选暂时禁用（数据库使用 salary_range 字符串字段）
    // if ($minSalary > 0) {
    //     $where[] = 'salary_max >= :minSalary';
    //     $params[':minSalary'] = $minSalary;
    // }
    // 
    // if ($maxSalary > 0) {
    //     $where[] = 'salary_min <= :maxSalary';
    //     $params[':maxSalary'] = $maxSalary;
    // }
    
    $whereClause = implode(' AND ', $where);
    
    // 查询总数
    $countSql = "SELECT COUNT(*) as total FROM positions WHERE {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 查询数据
    $sql = "SELECT 
        id, title, category, country, city, industry,
        salary_range,
        education_required, age_min, age_max,
        description, tags,
        created_at
        FROM positions 
        WHERE {$whereClause}
        ORDER BY created_at DESC
        LIMIT {$offset}, {$pageSize}";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化数据
    $list = [];
    foreach ($positions as $pos) {
        $salary = $pos['salary_range'] ?: '面议';
        
        $list[] = [
            'id' => $pos['id'],
            'title' => $pos['title'],
            'salary' => $salary,
            'company' => $pos['industry'] ?: '某公司',
            'location' => $pos['city'] ?: $pos['country'],
            'education' => $pos['education_required'] ?: '学历不限',
            'experience' => '经验不限',
            'tags' => $pos['tags'] ? json_decode($pos['tags'], true) : [],
            'publishTime' => formatTime($pos['created_at'])
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
 * 获取岗位详情
 */
function getDetail($pdo, $id) {
    if ($id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '无效的岗位 ID'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $position = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$position) {
        echo json_encode([
            'code' => 404,
            'message' => '岗位不存在'
        ]);
        return;
    }
    
    // 如果岗位有关联门店，获取门店信息
    $storeInfo = null;
    if (!empty($position['store_id'])) {
        $storeStmt = $pdo->prepare("SELECT id, name, address, city, country, phone, description, status FROM stores WHERE id = :id");
        $storeStmt->bindValue(':id', $position['store_id'], PDO::PARAM_INT);
        $storeStmt->execute();
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($store) {
            $storeInfo = [
                'store_id' => $store['id'],
                'store_name' => $store['name'],
                'store_address' => $store['address'] ?? '',
                'store_city' => $store['city'] ?? '',
                'store_country' => $store['country'] ?? '',
                'store_phone' => $store['phone'] ?? '',
                'store_description' => $store['description'] ?? '',
                'store_status' => (int)($store['status'] ?? 0)
            ];
        }
    }
    
    // 合并岗位数据和门店信息
    $responseData = $position;
    if ($storeInfo) {
        $responseData = array_merge($responseData, $storeInfo);
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $responseData
    ]);
}

/**
 * 增加岗位阅读量
 */
function addView($pdo, $id) {
    if ($id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '无效的岗位 ID'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE positions SET view_count = view_count + 1 WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'code' => 0,
        'message' => '阅读量已更新'
    ]);
}

/**
 * 获取热门岗位
 */
function getHotPositions($pdo, $limit) {
    $stmt = $pdo->prepare("SELECT 
        id, title, country, city, industry,
        salary_range, education_required
        FROM positions 
        WHERE status = 1
        ORDER BY view_count DESC, created_at DESC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $list = [];
    foreach ($positions as $pos) {
        $list[] = [
            'id' => $pos['id'],
            'title' => $pos['title'],
            'salary' => $pos['salary_range'] ?: '面议',
            'company' => $pos['industry'] ?: '某公司',
            'location' => ($pos['city'] ?: $pos['country']) ?: '不限',
            'education' => $pos['education_required'] ?: '学历不限'
        ];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $list
    ]);
}

/**
 * 获取推荐企业（从行业统计）
 */
function getRecommendCompanies($pdo) {
    $stmt = $pdo->prepare("SELECT DISTINCT industry FROM positions WHERE status = 1 AND industry IS NOT NULL AND industry != '' LIMIT 10");
    $stmt->execute();
    $industries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $list = [];
    $companyNames = ['科技', '互联网', '金融', '教育', '医疗', '制造', '服务', '贸易'];
    foreach ($industries as $ind) {
        $list[] = [
            'id' => count($list) + 1,
            'name' => $ind . '集团',
            'industry' => $ind,
            'scale' => (rand(100, 10000)) . '人以上'
        ];
    }
    
    // 如果数据不足，补充一些模拟数据
    while (count($list) < 3) {
        $name = $companyNames[array_rand($companyNames)];
        $list[] = [
            'id' => count($list) + 1,
            'name' => $name . '公司',
            'industry' => '综合',
            'scale' => (rand(100, 10000)) . '人以上'
        ];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => array_slice($list, 0, 3)
    ]);
}

/**
 * 格式化时间
 */
function formatTime($time) {
    $timestamp = strtotime($time);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 172800) {
        return '昨天';
    } else {
        return date('m-d', $timestamp);
    }
}

/**
 * 获取销售人员列表（业务经理）
 */
function getSalesmenList($pdo, $page, $pageSize, $keyword, $level) {
    $offset = ($page - 1) * $pageSize;
    
    // 构建查询条件
    $where = ['1=1'];
    $params = [];
    
    if ($keyword) {
        $where[] = '(name LIKE :keyword OR phone LIKE :keyword OR wechat LIKE :keyword OR store_name LIKE :keyword)';
        $params[':keyword'] = "%{$keyword}%";
    }
    
    if ($level) {
        $where[] = 'level = :level';
        $params[':level'] = $level;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 查询总数
    $countSql = "SELECT COUNT(*) as total FROM salesmen WHERE {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 查询数据
    $sql = "SELECT 
        id, name, phone, wechat, level, store_name, avatar,
        sales_amount, deal_count, status, remark,
        created_at
        FROM salesmen 
        WHERE {$whereClause}
        ORDER BY sales_amount DESC, created_at DESC
        LIMIT {$offset}, {$pageSize}";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $salesmen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化数据
    $list = [];
    foreach ($salesmen as $salesman) {
        // 处理头像 URL
        $avatar = '';
        if (!empty($salesman['avatar'])) {
            $avatar = $salesman['avatar'];
            // 如果是相对路径，添加域名
            if (strpos($avatar, '/uploads/') === 0) {
                $avatar = BASE_URL . $avatar;
            } elseif (strpos($avatar, '/www.gofong.com/') === 0) {
                $avatar = 'https:' . $avatar;
            }
        }
        
        // 从 sales 表自动统计成交量和销售额
        $stmtStats = $pdo->prepare("SELECT 
            COALESCE(SUM(amount), 0) as total_sales,
            COUNT(*) as total_deals
            FROM sales 
            WHERE salesman_id = :salesman_id AND status = '已成交'");
        $stmtStats->execute([':salesman_id' => $salesman['id']]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        $list[] = [
            'id' => intval($salesman['id']),
            'name' => $salesman['name'],
            'phone' => $salesman['phone'],
            'wechat' => $salesman['wechat'] ?? '',
            'level' => $salesman['level'] ?? '小白',
            'store_name' => $salesman['store_name'] ?? '',
            'avatar' => $avatar,
            'sales_amount' => floatval($stats['total_sales'] ?? 0),
            'deal_count' => intval($stats['total_deals'] ?? 0),
            'status' => $salesman['status'] ?? '在职',
            'remark' => $salesman['remark'] ?? ''
        ];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $list,
        'total' => intval($total),
        'page' => $page,
        'page_size' => $pageSize
    ]);
}

/**
 * 添加收藏
 */
function handle_favorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误']);
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    $type = isset($_GET['type']) ? $_GET['type'] : 'position';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    // 检查是否已收藏
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND position_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['code' => 0, 'message' => '已收藏']);
        return;
    }
    
    // 添加收藏
    $stmt = $pdo->prepare('INSERT INTO user_favorites (user_id, type, position_id, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, $type, $id]);
    
    echo json_encode(['code' => 0, 'message' => '收藏成功']);
}

/**
 * 取消收藏
 */
function handle_unfavorite($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['code' => 405, 'message' => '请求方法错误']);
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    $type = isset($_GET['type']) ? $_GET['type'] : 'position';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    $stmt = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND type = ? AND position_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    echo json_encode(['code' => 0, 'message' => '已取消收藏']);
}

/**
 * 检查收藏状态
 */
function handle_check_favorite($pdo) {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'position';
    
    if (!$user_id || !$id) {
        echo json_encode(['code' => 400, 'message' => '缺少参数']);
        return;
    }
    
    $stmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND type = ? AND position_id = ?');
    $stmt->execute([$user_id, $type, $id]);
    
    $is_favorite = (bool)$stmt->fetch();
    
    echo json_encode([
        'code' => 0,
        'is_favorite' => $is_favorite
    ]);
}
