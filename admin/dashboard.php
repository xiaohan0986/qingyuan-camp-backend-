<?php
/**
 * 青园营地管理后台 - 数据大屏
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

// [优化] 合并所有统计查询为 1 次数据库请求（原 9 次独立查询）
$statsSql = "SELECT
    (SELECT COALESCE(SUM(pay_amount), 0) FROM orders WHERE status = 'completed') as total_sales,
    (SELECT COALESCE(SUM(pay_amount), 0) FROM orders WHERE DATE(created_at) = ? AND status = 'completed') as today_sales,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?) as today_orders,
    (SELECT COUNT(*) FROM members) as total_members,
    (SELECT COUNT(*) FROM members WHERE DATE(created_at) = ?) as today_members,
    (SELECT COALESCE(COUNT(*), 0) FROM members WHERE DATE(last_login_at) = ?) as total_dau,
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM products WHERE DATE(created_at) = ?) as today_products";
$statsParams = [$today, $today, $today, $today, $today];

try {
    $statsRow = $db->fetchOne($statsSql, $statsParams);
} catch (Exception $e) {
    error_log("Dashboard stats query failed: " . $e->getMessage());
    $statsRow = [];
}

// 从合并结果中提取各个统计值
$totalSales = $statsRow['total_sales'] ?? 0;
$todaySales = $statsRow['today_sales'] ?? 0;
$totalOrders = $statsRow['total_orders'] ?? 0;
$todayOrders = $statsRow['today_orders'] ?? 0;
$totalMembers = $statsRow['total_members'] ?? 0;
$todayMembers = $statsRow['today_members'] ?? 0;
$totalDau = $statsRow['total_dau'] ?? 0;
$totalProducts = $statsRow['total_products'] ?? 0;
$todayProducts = $statsRow['today_products'] ?? 0;

// 合伙人列表（TOP7，按成交金额排序）
$partnerData = [];
try {
    $partnerQuery = "SELECT 
        m.id,
        m.nickname as name,
        m.avatar,
        m.phone,
        m.created_at,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.amount ELSE 0 END), 0) as total_sales,
        COALESCE(COUNT(DISTINCT o.id), 0) as order_count,
        COALESCE((
            SELECT COUNT(*) FROM members sub_m 
            WHERE sub_m.parent_id = m.id
        ), 0) as direct_downline_count,
        COALESCE((
            SELECT COUNT(*) FROM members sub_m 
            WHERE sub_m.parent_id = m.id
            OR sub_m.parent_id IN (
                SELECT id FROM members WHERE parent_id = m.id
            )
        ), 0) as total_downline_count
        FROM members m
        LEFT JOIN orders o ON m.id = o.member_id
        GROUP BY m.id, m.nickname, m.avatar, m.phone, m.created_at
        HAVING m.id IS NOT NULL
        ORDER BY total_sales DESC
        LIMIT 7";
    $stmt = $db->query($partnerQuery);
    $result = $stmt->get_result();
    $partnerData = $result->fetch_all(MYSQLI_ASSOC);
    // 调试输出
    error_log("合伙人数据：" . count($partnerData) . " 条");
} catch (Exception $e) {
    error_log("合伙人查询失败：" . $e->getMessage());
    $partnerData = [];
}

// 问候语和祝福语
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = '早安';
} elseif ($hour >= 12 && $hour < 14) {
    $greeting = '午安';
} elseif ($hour >= 14 && $hour < 18) {
    $greeting = '下午好';
} elseif ($hour >= 18 && $hour < 23) {
    $greeting = '晚上好';
} else {
    $greeting = '夜深了';
}

// 每天不一样的祝福语（根据日期哈希）
$dayHash = (int)date('md');
$blessings = [
    '今天也是充满活力的一天，加油！✨',
    '保持热爱，奔赴山海 🌟',
    '努力的人，运气都不会太差 💪',
    '心怀梦想，脚踏实地 🚀',
    '每一天都是新的开始 🌈',
    '做自己的光，不需要太亮 🌻',
    '生活明朗，万物可爱 😊',
    '越努力，越幸运 🍀',
    '不忘初心，方得始终 💫',
    '今天也要元气满满哦 🎉',
    '慢慢来，比较快 🐢',
    '你比昨天更优秀了 🏆',
];
$blessing = $blessings[$dayHash % count($blessings)];

// 格式化日期显示
$weekDays = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
$weekDay = $weekDays[(int)date('w')];
$dateFormat = date('Y 年 m 月 d 日') . ' ' . $weekDay;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据大屏 - 青园营地管理后台</title>
    <link rel="stylesheet" href="assets/css/admin.min.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
    <link rel="stylesheet" href="assets/css/layout.min.css?v=<?= filemtime(__DIR__ . '/assets/css/layout.css') ?>">
    <!-- 引入 Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* 侧边栏已由 sidebar.php 控制，此处不再隐藏 */
    
    /* 折线图组件样式 */
    .business-trend-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        margin-top: 8px;
    }
    
    .business-trend-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .business-trend-title {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .time-selector {
        display: flex;
        gap: 8px;
    }
    
    .time-btn {
        padding: 8px 16px;
        border: 1px solid #d9d9d9;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        color: #666;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .time-btn:hover {
        color: #1890ff;
        border-color: #1890ff;
    }
    
    .time-btn.active {
        background: #1890ff;
        color: white;
        border-color: #1890ff;
    }
    
    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
    }
    
    .data-legend {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }
    
    .legend-dot {
        width: 20px;
        height: 10px;
        border-radius: 2px;
        border: none;
    }
    
    .legend-dot.sales { background: #fa8c16; }
    .legend-dot.orders { background: #1890ff; }
    .legend-dot.members { background: #52c41a; }
    
    .data-description {
        margin-top: 20px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #f6f8fa, #fafafa);
        border-radius: 8px;
        font-size: 13px;
        color: #666;
        line-height: 1.8;
        border-left: 4px solid #1890ff;
    }
    
    .data-description strong {
        color: #262626;
        font-weight: 600;
    }
    
    .data-description .highlight {
        color: #1890ff;
        font-weight: 500;
    }
    
    @media (max-width: 900px) {
        .business-trend-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .time-selector {
            flex-wrap: wrap;
        }
        
        .data-legend {
            flex-wrap: wrap;
            gap: 20px;
        }
    }
    
    /* 背景图片 - 置于渐变上方 */
    .bg-image {
        position: fixed;
        top: 20px;
        right: 60px;
        width: 500px;
        height: 350px;
        z-index: 1;
        pointer-events: none;
        overflow: hidden;
    }
    
    .bg-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        opacity: 1;
    }
    
    /* 问候组件 */
    .greeting-card {
        background: transparent;
        border-radius: 16px;
        padding: 0;
        margin: 0;
        margin-bottom: 16px;
        box-shadow: 0;
    }
    
    .greeting-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #000000;
        font-family: 'PingFang SC', 'Microsoft YaHei', 'Helvetica Neue', Arial, sans-serif;
    }
    
    .greeting-date {
        font-size: 14px;
        margin-bottom: 12px;
        color: #666666;
        font-family: 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
    }
    
    .greeting-blessing {
        font-size: 15px;
        color: #666666;
        font-family: 'Hiragino Sans GB', 'PingFang SC', 'Microsoft YaHei', sans-serif;
    }
    
    .stats-container {
        padding: 16px;
        position: relative;
        z-index: 2;
    }
    
    /* 仪表板布局 */
    .dashboard-row {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: stretch;
    }
    
    .dashboard-left {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    
    .quick-actions {
        margin-top: 0;
    }
    
    .dashboard-right {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    
    /* 待办事项面板 */
    .todo-panel {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        height: fit-content;
        margin-bottom: 24px;
    }
    
    /* 合伙人面板 - 自动填充剩余高度 */
    .sales-rank-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    
    .sales-rank-panel .rank-list {
        flex: 1;
        overflow-y: auto;
    }
    
    /* 销售排名面板 */
    .sales-rank-panel {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    
    .rank-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .rank-header h3 {
        margin: 0;
        color: #262626;
        font-size: 18px;
    }
    
    .rank-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .rank-period {
        font-size: 13px;
        color: #8c8c8c;
        background: #f5f5f5;
        padding: 4px 12px;
        border-radius: 12px;
    }
    
    .view-all-btn {
        font-size: 13px;
        color: #1890ff;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .view-all-btn:hover {
        color: #40a9ff;
        text-decoration: underline;
    }
    
    .rank-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        overflow-y: auto;
        /* 隐藏滚动条 */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    
    .rank-list::-webkit-scrollbar {
        display: none; /* Chrome/Safari/Opera */
    }
    
    .rank-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: #fafafa;
        transition: all 0.3s;
        position: relative;
    }
    
    .rank-item:hover {
        background: #f5f5f5;
    }
    
    .rank-num {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .rank-num.gold {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #8b6900;
    }
    
    .rank-num.silver {
        background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
        color: #666;
    }
    
    .rank-num.bronze {
        background: linear-gradient(135deg, #cd7f32, #e8a87c);
        color: #8b4513;
    }
    
    .rank-num.normal {
        background: #f0f0f0;
        color: #666;
    }
    
    .rank-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }
    
    .rank-info {
        flex: 1;
        min-width: 0;
        cursor: pointer;
        position: relative;
    }
    
    .rank-name {
        font-size: 14px;
        color: #262626;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
    }
    
    .rank-stats {
        font-size: 12px;
        color: #8c8c8c;
    }
    
    .rank-stats strong {
        color: #1890ff;
        font-weight: 600;
    }
    
    /* 悬停提示框 */
    .rank-tooltip {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%) translateY(8px);
        background: rgba(0, 0, 0, 0.9);
        color: #fff;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 100;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    
    .rank-tooltip::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-bottom: 6px solid rgba(0, 0, 0, 0.9);
    }
    
    .rank-info:hover .rank-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(4px);
    }
    
    .tooltip-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 6px;
    }
    
    .tooltip-row:last-child {
        margin-bottom: 0;
    }
    
    .tooltip-label {
        color: #aaa;
    }
    
    .tooltip-value {
        color: #fff;
        font-weight: 600;
    }
    
    .tooltip-value.highlight {
        color: #52c41a;
    }
    
    .todo-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .todo-header h3 {
        margin: 0;
        color: #262626;
        font-size: 18px;
    }
    
    .todo-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .todo-count {
        font-size: 13px;
        color: #8c8c8c;
        background: #f5f5f5;
        padding: 4px 12px;
        border-radius: 12px;
    }
    
    .view-all-btn {
        font-size: 13px;
        color: #1890ff;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .view-all-btn:hover {
        color: #40a9ff;
        text-decoration: underline;
    }
    
    .todo-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .todo-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: #fafafa;
        transition: all 0.3s;
    }
    
    .todo-item:hover {
        background: #f5f5f5;
    }
    
    .todo-checkbox {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        cursor: pointer;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .todo-checkbox input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .checkmark {
        width: 20px;
        height: 20px;
        border: 2px solid #d9d9d9;
        border-radius: 6px;
        transition: all 0.3s;
    }
    
    .todo-checkbox input:checked + .checkmark {
        background: #52c41a;
        border-color: #52c41a;
    }
    
    .todo-checkbox input:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 14px;
        font-weight: bold;
    }
    
    .todo-content {
        flex: 1;
        min-width: 0;
    }
    
    .todo-title {
        font-size: 14px;
        color: #262626;
        margin-bottom: 6px;
        font-weight: 500;
    }
    
    .todo-checkbox input:checked ~ .todo-content .todo-title {
        color: #8c8c8c;
        text-decoration: line-through;
    }
    
    .todo-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .todo-tag {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .todo-tag.urgent {
        background: #fff1f0;
        color: #ff4d4f;
    }
    
    .todo-tag.normal {
        background: #e6f7ff;
        color: #1890ff;
    }
    
    .todo-tag.low {
        background: #f6ffed;
        color: #52c41a;
    }
    
    .todo-time {
        font-size: 12px;
        color: #8c8c8c;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .stat-card {
        min-width: 0;
    }
    
    .stat-card {
        border-radius: 12px;
        padding: 24px 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
        border: none;
        min-height: 140px;
    }
    
    .stat-card:nth-child(1) { background: linear-gradient(135deg, #e6f7ff, #ffffff); }
    .stat-card:nth-child(2) { background: linear-gradient(135deg, #f9f0ff, #ffffff); }
    .stat-card:nth-child(3) { background: linear-gradient(135deg, #f6ffed, #ffffff); }
    .stat-card:nth-child(4) { background: linear-gradient(135deg, #fff7e6, #ffffff); }
    .stat-card:nth-child(5) { 
        display: none;
        background: linear-gradient(135deg, #fff0f6, #ffffff);
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    
    .stat-icon {
        position: absolute;
        right: 0px;
        top: 16px;
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon img {
        width: 56px;
        height: 56px;
        object-fit: contain;
    }
    
    .stat-label {
        font-size: 13px;
        color: #8c8c8c;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #262626;
        margin-bottom: 6px;
    }
    
    .stat-trend {
        font-size: 12px;
        color: #8c8c8c;
    }
    
    .stat-trend.up {
        color: #52c41a;
    }
    
    .stat-trend.down {
        color: #ff4d4f;
    }
    
    .quick-actions {
        background: transparent;
        border-radius: 16px;
        padding: 0 0 16px 0;
        margin-bottom: 8px;
    }
    
    .quick-actions h3 {
        display: none;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        width: 100%;
    }
    
    .action-btn {
        min-width: 0;
        white-space: nowrap;
    }
    
    .action-btn {
        padding: 16px;
        border-radius: 12px;
        border: none;
        background: white;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        display: block;
        color: #262626;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
    
    .action-btn:hover {
        border-color: #1890ff;
        background: linear-gradient(135deg, #f0f5ff, #e6f7ff);
        transform: translateY(-2px);
    }
    
    .action-btn .icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        margin: 0 auto 8px;
        overflow: hidden;
    }
    
    .action-btn .icon img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }
    
    .action-btn .text {
        font-size: 14px;
        font-weight: 700;
        color: #000000;
    }
    
    .action-btn:nth-child(1) .icon { background: linear-gradient(135deg, #1890ff, #69c0ff); }
    .action-btn:nth-child(2) .icon { background: linear-gradient(135deg, #722ed1, #b37feb); }
    .action-btn:nth-child(3) .icon { background: linear-gradient(135deg, #52c41a, #95de64); }
    .action-btn:nth-child(4) .icon { background: linear-gradient(135deg, #fa8c16, #ffd666); }
    .action-btn:nth-child(5) .icon { background: linear-gradient(135deg, #eb2f96, #ffadd2); }
    .action-btn:nth-child(6) .icon { background: linear-gradient(135deg, #13c2c2, #36cfc9); }
    
    .charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 24px;
    }
    
    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
    
    .chart-card h3 {
        margin-bottom: 20px;
        color: #262626;
        font-size: 18px;
    }
    
    /* 响应式布局 */
    @media (max-width: 1400px) {
        .dashboard-row {
            grid-template-columns: 1fr;
        }
        
        .dashboard-right {
            order: -1;
        }
    }
    
    /* 确保数据卡片始终一行显示 */
    @media (max-width: 900px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        
        .stat-card {
            padding: 16px 12px;
            min-height: 120px;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
        }
        
        .stat-icon img {
            width: 40px;
            height: 40px;
        }
        
        .stat-label {
            font-size: 12px;
        }
        
        .stat-value {
            font-size: 20px;
        }
        
        .stat-trend {
            font-size: 11px;
        }
    }
    
    /* 确保快捷操作始终一行显示 */
    @media (max-width: 900px) {
        .action-grid {
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
        }
        
        .action-btn {
            padding: 12px 8px;
        }
        
        .action-btn .icon {
            width: 40px;
            height: 40px;
        }
        
        .action-btn .icon img {
            width: 24px;
            height: 24px;
        }
        
        .action-btn .text {
            font-size: 12px;
        }
    }
    </style>
</head>
<body class="admin-body">
    <!-- 背景图片 -->
    <!-- 背景图片（WebP格式，从 2MB 压缩至 170KB） -->
    <div class="bg-image">
        <picture>
            <source srcset="../images/beijing.webp" type="image/webp">
            <img src="../images/beijing.png" alt="背景图" loading="lazy" decoding="async" width="1536" height="1024">
        </picture>
    </div>
    
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <div class="stats-container">
                <!-- 问候组件 -->
                <div class="greeting-card">
                    <div class="greeting-title"><?= $greeting ?>，<?= htmlspecialchars($admin['name']) ?>！</div>
                    <div class="greeting-date">今天是：<?= $dateFormat ?></div>
                    <div class="greeting-blessing"><?= $blessing ?></div>
                </div>
                
                <div class="dashboard-row">
                    <!-- 左侧：快捷操作 + 数据卡片 -->
                    <div class="dashboard-left">
                        <!-- 快捷操作 -->
                        <div class="quick-actions">
                            <h3>快捷操作</h3>
                            <div class="action-grid">
                                <a href="/admin/notice/publish.php" class="action-btn">
                                    <div class="icon"><img src="../images/111.png" alt="发布公告" /></div>
                                    <div class="text">发布公告</div>
                                </a>
                                <a href="/admin/todo/add.php" class="action-btn">
                                    <div class="icon"><img src="../images/222.png" alt="新增待办" /></div>
                                    <div class="text">新增待办</div>
                                </a>
                                <a href="/admin/approval/index.php" class="action-btn">
                                    <div class="icon"><img src="../images/333.png" alt="审批处理" /></div>
                                    <div class="text">审批处理</div>
                                </a>
                                <a href="/admin/aftermarket/index.php" class="action-btn">
                                    <div class="icon"><img src="../images/444.png" alt="售后处理" /></div>
                                    <div class="text">售后处理</div>
                                </a>
                                <a href="/admin/task/assign.php" class="action-btn">
                                    <div class="icon"><img src="../images/555.png" alt="下发任务" /></div>
                                    <div class="text">下发任务</div>
                                </a>
                                <a href="/admin/user/index.php" class="action-btn">
                                    <div class="icon"><img src="../images/666.png" alt="用户管理" /></div>
                                    <div class="text">用户管理</div>
                                </a>
                            </div>
                        </div>
                        
                        <!-- 数据卡片 -->
                        <div class="stats-grid">
                            <!-- 总销售额 -->
                            <div class="stat-card">
                                <div class="stat-icon"><img src="../images/1111.png" alt="销售额" /></div>
                                <div class="stat-label">总销售额</div>
                                <div class="stat-value">¥<?= number_format($totalSales, 2) ?></div>
                                <div class="stat-trend up">↑ 今日 ¥<?= number_format($todaySales, 2) ?></div>
                            </div>
                            
                            <!-- 总订单数 -->
                            <div class="stat-card">
                                <div class="stat-icon"><img src="../images/2222.png" alt="订单数" /></div>
                                <div class="stat-label">总订单数</div>
                                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                                <div class="stat-trend up">↑ 今日 <?= $todayOrders ?> 单</div>
                            </div>
                            
                            <!-- 总会员数 -->
                            <div class="stat-card">
                                <div class="stat-icon"><img src="../images/3333.png" alt="会员数" /></div>
                                <div class="stat-label">总会员数</div>
                                <div class="stat-value"><?= number_format($totalMembers) ?></div>
                                <div class="stat-trend up">↑ 今日 <?= $todayMembers ?> 人</div>
                            </div>
                            
                            <!-- 总日活数 -->
                            <div class="stat-card">
                                <div class="stat-icon"><img src="../images/4444.png" alt="日活数" /></div>
                                <div class="stat-label">总日活数</div>
                                <div class="stat-value"><?= number_format($totalDau) ?></div>
                                <div class="stat-trend">今日活跃用户</div>
                            </div>
                            
                            <!-- 总商品数 -->
                            <div class="stat-card">
                                <div class="stat-icon"><img src="../images/5555.png" alt="商品数" /></div>
                                <div class="stat-label">总商品数</div>
                                <div class="stat-value"><?= number_format($totalProducts) ?></div>
                                <div class="stat-trend up">↑ 今日 <?= $todayProducts ?> 个</div>
                            </div>
                        </div>
                        
                        <!-- 业务趋势折线图 -->
                        <div class="business-trend-section">
                            <div class="business-trend-header">
                                <div class="business-trend-title">
                                    业务数据趋势
                                </div>
                                <div class="time-selector">
                                    <button class="time-btn active" data-days="7">近 7 天</button>
                                    <button class="time-btn" data-days="30">近 30 天</button>
                                    <button class="time-btn" data-days="90">近 3 月</button>
                                    <button class="time-btn" data-days="365">近 1 年</button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="businessTrendChart"></canvas>
                            </div>
                            <div class="data-legend">
                                <div class="legend-item">
                                    <div class="legend-dot sales"></div>
                                    <span>销售数据</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot orders"></div>
                                    <span>订单数据</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-dot members"></div>
                                    <span>会员数据</span>
                                </div>
                            </div>
                            <!-- 数据说明已隐藏
                            <div class="data-description">
                                <strong>数据说明：</strong>
                                折线图展示不同时间范围内的业务趋势变化。
                                <span class="highlight">橙色线</span>代表销售金额（元），
                                <span class="highlight">蓝色线</span>代表订单数量（单），
                                <span class="highlight">绿色线</span>代表新增会员数（人）。
                                X 轴显示日期，Y 轴显示数量。可通过上方时间选择器切换查看不同时间跨度的数据趋势。
                            </div>
                            -->
                        </div>
                    </div>
                    
                    <!-- 右侧：待办事项 -->
                    <div class="dashboard-right">
                        <div class="todo-panel">
                            <div class="todo-header">
                                <h3>待办事项</h3>
                                <div class="todo-header-right">
                                    <span class="todo-count">3 项待处理</span>
                                    <a href="/admin/todo/index.php" class="view-all-btn">查看全部</a>
                                </div>
                            </div>
                            <div class="todo-list">
                                <div class="todo-item">
                                    <label class="todo-checkbox">
                                        <input type="checkbox" />
                                        <span class="checkmark"></span>
                                    </label>
                                    <div class="todo-content">
                                        <div class="todo-title">处理待发货订单</div>
                                        <div class="todo-meta">
                                            <span class="todo-tag urgent">紧急</span>
                                            <span class="todo-time">今天 18:00 前</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="todo-item">
                                    <label class="todo-checkbox">
                                        <input type="checkbox" />
                                        <span class="checkmark"></span>
                                    </label>
                                    <div class="todo-content">
                                        <div class="todo-title">审核新用户入驻申请</div>
                                        <div class="todo-meta">
                                            <span class="todo-tag normal">普通</span>
                                            <span class="todo-time">今天内</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="todo-item">
                                    <label class="todo-checkbox">
                                        <input type="checkbox" />
                                        <span class="checkmark"></span>
                                    </label>
                                    <div class="todo-content">
                                        <div class="todo-title">查看会员投诉反馈</div>
                                        <div class="todo-meta">
                                            <span class="todo-tag low">一般</span>
                                            <span class="todo-time">本周内</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 合伙人列表 -->
                        <div class="sales-rank-panel">
                            <div class="rank-header">
                                <h3>合伙人列表</h3>
                                <div class="rank-header-right">
                                    <span class="rank-period">TOP 7</span>
                                    <a href="/admin/member/index.php" class="view-all-btn">查看全部</a>
                                </div>
                            </div>
                            <div class="rank-list">
                                <?php
                                if (empty($partnerData)) {
                                    echo '<div style="text-align: center; padding: 40px; color: #999;">暂无合伙人数据</div>';
                                } else {
                                    // 只显示前 5 条
                                    $displayCount = min(5, count($partnerData));
                                    for ($i = 0; $i < $displayCount; $i++) {
                                        $item = $partnerData[$i];
                                        $index = $i;
                                        $rank = $index + 1;
                                        $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'normal'));
                                        $partnerName = htmlspecialchars($item['name'] ?? '未知合伙人');
                                        $totalSales = number_format($item['total_sales'] ?? 0, 2);
                                        $directDownline = intval($item['direct_downline_count'] ?? 0);
                                        $totalDownline = intval($item['total_downline_count'] ?? 0);
                                        
                                        // 头像处理 - 生成 SVG
                                        $initials = function_exists('mb_substr') ? mb_substr($partnerName, 0, 1) : substr($partnerName, 0, 1);
                                        $avatarSvg = 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#1890ff"/><text x="50" y="60" font-size="40" text-anchor="middle" fill="white">' . $initials . '</text></svg>');
                                        
                                        echo '<div class="rank-item">';
                                        echo '<div class="rank-num ' . $rankClass . '">' . $rank . '</div>';
                                        echo '<img class="rank-avatar" src="' . $avatarSvg . '" alt="' . $partnerName . '" />';
                                        echo '<div class="rank-info">';
                                        echo '<div class="rank-name">' . $partnerName . '</div>';
                                        echo '<div class="rank-stats">成交：<strong>¥' . $totalSales . '</strong></div>';
                                        
                                        // 悬停提示
                                        echo '<div class="rank-tooltip">';
                                        echo '<div class="tooltip-row"><span class="tooltip-label">成交金额</span><span class="tooltip-value highlight">¥' . $totalSales . '</span></div>';
                                        echo '<div class="tooltip-row"><span class="tooltip-label">新增下线</span><span class="tooltip-value">' . $directDownline . ' 人</span></div>';
                                        echo '<div class="tooltip-row"><span class="tooltip-label">总下线</span><span class="tooltip-value">' . $totalDownline . ' 人</span></div>';
                                        echo '</div>';
                                        
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    
    <script>
    // 侧边栏初始化
    (function() {
        function bind() {
            const btn = document.getElementById('menuToggleBtn');
            if (!btn) {
                setTimeout(bind, 50);
                return;
            }
            
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const sidebar = document.getElementById('sidebar');
                const body = document.body;
                
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');
                
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
                
                window.dispatchEvent(new Event('resize'));
            });
            
            console.log('✅ dashboard.php: 事件已绑定');
        }
        bind();
    })();
    
    // 折线图初始化
    let businessChart = null;
    let currentDateRange = 7;
    let chartDataCache = {}; // 数据缓存
    
    // 从 API 获取数据
    async function fetchChartData(days) {
        // 检查缓存
        if (chartDataCache[days]) {
            return chartDataCache[days];
        }
        
        try {
            const response = await fetch('api/business_trend.php?days=' + days);
            const result = await response.json();
            
            if (result.code === 200) {
                chartDataCache[days] = result.data;
                return result.data;
            }
        } catch (error) {
            console.error('获取数据失败:', error);
        }
        
        // API 失败时使用模拟数据
        return generateMockData(days);
    }
    
    // 生成模拟数据（备用）
    function generateMockData(days) {
        const labels = [];
        const salesData = [];
        const ordersData = [];
        const membersData = [];
        
        const now = new Date();
        for (let i = days - 1; i >= 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            const month = date.getMonth() + 1;
            const day = date.getDate();
            labels.push(month + '/' + day);
            
            const baseSales = days === 7 ? 5000 : days === 30 ? 3000 : days === 90 ? 2000 : 1500;
            const baseOrders = days === 7 ? 50 : days === 30 ? 30 : days === 90 ? 20 : 15;
            const baseMembers = days === 7 ? 30 : days === 30 ? 20 : days === 90 ? 15 : 10;
            
            salesData.push(Math.round(baseSales + Math.random() * baseSales * 0.5));
            ordersData.push(Math.round(baseOrders + Math.random() * baseOrders * 0.3));
            membersData.push(Math.round(baseMembers + Math.random() * baseMembers * 0.4));
        }
        
        return { labels, salesData, ordersData, membersData };
    }
    
    // 创建折线图
    async function createBusinessChart(days) {
        const ctx = document.getElementById('businessTrendChart').getContext('2d');
        const data = await fetchChartData(days);
        
        if (businessChart) {
            businessChart.destroy();
        }
        
        businessChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: '销售数据',
                        data: data.salesData,
                        borderColor: '#fa8c16',
                        backgroundColor: 'rgba(250, 140, 22, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fa8c16',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        yAxisID: 'y'  // 使用左侧 Y 轴
                    },
                    {
                        label: '订单数据',
                        data: data.ordersData,
                        borderColor: '#1890ff',
                        backgroundColor: 'rgba(24, 144, 255, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#1890ff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        yAxisID: 'y1'  // 使用右侧 Y 轴（订单）
                    },
                    {
                        label: '会员数据',
                        data: data.membersData,
                        borderColor: '#52c41a',
                        backgroundColor: 'rgba(82, 196, 26, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#52c41a',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        yAxisID: 'y2'  // 使用独立 Y 轴（会员）
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#1890ff',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                if (label === '销售数据') {
                                    return label + ': ¥' + value.toLocaleString();
                                } else if (label === '订单数据') {
                                    return label + ': ' + value + ' 单';
                                } else {
                                    return label + ': ' + value + ' 人';
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f0f0f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#999',
                            maxRotation: 0,
                            autoSkip: days > 30
                        }
                    },
                    // 销售数据 Y 轴（左侧）
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: '#f0f0f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#fa8c16',
                            callback: function(value) {
                                if (value >= 10000) {
                                    return (value / 10000).toFixed(0) + '万';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                                return value;
                            }
                        },
                        title: {
                            display: true,
                            text: '销售额（元）',
                            color: '#fa8c16',
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    // 订单数据 Y 轴（右侧）
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#1890ff',
                            stepSize: 10
                        },
                        title: {
                            display: true,
                            text: '订单数（单）',
                            color: '#1890ff',
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    // 会员数据 Y 轴（隐藏，独立缩放）
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
    
    // 时间选择器事件绑定
    document.querySelectorAll('.time-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const days = parseInt(this.dataset.days);
            currentDateRange = days;
            await createBusinessChart(days);
        });
    });
    
    // 初始化折线图（近 7 天）
    setTimeout(async () => {
        await createBusinessChart(7);
    }, 500);
    </script>
</body>
</html>
