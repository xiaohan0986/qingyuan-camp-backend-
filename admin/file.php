<?php
/**
 * 文件管理后台界面
 * 调用 DX Storage 远程存储服务 API
 */

// 设置当前页面标识
$currentPage = 'file';
$pageTitle = '文件管理';

// 禁用缓存，确保每次加载最新数据
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 包含 header（跳过登录检查）
session_start();

// 模拟登录用户信息
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['login_type'] = 'role';
    $_SESSION['role_id'] = 1;
    $_SESSION['role_key'] = 'admin';
    $_SESSION['role_name'] = '管理员';
}

// 设置 currentUser 变量（header.php 需要）
$currentUser = [
    'id' => $_SESSION['user_id'],
    'nickname' => '管理员',
    'avatar' => '',
    'role' => 2
];

// 设置页面特定的内联 CSS
$pageInlineCss = '
/* 确保 html 和 body 占满视口 */
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

.file-manager-container {
    padding: 0;
    background: #f5f7fa;
    min-height: calc(100vh - 120px);
}
.folder-view-container {
    display: flex;
    gap: 16px;
    padding: 0;
    background: #f5f7fa;
    min-height: calc(100vh - 120px);
}
.folder-sidebar {
    width: 280px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.folder-sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
}
.folder-sidebar.collapsed .sidebar-content,
.folder-sidebar.collapsed .sidebar-header {
    display: none;
}
.folder-sidebar .sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e8e8e8;
}
.folder-sidebar .sidebar-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}
.folder-sidebar .sidebar-add-btn {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    border: 1px solid #d9d9d9;
    background: #fff;
    cursor: pointer;
    font-size: 18px;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
}
.folder-sidebar .sidebar-add-btn:hover {
    background: #4c7dff;
    color: #fff;
    border-color: #4c7dff;
}
.folder-sidebar .sidebar-content {
    padding: 12px 0;
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}
.folder-item {
    padding: 12px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
    position: relative;
}
.folder-item:hover { background: #f5f7fa; }
.folder-item.active {
    background: #e6f4ff;
    border-right: 3px solid #4c7dff;
    padding-right: 17px;
}
.folder-count {
    font-size: 12px;
    color: #999;
    background: #f5f5f5;
    padding: 2px 8px;
    border-radius: 10px;
}
.folder-item-delete {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    background: #fff1f0;
    border: none;
    border-radius: 4px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 16px;
    color: #ff4d4f;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.folder-item:hover .folder-item-delete {
    background: #ff4d4f;
    color: #fff;
}
.folder-divider {
    padding: 8px 20px;
    font-size: 12px;
    color: #999;
    border-top: 1px solid #f0f0f0;
    margin-top: 8px;
}
.folder-divider button:hover {
    background: #f0f0f0;
    color: #666;
}
#sidebarGroups.collapsed { display: none; }
.folder-main {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    overflow: hidden;
}
.folder-main .file-header h2 {
    margin: 0;
    font-size: 18px;
    color: #333;
}
.file-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.file-header h1 {
    margin: 0;
    font-size: 20px;
    color: #333;
}
.header-actions {
    display: flex;
    gap: 10px;
}
.upload-btn, .folder-view-btn, .create-group-btn {
    background: #4c7dff;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
    text-decoration: none;
    display: inline-block;
}
.upload-btn:hover { background: #3b68d8; }
.create-group-btn { background: #722ed1; }
.create-group-btn:hover { background: #5b21b0; }
.folder-view-btn { background: #52c41a; }
.folder-view-btn:hover { background: #389e0d; }
.file-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.stat-card h3 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}
.stat-card .number {
    font-size: 28px;
    font-weight: bold;
    color: #4c7dff;
}
.filter-bar {
    background: #fff;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
#selectAll {
    cursor: pointer;
}
.filter-bar label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
    white-space: nowrap;
}
.filter-bar select, .filter-bar input, .filter-bar button {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    white-space: nowrap;
}
.filter-bar input { 
    min-width: 200px; 
    flex: 1;
}
.filter-bar button { padding: 8px 16px; white-space: nowrap; }
.filter-group { flex-shrink: 0; }
.filter-group:nth-child(2) { flex: 1; }
.clear-filter-btn {
    margin-left: auto;
    background: #f5f5f5;
    transition: all 0.2s;
}
.clear-filter-btn:hover {
    background: #ff4d4f;
    color: #fff;
    border-color: #ff4d4f;
}
.file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
.file-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}
.file-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}
.file-card.selected { border: 2px solid #4c7dff; }
.file-checkbox { cursor: pointer; width: 18px; height: 18px; }
.file-preview {
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    overflow: hidden;
    cursor: pointer;
}
.file-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
.file-preview .file-icon { font-size: 56px; color: #ccc; }
.file-info { padding: 14px; }
.file-name {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #333;
}
.file-meta {
    font-size: 12px;
    color: #999;
    margin-bottom: 12px;
    line-height: 1.5;
}
.category-tag {
    display: inline-block;
    padding: 2px 8px;
    background: #f3e5f5;
    color: #722ed1;
    border-radius: 12px;
    font-size: 11px;
    margin-top: 4px;
}
.file-actions { display: flex; gap: 8px; }
.file-actions button {
    flex: 1;
    padding: 6px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: background 0.2s;
}
.btn-view { background: #e6f4ff; color: #1890ff; }
.btn-view:hover { background: #bae7ff; }
.btn-copy { background: #f6ffed; color: #52c41a; }
.btn-copy:hover { background: #d9f7be; }
.btn-delete { background: #fff1f0; color: #ff4d4f; }
.btn-delete:hover { background: #ffccc7; }
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 32px;
}
.pagination a {
    padding: 8px 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
    font-size: 14px;
    transition: all 0.2s;
}
.pagination a:hover { border-color: #4c7dff; color: #4c7dff; }
.pagination a.active {
    background: #4c7dff;
    color: #fff;
    border-color: #4c7dff;
}
/* 弹窗遮罩层 - 全屏覆盖 */
.modal, .modal.show {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    min-width: 100vw !important;
    min-height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    background: rgba(0,0,0,0.45) !important;
    z-index: 9999999 !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 0 !important;
}
.modal.show { 
    display: flex !important; 
}
.modal-content {
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    width: 480px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 24px rgba(0,0,0,0.15);
    position: relative;
    z-index: 10000000;
}
.modal-content h2 { margin: 0 0 20px 0; font-size: 18px; color: #333; }
.upload-area {
    border: 2px dashed #d9d9d9;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.3s;
}
.upload-area:hover { border-color: #4c7dff; }
.upload-area.dragover { border-color: #4c7dff; background: #f0f5ff; }
.upload-area p { margin: 0; color: #666; font-size: 14px; }
.upload-area p small { display: block; margin-top: 8px; color: #999; font-size: 12px; }
#uploadProgress { margin-top: 20px; }
#progressBar {
    height: 6px;
    background: linear-gradient(90deg, #4c7dff, #6b95ff);
    border-radius: 3px;
    transition: width 0.3s;
}
#uploadStatus { text-align: center; margin-top: 10px; font-size: 13px; color: #666; }
.modal-footer { margin-top: 20px; text-align: right; }
.modal-footer button {
    padding: 8px 20px;
    border: 1px solid #d9d9d9;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    transition: all 0.2s;
}
.modal-footer button:hover { border-color: #4c7dff; color: #4c7dff; }
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.empty-state .icon { font-size: 64px; color: #ddd; margin-bottom: 16px; }
.empty-state p { color: #999; font-size: 14px; }
.empty-state .warning {
    color: #faad14;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 8px;
}
.group-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 4px 10px;
    background: rgba(114, 46, 209, 0.9);
    color: #fff;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    z-index: 10;
}
.preview-modal .modal-content { max-width: 900px; padding: 0; overflow: hidden; }
.preview-image { width: 100%; max-height: 80vh; object-fit: contain; }
.preview-header {
    padding: 16px 24px;
    background: #fafafa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.preview-header h3 { margin: 0; font-size: 16px; color: #333; }
.close-preview {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}
.close-preview:hover { color: #333; }
';

require_once __DIR__ . '/includes/header.php';

// 加载 DX Storage 配置
$storageConfig = require __DIR__ . '/../config/dx_storage.php';

// 获取筛选参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = 20;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$groupId = isset($_GET['group_id']) ? $_GET['group_id'] : '';

// 默认只显示根目录文件（未分组）
if ($groupId === '' && !isset($_GET['group_id'])) {
    $groupId = '0'; // 默认筛选根目录
}

// 调用远程 API 获取文件列表
try {
    $apiUrl = $storageConfig['api']['files'];
    $params = [
        'action' => 'list',  // 添加 action 参数
        'page' => $page,
        'page_size' => $pageSize,
    ];
    
    if ($search) $params['search'] = $search;
    if ($typeFilter) $params['type'] = $typeFilter;
    if ($groupId !== '') $params['group_id'] = $groupId;
    
    $apiUrl .= '?' . http_build_query($params);
    
    // 使用 cURL 代替 file_get_contents（更稳定）
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // 同服务器访问，跳过 SSL 验证（因为 hosts 指向本地）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response && $httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && $result['code'] === 200) {
            $files = $result['data']['files'] ?? [];
            $pagination = $result['data']['pagination'] ?? [];
            $total = $pagination['total'] ?? 0;
            $totalPages = $pagination['total_pages'] ?? 1;
        } else {
            $files = [];
            $total = 0;
            $totalPages = 1;
            $errorMsg = 'API 返回错误：' . ($result['message'] ?? '未知错误');
        }
    } else {
        $files = [];
        $total = 0;
        $totalPages = 1;
        $errorMsg = 'API 请求失败：' . ($error ?: 'HTTP ' . $httpCode);
    }
    
} catch (Exception $e) {
    $files = [];
    $total = 0;
    $totalPages = 1;
    $errorMsg = 'API 调用失败：' . $e->getMessage();
}

// 获取分组列表
try {
    // 配置中已包含 ?action=list，直接使用
    $groupsApi = $storageConfig['api']['groups'];
    
    // 使用 cURL 代替 file_get_contents
    $ch = curl_init($groupsApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // 同服务器访问，跳过 SSL 验证（因为 hosts 指向本地）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $groupsResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($groupsResponse && $httpCode === 200) {
        $groupsResult = json_decode($groupsResponse, true);
        $groups = $groupsResult['data'] ?? [];
    } else {
        $groups = [];
        error_log('分组 API 请求失败：HTTP ' . $httpCode . ' - ' . $error);
    }
} catch (Exception $e) {
    $groups = [];
    error_log('分组 API 异常：' . $e->getMessage());
}

// 获取统计信息（通过 API）
$stats = [
    'total' => $total,
    'images' => 0,
    'total_size' => 0
];
foreach ($files as $file) {
    if (in_array(strtolower($file['extension'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $stats['images']++;
    }
    $stats['total_size'] += intval($file['file_size'] ?? 0);
}
?>



<div class="file-manager-container">
    <!-- 文件视图容器（包含左侧侧边栏和右侧文件列表） -->
    <div class="folder-view-container" id="folderViewContainer" >
        <!-- 左侧侧边栏 -->
        <div class="folder-sidebar" id="folderSidebar">
            <div class="sidebar-header">
                <h3>📁 文件分组</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button onclick="toggleFolderSidebar()" id="toggleSidebarBtn" title="折叠/展开" 
                            style="background:none;border:none;cursor:pointer;font-size:16px;color:#999;padding:4px;border-radius:4px;" 
                            title="折叠侧边栏">📍
                    </button>
                    <button onclick="showCreateGroupModal()" class="sidebar-add-btn" title="新建分组">+</button>
                </div>
            </div>
            <div class="sidebar-content" id="sidebarContent">
                <div class="folder-item <?= $groupId === '0' || $groupId === '' ? 'active' : '' ?>" 
                     onclick="loadFolder('0')" style="cursor:pointer;">
                    🏠 根目录
                    <span class="folder-count" id="rootCount"><?= $total ?></span>
                </div>
                <div class="folder-divider">分组列表</div>
                <div id="sidebarGroups">
                    <!-- 动态加载分组 -->
                </div>
            </div>
        </div>
        
        <!-- 侧边栏折叠时的展开按钮 -->
        <button id="expandSidebarBtn" onclick="toggleFolderSidebar()" 
                style="display:none;position:fixed;left:20px;top:120px;width:40px;height:40px;background:#4c7dff;color:#fff;border:none;border-radius:50%;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:18px;z-index:100;"
                title="展开侧边栏">
            📁
        </button>
        
        <!-- 右侧文件列表 -->
        <div class="folder-main">
            <div class="file-header">
                <h2 id="currentFolderName">
                    <?php if ($groupId === '0' || $groupId === ''): ?>
                        🏠 根目录
                    <?php else: ?>
                        🏠 根目录 - 📁 
                        <?php 
                        // 查找当前分组的名称
                        $currentGroupName = '未知分组';
                        foreach ($groups as $group) {
                            if ($group['id'] == $groupId) {
                                $currentGroupName = $group['name'];
                                break;
                            }
                        }
                        echo htmlspecialchars($currentGroupName);
                        ?>
                    <?php endif; ?>
                </h2>
                <div class="header-actions">
                    <button class="upload-btn" onclick="showUploadModal()">+ 上传文件</button>
                    <button class="create-group-btn" onclick="showCreateGroupModal()">+ 新建分组</button>
                </div>
            </div>
            
            <!-- 统计卡片 -->
            <div class="file-stats">
                <div class="stat-card">
                    <h3>总文件数</h3>
                    <div class="number"><?= (int)$total ?></div>
                </div>
                <div class="stat-card">
                    <h3>图片</h3>
                    <div class="number"><?= (int)$stats['images'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>总大小</h3>
                    <div class="number"><?= number_format($stats['total_size'] / 1024 / 1024, 2) ?> MB</div>
                </div>
            </div>
            
            <!-- 筛选栏 -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width:16px;height:16px;cursor:pointer;">
                        <span style="font-size:14px;color:#666;font-weight:500;">全选</span>
                    </label>
                </div>
                
                <div class="filter-group">
                    <label>🔍 搜索：</label>
                    <input type="text" id="searchInput" placeholder="搜索文件名..." value="<?= htmlspecialchars($search) ?>" 
                           onkeypress="if(event.keyCode===13) doSearch()" style="min-width:200px;">
                    <button onclick="doSearch()" style="padding:8px 16px;background:#4c7dff;color:#fff;border:none;border-radius:4px;cursor:pointer;margin-left:8px;">搜索</button>
                </div>
                
                <div class="filter-group">
                    <label>类型：</label>
                    <select onchange="filterByType(this.value)" style="min-width:100px;padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <option value="">全部类型</option>
                        <option value="jpg" <?= $typeFilter === 'jpg' ? 'selected' : '' ?>>JPG</option>
                        <option value="png" <?= $typeFilter === 'png' ? 'selected' : '' ?>>PNG</option>
                        <option value="gif" <?= $typeFilter === 'gif' ? 'selected' : '' ?>>GIF</option>
                        <option value="webp" <?= $typeFilter === 'webp' ? 'selected' : '' ?>>WebP</option>
                    </select>
                </div>
                
                <?php if ($groupId || $search || $typeFilter): ?>
                    <a href="?" class="clear-filter-btn" style="padding:8px 16px;color:#666;text-decoration:none;border:1px solid #ddd;border-radius:4px;white-space:nowrap;">清除筛选</a>
                <?php endif; ?>
            </div>
    
            <?php if (isset($errorMsg)): ?>
                <div style="background:#fff2f0;border:1px solid #ffccc7;border-radius:8px;padding:16px;color:#ff4d4f;margin-bottom:20px;">
                    ⚠️ <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($files)): ?>
                <div class="empty-state">
            <div class="icon"><?= ($search || $typeFilter) ? '🔍' : '📁' ?></div>
            <?php if ($search || $typeFilter): ?>
                <p class="warning">⚠️ 无查询结果</p>
                <p style="color:#999;font-size:13px;margin-top:8px;">
                    搜索条件：
                    <?php if ($search): ?>搜索"<?= htmlspecialchars($search) ?>" <?php endif; ?>
                    <?php if ($typeFilter): ?>类型=<?= strtoupper(htmlspecialchars($typeFilter)) ?> <?php endif; ?>
                </p>
                <p style="margin-top:12px;">
                    <a href="?" style="color:#4c7dff;text-decoration:none;">清除筛选条件</a>
                </p>
            <?php else: ?>
                <p>暂无文件，点击上方"上传文件"按钮添加</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- 批量操作栏 -->
        <div id="batchActions" style="display:none;background:#fff;padding:16px 20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);align-items:center;gap:12px;">
            <label style="font-size:14px;color:#666;font-weight:500;">已选择 <span id="selectedCount" style="color:#4c7dff;font-weight:bold;">0</span> 个文件</label>
            <button onclick="batchCopy()" style="padding:8px 16px;background:#1890ff;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">📋 复制链接</button>
            <button onclick="batchDownload()" style="padding:8px 16px;background:#52c41a;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">⬇️ 批量下载</button>
            <button onclick="showMoveModal()" style="padding:8px 16px;background:#722ed1;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">📁 移动分组</button>
            <button onclick="batchDelete()" style="padding:8px 16px;background:#ff4d4f;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">批量删除</button>
            <button onclick="clearSelection()" style="padding:8px 16px;background:#f0f0f0;color:#666;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:14px;">取消选择</button>
                </div>
                
                <div class="file-grid">
                    <?php foreach ($files as $file): ?>
                <div class="file-card" data-id="<?= (int)$file['id'] ?>">
                    <label style="position:absolute;top:8px;left:8px;z-index:20;background:rgba(255,255,255,0.9);border-radius:4px;padding:4px;">
                        <input type="checkbox" class="file-checkbox" value="<?= (int)$file['id'] ?>" onchange="updateBatchActions()">
                    </label>
                    <?php if (!empty($file['group_path'])): ?>
                        <span class="group-badge">📁 <?= htmlspecialchars(basename($file['group_path'])) ?></span>
                    <?php endif; ?>
                    <div class="file-preview" onclick="showPreview('<?= htmlspecialchars($storageConfig['base_url'] . $file['file_url']) ?>', '<?= htmlspecialchars($file['store_name'], ENT_QUOTES) ?>')">
                        <?php if (in_array(strtolower($file['extension'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?= htmlspecialchars($storageConfig['base_url'] . $file['file_url']) ?>" 
                                 alt="<?= htmlspecialchars($file['store_name']) ?>"
                                 onerror="this.style.display='none';this.parentElement.innerHTML='📄 图片加载失败'">
                        <?php else: ?>
                            <div class="file-icon">📄</div>
                        <?php endif; ?>
                    </div>
                    <div class="file-info">
                        <div class="file-name" title="<?= htmlspecialchars($file['store_name']) ?>">
                            <?= htmlspecialchars($file['store_name']) ?>
                        </div>
                        <div class="file-meta">
                            <?= strtoupper(htmlspecialchars($file['extension'] ?? '')) ?> · <?= formatSize($file['file_size']) ?>
                        </div>
                        <div class="file-actions">
                            <button class="btn-view" onclick="window.open('<?= htmlspecialchars($storageConfig['base_url'] . $file['file_url']) ?>', '_blank')">查看</button>
                            <button class="btn-copy" onclick="copyFileUrl('<?= htmlspecialchars($storageConfig['base_url'] . $file['file_url'], ENT_QUOTES) ?>')">复制</button>
                            <button class="btn-delete" onclick="deleteFile(<?= (int)$file['id'] ?>, '<?= htmlspecialchars($file['store_name'], ENT_QUOTES) ?>')">删除</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php 
                    $params = ['page' => $i];
                    if ($search) $params['search'] = $search;
                    if ($typeFilter) $params['type'] = $typeFilter;
                    if ($groupId) $params['group_id'] = $groupId;
                    ?>
                    <a href="?<?= http_build_query($params) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- 上传模态框 -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h2>上传文件</h2>
        <div class="upload-area" id="uploadArea">
            <p>📁 点击或拖拽文件到此处上传</p>
            <p><small>支持 JPG、PNG、GIF、WebP，最大 10MB</small></p>
            <input type="file" id="fileInput" style="display: none" accept="image/*" multiple>
        </div>
        <div id="uploadProgress" style="display: none;">
            <div style="background: #f0f0f0; border-radius: 3px; overflow: hidden;">
                <div id="progressBar" style="width: 0%;"></div>
            </div>
            <p id="uploadStatus">上传中...</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeUploadModal()">取消</button>
        </div>
    </div>
</div>

<!-- 新建分组模态框 -->
<div id="createGroupModal" class="modal">
    <div class="modal-content">
        <h2>📁 新建分组</h2>
        <div style="margin:20px 0;">
            <label style="display:block;margin-bottom:8px;font-size:14px;color:#666;">分组名称：</label>
            <input type="text" id="groupNameInput" placeholder="请输入分组名称" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
        </div>
        <div style="margin:20px 0;">
            <label style="display:block;margin-bottom:8px;font-size:14px;color:#666;">分组描述：</label>
            <textarea id="groupDescInput" placeholder="请输入分组描述（可选）" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;min-height:80px;resize:vertical;"></textarea>
        </div>
        <div id="createGroupStatus" style="margin:10px 0;text-align:center;"></div>
        <div class="modal-footer">
            <button onclick="closeCreateGroupModal()">取消</button>
            <button onclick="createGroup()" style="padding:8px 20px;background:#722ed1;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">确认创建</button>
        </div>
    </div>
</div>

<!-- 预览模态框 -->
<div id="previewModal" class="modal preview-modal">
    <div class="modal-content">
        <div class="preview-header">
            <h3 id="previewTitle">图片预览</h3>
            <button class="close-preview" onclick="closePreview()">&times;</button>
        </div>
        <img id="previewImage" class="preview-image" src="">
    </div>
</div>

<!-- 移动分组模态框 -->
<div id="moveModal" class="modal">
    <div class="modal-content">
        <h2>📁 移动到分组</h2>
        <div style="margin:20px 0;">
            <label style="display:block;margin-bottom:8px;font-size:14px;color:#666;">选择目标分组：</label>
            <select id="moveGroupSelect" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                <option value="">根目录（不分组）</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= (int)$group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="text-align:right;">
            <button onclick="closeMoveModal()" style="padding:8px 20px;border:1px solid #ddd;background:#fff;border-radius:4px;cursor:pointer;font-size:14px;margin-right:10px;">取消</button>
            <button onclick="batchMove()" style="padding:8px 20px;background:#722ed1;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">确认移动</button>
        </div>
    </div>
</div>

<script>
// DX Storage API 配置 (版本：2026-05-05-1506 - 强制刷新)
const DX_STORAGE = {
    baseUrl: '<?= $storageConfig['base_url'] ?>',
    version: '2026-05-05-1520',
    api: {
        upload: '<?= $storageConfig['api']['upload'] ?>',
        files: '<?= $storageConfig['api']['files'] ?>',
        delete: '<?= $storageConfig['api']['delete'] ?>',
        batch_delete: '<?= $storageConfig['api']['batch_delete'] ?>',
        groups: '<?= $storageConfig['api']['groups'] ?>',
        create_group: '<?= $storageConfig['api']['create_group'] ?>',
        delete_group: '<?= $storageConfig['api']['delete_group'] ?>',
        move_file: '<?= $storageConfig['api']['move_file'] ?>'
    }
};

function showUploadModal() {
    document.getElementById('uploadModal').classList.add('show');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('show');
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('fileInput').value = '';
}

// 文件夹视图切换
function toggleFolderView() {
    const container = document.getElementById('folderViewContainer');
    const isHidden = container.style.display === 'none';
    
    if (isHidden) {
        container.style.display = 'flex';
        loadSidebarGroups();
    } else {
        container.style.display = 'none';
    }
}

// 切换整个侧边栏折叠/展开
function toggleFolderSidebar() {
    const sidebar = document.getElementById('folderSidebar');
    const expandBtn = document.getElementById('expandSidebarBtn');
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    
    if (sidebar.classList.contains('collapsed')) {
        // 展开侧边栏
        sidebar.classList.remove('collapsed');
        expandBtn.style.display = 'none';
        if (toggleBtn) toggleBtn.textContent = '📍';
        localStorage.setItem('folderSidebarCollapsed', 'false');
    } else {
        // 折叠侧边栏
        sidebar.classList.add('collapsed');
        expandBtn.style.display = 'block';
        if (toggleBtn) toggleBtn.textContent = '📎';
        localStorage.setItem('folderSidebarCollapsed', 'true');
    }
}

// 加载侧边栏分组
function loadSidebarGroups() {
    fetch(DX_STORAGE.api.groups)
        .then(res => res.json())
        .then(result => {
            if (result.code === 200) {
                const groups = result.data || [];
                const sidebarGroups = document.getElementById('sidebarGroups');
                
                if (groups.length === 0) {
                    sidebarGroups.innerHTML = '<div style="padding:20px;text-align:center;color:#999;font-size:14px;">暂无分组</div>';
                } else {
                    sidebarGroups.innerHTML = groups.map(group => `
                        <div class="folder-item" onclick="loadFolder('${group.id}', '${group.name.replace(/'/g, "\\'")}')" style="cursor:pointer;">
                            📁 ${group.name}
                            <span class="folder-count">${group.file_count || 0}</span>
                            <button class="folder-item-delete" onclick="event.stopPropagation(); deleteGroup(${group.id}, '${group.name.replace(/'/g, "\\'")}')" title="删除分组">🗑️</button>
                        </div>
                    `).join('');
                }
                
                // 恢复侧边栏折叠状态
                const isCollapsed = localStorage.getItem('folderSidebarCollapsed') === 'true';
                if (isCollapsed) {
                    const sidebar = document.getElementById('folderSidebar');
                    const expandBtn = document.getElementById('expandSidebarBtn');
                    const toggleBtn = document.getElementById('toggleSidebarBtn');
                    
                    sidebar.classList.add('collapsed');
                    expandBtn.style.display = 'block';
                    if (toggleBtn) {
                        toggleBtn.textContent = '📎';
                    }
                }
            }
        })
        .catch(err => console.error('加载分组失败:', err));
}

// 加载文件夹
function loadFolder(groupId, groupName = '根目录') {
    // 更新当前文件夹名称：根目录 - 分组名称
    if (groupId === '0' || groupId === '') {
        document.getElementById('currentFolderName').textContent = '🏠 根目录';
    } else {
        document.getElementById('currentFolderName').textContent = '🏠 根目录 - 📁 ' + groupName;
    }
    
    // 更新侧边栏选中状态
    document.querySelectorAll('.folder-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // 重新加载文件列表
    const params = new URLSearchParams({
        page: 1,
        page_size: 20,
        group_id: groupId
    });
    
    const url = window.location.pathname + '?' + params.toString();
    window.history.pushState({}, '', url);
    
    // 刷新文件列表（这里简单处理，实际应该用 AJAX 加载）
    location.reload();
}

// 新建分组相关函数
function showCreateGroupModal() {
    document.getElementById('createGroupModal').classList.add('show');
    document.getElementById('groupNameInput').value = '';
    document.getElementById('groupDescInput').value = '';
    document.getElementById('createGroupStatus').textContent = '';
}

function closeCreateGroupModal() {
    document.getElementById('createGroupModal').classList.remove('show');
}

// 删除分组
function deleteGroup(groupId, groupName) {
    if (!confirm(`确定要删除分组 "${groupName}" 吗？\n注意：删除分组不会删除组内文件，文件会移回根目录。`)) {
        return;
    }
    
    fetch(DX_STORAGE.api.delete_group, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: groupId})
    })
    .then(response => response.json())
    .then(result => {
        if (result.code === 200) {
            alert(`✅ 分组 "${groupName}" 已删除`);
            loadSidebarGroups(); // 刷新侧边栏
            // 如果当前正在查看这个分组，返回根目录
            const currentParams = new URLSearchParams(window.location.search);
            if (currentParams.get('group_id') == groupId) {
                window.location.href = window.location.pathname;
            }
        } else {
            alert('删除失败：' + (result.message || '未知错误'));
        }
    })
    .catch(error => alert('删除失败：' + error.message));
}

function createGroup() {
    const name = document.getElementById('groupNameInput').value.trim();
    const description = document.getElementById('groupDescInput').value.trim();
    const statusDiv = document.getElementById('createGroupStatus');
    
    if (!name) {
        statusDiv.textContent = '❌ 请输入分组名称';
        statusDiv.style.color = 'red';
        return;
    }
    
    statusDiv.textContent = '⏳ 创建中...';
    statusDiv.style.color = '#666';
    
    // 调用远程 API 创建分组
    fetch(DX_STORAGE.api.create_group, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            name: name,
            description: description || ''
        })
    })
    .then(response => response.json())
    .then(result => {
        console.log('创建分组响应:', result);
        
        // 根据响应判断是否成功
        if (result.code === 200 && result.data) {
            statusDiv.textContent = '✅ 创建成功！';
            statusDiv.style.color = 'green';
            setTimeout(() => {
                closeCreateGroupModal();
                loadSidebarGroups(); // 刷新侧边栏
                alert('分组创建成功！');
            }, 1000);
        } else {
            statusDiv.textContent = '❌ 创建失败：' + (result.message || '未知错误');
            statusDiv.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('创建分组错误:', error);
        statusDiv.textContent = '❌ 创建失败：' + error.message;
        statusDiv.style.color = 'red';
    });
}

function doSearch() {
    const search = document.getElementById('searchInput').value;
    let url = '?search=' + encodeURIComponent(search);
    <?php if ($typeFilter): ?>url += '&type=<?= $typeFilter ?>';<?php endif; ?>
    // <?php if ($groupId): ?>url += '&group_id=<?= $groupId ?>';<?php endif; ?>
    location.href = url;
}

// 分组筛选已屏蔽
/*
function filterByGroup(groupId) {
    let url = groupId ? '?group_id=' + groupId : '?';
    <?php if ($search): ?>url += '&search=<?= $search ?>';<?php endif; ?>
    <?php if ($typeFilter): ?>url += '&type=<?= $typeFilter ?>';<?php endif; ?>
    location.href = url;
}
*/

function filterByType(type) {
    let url = type ? '?type=' + type : '?';
    <?php if ($search): ?>url += '&search=<?= $search ?>';<?php endif; ?>
    <?php if ($groupId): ?>url += '&group_id=<?= $groupId ?>';<?php endif; ?>
    location.href = url;
}

function showPreview(imageUrl, title) {
    document.getElementById('previewImage').src = imageUrl;
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewModal').classList.add('show');
}

function closePreview() {
    document.getElementById('previewModal').classList.remove('show');
}

const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');

uploadArea.addEventListener('click', () => fileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) uploadFiles(files);
});

fileInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    if (files.length > 0) uploadFiles(files);
});

function uploadFiles(files) {
    let uploadedCount = 0;
    let failedCount = 0;
    const totalFiles = files.length;
    
    document.getElementById('uploadProgress').style.display = 'block';
    const progressBar = document.getElementById('progressBar');
    const uploadStatus = document.getElementById('uploadStatus');
    
    uploadStatus.textContent = '准备上传 ' + totalFiles + ' 个文件...';
    
    // 逐个上传文件
    files.forEach((file, index) => {
        const formData = new FormData();
        formData.append('file', file);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const filePercent = Math.round((e.loaded / e.total) * 100);
                const totalPercent = Math.round(((index + (e.loaded / e.total)) / totalFiles) * 100);
                progressBar.style.width = totalPercent + '%';
                uploadStatus.textContent = '上传中 ' + (index + 1) + '/' + totalFiles + ' - ' + file.name + ' (' + filePercent + '%)';
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.code === 200) {
                        uploadedCount++;
                        console.log('✅ 上传成功:', file.name);
                    } else {
                        failedCount++;
                        console.error('❌ 上传失败:', file.name, result.message);
                    }
                } catch (e) {
                    failedCount++;
                    console.error('❌ JSON 解析错误:', file.name, e);
                }
            } else {
                failedCount++;
                console.error('❌ HTTP 错误:', file.name, xhr.status);
            }
            
            // 检查是否所有文件都上传完成
            if (uploadedCount + failedCount === totalFiles) {
                if (failedCount === 0) {
                    uploadStatus.textContent = '✅ 全部上传成功！';
                    progressBar.style.background = '#52c41a';
                    setTimeout(() => {
                        closeUploadModal();
                        // 强制刷新，添加时间戳参数避免缓存
                        window.location.href = window.location.pathname + '?_t=' + Date.now();
                    }, 1500);
                } else {
                    uploadStatus.textContent = '✅ 上传完成：' + uploadedCount + ' 成功，' + failedCount + ' 失败';
                    progressBar.style.background = uploadedCount > 0 ? '#52c41a' : '#ff4d4f';
                    setTimeout(() => {
                        if (uploadedCount > 0) {
                            closeUploadModal();
                            // 强制刷新，添加时间戳参数避免缓存
                            window.location.href = window.location.pathname + '?_t=' + Date.now();
                        }
                    }, 3000);
                }
            }
        });
        
        xhr.addEventListener('error', (e) => {
            failedCount++;
            console.error('❌ 网络错误:', file.name, e);
            
            if (uploadedCount + failedCount === totalFiles) {
                uploadStatus.textContent = '❌ 上传失败：网络错误';
                progressBar.style.background = '#ff4d4f';
            }
        });
        
        console.log('开始上传文件:', file.name, '大小:', file.size);
        xhr.open('POST', DX_STORAGE.api.upload);
        xhr.send(formData);
    });
}

function copyFileUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        const toast = document.createElement('div');
        toast.textContent = '✅ 链接已复制';
        toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#52c41a;color:white;padding:12px 24px;border-radius:8px;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }).catch(err => {
        alert('复制失败：' + err);
    });
}

function deleteFile(id, filename) {
    if (!confirm('确定要删除文件 "' + filename + '" 吗？\n此操作不可恢复！')) return;
    
    // 单个删除也使用 ids 数组格式
    fetch(DX_STORAGE.api.delete, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ids: [id] })
    })
    .then(response => response.json())
    .then(result => {
        if (result.code === 200) {
            alert('删除成功');
            location.reload();
        } else {
            alert('删除失败：' + (result.message || '未知错误'));
        }
    })
    .catch(error => alert('删除失败：' + error.message));
}

// 批量操作
let selectedIds = [];

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateBatchActions();
}

function updateBatchActions() {
    const checkboxes = document.querySelectorAll('.file-checkbox:checked');
    selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    const batchActions = document.getElementById('batchActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedIds.length > 0) {
        batchActions.style.display = 'flex';
        selectedCount.textContent = selectedIds.length;
    } else {
        batchActions.style.display = 'none';
        selectedCount.textContent = '0';
    }
}

function clearSelection() {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
    selectedIds = [];
    document.getElementById('selectAll').checked = false;
    document.getElementById('batchActions').style.display = 'none';
}

function batchDelete() {
    if (selectedIds.length === 0) {
        alert('请先选择要删除的文件');
        return;
    }
    
    if (!confirm(`确定要删除选中的 ${selectedIds.length} 个文件吗？\n此操作不可恢复！`)) {
        return;
    }
    
    fetch(DX_STORAGE.api.delete, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ids: selectedIds })
    })
    .then(response => response.json())
    .then(result => {
        if (result.code === 200) {
            const count = result.data?.deleted_count || selectedIds.length;
            alert(`✅ 成功删除 ${count} 个文件`);
            location.reload();
        } else {
            alert('删除失败：' + (result.message || '未知错误'));
        }
    })
    .catch(error => alert('删除失败：' + error.message));
}

// 批量复制链接
function batchCopy() {
    if (selectedIds.length === 0) {
        alert('请先选择文件');
        return;
    }
    
    // 获取选中文件的 URL
    const urls = [];
    document.querySelectorAll('.file-checkbox:checked').forEach(cb => {
        const card = cb.closest('.file-card');
        const img = card.querySelector('img');
        if (img && img.src) {
            urls.push(img.src);
        }
    });
    
    const text = urls.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.textContent = `✅ 已复制 ${urls.length} 个链接`;
        toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#52c41a;color:white;padding:12px 24px;border-radius:8px;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }).catch(err => {
        alert('复制失败：' + err);
    });
}

