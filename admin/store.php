<?php
$currentPage = 'store';
$pageTitle = '门店管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('store');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* 批量操作栏 */
.batch-action-bar {
    background: linear-gradient(135deg, #fff1f0, #ffe7e7);
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 2px solid #ffccc7;
    box-shadow: 0 4px 12px rgba(245,34,45,0.1);
    animation: slideDown 0.3s ease;
    display: none;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

/* 筛选栏 */
.filter-bar {
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.filter-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-item {
    flex: 1;
    min-width: 180px;
}

.filter-item label {
    display: block;
    margin-bottom: 10px;
    color: #595959;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-item label::before {
    content: '';
    width: 3px;
    height: 14px;
    background: linear-gradient(180deg, #1890ff, #40a9ff);
    border-radius: 2px;
}

.filter-item input,
.filter-item select {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.filter-item input:focus,
.filter-item select:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(24,144,255,0.1);
}

/* 按钮样式 */
.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff, #096dd9);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.3);
}

.btn-default {
    background: white;
    color: #595959;
    border: 2px solid #d9d9d9;
}

.btn-default:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.btn-success {
    background: linear-gradient(135deg, #52c41a, #389e0d);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff4d4f, #cf1322);
    color: white;
}

/* 数据表格 */
.data-table-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h2 {
    margin: 0;
    font-size: 18px;
    color: #262626;
    font-weight: 700;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: linear-gradient(135deg, #fafafa, #f5f5f5);
}

.data-table th {
    padding: 16px 20px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: #595959;
    border-bottom: 2px solid #f0f0f0;
}

.data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f5f5f5;
    font-size: 14px;
    color: #595959;
}

.data-table tbody tr:hover {
    background: #fafafa;
}

/* 状态标签 */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.success {
    background: #f6ffed;
    color: #237804;
    border: 1px solid #b7eb8f;
}

.status-badge.error {
    background: #fff2f0;
    color: #cf1322;
    border: 1px solid #ffccc7;
}

.status-badge.warning {
    background: #fffbe6;
    color: #ad6800;
    border: 1px solid #ffe58f;
}

/* 操作按钮组 */
.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    background: none;
}

.action-btn.edit {
    color: #1890ff;
    background: #e6f7ff;
}

.action-btn.edit:hover {
    background: #1890ff;
    color: white;
}

.action-btn.delete {
    color: #ff4d4f;
    background: #fff1f0;
}

.action-btn.delete:hover {
    background: #ff4d4f;
    color: white;
}

/* 分页 */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-top: 1px solid #f0f0f0;
    background: #fafafa;
}

.pagination-info {
    font-size: 14px;
    color: #595959;
}

.pagination-buttons {
    display: flex;
    gap: 8px;
}

.page-btn {
    padding: 8px 16px;
    border: 2px solid #d9d9d9;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.page-btn:hover:not(.disabled) {
    border-color: #1890ff;
    color: #1890ff;
}

.page-btn.active {
    background: #1890ff;
    color: white;
    border-color: #1890ff;
}

.page-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 弹窗 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 16px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #262626;
    font-weight: 700;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #8c8c8c;
    cursor: pointer;
    padding: 4px;
    line-height: 1;
}

.modal-close:hover {
    color: #595959;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fafafa;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(24,144,255,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
</style>

<!-- 筛选栏 -->
<div class="filter-bar">
    <div class="filter-row">
        <div class="filter-item">
            <label>🔍 关键词</label>
            <input type="text" id="keyword" placeholder="门店名称、地址">
        </div>
        <div class="filter-item">
            <label>🌍 国家</label>
            <input type="text" id="country" placeholder="国家">
        </div>
        <div class="filter-item">
            <label>🏙️ 城市</label>
            <input type="text" id="city" placeholder="城市">
        </div>
        <div class="filter-item" style="flex: 0 0 auto;">
            <button class="btn btn-primary" onclick="searchStores()">
                🔍 搜索
            </button>
            <button class="btn btn-default" onclick="resetFilter()" style="margin-left: 8px;">
                🔄 重置
            </button>
        </div>
    </div>
</div>

<!-- 批量操作栏 -->
<div class="batch-action-bar" id="batchActionBar">
    <div class="batch-action-content">
        <div class="batch-info">
            <input type="checkbox" id="batchSelectAll" onchange="toggleBatchSelect()">
            <span>已选择 <span id="batchCount">0</span> 项</span>
        </div>
        <div class="batch-actions">
            <button class="btn btn-danger" onclick="batchDelete()">
                批量删除
            </button>
        </div>
    </div>
</div>

<!-- 数据表格 -->
<div class="data-table-container">
    <div class="table-header">
        <h2>🏪 门店列表</h2>
        <button class="btn btn-primary" onclick="window.location.href='store_edit.php'">
            ➕ 新建门店
        </button>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th>头像</th>
                <th>ID</th>
                <th>门店名称</th>
                <th>国家</th>
                <th>城市</th>
                <th>地址</th>
                <th>在招岗位</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="storeList">
            <tr>
                <td colspan="10" style="text-align: center; padding: 60px; color: #8c8c8c;">加载中...</td>
            </tr>
        </tbody>
    </table>
    
    <div class="pagination">
        <div class="pagination-info">
            共 <span id="totalCount">0</span> 条记录
        </div>
        <div class="pagination-buttons">
            <button class="page-btn" onclick="changePage(1)" id="firstPage">首页</button>
            <button class="page-btn" onclick="changePage(currentPage - 1)" id="prevPage">上一页</button>
            <span class="page-btn active" id="currentPageBtn">1</span>
            <button class="page-btn" onclick="changePage(currentPage + 1)" id="nextPage">下一页</button>
        </div>
    </div>
</div>

<!-- 新建/编辑弹窗 -->
<div class="modal-overlay" id="modalOverlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">🏪 新建门店</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="storeForm">
                <input type="hidden" id="storeId">
                <div class="form-row">
                    <div class="form-group">
                        <label>门店名称 <span style="color: #ff4d4f;">*</span></label>
                        <input type="text" id="storeName" required placeholder="请输入门店名称">
                    </div>
                    <div class="form-group">
                        <label>联系电话</label>
                        <input type="text" id="storePhone" placeholder="请输入联系电话">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>国家 <span style="color: #ff4d4f;">*</span></label>
                        <input type="text" id="storeCountry" required placeholder="请输入国家">
                    </div>
                    <div class="form-group">
                        <label>城市 <span style="color: #ff4d4f;">*</span></label>
                        <input type="text" id="storeCity" required placeholder="请输入城市">
                    </div>
                </div>
                <div class="form-group">
                    <label>详细地址</label>
                    <textarea id="storeAddress" placeholder="请输入详细地址" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>门店简介</label>
                    <textarea id="storeDescription" placeholder="请输入门店简介" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>状态</label>
                        <select id="storeStatus">
                            <option value="1">营业中</option>
                            <option value="0">已关闭</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" id="storeSort" value="0" placeholder="数字越小越靠前">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default" onclick="closeModal()">取消</button>
            <button class="btn btn-primary" onclick="saveStore()">保存</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let selectedIds = [];

// 加载门店列表
function loadStores(page = 1) {
    currentPage = page;
    
    const params = new URLSearchParams({
        page: page,
        page_size: 20,
        keyword: document.getElementById('keyword').value,
        country: document.getElementById('country').value,
        city: document.getElementById('city').value
    });
    
    fetch('../api/store.php?action=list&' + params)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('storeList').innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: #ff4d4f;">❌ 加载失败</td></tr>';
                return;
            }
            
            renderStoreList(res.data.list, res.data.pagination);
        })
        .catch(err => {
            console.error('加载失败:', err);
            document.getElementById('storeList').innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: #ff4d4f;">❌ 网络错误</td></tr>';
        });
}

