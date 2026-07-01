<?php
/**
 * 数据库配置
 * 从 .env 文件读取配置，如果不存在则使用默认值
 */

// 加载环境变量
require_once __DIR__ . '/../includes/EnvLoader.php';

return [
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_NAME', ''),
    'username' => env('DB_USER', ''),
    'password' => env('DB_PASS', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'debug' => env('DB_DEBUG', 'false') === 'true',
];
