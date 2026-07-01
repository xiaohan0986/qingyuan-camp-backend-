<?php
/**
 * 商品标签管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '商品标签管理';
$config = SystemConfig::getInstance();

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // 新增
    if ($_POST['action'] === 'add') {
        try {
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#1890ff';
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '标签名称不能为空']);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("INSERT INTO product_tags (name, color, sort, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $color, $sort, $status]);
            
            echo json_encode(['success' => true, 'message' => '标签添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 编辑
    if ($_POST['action'] === 'edit') {
        try {
            $id = intval($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $color = $_POST['color'] ?? '#1890ff';
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '标签名称不能为空']);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("UPDATE product_tags SET name = ?, color = ?, sort = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $color, $sort, $status, $id]);
            
            echo json_encode(['success' => true, 'message' => '标签更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 切换状态
    if ($_POST['action'] === 'toggle_status') {
        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            
            $stmt = $db->getConnection()->prepare("UPDATE product_tags SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            echo json_encode(['success' => true, 'message' => '状态更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 删除
    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            
            $stmt = $db->getConnection()->prepare("DELETE FROM product_tags WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => '标签删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 批量删除
    if ($_POST['action'] === 'batch_delete') {
        try {
            $ids = $_POST['ids'] ?? [];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的标签']);
                exit;
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->getConnection()->prepare("DELETE FROM product_tags WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 批量设置状态
    if ($_POST['action'] === 'batch_status') {
        try {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status']);
            
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的标签']);
                exit;
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->getConnection()->prepare("UPDATE product_tags SET status = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));
            
            echo json_encode(['success' => true, 'message' => '批量操作成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 更新排序
    if ($_POST['action'] === 'update_sort') {
        try {
            $id = intval($_POST['id']);
            $sort = intval($_POST['sort']);
            
            $stmt = $db->getConnection()->prepare("UPDATE product_tags SET sort = ? WHERE id = ?");
            $stmt->execute([$sort, $id]);
            
            echo json_encode(['success' => true, 'message' => '排序更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 取一条（编辑表单填充用）
    if ($_POST['action'] === 'get') {
        try {
            $id = intval($_POST['id']);
            $tag = $db->fetchOne("SELECT * FROM product_tags WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => $tag]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '获取失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 获取标签列表
$tags = $db->fetchAll("SELECT * FROM product_tags ORDER BY sort ASC, id DESC");
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
            border-radius: 11px; cursor: pointer; transition: background 0.3s;
        }
        .toggle-switch::after {
            content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px;
            background: white; border-radius: 50%; transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch.on { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .toggle-switch.on::after { transform: translateX(22px); }
        .tag-badge {
            display: inline-block; padding: 4px 14px; border-radius: 16px; font-size: 13px;
            color: white; font-weight: 500;
        }
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
            background: white; border-radius: 12px; padding: 32px; min-width: 440px; max-width: 520px;
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
        .color-presets { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .color-preset {
            width: 28px; height: 28px; border-radius: 50%; cursor: pointer;
            border: 2px solid transparent; transition: all 0.2s;
        }
        .color-preset:hover, .color-preset.active { border-color: #262626; transform: scale(1.1); }
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
                <h1>🏷️ <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>➕</span> 新增标签
                    </button>
                </div>
            </div>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 个标签</div>
                <div class="batch-actions">
                    <button class="btn btn-success" onclick="batchSetStatus(1)">✅ 批量启用</button>
                    <button class="btn btn-default" onclick="batchSetStatus(0)">⏸️ 批量禁用</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($tags)): ?>
                    <div class="empty-state">
                        <div class="icon">🏷️</div>
                        <h3>暂无标签</h3>
                        <p>点击"新增标签"创建第一个商品标签</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th width="80">ID</th>
                                <th>标签名称</th>
                                <th width="120">颜色预览</th>
                                <th width="100">状态</th>
                                <th width="100">排序</th>
                                <th width="160">添加时间</th>
                                <th width="160">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tags as $tag): ?>
                                <tr data-id="<?= $tag['id'] ?>">
                                    <td><input type="checkbox" class="checkbox item-checkbox" value="<?= $tag['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>#<?= $tag['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($tag['name']) ?></strong></td>
                                    <td>
                                        <span class="tag-badge" style="background: <?= htmlspecialchars($tag['color']) ?>">
                                            <?= htmlspecialchars($tag['name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="toggle-switch <?= $tag['status'] ? 'on' : '' ?>"
                                             onclick="toggleStatus(<?= $tag['id'] ?>, <?= $tag['status'] ? 0 : 1 ?>)"
                                             title="<?= $tag['status'] ? '点击禁用' : '点击启用' ?>"></div>
                                    </td>
                                    <td>
                                        <input type="number" class="sort-input" value="<?= $tag['sort'] ?>"
                                               onchange="updateSort(<?= $tag['id'] ?>, this.value)">
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($tag['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn edit" onclick="editTag(<?= $tag['id'] ?>)">编辑</button>
                                        <button class="action-btn delete" onclick="deleteTag(<?= $tag['id'] ?>)">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 新增/编辑模态框 -->
    <div class="modal" id="tagModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">新增标签</div>
            <div class="modal-body">
                <form id="tagForm">
                    <input type="hidden" name="id" id="tagId" value="">
                    <div class="form-group">
                        <label>标签名称 <span class="required">*</span></label>
                        <input type="text" name="name" id="tagName" required maxlength="50" placeholder="例如：新品、热销、推荐">
                    </div>
                    <div class="form-group">
                        <label>标签颜色</label>
                        <input type="color" name="color" id="tagColor" value="#1890ff" style="height: 40px; padding: 4px;">
                        <div class="color-presets" id="colorPresets">
                            <div class="color-preset" style="background:#1890ff" data-color="#1890ff"></div>
                            <div class="color-preset" style="background:#40a9ff" data-color="#40a9ff"></div>
                            <div class="color-preset" style="background:#ff4757" data-color="#ff4757"></div>
                            <div class="color-preset" style="background:#ffa502" data-color="#ffa502"></div>
                            <div class="color-preset" style="background:#26de81" data-color="#26de81"></div>
                            <div class="color-preset" style="background:#2bcbba" data-color="#2bcbba"></div>
                            <div class="color-preset" style="background:#3742fa" data-color="#3742fa"></div>
                            <div class="color-preset" style="background:#a55eea" data-color="#a55eea"></div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>排序</label>
                            <input type="number" name="sort" id="tagSort" value="0">
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
                <button class="btn btn-primary" onclick="submitForm()">确定</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        let currentMode = 'add';
        
        // 颜色预设点击
        document.querySelectorAll('.color-preset').forEach(el => {
            el.addEventListener('click', function() {
                document.getElementById('tagColor').value = this.dataset.color;
                document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        function showAddModal() {
            currentMode = 'add';
            document.getElementById('modalTitle').textContent = '新增标签';
            document.getElementById('tagForm').reset();
            document.getElementById('tagId').value = '';
            document.getElementById('tagColor').value = '#1890ff';
            document.getElementById('tagSort').value = '<?= count($tags) + 1 ?>';
            document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
            document.getElementById('tagModal').classList.add('show');
            setTimeout(() => document.getElementById('tagName').focus(), 100);
        }
        
        function closeModal() {
            document.getElementById('tagModal').classList.remove('show');
        }
        
        function editTag(id) {
            currentMode = 'edit';
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const t = data.data;
                    document.getElementById('modalTitle').textContent = '编辑标签';
                    document.getElementById('tagId').value = t.id;
                    document.getElementById('tagName').value = t.name;
                    document.getElementById('tagColor').value = t.color;
                    document.getElementById('tagSort').value = t.sort;
                    document.querySelector(`input[name="status"][value="${t.status}"]`).checked = true;
                    document.getElementById('tagModal').classList.add('show');
                } else {
                    showMessage('❌ 获取失败', 'error');
                }
            });
        }
        
        function submitForm() {
            const name = document.getElementById('tagName').value.trim();
            if (!name) { showMessage('❌ 请输入标签名称', 'error'); return; }
            
            const fd = new FormData(document.getElementById('tagForm'));
            fd.append('action', currentMode);
            fd.set('color', document.getElementById('tagColor').value);
            
            fetch('', { method: 'POST', body: new URLSearchParams(fd) })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            })
            .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
        }
        
        function toggleStatus(id, status) {
            if (!confirm('确定要' + (status ? '启用' : '禁用') + '该标签吗？')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_status&id=${id}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
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
        
        function deleteTag(id) {
            if (!confirm('确定要删除该标签吗？删除后不可恢复！')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
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
            } else {
                tb.classList.remove('show');
            }
        }
        
        function batchSetStatus(status) {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            if (cbs.length === 0) { showMessage('❌ 请先选择标签', 'error'); return; }
            const ids = Array.from(cbs).map(cb => cb.value);
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=batch_status&ids=${ids.join(',')}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            });
        }
        
        function batchDelete() {
            const cbs = document.querySelectorAll('.item-checkbox:checked');
            if (cbs.length === 0) { showMessage('❌ 请先选择标签', 'error'); return; }
            if (!confirm('确定要删除选中的 ' + cbs.length + ' 个标签吗？')) return;
            const ids = Array.from(cbs).map(cb => cb.value);
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=batch_delete&ids=${ids.join(',')}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            });
        }
        
        function showMessage(msg, type) {
            const alert = document.createElement('div');
            alert.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:#f6ffed;color:#237804;border:1px solid #b7eb8f;box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:14px;';
            if (type === 'error') {
                alert.style.cssText = alert.style.cssText.replace('#f6ffed,#237804,#b7eb8f', '#fff2f0,#ff4d4f,#ffccc7');
            }
            alert.textContent = msg;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
        
        document.getElementById('tagModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
