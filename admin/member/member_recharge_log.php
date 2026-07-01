<?php
/**
 * 会员充值记录页面
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

$pageTitle = '充值记录';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$recharge_type = $_GET['recharge_type'] ?? '';
$pay_status = $_GET['pay_status'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = '(r.order_no LIKE ? OR m.nickname LIKE ? OR m.phone LIKE ?)';
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if ($recharge_type !== '') {
    $where[] = 'r.recharge_type = ?';
    $params[] = $recharge_type;
}

if ($pay_status !== '') {
    $where[] = 'r.pay_status = ?';
    $params[] = $pay_status;
}

if ($start_time !== '') {
    $where[] = 'r.created_at >= ?';
    $params[] = $start_time . ' 00:00:00';
}

if ($end_time !== '') {
    $where[] = 'r.created_at <= ?';
    $params[] = $end_time . ' 23:59:59';
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM member_recharge_log r LEFT JOIN members m ON r.member_id = m.id WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $logs = $db->fetchAll("SELECT r.*, m.nickname, m.phone, m.avatar FROM member_recharge_log r LEFT JOIN members m ON r.member_id = m.id WHERE {$whereStr} ORDER BY r.created_at DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}

$rechargeTypes = ['wechat' => '微信支付', 'balance' => '余额支付', 'offline' => '线下支付'];
$payStatusNames = ['待支付', '已支付', '已取消'];
$payStatusColors = ['#faad14', '#52c41a', '#8c8c8c'];
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
        .recharge-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1300px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .member-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .member-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .member-nickname { font-size: 14px; color: #262626; font-weight: 500; }
        .member-phone { font-size: 12px; color: #8c8c8c; }
        .order-no { font-size: 13px; color: #262626; font-family: monospace; }
        .recharge-type-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; background: #e6f7ff; color: #1890ff; }
        .package-name { font-size: 14px; color: #262626; font-weight: 500; }
        .amount { color: #ff4d4f; font-weight: 600; font-size: 15px; }
        .gift-amount { font-size: 12px; color: #faad14; margin-top: 4px; }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .time-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .create-time { font-size: 13px; color: #8c8c8c; }
        .paid-time { font-size: 12px; color: #52c41a; }
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
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="订单号/会员昵称/手机号">
                    </div>
                    <div class="form-item">
                        <label>充值方式</label>
                        <select name="recharge_type">
                            <option value="">全部</option>
                            <option value="wechat" <?= $recharge_type === 'wechat' ? 'selected' : '' ?>>微信支付</option>
                            <option value="balance" <?= $recharge_type === 'balance' ? 'selected' : '' ?>>余额支付</option>
                            <option value="offline" <?= $recharge_type === 'offline' ? 'selected' : '' ?>>线下支付</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>充值状态</label>
                        <select name="pay_status">
                            <option value="">全部</option>
                            <option value="0" <?= $pay_status === '0' ? 'selected' : '' ?>>待支付</option>
                            <option value="1" <?= $pay_status === '1' ? 'selected' : '' ?>>已支付</option>
                            <option value="2" <?= $pay_status === '2' ? 'selected' : '' ?>>已取消</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>付款时间</label>
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
            
            <!-- 充值记录列表 -->
            <div class="recharge-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th width="200">会员信息</th>
                                <th width="180">订单号</th>
                                <th width="120">充值方式</th>
                                <th width="150">套餐名称</th>
                                <th width="120">支付金额</th>
                                <th width="120">赠送金额</th>
                                <th width="100">支付状态</th>
                                <th width="180">创建时间/付款时间</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">💰</div>
                                    暂无充值记录
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><strong><?= $log['id'] ?></strong></td>
                                    <td>
                                        <div class="member-info">
                                            <img src="<?= htmlspecialchars($log['avatar']) ?>" alt="头像" class="member-avatar">
                                            <div class="member-nickname"><?= htmlspecialchars($log['nickname']) ?></div>
                                            <div class="member-phone"><?= htmlspecialchars($log['phone']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-no"><?= htmlspecialchars($log['order_no']) ?></div>
                                    </td>
                                    <td>
                                        <span class="recharge-type-badge"><?= $rechargeTypes[$log['recharge_type']] ?? $log['recharge_type'] ?></span>
                                    </td>
                                    <td>
                                        <div class="package-name"><?= htmlspecialchars($log['package_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="amount">¥<?= number_format($log['pay_amount'], 2) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($log['gift_amount'] > 0): ?>
                                            <div class="gift-amount">🎁 ¥<?= number_format($log['gift_amount'], 2) ?></div>
                                        <?php else: ?>
                                            <span style="color: #d9d9d9;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?= $payStatusColors[$log['pay_status']] ?>; color: white;">
                                            <?= $payStatusNames[$log['pay_status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="time-info">
                                            <div class="create-time"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></div>
                                            <?php if ($log['paid_at']): ?>
                                                <div class="paid-time">付款：<?= date('Y-m-d H:i', strtotime($log['paid_at'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.primary" onclick="viewDetail(<?= $log['id'] ?>)">详情</button>
                                            <?php if ($log['pay_status'] === 0): ?>
                                                <button class="action-btn.default" onclick="confirmPay(<?= $log['id'] ?>)">✅ 确认收款</button>
                                            <?php endif; ?>
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
    // 查看详情
    function viewDetail(id) {
        alert('详情功能开发中，记录 ID: ' + id);
    }
    
    // 确认收款
    function confirmPay(id) {
        if (confirm('确定要确认该笔充值吗？')) {
            alert('确认收款功能开发中，记录 ID: ' + id);
        }
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    </script>
</body>
</html>
