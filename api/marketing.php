<?php
/**
 * 营销获客 API
 */
header('Content-Type: application/json; charset=utf-8');

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'Database.php';

check_admin();

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        marketing_list($db);
        break;
    case 'detail':
        marketing_detail($db);
        break;
    case 'create':
        marketing_create($db);
        break;
    case 'update':
        marketing_update($db);
        break;
    case 'delete':
        marketing_delete($db);
        break;
    case 'toggle_status':
        marketing_toggle_status($db);
        break;
    case 'logs':
        marketing_logs($db);
        break;
    default:
        json_error('鏈煡鎿嶄綔', 400);
}

/**
 * 钀ラ攢閰嶇疆鍒楄〃
 */
function marketing_list($db) {
    $list = $db->fetchAll('SELECT * FROM marketing_configs ORDER BY created_at DESC');
    json_success($list);
}

/**
 * 钀ラ攢閰嶇疆璇︽儏
 */
function marketing_detail($db) {
    $id = (int)get_param('id', 0, 'GET');
    if (!$id) json_error('缂哄皯閰嶇疆 ID', 400);
    
    $config = $db->fetch('SELECT * FROM marketing_configs WHERE id = ?', [$id]);
    if (!$config) json_error('閰嶇疆涓嶅瓨鍦?, 404);
    
    json_success($config);
}

/**
 * 鍒涘缓钀ラ攢閰嶇疆
 */
function marketing_create($db) {
    $params = get_all_params('POST');
    
    $required = ['name'];
    $missing = validate_required($params, $required);
    if (!empty($missing)) {
        json_error('缂哄皯蹇呭～瀛楁锛? . implode(', ', $missing), 400);
    }
    
    $sql = "INSERT INTO marketing_configs (
        name, platform, keywords, comment_templates, like_probability, 
        comment_probability, run_duration, switch_interval, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $db->execute($sql, [
        $params['name'],
        $params['platform'] ?? null,
        $params['keywords'] ?? null,  // JSON 瀛楃涓?        $params['comment_templates'] ?? null,  // JSON 瀛楃涓?        $params['like_probability'] ?? 0,
        $params['comment_probability'] ?? 0,
        $params['run_duration'] ?? 60,
        $params['switch_interval'] ?? 30,
        $params['status'] ?? 0
    ]);
    
    json_success(['id' => $db->lastInsertId()], '鍒涘缓鎴愬姛');
}

/**
 * 鏇存柊钀ラ攢閰嶇疆
 */
function marketing_update($db) {
    $id = (int)get_param('id', 0, 'POST');
    if (!$id) json_error('缂哄皯閰嶇疆 ID', 400);
    
    $config = $db->fetch('SELECT id FROM marketing_configs WHERE id = ?', [$id]);
    if (!$config) json_error('閰嶇疆涓嶅瓨鍦?, 404);
    
    $params = get_all_params('POST');
    unset($params['id']);
    
    $fields = [];
    $values = [];
    
    $allowed_fields = ['name', 'platform', 'keywords', 'comment_templates', 'like_probability', 
                       'comment_probability', 'run_duration', 'switch_interval', 'status'];
    
    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $params)) {
            $fields[] = "{$field} = ?";
            $values[] = $params[$field];
        }
    }
    
    if (empty($fields)) json_error('娌℃湁瑕佹洿鏂扮殑瀛楁', 400);
    
    $values[] = $id;
    $sql = "UPDATE marketing_configs SET " . implode(', ', $fields) . " WHERE id = ?";
    $db->execute($sql, $values);
    
    json_success(null, '鏇存柊鎴愬姛');
}

/**
 * 鍒犻櫎钀ラ攢閰嶇疆
 */
function marketing_delete($db) {
    $id = (int)get_param('id', 0, 'POST');
    if (!$id) json_error('缂哄皯閰嶇疆 ID', 400);
    
    $db->execute('DELETE FROM marketing_configs WHERE id = ?', [$id]);
    json_success(null, '鍒犻櫎鎴愬姛');
}

/**
 * 鍒囨崲钀ラ攢閰嶇疆鐘舵€? */
function marketing_toggle_status($db) {
    $id = (int)get_param('id', 0, 'POST');
    if (!$id) json_error('缂哄皯閰嶇疆 ID', 400);
    
    $config = $db->fetch('SELECT status FROM marketing_configs WHERE id = ?', [$id]);
    if (!$config) json_error('閰嶇疆涓嶅瓨鍦?, 404);
    
    $new_status = $config['status'] == 1 ? 0 : 1;
    $db->execute('UPDATE marketing_configs SET status = ? WHERE id = ?', [$new_status, $id]);
    
    json_success(['status' => $new_status], '鐘舵€佸凡鏇存柊');
}

/**
 * 钀ラ攢鏃ュ織
 */
function marketing_logs($db) {
    $page = max(1, (int)(get_param('page', 1, 'GET')));
    $page_size = min(100, max(1, (int)(get_param('page_size', 50, 'GET'))));
    
    $where = ['1=1'];
    $params = [];
    
    $config_id = get_param('config_id', '', 'GET');
    if ($config_id) {
        $where[] = 'config_id = ?';
        $params[] = $config_id;
    }
    
    $status = get_param('status', '', 'GET');
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    
    $where_sql = implode(' AND ', $where);
    $total = $db->fetch("SELECT COUNT(*) as count FROM marketing_logs WHERE {$where_sql}", $params)['count'];
    $pagination = get_pagination($total, $page, $page_size);
    
    $sql = "SELECT * FROM marketing_logs 
            WHERE {$where_sql} 
            ORDER BY created_at DESC 
            LIMIT {$pagination['offset']}, {$pagination['page_size']}";
    
    $list = $db->fetchAll($sql, $params);
    
    json_success([
        'list' => $list,
        'pagination' => $pagination
    ]);
}
