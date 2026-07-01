<?php
/**
 * 开屏广告管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '开屏广告管理';

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        try {
            $title = trim($_POST['title'] ?? '');
            $image = $_POST['image'] ?? '';
            $link_url = trim($_POST['link_url'] ?? '');
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            
            if (empty($title)) { echo json_encode(['success' => false, 'message' => '广告标题不能为空']); exit; }
            if (empty($image)) { echo json_encode(['success' => false, 'message' => '请上传广告图片']); exit; }
            
            $db->query("INSERT INTO marketing_ads (title, image, link_url, sort, status, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())", [$title, $image, $link_url, $sort, $status, $start_time ?: null, $end_time ?: null]);
            echo json_encode(['success' => true, 'message' => '广告添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'update') {
        try {
            $id = intval($_POST['id']);
            $title = trim($_POST['title'] ?? '');
            $image = $_POST['image'] ?? '';
            $link_url = trim($_POST['link_url'] ?? '');
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            
            if (empty($title)) { echo json_encode(['success' => false, 'message' => '广告标题不能为空']); exit; }
            
            $db->query("UPDATE marketing_ads SET title=?, image=?, link_url=?, sort=?, status=?, start_time=?, end_time=? WHERE id=?", [$title, $image, $link_url, $sort, $status, $start_time ?: null, $end_time ?: null, $id]);
            echo json_encode(['success' => true, 'message' => '广告更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'toggle_status') {
        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            $db->query("UPDATE marketing_ads SET status = ? WHERE id = ?", [$status, $id]);
            echo json_encode(['success' => true, 'message' => '状态更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            $db->query("DELETE FROM marketing_ads WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => '广告删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'batch_delete') {
        try {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => '请选择要删除的广告']); exit; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM marketing_ads WHERE id IN ($placeholders)", $ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

$ads = $db->fetchAll("SELECT * FROM marketing_ads ORDER BY status DESC, sort ASC, id DESC");
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
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .toolbar-actions { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-danger { background: #ff4d4f; color: white; }
        .btn-danger:hover { background: #ff7875; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-default:hover { background: #e6e6e6; }
        .batch-toolbar { background: linear-gradient(135deg, #e6e7ff, #f3e7ff); border: 1px solid #b3b7ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: none; align-items: center; justify-content: space-between; }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
        .data-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .checkbox { width: 18px; height: 18px; cursor: pointer; }
        .toggle-switch { position: relative; width: 44px; height: 22px; background: #d9d9d9; border-radius: 11px; cursor: pointer; transition: background 0.3s; }
        .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch.on { background: #1890ff; }
        .toggle-switch.on::after { transform: translateX(22px); }
        .action-btn { padding: 6px 12px; border: none; background: transparent; cursor: pointer; border-radius: 4px; font-size: 13px; transition: all 0.3s; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .action-btn.edit { color: #1890ff; }
        .action-btn.edit:hover { background: #f0f0ff; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 32px; min-width: 500px; max-width: 600px; box-shadow: 0 4px 24px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
        .modal-header { font-size: 20px; font-weight: 600; color: #262626; margin-bottom: 24px; background: linear-gradient(135deg, #1890ff, #40a9ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 14px; color: #262626; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        .image-uploader { border: 2px dashed #d9d9d9; border-radius: 12px; padding: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.3s; background: #fafafa; }
        .image-uploader:hover { border-color: #1890ff; background: #f5f7ff; }
        .image-preview { position: relative; margin-top: 12px; max-width: 300px; }
        .image-preview img { width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid #f0f0f0; }
        .image-preview .remove { position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 50%; background: rgba(0,0,0,0.6); color: white; border: none; cursor: pointer; font-size: 16px; }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #8c8c8c; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .ad-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="toolbar">
                <h1 style="font-size: 24px; color: #262626; margin: 0;"><?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>➕</span> 新增广告
                    </button>
                </div>
            </div>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div class="batch-info">已选择 <strong id="selectedCount">0</strong> 个广告</div>
                <div class="batch-actions">
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($ads)): ?>
                    <div class="empty-state">
                        <div class="icon">📢</div>
                        <div>暂无广告，点击"新增广告"添加</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th width="80">ID</th>
                                <th width="100">图片</th>
                                <th>标题</th>
                                <th>链接</th>
                                <th width="120">生效时间</th>
                                <th width="80">排序</th>
                                <th width="90">状态</th>
                                <th width="160">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ads as $ad): ?>
                                <tr data-id="<?= $ad['id'] ?>">
                                    <td><input type="checkbox" class="checkbox item-checkbox" value="<?= $ad['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>#<?= $ad['id'] ?></td>
                                    <td>
                                        <?php if (!empty($ad['image'])): ?>
                                            <img src="<?= htmlspecialchars($ad['image']) ?>" class="ad-thumb" alt="广告图">
                                        <?php else: ?>
                                            <span style="color: #ccc;">无图</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($ad['title']) ?></strong></td>
                                    <td>
                                        <?php if (!empty($ad['link_url'])): ?>
                                            <a href="<?= htmlspecialchars($ad['link_url']) ?>" target="_blank" style="color: #1890ff; text-decoration: none;">
                                                <?= mb_substr(htmlspecialchars($ad['link_url']), 0, 30) ?>...
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #ccc;">无链接</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?php if ($ad['start_time'] || $ad['end_time']): ?>
                                            <?= $ad['start_time'] ? date('Y-m-d', strtotime($ad['start_time'])) : '不限' ?><br>
                                            <span style="color: #8c8c8c;">至</span> <?= $ad['end_time'] ? date('Y-m-d', strtotime($ad['end_time'])) : '不限' ?>
                                        <?php else: ?>
                                            <span style="color: #8c8c8c;">长期</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $ad['sort'] ?></td>
                                    <td>
                                        <div class="toggle-switch <?= $ad['status'] ? 'on' : '' ?>" 
                                             onclick="toggleStatus(<?= $ad['id'] ?>, <?= $ad['status'] ? 0 : 1 ?>)"
                                             title="<?= $ad['status'] ? '点击禁用' : '点击启用' ?>"></div>
                                    </td>
                                    <td>
                                        <button class="action-btn edit" onclick="editAd(<?= $ad['id'] ?>)">编辑</button>
                                        <button class="action-btn delete" onclick="deleteAd(<?= $ad['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="modal" id="adModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">新增广告</div>
            <div class="modal-body">
                <form id="adForm">
                    <input type="hidden" name="id" id="adId" value="">
                    
                    <div class="form-group">
                        <label>广告标题 <span style="color: red">*</span></label>
                        <input type="text" name="title" id="adTitle" required placeholder="请输入广告标题">
                    </div>
                    
                    <div class="form-group">
                        <label>广告图片 <span style="color: red">*</span></label>
                        <div class="image-uploader" onclick="document.getElementById('adImageInput').click()">
                            <div style="font-size: 32px; margin-bottom: 8px;">🖼️</div>
                            <div style="font-size: 13px; font-weight: 500; color: #262626;">点击上传广告图片</div>
                            <div style="font-size: 11px; color: #8c8c8c; margin-top: 4px;">建议尺寸 750×1334，支持 JPG/PNG</div>
                        </div>
                        <input type="file" id="adImageInput" accept="image/*" style="display: none;" onchange="handleImageUpload(event)">
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImg" src="" alt="预览">
                            <button type="button" class="remove" onclick="removeImage()">×</button>
                        </div>
                        <input type="hidden" name="image" id="adImage" value="">
                    </div>
                    
                    <div class="form-group">
                        <label>跳转链接</label>
                        <input type="text" name="link_url" id="adLink" placeholder="例如: https://example.com 或 pages/detail?id=1">
                        <div class="hint">用户点击广告后跳转的地址，留空则不跳转</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>开始时间</label>
                            <input type="datetime-local" name="start_time" id="adStartTime">
                        </div>
                        <div class="form-group">
                            <label>结束时间</label>
                            <input type="datetime-local" name="end_time" id="adEndTime">
                        </div>
                    </div>
                    <div class="hint" style="margin-top: -12px; margin-bottom: 18px;">留空表示长期有效</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>排序</label>
                            <input type="number" name="sort" id="adSort" value="0" placeholder="数字越小越靠前">
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <select name="status" id="adStatus">
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="submitForm()">确定</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    const ads = <?= json_encode($ads, JSON_UNESCAPED_UNICODE) ?>;
    
    function handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { showMessage('❌ 图片大小不能超过 5MB', 'error'); return; }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('adImage').value = e.target.result;
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    
    function removeImage() {
        document.getElementById('adImage').value = '';
        document.getElementById('previewImg').src = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('adImageInput').value = '';
    }
    
    function showAddModal() {
        document.getElementById('modalTitle').textContent = '新增广告';
        document.getElementById('adForm').reset();
        document.getElementById('adId').value = '';
        document.getElementById('adImage').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('adSort').value = '0';
        document.getElementById('adStatus').value = '1';
        document.getElementById('adModal').classList.add('show');
    }
    
    function closeModal() { document.getElementById('adModal').classList.remove('show'); }
    
    function editAd(id) {
        const ad = ads.find(a => a.id == id);
        if (!ad) return;
        document.getElementById('modalTitle').textContent = '编辑广告';
        document.getElementById('adId').value = ad.id;
        document.getElementById('adTitle').value = ad.title;
        document.getElementById('adImage').value = ad.image || '';
        document.getElementById('adLink').value = ad.link_url || '';
        document.getElementById('adStartTime').value = ad.start_time ? ad.start_time.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('adEndTime').value = ad.end_time ? ad.end_time.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('adSort').value = ad.sort;
        document.getElementById('adStatus').value = ad.status;
        if (ad.image) {
            document.getElementById('previewImg').src = ad.image;
            document.getElementById('imagePreview').style.display = 'block';
        } else {
            document.getElementById('imagePreview').style.display = 'none';
        }
        document.getElementById('adModal').classList.add('show');
    }
    
    function submitForm() {
        const form = document.getElementById('adForm');
        const id = document.getElementById('adId').value;
        const formData = new FormData(form);
        formData.append('action', id ? 'update' : 'add');
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.json())
        .then(data => {
            if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
            else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    
    function toggleStatus(id, status) {
        if (!confirm('确定要' + (status ? '启用' : '禁用') + '该广告吗？')) return;
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=toggle_status&id=${id}&status=${status}` })
        .then(res => res.json())
        .then(data => {
            if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
            else showMessage('❌ ' + data.message, 'error');
        });
    }
    
    function deleteAd(id) {
        if (!confirm('确定要删除该广告吗？删除后不可恢复！')) return;
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=delete&id=${id}` })
        .then(res => res.json())
        .then(data => {
            if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
            else showMessage('❌ ' + data.message, 'error');
        });
    }
    
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
        updateBatchToolbar();
    }
    
    function updateBatchToolbar() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        const toolbar = document.getElementById('batchToolbar');
        if (checkboxes.length > 0) { toolbar.classList.add('show'); document.getElementById('selectedCount').textContent = checkboxes.length; }
        else toolbar.classList.remove('show');
    }
    
    function batchDelete() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) { showMessage('❌ 请选择要删除的广告', 'error'); return; }
        if (!confirm('确定要删除选中的 ' + checkboxes.length + ' 个广告吗？')) return;
        const ids = Array.from(checkboxes).map(cb => cb.value);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=batch_delete&ids[]=${ids.join('&ids[]=')}` })
        .then(res => res.json())
        .then(data => {
            if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
            else showMessage('❌ ' + data.message, 'error');
        });
    }
    
    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:' + (type === 'error' ? '#fff2f0;color:#ff4d4f;border:1px solid #ffccc7' : '#f6ffed;color:#52c41a;border:1px solid #b7eb8f') + ';box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
    
    document.getElementById('adModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>
