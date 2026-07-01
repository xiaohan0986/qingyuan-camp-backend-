<?php
/**
 * 退出申请管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '退出申请管理';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_POST['action'] === 'list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $status = $_POST['status'] ?? '';
            $dateFrom = trim($_POST['date_from'] ?? '');
            $dateTo = trim($_POST['date_to'] ?? '');
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;

            $where = ['1=1'];
            $params = [];

            if ($keyword !== '') {
                $where[] = '(p.name LIKE ? OR p.phone LIKE ? OR q.reason LIKE ?)';
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            if ($status !== '') {
                $where[] = 'q.status = ?';
                $params[] = intval($status);
            }
            if ($dateFrom !== '') {
                $where[] = 'q.created_at >= ?';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if ($dateTo !== '') {
                $where[] = 'q.created_at <= ?';
                $params[] = $dateTo . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM app_quits q LEFT JOIN app_partners p ON q.partner_id = p.id WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = $db->fetchAll("
                SELECT q.*, p.name AS partner_name, p.phone AS partner_phone, p.level AS partner_level,
                       p.balance AS partner_balance, p.total_commission AS partner_commission
                FROM app_quits q
                LEFT JOIN app_partners p ON q.partner_id = p.id
                WHERE {$whereSql}
                ORDER BY q.id DESC
                LIMIT {$pageSize} OFFSET {$offset}
            ", $params);

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
            exit;
        }

        if ($_POST['action'] === 'detail') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("
                SELECT q.*, p.name AS partner_name, p.phone AS partner_phone, p.level AS partner_level,
                       p.balance AS partner_balance, p.total_commission AS partner_commission,
                       p.invite_count AS partner_invite_count
                FROM app_quits q
                LEFT JOIN app_partners p ON q.partner_id = p.id
                WHERE q.id = ?
            ", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '记录不存在']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        if ($_POST['action'] === 'audit') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');

            $row = $db->fetchOne("SELECT id, status, partner_id FROM app_quits WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '记录不存在']);
                exit;
            }
            if ($row['status'] != 0) {
                echo json_encode(['success' => false, 'message' => '该申请已处理，不能重复操作']);
                exit;
            }

            $conn = $db->getConnection();
            $conn->begin_transaction();
            try {
                $db->update('app_quits', [
                    'status' => $status,
                    'remark' => $remark,
                    'handled_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);

                if ($status == 1 && $row['partner_id']) {
                    // 通过：解绑合伙人身份（status=2 表示已退出）
                    $db->query("UPDATE app_partners SET status = 2, updated_at = NOW() WHERE id = ?", [$row['partner_id']]);
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => $status == 1 ? '已通过' : '已拒绝']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            exit;
        }

        if ($_POST['action'] === 'batch_audit') {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status'] ?? 0);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的记录']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $conn = $db->getConnection();
            $conn->begin_transaction();
            try {
                $params = array_merge([$status], $ids);
                $db->query("UPDATE app_quits SET status = ?, handled_at = NOW() WHERE status = 0 AND id IN ({$placeholders})", $params);

                if ($status == 1) {
                    // 找出对应的合伙人
                    $placeholders2 = implode(',', array_fill(0, count($ids), '?'));
                    $quits = $db->fetchAll("SELECT partner_id FROM app_quits WHERE id IN ({$placeholders2}) AND partner_id > 0", $ids);
                    foreach ($quits as $q) {
                        $db->query("UPDATE app_partners SET status = 2, updated_at = NOW() WHERE id = ?", [$q['partner_id']]);
                    }
                }
                $conn->commit();
                echo json_encode(['success' => true, 'message' => $status == 1 ? '批量通过成功' : '批量拒绝成功']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            exit;
        }

        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id, status FROM app_quits WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '记录不存在']);
                exit;
            }
            if ($row['status'] == 1) {
                echo json_encode(['success' => false, 'message' => '已通过的申请不能直接删除（已影响合伙人状态）']);
                exit;
            }
            $db->delete('app_quits', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '记录删除成功']);
            exit;
        }

        if ($_POST['action'] === 'batch_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的记录']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM app_quits WHERE status <> 1 AND id IN ({$placeholders})", $ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功（已通过的记录已跳过）']);
            exit;
        }

        if ($_POST['action'] === 'stats') {
            $total = $db->fetchOne("SELECT COUNT(*) AS c FROM app_quits")['c'] ?? 0;
            $pending = $db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 0")['c'] ?? 0;
            $approved = $db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 1")['c'] ?? 0;
            $rejected = $db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 2")['c'] ?? 0;
            echo json_encode([
                'success' => true,
                'total' => intval($total),
                'pending' => intval($pending),
                'approved' => intval($approved),
                'rejected' => intval($rejected),
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        if (isset($db)) {
            try { $db->getConnection()->rollback(); } catch (Exception $e2) {}
        }
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        exit;
    }
}

$stats = [
    'total' => intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_quits")['c'] ?? 0),
    'pending' => intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 0")['c'] ?? 0),
    'approved' => intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 1")['c'] ?? 0),
    'rejected' => intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 2")['c'] ?? 0),
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
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .toolbar-actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px; background: var(--primary-gradient); }
        .stat-card.orange::before { background: linear-gradient(135deg, #fa8c16, #ffc069); }
        .stat-card.green::before { background: linear-gradient(135deg, #52c41a, #95de64); }
        .stat-card.red::before { background: linear-gradient(135deg, #ff4d4f, #ff7875); }
        .stat-card .label { color: var(--text-secondary); font-size: 13px; margin-bottom: 8px; }
        .stat-card .value { font-size: 26px; font-weight: 700; color: var(--text-primary); }
        .stat-card .sub { color: var(--text-secondary); font-size: 12px; margin-top: 4px; }

        .search-bar { background: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .search-bar input, .search-bar select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; min-width: 140px; transition: all 0.2s; }
        .search-bar input:focus, .search-bar select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15); }
        .search-bar input[type="text"] { min-width: 200px; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-success { background: linear-gradient(135deg, #52c41a, #95de64); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ff4d4f, #ff7875); color: white; }
        .btn-warning { background: linear-gradient(135deg, #fa8c16, #ffc069); color: white; }
        .btn-default { background: #f5f5f5; color: #595959; }
        .btn-default:hover { background: #e6e6e6; }

        .batch-toolbar { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: none; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .batch-toolbar.show { display: flex; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #237804; }
        .badge-danger { background: #fff2f0; color: #cf1322; }
        .badge-warning { background: #fff7e6; color: #d46b08; }
        .badge-primary { background: #e6f7ff; color: #096dd9; }

        .action-btn { padding: 4px 10px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.2s; }
        .action-btn.view { color: var(--primary-color); }
        .action-btn.view:hover { background: #f0f5ff; }
        .action-btn.edit { color: #52c41a; }
        .action-btn.edit:hover { background: #f6ffed; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
        .modal-header { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .modal-body { margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; transition: all 0.2s; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-color); }

        .empty-state { text-align: center; padding: 80px 20px; color: var(--text-secondary); }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state .text { font-size: 14px; }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; background: white; border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--text-primary); font-size: 13px; transition: all 0.2s; }
        .pagination a:hover, .pagination .active { background: var(--primary-gradient); color: white; border-color: transparent; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }

        .checkbox { width: 16px; height: 16px; cursor: pointer; }
        .cell-name { color: var(--text-primary); font-weight: 500; }
        .cell-meta { color: var(--text-secondary); font-size: 12px; margin-top: 2px; }
        .cell-reason { color: #595959; font-size: 13px; line-height: 1.5; max-width: 320px; }

        .alert { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 240px; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 14px; animation: slideIn 0.3s ease; }
        .alert-success { background: #f6ffed; color: #237804; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffccc7; }
        .alert-info { background: #e6f7ff; color: #096dd9; border: 1px solid #91d5ff; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 12px; background: #fafbff; border-radius: 8px; margin-bottom: 16px; }
        .info-grid .info-item { font-size: 13px; }
        .info-grid .label { color: var(--text-secondary); }
        .info-grid .value { color: var(--text-primary); font-weight: 500; margin-top: 2px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="toolbar">
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">🚪 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <a class="btn btn-default" href="index.php">← 返回数据统计</a>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="label">退出申请总数</div>
                    <div class="value"><?= $stats['total'] ?></div>
                </div>
                <div class="stat-card orange">
                    <div class="label">待审核</div>
                    <div class="value" style="color:#fa8c16;"><?= $stats['pending'] ?></div>
                </div>
                <div class="stat-card green">
                    <div class="label">已通过</div>
                    <div class="value" style="color:#52c41a;"><?= $stats['approved'] ?></div>
                </div>
                <div class="stat-card red">
                    <div class="label">已拒绝</div>
                    <div class="value" style="color:#ff4d4f;"><?= $stats['rejected'] ?></div>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="searchKeyword" placeholder="🔍 姓名/电话/退出原因">
                <select id="searchStatus">
                    <option value="">全部状态</option>
                    <option value="0">待审核</option>
                    <option value="1">已通过</option>
                    <option value="2">已拒绝</option>
                </select>
                <input type="date" id="searchDateFrom" title="开始日期">
                <span style="color:#999">至</span>
                <input type="date" id="searchDateTo" title="结束日期">
                <button class="btn btn-primary" onclick="loadList(1)">搜索</button>
                <button class="btn btn-default" onclick="resetSearch()">重置</button>
            </div>

            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 条记录</div>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="batchAudit(1)">✅ 批量通过</button>
                    <button class="btn btn-warning" onclick="batchAudit(2)">❌ 批量拒绝</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>

            <div class="data-table" id="tableContainer">
                <div class="empty-state"><div class="icon">⏳</div><div class="text">数据加载中…</div></div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 审核模态框 -->
    <div class="modal" id="auditModal">
        <div class="modal-content">
            <div class="modal-header" id="auditTitle">审核退出申请</div>
            <div class="modal-body">
                <div class="info-grid" id="auditInfo"></div>
                <form id="auditForm">
                    <input type="hidden" name="id" id="auditId">
                    <input type="hidden" name="status" id="auditStatus">
                    <div class="form-group">
                        <label>退出原因</label>
                        <div id="auditReason" style="padding:10px 12px;background:#fafafa;border-radius:8px;color:#595959;font-size:13px;"></div>
                    </div>
                    <div class="form-group">
                        <label id="auditQuestion">审核备注</label>
                        <textarea name="remark" id="auditRemark" placeholder="可填写审核意见..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('auditModal').classList.remove('show')">取消</button>
                <button class="btn btn-primary" id="auditSubmitBtn" onclick="submitAudit()">确定</button>
            </div>
        </div>
    </div>

    <!-- 详情模态框 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">🚪 退出申请详情</div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('detailModal').classList.remove('show')">关闭</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    let currentPage = 1;

    function loadList(page) {
        currentPage = page || 1;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', currentPage);
        params.append('keyword', document.getElementById('searchKeyword').value.trim());
        params.append('status', document.getElementById('searchStatus').value);
        params.append('date_from', document.getElementById('searchDateFrom').value);
        params.append('date_to', document.getElementById('searchDateTo').value);

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.data);
                renderPagination(data.total, data.page, data.pageSize);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 加载失败：' + err.message, 'error'));
    }

    function renderTable(rows) {
        const container = document.getElementById('tableContainer');
        if (!rows || rows.length === 0) {
            container.innerHTML = `<div class="empty-state"><div class="icon">🚪</div><div class="text">暂无退出申请</div></div>`;
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th width="70">ID</th>
                        <th width="170">合伙人 / 等级</th>
                        <th>退出原因</th>
                        <th width="100">状态</th>
                        <th width="140">申请时间</th>
                        <th width="140">处理时间</th>
                        <th width="200">操作</th>
                    </tr>
                </thead>
                <tbody>`;
        rows.forEach(r => {
            let statusBadge, actionBtns = '';
            if (r.status == 0) {
                statusBadge = '<span class="badge badge-warning">待审核</span>';
                actionBtns += `<button class="action-btn edit" onclick="openAuditModal(${r.id}, 1)">✅ 通过</button>`;
                actionBtns += `<button class="action-btn delete" onclick="openAuditModal(${r.id}, 2)">❌ 拒绝</button>`;
            } else if (r.status == 1) {
                statusBadge = '<span class="badge badge-success">已通过</span>';
            } else {
                statusBadge = '<span class="badge badge-danger">已拒绝</span>';
            }
            const partner = r.partner_name ? escapeHtml(r.partner_name) : '<span style="color:#bbb">已删除合伙人</span>';
            const created = r.created_at ? new Date(r.created_at).toLocaleString('zh-CN', {hour12: false}) : '-';
            const handled = r.handled_at ? new Date(r.handled_at).toLocaleString('zh-CN', {hour12: false}) : '-';
            const reason = r.reason || '-';
            html += `
                <tr data-id="${r.id}">
                    <td><input type="checkbox" class="checkbox item-checkbox" value="${r.id}"></td>
                    <td>#${r.id}</td>
                    <td>
                        <div class="cell-name">${partner}</div>
                        <div class="cell-meta">${escapeHtml(r.partner_level || '')} · ${escapeHtml(r.partner_phone || '')}</div>
                    </td>
                    <td><div class="cell-reason">${escapeHtml(reason)}</div></td>
                    <td>${statusBadge}</td>
                    <td>${created}</td>
                    <td>${handled}</td>
                    <td>
                        <button class="action-btn view" onclick="viewDetail(${r.id})">详情</button>
                        ${r.status == 0 ? `<button class="action-btn delete" onclick="deleteItem(${r.id})">🗑️</button>` : ''}
                    </td>
                </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function renderPagination(total, page, pageSize) {
        const container = document.getElementById('pagination');
        const totalPage = Math.max(1, Math.ceil(total / pageSize));
        if (totalPage <= 1) { container.innerHTML = ''; return; }
        let html = '';
        if (page > 1) html += `<a href="javascript:;" onclick="loadList(${page - 1})">‹ 上一页</a>`;
        else html += `<span class="disabled">‹ 上一页</span>`;
        const start = Math.max(1, page - 2);
        const end = Math.min(totalPage, page + 2);
        if (start > 1) html += `<a href="javascript:;" onclick="loadList(1)">1</a>`;
        if (start > 2) html += `<span>...</span>`;
        for (let i = start; i <= end; i++) {
            if (i === page) html += `<a class="active">${i}</a>`;
            else html += `<a href="javascript:;" onclick="loadList(${i})">${i}</a>`;
        }
        if (end < totalPage - 1) html += `<span>...</span>`;
        if (end < totalPage) html += `<a href="javascript:;" onclick="loadList(${totalPage})">${totalPage}</a>`;
        if (page < totalPage) html += `<a href="javascript:;" onclick="loadList(${page + 1})">下一页 ›</a>`;
        else html += `<span class="disabled">下一页 ›</span>`;
        container.innerHTML = html;
    }

    function resetSearch() {
        document.getElementById('searchKeyword').value = '';
        document.getElementById('searchStatus').value = '';
        document.getElementById('searchDateFrom').value = '';
        document.getElementById('searchDateTo').value = '';
        loadList(1);
    }

    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
        updateBatchToolbar();
    }

    function updateBatchToolbar() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        document.getElementById('selectedCount').textContent = checked.length;
        document.getElementById('batchToolbar').classList.toggle('show', checked.length > 0);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function openAuditModal(id, status) {
        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', id);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const r = data.data;
                document.getElementById('auditId').value = id;
                document.getElementById('auditStatus').value = status;
                document.getElementById('auditRemark').value = '';
                document.getElementById('auditTitle').textContent = status == 1 ? '✅ 通过退出申请' : '❌ 拒绝退出申请';
                document.getElementById('auditQuestion').textContent = status == 1 ? '通过审核（合伙人将被标记为已退出）' : '拒绝审核（合伙人身份保留）';
                document.getElementById('auditReason').textContent = r.reason || '（未填写）';
                document.getElementById('auditInfo').innerHTML = `
                    <div class="info-item"><div class="label">合伙人</div><div class="value">${escapeHtml(r.partner_name || '-')}</div></div>
                    <div class="info-item"><div class="label">等级</div><div class="value"><span class="badge badge-primary">${escapeHtml(r.partner_level || '-')}</span></div></div>
                    <div class="info-item"><div class="label">联系电话</div><div class="value">${escapeHtml(r.partner_phone || '-')}</div></div>
                    <div class="info-item"><div class="label">邀请人数</div><div class="value">${r.partner_invite_count || 0} 人</div></div>
                    <div class="info-item"><div class="label">累计佣金</div><div class="value" style="color:#fa8c16;">¥${parseFloat(r.partner_commission || 0).toFixed(2)}</div></div>
                    <div class="info-item"><div class="label">当前余额</div><div class="value" style="color:#52c41a;">¥${parseFloat(r.partner_balance || 0).toFixed(2)}</div></div>
                `;
                const btn = document.getElementById('auditSubmitBtn');
                btn.textContent = status == 1 ? '确认通过' : '确认拒绝';
                btn.className = status == 1 ? 'btn btn-success' : 'btn btn-danger';
                document.getElementById('auditModal').classList.add('show');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function submitAudit() {
        const id = document.getElementById('auditId').value;
        const status = document.getElementById('auditStatus').value;
        const remark = document.getElementById('auditRemark').value.trim();

        const params = new URLSearchParams();
        params.append('action', 'audit');
        params.append('id', id);
        params.append('status', status);
        params.append('remark', remark);

        const btn = document.getElementById('auditSubmitBtn');
        btn.disabled = true;
        btn.textContent = '处理中…';

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                document.getElementById('auditModal').classList.remove('show');
                loadList(currentPage);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
        });
    }

    function batchAudit(status) {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择记录', 'error');
        const action = status == 1 ? '通过' : '拒绝';
        if (!confirm(`确定要${action}选中的 ${ids.length} 条退出申请吗？` + (status == 1 ? '\n（通过后这些合伙人将被标记为已退出）' : ''))) return;
        const params = new URLSearchParams();
        params.append('action', 'batch_audit');
        ids.forEach(id => params.append('ids[]', id));
        params.append('status', status);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                loadList(currentPage);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function deleteItem(id) {
        if (!confirm('确定要删除该退出申请？')) return;
        const params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('id', id);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                loadList(currentPage);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function batchDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择记录', 'error');
        if (!confirm(`确定要删除选中的 ${ids.length} 条申请吗？\n（已通过的申请将被跳过）`)) return;
        const params = new URLSearchParams();
        params.append('action', 'batch_delete');
        ids.forEach(id => params.append('ids[]', id));
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                loadList(1);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function viewDetail(id) {
        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', id);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const r = data.data;
                const statusHtml = r.status == 0
                    ? '<span class="badge badge-warning">待审核</span>'
                    : (r.status == 1 ? '<span class="badge badge-success">已通过</span>' : '<span class="badge badge-danger">已拒绝</span>');
                document.getElementById('detailBody').innerHTML = `
                    <div class="form-group"><label>状态</label><div>${statusHtml}</div></div>
                    <div class="form-group"><label>合伙人</label><div>${escapeHtml(r.partner_name || '-')} <span class="badge badge-primary">${escapeHtml(r.partner_level || '')}</span></div></div>
                    <div class="form-group"><label>联系电话</label><div>${escapeHtml(r.partner_phone || '-')}</div></div>
                    <div class="form-group"><label>邀请人数</label><div>${r.partner_invite_count || 0} 人</div></div>
                    <div class="form-group"><label>累计佣金</label><div style="color:#fa8c16;font-weight:500;">¥${parseFloat(r.partner_commission || 0).toFixed(2)}</div></div>
                    <div class="form-group"><label>当前余额</label><div style="color:#52c41a;font-weight:500;">¥${parseFloat(r.partner_balance || 0).toFixed(2)}</div></div>
                    <div class="form-group"><label>退出原因</label><div style="background:#fafafa;padding:10px 12px;border-radius:8px;color:#595959;">${escapeHtml(r.reason || '-')}</div></div>
                    <div class="form-group"><label>申请时间</label><div>${r.created_at || '-'}</div></div>
                    <div class="form-group"><label>处理时间</label><div>${r.handled_at || '-'}</div></div>
                    <div class="form-group"><label>审核备注</label><div>${escapeHtml(r.remark || '-')}</div></div>
                `;
                document.getElementById('detailModal').classList.add('show');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (type || 'info');
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    document.getElementById('auditModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });

    document.getElementById('searchKeyword').addEventListener('keydown', e => {
        if (e.key === 'Enter') loadList(1);
    });

    loadList(1);
    </script>
</body>
</html>
