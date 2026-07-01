<?php
/**
 * 商品分类管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

// 跳过 Auth 检查（测试阶段）
// Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '商品分类管理';
$config = SystemConfig::getInstance();

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // 新增分类
    if ($_POST['action'] === 'add') {
        try {
            $name = trim($_POST['name'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $image = $_POST['image'] ?? '';
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
                exit;
            }
            
            $db->query("INSERT INTO product_categories (name, parent_id, sort, status, icon, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [$name, $parent_id, $sort, $status, $image]);
            
            echo json_encode(['success' => true, 'message' => '分类添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 切换状态
    if ($_POST['action'] === 'toggle_status') {
        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            
            $db->query("UPDATE product_categories SET status = ? WHERE id = ?", [$status, $id]);
            
            echo json_encode(['success' => true, 'message' => '状态更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // 删除分类
    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            
            // 检查自己和所有下级是否有商品
            $children = $db->fetchAll("SELECT id FROM product_categories WHERE parent_id = ?", [$id]);
            $childIds = array_column($children, 'id');
            $allIds = array_merge([$id], $childIds);
            $placeholders = implode(',', array_fill(0, count($allIds), '?'));
            
            $prods = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM products WHERE category_id IN ($placeholders)",
                $allIds
            );
            if (intval($prods['cnt']) > 0) {
                echo json_encode(['success' => false, 'message' => '该分类或下级分类中有商品在使用，无法删除']);
                exit;
            }
            
            // 无商品，级联删除
            $db->query("DELETE FROM product_categories WHERE id IN ($placeholders)", $allIds);
            
            echo json_encode(['success' => true, 'message' => (count($children) > 0 ? '分类及下级共'.count($allIds).'个已删除' : '分类删除成功')]);
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
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_map('intval', array_filter($ids));
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的分类']);
                exit;
            }
            
            // 检查选中 + 下级中是否有商品
            $ids = array_values($ids); // 重置键名
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $children = $db->fetchAll("SELECT id FROM product_categories WHERE parent_id IN ($placeholders)", $ids);
            $childIds = array_column($children, 'id');
            $allIds = array_unique(array_merge($ids, $childIds));
            $allPlaceholders = implode(',', array_fill(0, count($allIds), '?'));
            
            $prods = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM products WHERE category_id IN ($allPlaceholders)",
                $allIds
            );
            if (intval($prods['cnt']) > 0) {
                echo json_encode(['success' => false, 'message' => '选中的分类或下级分类中有商品，无法删除']);
                exit;
            }
            
            // 无商品，级联删除
            $db->query("DELETE FROM product_categories WHERE id IN ($allPlaceholders)", $allIds);
            
            echo json_encode(['success' => true, 'message' => '批量删除成功（含下级分类）']);
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
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_map('intval', array_filter($ids));
            $status = intval($_POST['status']);
            
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的分类']);
                exit;
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("UPDATE product_categories SET status = ? WHERE id IN ($placeholders)", array_merge([$status], $ids));
            
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
            
            $db->query("UPDATE product_categories SET sort = ? WHERE id = ?", [$sort, $id]);
            
            echo json_encode(['success' => true, 'message' => '排序更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 获取分类列表（分层：父级 + 子级）
$allCats = $db->fetchAll("SELECT pc.*, (SELECT COUNT(*) FROM products WHERE category_id = pc.id) as product_count FROM product_categories pc ORDER BY pc.sort ASC, pc.id ASC");

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
        /* 工具栏 */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .toolbar-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #096dd9, #1890ff);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4);
        }
        
        .btn-danger {
            background: #ff4d4f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #ff7875;
        }
        
        .btn-default {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-default:hover {
            background: #e6e6e6;
        }
        
        /* 批量操作栏 */
        .batch-toolbar {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        
        .batch-toolbar.show {
            display: flex;
        }
        
        .batch-actions {
            display: flex;
            gap: 12px;
        }
        
        /* 数据表格 */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #fafafa;
            font-weight: 600;
            color: #262626;
            font-size: 14px;
            white-space: nowrap;
        }
        
        td {
            font-size: 14px;
            color: #595959;
        }
        
        tr:hover {
            background: #fafafa;
        }
        
        /* 复选框 */
        .checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        /* 状态开关 */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 22px;
            background: #d9d9d9;
            border-radius: 11px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.on {
            background: #52c41a;
        }
        
        .toggle-switch.on::after {
            transform: translateX(22px);
        }
        
        /* 状态标签 */
        .status-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .status-tag.on {
            background: #f6ffed;
            color: #52c41a;
        }
        
        .status-tag.off {
            background: #fff2f0;
            color: #ff4d4f;
        }
        
        /* 操作按钮 */
        .action-btn {
            padding: 6px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .action-btn.delete {
            color: #ff4d4f;
        }
        
        .action-btn.delete:hover {
            background: #fff2f0;
        }
        
        .action-btn.edit {
            color: #1890ff;
        }
        
        .action-btn.edit:hover {
            background: #e6f7ff;
        }
        
        /* 排序输入框 */
        .sort-input {
            width: 60px;
            padding: 4px 8px;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            text-align: center;
            font-size: 13px;
        }
        
        .sort-input:focus {
            border-color: #1890ff;
            outline: none;
        }
        
        /* 模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 32px;
            min-width: 400px;
            max-width: 500px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: 600;
            color: #262626;
            margin-bottom: 24px;
        }
        
        .modal-body {
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            color: #262626;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #1890ff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* 图片上传器 */
        .image-uploader {
            border: 2px dashed #d9d9d9;
            border-radius: 12px;
            width: 150px;
            height: 150px;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .image-uploader:hover {
            border-color: #1890ff;
            background: #f0f5ff;
        }
        
        .image-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        
        .image-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }
        
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-item .remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-item .remove:hover {
            background: rgba(255, 77, 79, 0.9);
        }
        
        .hint {
            font-size: 12px;
            color: #8c8c8c;
            margin-top: 4px;
        }
        
        .checkbox-group {
            display: flex;
            gap: 12px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        
        .checkbox-item input[type="radio"],
        .checkbox-item input[type="checkbox"] {
            cursor: pointer;
        }
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8c8c8c;
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- 工具栏 -->
            <div class="toolbar">
                <h1 style="font-size: 24px; color: #262626; margin: 0;"><?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>新增分类</span>
                    </button>
                </div>
            </div>
            
            <!-- 批量操作栏 -->
            <div class="batch-toolbar" id="batchToolbar">
                <div class="batch-info">
                    已选择 <strong id="selectedCount">0</strong> 个分类
                </div>
                <div class="batch-actions">
                    <button class="btn btn-success" onclick="batchSetStatus(1)">
                        <span>✅</span> 批量启用
                    </button>
                    <button class="btn btn-default" onclick="batchSetStatus(0)">
                        <span>⏸️</span> 批量禁用
                    </button>
                    <button class="btn btn-danger" onclick="batchDelete()">
                        <span>🗑️</span> 批量删除
                    </button>
                </div>
            </div>
            
            <!-- 数据表格 -->
            <div class="data-table">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <div class="icon">📂</div>
                        <div>暂无分类，点击"新增分类"添加</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th width="80">分类 ID</th>
                                <th>分类名称</th>
                                <th width="80">商品数</th>
                                <th width="80">状态</th>
                                <th width="80">排序</th>
                                <th width="160">添加时间</th>
                                <th width="150">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $hasChildren = []; foreach ($childCats as $cc) { $hasChildren[intval($cc['parent_id'])] = true; } ?><?php foreach ($categories as $category): ?>
                                <?php $isChild = intval($category['parent_id']) > 0; ?><tr data-id="<?= $category['id'] ?>" class="<?= $isChild ? 'child-row-'.intval($category['parent_id']) : 'parent-row' ?>" style="<?= $isChild ? 'background:#f5f9ff;' : '' ?>">
                                    <td>
                                        <input type="checkbox" class="checkbox item-checkbox" value="<?= $category['id'] ?>" onchange="onCategoryCheckboxChange(this, <?= intval($category['parent_id']) ?>)">
                                    </td>
                                    <td>#<?= $category['id'] ?></td>
                                    <td>
                                        <?php if ($isChild): ?><span style="display:inline-block;width:24px;"></span>├ <span style="font-size:13px;color:#595959;"><?= htmlspecialchars($category['name']) ?></span><?php else: ?><span class="toggle-child" data-parent="<?= $category['id'] ?>" onclick="toggleChildCategories(<?= $category['id'] ?>)" style="cursor:pointer;user-select:none;margin-right:4px;font-size:12px;color:#8c8c8c;display:inline-block;width:16px;text-align:center;<?= isset($hasChildren[intval($category['id'])]) ? '' : 'display:none;' ?>">▼</span><strong><?= htmlspecialchars($category['name']) ?></strong><?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="background:#e6f7ff;color:#1890ff;padding:2px 10px;border-radius:10px;font-size:13px;"><?= intval($category['product_count']) ?></span>
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
                                        <button class="action-btn edit" onclick="editCategory(<?= $category['id'] ?>)">
                                            编辑
                                        </button>
                                        <button class="action-btn delete" onclick="deleteCategory(<?= $category['id'] ?>)">
                                            删除
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 新增分类模态框 -->
    <div class="modal" id="addModal">
        <div class="modal-content" style="min-width: 500px; max-width: 600px;">
            <div class="modal-header">新增分类</div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="form-group">
                        <label>分类名称 <span style="color: red">*</span></label>
                        <input type="text" name="name" required placeholder="请输入分类名称">
                    </div>
                    
                    <div class="form-group">
                        <label>上级分类</label>
                        <select name="parent_id" id="parentSelect">
                            <option value="0">顶级分类</option>
                            <?php foreach ($parentCats as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>分类图片</label>
                        <div class="image-uploader" onclick="document.getElementById('categoryImageInput').click()" style="width: 150px; height: 150px; cursor: pointer;">
                            <div style="font-size: 32px; margin-bottom: 8px;">🖼️</div>
                            <div style="font-size: 12px; font-weight: 500; color: #262626;">点击上传图片</div>
                            <div style="font-size: 10px; color: #8c8c8c; margin-top: 4px;">JPG/PNG 5MB</div>
                        </div>
                        <input type="file" id="categoryImageInput" accept="image/*" style="display: none;" onchange="handleCategoryImageUpload(event)">
                        <div class="image-list" id="categoryImageList" style="margin-top: 12px;"></div>
                        <input type="hidden" name="image" id="categoryImage" value="">
                    </div>
                    
                    <div class="form-group">
                        <label>状态 <span style="color: red">*</span></label>
                        <div class="checkbox-group" style="display: flex; gap: 20px;">
                            <label class="checkbox-item" style="display: flex; align-items: center; gap: 6px;">
                                <input type="radio" name="status" value="1" checked>
                                <span>显示</span>
                            </label>
                            <label class="checkbox-item" style="display: flex; align-items: center; gap: 6px;">
                                <input type="radio" name="status" value="0">
                                <span>隐藏</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" name="sort" value="<?= count($categories) + 1 ?>" placeholder="数字越小越靠前">
                        <div class="hint">根据现有数量自动计算 +1，当前值：<?= count($categories) + 1 ?></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeAddModal()">取消</button>
                <button class="btn btn-primary" onclick="submitAdd()">确定</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        // 子分类折叠切换
        function toggleChildCategories(parentId) {
            const rows = document.querySelectorAll('.child-row-' + parentId);
            const btn = document.querySelector('.toggle-child[data-parent="' + parentId + '"]');
            if (!rows.length || !btn) return;
            const isHidden = rows[0].style.display === 'none';
            rows.forEach(function(r) { r.style.display = isHidden ? '' : 'none'; });
            btn.textContent = isHidden ? '▼' : '▶';
        }
    // 分类图片上传处理
    let categoryImage = '';
    
    function handleCategoryImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // 验证文件大小
        if (file.size > 5 * 1024 * 1024) {
            alert('图片大小不能超过 5MB');
            return;
        }
        
        // 验证图片格式
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('请上传 JPG、PNG、GIF 或 WebP 格式的图片');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            categoryImage = e.target.result;
            document.getElementById('categoryImage').value = categoryImage;
            document.getElementById('categoryImageList').innerHTML = `
                <div class="image-item">
                    <img src="${categoryImage}">
                    <button type="button" class="remove" onclick="removeCategoryImage()">×</button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
    
    function removeCategoryImage() {
        categoryImage = '';
        document.getElementById('categoryImage').value = '';
        document.getElementById('categoryImageList').innerHTML = '';
        document.getElementById('categoryImageInput').value = '';
    }
    
    // 显示新增模态框
    function showAddModal() {
        document.getElementById('addModal').classList.add('show');
        document.querySelector('#addForm input[name="name"]').focus();
    }
    
    // 关闭新增模态框
    function closeAddModal() {
        document.getElementById('addModal').classList.remove('show');
        document.getElementById('addForm').reset();
        categoryImage = '';
        document.getElementById('categoryImageList').innerHTML = '';
    }
    
    // 提交新增
    function submitAdd() {
        const form = document.getElementById('addForm');
        const formData = new FormData(form);
        formData.append('action', 'add');
        formData.append('image', document.getElementById('categoryImage').value);
        
        fetch('', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 切换状态
    function toggleStatus(id, status) {
        if (!confirm('确定要' + (status ? '启用' : '禁用') + '该分类吗？')) return;
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=toggle_status&id=${id}&status=${status}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 更新排序
    function updateSort(id, sort) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update_sort&id=${id}&sort=${sort}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 删除分类
    function deleteCategory(id) {
        if (!confirm('确定要删除该分类吗？删除后不可恢复！')) return;
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 全选/取消全选
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
        // 父级勾上时子级也要同步
        document.querySelectorAll('.parent-row .item-checkbox:checked').forEach(function(cb) {
            const catId = cb.value;
            document.querySelectorAll('.child-row-' + catId + ' .item-checkbox').forEach(function(c) { c.checked = true; });
        });
        updateBatchToolbar();
    }
    
    // 分类复选框变化：父级选则子级全选
    function onCategoryCheckboxChange(checkbox, parentId) {
        const row = checkbox.closest('tr');
        // 父级行：同步所有子级
        if (row && row.classList.contains('parent-row')) {
            const catId = checkbox.value;
            const children = document.querySelectorAll('.child-row-' + catId + ' .item-checkbox');
            children.forEach(function(cb) { cb.checked = checkbox.checked; });
        }
        updateBatchToolbar();
    }
    
    // 更新批量操作栏
    function updateBatchToolbar() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        const toolbar = document.getElementById('batchToolbar');
        const count = document.getElementById('selectedCount');
        
        if (checkboxes.length > 0) {
            toolbar.classList.add('show');
            count.textContent = checkboxes.length;
        } else {
            toolbar.classList.remove('show');
        }
    }
    
    // 批量设置状态
    function batchSetStatus(status) {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) {
            showMessage('❌ 请选择要操作的分类', 'error');
            return;
        }
        
        const ids = Array.from(checkboxes).map(cb => cb.value);
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=batch_status&ids=${ids}&status=${status}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 批量删除
    function batchDelete() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) {
            showMessage('❌ 请选择要删除的分类', 'error');
            return;
        }
        
        if (!confirm('确定要删除选中的 ' + checkboxes.length + ' 个分类吗？删除后不可恢复！')) return;
        
        const ids = Array.from(checkboxes).map(cb => cb.value);
        
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=batch_delete&ids=${ids}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => {
            showMessage('❌ 请求失败：' + err.message, 'error');
        });
    }
    
    // 编辑分类（暂未实现）
    function editCategory(id) {
        showMessage('⚠️ 编辑功能开发中...', 'info');
    }
    
    // 显示消息
    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:#f6ffed;color:#237804;border:1px solid #b7eb8f;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        if (type === 'error') {
            alert.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:#fff2f0;color:#ff4d4f;border:1px solid #ffccc7;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        }
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
    
    // 模态框点击外部关闭
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) closeAddModal();
    });
    </script>
</body>
</html>
