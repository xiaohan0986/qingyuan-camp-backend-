<?php
/**
 * 认证工具类
 */
class Auth {
    /**
     * 初始化 Session
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * 检查登录状态
    */
   public static function check() {
       self::initSession();
        if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
            // 使用绝对路径重定向到登录页面
            $loginUrl = '/admin/login.php';
            header('Location: ' . $loginUrl);
            exit;
        }
        // 自动生成 CSRF Token（如果尚未生成）
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * 获取当前登录用户
    */
   public static function user() {
       self::initSession();
        if (isset($_SESSION['admin_id'])) {
            return [
                'id' => $_SESSION['admin_id'] ?? 0,
                'username' => $_SESSION['admin_username'] ?? '',
                'name' => $_SESSION['admin_name'] ?? '',
                'role' => $_SESSION['admin_role'] ?? 'admin'
            ];
        }
        return [
            'id' => $_SESSION['user_id'] ?? 0,
            'username' => $_SESSION['username'] ?? '',
            'name' => $_SESSION['nickname'] ?? $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'] ?? $_SESSION['login_type'] ?? 'user'
        ];
    }
    
    /**
     * 退出登录
     */
    public static function logout() {
        self::initSession();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    /**
     * 检查权限
     * 默认拒绝，白名单模式
     */
    public static function can($permission) {
        self::initSession();
        // 管理员拥有所有权限
        if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
            return true;
        }
        // 其他角色走权限表检查
        $roleId = $_SESSION['role_id'] ?? null;
        if (!$roleId) {
            return false;
        }
        return self::checkPermission($roleId, $permission);
    }

    /**
     * 从数据库检查角色权限
     */
    private static function checkPermission($roleId, $permission) {
        static $pdo = null;
        if ($pdo === null) {
            $configFile = __DIR__ . '/../config/database.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                try {
                    $pdo = new PDO(
                        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                        $config['username'],
                        $config['password']
                    );
                } catch (PDOException $e) {
                    return false;
                }
            }
        }
        if (!$pdo) return false;
        try {
            $stmt = $pdo->prepare(
                'SELECT 1 FROM role_permissions rp 
                 JOIN permissions p ON rp.permission_id = p.id 
                 WHERE rp.role_id = ? AND p.key = ? LIMIT 1'
            );
            $stmt->execute([$roleId, $permission]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
}
