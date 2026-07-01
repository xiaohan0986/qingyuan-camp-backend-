<?php
/**
 * 订单管理列表页面 - 完整版
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL); ini_set("display_errors", 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Auth::check();

try {
    $db = Database::getInstance();
    $admin = Auth::user();
} catch (Exception $e) {
    echo "<pre>错误：" . $e->getMessage() . "</pre>";
    die();
}

$pageTitle = '订单管理';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$source = $_GET['source'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$delivery_method = $_GET['delivery_method'] ?? '';
$store_type = $_GET['store_type'] ?? '';
$status = $_GET['status'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = '(order_no LIKE ? OR buyer_name LIKE ? OR shipping_name LIKE ? OR shipping_phone LIKE ?)';
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if ($source !== '') {
    $where[] = 'source = ?';
    $params[] = $source;
}

if ($payment_method !== '') {
    $where[] = 'payment_method = ?';
    $params[] = $payment_method;
}

if ($delivery_method !== '') {
    $where[] = 'delivery_method = ?';
    $params[] = $delivery_method;
}

if ($store_type !== '') {
    $where[] = 'store_type = ?';
    $params[] = $store_type;
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($start_time !== '') {
    $where[] = 'created_at >= ?';
    $params[] = $start_time . ' 00:00:00';
}

if ($end_time !== '') {
    $where[] = 'created_at <= ?';
    $params[] = $end_time . ' 23:59:59';
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    
    // 查询订单列表，关联商品表获取商品图片
    $orders = $db->fetchAll("
        SELECT o.*, 
               oi.product_id,
               oi.product_name,
               oi.product_image,
               oi.quantity,
               oi.price
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE {$whereStr}
        ORDER BY o.created_at DESC
        LIMIT {$pageSize} OFFSET {$offset}
    ", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .content-wrapper { padding: 24px; }
        .search-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .form-item { display: flex; flex-direction: column; gap: 6px; }
        .form-item label { font-size: 13px; color: #666; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .form-item input[type="date"] { min-width: 140px; }
        .form-actions { grid-column: span 4; display: flex; gap: 8px; margin-top: 8px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-default { background: #f5f5f5; color: #666; }
        .quick-filters { background: white; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .filter-btn { padding: 6px 16px; border: 1px solid #d9d9d9; border-radius: 6px; background: white; color: #666; text-decoration: none; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { border-color: #1890ff; color: #1890ff; }
        .filter-btn.active { background: #1890ff; color: white; }
        .export-btn { margin-left: auto; padding: 6px 16px; border: 1px solid #52c41a; border-radius: 6px; background: #52c41a; color: white; text-decoration: none; font-size: 13px; }
        .order-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .product-info { display: flex; align-items: center; gap: 12px; }
        .product-checkbox { width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0; flex-shrink: 0; }
        .product-detail { display: flex; flex-direction: column; gap: 4px; min-width: 0; flex: 1; }
        .product-name { font-size: 14px; color: #262626; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-order-no { font-size: 12px; color: #8c8c8c; }
        .product-shop { font-size: 13px; color: #1890ff; font-weight: 500; white-space: nowrap; flex-shrink: 0; }
        .product-spec { font-size: 12px; color: #8c8c8c; }
        .price { color: #ff4d4f; font-weight: 600; font-size: 15px; }
        .buyer-info { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
        .buyer-name { font-size: 13px; color: #262626; }
        .buyer-phone { font-size: 12px; color: #8c8c8c; }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-badge.pending_pay { background: #fff2f0; color: #ff4d4f; }
        .status-badge.pending_ship { background: #e6f7ff; color: #1890ff; }
        .status-badge.pending_receive { background: #f6ffed; color: #52c41a; }
        .status-badge.completed { background: #f0f0f0; color: #666; }
        .status-badge.cancelled { background: #fafafa; color: #8c8c8c; }
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .action-btn { padding: 4px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .action-btn.primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn.primary:hover { background: #1890ff; color: white; }
        .action-btn.default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .action-btn.default:hover { background: #666; color: white; }
        .pagination { display: flex; justify-content: center; align-items: center; padding: 20px; gap: 8px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; }
        .pagination a:hover, .pagination .active { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { font-weight: 600; }
        
        /* 订单详情抽屉 */
        .order-drawer {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2000;
        }
        
        .order-drawer.show {
            display: flex;
            justify-content: flex-end;
        }
        
        .drawer-mask {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .drawer-content {
            position: relative;
            width: 1000px;
            max-width: 95%;
            height: 100%;
            background: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .drawer-header h3 {
            margin: 0;
            font-size: 18px;
            color: #262626;
        }
        
        .drawer-close {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            font-size: 24px;
            color: #8c8c8c;
            cursor: pointer;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .drawer-close:hover {
            background: #f5f5f5;
            color: #262626;
        }
        
        .drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .order-drawer.closing .drawer-content {
            animation: slideOut 0.3s forwards;
        }
        
        .order-drawer.closing .drawer-mask {
            animation: fadeOut 0.3s forwards;
        }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="content-wrapper">
            <!-- 搜索组件 -->
            <div class="search-bar">
                <form class="search-form" method="get">
                    <div class="form-item" style="grid-column: span 2;">
                        <label>关键词搜索</label>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="商品名称/订单号/用户昵称/收货人电话">
                    </div>
                    <div class="form-item">
                        <label>订单来源</label>
                        <select name="source">
                            <option value="">全部</option>
                            <option value="normal" <?= $source === 'normal' ? 'selected' : '' ?>>普通订单</option>
                            <option value="bargain" <?= $source === 'bargain' ? 'selected' : '' ?>>砍价订单</option>
                            <option value="seckill" <?= $source === 'seckill' ? 'selected' : '' ?>>秒杀订单</option>
                            <option value="group" <?= $source === 'group' ? 'selected' : '' ?>>拼团订单</option>
                            <option value="points" <?= $source === 'points' ? 'selected' : '' ?>>积分商城</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>支付方式</label>
                        <select name="payment_method">
                            <option value="">全部</option>
                            <option value="wechat" <?= $payment_method === 'wechat' ? 'selected' : '' ?>>微信支付</option>
                            <option value="balance" <?= $payment_method === 'balance' ? 'selected' : '' ?>>余额支付</option>
                            <option value="offline" <?= $payment_method === 'offline' ? 'selected' : '' ?>>线下支付</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>配送方式</label>
                        <select name="delivery_method">
                            <option value="">全部</option>
                            <option value="express" <?= $delivery_method === 'express' ? 'selected' : '' ?>>快递</option>
                            <option value="pickup" <?= $delivery_method === 'pickup' ? 'selected' : '' ?>>自提</option>
                            <option value="none" <?= $delivery_method === 'none' ? 'selected' : '' ?>>无需配送</option>
                            <option value="merchant" <?= $delivery_method === 'merchant' ? 'selected' : '' ?>>商家配送</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>门店类型</label>
                        <select name="store_type">
                            <option value="">全部</option>
                            <option value="direct" <?= $store_type === 'direct' ? 'selected' : '' ?>>直营</option>
                            <option value="self" <?= $store_type === 'self' ? 'selected' : '' ?>>自营</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>下单时间</label>
                        <input type="date" name="start_time" value="<?= htmlspecialchars($start_time) ?>">
                    </div>
                    <div class="form-item">
                        <label>至</label>
                        <input type="date" name="end_time" value="<?= htmlspecialchars($end_time) ?>">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <button type="button" class="btn btn-default" onclick="resetSearch()">🔄 重置</button>
                    </div>
                </form>
            </div>
            
            <!-- 快捷筛选 -->
            <div class="quick-filters">
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])) ?>" class="filter-btn <?= empty($status) ? 'active' : '' ?>">全部订单</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending_ship', 'page' => 1])) ?>" class="filter-btn <?= $status === 'pending_ship' ? 'active' : '' ?>">待发货</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending_receive', 'page' => 1])) ?>" class="filter-btn <?= $status === 'pending_receive' ? 'active' : '' ?>">待收货</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending_pay', 'page' => 1])) ?>" class="filter-btn <?= $status === 'pending_pay' ? 'active' : '' ?>">已付款</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'cancelled', 'page' => 1])) ?>" class="filter-btn <?= $status === 'cancelled' ? 'active' : '' ?>">已取消</a>
                <a href="export_orders.php" class="export-btn">📊 订单导出</a>
            </div>
            
            <!-- 订单列表 -->
            <div class="order-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="380">商品信息</th>
                                <th width="120">单价×数量</th>
                                <th width="120">实付金额</th>
                                <th width="150">买家信息</th>
                                <th width="150">支付/配送</th>
                                <th width="120">订单状态</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">📦</div>
                                    暂无订单数据
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <input type="checkbox" class="product-checkbox" value="<?= $order['id'] ?>">
                                            <?php if (!empty($order['product_image'])): ?>
                                                <img src="<?= htmlspecialchars($order['product_image']) ?>" alt="商品" class="product-image">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/60" alt="商品" class="product-image">
                                            <?php endif; ?>
                                            <div class="product-detail">
                                                <div class="product-name"><?= htmlspecialchars($order['product_name'] ?? '商品名称') ?></div>
                                                <div class="product-order-no">订单号：<?= htmlspecialchars($order['order_no']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="color:#8c8c8c; font-size:13px;">¥<?= number_format($order['price'] ?? $order['amount'], 2) ?> × <?= $order['quantity'] ?? 1 ?></div>
                                    </td>
                                    <td>
                                        <div class="price">¥<?= number_format($order['pay_amount'], 2) ?></div>
                                        <?php if (!empty($order['discount_amount'])): ?>
                                            <div style="font-size:11px; color:#ff4d4f;">省¥<?= number_format($order['discount_amount'], 2) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="buyer-info">
                                            <div class="buyer-name"><?= htmlspecialchars($order['buyer_name']) ?></div>
                                            <div class="buyer-phone"><?= htmlspecialchars($order['buyer_phone']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size:13px; color:#595959;">
                                            <div><?= ['wechat'=>'微信支付','balance'=>'余额支付','offline'=>'线下支付'][$order['payment_method']] ?? '未知' ?></div>
                                            <div style="font-size:12px; color:#8c8c8c;"><?= ['express'=>'快递','pickup'=>'自提','none'=>'无需配送','merchant'=>'商家配送'][$order['delivery_method']] ?? '未知' ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $order['status'] ?>"><?= ['pending_pay'=>'待付款','pending_ship'=>'待发货','pending_receive'=>'待收货','completed'=>'已完成','cancelled'=>'已取消'][$order['status']] ?? $order['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.default" onclick="openOrderDetail(<?= $order['id'] ?>)">详情</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">首页</a>
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">上一页</a>
                        <?php endif; ?>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">下一页</a>
                        <?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">末页</a>
                        <span style="margin-left: 12px; color: #8c8c8c;">共 <?= $total ?> 条</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    function resetSearch() {
        window.location.href = '?';
    }
    
    // 打开订单详情抽屉
    function openOrderDetail(orderId) {
        fetch('order_detail.php?id=' + orderId)
            .then(res => res.text())
            .then(html => {
                document.getElementById('detailContent').innerHTML = html;
                document.getElementById('orderDrawer').classList.add('show');
                document.body.style.overflow = 'hidden';
                
                // 初始化 TAB 切换
                initOrderTabs();
            })
            .catch(err => {
                alert('加载失败：' + err.message);
            });
    }
    
    // 初始化订单详情 TAB
    function initOrderTabs() {
        var buttons = document.querySelectorAll('#detailContent .tab-btn');
        for (var i = 0; i < buttons.length; i++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var tabName = btn.getAttribute('data-tab');
                    
                    // 隐藏所有 TAB 内容
                    var contents = document.querySelectorAll('#detailContent .tab-content');
                    for (var j = 0; j < contents.length; j++) {
                        contents[j].classList.remove('active');
                    }
                    
                    // 移除所有 TAB 按钮的激活状态
                    var allBtns = document.querySelectorAll('#detailContent .tab-btn');
                    for (var j = 0; j < allBtns.length; j++) {
                        allBtns[j].classList.remove('active');
                    }
                    
                    // 显示当前 TAB 内容
                    var tabElement = document.getElementById('tab-' + tabName);
                    if (tabElement) {
                        tabElement.classList.add('active');
                    }
                    
                    // 激活当前 TAB 按钮
                    btn.classList.add('active');
                });
            })(buttons[i]);
        }
    }
    
    // 关闭订单详情抽屉（带滑出动画）
    function closeOrderDetail() {
        var drawer = document.getElementById('orderDrawer');
        if (drawer.classList.contains('closing')) return; // 防止重复触发
        drawer.classList.add('closing');
        setTimeout(function() {
            drawer.classList.remove('show', 'closing');
            document.body.style.overflow = '';
        }, 300);
    }
    
    // 点击 drawer 空白区域也可关闭（遮罩 onclick 已处理主要场景）
    document.addEventListener('DOMContentLoaded', function() {
        var drawer = document.getElementById('orderDrawer');
        if (drawer) {
            drawer.addEventListener('click', function(e) {
                if (e.target === drawer && !drawer.classList.contains('closing')) {
                    closeOrderDetail();
                }
            });
        }
    });
    </script>
    
    <!-- 订单详情抽屉 -->
    <div id="orderDrawer" class="order-drawer">
        <div class="drawer-mask" onclick="closeOrderDetail()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3>订单详情</h3>
                <button class="drawer-close" onclick="closeOrderDetail()">×</button>
            </div>
            <div class="drawer-body" id="detailContent">
                <div style="text-align: center; padding: 40px; color: #8c8c8c;">加载中...</div>
            </div>
        </div>
    </div>
</body>
</html>
