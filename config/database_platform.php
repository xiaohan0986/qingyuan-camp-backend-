<?php
/**
 * 平台用户管理数据库配置
 * 从 .env 文件读取，如无则使用默认值
 */
require_once __DIR__ . '/../includes/EnvLoader.php';

return [
    'host' => env('DB_PLATFORM_HOST', env('DB_HOST', 'localhost')),
    'port' => env('DB_PLATFORM_PORT', env('DB_PORT', '3306')),
    'database' => env('DB_PLATFORM_NAME', 'qianwutong'),
    'username' => env('DB_PLATFORM_USER', 'qianwutong'),
    'password' => env('DB_PLATFORM_PASS', 'hb098634'),

    'charset' => 'utf8mb4',
    'debug' => false,
];
