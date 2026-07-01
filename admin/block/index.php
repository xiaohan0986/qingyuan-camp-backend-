<?php
/**
 * 回收/二手板块管理
 * 表: block_items, category='recycle'
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '回收/二手管理';
$category = 'recycle';

// 状态映射
$statusMap = [
    0 => ['label' => '待审核', 'class' => 'badge-warning'],
    1 => ['label' => '已通过', 'class' => 'badge-success'],
    2 => ['label' => '已拒绝', 'class' => 'badge-danger'],
];

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        try {
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            $images = $_POST['images'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $contact = trim($_POST['contact'] ?? '');
            $user_name = trim($_POST['user_name'] ?? '');
            $user_phone = trim($_POST['user_phone'] ?? '');
            $status = intval($_POST['status'] ?? 0);
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => '标题不能为空']);
                exit;
            }
            
            $stmt = $db->query("INSERT INTO block_items (title, content, images, price, contact, user_name, user_phone, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())", [$title, $content, $images, $price, $contact, $user_name, $user_phone, $category, $status]);
            
            echo json_encode(['success' => true, 'message' => '添加成功', 'id' => $db->getConnection()->insert_id]);
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
            $content = $_POST['content'] ?? '';
            $images = $_POST['images'] ?? '';
            $price = floatval($_POST['price'] ?? 0);
            $contact = trim($_POST['contact'] ?? '');
            $user_name = trim($_POST['user_name'] ?? '');
            $user_phone = trim($_POST['user_phone'] ?? '');
            $status = intval($_POST['status'] ?? 0);
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => '标题不能为空']);
                exit;
            }
            
            $stmt = $db->query("UPDATE block_items SET title=?, content=?, images=?, price=?, contact=?, user_name=?, user_phone=?, status=? WHERE id=? AND category=?", [$title, $content, $images, $price, $contact, $user_name, $user_phone, $status, $id, $category]);
            
            echo json_encode(['success' => true, 'message' => '更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'audit') {
        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            
            if (!in_array($status, [0, 1, 2])) {
                echo json_encode(['success' => false, 'message' => '无效的状态']);
                exit;
            }
            
            $stmt = $db->query("UPDATE block_items SET status = ? WHERE id = ? AND category = ?", [$status, $id, $category]);
            
            echo json_encode(['success' => true, 'message' => '审核状态已更新']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete') {
        try {
            $id = intval($_POST['id']);
            $stmt = $db->query("DELETE FROM block_items WHERE id = ? AND category = ?", [$id, $category]);
            echo json_encode(['success' => true, 'message' => '删除成功']);
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
                echo json_encode(['success' => false, 'message' => '请选择要删除的项']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->query("DELETE FROM block_items WHERE id IN ($placeholders) AND category = ?", array_merge($ids, [$category]));
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'batch_audit') {
        try {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status']);
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的项']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->query("UPDATE block_items SET status = ? WHERE id IN ($placeholders) AND category = ?", array_merge([$status], $ids, [$category]));
            echo json_encode(['success' => true, 'message' => '批量审核成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

// 获取搜索参数
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where = ['category = ?'];
$params = [$category];

if ($search !== '') {
    $where[] = '(title LIKE ? OR content LIKE ? OR user_name LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = intval($statusFilter);
}

$whereSql = implode(' AND ', $where);
$sql = "SELECT * FROM block_items WHERE {$whereSql} ORDER BY id DESC LIMIT 200";
$items = $db->fetchAll($sql, $params);

// 统计
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];
$statRows = $db->fetchAll("SELECT status, COUNT(*) as cnt FROM block_items WHERE category = ? GROUP BY status", [$category]);
foreach ($statRows as $r) {
    $stats['total'] += $r['cnt'];
    if ($r['status'] == 0) $stats['pending'] = $r['cnt'];
    if ($r['status'] == 1) $stats['approved'] = $r['cnt'];
    if ($r['status'] == 2) $stats['rejected'] = $r['cnt'];
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .toolbar-actions { display: flex; gap: 12px; }
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-danger { background: #ff4d4f; color: white; }
        .btn-danger:hover { background: #ff7875; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-default:hover { background: #e6e6e6; }
        .btn-success { background: #52c41a; color: white; }
        .btn-success:hover { background: #73d13d; }
        .btn-warning { background: #faad14; color: white; }
        .btn-warning:hover { background: #ffc53d; }
        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-bar input,
        .search-bar select {
            padding: 8px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }
        .search-bar input:focus,
        .search-bar select:focus {
            border-color: #1890ff;
            outline: none;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 4px solid #1890ff;
        }
        .stat-card .num {
            font-size: 28px;
            font-weight: 600;
            color: #262626;
        }
        .stat-card .label {
            font-size: 13px;
            color: #8c8c8c;
            margin-top: 4px;
        }
        .stat-card.pending { border-left-color: #faad14; }
        .stat-card.approved { border-left-color: #52c41a; }
        .stat-card.rejected { border-left-color: #ff4d4f; }
        .batch-toolbar {
            background: linear-gradient(135deg, #e6e7ff, #f3e7ff);
            border: 1px solid #b3b7ff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        table { width: 100%; border-collapse: collapse; }
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
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .checkbox { width: 18px; height: 18px; cursor: pointer; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: #f6ffed; color: #52c41a; }
        .badge-danger { background: #fff2f0; color: #ff4d4f; }
        .badge-warning { background: #fffbe6; color: #faad14; }
        .badge-primary { background: #f0f0ff; color: #1890ff; }
        .action-btn {
            padding: 6px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
        }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .action-btn.edit { color: #1890ff; }
        .action-btn.edit:hover { background: #f0f0ff; }
        .action-btn.view { color: #13c2c2; }
        .action-btn.view:hover { background: #e6fffb; }
        .action-btn.audit { color: #52c41a; }
        .action-btn.audit:hover { background: #f6ffed; }
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
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 32px;
            min-width: 600px;
            max-width: 760px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 20px;
            font-weight: 600;
            color: #262626;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 14px;
            color: #262626;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1890ff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        .image-uploader {
            border: 2px dashed #d9d9d9;
            border-radius: 12px;
            padding: 20px;
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
            background: #f5f7ff;
        }
        .image-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 8px;
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
        .image-item img { width: 100%; height: 100%; object-fit: cover; }
        .image-item .remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8c8c8c;
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .thumb-cell {
            display: flex;
            gap: 4px;
        }
        .thumb-cell img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #f0f0f0;
        }
        .price-cell {
            color: #ff4d4f;
            font-weight: 600;
        }
        .content-cell {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #8c8c8c;
            font-size: 12px;
        }
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
                        <span>➕</span> 发布二手
                    </button>
                </div>
            </div>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="num"><?= $stats['total'] ?></div>
                    <div class="label">📊 全部数量</div>
                </div>
                <div class="stat-card pending">
                    <div class="num"><?= $stats['pending'] ?></div>
                    <div class="label">⏳ 待审核</div>
                </div>
                <div class="stat-card approved">
                    <div class="num"><?= $stats['approved'] ?></div>
                    <div class="label">✅ 已通过</div>
                </div>
                <div class="stat-card rejected">
                    <div class="num"><?= $stats['rejected'] ?></div>
                    <div class="label">❌ 已拒绝</div>
                </div>
            </div>
            
            <form class="search-bar" method="get">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 标题/内容/发布人">
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>待审核</option>
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>已通过</option>
                    <option value="2" <?= $statusFilter === '2' ? 'selected' : '' ?>>已拒绝</option>
                </select>
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="index.php" class="btn btn-default">重置</a>
            </form>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div class="batch-info">
                    已选择 <strong id="selectedCount">0</strong> 项
                </div>
                <div class="batch-actions">
                    <button class="btn btn-success" onclick="batchAudit(1)">✅ 批量通过</button>
                    <button class="btn btn-warning" onclick="batchAudit(2)">❌ 批量拒绝</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <div class="icon">♻️</div>
                        <div>暂无二手信息</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                <th width="80">ID</th>
                                <th width="120">图片</th>
                                <th>标题</th>
                                <th width="80">价格</th>
                                <th width="140">发布人</th>
                                <th width="100">状态</th>
                                <th width="140">发布时间</th>
                                <th width="220">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr data-id="<?= $item['id'] ?>">
                                    <td><input type="checkbox" class="checkbox item-checkbox" value="<?= $item['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>#<?= $item['id'] ?></td>
                                    <td>
                                        <div class="thumb-cell">
                                            <?php
                                                $imgs = array_filter(explode(',', $item['images'] ?? ''));
                                                $imgs = array_slice($imgs, 0, 2);
                                                if (empty($imgs)) {
                                                    echo '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;font-size:11px;">无图</div>';
                                                } else {
                                                    foreach ($imgs as $img) {
                                                        echo '<img src="' . htmlspecialchars($img) . '" alt="缩略图">';
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                                        <div class="content-cell"><?= htmlspecialchars($item['content'] ?? '') ?></div>
                                    </td>
                                    <td class="price-cell">¥<?= number_format($item['price'] ?? 0, 2) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($item['user_name'] ?? '匿名') ?></div>
                                        <div style="font-size:12px;color:#999;"><?= htmlspecialchars($item['user_phone'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusMap[$item['status']]['class'] ?? 'badge-warning' ?>">
                                            <?= $statusMap[$item['status']]['label'] ?? '未知' ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px;"><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn view" onclick="viewItem(<?= $item['id'] ?>)">详情</button>
                                        <?php if ($item['status'] == 0): ?>
                                            <button class="action-btn audit" onclick="auditItem(<?= $item['id'] ?>, 1)">✅ 通过</button>
                                            <button class="action-btn delete" onclick="auditItem(<?= $item['id'] ?>, 2)">❌ 拒绝</button>
                                        <?php endif; ?>
                                        <button class="action-btn edit" onclick="editItem(<?= $item['id'] ?>)">编辑</button>
                                        <button class="action-btn delete" onclick="deleteItem(<?= $item['id'] ?>)">🗑️</button>
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
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">发布二手</div>
            <div class="modal-body">
                <form id="itemForm">
                    <input type="hidden" name="id" id="itemId" value="">
                    
                    <div class="form-group">
                        <label>标题 <span style="color: red">*</span></label>
                        <input type="text" name="title" id="itemTitle" required placeholder="如：九成新山地车转让">
                    </div>
                    
                    <div class="form-group">
                        <label>详细描述</label>
                        <textarea name="content" id="itemContent" placeholder="物品描述、新旧程度、交易方式等" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>价格 (元)</label>
                            <input type="number" step="0.01" name="price" id="itemPrice" value="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>联系人</label>
                            <input type="text" name="contact" id="itemContact" placeholder="如：张同学">
                        </div>
                        <div class="form-group">
                            <label>联系电话</label>
                            <input type="text" name="user_phone" id="itemPhone" placeholder="11位手机号">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>发布人姓名</label>
                        <input type="text" name="user_name" id="itemUserName" placeholder="发布人姓名">
                    </div>
                    
                    <div class="form-group">
                        <label>商品图片 (可上传多张)</label>
                        <div class="image-uploader" onclick="document.getElementById('imagesInput').click()">
                            <div style="font-size: 28px; margin-bottom: 6px;">📷</div>
                            <div style="font-size: 13px; font-weight: 500;">点击上传图片</div>
                            <div style="font-size: 11px; color: #8c8c8c; margin-top: 4px;">支持多张，每张 ≤ 5MB</div>
                        </div>
                        <input type="file" id="imagesInput" accept="image/*" multiple style="display: none;" onchange="handleImagesUpload(event)">
                        <div class="image-list" id="imageList"></div>
                        <input type="hidden" name="images" id="imagesHidden" value="">
                    </div>
                    
                    <div class="form-group">
                        <label>状态</label>
                        <select name="status" id="itemStatus">
                            <option value="0">待审核</option>
                            <option value="1">已通过</option>
                            <option value="2">已拒绝</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="submitForm()">确定</button>
            </div>
        </div>
    </div>
    
    <!-- 详情模态框 -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">详情查看</div>
            <div class="modal-body" id="viewBody"></div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeView()">关闭</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    const items = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
    let uploadedImages = [];
    
    function handleImagesUpload(event) {
        const files = Array.from(event.target.files);
        if (files.length === 0) return;
        
        let pending = files.length;
        files.forEach(file => {
            if (file.size > 5 * 1024 * 1024) { showMessage('❌ ' + file.name + ' 超过 5MB', 'error'); pending--; checkDone(); return; }
            const reader = new FileReader();
            reader.onload = function(e) {
                uploadedImages.push(e.target.result);
                pending--;
                checkDone();
            };
            reader.readAsDataURL(file);
        });
        
        function checkDone() {
            if (pending === 0) renderImages();
        }
    }
    
    function renderImages() {
        const list = document.getElementById('imageList');
        document.getElementById('imagesHidden').value = uploadedImages.join(',');
        list.innerHTML = uploadedImages.map((img, i) => `
            <div class="image-item">
                <img src="${img}">
                <button type="button" class="remove" onclick="removeImage(${i})">×</button>
            </div>
        `).join('');
    }
    
    function removeImage(i) {
        uploadedImages.splice(i, 1);
        renderImages();
    }
    
    function showAddModal() {
        document.getElementById('modalTitle').textContent = '发布二手';
        document.getElementById('itemForm').reset();
        document.getElementById('itemId').value = '';
        uploadedImages = [];
        document.getElementById('imageList').innerHTML = '';
        document.getElementById('imagesHidden').value = '';
        document.getElementById('itemStatus').value = '0';
        document.getElementById('itemModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('itemModal').classList.remove('show');
    }
    
    function editItem(id) {
        const item = items.find(x => x.id == id);
        if (!item) return;
        
        document.getElementById('modalTitle').textContent = '编辑二手';
        document.getElementById('itemId').value = item.id;
        document.getElementById('itemTitle').value = item.title;
        document.getElementById('itemContent').value = item.content || '';
        document.getElementById('itemPrice').value = item.price || 0;
        document.getElementById('itemContact').value = item.contact || '';
        document.getElementById('itemUserName').value = item.user_name || '';
        document.getElementById('itemPhone').value = item.user_phone || '';
        document.getElementById('itemStatus').value = item.status;
        
        uploadedImages = (item.images || '').split(',').filter(s => s.trim());
        document.getElementById('imagesHidden').value = uploadedImages.join(',');
        renderImages();
        
        document.getElementById('itemModal').classList.add('show');
    }
    
    function submitForm() {
        const form = document.getElementById('itemForm');
        const id = document.getElementById('itemId').value;
        const formData = new FormData(form);
        formData.append('action', id ? 'update' : 'add');
        formData.set('images', uploadedImages.join(','));
        
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.json())
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
    
    function auditItem(id, status) {
        const action = status == 1 ? '通过' : '拒绝';
        if (!confirm('确定要' + action + '该信息吗？')) return;
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=audit&id=${id}&status=${status}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }
    
    function deleteItem(id) {
        if (!confirm('确定要删除该信息吗？删除后不可恢复！')) return;
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }
    
    function viewItem(id) {
        const item = items.find(x => x.id == id);
        if (!item) return;
        const imgs = (item.images || '').split(',').filter(s => s.trim());
        const imgHtml = imgs.length > 0 ? `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:8px;">${imgs.map(i => `<img src="${i}" style="width:100%;height:120px;object-fit:cover;border-radius:6px;">`).join('')}</div>` : '<div style="color:#999;padding:20px;text-align:center;">无图片</div>';
        document.getElementById('viewBody').innerHTML = `
            <div style="line-height:1.8;">
                <h3 style="margin:0 0 16px;color:#262626;">${escapeHtml(item.title)}</h3>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;">
                    <div><strong>价格：</strong><span style="color:#ff4d4f;font-weight:600;">¥${parseFloat(item.price||0).toFixed(2)}</span></div>
                    <div><strong>状态：</strong><span class="badge ${item.status==1?'badge-success':(item.status==2?'badge-danger':'badge-warning')}">${['','已通过','已拒绝'][item.status]||'待审核'}</span></div>
                    <div><strong>发布人：</strong>${escapeHtml(item.user_name || '匿名')}</div>
                    <div><strong>联系电话：</strong>${escapeHtml(item.user_phone || '-')}</div>
                    <div><strong>联系人：</strong>${escapeHtml(item.contact || '-')}</div>
                    <div><strong>发布时间：</strong>${item.created_at}</div>
                </div>
                <div style="background:#fafafa;padding:16px;border-radius:8px;margin-bottom:16px;">
                    <strong>详细描述：</strong>
                    <div style="margin-top:8px;white-space:pre-wrap;color:#595959;">${escapeHtml(item.content || '无')}</div>
                </div>
                <div><strong>商品图片：</strong>${imgHtml}</div>
            </div>
        `;
        document.getElementById('viewModal').classList.add('show');
    }
    
    function closeView() {
        document.getElementById('viewModal').classList.remove('show');
    }
    
    function escapeHtml(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
        updateBatchToolbar();
    }
    
    function updateBatchToolbar() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        const toolbar = document.getElementById('batchToolbar');
        if (checkboxes.length > 0) {
            toolbar.classList.add('show');
            document.getElementById('selectedCount').textContent = checkboxes.length;
        } else {
            toolbar.classList.remove('show');
        }
    }
    
    function batchAudit(status) {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) { showMessage('❌ 请先选择项目', 'error'); return; }
        const action = status == 1 ? '通过' : '拒绝';
        if (!confirm('确定要批量' + action + '选中的 ' + checkboxes.length + ' 项吗？')) return;
        const ids = Array.from(checkboxes).map(cb => cb.value);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=batch_audit&ids[]=${ids.join('&ids[]=')}&status=${status}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }
    
    function batchDelete() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) { showMessage('❌ 请先选择项目', 'error'); return; }
        if (!confirm('确定要删除选中的 ' + checkboxes.length + ' 项吗？')) return;
        const ids = Array.from(checkboxes).map(cb => cb.value);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=batch_delete&ids[]=${ids.join('&ids[]=')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }
    
    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:200px;padding:12px 20px;border-radius:8px;background:' + (type === 'error' ? '#fff2f0;color:#ff4d4f;border:1px solid #ffccc7' : '#f6ffed;color:#52c41a;border:1px solid #b7eb8f') + ';box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
    
    document.getElementById('itemModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    document.getElementById('viewModal').addEventListener('click', function(e) { if (e.target === this) closeView(); });
    </script>
</body>
</html>
