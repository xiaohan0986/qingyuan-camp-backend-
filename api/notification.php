<?php
/**
 * 系统通知 API
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
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// 检查登录状态
$isSalesmanLogin = isset($_SESSION['login_type']) && $_SESSION['login_type'] === 'salesman';
$isUserLogin = isset($_SESSION['user_id']);

if (!$isSalesmanLogin && !$isUserLogin) {
    echo json_encode(['code' => 403, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$userType = $isSalesmanLogin ? 'salesman' : 'user';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            getList($pdo, $userId, $userType);
            break;
        case 'mark_read':
            markRead($pdo);
            break;
        case 'mark_all_read':
            markAllRead($pdo, $userId, $userType);
            break;
        case 'unread_count':
            getUnreadCount($pdo, $userId, $userType);
            break;
        case 'delete':
            deleteMessage($pdo, $userId, $userType);
            break;
        default:
            echo json_encode(['code' => 400, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取消息列表
 */
function getList($pdo, $userId, $userType) {
    $limit = (int)($_GET['limit'] ?? 10);
    $limit = min($limit, 50); // 最多 50 条
    
    $stmt = $pdo->prepare("
        SELECT * 
        FROM system_notifications 
        WHERE user_id = :user_id 
        AND user_type = :user_type 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_type', $userType, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 解析 extra_data
    foreach ($messages as &$msg) {
        if (!empty($msg['extra_data'])) {
            $msg['extra_data'] = json_decode($msg['extra_data'], true);
        }
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $messages
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 标记单条消息为已读
 */
function markRead($pdo) {
    // 解析 JSON 数据
    $input = json_decode(file_get_contents('php://input'), true);
    $messageId = (int)($input['id'] ?? 0);
    
    if (!$messageId) {
        echo json_encode(['code' => 400, 'message' => '消息 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE id = :id
    ");
    
    $stmt->execute([':id' => $messageId]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $messageId]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 标记所有消息为已读
 */
function markAllRead($pdo, $userId, $userType) {
    // 解析 JSON 数据（虽然这个接口不需要参数，但保持一致性）
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = :user_id 
        AND user_type = :user_type
        AND is_read = 0
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':user_type' => $userType
    ]);
    
    $affectedRows = $stmt->rowCount();
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['marked_count' => $affectedRows]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取未读消息数量
 */
function getUnreadCount($pdo, $userId, $userType) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_notifications 
        WHERE user_id = :user_id 
        AND user_type = :user_type 
        AND is_read = 0
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':user_type' => $userType
    ]);
    
    $result = $stmt->fetch();
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => ['count' => (int)($result['count'] ?? 0)]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 删除消息
 */
function deleteMessage($pdo, $userId, $userType) {
    // 解析 JSON 数据
    $input = json_decode(file_get_contents('php://input'), true);
    $messageId = (int)($input['id'] ?? 0);
    
    if (!$messageId) {
        echo json_encode(['code' => 400, 'message' => '消息 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证消息属于当前用户
    $stmt = $pdo->prepare("SELECT id FROM system_notifications WHERE id = :id AND user_id = :user_id AND user_type = :user_type");
    $stmt->execute([
        ':id' => $messageId,
        ':user_id' => $userId,
        ':user_type' => $userType
    ]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['code' => 404, 'message' => '消息不存在或无权删除'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 删除消息
    $stmt = $pdo->prepare("DELETE FROM system_notifications WHERE id = :id");
    $stmt->execute([':id' => $messageId]);
    
    echo json_encode([
        'code' => 200,
        'message' => '删除成功',
        'data' => ['id' => $messageId]
    ], JSON_UNESCAPED_UNICODE);
}
