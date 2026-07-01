<?php
/**
 * 会员消费明细
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '用户消费明细';
$config = SystemConfig::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'export') {
        // 导出 CSV
        $keyword = trim($_POST['keyword'] ?? '');
        $status_filter = $_POST['status'] ?? '';
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        
        $where = "1=1";
        $params = [];
        if ($keyword !== '') {
            $where .= " AND (m.username LIKE ? OR m.phone LIKE ? OR m.nickname LIKE ? OR o.order_no LIKE ?)";
            $like = "%{$keyword}%";
            array_push($params, $like, $like, $like, $like);
        }
        if ($status_filter !== '') {
            $where .= " AND o.status = ?";
            $params[] = intval($status_filter);
        }
        if ($date_from !== '') {
            $where .= " AND o.created_at >= ?";
            $params[] = $date_from . ' 00:00:00';
        }
        if ($date_to !== '') {
            $where .= " AND o.created_at <= ?";
            $params[] = $date_to . ' 23:59:59';
        }
        
        $rows = $db->fetchAll("SELECT o.*, m.username, m.phone, m.nickname, m.level FROM orders o LEFT JOIN members m ON o.member_id = m.id WHERE $where ORDER BY o.id DESC LIMIT 5000", $params);
        
        $filename = '消费明细_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        // 输出 BOM 以让 Excel 正确识别 UTF-8
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['订单ID', '订单号', '用户ID', '用户昵称', '手机号', '订单金额', '实付金额', '优惠金额', '订单状态', '支付时间', '下单时间']);
        $statusMap = [
            0 => '待支付', 1 => '已支付', 2 => '已发货', 3 => '已完成',
            4 => '已取消', 5 => '退款中', 6 => '已退款'
        ];
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['order_no'],
                $r['member_id'],
                $r['nickname'] ?: $r['username'],
                $r['phone'] ?: '-',
                $r['total_amount'],
                $r['pay_amount'],
                $r['discount_amount'] ?? 0,
                $statusMap[$r['status']] ?? ('状态' . $r['status']),
                $r['pay_time'] ?: '-',
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }
    
    if ($_POST['action'] === 'get_detail') {
        try {
            $id = intval($_POST['id']);
            $order = $db->fetchOne(
                "SELECT o.*, m.username, m.phone, m.nickname, m.avatar, m.level, m.points 
                 FROM orders o LEFT JOIN members m ON o.member_id = m.id WHERE o.id = ?", 
                [$id]
            );
            echo json_encode(['success' => true, 'data' => $order]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '获取失败']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 搜索
$keyword = trim($_GET['keyword'] ?? '');
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 15;

$where = "1=1";
$params = [];
if ($keyword !== '') {
    $where .= " AND (m.username LIKE ? OR m.phone LIKE ? OR m.nickname LIKE ? OR o.order_no LIKE ?)";
    $like = "%{$keyword}%";
    array_push($params, $like, $like, $like, $like);
}
if ($status_filter !== '' && $status_filter !== null) {
    $where .= " AND o.status = ?";
    $params[] = intval($status_filter);
}
if ($date_from !== '') {
    $where .= " AND o.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}
if ($date_to !== '') {
    $where .= " AND o.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$totalRow = $db->fetchOne("SELECT COUNT(*) AS cnt FROM orders o LEFT JOIN members m ON o.member_id = m.id WHERE $where", $params);
$total = intval($totalRow['cnt'] ?? 0);
$totalPages = max(1, ceil($total / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$orders = $db->fetchAll("SELECT o.*, m.username, m.phone, m.nickname, m.avatar, m.level FROM orders o LEFT JOIN members m ON o.member_id = m.id WHERE $where ORDER BY o.id DESC LIMIT $offset, $pageSize", $params);

// 统计
$stats = $db->fetchOne("SELECT 
    COUNT(*) AS total_count,
    SUM(CASE WHEN o.status >= 1 THEN o.pay_amount ELSE 0 END) AS total_pay,
    SUM(CASE WHEN o.status >= 1 THEN 1 ELSE 0 END) AS paid_count
    FROM orders o LEFT JOIN members m ON o.member_id = m.id WHERE $where", $params);

$statusMap = [
    0 => ['name' => '待支付', 'badge' => 'badge-warning', 'color' => '#faad14'],
    1 => ['name' => '已支付', 'badge' => 'badge-primary', 'color' => '#1890ff'],
    2 => ['name' => '已发货', 'badge' => 'badge-primary', 'color' => '#1890ff'],
    3 => ['name' => '已完成', 'badge' => 'badge-success', 'color' => '#52c41a'],
    4 => ['name' => '已取消', 'badge' => 'badge-danger', 'color' => '#ff4d4f'],
    5 => ['name' => '退款中', 'badge' => 'badge-warning', 'color' => '#fa8c16'],
    6 => ['name' => '已退款', 'badge' => 'badge-danger', 'color' => '#ff4d4f']
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .toolbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; background: white; padding: 20px 24px;
            border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .toolbar h1 {
            font-size: 22px; margin: 0; color: #262626;
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .toolbar-actions { display: flex; gap: 12px; }
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px;
            cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; font-weight: 500;
        }
        .btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(24,144,255,0.4); }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-default:hover { background: #e6e6e6; }
        .btn-success { background: #52c41a; color: white; }
        .btn-success:hover { background: #73d13d; }
        .btn-warm { background: linear-gradient(135deg, #fa8c16, #ffc53d); color: white; }
        .btn-warm:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(250,140,22,0.4); }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .stat-icon.orange { background: linear-gradient(135deg, #f7971e, #ffd200); }
        .stat-info { flex: 1; }
        .stat-label { font-size: 13px; color: #8c8c8c; margin-bottom: 4px; }
        .stat-value { font-size: 24px; font-weight: 600; color: #262626; }
        .filter-bar {
            background: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
        }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
        .filter-bar input:focus, .filter-bar select:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1); }
        .data-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; vertical-align: middle; }
        tr:hover { background: #fafafa; }
        .member-info { display: flex; align-items: center; gap: 10px; }
        .member-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1890ff, #40a9ff);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500; flex-shrink: 0;
        }
        .member-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .member-name { font-weight: 500; color: #262626; }
        .member-phone { font-size: 12px; color: #8c8c8c; }
        .order-no { font-family: 'SF Mono', Monaco, Consolas, monospace; font-size: 13px; color: #595959; }
        .amount { color: #ff4d4f; font-weight: 600; font-size: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .badge-warning { background: #fffbe6; color: #faad14; border: 1px solid #ffe58f; }
        .badge-danger { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .badge-primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn { padding: 6px 12px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.3s; }
        .action-btn.view { color: #1890ff; }
        .action-btn.view:hover { background: rgba(24,144,255,0.1); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; min-width: 540px; max-width: 640px; box-shadow: 0 4px 24px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
        .modal-header { font-size: 20px; font-weight: 600; color: #262626; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0; }
        .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
        .detail-label { width: 120px; color: #8c8c8c; font-size: 14px; flex-shrink: 0; }
        .detail-value { flex: 1; color: #262626; font-size: 14px; word-break: break-all; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid #f0f0f0; margin-top: 20px; }
        .empty-state { text-align: center; padding: 80px 20px; color: #8c8c8c; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { color: #595959; margin-bottom: 8px; }
        .pagination { display: flex; justify-content: center; gap: 6px; padding: 20px; background: white; border-top: 1px solid #f0f0f0; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; color: #595959; text-decoration: none; }
        .pagination a:hover { border-color: #1890ff; color: #1890ff; }
        .pagination .current { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border-color: transparent; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="toolbar">
                <h1>🛒 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-warm" onclick="exportCSV()">导出 CSV</button>
                </div>
            </div>
            
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon purple">📦</div>
                    <div class="stat-info">
                        <div class="stat-label">订单总数</div>
                        <div class="stat-value"><?= number_format($stats['total_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="stat-label">已支付订单</div>
                        <div class="stat-value"><?= number_format($stats['paid_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">💰</div>
                    <div class="stat-info">
                        <div class="stat-label">实付总额</div>
                        <div class="stat-value" style="color:#ff4d4f">¥<?= number_format($stats['total_pay'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
            
            <form class="filter-bar" method="GET">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="搜索会员/手机号/订单号" style="min-width:240px">
                <select name="status">
                    <option value="">全部状态</option>
                    <?php foreach ($statusMap as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $status_filter !== '' && $status_filter == $k ? 'selected' : '' ?>><?= $v['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" title="开始日期">
                <span style="color:#bfbfbf">至</span>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" title="结束日期">
                <button type="submit" class="btn btn-primary">🔍 搜索</button>
                <a href="member_consumption.php" class="btn btn-default">重置</a>
            </form>
            
            <div class="data-table">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="icon">🛒</div>
                        <h3><?= $keyword || $status_filter !== '' || $date_from || $date_to ? '未找到匹配的订单' : '暂无消费记录' ?></h3>
                        <p>会员下单后将在此显示</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="80">订单ID</th>
                                <th width="180">订单号</th>
                                <th>会员</th>
                                <th width="100">订单金额</th>
                                <th width="100">实付金额</th>
                                <th width="100">优惠</th>
                                <th width="100">状态</th>
                                <th width="140">支付时间</th>
                                <th width="140">下单时间</th>
                                <th width="80">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): 
                                $st = $statusMap[$o['status']] ?? ['name' => '未知', 'badge' => 'badge-warning', 'color' => '#999'];
                            ?>
                                <tr data-id="<?= $o['id'] ?>">
                                    <td>#<?= $o['id'] ?></td>
                                    <td><span class="order-no"><?= htmlspecialchars($o['order_no']) ?></span></td>
                                    <td>
                                        <div class="member-info">
                                            <div class="member-avatar">
                                                <?php if (!empty($o['avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($o['avatar']) ?>" onerror="this.style.display='none';this.parentNode.innerHTML='<?= mb_substr($o['nickname'] ?: $o['username'] ?: '?', 0, 1, 'UTF-8') ?>';">
                                                <?php else: ?>
                                                    <?= mb_substr($o['nickname'] ?: $o['username'] ?: '?', 0, 1, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="member-name"><?= htmlspecialchars($o['nickname'] ?: $o['username'] ?: '用户#' . $o['member_id']) ?></div>
                                                <div class="member-phone"><?= htmlspecialchars($o['phone'] ?: '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>¥<?= number_format($o['total_amount'], 2) ?></td>
                                    <td><span class="amount">¥<?= number_format($o['pay_amount'], 2) ?></span></td>
                                    <td><?= $o['discount_amount'] > 0 ? '<span style="color:#52c41a">-¥' . number_format($o['discount_amount'], 2) . '</span>' : '-' ?></td>
                                    <td><span class="badge <?= $st['badge'] ?>"><?= $st['name'] ?></span></td>
                                    <td><?= $o['pay_time'] ? date('Y-m-d H:i', strtotime($o['pay_time'])) : '<span style="color:#bfbfbf">未支付</span>' ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn view" onclick="viewDetail(<?= $o['id'] ?>)">详情</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <?php
                        $qb = http_build_query(array_filter([
                            'keyword' => $keyword, 'status' => $status_filter,
                            'date_from' => $date_from, 'date_to' => $date_to
                        ]));
                        $qb = $qb ? '&' . $qb : '';
                        ?>
                        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="?page=<?= max(1, $page-1) ?><?= $qb ?>">上一页</a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                <a class="<?= $i == $page ? 'current' : '' ?>" href="?page=<?= $i ?><?= $qb ?>"><?= $i ?></a>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="?page=<?= min($totalPages, $page+1) ?><?= $qb ?>">下一页</a>
                        <span style="border:none;color:#8c8c8c">共 <?= $total ?> 条</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 订单详情 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">📋 订单详情</div>
            <div id="detailContent">
                <div style="text-align:center;padding:40px;color:#999">加载中...</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeDetail()">关闭</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        
        const statusMap = <?= json_encode($statusMap, JSON_UNESCAPED_UNICODE) ?>;
        
        function viewDetail(id) {
            document.getElementById('detailContent').innerHTML = '<div style="text-align:center;padding:40px;color:#999">加载中...</div>';
            document.getElementById('detailModal').classList.add('show');
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_detail&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const o = data.data;
                    const stInfo = statusMap[o.status] || {name: '未知', color: '#999'};
                    document.getElementById('detailContent').innerHTML = `
                        <div class="detail-row"><div class="detail-label">订单号</div><div class="detail-value"><code>${escapeHtml(o.order_no)}</code></div></div>
                        <div class="detail-row"><div class="detail-label">订单状态</div><div class="detail-value"><span class="badge ${stInfo.badge || 'badge-warning'}" style="font-size:13px;padding:4px 12px">${stInfo.name}</span></div></div>
                        <div class="detail-row"><div class="detail-label">会员信息</div><div class="detail-value">#${o.member_id} ${escapeHtml(o.nickname || o.username || '匿名')}（${escapeHtml(o.phone || '无手机号')}）</div></div>
                        <div class="detail-row"><div class="detail-label">会员等级</div><div class="detail-value">${o.level || 1}</div></div>
                        <div class="detail-row"><div class="detail-label">订单金额</div><div class="detail-value">¥${parseFloat(o.total_amount).toFixed(2)}</div></div>
                        <div class="detail-row"><div class="detail-label">实付金额</div><div class="detail-value" style="color:#ff4d4f;font-weight:600">¥${parseFloat(o.pay_amount).toFixed(2)}</div></div>
                        <div class="detail-row"><div class="detail-label">优惠金额</div><div class="detail-value">${o.discount_amount > 0 ? '<span style="color:#52c41a">-¥' + parseFloat(o.discount_amount).toFixed(2) + '</span>' : '无'}</div></div>
                        <div class="detail-row"><div class="detail-label">支付时间</div><div class="detail-value">${o.pay_time || '<span style="color:#bfbfbf">未支付</span>'}</div></div>
                        <div class="detail-row"><div class="detail-label">发货时间</div><div class="detail-value">${o.ship_time || '<span style="color:#bfbfbf">未发货</span>'}</div></div>
                        <div class="detail-row"><div class="detail-label">完成时间</div><div class="detail-value">${o.complete_time || '<span style="color:#bfbfbf">未完成</span>'}</div></div>
                        <div class="detail-row"><div class="detail-label">下单时间</div><div class="detail-value">${o.created_at}</div></div>
                        <div class="detail-row"><div class="detail-label">订单备注</div><div class="detail-value">${escapeHtml(o.remark || '无')}</div></div>
                    `;
                } else {
                    document.getElementById('detailContent').innerHTML = '<div style="text-align:center;padding:40px;color:#ff4d4f">获取失败</div>';
                }
            });
        }
        
        function closeDetail() { document.getElementById('detailModal').classList.remove('show'); }
        
        function exportCSV() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            const params = {
                action: 'export',
                keyword: '<?= addslashes($keyword) ?>',
                status: '<?= addslashes($status_filter) ?>',
                date_from: '<?= addslashes($date_from) ?>',
                date_to: '<?= addslashes($date_to) ?>'
            };
            for (const k in params) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = params[k];
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function showMessage(msg, type) {
            const alert = document.createElement('div');
            let bg = '#f6ffed', color = '#237804', border = '#b7eb8f';
            if (type === 'error') { bg = '#fff2f0'; color = '#ff4d4f'; border = '#ffccc7'; }
            if (type === 'info') { bg = '#e6f7ff'; color = '#1890ff'; border = '#91d5ff'; }
            alert.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:${bg};color:${color};border:1px solid ${border};box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:14px;`;
            alert.textContent = msg;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
        
        document.getElementById('detailModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDetail(); });
    </script>
</body>
</html>
