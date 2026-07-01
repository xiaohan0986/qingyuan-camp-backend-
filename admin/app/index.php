<?php
/**
 * 应用中心 - 数据统计
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '应用中心数据统计';

// 处理 AJAX 请求（数据接口）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if ($_POST['action'] === 'refresh') {
            $partnerTotal = $db->fetchOne("SELECT COUNT(*) AS c FROM app_partners")['c'] ?? 0;
            $partnerActive = $db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE status = 1")['c'] ?? 0;
            $partnerPending = $db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE status = 0")['c'] ?? 0;

            $withdrawTotal = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 1")['a'] ?? 0;
            $withdrawPending = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 0")['a'] ?? 0;
            $withdrawPendingCount = $db->fetchOne("SELECT COUNT(*) AS c FROM app_withdraws WHERE status = 0")['c'] ?? 0;

            $monthNew = $db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['c'] ?? 0;
            $monthCommission = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_earnings WHERE type = 'commission' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['a'] ?? 0;
            $monthWithdraw = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 1 AND DATE_FORMAT(handled_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['a'] ?? 0;
            $totalCommission = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_earnings WHERE type = 'commission'")['a'] ?? 0;
            $totalInvite = $db->fetchOne("SELECT COALESCE(SUM(invite_count), 0) AS c FROM app_partners")['c'] ?? 0;

            $quitPending = $db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 0")['c'] ?? 0;

            // 应用数据
            $apps = $db->fetchAll("SELECT id, name, code, icon, status, sort FROM apps ORDER BY sort ASC, id ASC");
            $appActive = $db->fetchOne("SELECT COUNT(*) AS c FROM apps WHERE status = 1")['c'] ?? 0;
            $appTotal = $db->fetchOne("SELECT COUNT(*) AS c FROM apps")['c'] ?? 0;

            // 近30天合伙人数趋势
            $partnerTrend = $db->fetchAll("
                SELECT DATE(created_at) AS d, COUNT(*) AS c
                FROM app_partners
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(created_at)
                ORDER BY d ASC
            ");

            // 近30天提现趋势
            $withdrawTrend = $db->fetchAll("
                SELECT DATE(created_at) AS d, COALESCE(SUM(amount), 0) AS a, COUNT(*) AS c
                FROM app_withdraws
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(created_at)
                ORDER BY d ASC
            ");

            // 合伙人等级分布
            $levelDist = $db->fetchAll("
                SELECT level, COUNT(*) AS c
                FROM app_partners
                WHERE status = 1
                GROUP BY level
                ORDER BY c DESC
            ");

            // 近期活动
            $recentPartners = $db->fetchAll("SELECT id, name, level, created_at, status FROM app_partners ORDER BY created_at DESC LIMIT 5");
            $recentWithdraws = $db->fetchAll("
                SELECT w.id, w.amount, w.status, w.created_at, p.name AS partner_name
                FROM app_withdraws w
                LEFT JOIN app_partners p ON w.partner_id = p.id
                ORDER BY w.created_at DESC LIMIT 5
            ");
            $recentQuits = $db->fetchAll("
                SELECT q.id, q.status, q.reason, q.created_at, p.name AS partner_name
                FROM app_quits q
                LEFT JOIN app_partners p ON q.partner_id = p.id
                ORDER BY q.created_at DESC LIMIT 5
            ");

            echo json_encode([
                'success' => true,
                'data' => [
                    'partner_total' => intval($partnerTotal),
                    'partner_active' => intval($partnerActive),
                    'partner_pending' => intval($partnerPending),
                    'withdraw_total' => floatval($withdrawTotal),
                    'withdraw_pending' => floatval($withdrawPending),
                    'withdraw_pending_count' => intval($withdrawPendingCount),
                    'month_new' => intval($monthNew),
                    'month_commission' => floatval($monthCommission),
                    'month_withdraw' => floatval($monthWithdraw),
                    'total_commission' => floatval($totalCommission),
                    'total_invite' => intval($totalInvite),
                    'quit_pending' => intval($quitPending),
                    'apps' => $apps,
                    'app_active' => intval($appActive),
                    'app_total' => intval($appTotal),
                    'partner_trend' => $partnerTrend,
                    'withdraw_trend' => $withdrawTrend,
                    'level_dist' => $levelDist,
                    'recent_partners' => $recentPartners,
                    'recent_withdraws' => $recentWithdraws,
                    'recent_quits' => $recentQuits,
                ]
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '加载失败：' . $e->getMessage()]);
        exit;
    }
}

// 初始加载数据
$initial = [
    'partner_total' => 0,
    'partner_active' => 0,
    'partner_pending' => 0,
    'withdraw_total' => 0,
    'withdraw_pending' => 0,
    'withdraw_pending_count' => 0,
    'month_new' => 0,
    'month_commission' => 0,
    'month_withdraw' => 0,
    'total_commission' => 0,
    'total_invite' => 0,
    'quit_pending' => 0,
    'apps' => [],
    'app_active' => 0,
    'app_total' => 0,
    'partner_trend' => [],
    'withdraw_trend' => [],
    'level_dist' => [],
    'recent_partners' => [],
    'recent_withdraws' => [],
    'recent_quits' => [],
];

try {
    $initial['partner_total'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_partners")['c'] ?? 0);
    $initial['partner_active'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE status = 1")['c'] ?? 0);
    $initial['partner_pending'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE status = 0")['c'] ?? 0);
    $initial['withdraw_total'] = floatval($db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 1")['a'] ?? 0);
    $initial['withdraw_pending'] = floatval($db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 0")['a'] ?? 0);
    $initial['withdraw_pending_count'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_withdraws WHERE status = 0")['c'] ?? 0);
    $initial['month_new'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_partners WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['c'] ?? 0);
    $initial['month_commission'] = floatval($db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_earnings WHERE type = 'commission' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['a'] ?? 0);
    $initial['month_withdraw'] = floatval($db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_withdraws WHERE status = 1 AND DATE_FORMAT(handled_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")['a'] ?? 0);
    $initial['total_commission'] = floatval($db->fetchOne("SELECT COALESCE(SUM(amount), 0) AS a FROM app_earnings WHERE type = 'commission'")['a'] ?? 0);
    $initial['total_invite'] = intval($db->fetchOne("SELECT COALESCE(SUM(invite_count), 0) AS c FROM app_partners")['c'] ?? 0);
    $initial['quit_pending'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM app_quits WHERE status = 0")['c'] ?? 0);
    $initial['apps'] = $db->fetchAll("SELECT id, name, code, icon, status, sort FROM apps ORDER BY sort ASC, id ASC");
    $initial['app_active'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM apps WHERE status = 1")['c'] ?? 0);
    $initial['app_total'] = intval($db->fetchOne("SELECT COUNT(*) AS c FROM apps")['c'] ?? 0);
    $initial['partner_trend'] = $db->fetchAll("
        SELECT DATE(created_at) AS d, COUNT(*) AS c
        FROM app_partners
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(created_at)
        ORDER BY d ASC
    ");
    $initial['withdraw_trend'] = $db->fetchAll("
        SELECT DATE(created_at) AS d, COALESCE(SUM(amount), 0) AS a, COUNT(*) AS c
        FROM app_withdraws
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(created_at)
        ORDER BY d ASC
    ");
    $initial['level_dist'] = $db->fetchAll("
        SELECT level, COUNT(*) AS c
        FROM app_partners
        WHERE status = 1
        GROUP BY level
        ORDER BY c DESC
    ");
    $initial['recent_partners'] = $db->fetchAll("SELECT id, name, level, created_at, status FROM app_partners ORDER BY created_at DESC LIMIT 5");
    $initial['recent_withdraws'] = $db->fetchAll("
        SELECT w.id, w.amount, w.status, w.created_at, p.name AS partner_name
        FROM app_withdraws w
        LEFT JOIN app_partners p ON w.partner_id = p.id
        ORDER BY w.created_at DESC LIMIT 5
    ");
    $initial['recent_quits'] = $db->fetchAll("
        SELECT q.id, q.status, q.reason, q.created_at, p.name AS partner_name
        FROM app_quits q
        LEFT JOIN app_partners p ON q.partner_id = p.id
        ORDER BY q.created_at DESC LIMIT 5
    ");
} catch (Exception $e) {
    // 忽略错误，使用默认值
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
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .toolbar-actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .stat-card .icon-bg {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 80px;
            opacity: 0.12;
            line-height: 1;
        }
        .stat-card .label {
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 8px;
        }
        .stat-card .value {
            font-size: 30px;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-card .sub {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 6px;
        }
        .stat-card.green .value { background: linear-gradient(135deg, #52c41a, #95de64); -webkit-background-clip: text; background-clip: text; }
        .stat-card.orange .value { background: linear-gradient(135deg, #fa8c16, #ffc069); -webkit-background-clip: text; background-clip: text; }
        .stat-card.red .value { background: linear-gradient(135deg, #ff4d4f, #ff7875); -webkit-background-clip: text; background-clip: text; }
        .stat-card.cyan .value { background: linear-gradient(135deg, #13c2c2, #5cdbd3); -webkit-background-clip: text; background-clip: text; }

        .section-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .section-title .icon {
            margin-right: 8px;
        }

        .row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 1024px) {
            .row-2col { grid-template-columns: 1fr; }
        }

        .chart-area {
            height: 240px;
            display: flex;
            align-items: flex-end;
            gap: 4px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        .chart-bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            position: relative;
        }
        .chart-bar {
            width: 100%;
            max-width: 18px;
            background: var(--primary-gradient);
            border-radius: 4px 4px 0 0;
            transition: all 0.3s;
            min-height: 2px;
            position: relative;
        }
        .chart-bar:hover {
            background: linear-gradient(135deg, #40a9ff, #1890ff);
            transform: scaleY(1.05);
        }
        .chart-bar.withdraw {
            background: linear-gradient(135deg, #fa8c16, #ffc069);
        }
        .chart-bar.withdraw:hover {
            background: linear-gradient(135deg, #ffc069, #fa8c16);
        }
        .chart-bar-tip {
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: var(--text-primary);
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 10;
        }
        .chart-bar:hover .chart-bar-tip { opacity: 1; }

        .chart-xaxis {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            font-size: 10px;
            color: var(--text-secondary);
        }
        .chart-xaxis span {
            flex: 1;
            text-align: center;
        }

        .chart-legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        .chart-legend .dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            vertical-align: middle;
            margin-right: 6px;
        }
        .chart-legend .dot.partners { background: var(--primary-gradient); }
        .chart-legend .dot.withdraw { background: linear-gradient(135deg, #fa8c16, #ffc069); }

        .level-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .level-bar .name {
            width: 100px;
            font-size: 13px;
            color: var(--text-primary);
        }
        .level-bar .bar-track {
            flex: 1;
            height: 20px;
            background: #f5f5f5;
            border-radius: 10px;
            overflow: hidden;
        }
        .level-bar .bar-fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .level-bar .count {
            font-size: 13px;
            color: var(--text-primary);
            font-weight: 600;
            min-width: 36px;
            text-align: right;
        }

        .recent-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border-color);
            font-size: 13px;
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-item .info { flex: 1; }
        .recent-item .title { color: var(--text-primary); font-weight: 500; }
        .recent-item .meta { color: var(--text-secondary); font-size: 12px; margin-top: 2px; }
        .recent-item .amount { color: #fa8c16; font-weight: 600; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; }
        .badge-success { background: #f6ffed; color: #237804; }
        .badge-warning { background: #fff7e6; color: #d46b08; }
        .badge-danger { background: #fff2f0; color: #cf1322; }
        .badge-primary { background: #e6f7ff; color: #096dd9; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-default { background: #f5f5f5; color: #595959; }
        .btn-default:hover { background: #e6e6e6; }
        .btn-outline { background: white; border: 2px solid var(--border-color); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary-color); color: var(--primary-color); }

        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .app-item {
            padding: 16px;
            background: linear-gradient(135deg, #fafbff, #f5f7ff);
            border-radius: 12px;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .app-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        .app-item.disabled { opacity: 0.5; }
        .app-item .icon { font-size: 32px; margin-bottom: 8px; }
        .app-item .name { font-size: 13px; font-weight: 500; color: var(--text-primary); }
        .app-item .code { font-size: 11px; color: var(--text-secondary); font-family: 'SF Mono', Monaco, monospace; margin-top: 2px; }

        .empty-mini {
            text-align: center;
            color: var(--text-secondary);
            padding: 24px 0;
            font-size: 13px;
        }

        .alert { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 240px; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 14px; }
        .alert-success { background: #f6ffed; color: #237804; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffccc7; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="content-wrapper">
            <div class="toolbar">
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">📊 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-default" onclick="loadAll()">🔄 刷新数据</button>
                    <a class="btn btn-outline" href="app_partner.php">👥 合伙人管理</a>
                    <a class="btn btn-outline" href="app_withdraw.php">💰 提现审核</a>
                </div>
            </div>

            <!-- 顶部统计卡片 -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="icon-bg">👥</div>
                    <div class="label">总合伙人数</div>
                    <div class="value"><?= number_format($initial['partner_total']) ?></div>
                    <div class="sub">活跃：<?= $initial['partner_active'] ?> · 待审核：<?= $initial['partner_pending'] ?></div>
                </div>
                <div class="stat-card green">
                    <div class="icon-bg">💰</div>
                    <div class="label">总提现金额</div>
                    <div class="value">¥<?= number_format($initial['withdraw_total'], 2) ?></div>
                    <div class="sub">累计佣金：¥<?= number_format($initial['total_commission'], 2) ?></div>
                </div>
                <div class="stat-card orange">
                    <div class="icon-bg">⏳</div>
                    <div class="label">待审核提现</div>
                    <div class="value">¥<?= number_format($initial['withdraw_pending'], 2) ?></div>
                    <div class="sub">共 <?= $initial['withdraw_pending_count'] ?> 笔待处理</div>
                </div>
                <div class="stat-card cyan">
                    <div class="icon-bg">📈</div>
                    <div class="label">本月新增合伙人</div>
                    <div class="value"><?= $initial['month_new'] ?></div>
                    <div class="sub">本月佣金：¥<?= number_format($initial['month_commission'], 2) ?></div>
                </div>
            </div>

            <!-- 第二行：图表 + 等级分布 -->
            <div class="row-2col">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><span class="icon">📈</span>近 30 天趋势</div>
                    </div>
                    <div class="chart-area" id="chartPartner"></div>
                    <div class="chart-xaxis" id="chartPartnerX"></div>
                    <div class="chart-legend">
                        <span><span class="dot partners"></span>新增合伙人</span>
                        <span><span class="dot withdraw"></span>提现金额</span>
                    </div>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><span class="icon">🏆</span>合伙人等级分布</div>
                    </div>
                    <div id="levelDistBox">
                        <?php if (empty($initial['level_dist'])): ?>
                            <div class="empty-mini">暂无活跃合伙人数据</div>
                        <?php else:
                            $maxCount = max(array_column($initial['level_dist'], 'c'));
                            foreach ($initial['level_dist'] as $ld):
                                $pct = $maxCount > 0 ? round($ld['c'] / $maxCount * 100) : 0;
                        ?>
                            <div class="level-bar">
                                <div class="name"><?= htmlspecialchars($ld['level']) ?></div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                                <div class="count"><?= $ld['c'] ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- 第三行：近期活动 -->
            <div class="row-2col">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><span class="icon">👥</span>最新合伙人申请</div>
                        <a class="btn btn-default" href="app_partner.php" style="font-size:12px;padding:4px 12px;">查看全部 →</a>
                    </div>
                    <ul class="recent-list" id="recentPartnerBox">
                        <?php if (empty($initial['recent_partners'])): ?>
                            <li class="empty-mini">暂无合伙人申请</li>
                        <?php else: foreach ($initial['recent_partners'] as $p): ?>
                            <li class="recent-item">
                                <div class="info">
                                    <div class="title"><?= htmlspecialchars($p['name']) ?> · <span style="font-weight:normal;color:#8c8c8c;"><?= htmlspecialchars($p['level']) ?></span></div>
                                    <div class="meta"><?= $p['created_at'] ?></div>
                                </div>
                                <div>
                                    <?php if ($p['status'] == 0): ?>
                                        <span class="badge badge-warning">待审核</span>
                                    <?php elseif ($p['status'] == 1): ?>
                                        <span class="badge badge-success">已通过</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">已拒绝</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><span class="icon">💰</span>最新提现申请</div>
                        <a class="btn btn-default" href="app_withdraw.php" style="font-size:12px;padding:4px 12px;">查看全部 →</a>
                    </div>
                    <ul class="recent-list" id="recentWithdrawBox">
                        <?php if (empty($initial['recent_withdraws'])): ?>
                            <li class="empty-mini">暂无提现申请</li>
                        <?php else: foreach ($initial['recent_withdraws'] as $w): ?>
                            <li class="recent-item">
                                <div class="info">
                                    <div class="title"><?= htmlspecialchars($w['partner_name'] ?? '已删除') ?></div>
                                    <div class="meta"><?= $w['created_at'] ?></div>
                                </div>
                                <div>
                                    <span class="amount">¥<?= number_format($w['amount'], 2) ?></span>
                                    <?php if ($w['status'] == 0): ?>
                                        <span class="badge badge-warning">待审核</span>
                                    <?php elseif ($w['status'] == 1): ?>
                                        <span class="badge badge-success">已通过</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">已拒绝</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>

            <!-- 应用列表 -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title"><span class="icon">📱</span>应用中心 (<?= $initial['app_active'] ?>/<?= $initial['app_total'] ?> 启用)</div>
                </div>
                <?php if (empty($initial['apps'])): ?>
                    <div class="empty-mini">暂无应用数据，请先在 apps 表中添加应用</div>
                <?php else: ?>
                    <div class="app-grid">
                        <?php foreach ($initial['apps'] as $app): ?>
                            <div class="app-item <?= $app['status'] != 1 ? 'disabled' : '' ?>">
                                <div class="icon"><?= $app['icon'] ?: '📦' ?></div>
                                <div class="name"><?= htmlspecialchars($app['name']) ?></div>
                                <div class="code"><?= htmlspecialchars($app['code']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    // 渲染图表
    function renderTrendChart(partnerTrend, withdrawTrend) {
        // 构造 30 天的数据
        const days = [];
        const today = new Date();
        for (let i = 29; i >= 0; i--) {
            const d = new Date(today);
            d.setDate(today.getDate() - i);
            days.push(d.toISOString().slice(0, 10));
        }
        const partnerMap = {};
        (partnerTrend || []).forEach(r => { partnerMap[r.d] = parseInt(r.c); });
        const withdrawMap = {};
        (withdrawTrend || []).forEach(r => { withdrawMap[r.d] = parseFloat(r.a); });

        const partnerValues = days.map(d => partnerMap[d] || 0);
        const withdrawValues = days.map(d => withdrawMap[d] || 0);

        // 提现的最大值用于柱高
        const partnerMax = Math.max(...partnerValues, 1);
        const withdrawMax = Math.max(...withdrawValues, 1);

        // 用合伙人数量比例 + 提现金额/合伙人max的换算
        const maxScale = Math.max(partnerMax, withdrawMax / 100);
        const effectiveMax = Math.max(...partnerValues, ...withdrawValues.map(v => v / 100), 1);

        // 渲染
        const chartBox = document.getElementById('chartPartner');
        const xBox = document.getElementById('chartPartnerX');
        let barsHtml = '';
        let xHtml = '';
        days.forEach((d, idx) => {
            const partnerH = (partnerValues[idx] / effectiveMax) * 200;
            const withdrawH = (withdrawValues[idx] / 100 / effectiveMax) * 200;
            // 实际显示用两个柱子并排
            barsHtml += `
                <div class="chart-bar-wrap" style="gap:2px;flex-direction:row;align-items:flex-end;">
                    <div class="chart-bar" style="height:${Math.max(partnerH, 2)}px;width:40%;">
                        <div class="chart-bar-tip">${d}: ${partnerValues[idx]} 人</div>
                    </div>
                    <div class="chart-bar withdraw" style="height:${Math.max(withdrawH, 2)}px;width:40%;">
                        <div class="chart-bar-tip">${d}: ¥${withdrawValues[idx].toFixed(2)}</div>
                    </div>
                </div>`;
            if (idx % 5 === 0) {
                xHtml += `<span>${d.slice(5)}</span>`;
            } else {
                xHtml += `<span></span>`;
            }
        });
        chartBox.innerHTML = barsHtml;
        xBox.innerHTML = xHtml;
    }

    function loadAll() {
        const params = new URLSearchParams();
        params.append('action', 'refresh');
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const d = data.data;
                renderTrendChart(d.partner_trend, d.withdraw_trend);
                showMessage('✅ 数据已刷新', 'success');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }

    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (type || 'info');
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 2000);
    }

    // 初始渲染（基于 PHP 预加载数据）
    renderTrendChart(<?= json_encode($initial['partner_trend']) ?>, <?= json_encode($initial['withdraw_trend']) ?>);
    </script>
</body>
</html>
