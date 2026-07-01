<?php
/**
 * 全局辅助函数
 * 提供域名、路径等全局函数
 */

/**
 * 获取系统配置实例
 */
function sysConfig() {
    static $instance = null;
    if ($instance === null) {
        $instance = SystemConfig::getInstance();
    }
    return $instance;
}

/**
 * 获取完整域名（带协议）
 * 例：https://shop.auba.cn
 */
function site_url($path = '') {
    $baseUrl = sysConfig()->getFullDomain();
    return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
}

/**
 * 获取网站首页 URL
 */
function home_url() {
    return site_url('/');
}

/**
 * 获取后台首页 URL
 */
function admin_url($path = '') {
    $baseUrl = sysConfig()->getAdminPath();
    return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
}

/**
 * 获取上传目录 URL
 */
function upload_url($path = '') {
    $baseUrl = sysConfig()->getUploadPath();
    return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
}

/**
 * 获取资源文件 URL（CSS/JS 等）
 */
function asset_url($path = '') {
    return site_url('assets/' . ltrim($path, '/'));
}

/**
 * 获取当前域名
 */
function get_domain() {
    return sysConfig()->get('site_domain');
}

/**
 * 获取协议类型
 */
function get_protocol() {
    return sysConfig()->get('site_protocol', 'http');
}

/**
 * 获取服务器 IP
 */
function get_server_ip() {
    return sysConfig()->get('server_ip');
}

/**
 * 检查数据库状态
 */
function is_db_normal() {
    return sysConfig()->get('db_status') === 'normal';
}

/**
 * 获取网站名称
 */
function site_name() {
    return sysConfig()->get('site_name', '青园营地');
}

/**
 * 更新系统配置
 */
function update_config($key, $value) {
    sysConfig()->updateConfig($key, $value);
}

/**
 * 获取系统配置值
 */
function get_config($key, $default = '') {
    return sysConfig()->get($key, $default);
}

/**
 * 自动识别域名并保存
 */
function auto_detect_domain() {
    return sysConfig()->autoDetectDomain();
}

/**
 * 获取系统状态
 */
function get_system_status() {
    return sysConfig()->getSystemStatus();
}

/**
 * 返回 JSON 成功响应
 */
function json_success($data = null, $message = 'success', $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 返回 JSON 错误响应
 */
function json_error($message, $code = 400, $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'code' => $code,
        'message' => $message
    ];
    if (!empty($extra)) {
        $response = array_merge($response, $extra);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check admin login status for API files
 */
function check_admin() {
    session_start();
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
        json_error('未登录', 401);
    }
}
