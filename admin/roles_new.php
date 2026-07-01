<?php
/**
 * 角色管理页面 - 重组版
 * 功能：列表展示 + 权限配置
 */
session_start();

// 检查权限：只有超级管理员可以管理角色
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();

if (!$permissionChecker->isSuperAdmin()) {
    header('Location: dashboard.php?error=no_permission');
    exit;
}

$pageTitle = '角色管理';
$currentPage = 'role';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* 容器 */
.page-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

/* 头部 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
}

/* 按钮 */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.3);
}

/* 表格 */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    padding: 16px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: #262626;
    background: #fafafa;
    border-bottom: 2px solid #f0f0f0;
}

.table td {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #595959;
}

.table tbody tr:hover {
    background: #f0f5ff;
}

/* 状态标签 */
.status-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.status-1 {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.status-0 {
    background: #f5f5f5;
    color: #999;
    border: 1px solid #d9d9d9;
}

/* 操作按钮 */
.action-btns {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 14px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.action-btn-edit {
    background: #e6f7ff;
    color: #0050b3;
}

.action-btn-delete {
    background: #fff2f0;
    color: #cf1322;
}

.action-btn:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

/* 用户列表 */
.user-list {
    max-width: 300px;
}

.user-tag {
    display: inline-block;
    background: #f0f5ff;
    color: #1890ff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    margin: 2px;
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}
</style>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">🎭 角色管理</h1>
        <a href="role_edit.php" class="btn btn-primary">➕ 新增角色</a>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>角色名称</th>
                    <th>描述</th>
                    <th>受用人（销售人员）</th>
                    <th style="width: 100px;">状态</th>
                    <th style="width: 200px;">操作</th>
                </tr>
            </thead>
            <tbody id="roleList">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">加载中...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// 加载角色列表
function loadRoles() {
    fetch('../api/role.php?action=list')
        .then(res => res.json())
        .then(res => {
            if (res.code !== 200) {
                document.getElementById('roleList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #f5222d;">加载失败</td></tr>';
                return;
            }
            
            const list = res.data.list || [];
            
            if (list.length === 0) {
                document.getElementById('roleList').innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>暂无角色数据</p>
                                <p style="margin-top: 10px;">
                                    <a href="role_edit.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;">新增角色</a>
                                </p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            document.getElementById('roleList').innerHTML = list.map(item => {
                // 处理受用人列表
                let salesmenHtml = '';
                if (item.salesmen && item.salesmen.length > 0) {
                    salesmenHtml = item.salesmen.map(s => 
                        `<span class="user-tag">👤 ${s.name}</span>`
                    ).join('');
                } else {
                    salesmenHtml = '<span style="color: #999; font-size: 13px;">暂无销售人员</span>';
                }
                
                return `
                    <tr>
                        <td><span style="font-weight: 600; color: #bfbfbf;">#${item.id}</span></td>
                        <td><span style="font-weight: 600; color: #262626;">${item.role_name}</span></td>
                        <td>${item.description || '<span style="color: #999;">-</span>'}</td>
                        <td class="user-list">${salesmenHtml}</td>
                        <td><span class="status-tag status-${item.status}">${item.status == 1 ? '✅ 启用' : '⏸️ 禁用'}</span></td>
                        <td>
                            <div class="action-btns">
                                <a href="role_edit.php?id=${item.id}" class="action-btn action-btn-edit">编辑</a>
                                <button class="action-btn action-btn-delete" onclick="deleteRole(${item.id}, '${item.role_name}')">删除</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            document.getElementById('roleList').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #f5222d;">加载失败</td></tr>';
        });
}

// 删除角色
function deleteRole(id, roleName) {
    if (!confirm(`确定要删除角色"${roleName}"吗？\n\n注意：删除后，使用该角色的销售人员将失去角色权限。`)) {
        return;
    }
    
    fetch('../api/role.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert('✅ 删除成功');
            loadRoles();
        } else {
            alert('❌ 删除失败：' + (res.message || '未知错误'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('❌ 删除失败：网络错误');
    });
}

// 页面加载时获取角色列表
document.addEventListener('DOMContentLoaded', loadRoles);
</script>

<?php require_once __DIR__ . '/includes/footer.php';
