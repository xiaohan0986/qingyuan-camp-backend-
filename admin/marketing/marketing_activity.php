<?php
/**
 * 营销活动管理
 * 表: marketing_activities (id, name, type, config, start_time, end_time, status, created_at)
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '营销活动管理';

// 活动类型定义
$activityTypes = [
    'discount' => '限时折扣',
    'flash_sale' => '秒杀活动',
    'full_reduce' => '满减活动',
    'coupon' => '优惠券',
    'group_buy' => '团购活动',
    'new_user' => '新人专享',
    'points' => '积分活动',
    'lottery' => '抽奖活动',
    'other' => '其他',
];

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        try {
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? 'other');
            $config = $_POST['config'] ?? '';
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '活动名称不能为空']);
                exit;
            }
            
            // config 是 JSON 字符串
            $configJson = $config ?: '{}';
            
            $stmt = $db->query("INSERT INTO marketing_activities (name, type, config, start_time, end_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", [$name, $type, $configJson, $start_time ?: null, $end_time ?: null, $status]);
            
            echo json_encode(['success' => true, 'message' => '活动添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'update') {
        try {
            $id = intval($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? 'other');
            $config = $_POST['config'] ?? '';
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '活动名称不能为空']);
                exit;
            }
            
            $configJson = $config ?: '{}';
            
            $stmt = $db->query("UPDATE marketing_activities SET name=?, type=?, config=?, start_time=?, end_time=?, status=? WHERE id=?", [$name, $type, $configJson, $start_time ?: null, $end_time ?: null, $status, $id]);
            
            echo json_encode(['success' => true, 'message' => '活动更新成功']);
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
            $stmt = $db->query("UPDATE marketing_activities SET status = ? WHERE id = ?", [$status, $id]);
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
            $stmt = $db->query("DELETE FROM marketing_activities WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => '活动删除成功']);
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
                echo json_encode(['success' => false, 'message' => '请选择要删除的活动']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->query("DELETE FROM marketing_activities WHERE id IN ($placeholders)", $ids);
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

// 获取搜索参数
$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$statusFilter = $_GET['status'] ?? '';

// 构建查询
$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = 'name LIKE ?';
    $params[] = "%{$search}%";
}
if ($typeFilter !== '') {
    $where[] = 'type = ?';
    $params[] = $typeFilter;
}
if ($statusFilter !== '' && $statusFilter !== null) {
    $where[] = 'status = ?';
    $params[] = intval($statusFilter);
}

$whereSql = implode(' AND ', $where);
$sql = "SELECT * FROM marketing_activities WHERE {$whereSql} ORDER BY status DESC, id DESC";
$activities = $db->fetchAll($sql, $params);

// 解析每行的 config 字段
foreach ($activities as &$a) {
    $cfg = json_decode($a['config'] ?? '{}', true);
    if (!is_array($cfg)) $cfg = [];
    $a['config_arr'] = $cfg;
    $a['cover'] = $cfg['cover'] ?? '';
    $a['description'] = $cfg['description'] ?? '';
}
unset($a);
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
        .toggle-switch.on { background: #1890ff; }
        .toggle-switch.on::after { transform: translateX(22px); }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-primary { background: #f0f0ff; color: #1890ff; }
        .badge-success { background: #f6ffed; color: #52c41a; }
        .badge-danger { background: #fff2f0; color: #ff4d4f; }
        .badge-warning { background: #fffbe6; color: #faad14; }
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
            min-width: 560px;
            max-width: 700px;
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
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 14px;
            color: #262626;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
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
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }
        .image-uploader {
            border: 2px dashed #d9d9d9;
            border-radius: 12px;
            padding: 24px;
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
        .image-preview {
            position: relative;
            margin-top: 12px;
            max-width: 300px;
        }
        .image-preview img {
            width: 100%;
            max-height: 160px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }
        .image-preview .remove {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8c8c8c;
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .cover-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }
        .status-tag-active { color: #52c41a; font-weight: 500; }
        .status-tag-expired { color: #ff4d4f; font-weight: 500; }
        .status-tag-upcoming { color: #faad14; font-weight: 500; }
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
                        <span>➕</span> 新增活动
                    </button>
                </div>
            </div>
            
            <form class="search-bar" method="get">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 活动名称">
                <select name="type">
                    <option value="">全部类型</option>
                    <?php foreach ($activityTypes as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $typeFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">全部状态</option>
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>启用</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>禁用</option>
                </select>
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="marketing_activity.php" class="btn btn-default">重置</a>
            </form>
            
            <div class="batch-toolbar" id="batchToolbar">
                <div class="batch-info">
                    已选择 <strong id="selectedCount">0</strong> 个活动
                </div>
                <div class="batch-actions">
                    <button class="btn btn-danger" onclick="batchDelete()">
                        <span>🗑️</span> 批量删除
                    </button>
                </div>
            </div>
            
            <div class="data-table">
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <div class="icon">🎯</div>
                        <div>暂无活动，点击"新增活动"添加</div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th width="80">ID</th>
                                <th width="80">封面</th>
                                <th>活动名称</th>
                                <th width="100">类型</th>
                                <th width="200">活动时间</th>
                                <th width="100">状态</th>
                                <th width="100">启用</th>
                                <th width="160">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $a): ?>
                                <?php
                                    $now = time();
                                    $sTime = $a['start_time'] ? strtotime($a['start_time']) : null;
                                    $eTime = $a['end_time'] ? strtotime($a['end_time']) : null;
                                    $periodStatus = '进行中';
                                    $periodClass = 'status-tag-active';
                                    if ($sTime && $now < $sTime) { $periodStatus = '未开始'; $periodClass = 'status-tag-upcoming'; }
                                    elseif ($eTime && $now > $eTime) { $periodStatus = '已结束'; $periodClass = 'status-tag-expired'; }
                                ?>
                                <tr data-id="<?= $a['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="checkbox item-checkbox" value="<?= $a['id'] ?>" onchange="updateBatchToolbar()">
                                    </td>
                                    <td>#<?= $a['id'] ?></td>
                                    <td>
                                        <?php if (!empty($a['cover'])): ?>
                                            <img src="<?= htmlspecialchars($a['cover']) ?>" class="cover-thumb" alt="封面">
                                        <?php else: ?>
                                            <div style="width:60px;height:60px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999;">无</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($a['name']) ?></strong>
                                        <?php if (!empty($a['description'])): ?>
                                            <div style="font-size:12px;color:#999;margin-top:4px;"><?= mb_substr(htmlspecialchars($a['description']), 0, 40) ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?= $activityTypes[$a['type']] ?? $a['type'] ?></span>
                                    </td>
                                    <td style="font-size:12px;">
                                        <div><?= $a['start_time'] ? date('Y-m-d H:i', strtotime($a['start_time'])) : '不限' ?></div>
                                        <div style="color:#999;">至 <?= $a['end_time'] ? date('Y-m-d H:i', strtotime($a['end_time'])) : '不限' ?></div>
                                        <div class="<?= $periodClass ?>" style="font-size:11px;margin-top:2px;">● <?= $periodStatus ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $a['status'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $a['status'] ? '启用' : '禁用' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="toggle-switch <?= $a['status'] ? 'on' : '' ?>" 
                                             onclick="toggleStatus(<?= $a['id'] ?>, <?= $a['status'] ? 0 : 1 ?>)"></div>
                                    </td>
                                    <td>
                                        <button class="action-btn edit" onclick="editActivity(<?= $a['id'] ?>)">编辑</button>
                                        <button class="action-btn delete" onclick="deleteActivity(<?= $a['id'] ?>)">删除</button>
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
    <div class="modal" id="activityModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">新增活动</div>
            <div class="modal-body">
                <form id="activityForm">
                    <input type="hidden" name="id" id="actId" value="">
                    
                    <div class="form-group">
                        <label>活动名称 <span style="color: red">*</span></label>
                        <input type="text" name="name" id="actName" required placeholder="请输入活动名称">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>活动类型</label>
                            <select name="type" id="actType">
                                <?php foreach ($activityTypes as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>启用状态</label>
                            <select name="status" id="actStatus">
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>活动封面</label>
                        <div class="image-uploader" onclick="document.getElementById('coverInput').click()">
                            <div style="font-size: 32px; margin-bottom: 8px;">🖼️</div>
                            <div style="font-size: 13px; font-weight: 500; color: #262626;">点击上传封面</div>
                            <div style="font-size: 11px; color: #8c8c8c; margin-top: 4px;">建议 750×400，支持 JPG/PNG</div>
                        </div>
                        <input type="file" id="coverInput" accept="image/*" style="display: none;" onchange="handleCoverUpload(event)">
                        <div class="image-preview" id="coverPreview" style="display: none;">
                            <img id="coverImg" src="" alt="封面预览">
                            <button type="button" class="remove" onclick="removeCover()">×</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>活动描述</label>
                        <textarea name="description" id="actDescription" placeholder="活动规则、说明等" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>开始时间</label>
                            <input type="datetime-local" name="start_time" id="actStartTime">
                        </div>
                        <div class="form-group">
                            <label>结束时间</label>
                            <input type="datetime-local" name="end_time" id="actEndTime">
                        </div>
                    </div>
                    <div class="hint" style="margin-top: -12px; margin-bottom: 18px;">留空表示不限时间</div>
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
    const activities = <?= json_encode($activities, JSON_UNESCAPED_UNICODE) ?>;
    
    function handleCoverUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { showMessage('❌ 图片大小不能超过 5MB', 'error'); return; }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('coverImg').src = e.target.result;
            document.getElementById('coverPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    
    function removeCover() {
        document.getElementById('coverImg').src = '';
        document.getElementById('coverPreview').style.display = 'none';
        document.getElementById('coverInput').value = '';
    }
    
    function showAddModal() {
        document.getElementById('modalTitle').textContent = '新增活动';
        document.getElementById('activityForm').reset();
        document.getElementById('actId').value = '';
        document.getElementById('coverPreview').style.display = 'none';
        document.getElementById('actType').value = 'discount';
        document.getElementById('actStatus').value = '1';
        document.getElementById('activityModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('activityModal').classList.remove('show');
    }
    
    function editActivity(id) {
        const a = activities.find(x => x.id == id);
        if (!a) return;
        
        document.getElementById('modalTitle').textContent = '编辑活动';
        document.getElementById('actId').value = a.id;
        document.getElementById('actName').value = a.name;
        document.getElementById('actType').value = a.type;
        document.getElementById('actStatus').value = a.status;
        document.getElementById('actDescription').value = a.description || '';
        document.getElementById('actStartTime').value = a.start_time ? a.start_time.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('actEndTime').value = a.end_time ? a.end_time.replace(' ', 'T').substring(0, 16) : '';
        
        if (a.cover) {
            document.getElementById('coverImg').src = a.cover;
            document.getElementById('coverPreview').style.display = 'block';
        } else {
            document.getElementById('coverPreview').style.display = 'none';
        }
        
        document.getElementById('activityModal').classList.add('show');
    }
    
    function submitForm() {
        const form = document.getElementById('activityForm');
        const id = document.getElementById('actId').value;
        const formData = new FormData(form);
        formData.append('action', id ? 'update' : 'add');
        
        // 把封面/描述 打包成 config JSON
        const config = {
            cover: document.getElementById('coverImg').src && document.getElementById('coverPreview').style.display !== 'none' ? document.getElementById('coverImg').src : '',
            description: document.getElementById('actDescription').value,
        };
        formData.append('config', JSON.stringify(config));
        
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
    
    function toggleStatus(id, status) {
        if (!confirm('确定要' + (status ? '启用' : '禁用') + '该活动吗？')) return;
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=toggle_status&id=${id}&status=${status}`
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
    
    function deleteActivity(id) {
        if (!confirm('确定要删除该活动吗？删除后不可恢复！')) return;
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
    
    function batchDelete() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) { showMessage('❌ 请选择要删除的活动', 'error'); return; }
        if (!confirm('确定要删除选中的 ' + checkboxes.length + ' 个活动吗？')) return;
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
    
    document.getElementById('activityModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>
