<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 对象存储接口类
 * 支持本地存储和云存储（阿里云 OSS、腾讯云 COS 等）
 */

class Storage {
    private $config;
    private $basePath;
    
    /**
     * 构造函数
     * @param array $config 存储配置
     */
    public function __construct($config = []) {
        $this->config = array_merge([
            'driver' => 'local',  // local, oss, cos
            'root' => __DIR__ . '/../uploads',
            'url' => BASE_URL . '/uploads',
            // 阿里云 OSS 配置
            'oss' => [
                'accessKeyId' => '',
                'accessKeySecret' => '',
                'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
                'bucket' => ''
            ],
            // 腾讯云 COS 配置
            'cos' => [
                'secretId' => '',
                'secretKey' => '',
                'region' => 'ap-beijing',
                'bucket' => ''
            ]
        ], $config);
        
        // 规范化 basePath（解析 .. 和 .，统一使用正斜杠）
        $realRoot = realpath($this->config['root']);
        $this->basePath = $realRoot ? str_replace('\\', '/', $realRoot) : str_replace('\\', '/', rtrim($this->config['root'], '/'));
        
        // 确保上传目录存在
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }
    
    /**
     * 上传文件
     * @param string $file 文件路径（$_FILES['file']['tmp_name']）
     * @param string $filename 文件名
     * @param string $subDir 子目录
     * @return array ['success' => bool, 'url' => string, 'path' => string, 'error' => string]
     */
    public function upload($file, $filename = null, $subDir = '') {
        if (!file_exists($file)) {
            return ['success' => false, 'error' => '文件不存在'];
        }
        
        // 生成文件名
        if ($filename === null) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $filename = 'file_' . time() . '_' . uniqid() . '.' . $ext;
        }
        
        // 构建路径
        $subDir = trim($subDir, '/');
        $targetPath = $subDir ? $this->basePath . '/' . $subDir : $this->basePath;
        
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
        
        $targetFile = $targetPath . '/' . $filename;
        
        // 根据驱动类型上传
        if ($this->config['driver'] === 'local') {
            return $this->uploadLocal($file, $targetFile, $filename, $subDir);
        } elseif ($this->config['driver'] === 'oss') {
            return $this->uploadOss($file, $filename, $subDir);
        } elseif ($this->config['driver'] === 'cos') {
            return $this->uploadCos($file, $filename, $subDir);
        }
        
        return ['success' => false, 'error' => '不支持的存储驱动'];
    }
    
    /**
     * 本地存储上传
     */
    private function uploadLocal($file, $targetFile, $filename, $subDir) {
        if (move_uploaded_file($file, $targetFile)) {
            $url = rtrim($this->config['url'], '/') . '/' . ($subDir ? $subDir . '/' : '') . $filename;
            return [
                'success' => true,
                'url' => $url,
                'path' => $targetFile,
                'filename' => $filename,
                'size' => filesize($targetFile)
            ];
        }
        
        return ['success' => false, 'error' => '文件移动失败'];
    }
    
    /**
     * 阿里云 OSS 上传
     */
    private function uploadOss($file, $filename, $subDir) {
        // 需要安装阿里云 OSS SDK: composer require aliyuncs/oss-sdk-php
        if (!class_exists('OSS\OssClient')) {
            return ['success' => false, 'error' => '未安装阿里云 OSS SDK'];
        }
        
        try {
            $ossClient = new \OSS\OssClient(
                $this->config['oss']['accessKeyId'],
                $this->config['oss']['accessKeySecret'],
                $this->config['oss']['endpoint']
            );
            
            $object = ($subDir ? $subDir . '/' : '') . $filename;
            $result = $ossClient->uploadFile($this->config['oss']['bucket'], $object, $file);
            
            return [
                'success' => true,
                'url' => $result['info']['url'],
                'filename' => $filename
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 腾讯云 COS 上传
     */
    private function uploadCos($file, $filename, $subDir) {
        // 需要安装腾讯云 COS SDK: composer require qcloud/cos-sdk-v5
        if (!class_exists('COS\CosClient')) {
            return ['success' => false, 'error' => '未安装腾讯云 COS SDK'];
        }
        
        try {
            $cosClient = new \COS\CosClient([
                'appId' => $this->config['cos']['bucket'],
                'secretId' => $this->config['cos']['secretId'],
                'secretKey' => $this->config['cos']['secretKey'],
                'region' => $this->config['cos']['region']
            ]);
            
            $key = ($subDir ? $subDir . '/' : '') . $filename;
            $result = $cosClient->putObject([
                'Bucket' => $this->config['cos']['bucket'],
                'Key' => $key,
                'Body' => fopen($file, 'rb')
            ]);
            
            $url = $cosClient->getObjectUrl($this->config['cos']['bucket'], $key);
            
            return [
                'success' => true,
                'url' => $url,
                'filename' => $filename
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 删除文件
     * @param string $path 文件路径（相对路径或 URL）
     * @return array ['success' => bool, 'error' => string]
     */
    public function delete($path) {
        // 如果是 URL，转换为相对路径
        if (strpos($path, $this->config['url']) === 0) {
            $path = str_replace($this->config['url'], '', $path);
            $path = ltrim($path, '/');
        }
        
        $filePath = $this->basePath . '/' . $path;
        
        if ($this->config['driver'] === 'local') {
            if (file_exists($filePath) && unlink($filePath)) {
                return ['success' => true];
            }
            return ['success' => false, 'error' => '文件不存在或删除失败'];
        }
        
        // 云存储删除逻辑...
        return ['success' => false, 'error' => '未实现'];
    }
    
    /**
     * 获取文件列表
     * @param string $subDir 子目录
     * @return array 文件列表
     */
    public function listFiles($subDir = '') {
        $targetPath = $subDir ? $this->basePath . DIRECTORY_SEPARATOR . $subDir : $this->basePath;
        
        if (!is_dir($targetPath)) {
            return [];
        }
        
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetPath)
        );
        
        // 规范化 basePath（统一使用正斜杠）
        $normalizedBasePath = str_replace('\\', '/', realpath($this->basePath));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                // 获取规范化路径
                $pathname = str_replace('\\', '/', $file->getPathname());
                // 计算相对路径
                $relativePath = str_replace($normalizedBasePath . '/', '', $pathname);
                
                $files[] = [
                    'name' => $file->getFilename(),
                    'path' => $relativePath,
                    'url' => rtrim($this->config['url'], '/') . '/' . $relativePath,
                    'size' => $file->getSize(),
                    'mtime' => $file->getMTime(),
                    'type' => $this->getFileType($file->getExtension())
                ];
            }
        }
        
        return $files;
    }
    
    /**
     * 获取文件类型
     */
    private function getFileType($ext) {
        $types = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz']
        ];
        
        $ext = strtolower($ext);
        foreach ($types as $type => $extensions) {
            if (in_array($ext, $extensions)) {
                return $type;
            }
        }
        
        return 'file';
    }
    
    /**
     * 获取存储配置
     */
    public function getConfig() {
        return $this->config;
    }
}
