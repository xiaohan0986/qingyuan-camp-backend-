<?php
/**
 * 统一路径配置文件
 * 自动识别本地/服务器环境，智能适配域名
 */

// 项目根目录（从 config 目录往上一级）
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// 定义基础 URL（简单的动态域名检测）
if (!defined('BASE_URL')) {
    // CLI 模式下使用固定配置
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', 'http://localhost');
    } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        
        
        
        define('BASE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }
}

// 定义 DOMAIN_URL（与 BASE_URL 相同）
if (!defined('DOMAIN_URL')) {
    define('DOMAIN_URL', BASE_URL);
}

// 定义常用 URL 常量
if (!defined('API_URL')) {
    define('API_URL', DOMAIN_URL . '/api');
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', DOMAIN_URL . '/admin');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', DOMAIN_URL . '/uploads');
}
if (!defined('IMAGES_URL')) {
    define('IMAGES_URL', DOMAIN_URL . '/images');
}
if (!defined('CESHI_URL')) {
    define('CESHI_URL', DOMAIN_URL . '/111ceshi');
}
