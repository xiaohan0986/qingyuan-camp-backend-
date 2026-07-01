<?php
/**
 * 删除国家标签 API
 * 从预设标签列表中删除一个国家标签
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 使用 Database 单例类获取连接（避免重复加载配置文件）
require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 只允许 POST 请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法不允许');
    }
    
    // 获取参数
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    
    if (empty($country)) {
        throw new Exception('国家名称不能为空');
    }
    
    // 从国家标签表中删除
    $stmt = $pdo->prepare("DELETE FROM country_tags WHERE country_name = ?");
    $stmt->execute([$country]);
    
    $deletedCount = $stmt->rowCount();
    
    if ($deletedCount > 0) {
        // 同时从所有用户的已选列表中移除
        $stmt = $pdo->query("
            SELECT id, intended_countries FROM mini_program_users 
            WHERE intended_countries IS NOT NULL AND intended_countries != ''
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $userCountries = json_decode($user['intended_countries'], true);
            if (is_array($userCountries)) {
                $userCountries = array_values(array_diff($userCountries, [$country]));
                $newCountriesJson = json_encode($userCountries, JSON_UNESCAPED_UNICODE);
                
                $updateStmt = $pdo->prepare("
                    UPDATE mini_program_users 
                    SET intended_countries = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$newCountriesJson, $user['id']]);
            }
        }
        
        echo json_encode([
            'code' => 200,
            'message' => '删除成功',
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('标签不存在或已被删除');
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