// 渲染门店列表
function renderStoreList(list, pagination) {
    const tbody = document.getElementById('storeList');
    
    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 60px; color: #8c8c8c;">暂无数据</td></tr>';
    } else {
        tbody.innerHTML = list.map(store => `
            <tr>
                <td><input type="checkbox" class="store-checkbox" value="${store.id}" onchange="updateBatchActionBar()"></td>
                <td>
                    <div style="width:50px;height:50px;border-radius:8px;overflow:hidden;background:#fafafa;border:2px solid #f0f0f0;">
                        ${store.avatar ? 
                            `<img src="${store.avatar}" alt="${store.name}" style="width:100%;height:100%;object-fit:cover;">` : 
                            '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#d9d9d9;">🏪</div>'
                        }
                    </div>
                </td>
                <td>${store.id}</td>
                <td><strong>${store.name || '-'}</strong></td>
                <td>${store.country || '-'}</td>
                <td>${store.city || '-'}</td>
                <td>${store.address || '-'}</td>
                <td>${store.position_count || 0}</td>
                <td>
                    <span class="status-badge ${store.status == 1 ? 'success' : 'error'}">
                        ${store.status == 1 ? '营业中' : '已关闭'}
                    </span>
                </td>
                <td>${store.created_at || '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn edit" onclick="window.location.href='store_edit.php?id=${store.id}'">编辑</button>
                        <button class="action-btn delete" onclick="deleteStore(${store.id})">删除</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    document.getElementById('totalCount').textContent = pagination.total;
    document.getElementById('currentPageBtn').textContent = pagination.page;
    
    // 更新分页按钮状态
    document.getElementById('firstPage').disabled = pagination.page === 1;
    document.getElementById('prevPage').disabled = pagination.page === 1;
    document.getElementById('nextPage').disabled = pagination.page >= pagination.total_pages;
}

// 搜索
function searchStores() {
    loadStores(1);
}

// 重置筛选
function resetFilter() {
    document.getElementById('keyword').value = '';
    document.getElementById('country').value = '';
    document.getElementById('city').value = '';
    loadStores(1);
}

// 切换全选
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.store-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBatchActionBar();
}

// 切换批量操作栏
function updateBatchActionBar() {
    const checkboxes = document.querySelectorAll('.store-checkbox:checked');
    selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    const batchActionBar = document.getElementById('batchActionBar');
    if (selectedIds.length > 0) {
        batchActionBar.style.display = 'block';
        document.getElementById('batchCount').textContent = selectedIds.length;
    } else {
        batchActionBar.style.display = 'none';
    }
}

// 批量删除
function batchDelete() {
    if (selectedIds.length === 0) {
        alert('请选择要删除的门店');
        return;
    }
    
    if (!confirm(`确定要删除选中的 ${selectedIds.length} 个门店吗？`)) {
        return;
    }
    
    fetch('../api/store.php?action=batch_delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ids: selectedIds })
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert('✅ 删除成功');
            loadStores(currentPage);
        } else {
            alert('❌ ' + res.message);
        }
    })
    .catch(err => {
        console.error('删除失败:', err);
        alert('❌ 网络错误');
    });
}

// 删除门店
function deleteStore(id) {
    if (!confirm('确定要删除这个门店吗？')) {
        return;
    }
    
    fetch('../api/store.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert('✅ 删除成功');
            loadStores(currentPage);
        } else {
            alert('❌ ' + res.message);
        }
    })
    .catch(err => {
        console.error('删除失败:', err);
        alert('❌ 网络错误');
    });
}

// 切换分页
function changePage(page) {
    if (page < 1) return;
    loadStores(page);
}

// 初始化加载
loadStores(1);
</script>

<?php require_once __DIR__ . '/includes/footer.php';
