<?php
/**
 * 全局域名配置文件
 * 自动识别当前访问域名，供整个项目使用
 * 
 * 使用方法：
 * 1. 在所有需要域名的文件中引入：require_once __DIR__ . '/../config/domain.php';
 * 2. 使用 DOMAIN_URL 常量获取当前域名
 * 3. 使用 DOMAIN_CONFIG 数组获取详细配置
 */

// 防止重复定义
if (!defined('DOMAIN_INITIALIZED')) {
    define('DOMAIN_INITIALIZED', true);
    
    /**
     * 自动检测当前域名和协议
     * @return string 完整的域名 URL（不含末尾斜杠）
     */
    function getDomainUrl() {
        // 检测协议
        $isHttps = false;
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $isHttps = true;
        }
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            $isHttps = true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $isHttps = true;
        }
        
        $protocol = $isHttps ? 'https' : 'http';
        
        // 获取主机
        $host = 'localhost';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        }
        
        // 获取项目根路径（自动识别）
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        if ($scriptPath === '/' || $scriptPath === '\\' || $scriptPath === '') {
            $basePath = '';
        } else {
            // 移除 admin、api 等子目录路径
            $basePath = preg_replace('/\/(admin|api|111ceshi)$/', '', $scriptPath);
            $basePath = preg_replace('/\\\\(admin|api|111ceshi)$/', '', $basePath);
            // 确保不以斜杠结尾
            $basePath = rtrim($basePath, '/\\');
        }
        
        return $protocol . '://' . $host . $basePath;
    }
    
    /**
     * 从配置文件读取已保存的域名
     * @return string|null 保存的域名或 null
     */
    function getSavedDomain() {
        $configFile = __DIR__ . '/config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if (isset($config['site_url']) && !empty($config['site_url'])) {
                return rtrim($config['site_url'], '/');
            }
        }
        return null;
    }
    
    /**
     * 保存域名到配置文件
     * @param string $domain 要保存的域名
     * @return bool 是否保存成功
     */
    function saveDomain($domain) {
        $configFile = __DIR__ . '/config.json';
        
        // 加载现有配置
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        // 更新域名配置
        $config['site_url'] = rtrim($domain, '/');
        $config['api_url'] = rtrim($domain, '/') . '/api';
        $config['auto_detected_domain'] = $domain;
        $config['last_domain_check'] = date('Y-m-d H:i:s');
        
        // 保存域名历史
        if (!isset($config['domain_history']) || !is_array($config['domain_history'])) {
            $config['domain_history'] = [];
        }
        $config['domain_history'][] = [
            'domain' => $domain,
            'time' => date('Y-m-d H:i:s')
        ];
        // 只保留最近 10 条记录
        $config['domain_history'] = array_slice($config['domain_history'], -10);
        
        return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // 定义域名常量
    // 优先使用配置文件中保存的域名，如果没有则自动检测
    $savedDomain = getSavedDomain();
    $currentDomain = getDomainUrl();
    
    // 如果保存的域名和当前访问的域名不一致，使用当前访问的域名（自动适配）
    if ($savedDomain && strpos($currentDomain, parse_url($savedDomain, PHP_URL_HOST)) === false) {
        // 当前访问域名与保存的不同，使用当前访问的（适配多环境）
        define('DOMAIN_URL', $currentDomain);
        define('DOMAIN_AUTO_DETECTED', true);
    } else {
        // 使用保存的域名或当前域名
        define('DOMAIN_URL', $savedDomain ?: $currentDomain);
        define('DOMAIN_AUTO_DETECTED', !$savedDomain);
    }
    
    // 定义常用域名常量
    define('API_URL', DOMAIN_URL . '/api');
    define('ADMIN_URL', DOMAIN_URL . '/admin');
    define('UPLOADS_URL', DOMAIN_URL . '/uploads');
    define('IMAGES_URL', DOMAIN_URL . '/images');
    define('CESHI_URL', DOMAIN_URL . '/111ceshi');
    
    // 域名详细配置
    define('DOMAIN_CONFIG', [
        'url' => DOMAIN_URL,
        'api_url' => API_URL,
        'admin_url' => ADMIN_URL,
        'uploads_url' => UPLOADS_URL,
        'images_url' => IMAGES_URL,
        'ceshi_url' => CESHI_URL,
        'auto_detected' => DOMAIN_AUTO_DETECTED,
        'saved_domain' => $savedDomain,
        'current_domain' => $currentDomain,
        'protocol' => parse_url(DOMAIN_URL, PHP_URL_SCHEME),
        'host' => parse_url(DOMAIN_URL, PHP_URL_HOST),
        'port' => parse_url(DOMAIN_URL, PHP_URL_PORT),
        'path' => parse_url(DOMAIN_URL, PHP_URL_PATH),
    ]);
}
