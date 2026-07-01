<?php
/**
 * 阿里云 OSS 客户端
 * 使用简化版实现（无需 SDK）
 */
require_once __DIR__ . '/SimpleOSSClient.php';

class OSSClient {
    private static $instance = null;
    private $simpleClient;
    private $bucket;
    private $endpoint;
    private $uploadDir;
    private $cdnDomain;
    
    /**
     * 私有构造函数（单例模式）
     */
    private function __construct() {
        // 检查是否启用 OSS
        if (!defined('OSS_ENABLED') || !OSS_ENABLED) {
            return;
        }
        
        $this->bucket = OSS_BUCKET;
        $this->endpoint = OSS_ENDPOINT;
        $this->uploadDir = defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : '';
        $this->cdnDomain = defined('OSS_CNAME') ? OSS_CNAME : '';
        
        try {
            $this->simpleClient = SimpleOSSClient::getInstance();
        } catch (Exception $e) {
            throw new Exception('OSS 客户端初始化失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取单例实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 检查 OSS 是否可用
     */
    public function isAvailable() {
        return $this->simpleClient !== null && $this->simpleClient->isAvailable();
    }
    
    /**
     * 上传文件到 OSS
     * @param string $localFile 本地文件路径
     * @param string $objectName OSS 中的对象名称（包含路径）
     * @return string OSS 文件 URL
     */
    public function uploadFile($localFile, $objectName) {
        if (!$this->isAvailable()) {
            throw new Exception('OSS 未启用');
        }
        
        return $this->simpleClient->uploadFile($localFile, $objectName);
    }
    
    /**
     * 上传文件内容到 OSS
     * @param string $content 文件内容
     * @param string $objectName OSS 中的对象名称（包含路径）
     * @param array $options 选项（如 ContentType）
     * @return string OSS 文件 URL
     */
    public function uploadContent($content, $objectName, $options = []) {
        if (!$this->isAvailable()) {
            throw new Exception('OSS 未启用');
        }
        
        return $this->simpleClient->uploadContent($content, $objectName, $options);
    }
    
    /**
     * 删除 OSS 文件
     * @param string $objectName OSS 中的对象名称
     * @return bool
     */
    public function deleteFile($objectName) {
        if (!$this->isAvailable()) {
            return false;
        }
        
        return $this->simpleClient->deleteFile($objectName);
    }
    
    /**
     * 获取文件 URL
     * @param string $objectName OSS 中的对象名称
     * @return string 文件 URL
     */
    public function getFileUrl($objectName) {
        if (!$this->isAvailable()) {
            return '';
        }
        
        // 添加上传目录前缀
        if ($this->uploadDir) {
            $objectName = rtrim($this->uploadDir, '/') . '/' . ltrim($objectName, '/');
        }
        
        // 如果配置了 CDN 域名，使用 CDN 域名
        if ($this->cdnDomain) {
            return rtrim($this->cdnDomain, '/') . '/' . ltrim($objectName, '/');
        }
        
        // 否则使用 OSS 默认域名
        $protocol = strpos($this->endpoint, 'https') === 0 ? 'https' : 'http';
        $host = str_replace(['https://', 'http://'], '', $this->endpoint);
        
        return $protocol . '://' . $this->bucket . '.' . $host . '/' . ltrim($objectName, '/');
    }
    
    /**
     * 从本地路径提取相对路径
     * @param string $localPath 本地完整路径
     * @return string 相对路径
     */
    public function extractRelativePath($localPath) {
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : ROOT_PATH . '/uploads';
        return str_replace($uploadPath . '/', '', str_replace('\\', '/', $localPath));
    }
}
