<?php
$currentPage = 'user';
$pageTitle = '用户管理';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('user');

require_once __DIR__ . '/includes/header.php';
?>

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
.btn-primary { background: linear-gradient(135deg, #1890ff, #096dd9); color: white; }
.btn-danger { background: linear-gradient(135deg, #f5222d, #ff4d4f); color: white; }
.btn-warning { background: linear-gradient(135deg, #faad14, #ffc53d); color: white; }
.btn-sm { padding: 8px 16px; font-size: 13px; }
.btn-search { 
    padding: 11px 20px; 
    background: linear-gradient(135deg, #1890ff, #096dd9); 
    color: white; 
    border: none; 
    border-radius: 10px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.3s;
    white-space: nowrap;
}
.btn-search:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }

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
.batch-action-bar.show { display: block; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.batch-action-content { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.batch-info { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #595959; }
.batch-info input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
.batch-actions { display: flex; gap: 10px; }

.data-table-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { padding: 16px; text-align: left; font-size: 14px; font-weight: 600; color: #262626; border-bottom: 2px solid #f0f0f0; background: linear-gradient(135deg, #fafafa, #f5f5f5); }
.data-table td { padding: 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #595959; }
.data-table tbody tr:hover { background: #e6f7ff; }
.status-tag { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.status-0 { background: #fff2f0; color: #cf1322; }
.status-1 { background: #f6ffed; color: #237804; }
.online-status { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.online-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.online-dot-online { background: #52c41a; box-shadow: 0 0 8px rgba(82,196,26,0.6); }
.online-dot-offline { background: #f5222d; box-shadow: 0 0 8px rgba(245,34,45,0.6); }
.online-text { font-size: 12px; font-weight: 600; }
.online-days { font-size: 11px; color: #8c8c8c; }
.role-tag { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.role-1 { background: #e6f7ff; color: #0050b3; }
.role-2 { background: #f9f0ff; color: #531dab; }
.role-3 { background: #fff7e6; color: #fa8c16; }
.gender-tag { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
.gender-male { background: #e6f7ff; color: #1890ff; }
.gender-female { background: #fff0f6; color: #eb2f96; }
.gender-unknown { background: #f5f5f5; color: #8c8c8c; }
.avatar-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 2px solid #f0f0f0; }
.action-btns { display: flex; gap: 6px; align-items: center; }
.action-btn { padding: 5px 12px; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; }
.action-btn-view { background: #e6f7ff; color: #0050b3; }
.action-btn-delete { background: #fff2f0; color: #cf1322; }
.action-btn:hover { transform: translateY(-1px); opacity: 0.9; }

/* 开关拨动按钮 */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 22px;
    margin-right: 6px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 22px;
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.toggle-switch input:checked + .toggle-slider {
    background-color: #52c41a;
}
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(22px);
}
.toggle-switch:hover .toggle-slider {
    box-shadow: 0 0 0 3px rgba(82,196,26,0.2);
}
.pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    padding: 20px 32px;
    background: linear-gradient(135deg, #fafafa, white);
    border-radius: 16px;
    margin-top: 24px;
    border: 2px solid #f0f0f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.pagination-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #595959;
    font-size: 14px;
}
.pagination-info strong { color: #1890ff; font-weight: 700; }

.btn-secondary { background: #f0f0f0; color: #595959; }
.btn-secondary:hover { background: #d9d9d9; }
</style>

<!-- 批量操作栏 -->
<div class="batch-action-bar" id="batchActionBar">
    <div class="batch-action-content">
        <div class="batch-info">
            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
            <label for="selectAll" style="margin:0;cursor:pointer;">已选择 <strong id="selectedCount" style="color:#1890ff;font-weight:700;">0</strong> 个用户</label>
        </div>
        <div class="batch-actions">
            <button class="btn btn-warning btn-sm" onclick="batchEnable()">✅ 批量启用</button>
            <button class="btn btn-danger btn-sm" onclick="batchDisable()">🚫 批量禁用</button>
            <button class="btn btn-danger btn-sm" onclick="batchDelete()">批量删除</button>
            <button class="btn btn-secondary btn-sm" onclick="hideBatchBar()">取消</button>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-row">
        <div class="filter-item" style="flex: 2; min-width: 250px;">
            <label>搜索</label>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <input type="text" id="searchInput" placeholder="用户名/昵称/手机号/微信号" onkeyup="if(event.keyCode===13) searchUsers()" style="flex:1;">
                <button class="btn-search" onclick="searchUsers()">🔍 搜索</button>
            </div>
        </div>
        <div class="filter-item">
            <label>状态</label>
            <select id="statusFilter" onchange="loadUsers()">
                <option value="">全部</option>
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </div>
        <div class="filter-item">
            <label>性别</label>
            <select id="genderFilter" onchange="loadUsers()">
                <option value="">全部</option>
                <option value="1">男</option>
                <option value="2">女</option>
                <option value="0">未知</option>
            </select>
        </div>
        <div class="filter-item" style="flex: 0;">
            <label>&nbsp;</label>
            <button class="btn btn-primary" onclick="window.location.href='user_edit.php'">➕ 新增用户</button>
        </div>
    </div>
</div>

<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;"><input type="checkbox" id="headerCheckbox" onchange="toggleHeaderCheckbox()"></th>
                <th>ID</th>
                <th>头像</th>
                <th>会员 ID</th>
                <th>昵称</th>
                <th>微信号</th>
                <th>手机号</th>
                <th>性别</th>
                <th>状态</th>
                <th>在线状态</th>
                <th>最后登录</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="userList">
            <tr><td colspan="13" style="text-align:center;padding:60px;color:#8c8c8c;">加载中...</td></tr>
        </tbody>
    </table>
</div>

<div class="pagination" id="pagination"></div>


</div>

<script>
let currentPage = 1;
let pageSize = 20;
let selectedUsers = new Set();

function loadUsers() {
    const status = document.getElementById('statusFilter').value;
    const gender = document.getElementById('genderFilter').value;
    const keyword = document.getElementById('searchInput').value;
    
    let url = '../api/user.php?action=list&page=' + currentPage + '&page_size=' + pageSize;
    if (status) url += '&status=' + status;
    if (gender !== '') url += '&gender=' + gender;
    if (keyword) url += '&keyword=' + encodeURIComponent(keyword);
    
    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('userList').innerHTML = '<tr><td colspan="13" style="text-align:center;padding:40px;color:#f5222d;">❌ ' + res.message + '</td></tr>';
                return;
            }
            
            const list = res.data.list;
            if (list.length === 0) {
                document.getElementById('userList').innerHTML = '<tr><td colspan="13" style="text-align:center;padding:40px;color:#8c8c8c;">暂无数据</td></tr>';
            } else {
                document.getElementById('userList').innerHTML = list.map(u => {
                    const onlineInfo = getOnlineStatus(u.last_login_at);
                    // 处理头像 URL：如果是相对路径，添加完整域名
                    let avatarUrl = u.avatar || '';
                    if (avatarUrl && avatarUrl.startsWith('/')) {
                        // 相对路径转完整 URL：添加 /www.gofong.com 前缀
                        avatarUrl = window.location.protocol + '//' + window.location.host + '/www.gofong.com' + avatarUrl;
                    }
                    console.log('用户头像 URL:', avatarUrl, '原始值:', u.avatar);
                    const avatarHtml = avatarUrl ? `<img src="${escapeHtml(avatarUrl)}" class="avatar-img" alt="头像" onerror="console.log('❌ 头像加载失败:', this.src); this.style.display='none';">` : '<div style="width:40px;height:40px;border-radius:8px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#8c8c8c;font-size:18px;">👤</div>';
                    const genderHtml = getGenderTag(u.gender);
                    
                    return `
                    <tr>
                        <td><input type="checkbox" class="user-checkbox" value="${u.id}" onchange="updateSelectedCount()"></td>
                        <td>${u.id}</td>
                        <td>${avatarHtml}</td>
                        <td><strong style="color: #1890ff; font-size: 15px;">${escapeHtml(u.member_id) || '-'}</strong></td>
                        <td>${escapeHtml(u.nickname) || '-'}</td>
                        <td>${escapeHtml(u.wechat_id) || '-'}</td>
                        <td>${escapeHtml(u.phone) || '-'}</td>
                        <td>${genderHtml}</td>
                        <td><span class="status-tag status-${u.status == 1 ? '1' : '0'}">${u.status == 1 ? '启用' : '禁用'}</span></td>
                        <td>
                            <div class="online-status">
                                <span class="online-dot ${onlineInfo.online ? 'online-dot-online' : 'online-dot-offline'}"></span>
                                <span class="online-text">${onlineInfo.online ? '在线' : '离线'}</span>
                                <span class="online-days">${onlineInfo.days}</span>
                            </div>
                        </td>
                        <td>${u.last_login_at ? formatLastLogin(u.last_login_at) : '-'}</td>
                        <td>${u.created_at || '-'}</td>
                        <td>
                            <div class="action-btns">
                                <label class="toggle-switch" title="${u.status == 1 ? '点击禁用' : '点击启用'}">
                                    <input type="checkbox" ${u.status == 1 ? 'checked' : ''} onchange="toggleUserStatus(${u.id}, ${u.status == 1 ? 0 : 1})">
                                    <span class="toggle-slider"></span>
                                </label>
                                <button class="action-btn action-btn-view" onclick="window.location.href='user_edit.php?id=${u.id}'">编辑</button>
                                <button class="action-btn action-btn-delete" onclick="deleteUser(${u.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                    `;
                }).join('');
            }
            
            updatePagination(res.data.pagination);
            updateHeaderCheckbox();
        })
        .catch(err => {
            console.error(err);
            document.getElementById('userList').innerHTML = '<tr><td colspan="13" style="text-align:center;padding:40px;color:#f5222d;">加载失败，请刷新重试</td></tr>';
        });
}

function getOnlineStatus(lastLoginAt) {
    if (!lastLoginAt) {
        return { online: false, days: '从未登录' };
    }
    
    const lastLogin = new Date(lastLoginAt.replace(' ', 'T')); // 修复日期解析
    const now = new Date();
    const diffMs = now - lastLogin;
    const diffHours = diffMs / (1000 * 60 * 60);
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    // 2 小时内算在线
    if (diffHours < 2) {
        return { online: true, days: '在线' };
    } else {
        if (diffDays === 0) {
            return { online: false, days: '今天' };
        } else if (diffDays === 1) {
            return { online: false, days: '昨天' };
        } else if (diffDays < 7) {
            return { online: false, days: diffDays + '天前' };
        } else {
            return { online: false, days: '超过 7 天' };
        }
    }
}

function formatLastLogin(lastLoginAt) {
    if (!lastLoginAt) return '-';
    const date = new Date(lastLoginAt);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return '今天 ' + date.toLocaleTimeString('zh-CN', {hour: '2-digit', minute:'2-digit'});
    } else if (diffDays === 1) {
        return '昨天 ' + date.toLocaleTimeString('zh-CN', {hour: '2-digit', minute:'2-digit'});
    } else {
        return date.toLocaleDateString('zh-CN', {month: '2-digit', day:'2-digit'}) + ' ' + date.toLocaleTimeString('zh-CN', {hour: '2-digit', minute:'2-digit'});
    }
}

function getGenderTag(gender) {
    const map = {
        '1': '<span class="gender-tag gender-male">♂ 男</span>',
        '2': '<span class="gender-tag gender-female">♀ 女</span>',
        '0': '<span class="gender-tag gender-unknown">未知</span>'
    };
    return map[gender] || map['0'];
}

function updatePagination(pagination) {
    document.getElementById('pagination').innerHTML = `
        <div class="pagination-info">
            <span>共 <strong>${pagination.total}</strong> 条</span>
            <div style="display:flex;align-items:center;gap:8px;margin-left:16px;">
                <span style="color:#595959;font-size:13px;">每页显示</span>
                <select onchange="changePageSize(this.value)" style="padding:6px 12px;border:2px solid #f0f0f0;border-radius:8px;font-size:13px;font-weight:600;color:#262626;background:white;cursor:pointer;outline:none;">
                    <option value="20" ${pagination.page_size === 20 ? 'selected' : ''}>20</option>
                    <option value="50" ${pagination.page_size === 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${pagination.page_size === 100 ? 'selected' : ''}>100</option>
                    <option value="200" ${pagination.page_size === 200 ? 'selected' : ''}>200</option>
                </select>
                <span style="color:#595959;font-size:13px;">条</span>
            </div>
            <span style="margin-left:16px;">当前第 <strong>${pagination.page}</strong> 页，共 <strong>${pagination.total_pages}</strong> 页</span>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-sm ${pagination.page === 1 ? 'btn-secondary' : 'btn-primary'}" onclick="loadPage(${pagination.page - 1})" ${pagination.page === 1 ? 'disabled' : ''}>上一页</button>
            <button class="btn btn-sm ${pagination.page === pagination.total_pages ? 'btn-secondary' : 'btn-primary'}" onclick="loadPage(${pagination.page + 1})" ${pagination.page === pagination.total_pages ? 'disabled' : ''}>下一页</button>
        </div>
    `;
}

function loadPage(page) {
    currentPage = page;
    loadUsers();
}

function changePageSize(size) {
    pageSize = parseInt(size);
    currentPage = 1;
    loadUsers();
}

function searchUsers() {
    currentPage = 1;
    loadUsers();
}

function toggleHeaderCheckbox() {
    const headerCheckbox = document.getElementById('headerCheckbox');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = headerCheckbox.checked;
    });
    updateSelectedCount();
    updateHeaderCheckbox();
}

function updateHeaderCheckbox() {
    const headerCheckbox = document.getElementById('headerCheckbox');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    
    if (checkboxes.length > 0 && checkedBoxes.length === checkboxes.length) {
        headerCheckbox.checked = true;
        headerCheckbox.indeterminate = false;
    } else if (checkedBoxes.length > 0) {
        headerCheckbox.checked = false;
        headerCheckbox.indeterminate = true;
    } else {
        headerCheckbox.checked = false;
        headerCheckbox.indeterminate = false;
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    const batchBar = document.getElementById('batchActionBar');
    if (count > 0) {
        batchBar.classList.add('show');
    } else {
        batchBar.classList.remove('show');
    }
}

function hideBatchBar() {
    const batchBar = document.getElementById('batchActionBar');
    batchBar.classList.remove('show');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function getSelectedUserIds() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function batchEnable() {
    const ids = getSelectedUserIds();
    if (ids.length === 0) {
        alert('请先选择用户');
        return;
    }
    batchUpdateStatus(ids, 1);
}

function batchDisable() {
    const ids = getSelectedUserIds();
    if (ids.length === 0) {
        alert('请先选择用户');
        return;
    }
    batchUpdateStatus(ids, 0);
}

function batchDelete() {
    const ids = getSelectedUserIds();
    if (ids.length === 0) {
        alert('请先选择用户');
        return;
    }
    if (!confirm(`确定要删除选中的 ${ids.length} 个用户吗？此操作不可恢复！`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ids', ids.join(','));
    
    fetch('../api/user.php?action=batch_delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code !== 200) {
            alert('删除失败：' + res.message);
            return;
        }
        alert('删除成功！');
        hideBatchBar();
        loadUsers();
    })
    .catch(err => alert('删除失败：' + err.message));
}

function batchUpdateStatus(ids, status) {
    const formData = new FormData();
    formData.append('ids', ids.join(','));
    formData.append('status', status);
    
    fetch('../api/user.php?action=batch_set_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code !== 200) {
            alert('操作失败：' + res.message);
            return;
        }
        alert('操作成功！');
        hideBatchBar();
        loadUsers();
    })
    .catch(err => alert('操作失败：' + err.message));
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleUserStatus(id, newStatus) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);
    
    fetch('../api/user.php?action=set_status', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code !== 200) {
            alert('操作失败：' + res.message);
            loadUsers(); // 恢复原状态
            return;
        }
        // 不显示成功提示，避免频繁弹窗干扰
        loadUsers(); // 重新加载以更新状态显示
    })
    .catch(err => {
        console.error('操作失败:', err);
        alert('网络错误');
        loadUsers(); // 恢复原状态
    });
}

function deleteUser(id) {
    if (!confirm('确定要删除这个用户吗？')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('../api/user.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code !== 200) {
            alert('删除失败：' + res.message);
            return;
        }
        alert('删除成功！');
        loadUsers();
    })
    .catch(err => alert('删除失败：' + err.message));
}

// 初始化
loadUsers();
</script>

<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>
