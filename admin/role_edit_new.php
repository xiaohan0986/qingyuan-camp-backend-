<?php
/**
 * 角色编辑页面 - 权限配置
 * 功能：配置角色权限（16 个模块，每个模块 6 个操作）
 */
session_start();

// 检查权限：只有超级管理员可以管理角色
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();

if (!$permissionChecker->isSuperAdmin()) {
    header('Location: dashboard.php?error=no_permission');
    exit;
}

$pageTitle = '编辑角色';
$currentPage = 'role';
require_once __DIR__ . '/includes/header.php';

// 获取角色 ID
$roleId = $_GET['id'] ?? null;
$isEdit = $roleId !== null;

// 如果是编辑模式，获取角色信息
$role = null;
if ($isEdit) {
    require_once dirname(__DIR__) . '/includes/Database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
    $stmt->execute([':id' => $roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        echo "<script>alert('角色不存在'); window.location.href='roles.php';</script>";
        exit;
    }
}
?>

<style>
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #8c8c8c;
    font-size: 14px;
}

/* 表单 */
.form-container {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #262626;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #1890ff;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

/* 权限模块 */
.permission-section {
    margin-bottom: 32px;
}

.permission-section-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.permission-modules {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 16px;
}

.permission-module {
    background: #fafafa;
    border-radius: 8px;
    padding: 16px;
    border: 2px solid #f0f0f0;
    transition: all 0.3s;
}

.permission-module:hover {
    border-color: #1890ff;
    background: #f0f5ff;
}

.module-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.module-name {
    font-size: 15px;
    font-weight: 600;
    color: #262626;
}

.module-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.module-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.action-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #595959;
    padding: 6px 8px;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.action-checkbox:hover {
    background: #e6f7ff;
}

.action-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* 按钮 */
.form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
}

