<?php
/**
 * 用户管理页面
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

$pageTitle = '用户管理';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$level = $_GET['level'] ?? '';


// 构建查询条件
$where = ['1=1'];
$params = [];

if ($keyword) {
    $where[] = '(nickname LIKE ? OR phone LIKE ?)';
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if ($level !== '') {
    $where[] = 'level = ?';
    $params[] = $level;
}

$whereStr = implode(' AND ', $where);

try {
    $totalResult = $db->fetchOne("SELECT COUNT(*) as count FROM members WHERE {$whereStr}", $params);
    $total = $totalResult['count'] ?? 0;
    $totalPages = ceil($total / $pageSize);
    $members = $db->fetchAll("SELECT * FROM members WHERE {$whereStr} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
} catch (Exception $e) {
    echo "<pre>查询失败：" . $e->getMessage() . "</pre>";
    die();
}

$levelNames = ['', '普通会员', '银卡会员', '金卡会员', '钻石会员'];
$levelColors = ['', '#8c8c8c', '#d9d9d9', '#faad14', '#722ed1'];
$sourceNames = ['wechat' => '微信', 'app' => 'APP', 'web' => '网页', 'mini_program' => '小程序'];
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
        .search-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .form-item { display: flex; flex-direction: column; gap: 6px; }
        .form-item label { font-size: 13px; color: #666; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 13px; }
        .form-item input[type="date"] { min-width: 140px; }
        .form-actions { grid-column: span 4; display: flex; gap: 8px; margin-top: 8px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #1890ff; color: white; }
        .btn-default { background: #f5f5f5; color: #666; }
        .member-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th, td { padding: 16px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        th { background: #fafafa; font-weight: 600; color: #262626; font-size: 14px; white-space: nowrap; }
        td { font-size: 14px; color: #595959; }
        tr:hover { background: #fafafa; }
        .member-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .member-info { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .member-nickname { font-size: 14px; color: #262626; font-weight: 500; }
        .member-phone { font-size: 12px; color: #8c8c8c; }
        .level-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; }
        .balance-points { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .balance { color: #ff4d4f; font-weight: 600; }
        .points { font-size: 12px; color: #faad14; }
        .total-amount { color: #262626; font-weight: 500; }
        .source-badge { font-size: 12px; color: #595959; }
        .register-time { font-size: 13px; color: #8c8c8c; }
        .action-btns { display: flex; gap: 8px; justify-content: center; }
        .action-btn { 
            padding: 6px 12px; 
            border-radius: 6px; 
            font-size: 13px; 
            cursor: pointer; 
            text-decoration: none; 
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .action-btn.primary { 
            background: linear-gradient(135deg, #1890ff, #096dd9);
            color: white;
            box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
        }
        .action-btn.primary:hover { 
            background: linear-gradient(135deg, #096dd9, #0050b3);
            box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4);
            transform: translateY(-1px);
        }
        .action-btn:active {
            transform: translateY(0);
        }
        .pagination { display: flex; justify-content: center; align-items: center; padding: 20px; gap: 8px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; }
        .pagination a:hover, .pagination .active { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { font-weight: 600; }
        .batch-toolbar { background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: none; align-items: center; justify-content: space-between; }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 12px; }
        
        /* 右侧详情抽屉样式 */
        .detail-drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 2000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .detail-drawer-overlay.show {
            display: block;
            opacity: 1;
        }
        .detail-drawer {
            position: fixed;
            top: 0;
            right: -1170px;
            width: 1170px;
            height: 100vh;
            background: white;
            z-index: 2001;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease;
            overflow-y: auto;
        .history-popup::-webkit-scrollbar { display: none; }
        .history-popup { -ms-overflow-style: none; scrollbar-width: none; }
        }
        .detail-drawer.show {
            right: 0;
        }
        .detail-drawer-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        .detail-drawer-title {
            font-size: 18px;
            font-weight: 600;
            color: #262626;
        }
        .detail-drawer-close {
            width: 32px;
            height: 32px;
            border: none;
            background: #f5f5f5;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
            color: #8c8c8c;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .detail-drawer-close:hover { background: #e6e6e6; color: #262626; }
        .detail-drawer-body { padding: 24px; }
        .detail-section { margin-bottom: 24px; }
        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #262626;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .detail-label {
            font-size: 13px;
            color: #8c8c8c;
        }
        .detail-value {
            font-size: 14px;
            color: #262626;
            font-weight: 500;
        }
        .detail-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0f0f0;
            margin: 0 auto 16px;
            display: block;
        }
        .detail-center { text-align: center; margin-bottom: 24px; }
        .detail-nickname {
            font-size: 20px;
            font-weight: 600;
            color: #262626;
            margin-bottom: 8px;
        }
        .detail-phone {
            font-size: 14px;
            color: #8c8c8c;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-badge.active { background: #f6ffed; color: #237804; }
        .status-badge.inactive { background: #fff2f0; color: #cf1322; }
        
        @media (max-width: 768px) {
            .detail-drawer {
                width: 100%;
                right: -100%;
            }
        }
        /* 编辑按钮 - 默认隐藏，悬停显示 */
        .edit-field .edit-btn { opacity: 0; transition: opacity 0.2s ease; }
        .edit-field:hover .edit-btn { opacity: 1; }
        .edit-field .edit-btn:hover { background-color: #e6f7ff; }
        .duration-btn {
            padding: 8px 4px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            text-align: center;
            transition: all 0.2s;
        }
        .duration-btn:hover { border-color: #ff4d4f; color: #ff4d4f; }
        .duration-btn.active { background: #fff2f0; border-color: #ff4d4f; color: #cf1322; font-weight: 600; }
        .countdown-timer { font-size: 11px; color: #ff4d4f; margin-top: 4px; }
        .countdown-timer .countdown-time { font-weight: 600; }
        .disable-warning { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; color: #ff4d4f; font-size: 12px; }
        .disable-warning .icon { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; background: #ff4d4f; color: white; border-radius: 50%; font-size: 11px; font-weight: 700; line-height: 1; }
        .disable-warning:hover { color: #cf1322; }
        .disable-warning:hover .icon { background: #cf1322; }
        .history-popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); z-index: 3000; justify-content: center; align-items: center; }
        .history-popup-overlay.show { display: flex; }
        .history-popup { background: white; border-radius: 12px; width: 700px; max-width: 90%; max-height: 80vh; overflow-y: auto;
        .history-popup::-webkit-scrollbar { display: none; }
        .history-popup { -ms-overflow-style: none; scrollbar-width: none; } box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .history-popup-header { padding: 20px 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .history-popup-header h3 { margin: 0; font-size: 18px; color: #262626; }
        .history-popup-close { width: 32px; height: 32px; border: none; background: #f5f5f5; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .history-popup-body { padding: 24px; overflow-y: auto; max-height: calc(80vh - 80px); }
        .history-item { padding: 16px; border: 1px solid #f0f0f0; border-radius: 8px; margin-bottom: 12px; }
        .history-item:last-child { margin-bottom: 0; }
        .history-row { display: flex; margin-bottom: 8px; font-size: 14px; }
        .history-row:last-child { margin-bottom: 0; }
        .history-label { width: 100px; color: #8c8c8c; flex-shrink: 0; }
        .history-value { color: #262626; flex: 1; }
        .history-status { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .history-status.active { background: #fff2f0; color: #cf1322; }
        .history-status.unbanned { background: #f6ffed; color: #237804; }
        /* 详情抽屉 Tab 栏 */
        .detail-tabs {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
            padding: 0 24px;
            flex-shrink: 0;
            overflow-x: auto;
        }
        .detail-tabs::-webkit-scrollbar { display: none; }
        .detail-tabs { -ms-overflow-style: none; scrollbar-width: none; }
        .tab-btn {
            padding: 14px 18px;
            font-size: 13px;
            color: #8c8c8c;
            cursor: pointer;
            border: none;
            border-bottom: 2px solid transparent;
            background: none;
            transition: all 0.2s;
            white-space: nowrap;
            font-weight: 500;
            font-family: inherit;
        }
        .tab-btn:hover { color: #262626; }
        .tab-btn.active {
            color: #1890ff;
            border-bottom-color: #1890ff;
            font-weight: 600;
        }
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
                        <label>昵称/手机号</label>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="会员昵称或手机号">
                    </div>
                    <div class="form-item">
                        <label>会员等级</label>
                        <select name="level">
                            <option value="">全部</option>
                            <option value="1" <?= $level === '1' ? 'selected' : '' ?>>普通会员</option>
                            <option value="2" <?= $level === '2' ? 'selected' : '' ?>>银卡会员</option>
                            <option value="3" <?= $level === '3' ? 'selected' : '' ?>>金卡会员</option>
                            <option value="4" <?= $level === '4' ? 'selected' : '' ?>>钻石会员</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <button type="button" class="btn btn-default" onclick="resetSearch()">🔄 重置</button>
                    </div>
                </form>
            </div>
            
            <!-- 批量操作栏 -->
            <div class="batch-toolbar" id="batchToolbar">
                <span>已选择 <strong id="selectedCount">0</strong> 个会员</span>
                <div class="batch-actions">
                    <button class="btn btn-primary" onclick="batchRecharge()">💰 批量充值</button>
            </div>
        </div>
        
        <!-- 操作栏 -->
        <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="action-bar-left">
                <span style="font-size: 15px; font-weight: 600; color: #262626;">用户列表</span>
                <span style="font-size: 13px; color: #8c8c8c; margin-left: 8px;">共 <?= $total ?> 条记录</span>
            </div>
            <div class="action-bar-right" style="display: flex; gap: 8px;">
                <button class="btn" style="background: #1890ff; color: white; padding: 8px 20px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(24,144,255,0.3);" onclick="showAddUserModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    新增用户
                </button>
            </div>
        </div>
        
        <!-- 用户列表 -->
        <div class="member-list">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                <th width="80">头像</th>
                                <th width="200">会员信息</th>
                                <th width="100">会员等级</th>
                                <th width="100">状态</th>
                                <th width="130">余额/积分</th>
                                <th width="120">消费金额</th>
                                <th width="100">注册来源</th>
                                <th width="120">注册时间</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    <div style="font-size: 64px; margin-bottom: 16px;">👤</div>
                                    暂无会员数据
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><input type="checkbox" class="member-checkbox" value="<?= $member['id'] ?>" onchange="updateBatchToolbar()"></td>
                                    <td>
                                        <img src="<?= $member['avatar'] ? htmlspecialchars($member['avatar']) : '../images/default_avatar.png' ?>" alt="头像" class="member-avatar">
                                    </td>
                                    <td>
                                        <div class="member-info">
                                            <div class="member-nickname"><?= htmlspecialchars($member['nickname']) ?></div>
                                            <div class="member-phone"><?= htmlspecialchars($member['phone']) ?></div>
                                            <div style="font-size:11px; color:#8c8c8c;">ID: <?= $member['id'] ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="level-badge" style="background: <?= $levelColors[$member['level']] ?>; color: white;"><?= $levelNames[$member['level']] ?></span>
                                    </td>
                                    <td>
                                        <div class="status-badge <?= $member['status'] ? 'active' : 'inactive' ?>" style="cursor:pointer;" onclick="viewDetail(<?= $member['id'] ?>)">
                                            <?= $member['status'] ? '正常' : '禁用' ?>
                                        </div>
                                        <?php if (!$member['status'] && !empty($member['disabled_until'])): ?>
                                            <div class="countdown-timer" data-until="<?= $member['disabled_until'] ?>">
                                                倒计时: <span class="countdown-time"></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="balance-points">
                                            <div class="balance">¥<?= number_format($member['balance'], 2) ?></div>
                                            <div class="points">🎁 <?= number_format($member['points']) ?> 积分</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="total-amount">¥<?= number_format($member['total_amount'], 2) ?></div>
                                    </td>
                                    <td>
                                        <span class="source-badge"><?= $sourceNames[$member['source']] ?? '未知' ?></span>
                                    </td>
                                    <td>
                                        <span class="register-time"><?= date('Y-m-d', strtotime($member['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn.primary" onclick="viewDetail(<?= $member['id'] ?>)">详情</button>
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
    
    <!-- 用户详情抽屉 -->
    <div class="detail-drawer-overlay" id="detailOverlay" onclick="closeDetailDrawer(event)"></div>
    <div class="detail-drawer" id="detailDrawer">
        <div class="detail-drawer-header">
            <h3 class="detail-drawer-title">用户详情</h3>
            <div style="display:flex;align-items:center;gap:8px;">
                <button id="saveMemberBtn" onclick="saveMember()" style="padding:6px 14px;background:#1890ff;color:white;border:none;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;">保存</button>
                <button class="detail-drawer-close" onclick="closeDetailDrawer()">✕</button>
            </div>
        </div>
        <div class="detail-tabs" id="detailTabs">
            <span class="tab-btn active" data-tab="detail" onclick="switchDetailTab('detail')">用户详情</span>
            <span class="tab-btn" data-tab="activity" onclick="switchDetailTab('activity')">用户动态</span>
            <span class="tab-btn" data-tab="footprints" onclick="switchDetailTab('footprints')">用户足迹</span>
            <span class="tab-btn" data-tab="account" onclick="switchDetailTab('account')">用户账户</span>
        </div>
        <div class="detail-drawer-body" id="drawerBody">
            <!-- 内容动态加载 -->
        </div>
    </div>
    
    <!-- 处罚记录弹窗 -->
    <div class="history-popup-overlay" id="historyPopup" onclick="if(event.target===this)closeHistoryPopup()">
        <div class="history-popup">
            <div class="history-popup-header">
                <h3 id="historyPopupTitle">处罚记录</h3>
                <button class="history-popup-close" onclick="closeHistoryPopup()">x</button>
            </div>
            <div class="history-popup-body" id="historyPopupBody">
                <div style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</div>
            </div>
        </div>
    </div>
    
    <script>
    // 显示处罚记录
    var currentMemberId = null;
    function showDisableHistory(memberId) {
        currentMemberId = memberId;
        document.getElementById('historyPopup').classList.add('show');
        document.getElementById('historyPopupBody').innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</div>';
        
        fetch('member_ajax.php?action=getDisableLogs&member_id=' + memberId)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.code === 0 && res.data.length > 0) {
                    var html = '';
                    res.data.forEach(function(log) {
                        html += '<div class="history-item">' +
                            '<div class="history-row"><span class="history-label">禁用原因</span><span class="history-value">' + (log.disable_reason || '-') + '</span></div>' +
                            '<div class="history-row"><span class="history-label">禁用时间</span><span class="history-value">' + (log.disabled_at || '-') + '</span></div>' +
                            '<div class="history-row"><span class="history-label">禁用时长</span><span class="history-value">' + (log.duration_text || (log.disabled_until ? '至 ' + log.disabled_until : '永久')) + '</span></div>' +
                            '<div class="history-row"><span class="history-label">解除时间</span><span class="history-value">' + (log.unban_time || '-') + '</span></div>' +
                            '<div class="history-row"><span class="history-label">操作人员</span><span class="history-value">' + (log.operator || '系统') + '</span></div>' +
                            
                        '</div>';
                    });
                    document.getElementById('historyPopupTitle').textContent = '处罚记录 (' + res.data.length + ')';
                    document.getElementById('historyPopupBody').innerHTML = html;
                    document.getElementById('disableWarning').style.display = 'inline-flex';
                } else {
                    document.getElementById('historyPopupTitle').textContent = '处罚记录 (0)';
                    document.getElementById('historyPopupBody').innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无处罚记录</div>';
                }
            })
            .catch(function(e) {
                document.getElementById('historyPopupBody').innerHTML = '<div style="text-align:center;padding:40px;color:#ff4d4f;">加载失败</div>';
            });
    }
    function closeHistoryPopup() {
        document.getElementById('historyPopup').classList.remove('show');
    }
    </script>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>

    // 禁用倒计时更新
    function startCountdowns() {
        var timers = document.querySelectorAll('.countdown-timer');
        if (!timers.length) return setTimeout(startCountdowns, 1000);
        
        setInterval(function() {
            timers.forEach(function(el) {
                var target = new Date(el.getAttribute('data-until').replace(' ', 'T')).getTime();
                var now = new Date().getTime();
                var diff = target - now;
                if (diff <= 0) {
                    el.innerHTML = '<span style="color:#52c41a;">\u5df2\u5230\u671f</span>';
                    return;
                }
                var days = Math.floor(diff / (1000*60*60*24));
                var hours = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
                var mins = Math.floor((diff % (1000*60*60)) / (1000*60));
                var secs = Math.floor((diff % (1000*60)) / 1000);
                var timeEl = el.querySelector('.countdown-time');
                if (timeEl) {
                    timeEl.textContent = (days > 0 ? days + '\u5929 ' : '') +
                        String(hours).padStart(2,'0') + ':' +
                        String(mins).padStart(2,'0') + ':' +
                        String(secs).padStart(2,'0');
                }
            });
        }, 1000);
    }
    startCountdowns();

    function toggleAll(checkbox) {
        document.querySelectorAll('.member-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
        });
        updateBatchToolbar();
    }
    
    // 更新批量工具栏
    function updateBatchToolbar() {
        const selected = document.querySelectorAll('.member-checkbox:checked').length;
        const toolbar = document.getElementById('batchToolbar');
        const countEl = document.getElementById('selectedCount');
        
        if (selected > 0) {
            toolbar.classList.add('show');
            countEl.textContent = selected;
        } else {
            toolbar.classList.remove('show');
        }
    }
    
    // 批量充值
    function batchRecharge() {
        const selected = Array.from(document.querySelectorAll('.member-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('请先选择要充值的会员');
            return;
        }
        alert('批量充值功能开发中，已选择 ' + selected.length + ' 个会员');
    }
    
    // 查看详情
    function viewDetail(memberId) {
        console.log('🔵 点击详情，会员 ID:', memberId);
        
        const drawer = document.getElementById('detailDrawer');
        const overlay = document.getElementById('detailOverlay');
        const drawerBody = document.getElementById('drawerBody');
        
        if (!drawer || !overlay || !drawerBody) {
            console.error('❌ 抽屉元素不存在');
            alert('页面元素未加载完成，请刷新页面重试');
            return;
        }
        
        // 显示加载状态
        drawerBody.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</div>';
        
        // 先显示遮罩和抽屉
        overlay.classList.add('show');
        drawer.classList.add('show');
        
        // 锁定背景滚动
        document.body.style.overflow = 'hidden';
        
        console.log('✅ 抽屉已显示，开始加载数据...');
        
        // 获取用户详情
        fetch('member_ajax.php?action=detail&id=' + memberId)
            .then(res => {
                console.log('📡 响应状态:', res.status);
                return res.json();
            })
            .then(data => {
                console.log('📦 收到数据:', data);
                if (data.code === 0) {
                    renderDetail(data.member);
                    switchDetailTab('detail');
                    // 检查禁用历史 - 显示处罚记录警告
                    var warn = document.getElementById('disableWarning');
                    if (warn && data.member && data.member.id) {
                        fetch('member_ajax.php?action=getDisableLogs&member_id=' + data.member.id)
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (warn && res.code === 0 && res.data.length > 0) {
                                    warn.style.display = 'inline-flex';
                                    currentMemberId = data.member.id;
                                }
                            });
                    }
                    // 自动选中已保存的禁用时长
                    setTimeout(function() {
                        var du = data.member.disabled_until;
                        if (du) {
                            var target = new Date(du.replace(' ', 'T')).getTime();
                            var now = new Date().getTime();
                            var diffMin = Math.round((target - now) / 60000);
                            if (diffMin > 0) {
                                var btns = document.querySelectorAll('.duration-btn');
                                var best = null, bestDiff = Infinity;
                                btns.forEach(function(b) {
                                    var m = parseInt(b.getAttribute('data-minutes'));
                                    if (!isNaN(m)) {
                                        var d = Math.abs(m - diffMin);
                                        if (d < bestDiff) { bestDiff = d; best = b; }
                                    } else if (diffMin > 500000) {
                                        best = b;
                                    }
                                });
                                if (best) {
                                    best.classList.add('active');
                                    document.getElementById('editDisabledUntil').value = du;
                                }
                            }
                        }
                    }, 100);
                } else {
                    drawerBody.innerHTML = '<div style="text-align:center;padding:40px;"><div style="color:#ff4d4f;">' + data.message + '</div></div>';
                }
            })
            .catch(err => {
                console.error('❌ 加载失败:', err);
                drawerBody.innerHTML = '<div style="text-align:center;padding:40px;color:#ff4d4f;">加载失败</div>';
            });
    }
    
    // 渲染详情（可编辑模式）
    function renderDetail(member) {
        currentMember = member;
        const drawerBody = document.getElementById('drawerBody');
        const levelNames = ['', '普通会员', '银卡会员', '金卡会员', '钻石会员'];
        const levelColors = ['', '#8c8c8c', '#d9d9d9', '#faad14', '#722ed1'];
        
        // 构建可编辑字段
        function fieldHtml(label, id, type, value, options) {
            // select 类型：显示标签而非原始值
            var displayVal = value || '-';
            if (type === 'select' && options) {
                var matched = options.filter(function(o) { return o.value == value; })[0];
                if (matched) displayVal = matched.label;
            }
            var inputHtml = '';
            if (type === 'select' && options) {
                var opts = options.map(function(o) {
                    var sel = (o.value == value || o.value === value) ? 'selected' : '';
                    return '<option value="' + o.value + '" ' + sel + '>' + o.label + '</option>';
                }).join('');
                inputHtml = '<select class="edit-input" id="' + id + '" style="display:none;width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;">' + opts + '</select>';
            } else if (type === 'textarea') {
                inputHtml = '<textarea class="edit-input" id="' + id + '" style="display:none;width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;resize:vertical;" rows="3">' + (value || '') + '</textarea>';
            } else {
                inputHtml = '<input type="' + type + '" class="edit-input" id="' + id + '" value="' + (value || '') + '" style="display:none;width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;">';
            }
            return '<div class="edit-field">' +
                '<div class="display-row" style="display:flex;align-items:center;gap:8px;">' +
                '<span class="display-value" style="flex:1;">' + displayVal + '</span>' +
                '<button class="edit-btn" onclick="toggleEdit(this)" style="width:18px;height:18px;padding:0;border:none;cursor:pointer;background:url(../images/edit_icon.png) no-repeat center/contain;border-radius:4px;" title="点击编辑"></button>' +
                '</div>' + inputHtml + '</div>';
        }
        
        drawerBody.innerHTML = 
        '<div style="background:white;border:1px solid #f0f0f0;border-radius:12px;padding:20px;margin-bottom:20px;">' +
            // Header: avatar + name/phone + badges
            '<div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">' +
                '<img src="' + (member.avatar || '../images/default_avatar.png') + '" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #f0f0f0;flex-shrink:0;">' +
                '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:18px;font-weight:600;color:#262626;">' + (member.nickname || '未设置') + '</div>' +
                    '<div style="font-size:14px;color:#8c8c8c;margin-top:4px;">' + (member.phone || '未绑定') + '</div>' +
                '</div>' +
                '<div style="text-align:right;flex-shrink:0;">' +
                    '<div><span class="level-badge" style="display:inline-block;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:500;background:' + levelColors[member.level] + ';color:white;">' + levelNames[member.level] + '</span></div>' +
                    '<div style="margin-top:6px;"><span class="status-badge ' + (member.status === 1 ? 'active' : 'inactive') + '" style="display:inline-block;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:500;' + (member.status === 1 ? 'background:#f6ffed;color:#237804;' : 'background:#fff2f0;color:#cf1322;') + '">' + (member.status === 1 ? '正常' : '禁用') + '</span></div>' +
                '</div>' +
            '</div>' +
            // Info row (4 cols) + Social row (3 cols) + Balance row (4 cols)
            '<div style="padding:12px;background:#fafafa;border-radius:8px;">' +
                // Info: gender, birthday, registration, campus
                '<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;padding-bottom:10px;border-bottom:1px solid #f0f0f0;">' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">性别 </span><span style="font-size:14px;color:#262626;font-weight:500;">' + ['未设置','男','女'][member.gender] + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">生日 </span><span style="font-size:14px;color:#262626;font-weight:500;">' + (member.birthday || '-') + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">注册时间 </span><span style="font-size:14px;color:#262626;font-weight:500;">' + ((member.created_at || '').split(' ')[0] || '未知') + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">校区 </span><span style="font-size:14px;color:#262626;font-weight:500;">' + (member.campus || '-') + '</span></div>' +
                '</div>' +
                // Social: following, followers, likes
                '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;padding:10px 0;border-bottom:1px solid #f0f0f0;margin-bottom:10px;text-align:center;">' +
                    '<div><div style="font-size:12px;color:#8c8c8c;">关注</div><div style="font-size:16px;color:#1890ff;font-weight:600;margin-top:2px;cursor:pointer;" onclick="showFollowingList(' + member.id + ')" title="查看关注用户">' + Number(member.following_count || 0) + '</div></div>' +
                    '<div><div style="font-size:12px;color:#8c8c8c;">粉丝</div><div style="font-size:16px;color:#1890ff;font-weight:600;margin-top:2px;">' + Number(member.follower_count || 0) + '</div></div>' +
                    '<div><div style="font-size:12px;color:#8c8c8c;">获赞</div><div style="font-size:16px;color:#1890ff;font-weight:600;margin-top:2px;">' + Number(member.likes_count || 0) + '</div></div>' +
                '</div>' +
                // Balance: balance, points, total amount, orders
                '<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;">' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">余额 </span><span style="font-size:14px;color:#ff4d4f;font-weight:600;">¥' + Number(member.balance || 0).toFixed(2) + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">积分 </span><span style="font-size:14px;color:#faad14;font-weight:600;">' + Number(member.points || 0) + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">累计消费 </span><span style="font-size:14px;color:#262626;font-weight:600;">¥' + Number(member.total_amount || 0).toFixed(2) + '</span></div>' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">订单 </span><span style="font-size:14px;color:#262626;font-weight:600;">' + (member.order_count || 0) + '单</span></div>' +
                '</div>' +
            '</div>' +
        '</div>' +
            
            '<div class="detail-section"><h4 class="section-title">管理员备注</h4>' +
            '<div class="edit-field">' +
            '<div class="display-row" style="display:flex;align-items:flex-start;gap:8px;">' +
            '<span class="display-value" style="flex:1;white-space:pre-wrap;line-height:1.6;">' + (member.remark || '\u6682\u65e0\u5907\u6ce8') + '</span>' +
            '<button class="edit-btn" onclick="toggleEdit(this)" style="width:18px;height:18px;padding:0;border:none;cursor:pointer;background:url(../images/edit_icon.png) no-repeat center/contain;border-radius:4px;flex-shrink:0;" title="点击编辑"></button>' +
            '</div>' +
            '<textarea class="edit-input" id="editRemark" style="display:none;width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;resize:vertical;box-sizing:border-box;" rows="4">' + (member.remark || '') + '</textarea>' +
            '</div></div>' +
            
            '<div class="detail-section">' +
            '<h4 class="section-title">账户状态</h4>' +
            '<span class="disable-warning" id="disableWarning" style="display:none;" onclick="showDisableHistory(' + member.id + ')"><span class="icon">!</span><span>该账户有处罚记录</span></span>' +
            '<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fafafa;border-radius:8px;">' +
            '<div><div style="font-weight:600;color:#262626;">' + (member.status === 1 ? '\u7981\u7528\u8d26\u6237' : '\u542f\u7528\u8d26\u6237') + '</div>' +
            '<div style="font-size:13px;color:#8c8c8c;margin-top:4px;">' + (member.status === 1 ? '关闭后会员无法登录和下单' : '开启后会员可正常使用') + '</div></div>' +
            '<label class="toggle-switch" style="position:relative;display:inline-block;width:50px;height:26px;">' +
            '<input type="checkbox" id="editStatus" ' + (member.status !== 1 ? 'checked' : '') + ' onchange="toggleDisableReason(this)" style="display:none;">' +
            '<span class="toggle-slider" style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:' + (member.status !== 1 ? '#1890ff' : '#d9d9d9') + ';border-radius:26px;transition:0.3s;"></span>' +
            '<span class="toggle-knob" style="position:absolute;height:22px;width:22px;left:2px;bottom:2px;background:white;border-radius:50%;transition:0.3s;' + (member.status !== 1 ? 'transform:translateX(24px);' : '') + '"></span>' +
            '</label></div>' +
            '<div id="disableReasonWrap" style="display:' + (member.status !== 1 ? 'block' : 'none') + ';margin-top:12px;">' +
            '<label style="font-size:13px;color:#8c8c8c;display:block;margin-bottom:6px;">禁用理由</label>' +
            '<textarea id="editDisableReason" placeholder="请输入禁用原因（选填）" style="width:100%;padding:8px 12px;border:1px solid #ff4d4f;border-radius:6px;font-size:14px;resize:vertical;" rows="2">' + (member.disable_reason || '') + '</textarea>' +
            '<div style="margin-top:12px;">' +
            '<label style="font-size:13px;color:#8c8c8c;display:block;margin-bottom:8px;">禁用时长</label>' +
            '<div class="duration-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">' +
            '<button class="duration-btn" data-minutes="10" onclick="selectDuration(this)">10分钟</button>' +
            '<button class="duration-btn" data-minutes="120" onclick="selectDuration(this)">2小时</button>' +
            '<button class="duration-btn" data-minutes="1440" onclick="selectDuration(this)">1天</button>' +
            '<button class="duration-btn" data-minutes="10080" onclick="selectDuration(this)">7天</button>' +
            '<button class="duration-btn" data-minutes="43200" onclick="selectDuration(this)">30天</button>' +
            '<button class="duration-btn" data-minutes="129600" onclick="selectDuration(this)">90天</button>' +
            '<button class="duration-btn" data-minutes="525600" onclick="selectDuration(this)">1年</button>' +
            '<button class="duration-btn" data-value="999" onclick="selectDuration(this)">永久</button>' +
            '</div>' +
            '<input type="hidden" id="editDisabledUntil" value="">' +
            '</div></div></div>' +
            

        '';
    }
    
    var currentMember = null;
    
    // 详情抽屉 Tab 切换
    function switchDetailTab(tabName) {
        // 更新活动 tab
        document.querySelectorAll('#detailTabs .tab-btn').forEach(function(el) {
            el.classList.remove('active');
        });
        var activeTab = document.querySelector('#detailTabs .tab-btn[data-tab="' + tabName + '"]');
        if (activeTab) activeTab.classList.add('active');
        
        var btn = document.getElementById('saveMemberBtn');
        if (btn) btn.style.display = tabName === 'detail' ? '' : 'none';
        
        var body = document.getElementById('drawerBody');
        if (!body) return;
        
        if (!currentMember) {
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无数据</div>';
            return;
        }
        
        if (tabName === 'detail') {
            renderDetail(currentMember);
        } else if (tabName === 'activity') {
            renderActivityTabs();

        } else if (tabName === 'footprints') {
            loadTabData(tabName, '加载浏览记录...', function(id) {
                return fetch('member_ajax.php?action=getFootprints&member_id=' + id).then(function(r) { return r.json(); });
            }, function(data) {
                renderListTab('浏览足迹', data, ['页面', '时间']);
            });
        } else if (tabName === 'account') {
            renderAccountTabs();
        }
    }
    
    // 通用 tab 数据加载
    function loadTabData(tabName, loadingText, fetchFn, renderFn) {
        var body = document.getElementById('drawerBody');
        body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">' + loadingText + '</div>';
        fetchFn(currentMember.id)
            .then(function(res) {
                if (res.code === 0) {
                    renderFn(res);
                } else {
                    body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                }
            })
            .catch(function() {
                body.innerHTML = '<div style="text-align:center;padding:40px;color:#ff4d4f;">加载失败</div>';
            });
    }
    
    // 用户动态 - 帖子/评论子 tab
    function renderActivityTabs() {
        var body = document.getElementById('drawerBody');
        body.innerHTML = 
            '<div style="display:flex;gap:6px;margin-bottom:16px;">' +
                '<span class="sub-tab active" data-subtab="posts" onclick="switchActivityTab(\'posts\')" style="padding:6px 18px;font-size:13px;color:white;cursor:pointer;background:#1890ff;border-radius:6px;border:none;font-weight:500;transition:all 0.2s;user-select:none;">帖子</span>' +
                '<span class="sub-tab" data-subtab="comments" onclick="switchActivityTab(\'comments\')" style="padding:6px 18px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">评论</span>' +
                '<span class="sub-tab" data-subtab="favorites" onclick="switchActivityTab(\'favorites\')" style="padding:6px 18px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">收藏</span>' +
            '</div>' +
            '<div id="activityContent"><div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div></div>';
        switchActivityTab('posts');
    }
    
    function switchActivityTab(subtab) {
        var tabs = document.querySelectorAll('.sub-tab');
        tabs.forEach(function(t) {
            t.style.color = '#595959';
            t.style.background = 'transparent';
            t.style.fontWeight = '400';
        });
        var active = document.querySelector('.sub-tab[data-subtab="' + subtab + '"]');
        if (active) {
            active.style.color = 'white';
            active.style.background = '#1890ff';
            active.style.fontWeight = '500';
            active.style.borderRadius = '6px';
            active.style.border = 'none';
        }
        
        var content = document.getElementById('activityContent');
        if (!content) return;
        
        if (subtab === 'posts') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getMemberPosts&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        renderActivityPosts(res);
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        } else if (subtab === 'comments') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getMemberComments&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        renderActivityComments(res);
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        } else if (subtab === 'favorites') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getFavorites&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        renderActivityFavorites(res);
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        }
    }
    
    function renderActivityPosts(res) {
        var content = document.getElementById('activityContent');
        var list = res.data || [];
        if (list.length === 0) {
            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无帖子记录</div>';
            return;
        }
        var html = '';
        list.forEach(function(item) {
            html += '<div style="padding:14px 16px;border:1px solid #f0f0f0;border-radius:8px;margin-bottom:10px;">' +
                '<div style="font-size:14px;color:#262626;font-weight:500;line-height:1.4;">' + (item.title || '无标题') + '</div>' +
                (item.summary ? '<div style="font-size:13px;color:#8c8c8c;margin-top:6px;line-height:1.5;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">' + item.summary + '</div>' : '') +
                '<div style="font-size:12px;color:#bfbfbf;margin-top:8px;">' + (item.created_at || '') + '</div>' +
            '</div>';
        });
        content.innerHTML = html;
    }
    
    function renderActivityComments(res) {
        var content = document.getElementById('activityContent');
        var list = res.data || [];
        if (list.length === 0) {
            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无评论记录</div>';
            return;
        }
        var html = '';
        list.forEach(function(item) {
            html += '<div style="padding:14px 16px;border:1px solid #f0f0f0;border-radius:8px;margin-bottom:10px;">' +
                '<div style="font-size:14px;color:#262626;line-height:1.5;">' + (item.content || '') + '</div>' +
                '<div style="font-size:12px;color:#bfbfbf;margin-top:8px;">' + (item.created_at || '') + '</div>' +
            '</div>';
        });
        content.innerHTML = html;
    }
    
    function renderActivityFavorites(res) {
        var content = document.getElementById('activityContent');
        var list = res.data || [];
        if (list.length === 0) {
            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无收藏记录</div>';
            return;
        }
        var html = '<table style="width:100%;border-collapse:collapse;">' +
            '<thead><tr>' +
            '<th style="text-align:left;padding:10px 12px;font-size:13px;color:#8c8c8c;border-bottom:1px solid #f0f0f0;font-weight:500;">商品名称</th>' +
            '<th style="text-align:left;padding:10px 12px;font-size:13px;color:#8c8c8c;border-bottom:1px solid #f0f0f0;font-weight:500;">收藏时间</th>' +
            '</tr></thead><tbody>';
        list.forEach(function(item) {
            html += '<tr><td style="padding:10px 12px;font-size:14px;color:#262626;border-bottom:1px solid #f5f5f5;">' + (item.name || item.title || '-') + '</td>' +
                '<td style="padding:10px 12px;font-size:13px;color:#8c8c8c;border-bottom:1px solid #f5f5f5;">' + (item.created_at || item.time || '-') + '</td></tr>';
        });
        html += '</tbody></table>';
        content.innerHTML = html;
    }
    
    // 渲染通用列表 tab（收藏/足迹）
    function renderListTab(title, data, columns) {
        var body = document.getElementById('drawerBody');
        var list = data.data || [];
        if (list.length === 0) {
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无' + title + '</div>';
            return;
        }
        var html = '<table style="width:100%;border-collapse:collapse;">';
        html += '<thead><tr>';
        columns.forEach(function(col) {
            html += '<th style="text-align:left;padding:10px 12px;font-size:13px;color:#8c8c8c;border-bottom:1px solid #f0f0f0;font-weight:500;">' + col + '</th>';
        });
        html += '</tr></thead><tbody>';
        list.forEach(function(item) {
            html += '<tr><td style="padding:10px 12px;font-size:14px;color:#262626;border-bottom:1px solid #f5f5f5;">' + (item.name || item.title || '-') + '</td>' +
                '<td style="padding:10px 12px;font-size:13px;color:#8c8c8c;border-bottom:1px solid #f5f5f5;">' + (item.created_at || item.time || '-') + '</td></tr>';
        });
        html += '</tbody></table>';
        body.innerHTML = html;
    }
    
    // 账户 tab 子 tab
    function renderAccountTabs() {
        var body = document.getElementById('drawerBody');
        body.innerHTML = 
            '<div style="display:flex;gap:6px;margin-bottom:16px;">' +
                '<span class="acct-tab active" data-acctab="account" onclick="switchAccountTab(\'account\')" style="padding:6px 12px;font-size:13px;color:white;cursor:pointer;background:#1890ff;border-radius:6px;border:none;font-weight:500;transition:all 0.2s;user-select:none;">账户</span>' +
                '<span class="acct-tab" data-acctab="orders" onclick="switchAccountTab(\'orders\')" style="padding:6px 12px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">订单</span>' +
                '<span class="acct-tab" data-acctab="coupons" onclick="switchAccountTab(\'coupons\')" style="padding:6px 12px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">优惠券</span>' +
                '<span class="acct-tab" data-acctab="transactions" onclick="switchAccountTab(\'transactions\')" style="padding:6px 12px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">交易记录</span>' +
                '<span class="acct-tab" data-acctab="points" onclick="switchAccountTab(\'points\')" style="padding:6px 12px;font-size:13px;color:#595959;cursor:pointer;background:transparent;border-radius:6px;border:none;font-weight:400;transition:all 0.2s;user-select:none;">积分明细</span>' +
            '</div>' +
            '<div id="accountContent"></div>';
        switchAccountTab('account');
    }
    
    function switchAccountTab(subtab) {
        var tabs = document.querySelectorAll('.acct-tab');
        tabs.forEach(function(t) {
            t.style.color = '#595959';
            t.style.background = 'transparent';
            t.style.fontWeight = '400';
        });
        var active = document.querySelector('.acct-tab[data-acctab="' + subtab + '"]');
        if (active) {
            active.style.color = 'white';
            active.style.background = '#1890ff';
            active.style.fontWeight = '500';
            active.style.borderRadius = '6px';
            active.style.border = 'none';
        }
        
        var content = document.getElementById('accountContent');
        if (!content) return;
        
        if (subtab === 'account') {
            content.innerHTML = '<div class="detail-section" style="margin-bottom:16px;"><h4 class="section-title">账户总览</h4>' +
                '<div class="detail-grid">' +
                '<div class="detail-item"><span class="detail-label">账户余额</span><span class="detail-value" style="color:#ff4d4f;font-size:18px;font-weight:600;">¥' + Number(currentMember.balance || 0).toFixed(2) + '</span></div>' +
                '<div class="detail-item"><span class="detail-label">积分</span><span class="detail-value" style="color:#faad14;font-size:18px;font-weight:600;">' + Number(currentMember.points || 0) + '</span></div>' +
                '<div class="detail-item"><span class="detail-label">累计消费</span><span class="detail-value" style="font-size:18px;font-weight:600;">¥' + Number(currentMember.total_amount || 0).toFixed(2) + '</span></div>' +
                '<div class="detail-item"><span class="detail-label">订单数量</span><span class="detail-value">' + (currentMember.order_count || 0) + ' 单</span></div>' +
                '</div></div>' +
                '<div class="detail-section"><h4 class="section-title">账户流水</h4>' +
                '<div style="text-align:center;padding:30px;color:#8c8c8c;font-size:13px;">充值记录、消费明细等功能开发中</div></div>';
        } else if (subtab === 'orders') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            var allOrders = [];
            fetch('member_ajax.php?action=getMemberOrders&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        allOrders = res.data || [];
                        var expandedOrders = {};
                        renderOrderCards(content, 'all');
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
            
            function renderOrderCards(container, filter) {
                var filtered = allOrders.filter(function(o) {
                    if (filter === 'all') return true;
                    return o.status === filter;
                });
                
                var total = allOrders.length;
                var totalAmt = 0, completed = 0;
                allOrders.forEach(function(o) {
                    totalAmt += parseFloat(o.total_amount || 0);
                    if (o.status === 'completed') completed++;
                });
                
                // Stats
                var html = '<div style="display:flex;gap:10px;margin-bottom:14px;">' +
                    '<div style="flex:1;padding:10px;background:linear-gradient(135deg,#f6ffed,#fff);border-radius:10px;border:1px solid #f0f0f0;text-align:center;">' +
                        '<div style="font-size:11px;color:#8c8c8c;">总订单</div>' +
                        '<div style="font-size:20px;font-weight:700;color:#52c41a;">' + total + '</div></div>' +
                    '<div style="flex:1;padding:10px;background:linear-gradient(135deg,#fff7e6,#fff);border-radius:10px;border:1px solid #f0f0f0;text-align:center;">' +
                        '<div style="font-size:11px;color:#8c8c8c;">总金额</div>' +
                        '<div style="font-size:20px;font-weight:700;color:#fa8c16;">¥' + totalAmt.toFixed(2) + '</div></div>' +
                    '<div style="flex:1;padding:10px;background:linear-gradient(135deg,#f0f5ff,#fff);border-radius:10px;border:1px solid #f0f0f0;text-align:center;">' +
                        '<div style="font-size:11px;color:#8c8c8c;">已成交</div>' +
                        '<div style="font-size:20px;font-weight:700;color:#1890ff;">' + completed + '</div></div>' +
                '</div>' +
                // Filter pills
                '<div style="margin-bottom:12px;display:flex;gap:6px;border-bottom:1px solid #f0f0f0;padding-bottom:10px;flex-wrap:wrap;">' +
                    '<span onclick="filterMemberOrders(this,\'all\')" style="padding:5px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all 0.2s;background:' + (filter === 'all' ? '#1890ff;color:#fff' : '#f5f5f5;color:#595959') + ';">全部(<span>' + total + ')</span></span>' +
                    '<span onclick="filterMemberOrders(this,\'pending\')" style="padding:5px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all 0.2s;background:' + (filter === 'pending' ? '#1890ff;color:#fff' : '#f5f5f5;color:#595959') + ';">待付款(<span>' + allOrders.filter(function(o){return o.status==='pending';}).length + ')</span></span>' +
                    '<span onclick="filterMemberOrders(this,\'paid\')" style="padding:5px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all 0.2s;background:' + (filter === 'paid' ? '#1890ff;color:#fff' : '#f5f5f5;color:#595959') + ';">待发货(<span>' + allOrders.filter(function(o){return o.status==='paid';}).length + ')</span></span>' +
                    '<span onclick="filterMemberOrders(this,\'shipped\')" style="padding:5px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all 0.2s;background:' + (filter === 'shipped' ? '#1890ff;color:#fff' : '#f5f5f5;color:#595959') + ';">已发货(<span>' + allOrders.filter(function(o){return o.status==='shipped';}).length + ')</span></span>' +
                    '<span onclick="filterMemberOrders(this,\'completed\')" style="padding:5px 14px;border-radius:20px;font-size:12px;cursor:pointer;transition:all 0.2s;background:' + (filter === 'completed' ? '#1890ff;color:#fff' : '#f5f5f5;color:#595959') + ';">已完成(<span>' + completed + ')</span></span>' +
                '</div>';
                
                // Order cards
                if (filtered.length === 0) {
                    html += '<div style="text-align:center;padding:30px;color:#8c8c8c;">暂无该状态订单</div>';
                    container.innerHTML = html;
                    return;
                }
                
                var statusLabels = {
                    'pending': '待付款', 'paid': '已付款', 'shipped': '已发货',
                    'completed': '已完成', 'cancelled': '已取消'
                };
                var statusColors = {
                    'pending': '#fff7e6;#d46b08', 'paid': '#f6ffed;#52c41a', 'shipped': '#f9f0ff;#722ed1',
                    'completed': '#f6ffed;#237804', 'cancelled': '#fff2f0;#cf1322'
                };
                
                filtered.forEach(function(item) {
                    var sc = (statusColors[item.status] || '#f5f5f5;#595959').split(';');
                    html += '<div style="background:#fff;border:1px solid #f0f0f0;border-radius:8px;padding:14px 16px;margin-bottom:8px;">' +
                        '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                            '<div style="font-size:13px;color:#262626;font-weight:500;font-family:monospace;">' + (item.order_no || '-') + '</div>' +
                            '<span style="display:inline-block;padding:3px 10px;border-radius:4px;font-size:11px;font-weight:500;background:' + sc[0] + ';color:' + sc[1] + ';">' + (statusLabels[item.status] || item.status) + '</span>' +
                        '</div>' +
                        '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">' +
                            '<div style="font-size:16px;color:#ff4d4f;font-weight:600;">¥' + Number(item.total_amount || 0).toFixed(2) + '</div>' +
                            '<div style="font-size:12px;color:#bfbfbf;">' + (item.created_at || '') + '</div>' +
                        '</div>' +
                        // Expand toggle
                        '<div style="border-top:1px solid #f5f5f5;margin-top:10px;padding-top:8px;display:flex;justify-content:space-between;align-items:center;">' +
                            '<span id="orderToggle_' + item.id + '" onclick="toggleOrderExpand(' + item.id + ')" style="font-size:12px;color:#1890ff;cursor:pointer;">展开详情 ▼</span>' +
                        '</div>' +
                        '<div id="orderItems_' + item.id + '" style="display:none;padding:8px 0 0;"></div>' +
                    '</div>';
                });
                container.innerHTML = html;
            }
            
            window.toggleOrderExpand = function(orderId) {
                var container = document.getElementById('orderItems_' + orderId);
                var toggle = document.getElementById('orderToggle_' + orderId);
                if (!container) return;
                if (container.style.display === 'block') {
                    container.style.display = 'none';
                    if (toggle) toggle.innerHTML = '展开详情 ▼';
                    return;
                }
                container.style.display = 'block';
                if (toggle) toggle.innerHTML = '收起详情 ▲';
                if (container.dataset.loaded !== '1') {
                    container.innerHTML = '<div style="text-align:center;padding:10px;color:#8c8c8c;font-size:12px;">加载中...</div>';
                    fetch('member_ajax.php?action=getOrderItems&order_id=' + orderId)
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (container) {
                                var items = res.data || [];
                                if (items.length === 0) {
                                    container.innerHTML = '<div style="text-align:center;padding:10px;color:#8c8c8c;font-size:12px;">暂无商品信息</div>';
                                    return;
                                }
                                var html = '<div style="background:#fafafa;border-radius:6px;padding:10px;">';
                                items.forEach(function(item) {
                                    html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f0f0f0;">' +
                                        '<span style="font-size:13px;color:#262626;">' + (item.product_name || '-') + '</span>' +
                                        '<span style="font-size:13px;color:#595959;">¥' + Number(item.price || 0).toFixed(2) + ' x ' + (item.quantity || 0) + '</span></div>';
                                });
                                html += '</div>';
                                container.innerHTML = html;
                                container.dataset.loaded = '1';
                            }
                        })
                        .catch(function() {
                            container.innerHTML = '<div style="text-align:center;padding:10px;color:#ff4d4f;font-size:12px;">加载失败</div>';
                        });
                }
            };
            
            window.filterMemberOrders = function(el, filter) {
                if (!el) return;
                var container = document.getElementById('accountContent');
                renderOrderCards(container, filter);
            };
        } else if (subtab === 'coupons') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getMemberCoupons&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        var list = res.data || [];
                        if (list.length === 0) {
                            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无优惠券记录</div>';
                            return;
                        }
                        var html = '';
                        list.forEach(function(item) {
                            var st = {'unused':'未使用','used':'已使用','expired':'已过期'};
                            var sc = {'unused':'#52c41a','used':'#8c8c8c','expired':'#ff4d4f'};
                            var sb = {'unused':'#f6ffed','used':'#f5f5f5','expired':'#fff2f0'};
                            var s = st[item.status] || item.status;
                            html += '<div style="background:#fff;border:1px solid #f0f0f0;border-radius:8px;padding:14px 16px;margin-bottom:8px;">' +
                                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                                    '<div style="font-size:14px;color:#262626;font-weight:500;">' + (item.coupon_name || '-') + '</div>' +
                                    '<span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;background:' + (sb[item.status]||'#f5f5f5') + ';color:' + (sc[item.status]||'#8c8c8c') + ';">' + s + '</span>' +
                                '</div>' +
                                '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">' +
                                    '<div style="font-size:16px;color:#ff4d4f;font-weight:600;">¥' + Number(item.coupon_value || 0).toFixed(2) + '</div>' +
                                    '<div style="font-size:12px;color:#bfbfbf;">至 ' + (item.valid_until || '-') + '</div>' +
                                '</div>' +
                                '<div style="font-size:12px;color:#8c8c8c;margin-top:4px;">' + (Number(item.min_amount) > 0 ? '满¥' + Number(item.min_amount).toFixed(2) + '可用' : '无门槛') + '</div>' +
                            '</div>';
                        });
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        } else if (subtab === 'transactions') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getMemberTransactions&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        var list = res.data || [];
                        if (list.length === 0) {
                            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无交易记录</div>';
                            return;
                        }
                        var html = '';
                        var tl = {'payment':'消费','recharge':'充值','refund':'退款'};
                        var tlbg = {'payment':'#fff7e6','recharge':'#f6ffed','refund':'#fff2f0'};
                        var tlclr = {'payment':'#d46b08','recharge':'#52c41a','refund':'#cf1322'};
                        list.forEach(function(item) {
                            var tp = tl[item.type] || item.type;
                            html += '<div style="background:#fff;border:1px solid #f0f0f0;border-radius:6px;padding:10px 14px;margin-bottom:5px;display:flex;align-items:center;gap:10px;">' +
                                '<span style="flex-shrink:0;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500;background:' + (tlbg[item.type]||'#f5f5f5') + ';color:' + (tlclr[item.type]||'#595959') + ';">' + tp + '</span>' +
                                '<span style="flex-shrink:0;font-size:14px;color:#ff4d4f;font-weight:600;min-width:70px;text-align:right;">' + (item.type === 'refund' ? '+' : '') + '¥' + Number(item.amount || 0).toFixed(2) + '</span>' +
                                '<span style="flex:1;font-size:13px;color:#595959;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (item.payee && item.payee !== '-' ? item.payee : item.description || '') + '</span>' +
                                '<span style="flex-shrink:0;font-size:12px;color:#8c8c8c;white-space:nowrap;">' + (item.payment_method || '') + '</span>' +
                                '<span style="flex-shrink:0;font-size:12px;color:#bfbfbf;white-space:nowrap;">' + (item.created_at || '').substr(0,10) + '</span>' +
                            '</div>';
                        });
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        } else if (subtab === 'points') {
            content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">加载中...</div>';
            fetch('member_ajax.php?action=getMemberPoints&member_id=' + currentMember.id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 0) {
                        var list = res.data || [];
                        if (list.length === 0) {
                            content.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无积分明细</div>';
                            return;
                        }
                        var html = '';
                        list.forEach(function(item) {
                            var ch = Number(item.change || 0);
                            var color = ch >= 0 ? '#52c41a' : '#ff4d4f';
                            var prefix = ch >= 0 ? '+' : '';
                            html += '<div style="background:#fff;border:1px solid #f0f0f0;border-radius:8px;padding:14px 16px;margin-bottom:8px;">' +
                                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                                    '<div style="font-size:18px;font-weight:700;color:' + color + ';">' + prefix + ch + '</div>' +
                                    '<span style="font-size:12px;color:#bfbfbf;">' + (item.created_at || '') + '</span>' +
                                '</div>' +
                                '<div style="font-size:14px;color:#262626;margin-top:6px;line-height:1.4;">' + (item.description || '-') + '</div>' +
                                '<div style="font-size:12px;color:#8c8c8c;margin-top:4px;">积分余额: ' + (item.balance_after || '-') + '</div>' +
                            '</div>';
                        });
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<div style="text-align:center;padding:30px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                    }
                })
                .catch(function() {
                    content.innerHTML = '<div style="text-align:center;padding:30px;color:#ff4d4f;">加载失败</div>';
                });
        }
    }
    
    // 切换编辑状态
    function toggleEdit(btn) {
        var field = btn.closest('.edit-field');
        if (!field) return;
        var display = field.querySelector('.display-value');
        var displayRow = field.querySelector('.display-row');
        var input = field.querySelector('.edit-input');
        if (!input) return;
        
        if (input.style.display === 'none') {
            displayRow.style.display = 'none';
            input.style.display = 'block';
            btn.textContent = '\u2713';
            btn.title = '\u786e\u8ba4';
            btn.style.color = '#52c41a';
        } else {
            display.textContent = input.tagName === 'SELECT' ? input.options[input.selectedIndex].text : input.value;
            displayRow.style.display = 'flex';
            input.style.display = 'none';
            btn.textContent = '\u270f\ufe0f';
            btn.title = '\u70b9\u51fb\u7f16\u8f91';
            btn.style.color = '#8c8c8c';
        }
    }
    
    // 禁用开关切换
    function toggleDisableReason(cb) {
        var wrap = document.getElementById('disableReasonWrap');
        var slider = cb.parentElement.querySelector('.toggle-slider');
        var knob = cb.parentElement.querySelector('.toggle-knob');
        if (cb.checked) {
            wrap.style.display = 'block';
            slider.style.background = '#ff4d4f';
            knob.style.transform = 'translateX(24px)';
        } else {
            wrap.style.display = 'none';
            slider.style.background = '#1890ff';
            knob.style.transform = '';
        }
    }
    
    var selectedDuration = null;
    function selectDuration(btn) {
        var btns = document.querySelectorAll('.duration-btn');
        btns.forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        selectedDuration = btn.getAttribute('data-minutes') || btn.getAttribute('data-value');
        if (selectedDuration === '999') {
            document.getElementById('editDisabledUntil').value = '9999-12-31 23:59:59';
        } else {
            var now = new Date();
            now.setMinutes(now.getMinutes() + parseInt(selectedDuration));
            document.getElementById('editDisabledUntil').value = now.getFullYear() + '-' +
                String(now.getMonth()+1).padStart(2,'0') + '-' +
                String(now.getDate()).padStart(2,'0') + ' ' +
                String(now.getHours()).padStart(2,'0') + ':' +
                String(now.getMinutes()).padStart(2,'0') + ':' +
                String(now.getSeconds()).padStart(2,'0');
        }
    }
    
    // 保存修改
    function saveMember() {
        if (!currentMember) return;
        var data = new URLSearchParams();
        data.append('action', 'update');
        data.append('id', currentMember.id);
        
        // 收集输入值
        var fields = [
            ['editNickname', 'nickname'],
            ['editPhone', 'phone'],
            ['editGender', 'gender'],
            ['editBirthday', 'birthday'],
            ['editLevel', 'level'],
            ['editAvatar', 'avatar'],
            ['editBalance', 'balance'],
            ['editPoints', 'points'],
            ['editRemark', 'remark']
        ];
        fields.forEach(function(pair) {
            var el = document.getElementById(pair[0]);
            if (el) data.append(pair[1], el.value);
        });
        
        // 状态开关
        var statusCb = document.getElementById('editStatus');
        var newStatus = statusCb && statusCb.checked ? 0 : 1;
        data.append('status', newStatus);
        
        // 禁用时长
        var disabledUntilEl = document.getElementById('editDisabledUntil');
        if (disabledUntilEl && disabledUntilEl.value) {
            data.append('disabled_until', disabledUntilEl.value);
        }
        
        // 禁用理由 - 单独字段存储
        var reasonEl = document.getElementById('editDisableReason');
        data.append('disable_reason', reasonEl ? reasonEl.value : '');
        
        btn = document.querySelector('.detail-drawer-body button:last-of-type');
        if (btn) { btn.textContent = '\u4fdd\u5b58\u4e2d...'; btn.disabled = true; }
        
        fetch('member_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: data.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.code === 0) {
                alert('\u4fdd\u5b58\u6210\u529f');
                location.reload();
            } else {
                alert('\u4fdd\u5b58\u5931\u8d25\uff1a' + res.message);
                if (btn) { btn.textContent = '\ud83d\udcbe \u4fdd\u5b58\u4fee\u6539'; btn.disabled = false; }
            }
        })
        .catch(function(e) {
            alert('\u7f51\u7edc\u9519\u8bef\uff1a' + e.message);
            if (btn) { btn.textContent = '\ud83d\udcbe \u4fdd\u5b58\u4fee\u6539'; btn.disabled = false; }
        });
    }

    // 关闭详情抽屉
    function closeDetailDrawer(event) {
        const drawer = document.getElementById('detailDrawer');
        const overlay = document.getElementById('detailOverlay');
        
        if (!event || event.target === overlay) {
            drawer.classList.remove('show');
            overlay.classList.remove('show');
            // 恢复背景滚动
            document.body.style.overflow = '';
        }
    }
    
    // 键盘 ESC 关闭抽屉
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailDrawer();
        }
    });
    
    function showFollowingList(memberId) {
        var overlay = document.getElementById('followingOverlay');
        var body = document.getElementById('followingBody');
        overlay.classList.add('show');
        body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</div>';
        fetch('member_ajax.php?action=getFollowing&member_id=' + memberId)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.code === 0) {
                    var list = res.data || [];
                    if (list.length === 0) {
                        body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">暂无关注用户</div>';
                        return;
                    }
                    var html = '';
                    list.forEach(function(item) {
                        var avatarUrl = item.avatar || '../images/avatar-placeholder.svg';
                        html += '<div class="follow-item">' +
                            '<img src="' + avatarUrl + '" alt="头像" class="follow-avatar">' +
                            '<div class="follow-info">' +
                            '<div class="follow-name">' + (item.nickname || item.name || '用户') + '</div>' +
                            '<div class="follow-time">关注于 ' + (item.created_at || '') + '</div>' +
                            '</div></div>';
                    });
                    body.innerHTML = html;
                } else {
                    body.innerHTML = '<div style="text-align:center;padding:40px;color:#8c8c8c;">' + (res.message || '暂无数据') + '</div>';
                }
            })
            .catch(function() {
                body.innerHTML = '<div style="text-align:center;padding:40px;color:#ff4d4f;">加载失败</div>';
            });
    }
    function closeFollowingPopup() {
        document.getElementById('followingOverlay').classList.remove('show');
    }
    
    function resetSearch() {
        window.location.href = '?';
    }
    </script>
<?php include 'add_member_modal.php'; ?>
    <!-- 关注用户列表弹窗 -->
    <div class="history-popup-overlay" id="followingOverlay" onclick="if(event.target===this)closeFollowingPopup()">
        <div class="history-popup">
            <div class="history-popup-header">
                <h3>关注用户</h3>
                <button class="history-popup-close" onclick="closeFollowingPopup()">&#10005;</button>
            </div>
            <div class="history-popup-body" id="followingBody">
                <div style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</div>
            </div>
        </div>
    </div>
    
</body>
</html>