// 批量下载
function batchDownload() {
    if (selectedIds.length === 0) {
        alert('请先选择文件');
        return;
    }
    
    if (!confirm(`确定要下载选中的 ${selectedIds.length} 个文件吗？`)) {
        return;
    }
    
    // 获取选中文件的 URL
    const urls = [];
    document.querySelectorAll('.file-checkbox:checked').forEach(cb => {
        const card = cb.closest('.file-card');
        const img = card.querySelector('img');
        if (img && img.src) {
            urls.push(img.src);
        }
    });
    
    // 使用新窗口打开下载
    urls.forEach((url, index) => {
        setTimeout(() => {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            a.target = '_blank';
            a.click();
        }, index * 300); // 间隔 300ms，避免浏览器拦截
    });
    
    alert(`✅ 开始下载 ${urls.length} 个文件，请查看浏览器下载进度`);
}

// 显示移动模态框
function showMoveModal() {
    if (selectedIds.length === 0) {
        alert('请先选择文件');
        return;
    }
    document.getElementById('moveModal').classList.add('show');
}

function closeMoveModal() {
    document.getElementById('moveModal').classList.remove('show');
    document.getElementById('moveGroupSelect').value = '';
}

// 批量移动分组
function batchMove() {
    if (selectedIds.length === 0) {
        alert('请先选择文件');
        return;
    }
    
    const groupId = document.getElementById('moveGroupSelect').value;
    const groupName = document.getElementById('moveGroupSelect').options[document.getElementById('moveGroupSelect').selectedIndex].text;
    
    if (!groupId) {
        if (!confirm('确定要将文件移动到根目录吗？')) {
            return;
        }
    }
    
    fetch(DX_STORAGE.api.move_file, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            ids: selectedIds,
            group_id: groupId ? parseInt(groupId) : null
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.code === 200) {
            const count = result.data?.moved_count || selectedIds.length;
            alert(`✅ 成功移动 ${count} 个文件到 ${groupName}`);
            closeMoveModal();
            location.reload();
        } else {
            alert('移动失败：' + (result.message || '未知错误'));
        }
    })
    .catch(error => alert('移动失败：' + error.message));
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

document.getElementById('uploadModal').addEventListener('click', (e) => {
    if (e.target.id === 'uploadModal') closeUploadModal();
});

document.getElementById('previewModal').addEventListener('click', (e) => {
    if (e.target.id === 'previewModal') closePreview();
});

document.getElementById('moveModal').addEventListener('click', (e) => {
    if (e.target.id === 'moveModal') closeMoveModal();
});

// 页面加载时自动加载侧边栏分组
document.addEventListener('DOMContentLoaded', function() {
    console.log('页面加载完成，加载侧边栏分组...');
    loadSidebarGroups();
});
</script>

<?php
function formatSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}

require_once __DIR__ . '/includes/footer.php';
