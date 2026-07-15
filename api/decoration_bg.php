<?php
/**
 * 首页背景图 API（小程序端使用）
 * 
 * GET  /api/decoration_bg.php
 * 返回当前选中的首页背景图 URL
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// 检查表是否存在
$result = $conn->query("SHOW TABLES LIKE 'decoration_bg_images'");
if ($result->num_rows === 0) {
    echo json_encode(['success' => true, 'data' => null, 'message' => '暂无背景图']);
    exit;
}

// 查选中图
$stmt = $conn->prepare("SELECT id, image_url FROM decoration_bg_images WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id'        => (int)$row['id'],
            'image_url' => $row['image_url'],
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    // 没有选中的图，返回最新的第一张
    $result = $conn->query("SELECT id, image_url FROM decoration_bg_images ORDER BY sort_order ASC, id DESC LIMIT 1");
    $first = $result->fetch_assoc();
    if ($first) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id'        => (int)$first['id'],
                'image_url' => $first['image_url'],
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
}
