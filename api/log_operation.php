<?php
/**
 * 操作日志记录 API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = Database::getInstance();
    
    $db->insert('admin_logs', [
        'admin_id' => $_SESSION['admin_id'] ?? 0,
        'action' => $input['action'] ?? '未知操作',
        'detail' => $input['detail'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
