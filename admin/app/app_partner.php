<?php
/**
 * 合伙用户管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '合伙用户管理';

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_POST['action'] === 'list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $level = trim($_POST['level'] ?? '');
            $status = $_POST['status'] ?? '';
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;

            $where = ['1=1'];
            $params = [];

            if ($keyword !== '') {
                $where[] = '(p.name LIKE ? OR p.phone LIKE ?)';
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            if ($level !== '') {
                $where[] = 'p.level = ?';
                $params[] = $level;
            }
            if ($status !== '') {
                $where[] = 'p.status = ?';
                $params[] = intval($status);
            }

            $whereSql = implode(' AND ', $where);
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM app_partners p WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = $db->fetchAll("
                SELECT p.*
                FROM app_partners p
                WHERE {$whereSql}
                ORDER BY p.id DESC
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
            $row = $db->fetchOne("SELECT * FROM app_partners WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '合伙人不存在']);
                exit;
            }

            // 邀请列表
            $invites = $db->fetchAll("
                SELECT invitee_name, invitee_phone, commission, created_at
                FROM app_invites
                WHERE partner_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ", [$id]);

            // 收益明细
            $earnings = $db->fetchAll("
                SELECT type, amount, source, description, created_at
                FROM app_earnings
                WHERE partner_id = ?
                ORDER BY created_at DESC
                LIMIT 30
            ", [$id]);

            // 提现历史
            $withdraws = $db->fetchAll("
                SELECT amount, status, created_at, handled_at
                FROM app_withdraws
                WHERE partner_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ", [$id]);

            echo json_encode([
                'success' => true,
                'data' => $row,
                'invites' => $invites,
                'earnings' => $earnings,
                'withdraws' => $withdraws,
            ]);
            exit;
        }

        if ($_POST['action'] === 'audit') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');

            $row = $db->fetchOne("SELECT id, status FROM app_partners WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '合伙人不存在']);
                exit;
            }

            $db->update('app_partners', [
                'status' => $status,
                'audit_remark' => $remark,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            $msg = $status == 1 ? '已通过审核' : '已拒绝审核';
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        if ($_POST['action'] === 'update_level') {
            $id = intval($_POST['id'] ?? 0);
            $level = trim($_POST['level'] ?? '');
            $row = $db->fetchOne("SELECT id FROM app_partners WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '合伙人不存在']);
                exit;
            }
            $db->update('app_partners', [
                'level' => $level,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '等级更新成功']);
            exit;
        }

        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id FROM app_partners WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '合伙人不存在']);
                exit;
            }
            // 关联数据清理
            $db->query("DELETE FROM app_invites WHERE partner_id = ?", [$id]);
            $db->query("DELETE FROM app_earnings WHERE partner_id = ?", [$id]);
            $db->query("DELETE FROM app_withdraws WHERE partner_id = ?", [$id]);
            $db->query("DELETE FROM app_quits WHERE partner_id = ?", [$id]);
            $db->delete('app_partners', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '合伙人删除成功']);
            exit;
        }

        if ($_POST['action'] === 'batch_audit') {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status'] ?? 0);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的合伙人']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], $ids);
            $db->query("UPDATE app_partners SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})", $params);
            $msg = $status == 1 ? '批量通过成功' : '批量拒绝成功';
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        exit;
    }
}

$levels = $db->fetchAll("SELECT DISTINCT level FROM app_partners WHERE level IS NOT NULL AND level <> '' ORDER BY level");
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

        .search-bar { background: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .search-bar input, .search-bar select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; min-width: 140px; transition: all 0.2s; }
        .search-bar input:focus, .search-bar select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15); }
        .search-bar input { min-width: 200px; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-success { background: linear-gradient(135deg, #52c41a, #95de64); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ff4d4f, #ff7875); color: white; }
        .btn-warning { background: linear-gradient(135deg, #fa8c16, #ffc069); color: white; }
        .btn-default { background: #f5f5f5; color: #595959; }
        .btn-default:hover { background: #e6e6e6; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

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
        .action-btn.edit { color: #13c2c2; }
        .action-btn.edit:hover { background: #e6fffb; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
        .modal-content.wide { max-width: 820px; }
        .modal-header { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .modal-body { margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 8px; font-weight: 500; }
        .form-group label .required { color: #ff4d4f; }
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
        .cell-name { font-weight: 600; color: var(--text-primary); }
        .cell-meta { color: var(--text-secondary); font-size: 12px; margin-top: 2px; }
        .cell-money { color: #fa8c16; font-weight: 600; }

        .detail-tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; }
        .detail-tab { padding: 10px 16px; cursor: pointer; font-size: 14px; color: var(--text-secondary); border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .detail-tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); font-weight: 600; }
        .detail-panel { display: none; }
        .detail-panel.active { display: block; }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .info-item { padding: 12px; background: #fafbff; border-radius: 8px; }
        .info-item .label { color: var(--text-secondary); font-size: 12px; margin-bottom: 4px; }
        .info-item .value { color: var(--text-primary); font-size: 15px; font-weight: 500; }

        .mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .mini-table th, .mini-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .mini-table th { background: #fafafa; color: var(--text-secondary); font-weight: 500; }

        .alert { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 240px; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 14px; animation: slideIn 0.3s ease; }
        .alert-success { background: #f6ffed; color: #237804; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffccc7; }
        .alert-info { background: #e6f7ff; color: #096dd9; border: 1px solid #91d5ff; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="toolbar">
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">👥 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <a class="btn btn-default" href="index.php">← 返回数据统计</a>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="searchKeyword" placeholder="🔍 姓名 / 电话">
                <select id="searchLevel">
                    <option value="">全部等级</option>
                    <?php foreach ($levels as $l): ?>
                        <option value="<?= htmlspecialchars($l['level']) ?>"><?= htmlspecialchars($l['level']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="searchStatus">
                    <option value="">全部状态</option>
                    <option value="0">待审核</option>
                    <option value="1">已通过</option>
                    <option value="2">已拒绝</option>
                </select>
                <button class="btn btn-primary" onclick="loadList(1)">搜索</button>
                <button class="btn btn-default" onclick="resetSearch()">重置</button>
            </div>

            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 个合伙人</div>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="batchAudit(1)">✅ 批量通过</button>
                    <button class="btn btn-warning" onclick="batchAudit(2)">❌ 批量拒绝</button>
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
            <div class="modal-header" id="auditTitle">审核合伙人</div>
            <div class="modal-body">
                <form id="auditForm">
                    <input type="hidden" name="id" id="auditId">
                    <input type="hidden" name="status" id="auditStatus">
                    <div class="form-group">
                        <label id="auditQuestion">确定要通过该合伙人的申请吗？</label>
                    </div>
                    <div class="form-group">
                        <label>审核备注</label>
                        <textarea name="remark" id="auditRemark" placeholder="可填写审核意见..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('auditModal').classList.remove('show')">取消</button>
                <button class="btn btn-primary" onclick="submitAudit()">确定</button>
            </div>
        </div>
    </div>

    <!-- 详情模态框 -->
    <div class="modal" id="detailModal">
        <div class="modal-content wide">
            <div class="modal-header">👤 合伙人详情</div>
            <div class="modal-body">
                <div class="detail-tabs">
                    <div class="detail-tab active" data-tab="info" onclick="switchTab('info')">基本信息</div>
                    <div class="detail-tab" data-tab="invites" onclick="switchTab('invites')">邀请列表</div>
                    <div class="detail-tab" data-tab="earnings" onclick="switchTab('earnings')">收益明细</div>
                    <div class="detail-tab" data-tab="withdraws" onclick="switchTab('withdraws')">提现记录</div>
                </div>
                <div class="detail-panel active" id="tab-info"></div>
                <div class="detail-panel" id="tab-invites"></div>
                <div class="detail-panel" id="tab-earnings"></div>
                <div class="detail-panel" id="tab-withdraws"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('detailModal').classList.remove('show')">关闭</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    let currentPage = 1;
    let currentDetail = null;

    function loadList(page) {
        currentPage = page || 1;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', currentPage);
        params.append('keyword', document.getElementById('searchKeyword').value.trim());
        params.append('level', document.getElementById('searchLevel').value);
        params.append('status', document.getElementById('searchStatus').value);

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
            container.innerHTML = `<div class="empty-state"><div class="icon">👥</div><div class="text">暂无合伙人数据</div></div>`;
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th width="70">ID</th>
                        <th>姓名 / 电话</th>
                        <th width="120">等级</th>
                        <th width="120">累计佣金</th>
                        <th width="110">可提现余额</th>
                        <th width="80">邀请数</th>
                        <th width="100">状态</th>
                        <th width="140">申请时间</th>
                        <th width="220">操作</th>
                    </tr>
                </thead>
                <tbody>`;
        rows.forEach(r => {
            let statusBadge, actionBtns = '';
            if (r.status == 0) {
                statusBadge = '<span class="badge badge-warning">待审核</span>';
                actionBtns += `<button class="action-btn view" onclick="openAuditModal(${r.id}, 1)">✅ 通过</button>`;
                actionBtns += `<button class="action-btn delete" onclick="openAuditModal(${r.id}, 2)">❌ 拒绝</button>`;
            } else if (r.status == 1) {
                statusBadge = '<span class="badge badge-success">已通过</span>';
            } else {
                statusBadge = '<span class="badge badge-danger">已拒绝</span>';
            }
            const created = r.created_at ? new Date(r.created_at).toLocaleDateString('zh-CN') : '-';
            html += `
                <tr data-id="${r.id}">
                    <td><input type="checkbox" class="checkbox item-checkbox" value="${r.id}"></td>
                    <td>#${r.id}</td>
                    <td>
                        <div class="cell-name">${escapeHtml(r.name)}</div>
                        <div class="cell-meta">📞 ${escapeHtml(r.phone || '-')}</div>
                    </td>
                    <td><span class="badge badge-primary">${escapeHtml(r.level || '-')}</span></td>
                    <td><span class="cell-money">¥${parseFloat(r.total_commission || 0).toFixed(2)}</span></td>
                    <td>¥${parseFloat(r.balance || 0).toFixed(2)}</td>
                    <td>${r.invite_count || 0}</td>
                    <td>${statusBadge}</td>
                    <td>${created}</td>
                    <td>
                        <button class="action-btn view" onclick="viewDetail(${r.id})">详情</button>
                        ${actionBtns}
                        <button class="action-btn delete" onclick="deleteItem(${r.id})">🗑️</button>
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
        document.getElementById('searchLevel').value = '';
        document.getElementById('searchStatus').value = '';
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
        document.getElementById('auditId').value = id;
        document.getElementById('auditStatus').value = status;
        document.getElementById('auditRemark').value = '';
        document.getElementById('auditTitle').textContent = status == 1 ? '✅ 通过审核' : '❌ 拒绝审核';
        document.getElementById('auditQuestion').textContent = status == 1
            ? '确定要通过该合伙人的申请吗？通过后该用户将获得合伙人权益。'
            : '确定要拒绝该合伙人的申请吗？';
        document.getElementById('auditModal').classList.add('show');
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
        });
    }

    function batchAudit(status) {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择合伙人', 'error');
        const action = status == 1 ? '通过' : '拒绝';
        if (!confirm(`确定要${action}选中的 ${ids.length} 个合伙人吗？`)) return;
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
        if (!confirm('确定要删除该合伙人？该操作将一并删除其邀请、收益、提现、退出记录！')) return;
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

    function switchTab(tab) {
        document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.detail-panel').forEach(p => p.classList.remove('active'));
        document.querySelector('.detail-tab[data-tab="' + tab + '"]').classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
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
                currentDetail = data;
                renderDetail(data);
                document.getElementById('detailModal').classList.add('show');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function renderDetail(data) {
        const r = data.data;
        const statusHtml = r.status == 0
            ? '<span class="badge badge-warning">待审核</span>'
            : (r.status == 1 ? '<span class="badge badge-success">已通过</span>' : '<span class="badge badge-danger">已拒绝</span>');
        document.getElementById('tab-info').innerHTML = `
            <div class="info-grid">
                <div class="info-item"><div class="label">姓名</div><div class="value">${escapeHtml(r.name)}</div></div>
                <div class="info-item"><div class="label">联系电话</div><div class="value">${escapeHtml(r.phone || '-')}</div></div>
                <div class="info-item"><div class="label">合伙人等级</div><div class="value"><span class="badge badge-primary">${escapeHtml(r.level || '-')}</span></div></div>
                <div class="info-item"><div class="label">状态</div><div class="value">${statusHtml}</div></div>
                <div class="info-item"><div class="label">累计佣金</div><div class="value cell-money">¥${parseFloat(r.total_commission || 0).toFixed(2)}</div></div>
                <div class="info-item"><div class="label">已提现</div><div class="value">¥${parseFloat(r.withdrawn || 0).toFixed(2)}</div></div>
                <div class="info-item"><div class="label">可提现余额</div><div class="value" style="color:#fa8c16;font-weight:600;">¥${parseFloat(r.balance || 0).toFixed(2)}</div></div>
                <div class="info-item"><div class="label">邀请用户数</div><div class="value">${r.invite_count || 0} 人</div></div>
                <div class="info-item" style="grid-column:span 2;"><div class="label">申请理由</div><div class="value">${escapeHtml(r.apply_reason || '-')}</div></div>
                <div class="info-item" style="grid-column:span 2;"><div class="label">审核备注</div><div class="value">${escapeHtml(r.audit_remark || '-')}</div></div>
                <div class="info-item"><div class="label">申请时间</div><div class="value">${r.created_at || '-'}</div></div>
                <div class="info-item"><div class="label">最近更新</div><div class="value">${r.updated_at || '-'}</div></div>
            </div>
        `;

        // 邀请列表
        const invites = data.invites || [];
        if (invites.length === 0) {
            document.getElementById('tab-invites').innerHTML = '<div class="empty-state"><div class="icon">👥</div><div class="text">暂无邀请记录</div></div>';
        } else {
            let ih = '<table class="mini-table"><thead><tr><th>被邀请人</th><th>电话</th><th>佣金</th><th>邀请时间</th></tr></thead><tbody>';
            invites.forEach(i => {
                ih += `<tr><td>${escapeHtml(i.invitee_name || '-')}</td><td>${escapeHtml(i.invitee_phone || '-')}</td><td class="cell-money">¥${parseFloat(i.commission || 0).toFixed(2)}</td><td>${i.created_at || '-'}</td></tr>`;
            });
            ih += '</tbody></table>';
            document.getElementById('tab-invites').innerHTML = ih;
        }

        // 收益明细
        const earnings = data.earnings || [];
        if (earnings.length === 0) {
            document.getElementById('tab-earnings').innerHTML = '<div class="empty-state"><div class="icon">💰</div><div class="text">暂无收益记录</div></div>';
        } else {
            let eh = '<table class="mini-table"><thead><tr><th>类型</th><th>金额</th><th>来源</th><th>说明</th><th>时间</th></tr></thead><tbody>';
            earnings.forEach(e => {
                const typeMap = { commission: '<span class="badge badge-success">佣金</span>', withdraw: '<span class="badge badge-warning">提现</span>', refund: '<span class="badge badge-danger">退款</span>' };
                const amt = parseFloat(e.amount || 0);
                const amtClass = amt >= 0 ? 'cell-money' : 'cell-money';
                const amtStr = (amt >= 0 ? '+' : '') + '¥' + amt.toFixed(2);
                eh += `<tr><td>${typeMap[e.type] || e.type}</td><td class="${amtClass}">${amtStr}</td><td>${escapeHtml(e.source || '-')}</td><td>${escapeHtml(e.description || '-')}</td><td>${e.created_at || '-'}</td></tr>`;
            });
            eh += '</tbody></table>';
            document.getElementById('tab-earnings').innerHTML = eh;
        }

        // 提现记录
        const withdraws = data.withdraws || [];
        if (withdraws.length === 0) {
            document.getElementById('tab-withdraws').innerHTML = '<div class="empty-state"><div class="icon">💸</div><div class="text">暂无提现记录</div></div>';
        } else {
            let wh = '<table class="mini-table"><thead><tr><th>金额</th><th>状态</th><th>申请时间</th><th>处理时间</th></tr></thead><tbody>';
            withdraws.forEach(w => {
                const st = w.status == 0
                    ? '<span class="badge badge-warning">待审核</span>'
                    : (w.status == 1 ? '<span class="badge badge-success">已通过</span>' : '<span class="badge badge-danger">已拒绝</span>');
                wh += `<tr><td class="cell-money">¥${parseFloat(w.amount || 0).toFixed(2)}</td><td>${st}</td><td>${w.created_at || '-'}</td><td>${w.handled_at || '-'}</td></tr>`;
            });
            wh += '</tbody></table>';
            document.getElementById('tab-withdraws').innerHTML = wh;
        }

        switchTab('info');
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
