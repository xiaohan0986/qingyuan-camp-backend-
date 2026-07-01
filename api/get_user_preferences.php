<?php
/**
 * 获取用户偏好和标签列表 API
 * 返回所有可用的标签列表和用户已选的标签
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    // 获取用户 ID
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    // 查询技能标签表
    $stmt = $pdo->query("SELECT skill_name FROM skill_tags ORDER BY sort_order, id");
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 查询国家标签表
    $stmt = $pdo->query("SELECT country_name FROM country_tags ORDER BY sort_order, id");
    $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 用户已选的技能和国家
    $userSkills = [];
    $userCountries = [];
    
    if ($userId > 0) {
        // 查询用户偏好
        $stmt = $pdo->prepare("
            SELECT skills, intended_countries 
            FROM mini_program_users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // 解析 JSON 数据
            if (!empty($user['skills'])) {
                $userSkills = json_decode($user['skills'], true);
                if (!is_array($userSkills)) {
                    $userSkills = [];
                }
            }
            
            if (!empty($user['intended_countries'])) {
                $userCountries = json_decode($user['intended_countries'], true);
                if (!is_array($userCountries)) {
                    $userCountries = [];
                }
            }
        }
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'skills' => $skills,
            'countries' => $countries,
            'user_skills' => $userSkills,
            'user_countries' => $userCountries
        ]
    ], JSON_UNESCAPED_UNICODE);
    
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
