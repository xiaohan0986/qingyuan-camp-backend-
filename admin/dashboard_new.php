<?php
/**
 * 青园营地管理后台 - 数据大屏 (Tailwind CSS 风格)
 */
error_reporting(E_ALL); ini_set("display_errors", 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

// 检查登录状态
Auth::check();

$db = Database::getInstance();
$admin = Auth::user();

// 获取统计数据
$today = date('Y-m-d');
$month = date('Y-m');

// 商品统计
$totalProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'] ?? 0;
$todayProducts = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE DATE(created_at) = ?", [$today])['count'] ?? 0;

// 订单统计
$totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'] ?? 0;
$todayOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?", [$today])['count'] ?? 0;
$pendingOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0;

// 会员统计
$totalMembers = $db->fetchOne("SELECT COUNT(*) as count FROM members")['count'] ?? 0;
$monthMembers = $db->fetchOne("SELECT COUNT(*) as count FROM members WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])['count'] ?? 0;

// 内容统计
$totalArticles = $db->fetchOne("SELECT COUNT(*) as count FROM articles")['count'] ?? 0;

// 销售统计
$totalSales = $db->fetchOne("SELECT SUM(amount) as total FROM orders WHERE status = 'completed'")['total'] ?? 0;
$todaySales = $db->fetchOne("SELECT SUM(amount) as total FROM orders WHERE DATE(created_at) = ? AND status = 'completed'", [$today])['total'] ?? 0;

$pageTitle = '数据大屏';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>数据大屏 - 青园营地管理系统</title>
    
    <!-- 引入 Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 引入 Font Awesome 图标 -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- 引入 Chart.js 图表 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    
    <!-- Tailwind 配置 -->
    <script src="assets/js/tailwind.config.min.js"></script>
    
    <style type="text/tailwindcss">
        @layer utilities {
            .sidebar-item-active {
                @apply bg-primary/10 text-primary font-medium;
            }
            .card-shadow {
                @apply shadow-lg shadow-black/5;
            }
            .btn {
                @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2;
            }
            .btn-primary {
                @apply bg-primary text-white hover:bg-primary/90;
            }
            .btn-success {
                @apply bg-success text-white hover:bg-success/90;
            }
            .btn-default {
                @apply bg-white border border-gray-200 hover:bg-gray-50;
            }
        }
    </style>
</head>

<body class="font-inter bg-gray-50 text-gray-800 min-h-screen">
    <?php include 'includes/sidebar_new.php'; ?>
    
    <div class="main-content flex-1 flex flex-col overflow-hidden">
        <?php include 'includes/header_new.php'; ?>
        
        <!-- 内容滚动区 -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- 页面标题 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold">数据大屏</h2>
                        <p class="text-gray-500 mt-1">欢迎回来，今日数据实时更新</p>
                    </div>
                    <button class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        <span>新增商品</span>
                    </button>
                </div>
                
                <!-- 数据看板 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 商品总数 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">商品总数</p>
                                <h3 class="text-2xl font-bold mt-2"><?= number_format($totalProducts) ?></h3>
                                <p class="text-success text-sm mt-2 flex items-center">
                                    <i class="fa fa-arrow-up mr-1"></i> 今日新增 <?= $todayProducts ?> 个
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                <i class="fa fa-shopping-bag text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 订单总数 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">订单总数</p>
                                <h3 class="text-2xl font-bold mt-2"><?= number_format($totalOrders) ?></h3>
                                <p class="text-warning text-sm mt-2 flex items-center">
                                    <i class="fa fa-clock-o mr-1"></i> 待处理 <?= $pendingOrders ?> 单
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-warning/10 flex items-center justify-center text-warning">
                                <i class="fa fa-shopping-cart text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 会员总数 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">会员总数</p>
                                <h3 class="text-2xl font-bold mt-2"><?= number_format($totalMembers) ?></h3>
                                <p class="text-success text-sm mt-2 flex items-center">
                                    <i class="fa fa-arrow-up mr-1"></i> 本月新增 <?= $monthMembers ?> 人
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center text-success">
                                <i class="fa fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 总销售额 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">总销售额</p>
                                <h3 class="text-2xl font-bold mt-2">¥<?= number_format($totalSales, 2) ?></h3>
                                <p class="text-success text-sm mt-2 flex items-center">
                                    <i class="fa fa-arrow-up mr-1"></i> 今日 ¥<?= number_format($todaySales, 2) ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center text-danger">
                                <i class="fa fa-rmb text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 快捷操作 -->
                <div class="bg-white rounded-xl p-5 card-shadow">
                    <h3 class="font-bold mb-4">快捷操作</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <a href="product_edit.php" class="btn btn-default">
                            <i class="fa fa-plus-circle"></i>
                            <span>发布商品</span>
                        </a>
                        <a href="order_list.php" class="btn btn-default">
                            <i class="fa fa-list"></i>
                            <span>订单管理</span>
                        </a>
                        <a href="member_add.php" class="btn btn-default">
                            <i class="fa fa-user-plus"></i>
                            <span>添加会员</span>
                        </a>
                        <a href="article_edit.php" class="btn btn-default">
                            <i class="fa fa-edit"></i>
                            <span>发布内容</span>
                        </a>
                        <a href="marketing.php" class="btn btn-default">
                            <i class="fa fa-bullhorn"></i>
                            <span>营销活动</span>
                        </a>
                        <a href="settings.php" class="btn btn-default">
                            <i class="fa fa-cog"></i>
                            <span>系统设置</span>
                        </a>
                    </div>
                </div>
                
                <!-- 图表区域 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- 销售趋势图 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold">销售趋势</h3>
                            <select class="text-sm border border-gray-200 rounded-lg px-2 py-1 bg-transparent">
                                <option>近 7 天</option>
                                <option>近 30 天</option>
                            </select>
                        </div>
                        <canvas id="salesChart" class="w-full h-64"></canvas>
                    </div>
                    
                    <!-- 订单分布 -->
                    <div class="bg-white rounded-xl p-5 card-shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold">订单分布</h3>
                            <button class="text-sm text-primary">
                                <i class="fa fa-external-link"></i> 查看详情
                            </button>
                        </div>
                        <canvas id="orderChart" class="w-full h-64"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // 销售趋势图表
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['1 日', '5 日', '10 日', '15 日', '20 日', '25 日', '30 日'],
            datasets: [{
                label: '销售额',
                data: [5200, 6800, 7500, 6200, 8900, 9500, 11200],
                borderColor: '#165DFF',
                backgroundColor: 'rgba(22, 93, 255, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
    
    // 订单分布图表
    const orderCtx = document.getElementById('orderChart').getContext('2d');
    new Chart(orderCtx, {
        type: 'doughnut',
        data: {
            labels: ['已完成', '待处理', '已取消'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: ['#00B42A', '#FF7D00', '#F53F3F'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    </script>
</body>
</html>
