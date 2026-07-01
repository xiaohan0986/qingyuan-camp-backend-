<?php
/**
 * 后台用户管理 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/OperationLogger.php';
require_once __DIR__ . '/../includes/PasswordCrypto.php';
require_once __DIR__ . '/../includes/PermissionChecker.php';

session_start();

// 检查登录状态
$permissionChecker = new PermissionChecker();
if (!$permissionChecker->isSuperAdmin()) {
    echo json_encode(['code' => 403, 'message' => '权限不足，只有超级管理员可以管理后台用户']);
    exit;
}

$db = Database::getInstance();
$logger = new OperationLogger();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // 获取后台用户列表
            $users = $db->fetchAll("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC");
            
            // 解密密码
            foreach ($users as &$user) {
                if (!empty($user['password_plain'])) {
                    $user['password_plain'] = PasswordCrypto::decrypt($user['password_plain']);
                }
            }
            
            echo json_encode(['code' => 200, 'data' => $users]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? 0;
            $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
            if ($user && !empty($user['password_plain'])) {
                $user['password_plain'] = PasswordCrypto::decrypt($user['password_plain']);
            }
            echo json_encode(['code' => 200, 'data' => $user]);
            break;
            
        case 'create':
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['code' => 405, 'message' => '请求方法错误']);
                exit;
            }
            
            $id = $_POST['id'] ?? null;
            $username = $_POST['username'] ?? '';
            $nickname = $_POST['nickname'] ?? '';
            $password = $_POST['password'] ?? '';
            $roleId = $_POST['role_id'] ?? null;
            $status = $_POST['status'] ?? 1;
            
            if (!$username) {
                echo json_encode(['code' => 400, 'message' => '用户名不能为空']);
                exit;
            }
            
            if ($id) {
                // 更新用户
                $existingUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
                if (!$existingUser) {
                    echo json_encode(['code' => 404, 'message' => '用户不存在']);
                    exit;
                }
                
                $updateSql = "UPDATE users SET username = ?, nickname = ?, role_id = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $params = [$username, $nickname, $roleId, $status, $id];
                
                if ($password) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $encryptedPassword = PasswordCrypto::encrypt($password);
                    $updateSql = "UPDATE users SET username = ?, nickname = ?, password = ?, password_plain = ?, role_id = ?, status = ?, updated_at = NOW() WHERE id = ?";
                    $params = [$username, $nickname, $hashedPassword, $encryptedPassword, $roleId, $status, $id];
                }
                
                $db->execute($updateSql, $params);
                $logger->log('后台用户管理', "更新用户：{$username} (ID: {$id})");
                echo json_encode(['code' => 200, 'message' => '用户更新成功']);
            } else {
                // 创建用户
                if (!$password) {
                    echo json_encode(['code' => 400, 'message' => '初始密码不能为空']);
                    exit;
                }
                
                // 检查用户名是否已存在
                $exists = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
                if ($exists) {
                    echo json_encode(['code' => 400, 'message' => '用户名已存在']);
                    exit;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $encryptedPassword = PasswordCrypto::encrypt($password);
                
                $db->execute("INSERT INTO users (username, nickname, password, password_plain, role_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", 
                    [$username, $nickname, $hashedPassword, $encryptedPassword, $roleId, $status]);
                
                $newId = $db->lastInsertId();
                $logger->log('后台用户管理', "创建用户：{$username} (ID: {$newId})");
                echo json_encode(['code' => 200, 'message' => '用户创建成功', 'data' => ['id' => $newId]]);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($id == 1) {
                echo json_encode(['code' => 400, 'message' => '不能删除超级管理员账号']);
                exit;
            }
            $db->execute("DELETE FROM users WHERE id = ?", [$id]);
            $logger->log('后台用户管理', "删除用户 (ID: {$id})");
            echo json_encode(['code' => 200, 'message' => '用户删除成功']);
            break;
            
        case 'roles':
            // 获取角色列表
            $roles = $db->fetchAll("SELECT id, role_name, description FROM roles WHERE status = 1 ORDER BY sort_order ASC");
            echo json_encode(['code' => 200, 'data' => $roles]);
            break;
            
        case 'backend_users':
            // 获取后台用户列表（用于客户负责人选择）
            $users = $db->fetchAll("SELECT id, username, nickname, role_id FROM users WHERE status = 1 ORDER BY id ASC");
            echo json_encode(['code' => 200, 'data' => $users]);
            break;
            
        default:
            echo json_encode(['code' => 400, 'message' => '未知操作']);
    }
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库错误：' . $e->getMessage()]);
}
