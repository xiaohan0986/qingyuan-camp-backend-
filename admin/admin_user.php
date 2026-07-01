<?php
/**
 * 后台用户管理页面
 */
require_once __DIR__ . '/includes/header.php';

$currentPage = 'admin_user';
$pageTitle = '后台用户管理';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; padding: 24px; }
        
        .page-container { max-width: 1400px; margin: 0 auto; }
        
        .page-header {
            background: white;
            padding: 24px 32px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        .page-title { font-size: 24px; font-weight: 700; color: #262626; display: flex; align-items: center; gap: 12px; }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
        .btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
        .btn-success { background: linear-gradient(135deg, #52c41a, #73d13d); color: white; }
        .btn-default { background: #f5f5f5; color: #666; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .btn-danger { background: linear-gradient(135deg, #f5222d, #ff4d4f); color: white; }
        
        .data-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            padding: 16px 20px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
            color: #262626;
            border-bottom: 2px solid #f0f0f0;
            background: linear-gradient(135deg, #fafafa, #f5f5f5);
        }
        .data-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #595959;
        }
        .data-table tr:hover { background: #fafafa; }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.active { background: #f6ffed; color: #52c41a; }
        .status-badge.inactive { background: #f5f5f5; color: #d9d9d9; }
        
        .action-buttons { display: flex; gap: 8px; }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #bfbfbf;
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state .text { font-size: 16px; }
        
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
            width: 700px;
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

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #262626;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group label.required::after { content: ' *'; color: #ff4d4f; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #1890ff;
            box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
        }
        
        .modal-footer {
            padding: 20px 32px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .password-display {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 8px;
            padding: 12px;
            background: #f0f5ff;
            border: 1px solid #d6e4ff;
            border-radius: 8px;
        }
        .password-display input {
            flex: 1;
            border: none;
            background: transparent;
            font-family: monospace;
            font-size: 14px;
        }
        .password-display input:focus { outline: none; }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            transform: translateX(400px);
            transition: transform 0.3s;
            z-index: 9999;
        }
        .toast.show { transform: translateX(0); }
        .toast-success { background: linear-gradient(135deg, #52c41a, #73d13d); }
        .toast-error { background: linear-gradient(135deg, #f5222d, #ff4d4f); }
        .batch-toolbar { display: none; background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 8px; padding: 12px 20px; margin-bottom: 16px; align-items: center; gap: 12px; }
        .batch-toolbar.show { display: flex !important; }
        .batch-toolbar .batch-info { font-size: 14px; color: #0050b3; }
        .batch-toolbar .batch-info strong { color: #1890ff; }
        .batch-toolbar .batch-actions { display: flex; gap: 8px; margin-left: auto; }
        .batch-toolbar .btn { padding: 6px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; font-weight: 500; }
        .batch-toolbar .btn-primary { background: #1890ff; color: white; }
        .batch-toolbar .btn-primary:hover { background: #096dd9; }
        .batch-toolbar .btn-danger { background: #ff4d4f; color: white; }
        .batch-toolbar .btn-danger:hover { background: #cf1322; }
        .checkbox-cell { width: 40px; text-align: center; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">
                👥 后台用户管理
            </h1>
            <button class="btn btn-primary" onclick="openUserDrawer()">添加用户</button>
        </div>
        
        <!-- 批量操作栏 -->
        <div class="batch-toolbar" id="batchToolbar">
            <div class="batch-info">已选择 <strong id="selectedCount">0</strong> 个用户</div>
            <div class="batch-actions">
                <button class="btn btn-primary" onclick="batchEnable()">批量启用</button>
                <button class="btn btn-danger" onclick="batchDisable()">批量禁用</button>
                <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
            </div>
        </div>
        
        <div class="data-table-container">
            <table class="data-table" id="userTable">
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>昵称</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <!-- 动态加载 -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- 用户编辑弹窗 -->
    <div id="userDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeUserDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3 id="drawerTitle">用户管理</h3>
                <button class="drawer-close" onclick="closeUserDrawer()">x</button>
            </div>
            <div class="drawer-body" style="padding:24px;">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    
                    <div class="form-group">
                        <label class="required">用户名</label>
                        <input type="text" id="username" required placeholder="请输入用户名">
                    </div>
                    
                    <div class="form-group">
                        <label>昵称</label>
                        <input type="text" id="nickname" placeholder="请输入昵称">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">密码</label>
                        <input type="password" id="password" placeholder="请输入密码（新建用户必填，编辑时留空表示不修改）">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">角色</label>
                        <select id="roleId" required>
                            <option value="">请选择角色</option>
                            <!-- 动态加载角色 -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>状态</label>
                        <select id="status">
                            <option value="1">正常</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                    
                    <div id="passwordDisplayArea"></div>
                </form>
            </div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closeUserDrawer()">取消</button>
                <button class="btn btn-primary" onclick="saveUser()">保存</button>
            </div>
        </div>
    </div>
                    
                    <div class="form-group">
                        <label class="required">密码</label>
                        <input type="password" id="password" placeholder="请输入密码（新建用户必填，编辑时留空表示不修改）">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">角色</label>
                        <select id="roleId" required>
                            <option value="">请选择角色</option>
                            <!-- 动态加载角色 -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>状态</label>
                        <select id="status">
                            <option value="1">正常</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                    
                    <div id="passwordDisplayArea"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" onclick="closeUserDrawer()">取消</button>
                <button class="btn btn-primary" onclick="saveUser()">💾 保存</button>
            </div>
        </div>
    </div>
    
    <div id="toast" class="toast"></div>
    
    <script>
    let allUsers = [];
    let roles = [];
    
    // 加载用户列表

    function toggleAll(checkbox) {
        var cbs = document.querySelectorAll('.user-checkbox');
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = checkbox.checked;
        updateBatchToolbar();
    }
    function updateBatchToolbar() {
        var cbs = document.querySelectorAll('.user-checkbox:checked');
        var count = cbs.length;
        var tb = document.getElementById('batchToolbar');
        var sc = document.getElementById('selectedCount');
        if (sc) sc.textContent = count;
        if (tb) { if (count > 0) tb.classList.add('show'); else tb.classList.remove('show'); }
    }
    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.user-checkbox:checked')).map(function(cb) { return cb.value; }).join(',');
    }
    function batchEnable() {
        var ids = getSelectedIds();
        if (!ids) { alert('请先选择用户'); return; }
        if (!confirm('确定要启用选中的用户吗？')) return;
        fetch('../api/admin_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_status&ids=' + ids + '&status=1' })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.code === 200) { loadUsers(); alert('操作成功'); } else { alert(d.message); } });
    }
    function batchDisable() {
        var ids = getSelectedIds();
        if (!ids) { alert('请先选择用户'); return; }
        if (!confirm('确定要禁用选中的用户吗？')) return;
        fetch('../api/admin_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_status&ids=' + ids + '&status=0' })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.code === 200) { loadUsers(); alert('操作成功'); } else { alert(d.message); } });
    }
    function batchDelete() {
        var ids = getSelectedIds();
        if (!ids) { alert('请先选择用户'); return; }
        if (!confirm('确定要删除选中的用户吗？此操作不可恢复！')) return;
        fetch('../api/admin_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=batch_delete&ids=' + ids })
        .then(function(r) { return r.json(); }).then(function(d) { if (d.code === 200) { loadUsers(); alert('操作成功'); } else { alert(d.message); } });
    }

    function loadUsers() {
        fetch('../api/admin_user.php?action=list')
            .then(res => res.json())
            .then(res => {
                if (res.code === 200) {
                    allUsers = res.data;
                    renderUserTable();
                }
            });
    }
    
    // 加载角色列表
    function loadRoles() {
        fetch('../api/admin_user.php?action=roles')
            .then(res => res.json())
            .then(res => {
                if (res.code === 200) {
                    roles = res.data;
                    const select = document.getElementById('roleId');
                    roles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.id;
                        option.textContent = role.role_name;
                        select.appendChild(option);
                    });
                }
            });
    }
    
    // 渲染用户表格
    function renderUserTable() {
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = '';
        
        if (allUsers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <div class="text">暂无用户</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        allUsers.forEach(user => {
            const role = roles.find(r => r.id == user.role_id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="checkbox-cell"><input type="checkbox" class="user-checkbox" value="${user.id}" onchange="updateBatchToolbar()"></td>
                <td>${user.id}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.nickname || '-')}</td>
                <td>${role ? escapeHtml(role.role_name) : '<span style="color: #999;">未分配</span>'}</td>
                <td>
                    <span class="status-badge ${user.status == 1 ? 'active' : 'inactive'}">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: ${user.status == 1 ? '#52c41a' : '#d9d9d9'};"></span>
                        ${user.status == 1 ? '正常' : '禁用'}
                    </span>
                </td>
                <td>${user.created_at || '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">编辑</button>
                        ${user.id != 1 ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">删除</button>` : ''}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    // 打开用户弹窗
    function openUserDrawer(userId = null) {
        document.getElementById('userDrawer').classList.add('show');
        document.getElementById('userForm').reset();
        document.getElementById('passwordDisplayArea').innerHTML = '';
        
        if (userId) {
            document.getElementById('drawerTitle').textContent = '编辑用户';
            document.getElementById('userId').value = userId;
            document.getElementById('password').placeholder = '留空表示不修改密码';
            
            const user = allUsers.find(u => u.id == userId);
            if (user) {
                document.getElementById('username').value = user.username;
                document.getElementById('nickname').value = user.nickname || '';
                document.getElementById('roleId').value = user.role_id || '';
                document.getElementById('status').value = user.status;
                
                // 显示密码
                if (user.password_plain) {
                    document.getElementById('passwordDisplayArea').innerHTML = `
                        <div class="password-display">
                            <span style="color: #52c41a; font-weight: 600;">✅ 当前密码：</span>
                            <input type="password" id="currentPassword" value="${escapeHtml(user.password_plain)}" readonly>
                            <button type="button" class="btn btn-sm btn-default" onclick="togglePassword()">👁️</button>
                            <button type="button" class="btn btn-sm btn-default" onclick="copyPassword()">📋</button>
                        </div>
                    `;
                }
            }
        } else {
            document.getElementById('drawerTitle').textContent = '添加用户';
            document.getElementById('userId').value = '';
            document.getElementById('password').required = true;
        }
    }
    
    // 关闭弹窗
    function closeUserDrawer() {
        var d = document.getElementById('userDrawer');
        d.classList.add('closing');
        setTimeout(function() { d.classList.remove('show', 'closing'); document.body.style.overflow = ''; }, 300);;
    }
    
    // 编辑用户
    function editUser(userId) {
        openUserDrawer(userId);
    }
    
    // 保存用户
    function saveUser() {
        const userId = document.getElementById('userId').value;
        const username = document.getElementById('username').value.trim();
        const nickname = document.getElementById('nickname').value.trim();
        const password = document.getElementById('password').value;
        const roleId = document.getElementById('roleId').value;
        const status = document.getElementById('status').value;
        
        if (!username) {
            showToast('请输入用户名', 'error');
            return;
        }
        
        if (!roleId) {
            showToast('请选择角色', 'error');
            return;
        }
        
        if (!userId && !password) {
            showToast('新建用户必须设置密码', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', userId ? 'update' : 'create');
        if (userId) formData.append('id', userId);
        formData.append('username', username);
        formData.append('nickname', nickname);
        formData.append('password', password);
        formData.append('role_id', roleId);
        formData.append('status', status);
        
        fetch('../api/admin_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                showToast(userId ? '用户更新成功' : '用户创建成功', 'success');
                closeUserDrawer();
                loadUsers();
            } else {
                showToast(res.message || '操作失败', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('操作失败', 'error');
        });
    }
    
    // 删除用户
    function deleteUser(userId) {
        if (!confirm('确定要删除该用户吗？')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', userId);
        
        fetch('../api/admin_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                showToast('用户删除成功', 'success');
                loadUsers();
            } else {
                showToast(res.message || '删除失败', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('删除失败', 'error');
        });
    }
    
    // 切换密码显示
    function togglePassword() {
        const input = document.getElementById('currentPassword');
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
    
    // 复制密码
    function copyPassword() {
        const input = document.getElementById('currentPassword');
        input.select();
        document.execCommand('copy');
        showToast('密码已复制', 'success');
    }
    
    // 显示提示
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast toast-' + type + ' show';
        setTimeout(() => {
            toast.className = 'toast';
        }, 3000);
    }
    
    // HTML 转义
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 初始化
    loadUsers();
    loadRoles();
    </script>
<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>
