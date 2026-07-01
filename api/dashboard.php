<?php
/**
 * 数据大屏 API - 统计数据
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);  // 关闭错误输出，避免影响 JSON
ini_set('display_errors', 0);

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'Database.php';

check_admin();

$db = Database::getInstance();
$action = $_GET['action'] ?? 'overview';

switch ($action) {
    case 'overview':
        get_overview($db);
        break;
    case 'visit_trend':
        get_visit_trend($db);
        break;
    case 'position_stats':
        get_position_stats($db);
        break;
    case 'customer_stats':
        get_customer_stats($db);
        break;
    case 'get_layout':
        get_dashboard_layout($db);
        break;
    case 'save_layout':
        save_dashboard_layout($db);
        break;
    default:
        json_error('未知操作', 400);
}

/**
 * 获取概览数据
 */
function get_overview($db) {
    // 总访问数
    $total_visits = $db->fetch('SELECT COUNT(*) as count FROM visit_stats')['count'];
    
    // 总用户数（所有用户，包括管理员和普通用户）
    $total_users = $db->fetch('SELECT COUNT(*) as count FROM users')['count'];
    
    // 总客户数
    $total_customers = $db->fetch('SELECT COUNT(*) as count FROM customers')['count'];
    
    // 总岗位数
    $total_positions = $db->fetch('SELECT COUNT(*) as count FROM positions')['count'];
    
    // 已成交客户数
    $closed_customers = $db->fetch('SELECT COUNT(*) as count FROM customers WHERE status = 2')['count'];
    
    // 文章总数
    $total_articles = $db->fetch('SELECT COUNT(*) as count FROM articles')['count'];
    
    // 文章总阅读数
    $total_article_views = $db->fetch('SELECT COALESCE(SUM(view_count), 0) as count FROM articles')['count'];
    
    // 今日访问数
    $today_visits = $db->fetch('SELECT COUNT(*) as count FROM visit_stats WHERE DATE(created_at) = CURDATE()')['count'];
    
    // 今日新增用户
    $today_users = $db->fetch('SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()')['count'];
    
    // 今日新增客户
    $today_customers = $db->fetch('SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) = CURDATE()')['count'];
    
    // 今日新增文章
    $today_articles = $db->fetch('SELECT COUNT(*) as count FROM articles WHERE DATE(created_at) = CURDATE()')['count'];
    
    json_success([
        'total' => [
            'visits' => (int)$total_visits,
            'users' => (int)$total_users,
            'customers' => (int)$total_customers,
            'positions' => (int)$total_positions,
            'closed_customers' => (int)$closed_customers,
            'articles' => (int)$total_articles,
            'article_views' => (int)$total_article_views,
        ],
        'today' => [
            'visits' => (int)$today_visits,
            'users' => (int)$today_users,
            'customers' => (int)$today_customers,
            'articles' => (int)$today_articles,
        ],
        'conversion_rate' => $total_customers > 0 ? round(($closed_customers / $total_customers) * 100, 2) : 0
    ]);
}

/**
 * 获取访问趋势（最近 7 天）
 */
function get_visit_trend($db) {
    // 统计每天的访问次数（使用 created_at 字段）
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM visit_stats 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
    
    $result = $db->fetchAll($sql);
    
    // 补全 7 天数据（确保包含今天）
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $count = 0;
        foreach ($result as $row) {
            if ($row['date'] == $date) {
                $count = (int)$row['count'];
                break;
            }
        }
        $data[] = [
            'date' => $date,
            'count' => $count
        ];
    }
    
    json_success($data);
}

/**
 * 获取岗位统计
 */
function get_position_stats($db) {
    // 按国家统计
    $by_country = $db->fetchAll('SELECT country, COUNT(*) as count FROM positions GROUP BY country ORDER BY count DESC LIMIT 10');
    
    // 按行业统计
    $by_industry = $db->fetchAll('SELECT industry, COUNT(*) as count FROM positions GROUP BY industry ORDER BY count DESC LIMIT 10');
    
    // 按签证类型统计
    $by_visa = $db->fetchAll('SELECT visa_type, COUNT(*) as count FROM positions GROUP BY visa_type ORDER BY count DESC LIMIT 10');
    
    json_success([
        'by_country' => $by_country,
        'by_industry' => $by_industry,
        'by_visa_type' => $by_visa,
    ]);
}

/**
 * 获取客户统计
 */
function get_customer_stats($db) {
    // 按状态统计
    $by_status = $db->fetchAll('SELECT status, COUNT(*) as count FROM customers GROUP BY status');
    
    // 按意向国家统计
    $by_country = $db->fetchAll('SELECT country, COUNT(*) as count FROM customers WHERE country IS NOT NULL GROUP BY country ORDER BY count DESC LIMIT 10');
    
    // 状态映射
    $status_map = [
        0 => '潜在客户',
        1 => '已联系',
        2 => '已成交',
        3 => '已流失'
    ];
    
    $status_data = [];
    foreach ($by_status as $row) {
        $status_data[] = [
            'status' => $status_map[$row['status']] ?? '未知',
            'count' => $row['count']
        ];
    }
    
    json_success([
        'by_status' => $status_data,
        'by_country' => $by_country,
    ]);
}

/**
 * 获取仪表板布局
 */
function get_dashboard_layout($db) {
    if (!isset($_SESSION['user_id'])) {
        json_error('请先登录', 401);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        $layout = $db->fetch('SELECT first_row, second_row FROM dashboard_layout WHERE user_id = ?', [$user_id]);
        
        if ($layout) {
            json_success([
                'first_row' => json_decode($layout['first_row'], true),
                'second_row' => json_decode($layout['second_row'], true)
            ]);
        } else {
            json_success(null);
        }
    } catch (Exception $e) {
        json_error('数据库错误：' . $e->getMessage(), 500);
    }
}

/**
 * 保存仪表板布局
 */
function save_dashboard_layout($db) {
    $user_id = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $first_row = $input['first_row'] ?? [];
    $second_row = $input['second_row'] ?? [];
    
    // 检查是否已存在
    $exists = $db->fetch('SELECT id FROM dashboard_layout WHERE user_id = ?', [$user_id]);
    
    if ($exists) {
        // 更新
        $db->execute('UPDATE dashboard_layout SET first_row = ?, second_row = ?, updated_at = NOW() WHERE user_id = ?', [
            json_encode($first_row, JSON_UNESCAPED_UNICODE),
            json_encode($second_row, JSON_UNESCAPED_UNICODE),
            $user_id
        ]);
    } else {
        // 插入
        $db->execute('INSERT INTO dashboard_layout (user_id, first_row, second_row, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())', [
            $user_id,
            json_encode($first_row, JSON_UNESCAPED_UNICODE),
            json_encode($second_row, JSON_UNESCAPED_UNICODE)
        ]);
    }
    
    json_success(['message' => '布局已保存']);
}
