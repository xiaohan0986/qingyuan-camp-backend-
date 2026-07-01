<?php
$currentPage = 'article';
$pageTitle = '文章管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('article');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/layout.min.css?v=<?= time() ?>">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
<style>
.filter-bar {
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}
.filter-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
.filter-item { flex: 1; min-width: 180px; }
.filter-item label { display: block; margin-bottom: 10px; color: #595959; font-size: 14px; font-weight: 600; }
.filter-item input, .filter-item select {
    width: 100%; padding: 11px 14px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; background: white; transition: all 0.3s;
}
.filter-item input:focus, .filter-item select:focus { outline: none; border-color: #1890ff; box-shadow: 0 0 0 4px rgba(24,144,255,0.1); }
.btn { padding: 11px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
.btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
.btn-success { background: linear-gradient(135deg, #52c41a, #73d13d); color: white; }
.btn-danger { background: linear-gradient(135deg, #f5222d, #ff4d4f); color: white; }
.btn-warning { background: linear-gradient(135deg, #faad14, #ffc53d); color: white; }
.btn-sm { padding: 8px 16px; font-size: 13px; }
.batch-action-bar {
    background: linear-gradient(135deg, #fff1f0, #ffe7e7);
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 2px solid #ffccc7;
    box-shadow: 0 4px 12px rgba(245,34,45,0.1);
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.batch-action-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
.batch-info {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #595959;
}
.batch-info input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.batch-actions {
    display: flex;
    gap: 10px;
}
.data-table-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #262626; border-bottom: 2px solid #f0f0f0; background: linear-gradient(135deg, #fafafa, #f5f5f5); }
.data-table td { padding: 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #595959; }
.data-table tbody tr:hover { background: #f0f5ff; }
.status-tag { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.status-1 { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
.status-0 { background: #f5f5f5; color: #999; border: 1px solid #d9d9d9; }
.action-btns { display: flex; gap: 6px; }
.action-btn { padding: 6px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
.action-btn-edit { background: #e6f7ff; color: #0050b3; }
.action-btn-edit:hover { background: #bae7ff; }
.action-btn-delete { background: #fff2f0; color: #cf1322; }
.action-btn-delete:hover { background: #ffccc7; }
.pagination-info { text-align: center; padding: 20px; color: #8c8c8c; font-size: 14px; }
.pagination-info strong { color: #1890ff; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 8px; padding: 20px; }
.pagination-btn { padding: 8px 16px; border: 1px solid #d9d9d9; border-radius: 6px; background: white; cursor: pointer; font-size: 14px; color: #262626; }
.pagination-btn:hover { border-color: #1890ff; color: #1890ff; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pagination-btn.active { background: #1890ff; color: white; border-color: #1890ff; }
.pagination-info-divider { color: #e8e8e8; margin: 0 8px; }
.page-size-selector { display: flex; align-items: center; gap: 6px; }
.toggle-switch {
    position: relative; width: 40px; height: 20px; background: #d9d9d9;
    border-radius: 10px; cursor: pointer; transition: all 0.3s; display: inline-block;
}
.toggle-switch.on { background: linear-gradient(135deg, #1890ff, #40a9ff); }
.toggle-switch::after {
    content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px;
    background: white; border-radius: 50%; transition: all 0.3s;
}
.toggle-switch.on::after { transform: translateX(20px); }
.empty-state { text-align: center; padding: 80px 20px; color: #999; }
.empty-state .icon { font-size: 64px; margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; color: #595959; margin: 0 0 8px; }
.modal-overlay {
    display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;
}
.modal-overlay.show { display: flex; }
.modal { background: white; border-radius: 16px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 16px 48px rgba(0,0,0,0.2); }
.modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid #f0f0f0;
}
.modal-title { margin: 0; font-size: 18px; color: #262626; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #8c8c8c; padding: 0 4px; }
.modal-body { padding: 24px; }
.category-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 12px; }
.modal-footer {
    display: flex; justify-content: flex-end; gap: 12px;
    padding: 16px 24px; border-top: 1px solid #f0f0f0;
}

/* 文章抽屉 */
.article-drawer {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2000; display: none;
}
.article-drawer.show { display: block; }
.drawer-mask { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); }
.drawer-content {
    position: absolute; top: 0; right: 0; width: 640px; max-width: 90vw; height: 100%;
    background: white; box-shadow: -8px 0 32px rgba(0,0,0,0.15); overflow-y: auto;
}
.drawer-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid #f0f0f0;
}
.drawer-header h3 { margin: 0; font-size: 18px; color: #262626; }
.drawer-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #8c8c8c; }
.drawer-body { padding: 24px; }
.drawer-footer {
    display: flex; justify-content: flex-end; gap: 12px;
    padding: 16px 24px; border-top: 1px solid #f0f0f0;
}
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; color: #595959; font-size: 14px; font-weight: 600; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 10px 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none; border-color: #1890ff; box-shadow: 0 0 0 3px rgba(24,144,255,0.1);
}
.required { color: #f5222d; }
</style>

<!-- 批量操作栏 -->
<div class="batch-action-bar" id="batchActionBar" style="display: none;">
    <div class="batch-action-content">
        <div class="batch-info">
            <input type="checkbox" id="batchSelectAll" onchange="toggleSelectAll()">
            <span>已选择 <strong id="selectedCount">0</strong> 篇文章</span>
        </div>
        <div class="batch-actions">
            <button class="btn btn-success" onclick="batchSetStatus(1)">✅ 批量发布</button>
            <button class="btn btn-warning" onclick="batchSetStatus(0)">⏸️ 批量草稿</button>
            <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
            <button class="btn btn-sm" style="background: #f0f0f0;" onclick="clearSelection()">❌ 取消选择</button>
        </div>
    </div>
</div>

<!-- 筛选栏 -->
<div class="filter-bar">
    <div class="filter-row">
        <div class="filter-item" style="flex: 0 0 calc(33.333% - 11px);">
            <label>关键词</label>
            <input type="text" id="keyword" placeholder="搜索文章标题、摘要、内容">
        </div>
        <div class="filter-item" style="flex: 0 0 calc(33.333% - 11px);">
            <label>作者</label>
            <select id="authorFilter">
                <option value="">全部</option>
            </select>
        </div>
        <div class="filter-item" style="flex: 0 0 calc(33.333% - 11px);">
            <label>状态</label>
            <select id="status">
                <option value="">全部</option>
                <option value="1">已发布</option>
                <option value="0">草稿</option>
            </select>
        </div>
        <div class="filter-item" style="flex: 0 0 calc(33.333% - 11px);">
            <label>分类</label>
            <select id="category">
                <option value="">全部</option>
            </select>
        </div>
    </div>
    <div style="display: flex; gap: 12px; margin-top: 16px;">
        <button class="btn btn-primary" onclick="loadArticles()">🔍 搜索</button>
        <button class="btn btn-success" onclick="openArticleDrawer()">新增文章</button>
        <button class="btn btn-warning" onclick="showCategoryModal()">📁 分类管理</button>
        <button class="btn" style="background: linear-gradient(135deg, #f59e0b, #f97316); color: white;" onclick="window.location.href='ai_article.php'">🤖 AI 生文</button>
    </div>
</div>

<!-- 分类管理弹窗 -->
<div class="modal-overlay" id="category-modal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h2 class="modal-title">📁 分类管理</h2>
            <button class="modal-close" onclick="closeCategoryModal()">×</button>
        </div>
        <div class="modal-body" style="max-height: calc(90vh - 200px);">
            <!-- 新增分类表单 -->
            <div style="display: flex; gap: 12px; margin-bottom: 24px; padding: 20px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; flex-wrap: wrap;">
                <input type="text" id="new-category-name" placeholder="输入分类名称" style="flex: 1; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; min-width: 150px;">
                <input type="text" id="new-category-color" placeholder="颜色（如 #1890ff）" value="#1890ff" style="width: 130px; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px;">
                <button class="btn btn-primary" onclick="addCategory()">➕ 添加分类</button>
            </div>
            <div id="category-list">
                <!-- 动态加载 -->
            </div>
        </div>
    </div>
</div>

<!-- 文章列表 -->
<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th width="50"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width:18px;height:18px;cursor:pointer;"></th>
                <th width="60">ID</th>
                <th width="200">标题</th>
                <th width="100">分类</th>
                <th width="100">作者</th>
                <th width="80">浏览量</th>
                <th width="80">状态</th>
                <th width="80">推荐</th>
                <th width="140">发布时间</th>
                <th width="120">操作</th>
            </tr>
        </thead>
        <tbody id="articleList">
            <tr><td colspan="10" style="text-align:center;padding:60px;color:#999;">加载中...</td></tr>
        </tbody>
    </table>
    <div id="pagination" class="pagination"></div>
</div>

<!-- 新建文章抽屉 -->
<div class="article-drawer" id="articleDrawer">
    <div class="drawer-mask" onclick="closeArticleDrawer()"></div>
    <div class="drawer-content">
        <div class="drawer-header">
            <h3>新建文章</h3>
            <button class="drawer-close" onclick="closeArticleDrawer()">×</button>
        </div>
        <div class="drawer-body">
            <div class="form-group">
                <label>文章标题 <span class="required">*</span></label>
                <input type="text" id="articleTitle" placeholder="请输入文章标题">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>作者</label>
                    <input type="text" id="articleAuthor" placeholder="请输入作者">
                </div>
                <div class="form-group">
                    <label>文章分类 <span class="required">*</span></label>
                    <select id="articleCategory">
                        <option value="">请选择分类</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>文章简介</label>
                <textarea id="articleSummary" placeholder="请输入摘要" maxlength="500" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>文章内容 <span class="required">*</span></label>
                <textarea id="articleContent" class="article-content-editor" placeholder="请输入文章内容..." rows="8"></textarea>
            </div>
        </div>
        <div class="drawer-footer">
            <button type="button" class="btn btn-default" onclick="closeArticleDrawer()">取消</button>
            <button type="button" class="btn btn-warning" onclick="saveArticle('draft')">保存草稿</button>
            <button type="button" class="btn btn-success" onclick="saveArticle('publish')">发布文章</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let pageSize = 20;
let selectedIds = [];
let categoryList = [];

function loadArticles(page = 1) {
    const keyword = document.getElementById('keyword').value;
    const author = document.getElementById('authorFilter').value;
    const category = document.getElementById('category').value;
    const status = document.getElementById('status').value;
    
    let url = `../api/article.php?action=list&page=${page}&page_size=${pageSize}`;
    if (keyword) url += `&keyword=${encodeURIComponent(keyword)}`;
    if (author) url += `&author=${encodeURIComponent(author)}`;
    if (category) url += `&category=${encodeURIComponent(category)}`;
    if (status !== '') url += `&status=${status}`;
    
    document.getElementById('articleList').innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#999;">加载中...</td></tr>';
    
    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('articleList').innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#f5222d;">加载失败</td></tr>';
                return;
            }
            
            const { list, pagination } = res.data;
            currentPage = pagination.page;
            totalPages = pagination.total_pages;
            
            if (list.length === 0) {
                document.getElementById('articleList').innerHTML = '<tr><td colspan="10" style="text-align:center;padding:60px;color:#999;">暂无文章数据</td></tr>';
            } else {
                document.getElementById('articleList').innerHTML = list.map(item => `
                    <tr>
                        <td><input type="checkbox" class="select-item" data-id="${item.id}" onchange="updateSelectCount()" style="width:18px;height:18px;cursor:pointer;"></td>
                        <td><span style="font-weight:600;color:#bfbfbf;">#${item.id}</span></td>
                        <td style="max-width:200px;">
                            <div style="font-weight:600;color:#262626;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${item.title}">${item.title}</div>
                            ${item.summary ? `<div style="font-size:12px;color:#8c8c8c;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.summary.substring(0, 50)}...</div>` : ''}
                        </td>
                        <td><span style="background:#f0f5ff;color:#0050b3;padding:4px 10px;border-radius:6px;font-size:12px;">${item.category || '-'}</span></td>
                        <td>${item.author || '-'}</td>
                        <td style="text-align:center;font-weight:600;color:#1890ff;">${item.view_count || 0}</td>
                        <td><span class="status-tag status-${item.status}">${item.status == 1 ? '已发布' : '草稿'}</span></td>
                        <td>
                            <label class="toggle-switch ${item.is_recommend == 1 ? 'on' : ''}" onclick="toggleRecommend(${item.id}, this.classList.contains('on') ? 0 : 1)"><span class="toggle-slider"></span></label>
                        </td>
                        <td style="font-size:13px;color:#8c8c8c;">${item.created_at ? item.created_at.replace(' ', '<br>') : '-'}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn action-btn-edit" onclick="window.location.href='article_edit.php?id=${item.id}'">编辑</button>
                                <button class="action-btn action-btn-delete" onclick="deleteArticle(${item.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            renderPagination(pagination);
        })
        .catch(err => {
            document.getElementById('articleList').innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#f5222d;">网络错误</td></tr>';
        });
}

function renderPagination(pagination) {
    const page = pagination.page;
    const total = pagination.total_pages;
    document.getElementById('pagination').innerHTML = `
        <div class="pagination-info">
            <span>共 <strong>${pagination.total}</strong> 条</span>
            <span class="pagination-info-divider">|</span>
            <span>第 <strong>${page}</strong> / ${total} 页</span>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="pagination-btn" onclick="loadArticles(1)" ${page <= 1 ? 'disabled' : ''}>首页</button>
            <button class="pagination-btn" onclick="loadArticles(${page - 1})" ${page <= 1 ? 'disabled' : ''}>上一页</button>
            <button class="pagination-btn" onclick="loadArticles(${page + 1})" ${page >= total ? 'disabled' : ''}>下一页</button>
            <button class="pagination-btn" onclick="loadArticles(${total})" ${page >= total ? 'disabled' : ''}>末页</button>
        </div>
    `;
}

function loadCategories() {
    fetch('../api/article.php?action=categories')
        .then(res => res.json())
        .then(res => {
            let categories = [];
            if (res.success && Array.isArray(res.data)) {
                categories = res.data.map(cat => cat.name);
            } else if (res.code === 200 && Array.isArray(res.data)) {
                categories = res.data;
            }
            const html = '<option value="">全部</option>' + (categories.length > 0 ? categories.map(c => `<option value="${c}">${c}</option>`).join('') : '<option value="" disabled>暂无分类</option>');
            document.getElementById('category').innerHTML = html;
            document.getElementById('articleCategory').innerHTML = '<option value="">请选择分类</option>' + (categories.length > 0 ? categories.map(c => `<option value="${c}">${c}</option>`).join('') : '');
        })
        .catch(err => {
            document.getElementById('category').innerHTML = '<option value="">全部</option><option value="" disabled>加载失败</option>';
        });
}

function loadAuthors() {
    fetch('../api/article.php?action=authors')
        .then(res => res.json())
        .then(res => {
            let authors = [];
            if (res.code === 200 && Array.isArray(res.data)) authors = res.data;
            if (authors.length > 0) {
                document.getElementById('authorFilter').innerHTML = '<option value="">全部</option>' + authors.map(a => `<option value="${a}">${a}</option>`).join('');
            }
        })
        .catch(err => {});
}

function deleteArticle(id) {
    if (!confirm('确定要删除该文章吗？此操作不可恢复！')) return;
    fetch('../api/article.php?action=delete', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}` })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) { alert('✅ 删除成功'); loadArticles(currentPage); }
            else alert('❌ ' + (res.message || '删除失败'));
        });
}

function toggleRecommend(id, status) {
    fetch('../api/article.php?action=toggle_recommend', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}&is_recommend=${status}` })
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) alert('❌ 操作失败');
        });
}

function toggleSelectAll() {
    const checkAll = document.getElementById('selectAll').checked;
    document.querySelectorAll('.select-item').forEach(cb => cb.checked = checkAll);
    updateSelectCount();
}

function updateSelectCount() {
    const checked = document.querySelectorAll('.select-item:checked');
    selectedIds = Array.from(checked).map(cb => parseInt(cb.dataset.id));
    document.getElementById('selectedCount').textContent = selectedIds.length;
    document.getElementById('batchActionBar').style.display = selectedIds.length > 0 ? 'block' : 'none';
}

function clearSelection() {
    document.querySelectorAll('.select-item').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateSelectCount();
}

function batchSetStatus(status) {
    if (selectedIds.length === 0) return alert('请先选择文章');
    if (!confirm(`确定将选中的 ${selectedIds.length} 篇文章${status == 1 ? '发布' : '设为草稿'}吗？`)) return;
    fetch('../api/article.php?action=set_status', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `ids=${selectedIds.join(',')}&status=${status}` })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) { alert('✅ 操作成功'); loadArticles(currentPage); }
            else alert('❌ ' + (res.message || '操作失败'));
        });
}

function batchDelete() {
    if (selectedIds.length === 0) return alert('请先选择文章');
    if (!confirm(`⚠️ 确定要删除选中的 ${selectedIds.length} 篇文章吗？此操作不可恢复！`)) return;
    fetch('../api/article.php?action=delete', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `ids=${selectedIds.join(',')}` })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) { alert('✅ 删除成功'); loadArticles(currentPage); }
            else alert('❌ ' + (res.message || '删除失败'));
        });
}

function showCategoryModal() {
    document.getElementById('category-modal').classList.add('show');
    loadCategoryList();
}

function closeCategoryModal() {
    document.getElementById('category-modal').classList.remove('show');
}

function loadCategoryList() {
    fetch('../api/article.php?action=categories')
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                categoryList = result.data || [];
                renderCategoryList();
            }
        })
        .catch(err => {});
}

function renderCategoryList() {
    const container = document.getElementById('category-list');
    if (categoryList.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:#999;">暂无分类</div>';
        return;
    }
    container.innerHTML = categoryList.map(cat => `
        <div class="category-item">
            <div style="display:flex;align-items:center;gap:16px;flex:1;">
                <div style="width:40px;height:40px;border-radius:10px;background:${cat.color || '#1890ff'};display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;">${cat.name.charAt(0)}</div>
                <div>
                    <div style="font-size:16px;font-weight:600;color:#262626;">${cat.name}</div>
                    <div style="font-size:13px;color:#999;">${cat.article_count || 0} 篇文章</div>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="action-btn" onclick="editCategory(${cat.id})" style="padding:8px 16px;background:#e6f7ff;color:#0050b3;border:none;border-radius:8px;font-size:13px;cursor:pointer;font-weight:600;">编辑</button>
                <button class="action-btn" onclick="deleteCategory(${cat.id}, '${cat.name}', ${cat.article_count || 0})" style="padding:8px 16px;background:${cat.article_count > 0 ? '#f5f5f5' : '#fff2f0'};color:${cat.article_count > 0 ? '#999' : '#cf1322'};border:none;border-radius:8px;font-size:13px;cursor:${cat.article_count > 0 ? 'not-allowed' : 'pointer'};font-weight:600;" ${cat.article_count > 0 ? 'disabled' : ''}>删除</button>
            </div>
        </div>
    `).join('');
}

function addCategory() {
    const name = document.getElementById('new-category-name').value.trim();
    const color = document.getElementById('new-category-color').value.trim() || '#1890ff';
    if (!name) { alert('请输入分类名称'); return; }
    fetch('../api/article.php?action=add_category', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `name=${encodeURIComponent(name)}&color=${encodeURIComponent(color)}` })
        .then(res => res.json())
        .then(result => {
            if (result.success) { alert('✅ 添加成功'); loadCategoryList(); loadCategories(); document.getElementById('new-category-name').value = ''; }
            else alert('❌ ' + (result.message || '添加失败'));
        });
}

function editCategory(id) {
    const cat = categoryList.find(c => c.id === id);
    if (!cat) return;
    const name = prompt('修改分类名称:', cat.name);
    if (name && name.trim()) {
        const newColor = prompt('修改分类颜色:', cat.color || '#1890ff');
        fetch('../api/article.php?action=update_category', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}&name=${encodeURIComponent(name.trim())}&color=${encodeURIComponent(newColor || '#1890ff')}` })
            .then(res => res.json())
            .then(result => {
                if (result.success) { alert('✅ 修改成功'); loadCategoryList(); loadCategories(); }
                else alert('❌ ' + (result.message || '修改失败'));
            });
    }
}

function deleteCategory(id, name, count) {
    if (count > 0) { alert('⚠️ 该分类下有文章，无法删除'); return; }
    if (!confirm(`⚠️ 确定要删除分类"${name}"吗？此操作不可恢复！`)) return;
    fetch('../api/article.php?action=delete_category', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `id=${id}` })
        .then(res => res.json())
        .then(result => {
            if (result.success) { alert('✅ 删除成功'); loadCategoryList(); loadCategories(); }
            else alert('❌ ' + (result.message || '删除失败'));
        });
}

function openArticleDrawer() {
    document.getElementById('articleDrawer').classList.add('show');
}

function closeArticleDrawer() {
    document.getElementById('articleDrawer').classList.remove('show');
}

function saveArticle(mode) {
    const title = document.getElementById('articleTitle').value.trim();
    const content = document.getElementById('articleContent').value.trim();
    const categoryId = document.getElementById('articleCategory').value;
    if (!title) { alert('请输入文章标题'); return; }
    if (!content) { alert('请输入文章内容'); return; }
    if (!categoryId) { alert('请选择文章分类'); return; }
    
    const status = mode === 'publish' ? '1' : '0';
    const params = new URLSearchParams();
    params.append('title', title);
    params.append('category', categoryId);
    params.append('author', document.getElementById('articleAuthor').value.trim());
    params.append('summary', document.getElementById('articleSummary').value.trim());
    params.append('content', content);
    params.append('cover_image', '');
    params.append('status', status);
    
    fetch('../api/article.php?action=create', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) { alert(mode === 'publish' ? '✅ 发布成功' : '✅ 保存成功'); location.reload(); }
            else alert('❌ ' + (res.message || '操作失败'));
        })
        .catch(err => alert('❌ 网络错误：' + err.message));
}

// 页面加载时初始化
loadCategories();
loadAuthors();
loadArticles();
</script>
</div>
<?php require_once __DIR__ . '/includes/footer.php';
