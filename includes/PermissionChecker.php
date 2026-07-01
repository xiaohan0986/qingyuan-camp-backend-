<?php
/**
 * 基于角色的权限检查中间件
 * 用于检查用户是否有权限访问特定页面或执行特定操作
 */

class PermissionChecker {
    private $userId;
    private $roleId;
    private $permissions = [];
    private $isSuperAdmin = false;
    
    public function __construct() {
        // 如果 session 未启动，则启动
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->userId = $_SESSION['user_id'] ?? null;
        
        // 支持两种登录方式：
        // 1. 角色登录：使用 role_id
        // 2. 旧用户登录：使用 role（值为 2 表示管理员）
        $this->roleId = $_SESSION['role_id'] ?? null;
        
        // 兼容旧的用户登录方式
        if (!$this->roleId && isset($_SESSION['role'])) {
            // 旧系统中 role = 2 表示管理员，视为超级管理员
            if ($_SESSION['role'] == 2) {
                $this->roleId = 1; // 视为超级管理员
            } elseif ($_SESSION['role'] == 1) {
                $this->roleId = 2; // 视为普通管理员
            }
        }
        
        // 检查是否是超级管理员（role_id = 1）
        if ($this->roleId == 1) {
            $this->isSuperAdmin = true;
            $this->permissions = ['all' => true];
        } else {
            // 从数据库加载角色权限
            $this->loadRolePermissions();
        }
    }
    
    /**
     * 从数据库加载角色权限
     */
    private function loadRolePermissions() {
        if (!$this->roleId) return;
        
        require_once __DIR__ . '/../includes/Database.php';
        $db = Database::getInstance();
        
        $role = $db->fetch("SELECT permissions FROM roles WHERE id = ? AND status = 1", [$this->roleId]);
        
        if ($role && $role['permissions']) {
            $this->permissions = json_decode($role['permissions'], true) ?? [];
        }
    }
    
    /**
     * 检查是否有权限访问某个模块
     * @param string $module 模块标识（如：dashboard, customer, position 等）
     * @return bool
     */
    public function hasModuleAccess($module) {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin) {
            return true;
        }
        
        // 检查是否有整个模块的权限
        if (isset($this->permissions[$module])) {
            if ($this->permissions[$module] === true) {
                return true;
            }
            
            // 检查是否有 view 权限（基础访问权限）
            if (is_array($this->permissions[$module]) && isset($this->permissions[$module]['view'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查是否有权限执行某个操作
     * @param string $module 模块标识
     * @param string $action 操作标识（view, create, edit, delete, export 等）
     * @return bool
     */
    public function hasPermission($module, $action) {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin) {
            return true;
        }
        
        if (!isset($this->permissions[$module])) {
            return false;
        }
        
        // 如果整个模块都有权限
        if ($this->permissions[$module] === true) {
            return true;
        }
        
        // 检查具体操作权限
        if (is_array($this->permissions[$module])) {
            return isset($this->permissions[$module][$action]);
        }
        
        return false;
    }
    
    /**
     * 检查是否有任意一个操作权限
     * @param string $module 模块标识
     * @param array $actions 操作列表
     * @return bool
     */
    public function hasAnyPermission($module, $actions) {
        foreach ($actions as $action) {
            if ($this->hasPermission($module, $action)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 检查是否有所有指定的操作权限
     * @param string $module 模块标识
     * @param array $actions 操作列表
     * @return bool
     */
    public function hasAllPermissions($module, $actions) {
        foreach ($actions as $action) {
            if (!$this->hasPermission($module, $action)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 获取用户有权限访问的所有模块
     * @return array
     */
    public function getAccessibleModules() {
        if ($this->isSuperAdmin) {
            return array_keys($this->getAllModules());
        }
        
        $accessible = [];
        foreach ($this->permissions as $module => $perms) {
            if ($perms === true || (is_array($perms) && isset($perms['view']))) {
                $accessible[] = $module;
            }
        }
        return $accessible;
    }
    
    /**
     * 获取所有可用的模块列表
     * @return array
     */
    private function getAllModules() {
        return [
            'dashboard' => '数据大屏',
            'position' => '岗位管理',
            'customer' => '客户管理',
            'user' => '用户管理',
            'article' => '文章管理',
            'store' => '门店管理',
            'salesmen' => '销售管理',
            'finance' => '财务管理',
            'config' => '系统配置',
            'role' => '角色管理',
            'report' => '报表统计',
            'notification' => '消息通知',
            'marketing' => '营销管理',
            'miniprogram' => '小程序管理',
            'file' => '文件管理',
            'system' => '系统工具'
        ];
    }
    
    /**
     * 如果无权访问则重定向
     * @param string $module
     * @param string $redirectUrl 重定向地址
     */
    public function requireAccess($module, $redirectUrl = null) {
        if (!$this->hasModuleAccess($module)) {
            // 如果没有指定重定向地址，尝试找到用户有权限的第一个页面
            if ($redirectUrl === null) {
                $accessibleModules = $this->getAccessibleModules();
                if (!empty($accessibleModules)) {
                    $redirectUrl = $accessibleModules[0] . '.php';
                } else {
                    // 如果没有任何权限，重定向到登录页
                    $redirectUrl = 'role_login.php';
                }
            }
            header("Location: {$redirectUrl}");
            exit;
        }
    }
    
    /**
     * 如果无权执行操作则返回错误
     * @param string $module
     * @param string $action
     */
    public function requirePermission($module, $action) {
        if (!$this->hasPermission($module, $action)) {
            http_response_code(403);
            echo json_encode(['code' => 403, 'message' => '没有权限执行此操作']);
            exit;
        }
    }
    
    /**
     * 获取用户的权限配置
     * @return array
     */
    public function getPermissions() {
        return $this->permissions;
    }
    
    /**
     * 检查是否是超级管理员
     * @return bool
     */
    public function isSuperAdmin() {
        return $this->isSuperAdmin;
    }
    
    /**
     * 获取角色 ID
     * @return int|null
     */
    public function getRoleId() {
        return $this->roleId;
    }
    
    /**
     * 获取用户 ID
     * @return int|null
     */
    public function getUserId() {
        return $this->userId;
    }
}

/**
 * 快捷函数：检查页面访问权限
 */
function checkPageAccess($module) {
    $permission = new PermissionChecker();
    $permission->requireAccess($module);
    return $permission;
}

/**
 * 快捷函数：检查 API 操作权限
 */
function checkApiPermission($module, $action) {
    $permission = new PermissionChecker();
    $permission->requirePermission($module, $action);
    return $permission;
}
