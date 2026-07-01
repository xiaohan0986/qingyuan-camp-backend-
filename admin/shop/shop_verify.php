<?php
/**
 * 核销记录
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '核销记录';

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_POST['action'] === 'list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $storeId = intval($_POST['store_id'] ?? 0);
            $dateFrom = trim($_POST['date_from'] ?? '');
            $dateTo = trim($_POST['date_to'] ?? '');
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;

            $where = ['1=1'];
            $params = [];

            if ($keyword !== '') {
                $where[] = '(v.order_no LIKE ? OR v.member_name LIKE ? OR v.member_phone LIKE ? OR v.goods_name LIKE ? OR v.verify_code LIKE ?)';
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            if ($storeId > 0) {
                $where[] = 'v.store_id = ?';
                $params[] = $storeId;
            }
            if ($dateFrom !== '') {
                $where[] = 'v.verify_time >= ?';
                $params[] = $dateFrom . ' 00:00:00';
            }
            if ($dateTo !== '') {
                $where[] = 'v.verify_time <= ?';
                $params[] = $dateTo . ' 23:59:59';
            }

            $whereSql = implode(' AND ', $where);
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM shop_verify_log v WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = $db->fetchAll("
                SELECT v.*, s.name AS store_name, st.name AS staff_name
                FROM shop_verify_log v
                LEFT JOIN stores s ON v.store_id = s.id
                LEFT JOIN shop_staff st ON v.staff_id = st.id
                WHERE {$whereSql}
                ORDER BY v.verify_time DESC, v.id DESC
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
                SELECT v.*, s.name AS store_name, st.name AS staff_name
                FROM shop_verify_log v
                LEFT JOIN stores s ON v.store_id = s.id
                LEFT JOIN shop_staff st ON v.staff_id = st.id
                WHERE v.id = ?
            ", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '核销记录不存在']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $db->delete('shop_verify_log', 'id = ?', [$id]);
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
            $db->query("DELETE FROM shop_verify_log WHERE id IN ({$placeholders})", $ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        }

        if ($_POST['action'] === 'stats') {
            $total = $db->fetchOne("SELECT COUNT(*) AS c, COALESCE(SUM(verify_amount), 0) AS amount FROM shop_verify_log");
            $today = $db->fetchOne("SELECT COUNT(*) AS c, COALESCE(SUM(verify_amount), 0) AS amount FROM shop_verify_log WHERE DATE(verify_time) = CURDATE()");
            $month = $db->fetchOne("SELECT COUNT(*) AS c, COALESCE(SUM(verify_amount), 0) AS amount FROM shop_verify_log WHERE DATE_FORMAT(verify_time, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
            echo json_encode([
                'success' => true,
                'total_count' => intval($total['c'] ?? 0),
                'total_amount' => floatval($total['amount'] ?? 0),
                'today_count' => intval($today['c'] ?? 0),
                'today_amount' => floatval($today['amount'] ?? 0),
                'month_count' => intval($month['c'] ?? 0),
                'month_amount' => floatval($month['amount'] ?? 0),
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        exit;
    }
}

$stores = $db->fetchAll("SELECT id, name FROM stores ORDER BY sort DESC, id ASC");
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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 4px;
            background: var(--primary-gradient);
        }
        .stat-card .label { color: var(--text-secondary); font-size: 13px; margin-bottom: 8px; }
        .stat-card .value { font-size: 26px; font-weight: 700; color: var(--text-primary); }
        .stat-card .sub { color: var(--text-secondary); font-size: 12px; margin-top: 4px; }
        .stat-card.green::before { background: linear-gradient(135deg, #52c41a, #95de64); }
        .stat-card.orange::before { background: linear-gradient(135deg, #fa8c16, #ffc069); }

        .search-bar { background: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .search-bar input, .search-bar select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; min-width: 140px; transition: all 0.2s; }
        .search-bar input:focus, .search-bar select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15); }
        .search-bar input[type="text"] { min-width: 200px; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #ff4d4f, #ff7875); color: white; }
        .btn-default { background: #f5f5f5; color: #595959; }
        .btn-default:hover { background: #e6e6e6; }

        .batch-toolbar { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: none; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .batch-toolbar.show { display: flex; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-primary { background: #e6f7ff; color: #096dd9; }
        .badge-success { background: #f6ffed; color: #237804; }

        .action-btn { padding: 4px 10px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.2s; }
        .action-btn.view { color: var(--primary-color); }
        .action-btn.view:hover { background: #f0f5ff; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
        .modal-header { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .modal-body { margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 6px; }
        .form-group .value { font-size: 15px; color: var(--text-primary); font-weight: 500; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid var(--border-color); }

        .empty-state { text-align: center; padding: 80px 20px; color: var(--text-secondary); }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state .text { font-size: 14px; }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; background: white; border: 1px solid var(--border-color); border-radius: 6px; text-decoration: none; color: var(--text-primary); font-size: 13px; transition: all 0.2s; }
        .pagination a:hover, .pagination .active { background: var(--primary-gradient); color: white; border-color: transparent; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }

        .checkbox { width: 16px; height: 16px; cursor: pointer; }
        .cell-order { font-family: 'SF Mono', Monaco, monospace; color: var(--primary-color); font-weight: 600; }
        .cell-amount { color: #fa8c16; font-weight: 600; }
        .cell-meta { color: var(--text-secondary); font-size: 12px; margin-top: 2px; }

        .alert { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 240px; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 14px; animation: slideIn 0.3s ease; }
        .alert-success { background: #f6ffed; color: #237804; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffccc7; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="toolbar">
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">✅ <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-default" onclick="loadList(currentPage); loadStats();">🔄 刷新数据</button>
                </div>
            </div>

            <!-- 统计卡片 -->
            <div class="stats-row" id="statsRow">
                <div class="stat-card">
                    <div class="label">总核销次数</div>
                    <div class="value" id="statTotalCount">-</div>
                    <div class="sub">总核销金额：<span class="cell-amount" id="statTotalAmount">-</span></div>
                </div>
                <div class="stat-card green">
                    <div class="label">今日核销</div>
                    <div class="value" id="statTodayCount">-</div>
                    <div class="sub">今日金额：<span class="cell-amount" id="statTodayAmount">-</span></div>
                </div>
                <div class="stat-card orange">
                    <div class="label">本月核销</div>
                    <div class="value" id="statMonthCount">-</div>
                    <div class="sub">本月金额：<span class="cell-amount" id="statMonthAmount">-</span></div>
                </div>
            </div>

            <!-- 搜索栏 -->
            <div class="search-bar">
                <input type="text" id="searchKeyword" placeholder="🔍 订单号/会员姓名/电话/核销码/商品">
                <select id="searchStore">
                    <option value="">全部门店</option>
                    <?php foreach ($stores as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
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
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>

            <div class="data-table" id="tableContainer">
                <div class="empty-state"><div class="icon">⏳</div><div class="text">数据加载中…</div></div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 详情模态框 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">✅ 核销记录详情</div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('detailModal').classList.remove('show')">关闭</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    let currentPage = 1;

    function loadStats() {
        const params = new URLSearchParams();
        params.append('action', 'stats');
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('statTotalCount').textContent = data.total_count;
                document.getElementById('statTotalAmount').textContent = '¥' + data.total_amount.toFixed(2);
                document.getElementById('statTodayCount').textContent = data.today_count;
                document.getElementById('statTodayAmount').textContent = '¥' + data.today_amount.toFixed(2);
                document.getElementById('statMonthCount').textContent = data.month_count;
                document.getElementById('statMonthAmount').textContent = '¥' + data.month_amount.toFixed(2);
            }
        });
    }

    function loadList(page) {
        currentPage = page || 1;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', currentPage);
        params.append('keyword', document.getElementById('searchKeyword').value.trim());
        params.append('store_id', document.getElementById('searchStore').value);
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
            container.innerHTML = `<div class="empty-state"><div class="icon">✅</div><div class="text">暂无核销记录</div></div>`;
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th width="70">ID</th>
                        <th width="170">订单号 / 核销码</th>
                        <th width="140">核销门店</th>
                        <th>商品 / 会员</th>
                        <th width="100">核销员</th>
                        <th width="100">核销金额</th>
                        <th width="140">核销时间</th>
                        <th width="120">操作</th>
                    </tr>
                </thead>
                <tbody>`;
        rows.forEach(r => {
            const verifyTime = r.verify_time ? new Date(r.verify_time).toLocaleString('zh-CN', {hour12: false}) : '-';
            const storeName = r.store_name || '<span style="color:#bbb">已删除</span>';
            const staffName = r.staff_name || '-';
            const member = r.member_name || '匿名';
            const memberPhone = r.member_phone || '';
            html += `
                <tr data-id="${r.id}">
                    <td><input type="checkbox" class="checkbox item-checkbox" value="${r.id}"></td>
                    <td>#${r.id}</td>
                    <td>
                        <div class="cell-order">${escapeHtml(r.order_no || '-')}</div>
                        <div class="cell-meta">🔑 ${escapeHtml(r.verify_code || '-')}</div>
                    </td>
                    <td>${storeName}</td>
                    <td>
                        <div style="color:var(--text-primary);font-weight:500;">${escapeHtml(r.goods_name || '-')}</div>
                        <div class="cell-meta">👤 ${escapeHtml(member)} ${memberPhone ? '· ' + escapeHtml(memberPhone) : ''}</div>
                    </td>
                    <td>${escapeHtml(staffName)}</td>
                    <td><span class="cell-amount">¥${parseFloat(r.verify_amount || 0).toFixed(2)}</span></td>
                    <td>${verifyTime}</td>
                    <td>
                        <button class="action-btn view" onclick="viewDetail(${r.id})">详情</button>
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
        document.getElementById('searchStore').value = '';
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
        const toolbar = document.getElementById('batchToolbar');
        document.getElementById('selectedCount').textContent = checked.length;
        toolbar.classList.toggle('show', checked.length > 0);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function deleteItem(id) {
        if (!confirm('确定要删除该核销记录吗？此操作不可恢复！')) return;
        postAction('delete', {id}, '记录删除成功', () => { loadList(currentPage); loadStats(); });
    }

    function batchDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择记录', 'error');
        if (!confirm(`确定要删除选中的 ${ids.length} 条记录吗？`)) return;
        postAction('batch_delete', {ids}, '批量删除成功', () => { loadList(1); loadStats(); });
    }

    function postAction(action, extra, successMsg, callback) {
        const params = new URLSearchParams();
        params.append('action', action);
        Object.keys(extra).forEach(k => {
            const v = extra[k];
            if (Array.isArray(v)) v.forEach(x => params.append(k + '[]', x));
            else params.append(k, v);
        });
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + (data.message || successMsg), 'success');
                if (callback) callback();
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
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
                document.getElementById('detailBody').innerHTML = `
                    <div class="form-group"><label>订单号</label><div class="value cell-order">${escapeHtml(r.order_no || '-')}</div></div>
                    <div class="form-group"><label>核销码</label><div class="value">${escapeHtml(r.verify_code || '-')}</div></div>
                    <div class="form-group"><label>核销门店</label><div class="value">${escapeHtml(r.store_name || '-')}</div></div>
                    <div class="form-group"><label>核销员</label><div class="value">${escapeHtml(r.staff_name || '-')}</div></div>
                    <div class="form-group"><label>商品名称</label><div class="value">${escapeHtml(r.goods_name || '-')}</div></div>
                    <div class="form-group"><label>核销金额</label><div class="value cell-amount">¥${parseFloat(r.verify_amount || 0).toFixed(2)}</div></div>
                    <div class="form-group"><label>会员姓名</label><div class="value">${escapeHtml(r.member_name || '-')}</div></div>
                    <div class="form-group"><label>会员电话</label><div class="value">${escapeHtml(r.member_phone || '-')}</div></div>
                    <div class="form-group"><label>核销时间</label><div class="value">${r.verify_time || '-'}</div></div>
                    <div class="form-group"><label>记录创建</label><div class="value">${r.created_at || '-'}</div></div>
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

    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });

    document.getElementById('searchKeyword').addEventListener('keydown', e => {
        if (e.key === 'Enter') loadList(1);
    });

    loadStats();
    loadList(1);
    </script>
</body>
</html>
