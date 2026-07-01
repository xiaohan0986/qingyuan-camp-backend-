<?php
/**
 * 文章分类管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '文章分类管理';
$config = SystemConfig::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        try {
            $name = trim($_POST['name'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("INSERT INTO article_categories (name, parent_id, sort, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('siii', $name, $parent_id, $sort, $status);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => '分类添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'edit') {
        try {
            $id = intval($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
                exit;
            }
            
            // 防止将分类的父级设为自身或子级
            if ($parent_id == $id) {
                echo json_encode(['success' => false, 'message' => '不能将自身设为上级分类']);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("UPDATE article_categories SET name = ?, parent_id = ?, sort = ?, status = ? WHERE id = ?");
            $stmt->bind_param('siiis', $name, $parent_id, $sort, $status, $id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => '分类更新成功']);
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
            $stmt = $db->getConnection()->prepare("UPDATE article_categories SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
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
            // 检查是否有子分类
            $subCount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM article_categories WHERE parent_id = ?", [$id]);
            if ($subCount['cnt'] > 0) {
                echo json_encode(['success' => false, 'message' => '该分类下存在子分类，请先删除子分类']);
                exit;
            }
            // 检查是否有文章
            $artCount = $db->fetchOne("SELECT COUNT(*) AS cnt FROM articles WHERE category_id = ?", [$id]);
            if ($artCount['cnt'] > 0) {
                echo json_encode(['success' => false, 'message' => '该分类下有 ' . $artCount['cnt'] . ' 篇文章，无法删除']);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("DELETE FROM article_categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '分类删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'batch_delete') {
        try {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的分类']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->getConnection()->prepare("DELETE FROM article_categories WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'batch_status') {
        try {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status']);
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => '请选择要操作的分类']); exit; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->getConnection()->prepare("UPDATE article_categories SET status = ? WHERE id IN ($placeholders)");
            array_unshift($ids, $status);
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '批量操作成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'update_sort') {
        try {
            $id = intval($_POST['id']);
            $sort = intval($_POST['sort']);
            $stmt = $db->getConnection()->prepare("UPDATE article_categories SET sort = ? WHERE id = ?");
            $stmt->bind_param('ii', $sort, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '排序更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'get') {
        try {
            $id = intval($_POST['id']);
            $cat = $db->fetchOne("SELECT * FROM article_categories WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => $cat]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '获取失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

$allCats = $db->fetchAll("SELECT ac.*, (SELECT COUNT(*) FROM articles WHERE category_id = ac.id) as article_count FROM article_categories ac ORDER BY ac.sort ASC, ac.id ASC");

// 按 parent_id 分组
$parentCats = [];
$childCats = [];
foreach ($allCats as $c) {
    if (intval($c['parent_id']) === 0) {
        $parentCats[] = $c;
    } else {
        $childCats[] = $c;
    }
}

// 构建分层数组：每个父级后面跟它的子级
$categories = [];
foreach ($parentCats as $pc) {
    $categories[] = $pc;
    foreach ($childCats as $cc) {
        if (intval($cc['parent_id']) === intval($pc['id'])) {
            $categories[] = $cc;
        }
    }
}
// 添加没有父级的孤儿子节点
foreach ($childCats as $cc) {
    $found = false;
    foreach ($parentCats as $pc) {
        if (intval($cc['parent_id']) === intval($pc['id'])) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $categories[] = $cc;
    }
}
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
        .batch-toolbar {
            background: linear-gradient(135deg, rgba(24,144,255,0.08), rgba(64,169,255,0.08));
            border: 1px solid rgba(24,144,255,0.3); border-radius: 8px; padding: 12px 16px;
            margin-bottom: 20px; display: none; align-items: center; justify-content: space-between;
        }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
        .data-table {
            background: white; border-radius: 12px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .checkbox { width: 18px; height: 18px; cursor: pointer; }
        .toggle-switch {
            position: relative; width: 44px; height: 22px; background: #d9d9d9;
            border-radius: 11px; cursor: pointer; transition: background 0.3s; display: inline-block; vertical-align: middle;
        }
        .toggle-switch::after {
            content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px;
            background: white; border-radius: 50%; transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch.on { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .toggle-switch.on::after { transform: translateX(22px); }
        .cat-name { font-weight: 500; color: #262626; }
        .cat-name.level-1 { padding-left: 0; }
        .cat-name.level-2 { padding-left: 24px; color: #595959; }
        .cat-name.level-3 { padding-left: 48px; color: #8c8c8c; font-size: 13px; }
        .cat-name::before {
            content: '📁'; margin-right: 6px; opacity: 0.6;
        }
        .cat-name.level-1::before { content: '📂'; opacity: 1; }
        .action-btn {
            padding: 6px 12px; border: none; background: transparent; cursor: pointer;
            border-radius: 4px; font-size: 13px; transition: all 0.3s;
        }
        .action-btn.edit { color: #1890ff; }
        .action-btn.edit:hover { background: rgba(24,144,255,0.1); }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .sort-input {
            width: 70px; padding: 4px 8px; border: 1px solid #d9d9d9; border-radius: 4px;
            text-align: center; font-size: 13px;
        }
        .sort-input:focus { border-color: #1890ff; outline: none; }
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white; border-radius: 12px; padding: 32px; min-width: 480px; max-width: 560px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        }
        .modal-header {
            font-size: 20px; font-weight: 600; color: #262626; margin-bottom: 24px;
            padding-bottom: 16px; border-bottom: 1px solid #f0f0f0;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 14px; color: #262626; margin-bottom: 8px; font-weight: 500;
        }
        .form-group label .required { color: #ff4d4f; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 12px; border: 1px solid #d9d9d9; border-radius: 8px;
            font-size: 14px; box-sizing: border-box; transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1);
        }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
        .checkbox-group { display: flex; gap: 20px; }
        .checkbox-item { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .empty-state { text-align: center; padding: 80px 20px; color: #8c8c8c; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { color: #595959; margin-bottom: 8px; }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="toolbar">
                <h1>📂 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>➕</span> 新增分类
                    </button>
                </div>
            </div>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 个分类</div>
                <div class="batch-actions">
                    <button class="btn btn-success" onclick="batchSetStatus(1)">✅ 批量启用</button>
                    <button class="btn btn-default" onclick="batchSetStatus(0)">⏸️ 批量禁用</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <div class="icon">📂</div>
                        <h3>暂无分类</h3>
                        <p>点击"新增分类"创建第一个文章分类</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th width="80">ID</th>
                                <th>分类名称</th>
                                <th width="80">文章数</th>
                                <th width="100">状态</th>
                                <th width="100">排序</th>
                                <th width="160">添加时间</th>
                                <th width="160">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $hasChildren = []; foreach ($childCats as $cc) { $hasChildren[intval($cc['parent_id'])] = true; } ?>
                            <?php foreach ($categories as $category): ?>
                                <?php $isChild = intval($category['parent_id']) > 0; ?>
                                <tr data-id="<?= $category['id'] ?>" class="<?= $isChild ? 'child-row-'.intval($category['parent_id']) : 'parent-row' ?>" style="<?= $isChild ? 'background:#f5f9ff;' : '' ?>">
                                    <td><input type="checkbox" class="checkbox item-checkbox" value="<?= $category['id'] ?>" onchange="onCategoryCheckboxChange(this)"></td>
                                    <td>#<?= $category['id'] ?></td>
                                    <td>
                                        <?php if ($isChild): ?>
                                            <span style="display:inline-block;width:24px;"></span>├ <span style="font-size:13px;color:#595959;"><?= htmlspecialchars($category['name']) ?></span>
                                        <?php else: ?>
                                            <span class="toggle-child" data-parent="<?= $category['id'] ?>" onclick="toggleChildCategories(<?= $category['id'] ?>)" style="cursor:pointer;user-select:none;margin-right:4px;font-size:12px;color:#8c8c8c;display:inline-block;width:16px;text-align:center;<?= isset($hasChildren[intval($category['id'])]) ? '' : 'display:none;' ?>">▼</span>
                                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="background:#e6f7ff;color:#1890ff;padding:2px 10px;border-radius:10px;font-size:13px;"><?= intval($category['article_count']) ?></span>
                                    </td>
                                    <td>
                                        <div class="toggle-switch <?= $category['status'] ? 'on' : '' ?>"
                                             onclick="toggleStatus(<?= $category['id'] ?>, <?= $category['status'] ? 0 : 1 ?>)"
                                             title="<?= $category['status'] ? '点击禁用' : '点击启用' ?>"></div>
                                    </td>
                                    <td>
                                        <input type="number" class="sort-input" value="<?= $category['sort'] ?>"
                                               onchange="updateSort(<?= $category['id'] ?>, this.value)">
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($category['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn edit" onclick="editCategory(<?= $category['id'] ?>)">编辑</button>
                                        <button class="action-btn delete" onclick="deleteCategory(<?= $category['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="modal" id="catModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">新增分类</div>
            <div class="modal-body">
                <form id="catForm">
                    <input type="hidden" name="id" id="catId" value="">
                    <div class="form-group">
                        <label>分类名称 <span class="required">*</span></label>
                        <input type="text" name="name" id="catName" required maxlength="50" placeholder="例如：营地资讯">
                    </div>
                    <div class="form-group">
                        <label>上级分类</label>
                        <select name="parent_id" id="catParent">
                            <option value="0">顶级分类</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">仅支持两级分类</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>排序</label>
                            <input type="number" name="sort" id="catSort" value="0">
                            <div class="hint">数字越小越靠前</div>
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="status" value="1" checked> <span>启用</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="status" value="0"> <span>禁用</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">确定</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        let currentMode = 'add';
        
        function showAddModal() {
            currentMode = 'add';
            document.getElementById('modalTitle').textContent = '新增分类';
            document.getElementById('catForm').reset();
            document.getElementById('catId').value = '';
            document.getElementById('catSort').value = '<?= count($categories) + 1 ?>';
            document.getElementById('catModal').classList.add('show');
            setTimeout(() => document.getElementById('catName').focus(), 100);
        }
        
        function closeModal() {
            document.getElementById('catModal').classList.remove('show');
        }
        
        function editCategory(id) {
            currentMode = 'edit';
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const c = data.data;
                    document.getElementById('modalTitle').textContent = '编辑分类';
                    document.getElementById('catId').value = c.id;
                    document.getElementById('catName').value = c.name;
                    document.getElementById('catParent').value = c.parent_id;
                    document.getElementById('catSort').value = c.sort;
                    document.querySelector(`input[name="status"][value="${c.status}"]`).checked = true;
                    document.getElementById('catModal').classList.add('show');
                }
            });
        }
        
        function submitForm() {
            const name = document.getElementById('catName').value.trim();
            if (!name) { showMessage('❌ 请输入分类名称', 'error'); return; }
            
            const fd = new FormData(document.getElementById('catForm'));
            fd.append('action', currentMode);
            
            fetch('', { method: 'POST', body: new URLSearchParams(fd) })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 800); }
                else showMessage('❌ ' + data.message, 'error');
            })
            .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
        }
        
        function toggleStatus(id, status) {
            if (!confirm('确定要' + (status ? '启用' : '禁用') + '该分类吗？')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_status&id=${id}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function updateSort(id, sort) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update_sort&id=${id}&sort=${sort}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) showMessage('✅ ' + data.message, 'success');
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function deleteCategory(id) {
            if (!confirm('确定要删除该分类吗？删除后不可恢复！')) return;
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
        
        function toggleChildCategories(parentId) {
            const rows = document.querySelectorAll('.child-row-' + parentId);
            const btn = document.querySelector('.toggle-child[data-parent="' + parentId + '"]');
            if (!rows.length || !btn) return;
            const isHidden = rows[0].style.display === 'none';
            rows.forEach(function(r) { r.style.display = isHidden ? '' : 'none'; });
            btn.textContent = isHidden ? '▼' : '▶';
        }
        
        function toggleSelectAll() {
            const sa = document.getElementById('selectAll');
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = sa.checked);
            // 父级勾上时子级也要同步
            document.querySelectorAll('.parent-row .item-checkbox:checked').forEach(function(cb) {
                const catId = cb.value;
                document.querySelectorAll('.child-row-' + catId + ' .item-checkbox').forEach(function(c) { c.checked = true; });
            });
            updateBatchToolbar();
        }
        
        function onCategoryCheckboxChange(checkbox) {
            var row = checkbox.closest('tr');
            if (row && row.classList.contains('parent-row')) {
                var catId = checkbox.value;
                document.querySelectorAll('.child-row-' + catId + ' .item-checkbox').forEach(function(cb) { cb.checked = checkbox.checked; });
            }
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
        
        function batchSetStatus(status) {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            if (cbs.length === 0) { showMessage('❌ 请先选择分类', 'error'); return; }
            const ids = Array.from(cbs).map(cb => cb.value);
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=batch_status&ids=${ids.join(',')}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function batchDelete() {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            if (cbs.length === 0) { showMessage('❌ 请先选择分类', 'error'); return; }
            if (!confirm('确定要删除选中的 ' + cbs.length + ' 个分类吗？')) return;
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
        
        document.getElementById('catModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
