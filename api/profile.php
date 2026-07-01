<?php
/**
 * 个人中心 API
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

// 检查登录状态
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 403, 'message' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    $config = require_once CONFIG_PATH . 'database.php';
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
    echo json_encode(['code' => 500, 'message' => '数据库连接失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'change_password':
        changePassword($pdo, $userId);
        break;
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
}

/**
 * 修改密码
 */
function changePassword($pdo, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    // 验证必填字段
    if (empty($oldPassword)) {
        echo json_encode(['code' => 400, 'message' => '请输入原密码'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (empty($newPassword)) {
        echo json_encode(['code' => 400, 'message' => '请输入新密码'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['code' => 400, 'message' => '两次输入的新密码不一致'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['code' => 400, 'message' => '密码长度不能少于 6 位'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证原密码
    $stmt = $pdo->prepare("SELECT password, password_plain FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['code' => 404, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 优先使用 password 字段验证（bcrypt）
    $passwordValid = false;
    if (!empty($user['password']) && password_verify($oldPassword, $user['password'])) {
        $passwordValid = true;
    } elseif (!empty($user['password_plain']) && $oldPassword === $user['password_plain']) {
        // 降级：使用明文密码验证
        $passwordValid = true;
    }
    
    if (!$passwordValid) {
        echo json_encode(['code' => 400, 'message' => '原密码错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 更新密码
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = :password, password_plain = :password_plain, updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':password' => $hashedPassword,
        ':password_plain' => $newPassword,
        ':id' => $userId
    ]);
    
    echo json_encode([
        'code' => 200,
        'message' => '密码修改成功，请重新登录',
        'data' => ['logout' => true]
    ], JSON_UNESCAPED_UNICODE);
}
