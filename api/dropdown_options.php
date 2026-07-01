<?php
/**
 * 下拉选项管理 API
 * 从 dropdown_options 表读取/添加/删除选项数据
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/../bootstrap.php';
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
    // 显式设置连接字符集，确保中文正常显示
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'list':
            getOptions($pdo);
            break;
        case 'add':
            addOption($pdo);
            break;
        case 'delete':
            deleteOption($pdo);
            break;
        case 'update_sort':
            updateSort($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '无效的操作']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
}

/**
 * 获取选项列表
 */
function getOptions($pdo) {
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '请指定分类']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, value, label, sort_order, is_default, status, created_at, updated_at
        FROM dropdown_options
        WHERE category = :category
        ORDER BY sort_order ASC, id ASC
    ");
    
    $stmt->execute([':category' => $category]);
    $options = $stmt->fetchAll();
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $options
    ]);
}

/**
 * 添加选项
 */
function addOption($pdo) {
    $category = $_POST['category'] ?? '';
    $value = $_POST['value'] ?? '';
    $label = $_POST['label'] ?? $value;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_default = (int)($_POST['is_default'] ?? 0);
    $status = (int)($_POST['status'] ?? 1);
    
    if (empty($category) || empty($value)) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '分类和值不能为空']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO dropdown_options (category, value, label, sort_order, is_default, status, created_at, updated_at)
        VALUES (:category, :value, :label, :sort_order, :is_default, :status, NOW(), NOW())
    ");
    
    $stmt->execute([
        ':category' => $category,
        ':value' => $value,
        ':label' => $label,
        ':sort_order' => $sort_order,
        ':is_default' => $is_default,
        ':status' => $status
    ]);
    
    $newId = $pdo->lastInsertId();
    
    echo json_encode([
        'code' => 200,
        'message' => '添加成功',
        'data' => ['id' => $newId]
    ]);
}

/**
 * 删除选项
 */
function deleteOption($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '无效的 ID']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM dropdown_options WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '删除成功'
    ]);
}

/**
 * 更新排序
 */
function updateSort($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '无效的 ID']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE dropdown_options SET sort_order = :sort_order, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':id' => $id,
        ':sort_order' => $sort_order
    ]);
    
    echo json_encode([
        'code' => 200,
        'message' => '更新成功'
    ]);
}