.btn {
    padding: 12px 32px;
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

.btn-secondary {
    background: #f5f5f5;
    color: #595959;
}

.btn-secondary:hover {
    background: #e6e6e6;
}

/* 提示信息 */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-info {
    background: #e6f7ff;
    border: 1px solid #91d5ff;
    color: #0050b3;
}
</style>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><?php echo $isEdit ? '编辑角色' : '➕ 新增角色'; ?></h1>
        <p class="page-subtitle">配置角色的权限，勾选表示允许，取消勾选表示禁止</p>
    </div>
    
    <?php if ($isEdit && $role): ?>
    <div class="alert alert-info">
        <strong>ℹ️ 当前编辑：</strong><?php echo htmlspecialchars($role['role_name']); ?>
        <?php if ($role['description']): ?>
        - <?php echo htmlspecialchars($role['description']); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <form id="roleForm" method="POST" action="../api/role.php?action=save">
        <input type="hidden" name="id" value="<?php echo $roleId ?? ''; ?>">
        
        <!-- 基本信息 -->
        <div class="form-container">
            <div class="form-group">
                <label>角色名称 *</label>
                <input type="text" name="role_name" required 
                       value="<?php echo htmlspecialchars($role['role_name'] ?? ''); ?>"
                       placeholder="如：超级管理员、运营人员、客服人员">
            </div>
            
            <div class="form-group">
                <label>角色标识 *</label>
                <input type="text" name="role_key" required 
                       value="<?php echo htmlspecialchars($role['role_key'] ?? ''); ?>"
                       placeholder="如：super_admin、operator、service">
                <p style="color: #8c8c8c; font-size: 12px; margin-top: 6px;">
                    英文标识，用于代码中引用
                </p>
            </div>
            
            <div class="form-group">
                <label>描述</label>
                <textarea name="description" placeholder="描述该角色的职责和权限范围"><?php echo htmlspecialchars($role['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>状态</label>
                <select name="status">
                    <option value="1" <?php echo ($role['status'] ?? 1) == 1 ? 'selected' : ''; ?>>✅ 启用</option>
                    <option value="0" <?php echo ($role['status'] ?? 1) == 0 ? 'selected' : ''; ?>>⏸️ 禁用</option>
                </select>
            </div>
        </div>
        
        <!-- 权限配置 -->
        <div class="form-container">
            <h2 style="margin-bottom: 24px; font-size: 18px;">🔐 权限配置</h2>
            <p style="color: #8c8c8c; margin-bottom: 24px; font-size: 14px;">
                勾选表示允许该角色执行对应操作，取消勾选表示禁止
            </p>
            
            <div class="permission-modules" id="permissionModules">
                <!-- 由 JavaScript 动态生成 -->
            </div>
        </div>
        
        <!-- 提交按钮 -->
        <div class="form-actions">
            <a href="roles.php" class="btn btn-secondary">❌ 取消</a>
            <button type="submit" class="btn btn-primary">💾 保存角色</button>
        </div>
    </form>
</div>

<script>
// 所有可权限管理的模块
const modules = {
    'dashboard': '📊 数据大屏',
    'position': '💼 岗位管理',
    'customer': '👥 客户管理',
    'user': '👤 用户管理',
    'article': '📰 文章管理',
    'store': '🏪 门店管理',
    'salesmen': '👨‍💼 销售管理',
    'finance': '💰 财务管理',
    'config': '🔧 系统配置',
    'role': '🎭 角色管理',
    'report': '📈 报表统计',
    'notification': '🔔 消息通知',
    'marketing': '🎯 营销管理',
    'miniprogram': '📱 小程序管理',
    'file': '📁 文件管理',
    'system': '⚙️ 系统工具'
};

// 每个模块的操作权限
const actions = {
    'view': '查看',
    'create': '➕ 创建',
    'edit': '编辑',
    'delete': '删除',
    'export': '导出',
    'import': '导入'
};

// 当前角色的权限
const currentPermissions = <?php echo $role && $role['permissions'] ? $role['permissions'] : '{}'; ?>;

// 渲染权限模块
function renderPermissionModules() {
    const container = document.getElementById('permissionModules');
    let html = '';
    
    for (const [moduleKey, moduleName] of Object.entries(modules)) {
        const modulePerms = currentPermissions[moduleKey] || {};
        const isModuleEnabled = modulePerms === true || Object.keys(modulePerms).length > 0;
        
        html += `
            <div class="permission-module">
                <div class="module-header">
                    <span class="module-name">${moduleName}</span>
                    <label class="action-checkbox">
                        <input type="checkbox" 
                               class="module-enable" 
                               data-module="${moduleKey}"
                               ${isModuleEnabled ? 'checked' : ''}
                               onchange="toggleModule('${moduleKey}')">
                        启用模块
                    </label>
                </div>
                <div class="module-actions" id="module-actions-${moduleKey}" style="${isModuleEnabled ? '' : 'opacity: 0.5; pointer-events: none;'}">
        `;
        
        for (const [actionKey, actionName] of Object.entries(actions)) {
            const isEnabled = modulePerms === true || modulePerms[actionKey];
            html += `
                <label class="action-checkbox">
                    <input type="checkbox" 
                           name="permissions[${moduleKey}][]" 
                           value="${actionKey}"
                           ${isEnabled ? 'checked' : ''}
                           ${!isModuleEnabled ? 'disabled' : ''}>
                    ${actionName}
                </label>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

// 切换模块启用状态
function toggleModule(moduleKey) {
    const checkbox = document.querySelector(`.module-enable[data-module="${moduleKey}"]`);
    const actionsDiv = document.getElementById(`module-actions-${moduleKey}`);
    const actionCheckboxes = actionsDiv.querySelectorAll('input[type="checkbox"]');
    
    if (checkbox.checked) {
        actionsDiv.style.opacity = '1';
        actionsDiv.style.pointerEvents = 'auto';
        actionCheckboxes.forEach(cb => cb.disabled = false);
    } else {
        actionsDiv.style.opacity = '0.5';
        actionsDiv.style.pointerEvents = 'none';
        actionCheckboxes.forEach(cb => {
            cb.disabled = true;
            cb.checked = false;
        });
    }
}

// 收集权限数据
function collectPermissions() {
    const permissions = {};
    
    for (const [moduleKey, moduleName] of Object.entries(modules)) {
        const checkbox = document.querySelector(`.module-enable[data-module="${moduleKey}"]`);
        if (!checkbox || !checkbox.checked) {
            continue;
        }
        
        const actionCheckboxes = document.querySelectorAll(`#module-actions-${moduleKey} input[type="checkbox"]:checked`);
        const enabledActions = [];
        
        actionCheckboxes.forEach(cb => {
            enabledActions.push(cb.value);
        });
        
        if (enabledActions.length > 0) {
            permissions[moduleKey] = {};
            enabledActions.forEach(action => {
                permissions[moduleKey][action] = true;
            });
        }
    }
    
    return permissions;
}

// 表单提交
document.getElementById('roleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const permissions = collectPermissions();
    
    // 添加权限数据
    formData.append('permissions', JSON.stringify(permissions));
    
    // 显示加载状态
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = '💾 保存中...';
    submitBtn.disabled = true;
    
    fetch('../api/role.php?action=save', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert('✅ 保存成功！');
            window.location.href = 'roles.php';
        } else {
            alert('❌ 保存失败：' + (res.message || '未知错误'));
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('❌ 保存失败：网络错误');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// 初始化
renderPermissionModules();
</script>

<?php require_once __DIR__ . '/includes/footer.php';
