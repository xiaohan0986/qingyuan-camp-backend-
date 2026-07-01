<?php
/**
 * 阿里云 OSS 配置
 * 
 * 配置来源：system_config 数据库表
 * 配置路径：后台 > 系统设置 > 其他配置 > 对象存储配置
 */

// 引入系统配置
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (file_exists(ROOT_PATH . '/includes/Database.php')) {
    require_once ROOT_PATH . '/includes/Database.php';
    require_once ROOT_PATH . '/includes/SystemConfig.php';
    
    $config = SystemConfig::getInstance();
    
    // 从数据库读取 OSS 配置
    define('OSS_ENABLED', (int)$config->get('oss_enabled', 0) === 1);
    define('OSS_ENDPOINT', $config->get('oss_endpoint', 'oss-cn-hangzhou.aliyuncs.com'));
    define('OSS_BUCKET', $config->get('oss_bucket', ''));
    define('OSS_ACCESS_KEY_ID', $config->get('oss_access_key_id', ''));
    define('OSS_ACCESS_KEY_SECRET', $config->get('oss_access_key_secret', ''));
    define('OSS_CNAME', $config->get('oss_cname', ''));
    define('OSS_UPLOAD_DIR', $config->get('oss_upload_dir', 'shopauba'));
} else {
    // 如果无法读取数据库，使用默认配置（禁用状态）
    define('OSS_ENABLED', false);
    define('OSS_ENDPOINT', '');
    define('OSS_BUCKET', '');
    define('OSS_ACCESS_KEY_ID', '');
    define('OSS_ACCESS_KEY_SECRET', '');
    define('OSS_CNAME', '');
    define('OSS_UPLOAD_DIR', 'shopauba');
}
