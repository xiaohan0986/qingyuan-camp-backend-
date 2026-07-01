<?php
/**
 * 项目入口引导文件
 * 定义项目根路径和常用目录
 * 自动识别本地/服务器环境
 */

// 加载路径配置
require_once __DIR__ . '/config/paths.php';

// 定义常用目录路径（使用 ROOT_PATH）
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);
}
if (!defined('API_PATH')) {
    define('API_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR);
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
}
if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR);
}
if (!defined('DATABASE_PATH')) {
    define('DATABASE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR);
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
}

// 加载环境变量并定义数据库常量（Database 类依赖）
if (!defined('DB_HOST')) {
    require_once INCLUDES_PATH . 'EnvLoader.php';
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_PORT', env('DB_PORT', '3306'));
    define('DB_NAME', env('DB_NAME', ''));
    define('DB_USER', env('DB_USER', ''));
    define('DB_PASS', env('DB_PASS', ''));
    define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
}

// 确保 Database 类已加载
if (!class_exists('Database', false)) {
    require_once INCLUDES_PATH . 'Database.php';
}

// 设置错误报告（生产环境应关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0);  // 改为 0，避免错误输出影响 JSON

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置默认字符集
ini_set('default_charset', 'UTF-8');

// ========== 新架构：自动加载（基于 core/AutoLoader.php） ==========
// 定义新架构目录
if (!defined('CORE_PATH')) {
    define('CORE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR);
}
if (!defined('APP_PATH_NEW')) {
    define('APP_PATH_NEW', ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
}
if (!defined('ROUTE_PATH')) {
    define('ROUTE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR);
}

// 加载自动加载器
require_once CORE_PATH . 'AutoLoader.php';

// 注册命名空间 → 目录映射
AutoLoader::addNamespace('core', CORE_PATH);
AutoLoader::addNamespace('app', APP_PATH_NEW);
// 全局类（无命名空间）可直接 require，或放在 app/ 下用 app\ 前缀
AutoLoader::register();

// 手动加载核心类（无命名空间的全局类）
$coreClassMap = [
    'Request' => 'Request.php',
    'JsonResponse' => 'JsonResponse.php',
    'Router' => 'Router.php',
    'BaseModel' => 'BaseModel.php',
    'BaseDao' => 'BaseDao.php',
    'BaseController' => 'BaseController.php',
    // Middleware.php 包含接口和两个中间件类
    'MiddlewareInterface' => 'Middleware.php',
    'AuthMiddleware' => 'Middleware.php',
    'CorsMiddleware' => 'Middleware.php',
];
$loadedFiles = [];
foreach ($coreClassMap as $class => $filename) {
    $file = CORE_PATH . $filename;
    if (file_exists($file) && !in_array($file, $loadedFiles)) {
        require_once $file;
        $loadedFiles[] = $file;
    }
}

// 加载应用层类（model / dao / controller）
$appDirs = ['model', 'dao', 'repository', 'controller/adminapi', 'controller/api'];
foreach ($appDirs as $dir) {
    $fullDir = APP_PATH_NEW . $dir;
    if (is_dir($fullDir)) {
        foreach (glob($fullDir . '/*.php') as $file) {
            require_once $file;
        }
    }
}
// ========== 新架构 END ==========

// 禁止直接访问此文件
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('禁止直接访问');
}
