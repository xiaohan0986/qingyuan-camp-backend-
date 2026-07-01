<?php
/**
 * 售后管理页面
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

// 批量操作处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $action = $_POST['action'] ?? '';
        $ids_raw = $_POST['ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $ids_raw)), function($v) { return $v > 0; });
        if (empty($ids)) { echo json_encode(['success' => false, 'message' => '请选择记录']); exit; }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($action === 'batch_approve') {
            $db->query("UPDATE after_sales SET after_status = 'handled' WHERE id IN ($placeholders)", $ids);
            echo json_encode(['success' => true, 'message' => '操作成功']);
        } elseif ($action === 'batch_reject') {
            $db->query("UPDATE after_sales SET after_status = 'rejected' WHERE id IN ($placeholders)", $ids);
            echo json_encode(['success' => true, 'message' => '操作成功']);
        } elseif ($action === 'batch_delete') {
            $db->query("DELETE FROM after_sales WHERE id IN ($placeholders)", $ids);
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            echo json_encode(['success' => false, 'message' => '未知操作']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = '售后管理';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$after_type = $_GET['after_type'] ?? '';
$after_status = $_GET['after_status'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = '(after_no LIKE ? OR order_no LIKE ? OR buyer_name LIKE ? OR goods_name LIKE ?)';
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if ($after_type !== '') {
    $where[] = 'after_type = ?';
    $params[] = $after_type;
}

if ($after_status !== '') {
    $where[] = 'after_status = ?';
    $params[] = $after_status;
}

if ($start_time !== '') {
    $where[] = 'apply_time >= ?';
    $params[] = $start_time . ' 00:00:00';
}

if ($end_time !== '') {
    $where[] = 'apply_time <= ?';
    $params[] = $end_time . ' 23:59:59';
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM order_after WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $afters = $db->fetchAll("SELECT * FROM order_after WHERE {$whereStr} ORDER BY apply_time DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
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
        .after-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .product-info { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
        .product-row { display: flex; align-items: center; gap: 12px; }
        .product-checkbox { width: 18px; height: 18px; flex-shrink: 0; cursor: pointer; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0; flex-shrink: 0; }
        .product-detail { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
        .product-name { font-size: 13px; color: #262626; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-spec { font-size: 12px; color: #8c8c8c; }
        .product-price { font-size: 13px; color: #ff4d4f; font-weight: 600; }
        .after-no { font-size: 12px; color: #8c8c8c; padding-top: 4px; border-top: 1px solid #f0f0f0; width: 100%; }
        .after-desc { font-size: 12px; color: #666; background: #fafafa; padding: 6px 10px; border-radius: 4px; margin-top: 4px; }
        .price { color: #ff4d4f; font-weight: 600; font-size: 15px; }
        .buyer-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .buyer-name { font-size: 13px; color: #262626; }
        .buyer-phone { font-size: 12px; color: #8c8c8c; }
        .after-type-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .after-type-badge.return_refund { background: #fff2f0; color: #ff4d4f; }
        .after-type-badge.refund_only { background: #e6f7ff; color: #1890ff; }
        .after-type-badge.exchange { background: #f6ffed; color: #52c41a; }
        .after-status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .after-status-badge.processing { background: #e6f7ff; color: #1890ff; }
        .after-status-badge.handled { background: #f6ffed; color: #52c41a; }
        .after-status-badge.rejected { background: #fff2f0; color: #ff4d4f; }
        .after-status-badge.cancelled { background: #fafafa; color: #8c8c8c; }
        .progress-bar { width: 100px; height: 6px; background: #f0f0f0; border-radius: 3px; overflow: hidden; margin: 0 auto; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #1890ff, #91d5ff); border-radius: 3px; }
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
        
        /* 售后详情抽屉样式 */
        .after-drawer { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000; }
        .after-drawer.show { display: flex; justify-content: flex-end; }
        .drawer-mask { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); }
        .drawer-content { position: relative; width: 1000px; height: 100vh; background: white; box-shadow: -4px 0 16px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .drawer-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
        .drawer-header h3 { margin: 0; font-size: 18px; color: #262626; }
        .drawer-close { width: 32px; height: 32px; border: none; background: transparent; cursor: pointer; font-size: 20px; color: #8c8c8c; border-radius: 4px; }
        .drawer-close:hover { background: #f5f5f5; color: #262626; }
        .drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
.batch-toolbar { display: none; background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 20px; margin-bottom: 16px; align-items: center; gap: 12px; }
.batch-toolbar.show { display: flex; }
.batch-toolbar .batch-info { font-size: 14px; color: #0050b3; }
.batch-toolbar .batch-info strong { color: #1890ff; }
.batch-toolbar .batch-actions { display: flex; gap: 8px; margin-left: auto; }
.batch-toolbar .btn { padding: 6px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; font-weight: 500; }
.batch-toolbar .btn-primary { background: #1890ff; color: white; }
.batch-toolbar .btn-primary:hover { background: #096dd9; }
.batch-toolbar .btn-danger { background: #ff4d4f; color: white; }
.batch-toolbar .btn-danger:hover { background: #cf1322; }
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
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="售后单号/订单号/买家姓名/商品名称">
                    </div>
                    <div class="form-item">
                        <label>售后类型</label>
                        <select name="after_type">
                            <option value="">全部</option>
                            <option value="return_refund" <?= $after_type === 'return_refund' ? 'selected' : '' ?>>退货退款</option>
                            <option value="refund_only" <?= $after_type === 'refund_only' ? 'selected' : '' ?>>仅退款</option>
                            <option value="exchange" <?= $after_type === 'exchange' ? 'selected' : '' ?>>换货</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>售后单状态</label>
                        <select name="after_status">
                            <option value="">全部</option>
                            <option value="processing" <?= $after_status === 'processing' ? 'selected' : '' ?>>进行中</option>
                            <option value="handled" <?= $after_status === 'handled' ? 'selected' : '' ?>>已处理</option>
                            <option value="rejected" <?= $after_status === 'rejected' ? 'selected' : '' ?>>已拒绝</option>
                            <option value="cancelled" <?= $after_status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>申请时间</label>
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
            
            <!-- 售后列表 -->
            <div class="after-list">
                <div class="table-container">
        <!-- 批量操作栏 -->
        <div class="batch-toolbar" id="batchToolbar">
            <div class="batch-info">
                已选择 <strong id="selectedCount">0</strong> 条售后记录
            </div>
            <div class="batch-actions">
                <button class="btn btn-primary" onclick="batchApprove()">批量通过</button>
                <button class="btn btn-danger" onclick="batchReject()">批量拒绝</button>
                <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
            </div>
        </div>
        
                    <table>
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                <th width="320">商品信息</th>
                                <th width="120">付款金额</th>
                                <th width="150">买家信息</th>
                                <th width="120">售后类型</th>
                                <th width="150">处理进度</th>
                                <th width="120">售后状态</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($afters)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">📦</div>
                                    暂无售后数据
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($afters as $after): ?>
                                <tr>
                                    <td width="40" style="text-align:center; vertical-align:middle;">
                                        <input type="checkbox" class="product-checkbox" value="<?= $after['id'] ?>">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-row">
                                                <img src="<?= !empty($after['goods_image']) ? htmlspecialchars($after['goods_image']) : 'https://via.placeholder.com/60' ?>" alt="商品" class="product-image">
                                                <div class="product-detail">
                                                    <div class="product-name"><?= htmlspecialchars($after['goods_name']) ?></div>
                                                    <div class="product-spec">规格：<?= htmlspecialchars($after['goods_spec']) ?></div>
                                                    <div class="product-price">¥<?= number_format($after['goods_price'], 2) ?> × <?= $after['goods_quantity'] ?></div>
                                                </div>
                                            </div>
                                            <div class="after-no">售后单号：<?= htmlspecialchars($after['after_no']) ?></div>
                                            <?php if (!empty($after['description'])): ?>
                                            <div class="after-desc"><?= htmlspecialchars($after['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price">¥<?= number_format($after['pay_amount'], 2) ?></div>
                                    </td>
                                    <td>
                                        <div class="buyer-info">
                                            <div class="buyer-name"><?= htmlspecialchars($after['buyer_name']) ?></div>
                                            <div class="buyer-phone"><?= htmlspecialchars($after['buyer_phone']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="after-type-badge <?= $after['after_type'] ?>">
                                            <?= ['return_refund'=>'退货退款','refund_only'=>'仅退款','exchange'=>'换货'][$after['after_type']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-bar"><div class="progress-fill" style="width: <?= $after['after_status'] === 'handled' ? '100' : ($after['after_status'] === 'processing' ? '50' : '0') ?>%"></div></div>
                                        <div style="font-size:11px; color:#8c8c8c; margin-top:4px;"><?= $after['after_status'] === 'handled' ? '已完成' : ($after['after_status'] === 'processing' ? '处理中' : '已终止') ?></div>
                                    </td>
                                    <td>
                                        <span class="after-status-badge <?= $after['after_status'] ?>">
                                            <?= ['processing'=>'进行中','handled'=>'已处理','rejected'=>'已拒绝','cancelled'=>'已取消'][$after['after_status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.default" onclick="openAfterDetail(<?= $after['id'] ?>)">详情</button>
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
    

    function toggleAll(checkbox) {
        var checkboxes = document.querySelectorAll('.product-checkbox');
        for (var i = 0; i < checkboxes.length; i++) checkboxes[i].checked = checkbox.checked;
        updateBatchToolbar();
    }
    function updateBatchToolbar() {
        var checkboxes = document.querySelectorAll('.product-checkbox:checked');
        var count = checkboxes.length;
        var toolbar = document.getElementById('batchToolbar');
        var sc = document.getElementById('selectedCount');
        if (sc) sc.textContent = count;
        if (toolbar) { if (count > 0) toolbar.classList.add('show'); else toolbar.classList.remove('show'); }
    }
    function batchApprove() {
        var cbs = document.querySelectorAll('.product-checkbox:checked');
        if (cbs.length === 0) { alert('\u8bf7\u5148\u9009\u62e9\u8bb0\u5f55'); return; }
        var ids = Array.from(cbs).map(function(cb) { return cb.value; });
        if (!confirm('\u786e\u5b9a\u8981\u901a\u8fc7\u9009\u4e2d\u7684 ' + ids.length + ' \u6761\u552e\u540e\u8bb0\u5f55\u5417\uff1f')) return;
        fetch(location.href, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_approve&ids=' + ids.join(',') })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert(d.message); })
        .catch(function(e) { alert('\u7f51\u7edc\u9519\u8bef'); });
    }
    function batchReject() {
        var cbs = document.querySelectorAll('.product-checkbox:checked');
        if (cbs.length === 0) { alert('\u8bf7\u5148\u9009\u62e9\u8bb0\u5f55'); return; }
        var ids = Array.from(cbs).map(function(cb) { return cb.value; });
        if (!confirm('\u786e\u5b9a\u8981\u62d2\u7edd\u9009\u4e2d\u7684 ' + ids.length + ' \u6761\u552e\u540e\u8bb0\u5f55\u5417\uff1f')) return;
        fetch(location.href, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_reject&ids=' + ids.join(',') })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert(d.message); })
        .catch(function(e) { alert('\u7f51\u7edc\u9519\u8bef'); });
    }
    function batchDelete() {
        var cbs = document.querySelectorAll('.product-checkbox:checked');
        if (cbs.length === 0) { alert('\u8bf7\u5148\u9009\u62e9\u8bb0\u5f55'); return; }
        var ids = Array.from(cbs).map(function(cb) { return cb.value; });
        if (!confirm('\u786e\u5b9a\u8981\u5220\u9664\u9009\u4e2d\u7684 ' + ids.length + ' \u6761\u552e\u540e\u8bb0\u5f55\u5417\uff1f')) return;
        fetch(location.href, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_delete&ids=' + ids.join(',') })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert(d.message); })
        .catch(function(e) { alert('\u7f51\u7edc\u9519\u8bef'); });
    }

    // 打开售后详情抽屉
    function openAfterDetail(afterId) {
        fetch('order_after_detail.php?id=' + afterId)
            .then(res => res.text())
            .then(html => {
                document.getElementById('afterDetailContent').innerHTML = html;
                document.getElementById('afterDrawer').classList.add('show');
                document.body.style.overflow = 'hidden';
                
                // 初始化 TAB 切换
                initAfterTabs();
            })
            .catch(err => {
                alert('加载失败：' + err.message);
            });
    }
    
    // 初始化售后详情 TAB
    function initAfterTabs() {
        var buttons = document.querySelectorAll('#afterDetailContent .tab-btn');
        for (var i = 0; i < buttons.length; i++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var tabName = btn.getAttribute('data-tab');
                    
                    // 隐藏所有 TAB 内容
                    var contents = document.querySelectorAll('#afterDetailContent .tab-content');
                    for (var j = 0; j < contents.length; j++) {
                        contents[j].classList.remove('active');
                    }
                    
                    // 移除所有 TAB 按钮的激活状态
                    var allBtns = document.querySelectorAll('#afterDetailContent .tab-btn');
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
    
    // 关闭售后详情抽屉
    function closeAfterDetail() {
        document.getElementById('afterDrawer').classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // 点击遮罩关闭
    document.addEventListener('DOMContentLoaded', function() {
        const drawer = document.getElementById('afterDrawer');
        if (drawer) {
            drawer.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAfterDetail();
                }
            });
        }
    });
    </script>
    
    <!-- 售后详情抽屉 -->
    <div id="afterDrawer" class="after-drawer">
        <div class="drawer-mask"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3>售后详情</h3>
                <button class="drawer-close" onclick="closeAfterDetail()">×</button>
            </div>
            <div class="drawer-body" id="afterDetailContent">
                <div style="text-align: center; padding: 40px; color: #8c8c8c;">加载中...</div>
            </div>
        </div>
    </div>
</body>
</html>
