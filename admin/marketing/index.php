<?php
/**
 * 优惠发放页面
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL); ini_set("display_errors", 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Auth::check();

try {
    $db = Database::getInstance();
    $admin = Auth::user();
} catch (Exception $e) {
    echo "<pre>错误：" . $e->getMessage() . "</pre>";
    die();
}

$pageTitle = '优惠发放';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';

// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = 'name LIKE ?';
    $params[] = "%{$keyword}%";
}

if ($type !== '') {
    $where[] = 'type = ?';
    $params[] = $type;
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM coupons WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $coupons = $db->fetchAll("SELECT * FROM coupons WHERE {$whereStr} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}

$typeNames = ['', '满减', '折扣', '立减'];
$statusNames = ['禁用', '启用'];
$statusColors = ['#d9d9d9', '#52c41a'];

// 适配现有表结构字段映射
// 原字段：type, value, min_amount, max_amount, total, used, start_time, end_time
// 新字段：type, discount_type, discount_value, min_amount, total_count, received_count, used_count, valid_start, valid_end
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .content-wrapper { padding: 24px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-primary:hover { background: #40a9ff; }
        .btn-success { background: #52c41a; color: white; }
        .btn-success:hover { background: #73d13d; }
        .btn-default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #666; color: white; }
        .search-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .form-item { display: flex; flex-direction: column; gap: 6px; }
        .form-item label { font-size: 13px; color: #666; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .form-actions { grid-column: span 4; display: flex; gap: 12px; margin-top: 8px; }
        .coupon-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .coupon-name { font-size: 14px; color: #262626; font-weight: 500; }
        .coupon-type-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; background: #e6f7ff; color: #1890ff; }
        .min-amount { font-size: 13px; color: #ff4d4f; font-weight: 500; }
        .discount-value { font-size: 15px; color: #ff4d4f; font-weight: 600; }
        .discount-desc { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .progress-container { width: 100%; }
        .progress-bar { width: 100%; height: 6px; background: #f0f0f0; border-radius: 3px; overflow: hidden; margin: 8px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #1890ff, #91d5ff); border-radius: 3px; }
        .progress-text { font-size: 12px; color: #8c8c8c; }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .add-time { font-size: 13px; color: #8c8c8c; }
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .action-btn { padding: 4px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; text-decoration: none; }
        .action-btn.primary { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .action-btn.primary:hover { background: #1890ff; color: white; }
        .action-btn.success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .action-btn.success:hover { background: #52c41a; color: white; }
        .action-btn.default { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }
        .action-btn.default:hover { background: #666; color: white; }
        .pagination { display: flex; justify-content: center; align-items: center; padding: 20px; gap: 8px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; }
        .pagination a:hover, .pagination .active { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { font-weight: 600; }
        
        /* 弹窗样式 */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 16px; font-weight: 600; color: #262626; }
        .modal-close { cursor: pointer; font-size: 20px; color: #8c8c8c; }
        .modal-close:hover { color: #262626; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 12px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #262626; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
        .form-tip { font-size: 12px; color: #8c8c8c; margin-top: 6px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        <div class="content-wrapper">
            <!-- 搜索组件 -->
            <div class="search-bar">
                <form class="search-form" method="get">
                    <div class="form-item" style="grid-column: span 2;">
                        <label>关键词搜索</label>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="优惠名称">
                    </div>
                    <div class="form-item">
                        <label>优惠类型</label>
                        <select name="type">
                            <option value="">全部</option>
                            <option value="1" <?= $type === '1' ? 'selected' : '' ?>>满减</option>
                            <option value="2" <?= $type === '2' ? 'selected' : '' ?>>折扣</option>
                            <option value="3" <?= $type === '3' ? 'selected' : '' ?>>立减</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>状态</label>
                        <select name="status">
                            <option value="">全部</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>启用</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>禁用</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <button type="button" class="btn btn-default" onclick="resetSearch()">🔄 重置</button>
                        <button type="button" class="btn btn-success" onclick="showDistributeModal()">优惠发放</button>
                        <button type="button" class="btn btn-primary" onclick="showAddModal()">➕ 新增优惠</button>
                    </div>
                </form>
            </div>
            
            <!-- 优惠券列表 -->
            <div class="coupon-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="60">优惠 ID</th>
                                <th width="200">优惠名称</th>
                                <th width="120">优惠类型</th>
                                <th width="120">低消</th>
                                <th width="150">优惠方式</th>
                                <th width="200">已发放/已领取</th>
                                <th width="100">状态</th>
                                <th width="120">添加时间</th>
                                <th width="200">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">🎫</div>
                                    暂无优惠券
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td><strong><?= $coupon['id'] ?></strong></td>
                                    <td>
                                        <div class="coupon-name"><?= htmlspecialchars($coupon['name']) ?></div>
                                        <?php if (!empty($coupon['start_time']) && !empty($coupon['end_time'])): ?>
                                            <div style="font-size:11px; color:#8c8c8c; margin-top:4px;">
                                                <?= date('m-d', strtotime($coupon['start_time'])) ?> ~ <?= date('m-d', strtotime($coupon['end_time'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="coupon-type-badge"><?= $typeNames[$coupon['type']] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($coupon['min_amount'] > 0): ?>
                                            <div class="min-amount">¥<?= number_format($coupon['min_amount'], 2) ?></div>
                                        <?php else: ?>
                                            <span style="color: #d9d9d9;">无门槛</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // 适配现有表结构：value 字段直接存储优惠值
                                        $value = $coupon['value'] ?? 0;
                                        if ($coupon['type'] == 2) { // 折扣
                                            echo "<div class='discount-value'>打" . number_format($value / 10, 1) . "折</div>";
                                            echo "<div class='discount-desc'>折扣</div>";
                                        } else { // 满减/立减
                                            echo "<div class='discount-value'>减¥" . number_format($value, 2) . "</div>";
                                            echo "<div class='discount-desc'>满减</div>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-text">已使用 <?= $coupon['used'] ?? 0 ?> / 总量 <?= $coupon['total'] ?? 0 ?></div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?= ($coupon['total'] ?? 0) > 0 ? (($coupon['used'] ?? 0) / ($coupon['total'] ?? 1) * 100) : 0 ?>%"></div>
                                            </div>
                                            <div class="progress-text">剩余 <?= (($coupon['total'] ?? 0) - ($coupon['used'] ?? 0)) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?= $statusColors[$coupon['status']] ?>; color: white;">
                                            <?= $statusNames[$coupon['status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="add-time"><?= date('Y-m-d', strtotime($coupon['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.success" onclick="editCoupon(<?= $coupon['id'] ?>)">编辑</button>
                                            <button class="action-btn.primary" onclick="viewStats(<?= $coupon['id'] ?>)">📊 统计</button>
                                            <?php if ($coupon['status'] === 1): ?>
                                                <button class="action-btn.default" onclick="toggleStatus(<?= $coupon['id'] ?>, 0)">🚫 禁用</button>
                                            <?php else: ?>
                                                <button class="action-btn.default" onclick="toggleStatus(<?= $coupon['id'] ?>, 1)">✅ 启用</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">首页</a>
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">上一页</a>
                        <?php endif; ?>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">下一页</a>
                        <?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">末页</a>
                        <span style="margin-left: 12px; color: #8c8c8c;">共 <?= $total ?> 条</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 新增/编辑优惠弹窗 -->
    <div class="modal-overlay" id="couponModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">新增优惠券</div>
                <div class="modal-close" onclick="closeModal()">×</div>
            </div>
            <div class="modal-body">
                <form id="couponForm">
                    <input type="hidden" id="couponId" value="">
                    <div class="form-group">
                        <label>优惠名称</label>
                        <input type="text" id="couponName" required placeholder="如：新人专享券">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>优惠类型</label>
                            <select id="couponType" required>
                                <option value="1">满减</option>
                                <option value="2">折扣</option>
                                <option value="3">立减</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>最低消费（元）</label>
                            <input type="number" id="minAmount" value="0" min="0" placeholder="0 表示无门槛">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>优惠值</label>
                        <input type="number" id="discountValue" required placeholder="金额或折扣百分比（88 表示 8.8 折）">
                        <div class="form-tip" id="discountTip">满减/立减单位：元，折扣单位：百分比</div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>发放总量</label>
                            <input type="number" id="totalCount" value="1000" min="0">
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <select id="status">
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>有效开始时间</label>
                            <input type="datetime-local" id="startTime">
                        </div>
                        <div class="form-group">
                            <label>有效结束时间</label>
                            <input type="datetime-local" id="endTime">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="saveCoupon()">💾 保存</button>
            </div>
        </div>
    </div>
</body>
</html>
    <div class="modal-overlay" id="distributeModal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">优惠发放</div>
                <div class="modal-close" onclick="closeDistributeModal()">×</div>
            </div>
            <div class="modal-body">
                <form id="distributeForm">
                    <div class="form-group">
                        <label>选择优惠券</label>
                        <select id="selectCoupon">
                            <option value="">请选择优惠券</option>
                            <?php foreach ($coupons as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?>（剩余<?= ($c['total'] ?? 0) - ($c['used'] ?? 0) ?>张）</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>发放方式</label>
                        <select id="distributeType" onchange="updateDistributeOptions()">
                            <option value="all">全员发放</option>
                            <option value="level">按等级发放</option>
                            <option value="user">指定用户</option>
                        </select>
                    </div>
                    <div class="form-group" id="levelSelect" style="display:none;">
                        <label>会员等级</label>
                        <select id="targetLevel">
                            <option value="1">普通会员</option>
                            <option value="2">银卡会员</option>
                            <option value="3">金卡会员</option>
                            <option value="4">钻石会员</option>
                        </select>
                    </div>
                    <div class="form-group" id="userSelect" style="display:none;">
                        <label>指定用户 ID</label>
                        <input type="text" id="targetUsers" placeholder="多个用户用逗号分隔">
                        <div class="form-tip">例：1,2,3</div>
                    </div>
                    <div class="form-group">
                        <label>发放数量</label>
                        <input type="number" id="distributeCount" value="1" min="1" max="10">
                        <div class="form-tip">每个用户可领取的数量</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeDistributeModal()">取消</button>
                <button class="btn btn-success" onclick="executeDistribute()">确认发放</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    // 显示新增弹窗
    function showAddModal() {
        document.getElementById('modalTitle').textContent = '新增优惠券';
        document.getElementById('couponId').value = '';
        document.getElementById('couponForm').reset();
        document.getElementById('couponModal').classList.add('show');
    }
    
    // 编辑优惠
    function editCoupon(id) {
        alert('编辑功能开发中，优惠券 ID: ' + id);
    }
    
    // 查看统计
    function viewStats(id) {
        alert('统计功能开发中，优惠券 ID: ' + id);
    }
    
    // 切换状态
    function toggleStatus(id, status) {
        const action = status === 1 ? '启用' : '禁用';
        if (confirm('确定要' + action + '该优惠券吗？')) {
            alert('状态切换功能开发中，优惠券 ID: ' + id);
        }
    }
    
    // 保存优惠
    function saveCoupon() {
        const data = {
            id: document.getElementById('couponId').value,
            name: document.getElementById('couponName').value,
            type: document.getElementById('couponType').value,
            min_amount: document.getElementById('minAmount').value,
            value: document.getElementById('discountValue').value,
            total: document.getElementById('totalCount').value,
            status: document.getElementById('status').value,
            start_time: document.getElementById('startTime').value,
            end_time: document.getElementById('endTime').value
        };
        
        if (!data.name) {
            alert('请填写优惠名称');
            return;
        }
        
        console.log('保存数据:', data);
        alert('保存功能开发中');
        closeModal();
    }
    
    // 显示发放弹窗
    function showDistributeModal() {
        document.getElementById('distributeModal').classList.add('show');
    }
    
    // 关闭发放弹窗
    function closeDistributeModal() {
        document.getElementById('distributeModal').classList.remove('show');
    }
    
    // 更新发放选项
    function updateDistributeOptions() {
        const type = document.getElementById('distributeType').value;
        document.getElementById('levelSelect').style.display = (type === 'level') ? 'block' : 'none';
        document.getElementById('userSelect').style.display = (type === 'user') ? 'block' : 'none';
    }
    
    // 执行发放
    function executeDistribute() {
        const couponId = document.getElementById('selectCoupon').value;
        if (!couponId) {
            alert('请选择优惠券');
            return;
        }
        alert('优惠发放功能开发中');
        closeDistributeModal();
    }
    
    // 关闭弹窗
    function closeModal() {
        document.getElementById('couponModal').classList.remove('show');
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    
    // 点击遮罩关闭弹窗
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
