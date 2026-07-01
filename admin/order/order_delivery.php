<?php
/**
 * 发货管理页面
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

$pageTitle = '发货管理';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$source = $_GET['source'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = '(order_no LIKE ? OR buyer_name LIKE ? OR shipping_name LIKE ?)';
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

if ($start_time !== '') {
    $where[] = 'created_at >= ?';
    $params[] = $start_time . ' 00:00:00';
}

if ($end_time !== '') {
    $where[] = 'created_at <= ?';
    $params[] = $end_time . ' 23:59:59';
}

// 只显示待发货和已发货的订单
$where[] = 'status IN (?, ?)';
$params[] = 'pending_ship';
$params[] = 'pending_receive';

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $orders = $db->fetchAll("SELECT * FROM orders WHERE {$whereStr} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
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
        .form-actions { grid-column: span 4; display: flex; gap: 8px; margin-top: 8px; align-items: center; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-success { background: #52c41a; color: white; }
        .order-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1300px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .product-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .product-name { font-size: 13px; color: #262626; font-weight: 500; }
        .product-spec { font-size: 12px; color: #8c8c8c; }
        .price { color: #ff4d4f; font-weight: 600; font-size: 15px; }
        .buyer-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .buyer-name { font-size: 13px; color: #262626; }
        .buyer-phone { font-size: 12px; color: #8c8c8c; }
        .delivery-info { font-size: 12px; color: #595959; }
        .delivery-method { font-weight: 500; }
        .delivery-address { color: #8c8c8c; margin-top: 4px; }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-badge.pending_ship { background: #e6f7ff; color: #1890ff; }
        .status-badge.pending_receive { background: #f6ffed; color: #52c41a; }
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
        .batch-toolbar { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: none; align-items: center; justify-content: space-between; }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
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
                        <label>关键词查询</label>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="订单号/买家姓名/收货人">
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
                        <a href="delivery_records.php" class="btn btn-success" style="text-decoration:none; display:inline-block;">📋 发货记录</a>
                    </div>
                </form>
            </div>
            
            <!-- 批量操作栏 -->
            <div class="batch-toolbar" id="batchToolbar">
                <span>已选择 <strong id="selectedCount">0</strong> 个订单</span>
                <div class="batch-actions">
                    <button class="btn btn-primary" onclick="batchDelivery()">📦 批量发货</button>
                </div>
            </div>
            
            <!-- 订单列表 -->
            <div class="order-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                <th width="280">商品信息</th>
                                <th width="120">实付款</th>
                                <th width="150">买家</th>
                                <th width="200">配送信息</th>
                                <th width="120">发货状态</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">📦</div>
                                    暂无待发货订单
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>
                                        <div class="product-info">
                                            <img src="https://via.placeholder.com/60" alt="商品" style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #f0f0f0;">
                                            <div class="product-name">商品名称</div>
                                            <div class="product-spec">规格：默认</div>
                                            <div style="font-size:11px; color:#8c8c8c;">¥<?= number_format($order['total_amount'], 2) ?> × 1</div>
                                        </div>
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
                                        <div class="delivery-info">
                                            <div class="delivery-method"><?= ['express'=>'快递','pickup'=>'自提','none'=>'无需配送','merchant'=>'商家配送'][$order['delivery_method']] ?? '未知' ?></div>
                                            <div class="delivery-address">
                                                <?= htmlspecialchars($order['shipping_name']) ?> <?= htmlspecialchars($order['shipping_phone']) ?><br>
                                                <?= htmlspecialchars(mb_substr($order['shipping_address'], 0, 20)) ?>...
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $order['status'] ?>">
                                            <?= ['pending_ship'=>'待发货','pending_receive'=>'已发货'][$order['status']] ?? $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($order['status'] === 'pending_ship'): ?>
                                                <button class="action-btn.primary" onclick="delivery(<?= $order['id'] ?>, '<?= $order['order_no'] ?>')">📦 发货</button>
                                            <?php else: ?>
                                                <button class="action-btn.default" onclick="alert('已发货，无需重复操作')">✓ 已发货</button>
                                            <?php endif; ?>
                                            <button class="action-btn.default" onclick="alert('详情功能开发中')">详情</button>
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
    // 全选/取消全选
    function toggleAll(checkbox) {
        document.querySelectorAll('.order-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
        });
        updateBatchToolbar();
    }
    
    // 更新批量工具栏
    function updateBatchToolbar() {
        const selected = document.querySelectorAll('.order-checkbox:checked').length;
        const toolbar = document.getElementById('batchToolbar');
        const countEl = document.getElementById('selectedCount');
        
        if (selected > 0) {
            toolbar.classList.add('show');
            countEl.textContent = selected;
        } else {
            toolbar.classList.remove('show');
        }
    }
    
    // 批量发货
    function batchDelivery() {
        const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('请先选择要发货的订单');
            return;
        }
        alert('批量发货功能开发中，已选择 ' + selected.length + ' 条订单');
    }
    
    // 单个发货
    function delivery(orderId, orderNo) {
        if (confirm('确定要对订单 ' + orderNo + ' 进行发货吗？')) {
            alert('发货功能开发中，订单 ID: ' + orderId);
        }
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    </script>
</body>
</html>
