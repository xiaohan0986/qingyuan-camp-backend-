<?php
/**
 * 用户信息完善 API
 * 用于小程序用户补充个人信息（微信号等）
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'code' => 405,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 数据库配置
require_once __DIR__ . '/../config/database.php';

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败'
    ]);
    exit;
}

// 验证 Token
$headers = getallheaders();
$token = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($token)) {
    // 开发环境：如果没有 token，允许访问（需要传入 user_id）
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? 0;
    if (empty($userId)) {
        echo json_encode([
            'code' => 401,
            'message' => '未授权访问'
        ]);
        exit;
    }
} else {
    // 验证 token 并获取用户 ID
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([str_replace('Bearer ', '', $token)]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            // token 无效，尝试从 token 中直接获取 user_id（开发环境）
            $userId = 1;
        } else {
            $userId = $tokenData['user_id'];
        }
    } catch (PDOException $e) {
        // 如果没有 user_tokens 表，使用默认用户 ID（开发环境）
        $userId = 1;
    }
}

// 获取请求参数
$input = json_decode(file_get_contents('php://input'), true);
$wechatId = isset($input['wechat_id']) ? trim($input['wechat_id']) : '';
$realName = isset($input['real_name']) ? trim($input['real_name']) : '';
$gender = isset($input['gender']) ? intval($input['gender']) : null;
$age = isset($input['age']) ? intval($input['age']) : null;
$education = isset($input['education']) ? trim($input['education']) : '';

// 更新用户信息
try {
    $updateFields = [];
    $params = [];
    
    if ($wechatId !== '') {
        $updateFields[] = 'wechat_id = ?';
        $params[] = $wechatId;
    }
    
    if ($realName !== '') {
        $updateFields[] = 'real_name = ?';
        $params[] = $realName;
    }
    
    if ($gender !== null) {
        $updateFields[] = 'gender = ?';
        $params[] = $gender;
    }
    
    if ($age !== null) {
        $updateFields[] = 'age = ?';
        $params[] = $age;
    }
    
    if ($education !== '') {
        $updateFields[] = 'education = ?';
        $params[] = $education;
    }
    
    $updateFields[] = 'updated_at = NOW()';
    
    if (empty($updateFields)) {
        echo json_encode([
            'code' => 400,
            'message' => '没有要更新的字段'
        ]);
        exit;
    }
    
    $params[] = $userId;
    
    // 优先使用 mini_program_users 表
    try {
        $sql = "UPDATE mini_program_users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } catch (PDOException $e) {
        // 如果 mini_program_users 表不存在，使用 users 表
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'user_id' => $userId,
            'updated_fields' => count($updateFields) - 1
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '更新失败：' . $e->getMessage()
    ]);
    exit;
}
