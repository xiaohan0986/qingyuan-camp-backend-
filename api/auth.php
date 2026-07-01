<?php
/**
 * 认证 API - 登录/登出/获取用户信息
 */
header('Content-Type: application/json; charset=utf-8');

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'DatabaseManager.php';

$action = $_GET['action'] ?? '';

try {
    // 获取数据库连接（会自动检查并添加缺失字段）
    $pdo = getDB();
} catch (PDOException $e) {
    json_error('数据库连接失败：' . $e->getMessage(), 500);
}

switch ($action) {
    case 'login':
        handle_login($pdo);
        break;
    case 'logout':
        handle_logout();
        break;
    case 'userinfo':
        handle_userinfo($pdo);
        break;
    case 'miniprogram_login':
        handle_miniprogram_login($pdo);
        break;
    default:
        json_error('未知操作', 400);
}

/**
 * 处理登录（支持普通用户登录和销售人员登录）
 */
function handle_login($pdo) {
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('请求方法错误', 405);
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        json_error('用户名和密码不能为空', 400);
    }
    
    try {
        // 先尝试从 admins 表查找（管理员登录）
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND status = 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // 管理员登录成功
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['login_type'] = 'user';
            
            json_success([
                'redirect_url' => 'dashboard.php',
                'user_id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['name'],
                'role' => $admin['role'],
                'login_type' => 'user'
            ], '登录成功');
            return;
        }
        
        // 管理员登录失败，尝试 users 表登录（普通用户）
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR phone = ?) AND status = 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // 用户登录成功
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role'] ?? 1;
            $_SESSION['username'] = $user['username'];
            $_SESSION['nickname'] = $user['nickname'] ?? $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_type'] = 'user';
            // 也设 admin_id 以兼容旧版 Auth::check()
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['nickname'] ?? $user['username'];
            
            json_success([
                'redirect_url' => 'dashboard.php',
                'user_id' => $user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'],
                'role' => $user['role'],
                'login_type' => 'user'
            ], '登录成功');
            return;
        }
        
        // 管理员和用户都不存在，尝试手机号密码登录（salesmen）
        try {
            $stmt = $pdo->prepare('SELECT * FROM salesmen WHERE phone = ? AND status = "在职" LIMIT 1');
            $stmt->execute([$username]);
            $salesman = $stmt->fetch();
            
            if ($salesman) {
                // 验证密码（有密码的用 password_verify，无密码的拒绝登录）
                $hashedPassword = $salesman['password'] ?? '';
                if (empty($hashedPassword) || !password_verify($password, $hashedPassword)) {
                    json_error('用户名或密码错误', 401);
                    return;
                }
                
                $_SESSION['user_id'] = $salesman['id'];
                $_SESSION['username'] = $salesman['name'];
                $_SESSION['nickname'] = $salesman['name'];
                $_SESSION['phone'] = $salesman['phone'];
                $_SESSION['login_type'] = 'salesman';
                
                json_success([
                    'redirect_url' => 'dashboard.php',
                    'user_id' => $salesman['id'],
                    'username' => $salesman['name'],
                    'phone' => $salesman['phone'],
                    'login_type' => 'salesman'
                ], '登录成功');
                return;
            }
        } catch (PDOException $e) {
            // salesmen 表查询失败，忽略（可能表不存在）
        }
        
        json_error('用户名或密码错误', 401);
        
    } catch (PDOException $e) {
        json_error('登录失败：' . $e->getMessage(), 500);
    }
}

/**
 * 处理登出
 */
function handle_logout() {
    session_start();
    session_destroy();
    json_success(null, '已退出登录');
}

/**
 * 获取用户信息
 */
function handle_userinfo($pdo) {
    session_start();
    
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['role_id'])) {
        json_error('未登录', 401);
    }
    
    try {
        $loginType = $_SESSION['login_type'] ?? 'user';
        
        if ($loginType === 'salesman') {
            // 销售人员
            $stmt = $pdo->prepare('SELECT s.*, r.role_name, r.role_key FROM salesmen s LEFT JOIN roles r ON s.role_id = r.id WHERE s.id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $salesman = $stmt->fetch();
            
            if (!$salesman) {
                // 账号已被删除
                session_destroy();
                json_error('账号已被删除，请重新登录', 403, ['need_logout' => true]);
            }
            
            // 检查账号状态
            if ($salesman['status'] !== '在职') {
                // 账号已禁用
                session_destroy();
                json_error('账号已禁用，请联系管理员', 403, ['need_logout' => true]);
            }
            
            json_success([
                'id' => $salesman['id'],
                'username' => $salesman['name'],
                'nickname' => $salesman['name'],
                'phone' => $salesman['phone'],
                'email' => $salesman['email'] ?? '',
                'role_id' => $salesman['role_id'],
                'role_name' => $salesman['role_name'] ?? '未分配',
                'role_key' => $salesman['role_key'] ?? '',
                'login_type' => 'salesman',
                'avatar' => $salesman['avatar'] ?? ''
            ]);
            
        } else {
            // 普通用户
            $stmt = $pdo->prepare('SELECT id, username, nickname, email, phone, role, status FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                json_error('用户不存在', 404);
            }
            
            json_success($user);
        }
        
    } catch (PDOException $e) {
        json_error('获取用户信息失败', 500);
    }
}

/**
 * 小程序登录
 */
function handle_miniprogram_login($pdo) {
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        json_error('缺少 code 参数', 400);
    }
    
    // TODO: 调用微信 API 获取 openid
    json_error('小程序登录功能开发中', 501);
}
