<?php
/**
 * 优惠券管理页面
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();

$pageTitle = '优惠券管理';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;

// 搜索
$keyword = trim($_GET['keyword'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

$where = ['1=1'];
$params = [];
if ($keyword) { $where[] = 'name LIKE ?'; $params[] = "%{$keyword}%"; }
if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
if ($type !== '') { $where[] = 'type = ?'; $params[] = $type; }
$whereStr = implode(' AND ', $where);

$total = $db->fetchOne("SELECT COUNT(*) as cnt FROM coupons WHERE {$whereStr}", $params)['cnt'] ?? 0;
$totalPages = ceil($total / $pageSize);
$offset = ($page - 1) * $pageSize;
$list = $db->fetchAll("SELECT * FROM coupons WHERE {$whereStr} ORDER BY sort DESC, id DESC LIMIT {$pageSize} OFFSET {$offset}", $params);

// 处理 POST 提交
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid = intval($_POST['id'] ?? 0);
    
    if ($action === 'toggle' && $cid > 0) {
        $cur = $db->fetchOne("SELECT status FROM coupons WHERE id = ?", [$cid]);
        $db->execute("UPDATE coupons SET status = ? WHERE id = ?", [$cur['status'] ? 0 : 1, $cid]);
        $message = $cur['status'] ? '已下架' : '已上架';
    } elseif ($action === 'delete' && $cid > 0) {
        $db->execute("DELETE FROM coupons WHERE id = ?", [$cid]);
        $db->execute("DELETE FROM coupon_receive_log WHERE coupon_id = ?", [$cid]);
        $message = '已删除';
    } elseif ($action === 'save') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? 'discount',
            'value' => floatval($_POST['value'] ?? 0),
            'min_amount' => floatval($_POST['min_amount'] ?? 0),
            'max_amount' => floatval($_POST['max_amount'] ?? 0) ?: null,
            'use_type' => $_POST['use_type'] ?? 'all',
            'total' => intval($_POST['total'] ?? 0),
            'per_limit' => intval($_POST['per_limit'] ?? 1),
            'receive_start' => $_POST['receive_start'] ?: null,
            'receive_end' => $_POST['receive_end'] ?: null,
            'start_time' => $_POST['start_time'] ?: null,
            'end_time' => $_POST['end_time'] ?: null,
            'sort' => intval($_POST['sort'] ?? 0),
            'instructions' => trim($_POST['instructions'] ?? ''),
            'is_show' => isset($_POST['is_show']) ? 1 : 0,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
        if ($cid > 0) {
            $db->update('coupons', $data, 'id = ?', [$cid]);
            $message = '更新成功';
        } else {
            $data['received'] = 0;
            $data['used'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('coupons', $data);
            $message = '创建成功';
        }
        // 刷新列表
        header('Location: coupon_manage.php?msg=' . urlencode($message));
        exit;
    }
    if ($message && $action !== 'save') {
        header('Location: coupon_manage.php?msg=' . urlencode($message));
        exit;
    }
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <style>
        .content-wrapper { padding: 24px; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-title { font-size: 18px; font-weight: 600; color: #262626; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        th { background: #fafafa; font-weight: 600; color: #262626; }
        .btn { padding: 6px 14px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-danger { background: #ff4d4f; color: white; }
        .btn-default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .tag { padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .tag-on { background: #f6ffed; color: #52c41a; }
        .tag-off { background: #fafafa; color: #8c8c8c; }
        .search-form { display: flex; gap: 10px; margin-bottom: 16px; align-items: center; }
        .search-form input, .search-form select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px; }
        .pagination a { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; }
        .pagination a.active { background: #1890ff; color: white; border-color: #1890ff; }
        .alert { padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        
        /* 弹窗 */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 600px; max-width: 95%; max-height: 85vh; overflow-y: auto; }
        .modal h3 { margin: 0 0 16px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 4px; font-size: 13px; color: #666; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        .form-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="content-wrapper">
            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            
            <!-- 搜索 -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><?= $pageTitle ?></div>
                    <button class="btn btn-primary" onclick="openModal(0)">+ 新增优惠券</button>
                </div>
                <form class="search-form" method="get">
                    <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="搜索优惠券名称">
                    <select name="type">
                        <option value="">全部类型</option>
                        <option value="discount" <?= $type==='discount'?'selected':'' ?>>折扣券</option>
                        <option value="fixed" <?= $type==='fixed'?'selected':'' ?>>满减券</option>
                    </select>
                    <select name="status">
                        <option value="">全部状态</option>
                        <option value="1" <?= $status==='1'?'selected':'' ?>>上架</option>
                        <option value="0" <?= $status==='0'?'selected':'' ?>>下架</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
                    <a href="coupon_manage.php" class="btn btn-default btn-sm">重置</a>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>名称</th><th>类型</th><th>面值</th><th>门槛</th>
                            <th>领取/总量</th><th>时间</th><th>状态</th><th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): 
                            $typeName = $row['type'] === 'discount' ? '折扣' : '满减';
                            $valueStr = $row['type'] === 'discount' ? ($row['value']*10).'折' : '¥'.$row['value'];
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= $typeName ?></td>
                            <td style="color:#ff4d4f;font-weight:600;"><?= $valueStr ?></td>
                            <td><?= $row['min_amount'] > 0 ? '满¥'.$row['min_amount'] : '无门槛' ?></td>
                            <td><?= $row['received'] ?>/<?= $row['total'] ?></td>
                            <td style="font-size:12px;"><?= substr($row['start_time']??'',0,10) ?> ~ <?= substr($row['end_time']??'',0,10) ?></td>
                            <td><span class="tag <?= $row['status']?'tag-on':'tag-off' ?>"><?= $row['status']?'上架':'下架' ?></span></td>
                            <td>
                                <button class="btn btn-default btn-sm" onclick="openModal(<?= $row['id'] ?>)">编辑</button>
                                <form method="post" style="display:inline" onsubmit="return confirm('确定<?= $row['status']?'下架':'上架' ?>?')">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-default btn-sm"><?= $row['status']?'下架':'上架' ?></button>
                                </form>
                                <form method="post" style="display:inline" onsubmit="return confirm('确定删除?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-danger btn-sm">删除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($list)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:#8c8c8c;">暂无优惠券</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&type=<?= $type ?>&status=<?= $status ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 新增/编辑弹窗 -->
    <div class="modal-overlay" id="couponModal">
        <div class="modal">
            <h3 id="modalTitle">新增优惠券</h3>
            <form method="post" id="couponForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId" value="0">
                
                <div class="form-group">
                    <label>名称 <span style="color:red">*</span></label>
                    <input type="text" name="name" id="fname" required placeholder="如：新用户专享券">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>类型</label>
                        <select name="type" id="ftype">
                            <option value="discount">折扣券</option>
                            <option value="fixed">满减券</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>面值 <span style="color:red">*</span></label>
                        <input type="number" name="value" id="fvalue" step="0.01" min="0" required placeholder="折扣填0.1~0.99，满减填金额">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>最低消费金额</label>
                        <input type="number" name="min_amount" id="fmin" step="0.01" min="0" value="0" placeholder="0=无门槛">
                    </div>
                    <div class="form-group">
                        <label>最高抵扣金额(折扣券)</label>
                        <input type="number" name="max_amount" id="fmax" step="0.01" min="0" placeholder="选填">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>发放总量</label>
                        <input type="number" name="total" id="ftotal" min="1" value="100">
                    </div>
                    <div class="form-group">
                        <label>每人限领</label>
                        <input type="number" name="per_limit" id="fper" min="1" value="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>领取开始</label>
                        <input type="datetime-local" name="receive_start" id="frs">
                    </div>
                    <div class="form-group">
                        <label>领取结束</label>
                        <input type="datetime-local" name="receive_end" id="fre">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>使用开始</label>
                        <input type="datetime-local" name="start_time" id="fts">
                    </div>
                    <div class="form-group">
                        <label>使用结束</label>
                        <input type="datetime-local" name="end_time" id="fte">
                    </div>
                </div>
                <div class="form-group">
                    <label>使用说明</label>
                    <textarea name="instructions" id="fins" rows="2" placeholder="如：全场通用，不可叠加"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" id="fstatus" checked value="1"> 上架
                    </label>
                    &nbsp;&nbsp;
                    <label>
                        <input type="checkbox" name="is_show" id="fshow" checked value="1"> 前端展示
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-default" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // 优惠券数据（用于编辑填充）
        var couponData = <?= json_encode($list, JSON_UNESCAPED_UNICODE) ?>;
        
        function openModal(id) {
            document.getElementById('editId').value = id;
            if (id > 0) {
                document.getElementById('modalTitle').textContent = '编辑优惠券';
                var c = couponData.find(function(r) { return r.id == id; });
                if (c) {
                    document.getElementById('fname').value = c.name || '';
                    document.getElementById('ftype').value = c.type || 'discount';
                    document.getElementById('fvalue').value = c.value || 0;
                    document.getElementById('fmin').value = c.min_amount || 0;
                    document.getElementById('fmax').value = c.max_amount || '';
                    document.getElementById('ftotal').value = c.total || 100;
                    document.getElementById('fper').value = c.per_limit || 1;
                    document.getElementById('frs').value = (c.receive_start||'').replace(' ','T');
                    document.getElementById('fre').value = (c.receive_end||'').replace(' ','T');
                    document.getElementById('fts').value = (c.start_time||'').replace(' ','T');
                    document.getElementById('fte').value = (c.end_time||'').replace(' ','T');
                    document.getElementById('fins').value = c.instructions || '';
                    document.getElementById('fstatus').checked = c.status == 1;
                    document.getElementById('fshow').checked = c.is_show == 1;
                }
            } else {
                document.getElementById('modalTitle').textContent = '新增优惠券';
                document.getElementById('couponForm').reset();
                document.getElementById('fstatus').checked = true;
                document.getElementById('fshow').checked = true;
            }
            document.getElementById('couponModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('couponModal').classList.remove('show');
        }
        
        document.getElementById('couponModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
    </div><!-- /content-wrapper -->
</div><!-- /main-content -->
</body>
</html>
