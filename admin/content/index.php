<?php
/**
 * 文章列表管理
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();

$pageTitle = '文章列表管理';

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$author_filter = trim($_GET['author'] ?? '');
$category_id = intval($_GET['category_id'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$recommend_filter = $_GET['is_recommend'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 15;

// 构建查询条件
$where = "1=1";
$params = [];
if ($keyword !== '') {
    $where .= " AND (a.title LIKE ? OR a.summary LIKE ? OR a.author LIKE ?)";
    $kw = "%{$keyword}%";
    $params[] = $kw; $params[] = $kw; $params[] = $kw;
}
if ($author_filter !== '') {
    $where .= " AND a.author = ?";
    $params[] = $author_filter;
}
if ($category_id > 0) {
    $where .= " AND a.category_id = ?";
    $params[] = $category_id;
}
if ($status_filter !== '' && $status_filter !== null) {
    $where .= " AND a.status = ?";
    $params[] = intval($status_filter);
}
if ($recommend_filter !== '' && $recommend_filter !== null) {
    $where .= " AND a.is_recommend = ?";
    $params[] = intval($recommend_filter);
}

// 总数
$totalSql = "SELECT COUNT(*) AS cnt FROM articles a WHERE $where";
$totalRow = $db->fetchOne($totalSql, $params);
$total = intval($totalRow['cnt'] ?? 0);
$totalPages = max(1, ceil($total / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

// 列表
$listSql = "SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN article_categories c ON a.category_id = c.id WHERE $where ORDER BY a.id DESC LIMIT $offset, $pageSize";
$articles = $db->fetchAll($listSql, $params);

// 分类（下拉用）
$categories = $db->fetchAll("SELECT id, name FROM article_categories WHERE status = 1 ORDER BY sort ASC, id DESC");
// 作者（下拉用）
$aAuthors = $db->fetchAll("SELECT DISTINCT author FROM articles WHERE author IS NOT NULL AND author != '' ORDER BY author");

// AJAX 处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if ($_POST['action'] === 'save' || $_POST['action'] === 'save_draft') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            if (empty($title) || empty($content)) {
                echo json_encode(['success' => false, 'message' => '标题和内容不能为空']); exit;
            }
            $stmt = $db->getConnection()->prepare("INSERT INTO articles (title, category_id, cover, summary, content, author, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param('sissssi', $title, intval($_POST['category'] ?? 0), $_POST['cover_image'] ?? '', $_POST['summary'] ?? '', $content, $_POST['author'] ?? '', intval($_POST['status'] ?? 1));
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => '发布成功', 'id' => $db->getConnection()->insert_id]); exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]); exit;
    }
    echo json_encode(['success' => false, 'message' => '未知操作']); exit;
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
        .search-panel { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
        .form-item { display: flex; flex-direction: column; gap: 6px; min-width: 140px; }
        .form-item label { font-size: 13px; color: #8c8c8c; font-weight: 600; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
        .form-item input:focus, .form-item select:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1); }
        .filter-tabs { display: flex; gap: 8px; align-items: center; margin-bottom: 20px; padding: 12px 16px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); flex-wrap: wrap; }
        .filter-tabs label { font-size: 14px; color: #595959; font-weight: 600; white-space: nowrap; }
        .filter-btn { padding: 6px 16px; border: 1px solid #d9d9d9; border-radius: 6px; background: white; cursor: pointer; font-size: 13px; color: #595959; transition: all 0.2s; }
        .filter-btn:hover { border-color: #1890ff; color: #1890ff; }
        .filter-btn.active { background: #1890ff; color: white; border-color: #1890ff; }
        .batch-toolbar { display: none; background: linear-gradient(135deg, rgba(24,144,255,0.08), rgba(64,169,255,0.08)); border: 1px solid rgba(24,144,255,0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; align-items: center; justify-content: space-between; }
        .batch-toolbar.show { display: flex; }
        .batch-actions { display: flex; gap: 8px; }
        /* 网格卡片布局 */
        .article-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 4px; }
        .article-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.25s; position: relative; border: 1px solid #f0f0f0; }
        .article-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); cursor: pointer; }
        .article-card .card-cover-wrap { width: 100%; height: 190px; overflow: hidden; position: relative; background: #f5f5f5; }
        .article-card .card-cover { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.5s ease, transform 0.3s; position: absolute; top: 0; left: 0; opacity: 0; }
        .article-card .card-cover.active { opacity: 1; position: relative; }
        .article-card:hover .card-cover.active { transform: scale(1.05); }
        .article-card .cover-dots { position: absolute; bottom: 8px; left: 0; right: 0; display: flex; justify-content: center; gap: 4px; z-index: 3; pointer-events: none; }
        .article-card .cover-dots .dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.25s; pointer-events: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .article-card .cover-dots .dot.active { width: 16px; border-radius: 4px; background: white; }
        .article-card .card-cover-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: rgba(255,255,255,0.4); }


        .article-card .card-status-badge { position: absolute; top: 12px; right: 12px; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .article-card .card-status-badge.published { background: #52c41a; color: white; }
        .article-card .card-status-badge.draft { background: #ff4d4f; color: white; }
        .article-card .card-body { padding: 14px 16px 16px; }
        .article-card .card-title { font-size: 15px; font-weight: 700; color: #1a1a2e; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 6px; }
        .article-card .card-summary { font-size: 13px; color: #8c8c8c; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 10px; }
        .article-card .card-meta-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; padding: 8px 0; border-top: 1px solid #f5f5f5; }
        .article-card .card-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: white; flex-shrink: 0; overflow: hidden; }
        .article-card .card-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .article-card .card-avatar-text { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
        .article-card .card-author { font-size: 13px; color: #595959; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80px; }
        .article-card .card-stat { font-size: 12px; color: #999; font-weight: 500; white-space: nowrap; }
        .article-card .card-stat-label { color: #bfbfbf; margin-left: 2px; }
        .article-card .card-date { font-size: 11px; color: #ccc; margin-left: auto; white-space: nowrap; }
        .article-card .card-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .article-card .card-tag { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; color: #0050b3; background: #e6f7ff; border: 1px solid #bae7ff; }
        .article-card .card-tag:hover { background: #bae7ff; }

        .checkbox { width: 18px; height: 18px; cursor: pointer; }
        .toggle-switch { position: relative; width: 40px; height: 20px; background: #d9d9d9; border-radius: 10px; cursor: pointer; transition: all 0.3s; display: inline-block; }
        .toggle-switch.on { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: white; border-radius: 50%; transition: all 0.3s; }
        .toggle-switch.on::after { transform: translateX(20px); }
        .status-tag { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-1 { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .status-0 { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .action-btn { padding: 5px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 600; }
        .action-btn-edit { background: #e6f7ff; color: #0050b3; }
        .action-btn-edit:hover { background: #bae7ff; }
        .action-btn-delete { background: #fff2f0; color: #cf1322; }
        .action-btn-delete:hover { background: #ffccc7; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; padding: 20px; }
        .pagination a, .pagination span { padding: 8px 14px; border: 1px solid #d9d9d9; border-radius: 6px; text-decoration: none; color: #262626; font-size: 14px; transition: all 0.3s; }
        .pagination a:hover { background: #1890ff; color: white; border-color: #1890ff; }
        .pagination .active { background: #1890ff; color: white; border-color: #1890ff; font-weight: 600; }
        .pagination .disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination .page-info { background: #e6f7ff; color: #1890ff; font-weight: 600; border-color: #91d5ff; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 18px; color: #595959; margin: 0 0 8px; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; transition: all 0.2s; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(24,144,255,0.4); }
        .btn-success { background: linear-gradient(135deg, #52c41a, #73d13d); color: white; }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(82,196,26,0.4); }
        .btn-danger { background: #ff4d4f; color: white; }
        .btn-danger:hover { background: #ff7875; }
        .btn-outline { background: white; color: #595959; border: 1px solid #d9d9d9; }
        .btn-outline:hover { border-color: #1890ff; color: #1890ff; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-default:hover { background: #e6e6e6; }
        .article-drawer { display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:2000; }
        .article-drawer.show { display:flex; }
        .article-drawer .drawer-mask { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); animation:fadeIn 0.3s; }
        .article-drawer .drawer-content { position:relative; width:1500px; max-width:95%; height:100%; background:white; box-shadow:-4px 0 24px rgba(0,0,0,0.15); animation:slideIn 0.3s; display:flex; flex-direction:row; margin-left:auto; }
        .article-drawer .drawer-header { display:flex; justify-content:space-between; align-items:center; padding:20px 24px; border-bottom:1px solid #f0f0f0; }
        .article-drawer .drawer-header h3 { margin:0; font-size:18px; color:#262626; }
        .article-drawer .drawer-close { width:32px; height:32px; border:none; background:transparent; font-size:24px; color:#8c8c8c; cursor:pointer; border-radius:4px; display:flex; align-items:center; justify-content:center; }
        .article-drawer .drawer-close:hover { background:#f5f5f5; color:#262626; }
        .article-drawer .drawer-body { flex:1; overflow-y:auto; padding:24px; }
        .article-drawer.closing .drawer-content { animation:slideOut 0.3s forwards; }
        .article-drawer.closing .drawer-mask { animation:fadeOut 0.3s forwards; }

        /* 抽屉动画 */
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        @keyframes slideOut {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* 抽屉内左右分栏 */
        .drawer-left {
            width:380px;
            flex-shrink:0;
            display:flex;
            flex-direction:column;
            background:#1a1a1a;
            border-right:1px solid #f0f0f0;
        }
        .drawer-left .preview-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:16px 20px;
            border-bottom:1px solid rgba(255,255,255,0.08);
            color:rgba(255,255,255,0.6);
            font-size:13px;
            font-weight:600;
        }
        .drawer-left .preview-header span:last-child {
            background:rgba(255,255,255,0.1);
            padding:2px 8px;
            border-radius:4px;
            font-size:11px;
        }
        .drawer-left .phone-wrapper {
            flex:1;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }
        .drawer-left .phone-wrapper .phone-shell {
            width:320px;
            height:660px;
            background:#1a1a1a;
            border-radius:40px;
            padding:10px;
            box-shadow:0 8px 32px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.06);
            position:relative;
            box-sizing:content-box;
        }
        .drawer-left .phone-wrapper .phone-screen {
            width:100%;
            height:100%;
            border-radius:30px;
            overflow:hidden;
            background:#f7f8fa;
            position:relative;
        }
        .drawer-left .phone-wrapper .phone-screen iframe {
            width:100%;
            height:100%;
            border:none;
            display:block;
        }
        .drawer-left .phone-wrapper .phone-notch {
            position:absolute;
            top:0;
            left:50%;
            transform:translateX(-50%);
            width:110px;
            height:22px;
            background:#1a1a1a;
            border-radius:0 0 14px 14px;
            z-index:10;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:5px;
        }
        .drawer-left .phone-wrapper .phone-notch .speaker {
            width:36px;
            height:4px;
            border-radius:2px;
            background:#2a2a3a;
        }
        .drawer-left .phone-wrapper .phone-notch .camera {
            width:8px;
            height:8px;
            border-radius:50%;
            background:#2a2a3a;
            border:1px solid #333;
        }
        .drawer-left .phone-wrapper .phone-home {
            position:absolute;
            bottom:6px;
            left:50%;
            transform:translateX(-50%);
            width:100px;
            height:4px;
            border-radius:2px;
            background:rgba(0,0,0,0.12);
            z-index:10;
        }
        .drawer-right {
            flex:1;
            display:flex;
            flex-direction:column;
            min-width:0;
        }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .toolbar h1 { font-size: 22px; margin: 0; color: #262626; }
        .toolbar-actions { display: flex; gap: 12px; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- 工具栏 -->
            <div class="toolbar">
                <h1>📄 文章列表</h1>
                <div class="toolbar-actions">
                    <a href="article_category.php" class="btn btn-outline">📁 分类管理</a>
                    <button type="button" class="btn btn-success" onclick="openArticleDrawer()">✚ 新增文章</button>
                </div>
            </div>
            
            <!-- 搜索面板 -->
            <div class="search-panel">
                <form method="GET" class="search-form">
                    <div class="form-item">
                        <label>关键词</label>
                        <input type="text" name="keyword" placeholder="搜索标题/摘要/作者" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    <div class="form-item">
                        <label>作者</label>
                        <select name="author">
                            <option value="">全部</option>
                            <?php foreach ($aAuthors as $a): ?>
                                <option value="<?= htmlspecialchars($a['author']) ?>" <?= $author_filter === $a['author'] ? 'selected' : '' ?>><?= htmlspecialchars($a['author']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>状态</label>
                        <select name="status">
                            <option value="">全部</option>
                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>已发布</option>
                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>已下架</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>分类</label>
                        <select name="category_id">
                            <option value="0">全部分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-item" style="flex-direction:row;gap:8px;">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <a href="index.php" class="btn btn-default">重置</a>
                    </div>
                </form>
            </div>
            
            <!-- 快捷筛选 -->
            <div class="filter-tabs">
                <label>快捷筛选：</label>
                <a href="index.php" class="filter-btn <?= $status_filter === '' && $recommend_filter === '' ? 'active' : '' ?>">全部</a>
                <a href="index.php?status=1" class="filter-btn <?= $status_filter === '1' ? 'active' : '' ?>">已发布</a>
                <a href="index.php?status=0" class="filter-btn <?= $status_filter === '0' ? 'active' : '' ?>">已下架</a>
                <a href="index.php?is_recommend=1" class="filter-btn <?= $recommend_filter === '1' ? 'active' : '' ?>">已推荐</a>
            </div>
            

            
                        <!-- 网格列表 -->
            <div style="text-align:right;margin-bottom:16px;padding:0 4px;">
                <span style="font-size:13px;color:#bfbfbf;">共 <?= $total ?> 篇文章</span>
            </div>
            <div class="article-grid">
                <?php if (empty($articles)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:80px 20px;color:#999;">
                    <div style="font-size:64px;margin-bottom:16px;">📭</div>
                    <h3 style="font-size:18px;color:#595959;margin:0 0 8px;">暂无文章数据</h3>
                    <p style="font-size:14px;color:#bfbfbf;">点击右上角「新增文章」开始创建</p>
                </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): 
                        $authorName = $article['author'] ?? '';
                        $isPlatform = in_array($authorName, ['管理员', '平台', '系统', '青园营地', '青园']);
                        $avatarColor = $isPlatform ? '#FF6B35' : '#1890ff';
                    ?>
                    <div class="article-card" onclick="editArticle(<?= $article['id'] ?>)">
                        <div class="card-cover-wrap">
                            <?php 
                            $_cv = $article['cover'] ?? '';
                            $_covers = [];
                            if (strpos($_cv, '[') === 0) { $_d = json_decode($_cv, true); if (is_array($_d)) $_covers = $_d; }
                            elseif ($_cv) { $_covers = [$_cv]; }
                            ?>
                            <?php if (!empty($_covers)): 
                                foreach ($_covers as $_ci => $_cu): ?>
                            <img src="<?= htmlspecialchars($_cu) ?>" class="card-cover <?= $_ci === 0 ? 'active' : '' ?>" data-index="<?= $_ci ?>" alt="" onerror="this.style.display='none'">
                                <?php endforeach; ?>
                                <?php if (count($_covers) > 1): ?>
                            <div class="cover-dots">
                                <?php foreach ($_covers as $_ci => $_cu): ?>
                                <span class="dot <?= $_ci === 0 ? 'active' : '' ?>" data-index="<?= $_ci ?>"></span>
                                <?php endforeach; ?>
                            </div>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="card-cover-placeholder">📷</div>
                            <?php endif; ?>
                            <span class="card-status-badge <?= $article['status'] == 1 ? 'published' : 'draft' ?>"><?= $article['status'] == 1 ? '已发布' : '已下架' ?></span>
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?= htmlspecialchars($article['title']) ?></div>
                            <?php if (!empty($article['summary'])): ?>
                            <div class="card-summary"><?= htmlspecialchars(mb_substr(strip_tags($article['summary']), 0, 80)) ?></div>
                            <?php endif; ?>
                            <div class="card-meta-row">
                                <div class="card-avatar" style="background:<?= $avatarColor ?>">
                                    <?php if ($isPlatform): ?>
                                    <img src="../images/logo.png" alt="平台">
                                    <?php else: ?>
                                    <span class="card-avatar-text"><?= mb_substr($authorName, 0, 1) ?: '?' ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="card-author"><?= htmlspecialchars($authorName ?: '未知') ?></span>
                                <span class="card-stat"><?= intval($article['likes'] ?? 0) ?><span class="card-stat-label">赞</span></span>
                                <span class="card-stat"><?= intval($article['views']) ?><span class="card-stat-label">播放</span></span>
                                <span class="card-date"><?= date('m-d H:i', strtotime($article['published_at'] ?? $article['created_at'])) ?></span>
                            </div>
                            <?php if (!empty($article['tags'])): ?>
                            <div class="card-tags">
                                <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                    <?php $tag = trim($tag); if ($tag): ?>
                                    <span class="card-tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">首页</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>">上一页</a>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">下一页</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>">末页</a>
                <span class="page-info">第 <?= $page ?> / <?= $totalPages ?> 页 (共 <?= $total ?> 条)</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleAll(source) {
            document.querySelectorAll('.article-checkbox').forEach(cb => cb.checked = source.checked);
            updateBatchToolbar();
        }
        function updateBatchToolbar() {
            const checked = document.querySelectorAll('.article-checkbox:checked');
            const toolbar = document.getElementById('batchToolbar');
            if (checked.length > 0) {
                toolbar.classList.add('show');
                document.getElementById('selectedCount').textContent = checked.length;
            } else { toolbar.classList.remove('show'); }
        }
        function batchSetStatus(status) {
            const ids = Array.from(document.querySelectorAll('.article-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('请先选择文章');
            if (!confirm(`确定将选中的 ${ids.length} 篇文章${status == 1 ? '发布' : '下架'}吗？`)) return;
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=batch_status&ids=${ids.join(',')}&status=${status}` })
            .then(r => r.json()).then(data => { if (data.success) window.location.reload(); else alert('❌ ' + (data.message || '操作失败')); });
        }
        function batchDelete() {
            const ids = Array.from(document.querySelectorAll('.article-checkbox:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('请先选择文章');
            if (!confirm(`⚠️ 确定要删除选中的 ${ids.length} 篇文章吗？此操作不可恢复！`)) return;
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=batch_delete&ids=${ids.join(',')}` })
            .then(r => r.json()).then(data => { if (data.success) window.location.reload(); else alert('❌ ' + (data.message || '删除失败')); });
        }
        function openArticleDrawer(id) {
            var drawer = document.getElementById('articleDrawer');
            drawer.classList.add('show');
            document.body.style.overflow = 'hidden';
            document.getElementById('articleDrawerBody').innerHTML = '<div style="text-align:center;padding:60px;color:#999;">加载中...</div>';
            // 更新标题
            var h3 = drawer.querySelector('.drawer-header h3');
            if (h3) h3.textContent = id ? '编辑文章' : '📝 新增文章';
            var url = id ? ('../article_edit.php?id=' + id) : '../article_edit.php';
            // 异步加载编辑页内容
            fetch(url)
                .then(function(r) { return r.text(); })
                .then(function(html) {
                    document.getElementById('articleDrawerBody').innerHTML = html;
                    // HTML 加载完成后初始化文章编辑器
                    initArticleEditor();
                })
                .catch(function(err) {
                    document.getElementById('articleDrawerBody').innerHTML = '<div style="padding:60px;text-align:center;color:#f5222d;">加载失败：' + err.message + '</div>';
                });
        }
        function closeArticleDrawer() {
            var drawer = document.getElementById('articleDrawer');
            if (drawer.classList.contains('closing')) return;
            drawer.classList.add('closing');
            setTimeout(function() {
                drawer.classList.remove('show', 'closing');
                document.body.style.overflow = '';
            }, 300);
        }
        function editArticle(id) { openArticleDrawer(id); }
        function deleteArticle(id) {
            if (!confirm('确定要删除该文章吗？此操作不可恢复！')) return;
            fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `action=delete&id=${id}` })
            .then(r => r.json()).then(data => { if (data.success) window.location.reload(); else alert('❌ ' + (data.message || '删除失败')); });
        }
        function toggleRecommend(id, status) {
            fetch('../../api/article.php?action=toggle_recommend', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'id=' + id + '&is_recommend=' + status })
                .then(function(r) { return r.json(); })
                .then(function(data) { 
                    if (data.code === 200) {
                        window.location.reload();
                    } else {
                        alert('操作失败: ' + (data.message || '未知错误'));
                    }
                })
                .catch(function(err) {
                    alert('网络错误: ' + err.message);
                });
        }

        // 服务端上传限制由 php.ini upload_max_filesize 控制，当前值为: 64M

        /* ===== 文章编辑器 JS ===== */
        var articleTags = [];
        var articlePreviewTimer = null;
        var _coverUploader = null;

        function initArticleEditor() {
            var td = document.getElementById('tagsData'); articleTags = td && td.value ? td.value.split(',').map(function(t) { return t.trim(); }).filter(Boolean) : [];
            renderArticleTags();
            bindArticleTabEvents();
            bindArticleTagInput();
            // 初始化 ProductImageUpload 组件
            initCoverUploader();
            bindArticleAutoSave();
            bindArticlePreviewUpdate();
            // 初始生成预览
            setTimeout(updateArticlePreview, 100);
        }

        /* 初始化封面图片上传组件(ProductImageUpload) */
        function initCoverUploader() {
            var container = document.getElementById('coverUploader');
            if (!container) return;
            // 从隐藏字段读取已保存封面
            var coverVal = document.getElementById('cover_image');
            var initialImages = [];
            if (coverVal && coverVal.value) {
                try {
                    initialImages = JSON.parse(coverVal.value);
                    if (!Array.isArray(initialImages)) initialImages = [coverVal.value];
                } catch(e) {
                    initialImages = coverVal.value ? [coverVal.value] : [];
                }
                initialImages = initialImages.filter(function(s) { return s && s.trim(); });
            }
            _coverUploader = new ProductImageUpload(container, {
                images: initialImages,
                maxCount: 10,
                maxSizeMB: 10,
                itemSize: 120, // 更适配封面区域
                onImagesChange: function() {
                    updateArticlePreview();
                }
            });
        }

        /* 标签 */
        function renderArticleTags() {
            var list = document.getElementById('tagList');
            if (!list) return;
            if (articleTags.length === 0) { list.innerHTML = ''; return; }
            list.innerHTML = articleTags.map(function(t, i) {
                return '<span class="tag-item"><span>' + escapeHtml(t) + '</span><button class="tag-delete" onclick="deleteTag(' + i + ')">×</button></span>';
            }).join('');
            syncArticleTags();
        }

        function syncArticleTags() {
            var el = document.getElementById('tagsData');
            if (el) el.value = articleTags.join(',');
        }

        function addTag() {
            var input = document.getElementById('tagInput');
            if (!input) return;
            var val = input.value.trim();
            if (!val) { input.focus(); return; }
            if (articleTags.indexOf(val) !== -1) { input.value = ''; input.focus(); return; }
            articleTags.push(val);
            renderArticleTags();
            input.value = '';
            input.focus();
        }

        function deleteTag(index) {
            articleTags.splice(index, 1);
            renderArticleTags();
        }

        /* TAB 切换 */
        function bindArticleTabEvents() {
            document.querySelectorAll('#articleDrawer .tab-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var container = document.getElementById('articleDrawerBody');
                    container.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    container.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
                    btn.classList.add('active');
                    var tab = container.querySelector('#tab-' + btn.getAttribute('data-tab'));
                    if (tab) tab.classList.add('active');
                });
            });
        }

        function switchArticleTab(name) {
            var container = document.getElementById('articleDrawerBody');
            container.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.toggle('active', b.getAttribute('data-tab') === name);
            });
            container.querySelectorAll('.tab-content').forEach(function(c) {
                c.classList.toggle('active', c.id === 'tab-' + name);
            });
        }
        /* 封面图片上传 - 已迁移至 ProductImageUpload 组件 */

        /* 保存文章 */
        function saveArticle(mode) {
            var title = document.getElementById('title').value.trim();
            var content = document.getElementById('content').value.trim();
            if (!title) { alert('请输入文章标题'); document.getElementById('title').focus(); return; }
            if (!content) { alert('请输入文章内容'); switchArticleTab('content'); document.getElementById('content').focus(); return; }
            var category = document.getElementById('category').value.trim();
            if (!category) { alert('请选择文章分类'); document.getElementById('category').focus(); return; }

            // 通过 ProductImageUpload 组件上传待处理的封面图片
            var uploadPending = function() {
                if (!_coverUploader) return Promise.resolve([]);
                var pending = _coverUploader.getPendingItems();
                if (!pending || pending.length === 0) return Promise.resolve([]);
                var promises = [];
                pending.forEach(function(item) {
                    if (!item.file) return;
                    var fd = new FormData();
                    fd.append('file', item.file);
                    promises.push(
                        fetch('../../api/upload.php', { method: 'POST', body: fd })
                            .then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.code === 200) {
                                    if (item.blobUrl) URL.revokeObjectURL(item.blobUrl);
                                    return res.data.file_path || '';
                                } else {
                                    throw new Error(res.message || '上传失败');
                                }
                            })
                    );
                });
                return Promise.all(promises);
            };

            var doSave = function() {
                var articleId = document.getElementById('articleId').value;

                // 组合最终封面图片列表
                var savedUrls = _coverUploader ? _coverUploader.getImages().slice() : [];
                uploadPending().then(function(newUrls) {
                    var allUrls = savedUrls.concat(newUrls);
                    document.getElementById('cover_image').value = JSON.stringify(allUrls);

                    var fd = new FormData();
                    if (articleId && articleId !== '0') fd.append('id', articleId);
                    fd.append('title', title);
                    fd.append('category', category);
                    fd.append('author', document.getElementById('author').value.trim());
                    fd.append('cover_image', document.getElementById('cover_image').value.trim());
                    fd.append('summary', document.getElementById('summary').value.trim());
                    fd.append('content', content);
                    fd.append('tags', document.getElementById('tagsData').value.trim());
                    fd.append('sort', document.getElementById('sort').value || '0');

                    var stEl = document.querySelector('#articleDrawerBody input[name="status"]');
                    if (stEl) fd.append('status', stEl.value);
                    var rcEl = document.querySelector('#articleDrawerBody input[name="is_recommend"]');
                    if (rcEl) fd.append('is_recommend', rcEl.value);
                    var pt = document.getElementById('published_at').value;
                    if (pt) fd.append('published_at', pt);

                    var isEdit = articleId && articleId !== '0';
                    var action = isEdit ? 'update' : 'create';

                    showArticleLoading((isEdit ? '更新' : '创建') + '文章中...');

                    fetch('../../api/article.php?action=' + action, { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            hideArticleLoading();
                            if (res.code === 200) {
                                alert(mode === 'publish' ? '🚀 发布成功' : '💾 保存成功');
                                closeArticleDrawer();
                                window.location.reload();
                            } else {
                                alert('❌ ' + (res.message || '操作失败'));
                            }
                        })
                        .catch(function(err) {
                            hideArticleLoading();
                            alert('网络错误：' + err.message);
                        });
                }).catch(function(err) {
                    hideArticleLoading();
                    alert('❌ 封面上传失败: ' + err.message);
                });
            };

            doSave();
        }

        /* 删除 */
        function deleteArticle() {
            var id = document.getElementById('articleId').value;
            if (!id || id === '0') { alert('请先保存后再删除'); return; }
            if (!confirm('⚠️ 确定要删除该文章吗？此操作不可恢复！')) return;

            showArticleLoading('删除中...');
            var fd = new FormData();
            fd.append('id', id);
            fetch('../../api/article.php?action=delete', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    hideArticleLoading();
                    if (res.code === 200) {
                        alert('✅ 删除成功');
                        closeArticleDrawer();
                        window.location.reload();
                    } else {
                        alert('❌ ' + (res.message || '删除失败'));
                    }
                })
                .catch(function(err) {
                    hideArticleLoading();
                    alert('网络错误：' + err.message);
                });
        }

        /* 预览 */
        function updateArticlePreview() {
            var iframe = document.getElementById('drawerPreviewIframe');
            if (iframe) iframe.srcdoc = generateArticlePreviewHTML();
        }

        /* 拨动开关标签更新 */
        /* 拨动开关切换 */
        function toggleSwitch(el, name) {
            var sw = el.querySelector('.toggle-switch');
            if (!sw) return;
            var isOn = sw.classList.toggle('on');
            var hidden = el.querySelector('input[type="hidden"]');
            if (hidden) hidden.value = isOn ? '1' : '0';
            var lbl = el.querySelector('.toggle-label');
            if (lbl) {
                if (name === 'status') lbl.textContent = isOn ? '已发布' : '下架';
                else if (name === 'recommend') lbl.textContent = isOn ? '推荐' : '不推荐';
            }
            updateArticlePreview();
        }

        /* 初始化预览事件绑定 - 实时更新 */
        function bindArticlePreviewUpdate() {
            var container = document.getElementById('articleDrawerBody');
            container.addEventListener('input', function() {
                if (articlePreviewTimer) clearTimeout(articlePreviewTimer);
                articlePreviewTimer = setTimeout(updateArticlePreview, 200);
            });
            container.addEventListener('change', function() {
                if (articlePreviewTimer) clearTimeout(articlePreviewTimer);
                articlePreviewTimer = setTimeout(updateArticlePreview, 200);
            });
        }

        function generateArticlePreviewHTML() {
            var title = document.getElementById('title').value || '文章标题';
            var cover = document.getElementById('cover_image').value || '';
            var summary = document.getElementById('summary').value || '';
            var content = document.getElementById('content').value || '';
            var author = document.getElementById('author').value || '管理员';
            var cat = document.getElementById('category').value || '分类';
            var coverUrls = [];
            if (cover) {
                try { coverUrls = JSON.parse(cover); } catch(e) { coverUrls = [cover]; }
                if (!Array.isArray(coverUrls)) coverUrls = [cover];
                coverUrls = coverUrls.filter(function(u) { return u && u.trim(); });
            }
            // 加上 ProductImageUpload 组件中待上传封面的 blob URL
            if (_coverUploader) {
                var pending = _coverUploader.getPendingItems();
                pending.forEach(function(item) {
                    if (item.blobUrl) coverUrls.push(item.blobUrl);
                });
            }
            var coverHtml = '';
            var dotHtml = '';
            var carouselJs = '';
            if (coverUrls.length > 0) {
                coverHtml = coverUrls.map(function(url, i) {
                    var imgUrl = url.indexOf('blob:') === 0 || url.startsWith('http') ? url : '../' + url;
                    return '<img src="' + imgUrl + '" alt="" class="cs-img" data-index="' + i + '" style="' + (i === 0 ? '' : 'display:none') + '">';
                }).join('');
                if (coverUrls.length > 1) {
                    dotHtml = '<div class="dots">' + coverUrls.map(function(u, i) {
                        return '<span class="dot ' + (i === 0 ? 'on' : '') + '" data-idx="' + i + '"></span>';
                    }).join('') + '</div>';
                    carouselJs = 'var ci=0,n=' + coverUrls.length + ',imgs=document.querySelectorAll(".cs-img"),dots=document.querySelectorAll(".dot");function go(i){imgs.forEach(function(x){x.style.display="none"});dots.forEach(function(d){d.classList.remove("on")});imgs[i].style.display="";dots[i].classList.add("on");ci=i}setInterval(function(){go((ci+1)%n)},3000);dots.forEach(function(d,i){d.onclick=function(){go(i)}});';
                }
            } else {
                coverHtml = '<div class="empty">📷</div>';
                dotHtml = '<div class="dots"><span class="on"></span><span></span><span></span></div>';
            }
            var coverUrl = coverUrls.length > 0 ? (coverUrls[0].indexOf('blob:') === 0 || coverUrls[0].startsWith('http') ? coverUrls[0] : '../' + coverUrls[0]) : '';

            var now = new Date();
            var Y = now.getFullYear();
            var M = String(now.getMonth()+1).padStart(2,'0');
            var D = String(now.getDate()).padStart(2,'0');
            var hh = String(now.getHours()).padStart(2,'0');
            var mm = String(now.getMinutes()).padStart(2,'0');
            var timeStr = Y + '-' + M + '-' + D + ' ' + hh + ':' + mm;

            // 有摘要则放到 body 前面，无摘要直接正文
            var summaryBlock = summary
                ? '<p style="font-size:14px;color:#3A3A4A;font-weight:600;line-height:1.7;margin-bottom:16px;white-space:pre-line">' + escapeHtml(summary) + '</p>'
                : '';

            // 正文为空时显示空状态
            var contentBlock = content
                ? '<div class="cw">' + content.replace(/\n/g, '<br>') + '</div>'
                : '<div style="text-align:center;padding:40px 0;color:#ADADBB"><span style="font-size:48px;opacity:0.4;display:block;margin-bottom:12px">📝</span><p style="font-size:14px;font-weight:600;color:#ADADBB">请输入文章内容</p></div>';

            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"><title>' + escapeHtml(title) + '</title><style>' +
                '*{margin:0;padding:0;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Helvetica Neue",sans-serif}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}body{background:#F5F4F0;color:#1A1A2E;font-size:15px;line-height:1.6;scrollbar-width:none;-ms-overflow-style:none}' +

                /* Hero image area */
                '.hero{width:100%;height:280px;background-color:#1A1A2E;position:relative;overflow:hidden}' +
                '.hero img{width:100%;height:100%;object-fit:cover;opacity:0.95}' +
                '.hero .empty{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:48px;opacity:0.3}' +

                /* Back button - like PostDetailScreen top-4 left-4 w-8 h-8 rounded-full bg-black/30 */
                '.hero .back{position:absolute;top:16px;left:16px;width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;cursor:pointer}' +
                '.hero .back svg{width:16px;height:16px;stroke:white;stroke-width:2.5;fill:none;stroke-linecap:round}' +

                /* Tag badge - like PostDetailScreen absolute top-4 right-4, same style as qingyuanwx */
                '.hero .tag{position:absolute;top:16px;right:16px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:800;color:white;background:#FF6B35}' +

                /* Dot indicators below hero */
                '.dots{position:absolute;bottom:12px;left:0;right:0;display:flex;justify-content:center;gap:6px}' +
                '.dots span{width:6px;height:6px;border-radius:3px;background:rgba(255,255,255,0.5);transition:all 0.2s}' +
                '.dots span.on{width:16px;background:white}' +

                /* Content area - exactly as PostDetailScreen: px-4 pt-4 pb-3 */
                '.cont{padding:16px 16px 12px;background:white}' +

                /* Author row - flex items-center gap-2.5 mb-3 */
                '.arow{display:flex;align-items:center;gap:10px;margin-bottom:12px}' +
                '.arow .av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:#FFF0EB}' +
                '.arow .nm{font-size:14px;font-weight:800;color:#1A1A2E;line-height:1.3}' +
                '.arow .tm{font-size:10px;color:#ADADBB;font-weight:600}' +
                '.arow .ct{font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;color:white;background:#FF6B35;margin-left:auto;flex-shrink:0}' +

                /* Title - text-base font-black = 16px 900 */
                '.ttl{font-size:16px;font-weight:900;color:#1A1A2E;margin-bottom:8px;line-height:1.4}' +

                /* Action bar - px-4 py-3 flex items-center gap-5 border-t border-b */
                '.abar{display:flex;align-items:center;gap:20px;padding:12px 16px;background:white;border-top:1px solid rgba(26,26,46,0.06);border-bottom:1px solid rgba(26,26,46,0.06)}' +
                '.abar .abtn{display:flex;align-items:center;gap:6px;cursor:pointer;background:none;border:none;padding:0}' +
                '.abar .abtn svg{width:20px;height:20px}' +
                '.abar .abtn .cnt{font-size:14px;font-weight:700;color:#7A7A8C}' +
                '.abar .abtn.like svg{stroke:#FF6B35;fill:#FF6B35}' +
                '.abar .abtn.like .cnt{color:#FF6B35}' +
                '.abar .abtn.sv{margin-left:auto}' +

                /* Content wrapper for article body */ +
                '.cw{font-size:14px;color:#3A3A4A;font-weight:500;line-height:1.8;white-space:pre-line}' +
                '.cw h1{font-size:20px;margin:20px 0 10px;color:#1A1A2E;font-weight:800}' +
                '.cw h2{font-size:17px;margin:16px 0 8px;color:#1A1A2E;font-weight:800;padding-left:10px;border-left:3px solid #FF6B35}' +
                '.cw p{margin-bottom:14px}' +
                '.cw img{max-width:100%;border-radius:10px;margin:12px 0}' +
                '.cw strong{color:#1A1A2E}' +
                '.cw a{color:#FF6B35;text-decoration:none}' +
                '.cw blockquote{margin:12px 0;padding:10px 14px;background:#FFF0EB;border-radius:10px;color:#3A3A4A}' +
                '.cw ul,.cw ol{padding-left:18px;margin:10px 0}' +
                '.cw code{background:#F5F4F0;padding:2px 5px;border-radius:4px;font-size:12px;color:#FF6B35}' +
                '.cw pre{background:#1A1A2E;color:#f8f8f2;padding:14px;border-radius:10px;overflow-x:auto;margin:12px 0;font-size:12px}' +
                '.cw pre code{background:none;color:inherit;padding:0}' +

            '</style></head><body>' +

                // === Hero image ===
                '<div class="hero">' +
                    coverHtml + dotHtml +
                    '<div class="back"><svg viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg></div>' +
                    '<span class="tag">' + escapeHtml(cat) + '</span>' +
                '</div>' +

                // === Content area ===
                '<div class="cont">' +

                    // Author row
                    '<div class="arow">' +
                        '<div class="av">👤</div>' +
                        '<div>' +
                            '<div class="nm">' + escapeHtml(author) + '</div>' +
                            '<div class="tm">' + timeStr + '</div>' +
                        '</div>' +
                        '<span class="ct">' + escapeHtml(cat) + '</span>' +
                    '</div>' +

                    // Title
                    '<div class="ttl">' + escapeHtml(title) + '</div>' +

                    // Summary + body
                    summaryBlock +
                    contentBlock +
                '</div>' +

                // === Action bar ===
                '<div class="abar">' +
                    '<button class="abtn like">' +
                        '<svg viewBox="0 0 24 24" stroke-width="2" fill="#FF6B35" stroke="#FF6B35"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>' +
                        '<span class="cnt">0</span>' +
                    '</button>' +
                    '<button class="abtn">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="#7A7A8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
                        '<span class="cnt">0</span>' +
                    '</button>' +
                    '<button class="abtn sv">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="#7A7A8C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>' +
                        '<span class="cnt">收藏</span>' +
                    '</button>' +
                '</div>' +

            (carouselJs ? '<script>' + carouselJs + '</scr' + 'ipt>' : '') +
            '</body></html>';
        }

        /* 实时预览更新 */
        function bindArticleAutoSave() {
            var container = document.getElementById('articleDrawerBody');
            container.addEventListener('input', function() {
                // 预览由 bindArticlePreviewUpdate 统一处理，此处只处理自动保存

                // 自动保存
                if (typeof autoArticleSaveTimer !== 'undefined') clearTimeout(autoArticleSaveTimer);
                autoArticleSaveTimer = setTimeout(function() {
                    var id = document.getElementById('articleId').value;
                    if (id && id !== '0') autoArticleSave();
                }, 30000);
            });
        }

        var autoArticleSaveTimer = null;

        function autoArticleSave() {
            var title = document.getElementById('title').value.trim();
            var content = document.getElementById('content').value.trim();
            if (!title || !content) return;

            var fd = new FormData();
            fd.append('id', document.getElementById('articleId').value);
            fd.append('title', title);
            fd.append('category', document.getElementById('category').value.trim());
            fd.append('author', document.getElementById('author').value.trim());
            fd.append('cover_image', document.getElementById('cover_image').value.trim());
            fd.append('summary', document.getElementById('summary').value.trim());
            fd.append('content', content);
            fd.append('tags', document.getElementById('tagsData').value.trim());
            fd.append('status', '0');

            fetch('../../api/article.php?action=update', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.code === 200) {
                        var tip = document.getElementById('autoSaveTip');
                        if (tip) {
                            tip.classList.add('show');
                            setTimeout(function() { tip.classList.remove('show'); }, 2000);
                        }
                    }
                })
                .catch(function(err) { console.error('自动保存失败:', err); });
        }

        /* 加载遮罩 */
        function showArticleLoading(text) {
            var el = document.getElementById('loadingText');
            if (el) el.textContent = text || '处理中...';
            var overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.add('show');
        }

        function hideArticleLoading() {
            var overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('show');
        }

        /* 编辑器工具栏 */
        function insertEditorTag(tag) {
            var ta = document.getElementById('content');
            if (!ta) return;
            var start = ta.selectionStart, end = ta.selectionEnd;
            var sel = ta.value.substring(start, end);
            var before = ta.value.substring(0, start);
            var after = ta.value.substring(end);
            if (sel) {
                ta.value = before + '<' + tag + '>' + sel + '</' + tag + '>' + after;
                ta.selectionStart = start + tag.length + 2;
                ta.selectionEnd = start + tag.length + 2 + sel.length;
            } else {
                ta.value = before + '<' + tag + '></' + tag + '>' + after;
                ta.selectionStart = start + tag.length + 2;
                ta.selectionEnd = start + tag.length + 2;
            }
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        }

        function insertAtCursor(text) {
            var ta = document.getElementById('content');
            if (!ta) return;
            var start = ta.selectionStart, end = ta.selectionEnd;
            ta.value = ta.value.substring(0, start) + text + ta.value.substring(end);
            ta.selectionStart = ta.selectionEnd = start + text.length;
            ta.focus();
            ta.dispatchEvent(new Event('input'));
        }

        function insertEditorImage() {
            var input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                var file = e.target.files[0];
                if (!file) return;
                if (file.size > 5 * 1024 * 1024) { alert('图片不能超过 5MB'); return; }
                insertAtCursor('[上传中...]');
                var fd = new FormData();
                fd.append('file', file);
                fetch('../../api/upload.php', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.code === 200) {
                            var url = res.data.file_url || '../' + (res.data.file_path || '');
                            var imgTag = '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;">';
                            // 替换占位
                            var ta = document.getElementById('content');
                            if (ta) ta.value = ta.value.replace('[上传中...]', imgTag);
                        } else {
                            var ta = document.getElementById('content');
                            if (ta) ta.value = ta.value.replace('[上传中...]', '');
                            alert('上传失败: ' + (res.message || '未知错误'));
                        }
                        if (ta) ta.dispatchEvent(new Event('input'));
                    })
                    .catch(function(err) {
                        var ta = document.getElementById('content');
                        if (ta) ta.value = ta.value.replace('[上传中...]', '');
                        alert('网络错误: ' + err.message);
                    });
            };
            input.click();
        }

        function insertEditorLink() {
            var url = prompt('请输入链接 URL:', 'https://');
            if (!url || url === 'https://') return;
            var ta = document.getElementById('content');
            if (!ta) return;
            var sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
            var text = prompt('请输入链接文字:', sel || '链接');
            if (!text) text = url;
            var aTag = '<a href="' + url + '" target="_blank" rel="noopener">' + text + '</a>';
            insertAtCursor(aTag);
        }

        /* 卡片封面轮播 */
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.card-cover-wrap').forEach(function(wrap) {
                var imgs = wrap.querySelectorAll('.card-cover');
                var dots = wrap.querySelectorAll('.cover-dots .dot');
                if (imgs.length < 2) return;
                var current = 0, timer = null;
                function goTo(idx) {
                    imgs[current].classList.remove('active');
                    imgs.forEach(function(im) { im.style.position = 'absolute'; });
                    imgs[idx].style.position = '';
                    if (dots.length) { dots[current].classList.remove('active'); dots[idx].classList.add('active'); }
                    current = idx;
                    imgs[current].classList.add('active');
                }
                function startTimer() { timer = setInterval(function() { goTo((current + 1) % imgs.length); }, 3500); }
                function stopTimer() { if (timer) { clearInterval(timer); timer = null; } }
                wrap.addEventListener('mouseenter', stopTimer);
                wrap.addEventListener('mouseleave', startTimer);
                dots.forEach(function(d) {
                    d.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var idx = parseInt(d.getAttribute('data-index'));
                        if (idx !== current) { stopTimer(); goTo(idx); startTimer(); }
                    });
                });
                startTimer();
            });
        });
                /* HTML 转义 */
        function escapeHtml(text) {
            var d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        /* 绑定标签输入框回车 */
        function bindArticleTagInput() {
            var input = document.getElementById('tagInput');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); addTag(); }
                });
            }
        }

/* ============================================
 * 🏕️ ProductImageUpload 组件 (来源: 组件库 #30075)
 * 图片上传 · 拖拽排序 · 封面标识 · 大图预览 · 数量限制
 * ============================================ */
function ProductImageUpload(container, options) {
  var opts = options || {};
  var _maxCount    = opts.maxCount    || 10;
  var _maxSizeMB   = opts.maxSizeMB   || 5;
  var _maxSizeBytes = _maxSizeMB * 1024 * 1024;
  var _accept      = opts.accept      || 'image/*';
  var _itemSize    = opts.itemSize    || 150;
  var _images      = Array.isArray(opts.images) ? opts.images.slice() : [];
  var _pending     = [];
  var _onChange    = typeof opts.onImagesChange === 'function' ? opts.onImagesChange : function() {};
  var _grid, _uploader, _fileInput, _countEl;

  container.innerHTML =
    '<div class="piu-label">' +
      '<span class="piu-label__text">封面图片</span>' +
      '<span class="piu-label__hint">（最少 1 张，最多 ' + _maxCount + ' 张）</span>' +
      '<span class="piu-label__count" id="piu-cnt-' + Date.now() + '">已选择 0 张</span>' +
    '</div>' +
    '<div class="piu-grid" id="piu-grid-' + Date.now() + '">' +
      '<div class="piu-uploader" style="width:' + _itemSize + 'px;height:' + _itemSize + 'px">' +
        '<div class="piu-uploader__icon">📷</div>' +
        '<div class="piu-uploader__text">点击上传</div>' +
        '<div class="piu-uploader__hint">JPG/PNG ' + _maxSizeMB + 'MB</div>' +
      '</div>' +
    '</div>' +
    '<input type="file" class="piu-hidden-input" id="piu-file-' + Date.now() + '" multiple accept="' + _accept + '">';

  _grid = container.querySelector('[id^="piu-grid"]');
  _uploader = container.querySelector('.piu-uploader');
  _fileInput = container.querySelector('[id^="piu-file-"]');
  _countEl = container.querySelector('[id^="piu-cnt-"]');

  function render() {
    var items = _grid.querySelectorAll('.piu-item');
    items.forEach(function(el) { el.remove(); });
    _images.forEach(function(url, idx) {
      _uploader.insertAdjacentHTML('beforebegin',
        '<div class="piu-item" draggable="true" data-source="saved" data-index="' + idx + '" style="width:' + _itemSize + 'px;height:' + _itemSize + 'px">' +
        '<img src="' + _escapeHtml(_resolveUrl(url)) + '" alt="图片">' +
        '<button class="piu-remove-btn" type="button"></button></div>');
    });
    _pending.forEach(function(item, idx) {
      _uploader.insertAdjacentHTML('beforebegin',
        '<div class="piu-item" draggable="true" data-source="pending" data-index="' + idx + '" style="width:' + _itemSize + 'px;height:' + _itemSize + 'px">' +
        '<img src="' + _escapeHtml(item.blobUrl || '') + '" alt="图片">' +
        '<button class="piu-remove-btn" type="button"></button></div>');
    });
    updateCoverBadge();
    updateCount();
    bindItemEvents();
    initDragDrop();
    var total = _images.length + _pending.length;
    _uploader.style.display = (total >= _maxCount) ? 'none' : 'flex';
  }

  function updateCoverBadge() {
    var allItems = _grid.querySelectorAll('.piu-item');
    allItems.forEach(function(el, i) {
      var badge = el.querySelector('.piu-cover-badge');
      if (i === 0) {
        if (!badge) {
          var b = document.createElement('span');
          b.className = 'piu-cover-badge';
          b.textContent = '封面图';
          el.insertBefore(b, el.firstChild);
        }
      } else {
        if (badge) badge.remove();
      }
    });
  }

  function updateCount() {
    var total = _images.length + _pending.length;
    _countEl.textContent = '已选择 ' + total + ' 张图片';
    _onChange(total);
  }

  function _resolveUrl(url) {
    if (!url) return '';
    if (url.indexOf('http') === 0 || url.indexOf('/') === 0 || url.indexOf('blob:') === 0 || url.indexOf('data:') === 0) return url;
    return '/' + url;
  }

  function _escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function bindItemEvents() {
    _grid.querySelectorAll('.piu-item').forEach(function(el) {
      var removeBtn = el.querySelector('.piu-remove-btn');
      if (removeBtn) {
        removeBtn.onclick = function(e) { e.stopPropagation(); removeItem(el); };
      }
      el.onclick = function(e) {
        if (e.target.closest('.piu-remove-btn')) return;
        showPreview(el);
      };
    });
  }

  function removeItem(el) {
    var source = el.getAttribute('data-source');
    var idx = parseInt(el.getAttribute('data-index'));
    if (source === 'saved') {
      _images.splice(idx, 1);
    } else {
      if (_pending[idx] && _pending[idx].blobUrl) URL.revokeObjectURL(_pending[idx].blobUrl);
      _pending.splice(idx, 1);
    }
    el.remove();
    rebuildDataFromDOM();
    render();
  }

  function rebuildDataFromDOM() {
    var newSaved = [], newPending = [];
    _grid.querySelectorAll('.piu-item').forEach(function(el) {
      var src = el.getAttribute('data-source');
      var idx = parseInt(el.getAttribute('data-index'));
      if (src === 'saved' && _images[idx] !== undefined) newSaved.push(_images[idx]);
      else if (src === 'pending' && _pending[idx] !== undefined) newPending.push(_pending[idx]);
    });
    _images = newSaved; _pending = newPending;
  }

  function initDragDrop() {
    var dragEl = null;
    _grid.ondragstart = function(e) {
      var item = e.target.closest('.piu-item');
      if (!item) { e.preventDefault(); return; }
      dragEl = item; item.classList.add('piu-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', 'x');
    };
    _grid.ondragover = function(e) {
      e.preventDefault(); e.dataTransfer.dropEffect = 'move';
      _grid.querySelectorAll('.piu-item').forEach(function(it) { it.classList.remove('piu-drag-over'); });
      var target = e.target.closest('.piu-item');
      if (target) target.classList.add('piu-drag-over');
    };
    _grid.ondrop = function(e) {
      e.preventDefault();
      _grid.querySelectorAll('.piu-item').forEach(function(it) { it.classList.remove('piu-drag-over', 'piu-dragging'); });
      if (!dragEl) return;
      rebuildDataFromDOM();
      dragEl = null;
      render();
    };
    _grid.ondragend = function(e) {
      _grid.querySelectorAll('.piu-item').forEach(function(it) { it.classList.remove('piu-dragging', 'piu-drag-over'); });
      dragEl = null;
    };
  }

  _uploader.onclick = function() { _fileInput.click(); };
  _uploader.addEventListener('dragover', function(e) { e.preventDefault(); _uploader.classList.add('piu-drag-over-upload'); });
  _uploader.addEventListener('dragleave', function(e) { e.preventDefault(); _uploader.classList.remove('piu-drag-over-upload'); });
  _uploader.addEventListener('drop', function(e) {
    e.preventDefault(); _uploader.classList.remove('piu-drag-over-upload');
    handleFiles(e.dataTransfer.files);
  });

  _fileInput.onchange = function(e) {
    handleFiles(e.target.files);
    this.value = '';
  };

  function handleFiles(fileList) {
    if (!fileList || fileList.length === 0) return;
    Array.from(fileList).forEach(function(file) {
      if (!file.type.match(/image\//)) return;
      if (file.size > _maxSizeBytes) { var sm = (file.size / 1024 / 1024).toFixed(1); alert('"' + file.name + '" 大小为 ' + sm + 'MB，超过 ' + _maxSizeMB + 'MB 限制，请压缩后重试'); return; }
      var total = _images.length + _pending.length;
      if (total >= _maxCount) return;
      var blobUrl = URL.createObjectURL(file);
      _pending.push({ file: file, blobUrl: blobUrl });
    });
    render();
  }

  function showPreview(el) {
    var img = el.querySelector('img');
    if (!img) return;
    var allImages = _grid.querySelectorAll('.piu-item img');
    var currentIdx = -1, srcs = [];
    allImages.forEach(function(im, i) { srcs.push(im.src); if (im === img) currentIdx = i; });
    var overlay = document.createElement('div');
    overlay.className = 'piu-preview-overlay';
    overlay.innerHTML =
      '<img src="' + _escapeHtml(img.src) + '" id="piu-pv-img">' +
      '<button class="piu-preview-nav piu-preview-nav--prev">‹</button>' +
      '<button class="piu-preview-nav piu-preview-nav--next">›</button>';
    var pvImg = overlay.querySelector('#piu-pv-img');
    var prevBtn = overlay.querySelector('.piu-preview-nav--prev');
    var nextBtn = overlay.querySelector('.piu-preview-nav--next');
    function showAt(idx) {
      idx = (idx + srcs.length) % srcs.length;
      currentIdx = idx;
      pvImg.src = srcs[idx];
    }
    if (prevBtn) prevBtn.onclick = function(e) { e.stopPropagation(); showAt(currentIdx - 1); };
    if (nextBtn) nextBtn.onclick = function(e) { e.stopPropagation(); showAt(currentIdx + 1); };
    overlay.onclick = function() { overlay.remove(); };
    document.addEventListener('keydown', function handler(e) {
      if (!overlay.parentNode) { document.removeEventListener('keydown', handler); return; }
      if (e.key === 'Escape') overlay.remove();
      else if (e.key === 'ArrowLeft') showAt(currentIdx - 1);
      else if (e.key === 'ArrowRight') showAt(currentIdx + 1);
    });
    document.body.appendChild(overlay);
  }

  this.getImages = function() { return _images.slice(); };
  this.getPendingItems = function() {
    return _pending.map(function(item) { return { file: item.file, blobUrl: item.blobUrl }; });
  };
  this.clear = function() {
    _pending.forEach(function(item) { if (item.blobUrl) URL.revokeObjectURL(item.blobUrl); });
    _images = []; _pending = [];
    render();
  };
  this.loadImages = function(arr) {
    _pending.forEach(function(item) { if (item.blobUrl) URL.revokeObjectURL(item.blobUrl); });
    _images = Array.isArray(arr) ? arr.slice() : [];
    _pending = [];
    render();
  };
  this.destroy = function() {
    _pending.forEach(function(item) { if (item.blobUrl) URL.revokeObjectURL(item.blobUrl); });
    _grid.innerHTML = '';
    container.innerHTML = '';
  };

  render();
}

    </script>
    
    <!-- 新增文章抽屉 -->
    <div id="articleDrawer" class="article-drawer">
        <div class="drawer-mask" onclick="closeArticleDrawer()"></div>
        <div class="drawer-content">
            <!-- 左侧：手机预览 -->
            <div class="drawer-left">
                <div class="preview-header">
                    <span>📱 手机预览</span>
                    <span>实时</span>
                </div>
                <div class="phone-wrapper">
                    <div class="phone-shell">
                        <div class="phone-screen">
                            <div class="phone-notch">
                                <div class="speaker"></div>
                                <div class="camera"></div>
                            </div>
                            <iframe id="drawerPreviewIframe"></iframe>
                            <div class="phone-home"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 右侧：新增文章组件 -->
            <div class="drawer-right">
                <div class="drawer-header">
                    <h3>📝 新增文章</h3>
                    <button class="drawer-close" onclick="closeArticleDrawer()">×</button>
                </div>
                <div class="drawer-body" id="articleDrawerBody">
                <div style="text-align:center;padding:60px;color:#999;">加载中...</div>
            </div>
        </div>
    </div>
    
    <!-- 点击抽屉空白关闭 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var drawer = document.getElementById('articleDrawer');
        if (drawer) {
            drawer.addEventListener('click', function(e) {
                if (e.target === drawer && !drawer.classList.contains('closing')) {
                    closeArticleDrawer();
                }
            });
        }
    });
    </script>
    
    <?php require_once __DIR__ . '/../includes/footer.php';
