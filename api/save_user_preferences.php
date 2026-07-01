<?php
/**
 * 保存用户偏好（特长 + 意向国家）
 * 用于小程序注册引导流程
 */

header('Content-Type: application/json; charset=utf-8');

// 使用 Database 单例类获取连接（避免重复加载配置文件）
require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 405, 'message' => '请求方法不允许']);
    exit;
}

// 获取参数（支持 JSON 和 form-urlencoded 两种格式）
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (is_array($data)) {
    // JSON 格式
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $skills = isset($data['skills']) ? $data['skills'] : [];
    $intended_countries = isset($data['intended_countries']) ? $data['intended_countries'] : [];
} else {
    // form-urlencoded 格式
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $intended_countries = isset($_POST['intended_countries']) ? $_POST['intended_countries'] : [];
}

// 验证用户 ID
if ($user_id <= 0) {
    echo json_encode(['code' => 400, 'message' => '用户 ID 无效']);
    exit;
}

// 验证至少选择一个特长
if (empty($skills)) {
    echo json_encode(['code' => 400, 'message' => '请至少选择一个特长']);
    exit;
}

// 验证至少选择一个国家
if (empty($intended_countries)) {
    echo json_encode(['code' => 400, 'message' => '请至少选择一个意向国家']);
    exit;
}

try {
    // 将数组转为 JSON 字符串
    $skills_json = json_encode($skills, JSON_UNESCAPED_UNICODE);
    $countries_json = json_encode($intended_countries, JSON_UNESCAPED_UNICODE);
    
    // 更新数据库
    $sql = "UPDATE mini_program_users SET skills = ?, intended_countries = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$skills_json, $countries_json, $user_id]);
    
    if ($result) {
        // 检查影响的行数
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            echo json_encode([
                'code' => 200,
                'message' => '保存成功',
                'data' => [
                    'user_id' => $user_id,
                    'skills' => $skills,
                    'intended_countries' => $intended_countries
                ]
            ]);
        } else {
            echo json_encode([
                'code' => 404,
                'message' => '用户不存在'
            ]);
        }
    } else {
        echo json_encode(['code' => 500, 'message' => '保存失败']);
    }
} catch (PDOException $e) {
    error_log('保存用户偏好失败：' . $e->getMessage());
    echo json_encode(['code' => 500, 'message' => '服务器错误']);
}
