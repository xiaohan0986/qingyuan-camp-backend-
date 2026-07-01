<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * DX Storage 远程存储服务配置
 * 
 * 所有环境统一使用远程 API（dx.gofong.com）
 */

// 远程服务地址
$baseUrl = 'https://dx.gofong.com'; // 同服务器部署 - 使用 HTTPS（避免混合内容警告）
// $baseUrl = 'http://dx.gofong.com'; // HTTP 访问（会有 Mixed Content 警告）
$mode = '同服务器部署模式（dx.gofong.com HTTPS）';

return [
    // 存储服务基础 URL（自动检测）
    'base_url' => $baseUrl,
    
    // 当前模式说明
    'mode' => $mode,
    
    // API 端点
    // 文件操作 API：使用远程服务器实际路径
    'api' => [
        'upload' => $baseUrl . '/api/upload.php',
        'files' => $baseUrl . '/api/files.php',  // action 在调用时添加
        'delete' => $baseUrl . '/api/delete.php',  // 独立的删除 API
        'batch_delete' => $baseUrl . '/api/delete.php',  // 复用 delete.php（支持批量）
        // 分组管理（前端直接使用，保留 action 参数）
        'groups' => $baseUrl . '/api/groups.php?action=list',
        'create_group' => $baseUrl . '/api/groups.php?action=create',
        'delete_group' => $baseUrl . '/api/groups.php?action=delete',
        'move_file' => $baseUrl . '/api/move_file.php',
    ],
    
    // 前端展示配置
    'frontend' => [
        // 文件夹视图：始终使用线上 URL
        'admin_page' => $baseUrl . '/admin/',
        'folder_view' => $baseUrl . '/admin/folder_view.php',
    ],
    
    // 上传配置（与远程服务保持一致）
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
    ],
];
