<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 对象存储配置文件
 * 修改此文件以切换存储驱动（本地/阿里云 OSS/腾讯云 COS）
 */

return [
    // 存储驱动：local（本地）, oss（阿里云）, cos（腾讯云）
    'driver' => 'local',
    
    // 本地存储配置
    'root' => __DIR__ . '/../images',
    'url' => IMAGES_URL,  // 使用 paths.php 中定义的常量
    
    // 阿里云 OSS 配置
    'oss' => [
        'accessKeyId' => 'YOUR_ACCESS_KEY_ID',
        'accessKeySecret' => 'YOUR_ACCESS_KEY_SECRET',
        'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',  // 根据实际区域修改
        'bucket' => 'your-bucket-name'
    ],
    
    // 腾讯云 COS 配置
    'cos' => [
        'secretId' => 'YOUR_SECRET_ID',
        'secretKey' => 'YOUR_SECRET_KEY',
        'region' => 'ap-beijing',  // 根据实际区域修改
        'bucket' => 'your-bucket-123456'
    ],
    
    // 上传限制
    'max_size' => 10 * 1024 * 1024,  // 10MB
    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'],
    
    // 目录结构
    'directories' => [
        'avatars' => 'avatars',          // 头像
        'positions' => 'positions',      // 职位图片
        'articles' => 'articles',        // 文章图片
        'stores' => 'stores',            // 门店图片
        'customers' => 'customers',      // 客户文件
        'temp' => 'temp'                 // 临时文件
    ]
];
