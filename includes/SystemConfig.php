<?php
/**
 * 系统配置管理类
 * 统一管理域名、路径等全局配置
 */
class SystemConfig {
    private static $instance = null;
    private $config = [];
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 加载配置
     */
    private function loadConfig() {
        try {
            // 检查配置表是否存在
            $tableExists = $this->db->fetchOne("SHOW TABLES LIKE 'system_config'");
            
            if (!$tableExists) {
                // 创建配置表
                $this->createConfigTable();
                $this->initDefaultConfig();
            }
            
            // 加载配置
            $rows = $this->db->fetchAll("SELECT config_key, config_value FROM system_config");
            foreach ($rows as $row) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
            
            // 自动识别域名（如果未配置）
            if (empty($this->config['site_domain'])) {
                $this->autoDetectDomain();
            }
            
        } catch (Exception $e) {
            // 配置表不存在或出错，初始化
            $this->createConfigTable();
            $this->initDefaultConfig();
        }
    }
    
    /**
     * 创建配置表
     */
    private function createConfigTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `system_config` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `config_key` VARCHAR(100) NOT NULL UNIQUE,
            `config_value` TEXT,
            `config_desc` VARCHAR(255),
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表'";
        
        $conn = $this->db->getConnection();
        $conn->query($sql);
    }
    
    /**
     * 初始化默认配置
     */
    private function initDefaultConfig() {
        $defaults = [
            ['site_domain', $this->detectDomain(), '网站域名'],
            ['site_protocol', 'http', '协议类型'],
            ['site_port', '', '端口号'],
            ['server_ip', $this->getServerIP(), '服务器 IP'],
            ['db_status', 'normal', '数据库状态'],
            ['site_status', 'online', '网站状态'],
            ['site_name', '青园营地', '网站名称'],
        ];
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT IGNORE INTO system_config (config_key, config_value, config_desc) VALUES (?, ?, ?)");
        
        foreach ($defaults as $item) {
            $stmt->bind_param('sss', $item[0], $item[1], $item[2]);
            $stmt->execute();
        }
        $stmt->close();
        
        // 重新加载配置
        $this->loadConfig();
    }
    
    /**
     * 自动检测域名
     */
    private function detectDomain() {
        $protocol = $this->getProtocol();
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = $_SERVER['SERVER_PORT'] ?? '80';
        
        // 标准端口不显示
        if (($protocol === 'http' && $port == '80') || ($protocol === 'https' && $port == '443')) {
            return $host;
        }
        
        return $host . ':' . $port;
    }
    
    /**
     * 获取协议
     */
    private function getProtocol() {
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == '1')) {
            return 'https';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return 'https';
        }
        return 'http';
    }
    
    /**
     * 获取服务器 IP
     */
    private function getServerIP() {
        return $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost');
    }
    
    /**
     * 自动识别域名并保存
     */
    public function autoDetectDomain() {
        $domain = $this->detectDomain();
        $protocol = $this->getProtocol();
        $serverIP = $this->getServerIP();
        
        $this->updateConfig('site_domain', $domain);
        $this->updateConfig('site_protocol', $protocol);
        $this->updateConfig('server_ip', $serverIP);
        
        // 检查数据库状态
        $this->checkDatabaseStatus();
        
        return [
            'domain' => $domain,
            'protocol' => $protocol,
            'server_ip' => $serverIP,
            'db_status' => $this->config['db_status'],
        ];
    }
    
    /**
     * 检查数据库状态
     */
    private function checkDatabaseStatus() {
        try {
            $this->db->fetchOne("SELECT 1");
            $this->updateConfig('db_status', 'normal');
        } catch (Exception $e) {
            $this->updateConfig('db_status', 'error');
        }
    }
    
    /**
     * 更新配置
     */
    public function updateConfig($key, $value) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value, config_desc) VALUES (?, ?, '') ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->bind_param('sss', $key, $value, $value);
        $stmt->execute();
        $stmt->close();
        
        $this->config[$key] = $value;
    }
    
    /**
     * 获取配置值
     */
    public function get($key, $default = '') {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 获取完整域名（带协议）
     */
    public function getFullDomain() {
        $protocol = $this->get('site_protocol', 'http');
        $domain = $this->get('site_domain', 'localhost');
        return $protocol . '://' . $domain;
    }
    
    /**
     * 获取网站路径
     */
    public function getSitePath() {
        return $this->getFullDomain() . '/';
    }
    
    /**
     * 获取后台路径
     */
    public function getAdminPath() {
        return $this->getFullDomain() . '/admin/';
    }
    
    /**
     * 获取上传路径
     */
    public function getUploadPath() {
        return $this->getFullDomain() . '/uploads/';
    }
    
    /**
     * 获取所有配置
     */
    public function getAll() {
        return $this->config;
    }
    
    /**
     * 获取系统状态
     */
    public function getSystemStatus() {
        return [
            'domain' => $this->get('site_domain'),
            'protocol' => $this->get('site_protocol'),
            'server_ip' => $this->get('server_ip'),
            'db_status' => $this->get('db_status'),
            'site_status' => $this->get('site_status'),
            'site_name' => $this->get('site_name'),
            // OSS 配置
            'oss_enabled' => $this->get('oss_enabled', 0),
            'oss_endpoint' => $this->get('oss_endpoint', ''),
            'oss_bucket' => $this->get('oss_bucket', ''),
            'oss_access_key_id' => $this->get('oss_access_key_id', ''),
            'oss_access_key_secret' => $this->get('oss_access_key_secret', ''),
            'oss_cname' => $this->get('oss_cname', ''),
            'oss_upload_dir' => $this->get('oss_upload_dir', 'shopauba'),
        ];
    }
}
