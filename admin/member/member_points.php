<?php
/**
 * 会员积分管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '用户积分管理';
$config = SystemConfig::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'adjust') {
        try {
            $member_id = intval($_POST['member_id'] ?? 0);
            $type = $_POST['type'] ?? 'add';
            $points = intval($_POST['points'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $operator = !empty($admin['name']) ? $admin['name'] : (!empty($admin['username']) ? $admin['username'] : 'admin');
            
            if (!$member_id) { echo json_encode(['success' => false, 'message' => '请选择会员']); exit; }
            if ($points <= 0) { echo json_encode(['success' => false, 'message' => '积分数值必须大于0']); exit; }
            if (empty($reason)) { echo json_encode(['success' => false, 'message' => '请填写变更原因']); exit; }
            
            $member = $db->fetchOne("SELECT id, points, nickname, username FROM members WHERE id = ?", [$member_id]);
            if (!$member) { echo json_encode(['success' => false, 'message' => '用户不存在']); exit; }
            
            // 使用事务保证一致性
            $db->getConnection()->begin_transaction();
            try {
                // 写入积分日志
                $stmt = $db->getConnection()->prepare("INSERT INTO member_points_log (member_id, type, points, reason, operator, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$member_id, $type, $points, $reason, $operator]);
                
                // 更新会员积分
                if ($type === 'add') {
                    $upd = $db->getConnection()->prepare("UPDATE members SET points = points + ? WHERE id = ?");
                } else {
                    // 减分时检查余额
                    if ($member['points'] < $points) {
                        $db->getConnection()->rollback();
                        echo json_encode(['success' => false, 'message' => '用户当前积分不足（当前 ' . $member['points'] . '，需要扣减 ' . $points . '）']);
                        exit;
                    }
                    $upd = $db->getConnection()->prepare("UPDATE members SET points = points - ? WHERE id = ?");
                }
                $upd->execute([$points, $member_id]);
                
                $db->getConnection()->commit();
                echo json_encode(['success' => true, 'message' => '积分' . ($type === 'add' ? '增加' : '扣减') . '成功']);
                exit;
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            $stmt = $db->getConnection()->prepare("DELETE FROM member_points_log WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '记录已删除']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'batch_delete') {
        try {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => '请选择要删除的记录']); exit; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->getConnection()->prepare("DELETE FROM member_points_log WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'search_member') {
        try {
            $kw = trim($_POST['keyword'] ?? '');
            if (empty($kw)) { echo json_encode(['success' => true, 'data' => []]); exit; }
            $like = "%{$kw}%";
            $list = $db->fetchAll("SELECT id, username, phone, nickname, points FROM members WHERE (username LIKE ? OR phone LIKE ? OR nickname LIKE ? OR id = ?) AND status = 1 ORDER BY id DESC LIMIT 20", [$like, $like, $like, intval($kw)]);
            echo json_encode(['success' => true, 'data' => $list]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '查询失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 搜索
$keyword = trim($_GET['keyword'] ?? '');
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 15;

$where = "1=1";
$params = [];
if ($keyword !== '') {
    $where .= " AND (m.username LIKE ? OR m.phone LIKE ? OR m.nickname LIKE ? OR l.reason LIKE ?)";
    $like = "%{$keyword}%";
    array_push($params, $like, $like, $like, $like);
}
if ($type_filter !== '') {
    $where .= " AND l.type = ?";
    $params[] = $type_filter;
}
if ($date_from !== '') {
    $where .= " AND l.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}
if ($date_to !== '') {
    $where .= " AND l.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$totalRow = $db->fetchOne("SELECT COUNT(*) AS cnt FROM member_points_log l LEFT JOIN members m ON l.member_id = m.id WHERE $where", $params);
$total = intval($totalRow['cnt'] ?? 0);
$totalPages = max(1, ceil($total / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$logs = $db->fetchAll("SELECT l.*, m.username, m.phone, m.nickname, m.avatar, m.level FROM member_points_log l LEFT JOIN members m ON l.member_id = m.id WHERE $where ORDER BY l.id DESC LIMIT $offset, $pageSize", $params);

// 统计
$stats = $db->fetchOne("SELECT 
    COUNT(*) AS total_count,
    SUM(CASE WHEN l.type='add' THEN l.points ELSE 0 END) AS total_add,
    SUM(CASE WHEN l.type='deduct' THEN l.points ELSE 0 END) AS total_deduct
    FROM member_points_log l LEFT JOIN members m ON l.member_id = m.id WHERE $where", $params);
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
        .btn-danger { background: #ff4d4f; color: white; }
        .btn-danger:hover { background: #ff7875; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-default:hover { background: #e6e6e6; }
        .btn-success { background: #52c41a; color: white; }
        .btn-success:hover { background: #73d13d; }
        .btn-warning { background: #fa8c16; color: white; }
        .btn-warning:hover { background: #ffa940; }
        .stats-row {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;
        }
        .stat-card {
            background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center;
            justify-content: center; font-size: 24px; color: white;
        }
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
        .filter-bar input, .filter-bar select {
            padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px;
        }
        .filter-bar input:focus, .filter-bar select:focus {
            border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1);
        }
        .batch-toolbar {
            background: linear-gradient(135deg, rgba(24,144,255,0.08), rgba(64,169,255,0.08));
            border: 1px solid rgba(24,144,255,0.3); border-radius: 8px; padding: 12px 16px;
            margin-bottom: 20px; display: none; align-items: center; justify-content: space-between;
        }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
        .data-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; vertical-align: middle; }
        tr:hover { background: #fafafa; }
        .checkbox { width: 18px; height: 18px; cursor: pointer; }
        .member-info { display: flex; align-items: center; gap: 10px; }
        .member-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1890ff, #40a9ff);
            color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 500;
        }
        .member-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .member-name { font-weight: 500; color: #262626; }
        .member-phone { font-size: 12px; color: #8c8c8c; }
        .points-add { color: #52c41a; font-weight: 600; font-size: 16px; }
        .points-deduct { color: #ff4d4f; font-weight: 600; font-size: 16px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .badge-danger { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .badge-primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn { padding: 6px 12px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.3s; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; min-width: 480px; max-width: 560px; box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
        .modal-header { font-size: 20px; font-weight: 600; color: #262626; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #262626; margin-bottom: 8px; font-weight: 500; }
        .form-group label .required { color: #ff4d4f; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 8px; font-size: 14px; box-sizing: border-box; transition: all 0.3s; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1); }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
        .type-radio { display: flex; gap: 12px; }
        .type-radio label { flex: 1; padding: 12px; border: 2px solid #d9d9d9; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.3s; }
        .type-radio label.active-add { border-color: #52c41a; background: #f6ffed; color: #52c41a; }
        .type-radio label.active-deduct { border-color: #ff4d4f; background: #fff2f0; color: #ff4d4f; }
        .type-radio input { display: none; }
        .empty-state { text-align: center; padding: 80px 20px; color: #8c8c8c; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { color: #595959; margin-bottom: 8px; }
        .pagination { display: flex; justify-content: center; gap: 6px; padding: 20px; background: white; border-top: 1px solid #f0f0f0; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; color: #595959; text-decoration: none; }
        .pagination a:hover { border-color: #1890ff; color: #1890ff; }
        .pagination .current { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border-color: transparent; }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .member-search-result {
            max-height: 200px; overflow-y: auto; border: 1px solid #f0f0f0; border-radius: 8px; margin-top: 8px; display: none;
        }
        .member-search-result.show { display: block; }
        .member-search-item {
            padding: 10px 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #f5f5f5;
        }
        .member-search-item:hover { background: #f5f5f5; }
        .member-search-item .info { display: flex; flex-direction: column; gap: 2px; }
        .member-search-item .name { font-weight: 500; color: #262626; }
        .member-search-item .meta { font-size: 12px; color: #8c8c8c; }
        .member-search-item .points { color: #1890ff; font-weight: 600; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="toolbar">
                <h1>💎 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="showAdjustModal()">
                        <span>➕</span> 手动加减分
                    </button>
                </div>
            </div>
            
            <!-- 统计 -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon purple">📊</div>
                    <div class="stat-info">
                        <div class="stat-label">记录总数</div>
                        <div class="stat-value"><?= number_format($stats['total_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">➕</div>
                    <div class="stat-info">
                        <div class="stat-label">累计加分</div>
                        <div class="stat-value" style="color:#52c41a">+<?= number_format($stats['total_add'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">➖</div>
                    <div class="stat-info">
                        <div class="stat-label">累计减分</div>
                        <div class="stat-value" style="color:#ff4d4f">-<?= number_format($stats['total_deduct'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- 搜索 -->
            <form class="filter-bar" method="GET">
                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="搜索会员/手机号/原因" style="min-width:240px">
                <select name="type">
                    <option value="">全部类型</option>
                    <option value="add" <?= $type_filter === 'add' ? 'selected' : '' ?>>加分</option>
                    <option value="deduct" <?= $type_filter === 'deduct' ? 'selected' : '' ?>>减分</option>
                </select>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" title="开始日期">
                <span style="color:#bfbfbf">至</span>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" title="结束日期">
                <button type="submit" class="btn btn-primary">🔍 搜索</button>
                <a href="member_points.php" class="btn btn-default">重置</a>
            </form>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 条记录</div>
                <div class="batch-actions">
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <div class="icon">💎</div>
                        <h3><?= $keyword || $type_filter || $date_from || $date_to ? '未找到匹配的积分记录' : '暂无积分记录' ?></h3>
                        <p>点击"手动加减分"开始第一笔操作</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th width="80">ID</th>
                                <th>会员</th>
                                <th width="100">类型</th>
                                <th width="120">积分变动</th>
                                <th>变更原因</th>
                                <th width="100">操作人</th>
                                <th width="150">操作时间</th>
                                <th width="100">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr data-id="<?= $log['id'] ?>">
                                    <td><input type="checkbox" class="checkbox item-checkbox" value="<?= $log['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>#<?= $log['id'] ?></td>
                                    <td>
                                        <div class="member-info">
                                            <div class="member-avatar">
                                                <?php if (!empty($log['avatar'])): ?>
                                                    <img src="<?= htmlspecialchars($log['avatar']) ?>" onerror="this.style.display='none';this.parentNode.innerHTML='<?= mb_substr($log['nickname'] ?: $log['username'] ?: '?', 0, 1, 'UTF-8') ?>';">
                                                <?php else: ?>
                                                    <?= mb_substr($log['nickname'] ?: $log['username'] ?: '?', 0, 1, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="member-name"><?= htmlspecialchars($log['nickname'] ?: $log['username'] ?: '用户#' . $log['member_id']) ?></div>
                                                <div class="member-phone"><?= htmlspecialchars($log['phone'] ?: '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log['type'] === 'add'): ?>
                                            <span class="badge badge-success">➕ 加分</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">➖ 减分</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="points-<?= $log['type'] === 'add' ? 'add' : 'deduct' ?>">
                                            <?= $log['type'] === 'add' ? '+' : '-' ?><?= number_format($log['points']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['reason']) ?></td>
                                    <td><?= htmlspecialchars($log['operator']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn delete" onclick="deleteLog(<?= $log['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <?php
                        $qb = http_build_query(array_filter([
                            'keyword' => $keyword, 'type' => $type_filter,
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
    
    <!-- 手动加减分模态框 -->
    <div class="modal" id="adjustModal">
        <div class="modal-content">
            <div class="modal-header">💎 手动调整积分</div>
            <div class="modal-body">
                <form id="adjustForm">
                    <div class="form-group">
                        <label>选择会员 <span class="required">*</span></label>
                        <input type="text" id="memberSearch" placeholder="输入会员ID/手机号/昵称搜索" autocomplete="off">
                        <input type="hidden" name="member_id" id="memberId" required>
                        <div class="member-search-result" id="memberSearchResult"></div>
                        <div id="selectedMember" style="margin-top:8px;display:none;padding:8px 12px;background:#f6ffed;border-radius:6px;color:#52c41a;font-size:13px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>调整类型 <span class="required">*</span></label>
                        <div class="type-radio">
                            <label class="active-add" id="typeAddLabel">
                                <input type="radio" name="type" value="add" checked>
                                <div>➕ 增加积分</div>
                            </label>
                            <label id="typeDeductLabel">
                                <input type="radio" name="type" value="deduct">
                                <div>➖ 扣减积分</div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>积分数量 <span class="required">*</span></label>
                        <input type="number" name="points" id="points" min="1" required placeholder="请输入积分数量">
                    </div>
                    
                    <div class="form-group">
                        <label>变更原因 <span class="required">*</span></label>
                        <textarea name="reason" id="reason" required placeholder="请说明本次积分变动原因，将记录在日志中" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="submitAdjust()">确定提交</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        // 类型切换
        document.querySelectorAll('input[name="type"]').forEach(r => {
            r.addEventListener('change', function() {
                document.getElementById('typeAddLabel').classList.toggle('active-add', this.value === 'add');
                document.getElementById('typeDeductLabel').classList.toggle('active-deduct', this.value === 'deduct');
            });
        });
        
        // 会员搜索
        let searchTimer;
        document.getElementById('memberSearch').addEventListener('input', function() {
            clearTimeout(searchTimer);
            const kw = this.value.trim();
            if (!kw) { document.getElementById('memberSearchResult').classList.remove('show'); return; }
            searchTimer = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=search_member&keyword=${encodeURIComponent(kw)}`
                })
                .then(r => r.json())
                .then(data => {
                    const box = document.getElementById('memberSearchResult');
                    if (data.success && data.data && data.data.length > 0) {
                        box.innerHTML = data.data.map(m => `
                            <div class="member-search-item" onclick='selectMember(${JSON.stringify(m).replace(/'/g, "&#39;")})'>
                                <div class="info">
                                    <span class="name">#${m.id} ${escapeHtml(m.nickname || m.username)}</span>
                                    <span class="meta">${escapeHtml(m.phone || '无手机号')}</span>
                                </div>
                                <span class="points">💎 ${m.points || 0}</span>
                            </div>
                        `).join('');
                        box.classList.add('show');
                    } else {
                        box.innerHTML = '<div class="member-search-item" style="color:#999">未找到匹配的会员</div>';
                        box.classList.add('show');
                    }
                });
            }, 300);
        });
        
        function selectMember(m) {
            document.getElementById('memberId').value = m.id;
            document.getElementById('memberSearch').value = '';
            document.getElementById('memberSearchResult').classList.remove('show');
            const sm = document.getElementById('selectedMember');
            sm.style.display = 'block';
            sm.innerHTML = `✅ 已选择：#${m.id} ${escapeHtml(m.nickname || m.username)}（手机：${escapeHtml(m.phone || '无')}，当前积分：${m.points || 0}）`;
        }
        
        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        
        function showAdjustModal() {
            document.getElementById('adjustForm').reset();
            document.getElementById('memberId').value = '';
            document.getElementById('selectedMember').style.display = 'none';
            document.getElementById('memberSearchResult').classList.remove('show');
            document.getElementById('typeAddLabel').classList.add('active-add');
            document.getElementById('typeDeductLabel').classList.remove('active-deduct');
            document.getElementById('adjustModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('adjustModal').classList.remove('show');
        }
        
        function submitAdjust() {
            const memberId = document.getElementById('memberId').value;
            const points = document.getElementById('points').value;
            const reason = document.getElementById('reason').value.trim();
            
            if (!memberId) { showMessage('❌ 请先选择会员', 'error'); return; }
            if (!points || parseInt(points) <= 0) { showMessage('❌ 请输入正确的积分数值', 'error'); return; }
            if (!reason) { showMessage('❌ 请填写变更原因', 'error'); return; }
            
            const fd = new FormData(document.getElementById('adjustForm'));
            fd.append('action', 'adjust');
            
            fetch('', { method: 'POST', body: new URLSearchParams(fd) })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
                else showMessage('❌ ' + data.message, 'error');
            })
            .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
        }
        
        function deleteLog(id) {
            if (!confirm('确定要删除该积分记录吗？')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function toggleSelectAll() {
            const sa = document.getElementById('selectAll');
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = sa.checked);
            updateBatchToolbar();
        }
        
        function updateBatchToolbar() {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            const tb = document.getElementById('batchToolbar');
            if (cbs.length > 0) {
                tb.classList.add('show');
                document.getElementById('selectedCount').textContent = cbs.length;
            } else tb.classList.remove('show');
        }
        
        function batchDelete() {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            if (cbs.length === 0) { showMessage('❌ 请先选择记录', 'error'); return; }
            if (!confirm('确定要删除选中的 ' + cbs.length + ' 条记录吗？')) return;
            const ids = Array.from(cbs).map(cb => cb.value);
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=batch_delete&ids=${ids.join(',')}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function showMessage(msg, type) {
            const alert = document.createElement('div');
            let bg = '#f6ffed', color = '#237804', border = '#b7eb8f';
            if (type === 'error') { bg = '#fff2f0'; color = '#ff4d4f'; border = '#ffccc7'; }
            alert.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:${bg};color:${color};border:1px solid ${border};box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:14px;`;
            alert.textContent = msg;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
        
        document.getElementById('adjustModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
