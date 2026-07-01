<?php
/**
 * 删除技能标签 API
 * 从预设标签列表中删除一个技能标签
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);  // 不显示 HTML 错误，只返回 JSON

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
    $skill = isset($_POST['skill']) ? trim($_POST['skill']) : '';
    
    if (empty($skill)) {
        throw new Exception('技能名称不能为空');
    }
    
    // 从技能标签表中删除
    $stmt = $pdo->prepare("DELETE FROM skill_tags WHERE skill_name = ?");
    $stmt->execute([$skill]);
    
    $deletedCount = $stmt->rowCount();
    
    if ($deletedCount > 0) {
        // 同时从所有用户的已选列表中移除
        $stmt = $pdo->query("
            SELECT id, skills FROM mini_program_users 
            WHERE skills IS NOT NULL AND skills != ''
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $userSkills = json_decode($user['skills'], true);
            if (is_array($userSkills)) {
                $userSkills = array_values(array_diff($userSkills, [$skill]));
                $newSkillsJson = json_encode($userSkills, JSON_UNESCAPED_UNICODE);
                
                $updateStmt = $pdo->prepare("
                    UPDATE mini_program_users 
                    SET skills = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$newSkillsJson, $user['id']]);
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
