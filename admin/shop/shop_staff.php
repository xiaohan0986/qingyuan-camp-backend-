<?php
/**
 * 店员管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '店员管理';

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_POST['action'] === 'list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $storeId = intval($_POST['store_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;

            $where = ['1=1'];
            $params = [];

            if ($keyword !== '') {
                $where[] = '(s.name LIKE ? OR s.phone LIKE ?)';
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            if ($storeId > 0) {
                $where[] = 's.store_id = ?';
                $params[] = $storeId;
            }
            if ($status !== '') {
                $where[] = 's.status = ?';
                $params[] = intval($status);
            }

            $whereSql = implode(' AND ', $where);
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM shop_staff s WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = $db->fetchAll("
                SELECT s.*, st.name AS store_name
                FROM shop_staff s
                LEFT JOIN stores st ON s.store_id = st.id
                WHERE {$whereSql}
                ORDER BY s.id DESC
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
                SELECT s.*, st.name AS store_name
                FROM shop_staff s
                LEFT JOIN stores st ON s.store_id = st.id
                WHERE s.id = ?
            ", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '店员不存在']);
                exit;
            }
            // 该店员的核销统计
            $verifyCount = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_verify_log WHERE staff_id = ?", [$id]);
            $row['verify_count'] = intval($verifyCount['c'] ?? 0);
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        if ($_POST['action'] === 'add') {
            $storeId = intval($_POST['store_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = trim($_POST['role'] ?? '店员');
            $status = intval($_POST['status'] ?? 1);

            if ($storeId <= 0) {
                echo json_encode(['success' => false, 'message' => '请选择所属门店']);
                exit;
            }
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '姓名不能为空']);
                exit;
            }

            $store = $db->fetchOne("SELECT id FROM stores WHERE id = ?", [$storeId]);
            if (!$store) {
                echo json_encode(['success' => false, 'message' => '门店不存在']);
                exit;
            }

            $id = $db->insert('shop_staff', [
                'store_id' => $storeId,
                'name' => $name,
                'phone' => $phone,
                'role' => $role,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['success' => true, 'message' => '店员添加成功', 'id' => $id]);
            exit;
        }

        if ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id FROM shop_staff WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '店员不存在']);
                exit;
            }

            $storeId = intval($_POST['store_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($storeId <= 0 || $name === '') {
                echo json_encode(['success' => false, 'message' => '门店和姓名不能为空']);
                exit;
            }

            $db->update('shop_staff', [
                'store_id' => $storeId,
                'name' => $name,
                'phone' => trim($_POST['phone'] ?? ''),
                'role' => trim($_POST['role'] ?? '店员'),
                'status' => intval($_POST['status'] ?? 1),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => '店员更新成功']);
            exit;
        }

        if ($_POST['action'] === 'toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $db->update('shop_staff', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '状态更新成功']);
            exit;
        }

        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            $verifyCount = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_verify_log WHERE staff_id = ?", [$id]);
            if (intval($verifyCount['c'] ?? 0) > 0) {
                echo json_encode(['success' => false, 'message' => '该店员存在核销记录，无法删除']);
                exit;
            }
            $db->delete('shop_staff', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '店员删除成功']);
            exit;
        }

        if ($_POST['action'] === 'batch_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的店员']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM shop_staff WHERE id IN ({$placeholders})", $ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        }

        if ($_POST['action'] === 'batch_status') {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status'] ?? 0);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的店员']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], $ids);
            $db->query("UPDATE shop_staff SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})", $params);
            echo json_encode(['success' => true, 'message' => '批量操作成功']);
            exit;
        }

        if ($_POST['action'] === 'stores') {
            $rows = $db->fetchAll("SELECT id, name FROM stores WHERE status = 1 ORDER BY sort DESC, id ASC");
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        exit;
    }
}

$stores = $db->fetchAll("SELECT id, name FROM stores WHERE status = 1 ORDER BY sort DESC, id ASC");
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
        .btn-danger { background: linear-gradient(135deg, #ff4d4f, #ff7875); color: white; }
        .btn-success { background: linear-gradient(135deg, #52c41a, #95de64); color: white; }
        .btn-default { background: #f5f5f5; color: #595959; }
        .btn-default:hover { background: #e6e6e6; }

        .batch-toolbar { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: none; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .batch-toolbar.show { display: flex; }

        .toggle-switch { position: relative; width: 44px; height: 22px; background: #d9d9d9; border-radius: 11px; cursor: pointer; transition: background 0.3s; display: inline-block; }
        .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch.on { background: linear-gradient(135deg, #52c41a, #95de64); }
        .toggle-switch.on::after { transform: translateX(22px); }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #237804; }
        .badge-danger { background: #fff2f0; color: #cf1322; }
        .badge-primary { background: #e6f7ff; color: #096dd9; }
        .badge-warning { background: #fff7e6; color: #d46b08; }

        .action-btn { padding: 4px 10px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.2s; }
        .action-btn.edit { color: var(--primary-color); }
        .action-btn.edit:hover { background: #f0f5ff; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .action-btn.view { color: #13c2c2; }
        .action-btn.view:hover { background: #e6fffb; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
        .modal-header { font-size: 20px; font-weight: 600; color: var(--text-primary); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .modal-body { margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 8px; font-weight: 500; }
        .form-group label .required { color: #ff4d4f; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; transition: all 0.2s; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
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
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">👨‍💼 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <span>➕</span> 新增店员
                    </button>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="searchKeyword" placeholder="🔍 姓名 / 电话">
                <select id="searchStore">
                    <option value="">全部门店</option>
                    <?php foreach ($stores as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="searchStatus">
                    <option value="">全部状态</option>
                    <option value="1">在职</option>
                    <option value="0">离职</option>
                </select>
                <button class="btn btn-primary" onclick="loadList(1)">搜索</button>
                <button class="btn btn-default" onclick="resetSearch()">重置</button>
            </div>

            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 个店员</div>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="batchSetStatus(1)">✅ 批量启用</button>
                    <button class="btn btn-default" onclick="batchSetStatus(0)">⏸️ 批量停用</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>

            <div class="data-table" id="tableContainer">
                <div class="empty-state"><div class="icon">⏳</div><div class="text">数据加载中…</div></div>
            </div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 表单模态框 -->
    <div class="modal" id="formModal">
        <div class="modal-content">
            <div class="modal-header" id="formTitle">新增店员</div>
            <div class="modal-body">
                <form id="staffForm">
                    <input type="hidden" name="id" id="formId">
                    <div class="form-group">
                        <label>所属门店 <span class="required">*</span></label>
                        <select name="store_id" id="formStoreId" required>
                            <option value="">请选择门店</option>
                            <?php foreach ($stores as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>姓名 <span class="required">*</span></label>
                        <input type="text" name="name" id="formName" required placeholder="店员姓名">
                    </div>
                    <div class="form-group">
                        <label>联系电话</label>
                        <input type="text" name="phone" id="formPhone" placeholder="如：13800138000">
                    </div>
                    <div class="form-group">
                        <label>角色</label>
                        <select name="role" id="formRole">
                            <option value="店长">店长</option>
                            <option value="店员" selected>店员</option>
                            <option value="收银员">收银员</option>
                            <option value="客服">客服</option>
                            <option value="管理员">管理员</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>状态</label>
                        <select name="status" id="formStatus">
                            <option value="1">在职</option>
                            <option value="0">离职</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeFormModal()">取消</button>
                <button class="btn btn-primary" id="formSubmitBtn" onclick="submitForm()">确定保存</button>
            </div>
        </div>
    </div>

    <!-- 详情模态框 -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">👨‍💼 店员详情</div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="document.getElementById('detailModal').classList.remove('show')">关闭</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    let currentPage = 1;
    let editingId = 0;

    function loadList(page) {
        currentPage = page || 1;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', currentPage);
        params.append('keyword', document.getElementById('searchKeyword').value.trim());
        params.append('store_id', document.getElementById('searchStore').value);
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
            container.innerHTML = `<div class="empty-state"><div class="icon">👨‍💼</div><div class="text">暂无店员数据，点击"新增店员"开始添加</div></div>`;
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th width="70">ID</th>
                        <th>姓名 / 电话</th>
                        <th width="80">角色</th>
                        <th width="220">所属门店</th>
                        <th width="80">状态</th>
                        <th width="140">添加时间</th>
                        <th width="180">操作</th>
                    </tr>
                </thead>
                <tbody>`;
        rows.forEach(r => {
            const statusBadge = r.status == 1 ? '<span class="badge badge-success">在职</span>' : '<span class="badge badge-danger">离职</span>';
            const roleBadge = r.role === '店长'
                ? '<span class="badge badge-warning">' + escapeHtml(r.role) + '</span>'
                : '<span class="badge badge-primary">' + escapeHtml(r.role || '店员') + '</span>';
            const created = r.created_at ? new Date(r.created_at).toLocaleDateString('zh-CN') : '-';
            const storeName = r.store_name || '<span style="color:#bbb">已删除门店</span>';
            html += `
                <tr data-id="${r.id}">
                    <td><input type="checkbox" class="checkbox item-checkbox" value="${r.id}"></td>
                    <td>#${r.id}</td>
                    <td>
                        <div class="cell-name">${escapeHtml(r.name)}</div>
                        <div class="cell-meta">📞 ${escapeHtml(r.phone || '-')}</div>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${storeName}</td>
                    <td>
                        <div class="toggle-switch ${r.status == 1 ? 'on' : ''}"
                             onclick="toggleStatus(${r.id}, ${r.status == 1 ? 0 : 1})"></div>
                    </td>
                    <td>${created}</td>
                    <td>
                        <button class="action-btn view" onclick="viewDetail(${r.id})">详情</button>
                        <button class="action-btn edit" onclick="openEditModal(${r.id})">编辑</button>
                        <button class="action-btn delete" onclick="deleteItem(${r.id})">删除</button>
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
        const toolbar = document.getElementById('batchToolbar');
        document.getElementById('selectedCount').textContent = checked.length;
        toolbar.classList.toggle('show', checked.length > 0);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function toggleStatus(id, status) {
        if (!confirm('确定要' + (status ? '启用' : '停用') + '该店员吗？')) return;
        postAction('toggle_status', {id, status}, '状态更新成功');
    }

    function deleteItem(id) {
        if (!confirm('确定要删除该店员吗？')) return;
        postAction('delete', {id}, '店员删除成功', () => loadList(currentPage));
    }

    function batchDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择店员', 'error');
        if (!confirm(`确定要删除选中的 ${ids.length} 个店员吗？`)) return;
        postAction('batch_delete', {ids}, '批量删除成功', () => loadList(1));
    }

    function batchSetStatus(status) {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择店员', 'error');
        const action = status ? '启用' : '停用';
        if (!confirm(`确定要${action}选中的 ${ids.length} 个店员吗？`)) return;
        postAction('batch_status', {ids, status}, '批量操作成功', () => loadList(currentPage));
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

    function openAddModal() {
        editingId = 0;
        document.getElementById('formTitle').textContent = '新增店员';
        document.getElementById('staffForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('formStatus').value = '1';
        document.getElementById('formRole').value = '店员';
        document.getElementById('formModal').classList.add('show');
    }

    function openEditModal(id) {
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
                editingId = r.id;
                document.getElementById('formTitle').textContent = '编辑店员 #' + r.id;
                document.getElementById('formId').value = r.id;
                document.getElementById('formStoreId').value = r.store_id;
                document.getElementById('formName').value = r.name || '';
                document.getElementById('formPhone').value = r.phone || '';
                document.getElementById('formRole').value = r.role || '店员';
                document.getElementById('formStatus').value = r.status;
                document.getElementById('formModal').classList.add('show');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function closeFormModal() {
        document.getElementById('formModal').classList.remove('show');
    }

    function submitForm() {
        const storeId = document.getElementById('formStoreId').value;
        const name = document.getElementById('formName').value.trim();
        if (!storeId) return showMessage('请选择所属门店', 'error');
        if (!name) return showMessage('姓名不能为空', 'error');

        const params = new URLSearchParams();
        params.append('action', editingId ? 'edit' : 'add');
        if (editingId) params.append('id', editingId);
        params.append('store_id', storeId);
        params.append('name', name);
        params.append('phone', document.getElementById('formPhone').value.trim());
        params.append('role', document.getElementById('formRole').value);
        params.append('status', document.getElementById('formStatus').value);

        const btn = document.getElementById('formSubmitBtn');
        btn.disabled = true;
        btn.textContent = '保存中…';

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                closeFormModal();
                loadList(editingId ? currentPage : 1);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '确定保存';
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
                document.getElementById('detailBody').innerHTML = `
                    <div class="form-group"><label>姓名</label><div class="cell-name">${escapeHtml(r.name)}</div></div>
                    <div class="form-group"><label>所属门店</label><div>${escapeHtml(r.store_name || '-')}</div></div>
                    <div class="form-group"><label>联系电话</label><div>${escapeHtml(r.phone || '-')}</div></div>
                    <div class="form-group"><label>角色</label><div><span class="badge badge-primary">${escapeHtml(r.role || '-')}</span></div></div>
                    <div class="form-group"><label>核销次数</label><div>${r.verify_count || 0} 次</div></div>
                    <div class="form-group"><label>状态</label><div>${r.status == 1 ? '<span class="badge badge-success">在职</span>' : '<span class="badge badge-danger">离职</span>'}</div></div>
                    <div class="form-group"><label>添加时间</label><div>${r.created_at || '-'}</div></div>
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

    document.getElementById('formModal').addEventListener('click', function(e) {
        if (e.target === this) closeFormModal();
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
