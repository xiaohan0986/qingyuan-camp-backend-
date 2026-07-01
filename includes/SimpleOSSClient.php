<?php
/**
 * 阿里云 OSS 客户端（简化版 - 无需 SDK）
 * 使用原生 PHP cURL 实现 OSS API
 */
class SimpleOSSClient {
    private static $instance = null;
    private $accessKeyId;
    private $accessKeySecret;
    private $endpoint;
    private $bucket;
    private $uploadDir;
    private $cdnDomain;
    
    /**
     * 私有构造函数（单例模式）
     */
    private function __construct() {
        if (!defined('OSS_ENABLED') || !OSS_ENABLED) {
            return;
        }
        
        $this->accessKeyId = defined('OSS_ACCESS_KEY_ID') ? OSS_ACCESS_KEY_ID : '';
        $this->accessKeySecret = defined('OSS_ACCESS_KEY_SECRET') ? OSS_ACCESS_KEY_SECRET : '';
        $this->endpoint = defined('OSS_ENDPOINT') ? OSS_ENDPOINT : '';
        $this->bucket = defined('OSS_BUCKET') ? OSS_BUCKET : '';
        $this->uploadDir = defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : '';
        $this->cdnDomain = defined('OSS_CNAME') ? OSS_CNAME : '';
        
        if (empty($this->accessKeyId) || empty($this->accessKeySecret) || empty($this->endpoint) || empty($this->bucket)) {
            throw new Exception('OSS 配置不完整，请检查 config/oss.php');
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
        return !empty($this->accessKeyId) && !empty($this->accessKeySecret);
    }
    
    /**
     * 上传文件到 OSS
     * @param string $localFile 本地文件路径
     * @param string $objectName OSS 中的对象名称
     * @return string OSS 文件 URL
     */
    public function uploadFile($localFile, $objectName) {
        if (!file_exists($localFile)) {
            throw new Exception('本地文件不存在：' . $localFile);
        }
        
        $fileContent = file_get_contents($localFile);
        $mimeType = $this->getMimeType($localFile);
        
        return $this->uploadContent($fileContent, $objectName, ['ContentType' => $mimeType]);
    }
    
    /**
     * 上传文件内容到 OSS
     * @param string $content 文件内容
     * @param string $objectName OSS 中的对象名称
     * @param array $options 选项
     * @return string OSS 文件 URL
     */
    public function uploadContent($content, $objectName, $options = []) {
        // 添加上传目录前缀
        if ($this->uploadDir) {
            $objectName = rtrim($this->uploadDir, '/') . '/' . ltrim($objectName, '/');
        }
        
        $url = $this->buildObjectUrl($objectName);
        $method = 'PUT';
        $date = gmdate('D, d M Y H:i:s', time()) . ' GMT';
        
        // 构建请求头
        $contentType = isset($options['ContentType']) ? $options['ContentType'] : 'application/octet-stream';
        $contentMd5 = base64_encode(md5($content, true));
        $contentLength = strlen($content);
        
        // 构建签名字符串（OSS API 标准格式）
        // VERB + "\n" + Content-MD5 + "\n" + Content-Type + "\n" + Date + "\n" + CanonicalizedOSSHeaders + CanonicalizedResource
        $canonicalizedOSSHeaders = "x-oss-date:" . $date . "\n";
        $canonicalizedResource = '/' . $this->bucket . '/' . $objectName;
        
        $stringToSign = $method . "\n";
        $stringToSign .= $contentMd5 . "\n";
        $stringToSign .= $contentType . "\n";
        $stringToSign .= $date . "\n";
        $stringToSign .= $canonicalizedOSSHeaders;
        $stringToSign .= $canonicalizedResource;
        
        // 计算签名
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));
        $authorization = "OSS " . $this->accessKeyId . ":" . $signature;
        
        // 发送请求
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        
        $httpHeaders = [
            'Authorization: ' . $authorization,
            'Date: ' . $date,
            'Content-MD5: ' . $contentMd5,
            'Content-Type: ' . $contentType,
            'Content-Length: ' . $contentLength,
            'x-oss-date: ' . $date,
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("OSS 上传失败 (HTTP $httpCode): " . ($response ?: $error));
        }
        
        return $url;
    }
    
    /**
     * 删除 OSS 文件
     */
    public function deleteFile($objectName) {
        // 检查是否已经包含上传目录前缀，避免重复添加
        if ($this->uploadDir && strpos($objectName, rtrim($this->uploadDir, '/') . '/') !== 0) {
            $objectName = rtrim($this->uploadDir, '/') . '/' . ltrim($objectName, '/');
        }
        
        $url = $this->buildObjectUrl($objectName);
        $method = 'DELETE';
        $date = gmdate('D, d M Y H:i:s', time()) . ' GMT';
        
        // 与上传保持一致的签名格式（使用 x-oss-date）
        $canonicalizedOSSHeaders = "x-oss-date:" . $date . "\n";
        $canonicalizedResource = '/' . $this->bucket . '/' . $objectName;
        
        // DELETE 请求没有 Content-MD5 和 Content-Type，所以为空
        $stringToSign = $method . "\n";
        $stringToSign .= "\n";  // Content-MD5 为空
        $stringToSign .= "\n";  // Content-Type 为空
        $stringToSign .= $date . "\n";
        $stringToSign .= $canonicalizedOSSHeaders;
        $stringToSign .= $canonicalizedResource;
        
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));
        $authorization = "OSS {$this->accessKeyId}:{$signature}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $authorization,
            'Date: ' . $date,
            'x-oss-date: ' . $date,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 204 && $httpCode !== 200) {
            error_log("OSS 删除失败 (HTTP $httpCode): " . ($response ?: $error));
        }
        
        return ($httpCode === 204 || $httpCode === 200);
    }
    
    /**
     * 获取文件 URL
     */
    public function getFileUrl($objectName) {
        // 添加上传目录前缀（和 uploadContent 保持一致）
        if ($this->uploadDir) {
            $objectName = rtrim($this->uploadDir, '/') . '/' . ltrim($objectName, '/');
        }
        
        if ($this->cdnDomain) {
            return rtrim($this->cdnDomain, '/') . '/' . ltrim($objectName, '/');
        }
        
        $protocol = strpos($this->endpoint, 'https') === 0 ? 'https' : 'http';
        $host = str_replace(['https://', 'http://'], '', $this->endpoint);
        
        return $protocol . '://' . $this->bucket . '.' . $host . '/' . ltrim($objectName, '/');
    }
    
    /**
     * 构建对象 URL
     */
    private function buildObjectUrl($objectName) {
        $protocol = strpos($this->endpoint, 'https') === 0 ? 'https' : 'http';
        $host = str_replace(['https://', 'http://'], '', $this->endpoint);
        
        return $protocol . '://' . $this->bucket . '.' . $host . '/' . $objectName;
    }
    
    /**
     * 构建规范化 Header
     */
    private function buildCanonicalizedHeaders($headers) {
        $result = '';
        ksort($headers);
        foreach ($headers as $key => $value) {
            if (strpos($key, 'x-oss-') === 0) {
                $result .= strtolower($key) . ':' . $value . "\n";
            }
        }
        return $result;
    }
    
    /**
     * 获取 MIME 类型
     */
    private function getMimeType($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
        ];
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
}
