<?php
/**
 * 青园营地管理后台 - 配置文件
 */

// 从 .env 加载数据库配置
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {$env = parse_ini_file($envFile);
  define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
  define('DB_PORT', $env['DB_PORT'] ?? '3306');
  define('DB_NAME', $env['DB_NAME'] ?? 'shopauba');
  define('DB_USER', $env['DB_USER'] ?? 'root');
  define('DB_PASS', $env['DB_PASS'] ?? '');
  define('DB_CHARSET', $env['DB_CHARSET'] ?? 'utf8mb4');
}

// 项目配置
define('PROJECT_NAME', '青园营地管理后台');
define('PROJECT_VERSION', '1.0.0');
define('ADMIN_EMAIL', 'admin@shop.auba.cn');

// 路径配置
define('ROOT_PATH', dirname(__DIR__));
define('ADMIN_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'admin');
define('API_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'api');
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');

// 会话配置
ini_set('session.cookie_httponly', 1);
session_start();

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 引入系统配置和辅助函数
if (file_exists(__DIR__ . '/../includes/Database.php')) {
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/SystemConfig.php';
    require_once __DIR__ . '/../includes/functions.php';
}
