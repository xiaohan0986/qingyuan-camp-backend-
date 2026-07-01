<?php
/**
 * 检查用户是否为客户 API
 * 判断小程序用户是否在 customers 表中有记录
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 使用 Database 单例类获取连接（避免重复加载配置文件）
require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 获取手机号
    $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
    
    if (empty($phone)) {
        echo json_encode([
            'code' => 400,
            'message' => '手机号不能为空',
            'data' => [
                'is_customer' => false
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查 customers 表中是否有该手机号的记录
    $stmt = $pdo->prepare("
        SELECT id, name, phone 
        FROM customers 
        WHERE phone = ?
        LIMIT 1
    ");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        // 是客户
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'is_customer' => true,
                'customer_info' => [
                    'id' => $customer['id'],
                    'name' => $customer['name'],
                    'phone' => $customer['phone']
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 不是客户
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'is_customer' => false
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '数据库错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
