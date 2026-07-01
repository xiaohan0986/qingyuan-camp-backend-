<?php
/**
 * 登录处理
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/CsrfHelper.php';

session_start();

// 防止重复提交
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// CSRF Token 验证
if (!CsrfHelper::verify($_POST['csrf_token'] ?? '')) {
    $_SESSION['csrf_error'] = '安全验证失败，请刷新页面后重试';
    header('Location: login.php?error=csrf');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: login.php?error=empty');
    exit;
}

try {
    $db = Database::getInstance();
    
    // 查询管理员用户（明确字段列表，禁止 SELECT *）
    $sql = "SELECT id, username, name, password, role FROM admins WHERE username = ? AND status = 1 LIMIT 1";
    $user = $db->fetchOne($sql, [$username]);
    
    if (!$user) {
        header('Location: login.php?error=invalid');
        exit;
    }
    
    // 验证密码
    if (!password_verify($password, $user['password'])) {
        // 兼容旧系统的 md5 密码
        if (md5($password) !== $user['password']) {
            header('Location: login.php?error=invalid');
            exit;
        } else {
            // MD5 密码验证通过，升级为 bcrypt 哈希并更新数据库
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query("UPDATE admins SET password = ? WHERE id = ?", [$newHash, $user['id']]);
            error_log("User {$user['username']}(id={$user['id']}) password upgraded from MD5 to bcrypt");
        }
    }
    
    // 登录成功，设置会话
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    // 记录登录日志
    $db->insert('admin_logs', [
        'admin_id' => $user['id'],
        'action' => '登录',
        'detail' => '管理员登录成功',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // 跳转到数据大屏
    header('Location: dashboard.php');
    exit;
    } catch (Exception $e) {
    error_log("登录错误：" . $e->getMessage());
    header('Location: login.php?error=invalid');
    exit;
}
