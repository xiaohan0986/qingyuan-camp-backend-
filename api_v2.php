<?php
/**
 * 统一 API 入口（新架构）
 * 
 * 所有 /api/v2/* 请求走此入口
 * 通过 nginx rewrite 指向此文件，实现统一路由分发
 * 
 * 部署方式（nginx）：
 *   location /api/v2/ {
 *       try_files $uri /api_v2.php?$query_string;
 *   }
 * 
 * 测试方式：
 *   php -S localhost:8080 api_v2.php
 */

// 加载引导
require_once __DIR__ . '/bootstrap.php';

// 加载路由
require_once ROUTE_PATH . 'adminapi.php';
// require_once ROUTE_PATH . 'api.php';  // 后续添加前端 API 路由

// 调度
Router::dispatch();
