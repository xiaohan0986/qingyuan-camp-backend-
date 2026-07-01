<?php
/**
 * 会员充值管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '用户充值管理';
$config = SystemConfig::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // ============ 套餐管理 ============
    
    if ($_POST['action'] === 'add_package') {
        try {
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $gift_amount = floatval($_POST['gift_amount'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) { echo json_encode(['success' => false, 'message' => '套餐名称不能为空']); exit; }
            if ($price <= 0) { echo json_encode(['success' => false, 'message' => '套餐价格必须大于0']); exit; }
            
            $stmt = $db->getConnection()->prepare("INSERT INTO recharge_packages (name, price, gift_amount, sort, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $price, $gift_amount, $sort, $status]);
            
            echo json_encode(['success' => true, 'message' => '套餐添加成功', 'id' => $db->getConnection()->insert_id]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '添加失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'edit_package') {
        try {
            $id = intval($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $gift_amount = floatval($_POST['gift_amount'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            
            if (empty($name)) { echo json_encode(['success' => false, 'message' => '套餐名称不能为空']); exit; }
            if ($price <= 0) { echo json_encode(['success' => false, 'message' => '套餐价格必须大于0']); exit; }
            
            $stmt = $db->getConnection()->prepare("UPDATE recharge_packages SET name = ?, price = ?, gift_amount = ?, sort = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $price, $gift_amount, $sort, $status, $id]);
            
            echo json_encode(['success' => true, 'message' => '套餐更新成功']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'toggle_package_status') {
        try {
            $id = intval($_POST['id']);
            $status = intval($_POST['status']);
            $stmt = $db->getConnection()->prepare("UPDATE recharge_packages SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => '状态已更新']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete_package') {
        try {
            $id = intval($_POST['id']);
            $stmt = $db->getConnection()->prepare("DELETE FROM recharge_packages WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '套餐已删除']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'get_package') {
        try {
            $id = intval($_POST['id']);
            $pkg = $db->fetchOne("SELECT * FROM recharge_packages WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => $pkg]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '获取失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    // ============ 手动充值 ============
    
    if ($_POST['action'] === 'manual_recharge') {
        try {
            $member_id = intval($_POST['member_id'] ?? 0);
            $package_id = intval($_POST['package_id'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');
            $operator = !empty($admin['name']) ? $admin['name'] : (!empty($admin['username']) ? $admin['username'] : 'admin');
            
            if (!$member_id) { echo json_encode(['success' => false, 'message' => '请选择会员']); exit; }
            if (!$package_id) { echo json_encode(['success' => false, 'message' => '请选择套餐']); exit; }
            
            $member = $db->fetchOne("SELECT * FROM members WHERE id = ?", [$member_id]);
            if (!$member) { echo json_encode(['success' => false, 'message' => '用户不存在']); exit; }
            
            $pkg = $db->fetchOne("SELECT * FROM recharge_packages WHERE id = ? AND status = 1", [$package_id]);
            if (!$pkg) { echo json_encode(['success' => false, 'message' => '套餐不存在或已下架']); exit; }
            
            $order_no = 'RC' . date('YmdHis') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $db->getConnection()->begin_transaction();
            try {
                $stmt = $db->getConnection()->prepare("INSERT INTO member_recharge_log (member_id, order_no, recharge_type, package_name, pay_amount, gift_amount, pay_status, paid_at, created_at) VALUES (?, ?, 'admin', ?, ?, ?, 1, NOW(), NOW())");
                $stmt->execute([$member_id, $order_no, $pkg['name'], $pkg['price'], $pkg['gift_amount']]);
                
                $total = $pkg['price'] + $pkg['gift_amount'];
                $upd = $db->getConnection()->prepare("UPDATE members SET balance = balance + ?, total_amount = total_amount + ? WHERE id = ?");
                $upd->execute([$total, $pkg['price'], $member_id]);
                
                $db->getConnection()->commit();
                echo json_encode(['success' => true, 'message' => '手动充值成功，订单号：' . $order_no, 'order_no' => $order_no]);
                exit;
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '充值失败：' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'search_member') {
        try {
            $kw = trim($_POST['keyword'] ?? '');
            if (empty($kw)) { echo json_encode(['success' => true, 'data' => []]); exit; }
            $like = "%{$kw}%";
            $list = $db->fetchAll("SELECT id, username, phone, nickname, balance, points FROM members WHERE (username LIKE ? OR phone LIKE ? OR nickname LIKE ? OR id = ?) AND status = 1 ORDER BY id DESC LIMIT 20", [$like, $like, $like, intval($kw)]);
            echo json_encode(['success' => true, 'data' => $list]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => '查询失败']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => '未知操作']);
    exit;
}

$packages = $db->fetchAll("SELECT * FROM recharge_packages ORDER BY sort ASC, id ASC");
$recentRecharges = $db->fetchAll(
    "SELECT r.*, m.username, m.phone, m.nickname FROM member_recharge_log r LEFT JOIN members m ON r.member_id = m.id ORDER BY r.id DESC LIMIT 10"
);
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
        .tabs {
            display: flex; background: white; border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #f0f0f0; padding: 0 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .tab {
            padding: 16px 24px; cursor: pointer; font-size: 15px; color: #595959;
            border-bottom: 3px solid transparent; transition: all 0.2s; font-weight: 500;
        }
        .tab:hover { color: #1890ff; }
        .tab.active { color: #1890ff; border-bottom-color: #1890ff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .package-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px; padding: 20px; background: white; border-radius: 0 0 12px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .package-card {
            border: 2px solid #f0f0f0; border-radius: 12px; padding: 24px; transition: all 0.3s;
            position: relative; background: white;
        }
        .package-card:hover { border-color: #1890ff; transform: translateY(-4px); box-shadow: 0 8px 24px rgba(24,144,255,0.15); }
        .package-card.disabled { opacity: 0.5; }
        .package-name { font-size: 18px; font-weight: 600; color: #262626; margin-bottom: 12px; }
        .package-price { font-size: 32px; font-weight: 700; color: #1890ff; line-height: 1; margin-bottom: 8px; }
        .package-price small { font-size: 14px; color: #8c8c8c; font-weight: normal; }
        .package-gift { color: #ff4d4f; font-size: 14px; margin-bottom: 16px; }
        .package-gift strong { font-size: 18px; }
        .package-actions { display: flex; gap: 8px; }
        .package-actions .btn { flex: 1; padding: 8px 12px; font-size: 13px; justify-content: center; }
        .package-status {
            position: absolute; top: 12px; right: 12px;
            padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 500;
        }
        .package-status.on { background: #f6ffed; color: #52c41a; }
        .package-status.off { background: #fff2f0; color: #ff4d4f; }
        .recharge-table {
            background: white; border-radius: 0 0 12px 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .badge-warning { background: #fffbe6; color: #faad14; border: 1px solid #ffe58f; }
        .badge-danger { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .toggle-switch { position: relative; width: 44px; height: 22px; background: #d9d9d9; border-radius: 11px; cursor: pointer; transition: background 0.3s; display: inline-block; vertical-align: middle; }
        .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch.on { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .toggle-switch.on::after { transform: translateX(22px); }
                .page-drawer {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2000;
        }
        .page-drawer.show { display: flex; justify-content: flex-end; }
        .drawer-mask {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .drawer-content {
            position: relative;
            width: 640px;
            max-width: 95%;
            height: 100%;
            background: white;
            box-shadow: -4px 0 24px rgba(0,0,0,0.15);
            animation: slideIn 0.3s;
            display: flex;
            flex-direction: column;
        }
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
        }
        .drawer-header h3 { margin: 0; font-size: 18px; color: #262626; }
        .drawer-close {
            width: 32px; height: 32px; border: none; background: transparent;
            font-size: 24px; color: #8c8c8c; cursor: pointer; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .drawer-close:hover { background: #f5f5f5; color: #262626; }
        .drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
        .drawer-footer {
            padding: 16px 24px; border-top: 1px solid #f0f0f0;
            display: flex; justify-content: flex-end; gap: 12px;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(100%); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        .page-drawer.closing .drawer-content { animation: slideOut 0.3s forwards; }
        .page-drawer.closing .drawer-mask { animation: fadeOut 0.3s forwards; }

        .checkbox-group { display: flex; gap: 20px; }
        .checkbox-item { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .empty-state { text-align: center; padding: 80px 20px; color: #8c8c8c; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { color: #595959; margin-bottom: 8px; }
        .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .member-search-result { max-height: 180px; overflow-y: auto; border: 1px solid #f0f0f0; border-radius: 8px; margin-top: 8px; display: none; }
        .member-search-result.show { display: block; }
        .member-search-item { padding: 10px 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; }
        .member-search-item:hover { background: #f5f5f5; }
        .member-search-item .info { display: flex; flex-direction: column; gap: 2px; }
        .member-search-item .name { font-weight: 500; color: #262626; }
        .member-search-item .meta { font-size: 12px; color: #8c8c8c; }
        .selected-info { margin-top:8px;padding:8px 12px;background:#f6ffed;border-radius:6px;color:#52c41a;font-size:13px;display:none; }
        .pkg-option { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border: 1px solid #f0f0f0; border-radius: 6px; margin-bottom: 8px; cursor: pointer; }
        .pkg-option:hover { border-color: #1890ff; background: #f5f7ff; }
        .pkg-option.selected { border-color: #1890ff; background: linear-gradient(135deg, rgba(24,144,255,0.05), rgba(64,169,255,0.05)); }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="toolbar">
                <h1>💰 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="showRechargeModal()">手动充值</button>
                    <button class="btn btn-primary" onclick="showPackageModal()">➕ 新增套餐</button>
                </div>
            </div>
            
            <div class="tabs">
                <div class="tab active" data-tab="packages" onclick="switchTab('packages')">📦 充值套餐</div>
                <div class="tab" data-tab="recent" onclick="switchTab('recent')">📋 最近充值</div>
            </div>
            
            <!-- 套餐列表 -->
            <div class="tab-content active" id="tab-packages">
                <?php if (empty($packages)): ?>
                    <div class="empty-state">
                        <div class="icon">📦</div>
                        <h3>暂无套餐</h3>
                        <p>点击"新增套餐"创建第一个充值套餐</p>
                    </div>
                <?php else: ?>
                    <div class="package-grid">
                        <?php foreach ($packages as $pkg): ?>
                            <div class="package-card <?= !$pkg['status'] ? 'disabled' : '' ?>" data-id="<?= $pkg['id'] ?>">
                                <div class="package-status <?= $pkg['status'] ? 'on' : 'off' ?>">
                                    <?= $pkg['status'] ? '上架' : '下架' ?>
                                </div>
                                <div class="package-name"><?= htmlspecialchars($pkg['name']) ?></div>
                                <div class="package-price">
                                    <small>¥</small><?= number_format($pkg['price'], 2) ?>
                                </div>
                                <?php if ($pkg['gift_amount'] > 0): ?>
                                    <div class="package-gift">🎁 赠送 <strong>¥<?= number_format($pkg['gift_amount'], 2) ?></strong></div>
                                <?php else: ?>
                                    <div class="package-gift" style="color:#8c8c8c">无赠送</div>
                                <?php endif; ?>
                                <div class="package-actions">
                                    <button class="btn btn-default" onclick="editPackage(<?= $pkg['id'] ?>)">编辑</button>
                                    <button class="btn <?= $pkg['status'] ? 'btn-default' : 'btn-success' ?>" onclick="togglePackageStatus(<?= $pkg['id'] ?>, <?= $pkg['status'] ? 0 : 1 ?>)">
                                        <?= $pkg['status'] ? '下架' : '上架' ?>
                                    </button>
                                    <button class="btn btn-danger" onclick="deletePackage(<?= $pkg['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 最近充值 -->
            <div class="tab-content" id="tab-recent">
                <div class="recharge-table">
                    <?php if (empty($recentRecharges)): ?>
                        <div class="empty-state">
                            <div class="icon">📋</div>
                            <h3>暂无充值记录</h3>
                            <p>点击"手动充值"开始第一笔充值</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th>订单号</th>
                                    <th>会员</th>
                                    <th width="160">套餐</th>
                                    <th width="100">支付金额</th>
                                    <th width="100">赠送</th>
                                    <th width="100">类型</th>
                                    <th width="100">状态</th>
                                    <th width="140">支付时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRecharges as $r): ?>
                                    <tr>
                                        <td>#<?= $r['id'] ?></td>
                                        <td><code style="font-size:12px;color:#595959"><?= htmlspecialchars($r['order_no']) ?></code></td>
                                        <td>
                                            <div style="font-weight:500;color:#262626"><?= htmlspecialchars($r['nickname'] ?: $r['username'] ?: '用户#' . $r['member_id']) ?></div>
                                            <div style="font-size:12px;color:#8c8c8c"><?= htmlspecialchars($r['phone'] ?: '-') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($r['package_name']) ?></td>
                                        <td style="color:#1890ff;font-weight:600">¥<?= number_format($r['pay_amount'], 2) ?></td>
                                        <td style="color:#ff4d4f">+¥<?= number_format($r['gift_amount'], 2) ?></td>
                                        <td>
                                            <?php if ($r['recharge_type'] === 'admin'): ?>
                                                <span class="badge badge-warning">🛠️ 后台</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">💳 用户</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($r['pay_status'] == 1): ?>
                                                <span class="badge badge-success">✅ 已支付</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">⏳ 待支付</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $r['paid_at'] ? date('Y-m-d H:i', strtotime($r['paid_at'])) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 套餐表单模态框 -->
    <div id="packageDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closePackageDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3 id="pkgDrawerTitle">套餐管理</h3>
                <button class="drawer-close" onclick="closePackageDrawer()">×</button>
            </div>
            <div class="drawer-body">
                <form id="packageForm">
                    <input type="hidden" name="id" id="pkgId" value="">
                    <div class="form-group">
                        <label>套餐名称 <span class="required">*</span></label>
                        <input type="text" name="name" id="pkgName" required maxlength="50" placeholder="例如：入门套餐">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>售价 (元) <span class="required">*</span></label>
                            <input type="number" name="price" id="pkgPrice" min="0.01" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>赠送金额 (元)</label>
                            <input type="number" name="gift_amount" id="pkgGift" min="0" step="0.01" value="0" placeholder="0.00">
                            <div class="hint">额外赠送的余额</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>排序</label>
                            <input type="number" name="sort" id="pkgSort" value="0">
                            <div class="hint">数字越小越靠前</div>
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="status" value="1" checked> <span>上架</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="status" value="0"> <span>下架</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closePackageDrawer()">取消</button>
                <button class="btn btn-primary" onclick="submitPackage()">确定</button>
            </div>
        </div>
    </div>
    
    <!-- 手动充值抽屉 -->
    <div id="rechargeDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeRechargeDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3>手动充值</h3>
                <button class="drawer-close" onclick="closeRechargeDrawer()">×</button>
            </div>
            <div class="drawer-body">
                <form id="rechargeForm">
                    <div class="form-group">
                        <label>选择会员 <span class="required">*</span></label>
                        <input type="text" id="memberSearch" placeholder="输入会员ID/手机号/昵称" autocomplete="off">
                        <input type="hidden" name="member_id" id="memberId">
                        <div class="member-search-result" id="memberSearchResult"></div>
                        <div class="selected-info" id="selectedMember"></div>
                    </div>
                    <div class="form-group">
                        <label>选择套餐 <span class="required">*</span></label>
                        <input type="hidden" name="package_id" id="packageId">
                        <div id="packageOptions">
                            <?php foreach ($packages as $pkg): ?>
                                <?php if ($pkg['status']): ?>
                                    <div class="pkg-option" data-id="<?= $pkg['id'] ?>" data-price="<?= $pkg['price'] ?>" data-gift="<?= $pkg['gift_amount'] ?>" onclick="selectPackage(<?= $pkg['id'] ?>)">
                                        <div>
                                            <div style="font-weight:500;color:#262626"><?= htmlspecialchars($pkg['name']) ?></div>
                                            <div style="font-size:12px;color:#8c8c8c">赠送 ¥<?= number_format($pkg['gift_amount'], 2) ?></div>
                                        </div>
                                        <div style="color:#1890ff;font-weight:600">¥<?= number_format($pkg['price'], 2) ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (empty($packages) || !array_filter($packages, function($p) { return $p['status']; })): ?>
                                <div style="color:#999;padding:12px;text-align:center">暂无可用套餐</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>备注</label>
                        <textarea name="remark" placeholder="可选：本次手动充值原因" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closeRechargeDrawer()">取消</button>
                <button class="btn btn-primary" onclick="submitRecharge()">确认充值</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        function switchTab(name) {
            document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === 'tab-' + name));
        }
        
        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        
        // ============ 套餐管理 ============
        let pkgMode = 'add_package';
        function showPackageModal() {
            pkgMode = 'add_package';
            document.getElementById('pkgDrawerTitle').textContent = '新增套餐';
            document.getElementById('packageForm').reset();
            document.getElementById('pkgId').value = '';
            document.getElementById('packageModal').classList.add('show');
        }
        function closePackageDrawer() { document.getElementById('packageModal').classList.remove('show'); }
        
        function editPackage(id) {
            pkgMode = 'edit_package';
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_package&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const p = data.data;
                    document.getElementById('pkgDrawerTitle').textContent = '编辑套餐';
                    document.getElementById('pkgId').value = p.id;
                    document.getElementById('pkgName').value = p.name;
                    document.getElementById('pkgPrice').value = p.price;
                    document.getElementById('pkgGift').value = p.gift_amount;
                    document.getElementById('pkgSort').value = p.sort;
                    document.querySelector(`input[name="status"][value="${p.status}"]`).checked = true;
                    document.getElementById('packageModal').classList.add('show');
                }
            });
        }
        
        function submitPackage() {
            const name = document.getElementById('pkgName').value.trim();
            const price = document.getElementById('pkgPrice').value;
            if (!name) { showMessage('❌ 请输入套餐名称', 'error'); return; }
            if (!price || parseFloat(price) <= 0) { showMessage('❌ 套餐价格必须大于0', 'error'); return; }
            
            const fd = new FormData(document.getElementById('packageForm'));
            fd.append('action', pkgMode);
            
            fetch('', { method: 'POST', body: new URLSearchParams(fd) })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 600); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function togglePackageStatus(id, status) {
            if (!confirm('确定要' + (status ? '上架' : '下架') + '该套餐吗？')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle_package_status&id=${id}&status=${status}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 500); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        function deletePackage(id) {
            if (!confirm('确定要删除该套餐吗？')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_package&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 500); }
                else showMessage('❌ ' + data.message, 'error');
            });
        }
        
        // ============ 手动充值 ============
        let searchTimer;
        function showRechargeModal() {
            document.getElementById('rechargeForm').reset();
            document.getElementById('memberId').value = '';
            document.getElementById('packageId').value = '';
            document.getElementById('selectedMember').style.display = 'none';
            document.getElementById('memberSearchResult').classList.remove('show');
            document.querySelectorAll('.pkg-option').forEach(o => o.classList.remove('selected'));
            document.getElementById('rechargeModal').classList.add('show');
        }
        function closeRechargeDrawer() { document.getElementById('rechargeModal').classList.remove('show'); }
        
        document.getElementById('memberSearch').addEventListener('input', function() {
            clearTimeout(searchTimer);
            const kw = this.value.trim();
            if (!kw) { document.getElementById('memberSearchResult').classList.remove('show'); return; }
            searchTimer = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=search_member&keyword=${encodeURIComponent(kw)}`
                })
                .then(r => r.json())
                .then(data => {
                    const box = document.getElementById('memberSearchResult');
                    if (data.success && data.data && data.data.length > 0) {
                        box.innerHTML = data.data.map(m => `
                            <div class="member-search-item" onclick='selectMember(${JSON.stringify(m).replace(/'/g, "&#39;")})'>
                                <div class="info">
                                    <span class="name">#${m.id} ${escapeHtml(m.nickname || m.username)}</span>
                                    <span class="meta">${escapeHtml(m.phone || '无手机号')}</span>
                                </div>
                                <span style="color:#1890ff">余额: ¥${m.balance || 0}</span>
                            </div>
                        `).join('');
                        box.classList.add('show');
                    } else {
                        box.innerHTML = '<div class="member-search-item" style="color:#999;cursor:default">未找到会员</div>';
                        box.classList.add('show');
                    }
                });
            }, 300);
        });
        
        function selectMember(m) {
            document.getElementById('memberId').value = m.id;
            document.getElementById('memberSearch').value = '';
            document.getElementById('memberSearchResult').classList.remove('show');
            const sm = document.getElementById('selectedMember');
            sm.style.display = 'block';
            sm.innerHTML = `✅ 已选择：#${m.id} ${escapeHtml(m.nickname || m.username)}（手机：${escapeHtml(m.phone || '无')}，当前余额：¥${m.balance || 0}）`;
        }
        
        function selectPackage(id) {
            document.getElementById('packageId').value = id;
            document.querySelectorAll('.pkg-option').forEach(o => o.classList.toggle('selected', o.dataset.id == id));
        }
        
        function submitRecharge() {
            const memberId = document.getElementById('memberId').value;
            const pkgId = document.getElementById('packageId').value;
            if (!memberId) { showMessage('❌ 请先选择会员', 'error'); return; }
            if (!pkgId) { showMessage('❌ 请选择充值套餐', 'error'); return; }
            
            if (!confirm('确认要给该会员充值此套餐？此操作不可撤销！')) return;
            
            const fd = new FormData(document.getElementById('rechargeForm'));
            fd.append('action', 'manual_recharge');
            
            fetch('', { method: 'POST', body: new URLSearchParams(fd) })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showMessage('✅ ' + data.message, 'success'); setTimeout(() => location.reload(), 1000); }
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
        
        document.getElementById('packageModal').addEventListener('click', e => { if (e.target === e.currentTarget) closePackageDrawer(); });
        document.getElementById('rechargeModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeRechargeDrawer(); });
    </script>
</body>
</html>
