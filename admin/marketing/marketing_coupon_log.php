<?php
/**
 * 领券记录页面
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

$pageTitle = '领券记录';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$coupon_name = trim($_GET['coupon_name'] ?? '');
$member_name = trim($_GET['member_name'] ?? '');
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($coupon_name) {
    $where[] = 'coupon_name LIKE ?';
    $params[] = "%{$coupon_name}%";
}

if ($member_name) {
    $where[] = 'm.nickname LIKE ?';
    $params[] = "%{$member_name}%";
}

if ($start_time) {
    $where[] = 'receive_time >= ?';
    $params[] = $start_time . ' 00:00:00';
}

if ($end_time) {
    $where[] = 'receive_time <= ?';
    $params[] = $end_time . ' 23:59:59';
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM coupon_receive_log l LEFT JOIN members m ON l.member_id = m.id WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $logs = $db->fetchAll("SELECT l.*, m.nickname, m.phone, m.avatar FROM coupon_receive_log l LEFT JOIN members m ON l.member_id = m.id WHERE {$whereStr} ORDER BY l.receive_time DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}

$typeNames = ['', '满减', '折扣', '立减'];
$statusNames = ['未使用', '已使用', '已过期'];
$statusColors = ['#1890ff', '#52c41a', '#d9d9d9'];
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
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-primary:hover { background: #40a9ff; }
        .btn-default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #666; color: white; }
        .search-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .form-item { display: flex; flex-direction: column; gap: 6px; }
        .form-item label { font-size: 13px; color: #666; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .form-item input[type="date"] { min-width: 140px; }
        .form-actions { grid-column: span 4; display: flex; gap: 12px; margin-top: 8px; }
        .coupon-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1400px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .member-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .member-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .member-nickname { font-size: 14px; color: #262626; font-weight: 500; }
        .member-phone { font-size: 12px; color: #8c8c8c; }
        .coupon-name { font-size: 14px; color: #262626; font-weight: 500; }
        .coupon-type-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; background: #e6f7ff; color: #1890ff; }
        .min-amount { font-size: 13px; color: #ff4d4f; font-weight: 500; }
        .discount-value { font-size: 15px; color: #ff4d4f; font-weight: 600; }
        .discount-desc { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .valid-time { font-size: 12px; color: #8c8c8c; }
        .receive-time { font-size: 13px; color: #262626; }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .action-btn { padding: 4px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .action-btn.primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn.primary:hover { background: #1890ff; color: white; }
        .pagination { display: flex; justify-content: center; align-items: center; padding: 20px; gap: 8px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; }
        .pagination a:hover, .pagination .active { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { font-weight: 600; }
        .checkbox-cell { width: 40px; }
        .checkbox-cell input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        .batch-toolbar.show { display: flex !important; }
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
                    <div class="form-item">
                        <label>优惠券名称</label>
                        <input type="text" name="coupon_name" value="<?= htmlspecialchars($coupon_name) ?>" placeholder="优惠券名称">
                    </div>
                    <div class="form-item">
                        <label>会员昵称</label>
                        <input type="text" name="member_name" value="<?= htmlspecialchars($member_name) ?>" placeholder="会员昵称">
                    </div>
                    <div class="form-item">
                        <label>领取时间</label>
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
            
            <!-- 领券记录列表 -->
            <div class="coupon-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="checkbox-cell"><input type="checkbox" id="headerSelectAll" onclick="toggleAll(this)"></th>
                                <th width="60">ID</th>
                                <th width="200">会员信息</th>
                                <th width="180">优惠名称</th>
                                <th width="120">优惠类型</th>
                                <th width="120">最低消费</th>
                                <th width="150">优惠方式</th>
                                <th width="180">有效期</th>
                                <th width="180">领取时间</th>
                                <th width="100">状态</th>
                                <th width="120">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">🎫</div>
                                    暂无领券记录
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="checkbox-cell"><input type="checkbox" class="rowCheckbox" value="<?= $log['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td><strong><?= $log['id'] ?></strong></td>
                                    <td>
                                        <div class="member-info">
                                            <img src="<?= htmlspecialchars($log['avatar']) ?>" alt="头像" class="member-avatar">
                                            <div class="member-nickname"><?= htmlspecialchars($log['nickname']) ?></div>
                                            <div class="member-phone"><?= htmlspecialchars($log['phone']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="coupon-name"><?= htmlspecialchars($log['coupon_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="coupon-type-badge"><?= $typeNames[$log['coupon_type']] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log['min_amount'] > 0): ?>
                                            <div class="min-amount">¥<?= number_format($log['min_amount'], 2) ?></div>
                                        <?php else: ?>
                                            <span style="color: #d9d9d9;">无门槛</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['discount_type'] === 'amount'): ?>
                                            <div class="discount-value">减¥<?= number_format($log['discount_value'], 2) ?></div>
                                        <?php else: ?>
                                            <div class="discount-value">打<?= number_format($log['discount_value'] / 10, 1) ?>折</div>
                                        <?php endif; ?>
                                        <div class="discount-desc"><?= $log['discount_type'] === 'amount' ? '满减' : '折扣' ?></div>
                                    </td>
                                    <td>
                                        <?php if ($log['valid_start'] && $log['valid_end']): ?>
                                            <div class="valid-time"><?= date('m-d', strtotime($log['valid_start'])) ?> ~ <?= date('m-d', strtotime($log['valid_end'])) ?></div>
                                        <?php else: ?>
                                            <span style="color: #d9d9d9;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="receive-time"><?= date('Y-m-d H:i', strtotime($log['receive_time'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?= $statusColors[$log['status']] ?>; color: white;">
                                            <?= $statusNames[$log['status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.primary" onclick="viewDetail(<?= $log['id'] ?>)">详情</button>
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

    function updateBatchToolbar() {
        var cbs = document.querySelectorAll('.rowCheckbox:checked');
        var count = cbs.length;
        var tb = document.getElementById('batchToolbar');
        var sc = document.getElementById('selectedCount');
        if (sc) sc.textContent = count;
        if (tb) {
            if (count > 0) tb.style.display = 'flex';
            else tb.style.display = 'none';
        }
    }
    
    function batchDelete() {
        var cbs = document.querySelectorAll('.rowCheckbox:checked');
        if (cbs.length === 0) { alert('请先选择记录'); return; }
        var ids = Array.from(cbs).map(function(cb) { return cb.value; });
        if (!confirm('确定要删除选中的 ' + ids.length + ' 条记录吗？此操作不可恢复！')) return;
        fetch(location.href, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_delete&ids=' + ids.join(',') })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert(d.message); })
        .catch(function(e) { alert('网络错误'); });
    }
    function toggleAll(headerCheckbox) {
        updateBatchToolbar();
        document.querySelectorAll('.rowCheckbox').forEach(cb => {
            cb.checked = headerCheckbox.checked;
        });
    }
    
    // 查看详情
    function viewDetail(id) {
        alert('详情功能开发中，记录 ID: ' + id);
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    </script>
</body>
</html>
