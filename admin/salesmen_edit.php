<?php
$currentPage = 'salesmen';
$pageTitle = '销售顾问编辑';

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('salesmen');

require_once __DIR__ . '/includes/header.php';

// 获取销售顾问 ID（如果有则是编辑模式）
$salesmanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $salesmanId > 0;
?>

<style>
.edit-container {
    padding: 24px;
}

.edit-header {
    display: none !important; /* 屏蔽顶部按钮区域 */
    background: linear-gradient(135deg, #fafafa, white);
    padding: 24px 32px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.edit-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    display: flex;
    align-items: center;
    gap: 12px;
}

.edit-title .mode-tag {
    font-size: 14px;
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.mode-tag.new {
    background: linear-gradient(135deg, #52c41a, #73d13d);
    color: white;
}

.mode-tag.edit {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    color: #595959;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.back-btn:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.edit-form {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
}

.form-section {
    margin-bottom: 32px;
}

.form-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 20px;
    padding-left: 12px;
    border-left: 4px solid #1890ff;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 10px;
    color: #262626;
    font-weight: 600;
    font-size: 14px;
}

.form-group label::before {
    content: '';
    width: 3px;
    height: 14px;
    background: #1890ff;
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
    border-radius: 2px;
}

.form-group label.required::after {
    content: '*';
    color: #ff4d4f;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group .hint {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.form-group .hint a {
    color: #1890ff;
    text-decoration: none;
}

.form-group .hint a:hover {
    text-decoration: underline;
}

/* 头像上传 */
.avatar-upload {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.avatar-preview {
    width: 150px;
    height: 150px;
    border-radius: 8px;
    border: 3px solid #f0f0f0;
    overflow: hidden;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar-preview .placeholder {
    font-size: 60px;
    color: #d9d9d9;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.avatar-upload-btn {
    flex: 1;
}

.avatar-upload-actions {
    display: flex;
    gap: 12px;
    margin-top: 12px;
}

.btn-upload {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-remove {
    background: white;
    border: 2px solid #ff4d4f;
    color: #ff4d4f;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-remove:hover {
    background: #ff4d4f;
    color: white;
}

#avatarFileInput {
    display: none;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #f0f0f0;
    margin-top: 24px;
}

.btn {
    padding: 11px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-default {
    background: white;
    border: 2px solid #d9d9d9;
    color: #595959;
}

.btn-default:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ff4d4f, #ff7875);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 77, 79, 0.4);
}

/* 加载动画 */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    flex-direction: column;
    gap: 16px;
}

.loading-overlay.show {
    display: flex;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f0f0f0;
    border-top-color: #1890ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: #595959;
    font-size: 14px;
    font-weight: 600;
}

/* 响应式 */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .edit-form {
        padding: 20px;
    }
}
</style>

<div class="edit-container">
    <!-- 页面标题 -->
    <div class="edit-header" style="display: flex !important;">
        <div class="edit-title">
            <?php if ($isEditMode): ?>
                <span>编辑销售顾问</span>
                <span class="mode-tag edit">编辑模式</span>
            <?php else: ?>
                <span>新建销售顾问</span>
                <span class="mode-tag new">新建模式</span>
            <?php endif; ?>
        </div>
        <button class="back-btn" onclick="window.location.href='salesmen.php'">
            ← 返回列表
        </button>
    </div>

    <!-- 编辑表单 -->
    <div class="edit-form">
        <form id="salesmanForm" onsubmit="return saveSalesman(event)">
            <input type="hidden" id="formId" value="<?php echo $salesmanId; ?>">
            
            <div class="form-section">
                <div class="form-section-title">👤 基本信息</div>
                
                <!-- 头像上传 -->
                <div class="avatar-upload">
                    <div class="avatar-preview" id="avatarPreview">
                        <div class="placeholder">👤</div>
                    </div>
                    <div class="avatar-upload-btn">
                        <label for="avatarFileInput" class="btn-upload">
                            📷 选择头像
                        </label>
                        <input type="file" id="avatarFileInput" accept="image/*" onchange="handleAvatarUpload(event)">
                        <div class="avatar-upload-actions">
                            <button type="button" class="btn-remove" onclick="removeAvatar()" style="display: none;" id="removeAvatarBtn">
                                删除头像
                            </button>
                        </div>
                        <input type="hidden" id="formAvatar">
                        <div class="hint" style="margin-top: 8px;">
                            💡 支持 JPG、PNG、GIF 格式，建议尺寸 200x200px
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">姓名</label>
                        <input type="text" id="formName" required placeholder="请输入姓名">
                    </div>
                    <div class="form-group">
                        <label class="required">手机号</label>
                        <input type="text" id="formPhone" required placeholder="请输入手机号">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">登录密码</label>
                        <input type="password" id="formPassword" placeholder="请输入密码">
                        <div class="hint" id="passwordStatus">
                            💡 创建时必填，编辑时留空表示不修改
                        </div>
                        <div class="hint" id="passwordResetTip" style="display: none; color: #1890ff;">
                            🔑 忘记密码？<a href="javascript:void(0)" onclick="resetPassword()" style="color: #1890ff;">点击重置密码</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>所属角色</label>
                        <input type="text" value="销售人员" disabled style="background:#f5f5f5;color:#999;">
                        <input type="hidden" id="formRoleId" value="5">
                        <div class="hint">
                            💡 默认角色为销售人员，不可修改
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>邮箱</label>
                        <input type="email" id="formEmail" placeholder="请输入邮箱">
                    </div>
                    <div class="form-group">
                        <label>微信号</label>
                        <input type="text" id="formWechat" placeholder="请输入微信号">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-title">💼 工作信息</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>等级</label>
                        <select id="formLevel">
                            <option value="小白">小白</option>
                            <option value="初级">初级</option>
                            <option value="中级">中级</option>
                            <option value="高级">高级</option>
                            <option value="金牌">金牌</option>
                            <option value="王牌">王牌</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>状态</label>
                        <select id="formStatus">
                            <option value="在职">在职</option>
                            <option value="离职">离职</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>入职日期</label>
                        <input type="date" id="formEntryDate">
                    </div>
                    <div class="form-group">
                        <label>所属门店</label>
                        <select id="formStore">
                            <option value="">请选择门店</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>销售额（元）</label>
                        <input type="text" id="formSalesAmount" disabled style="background:#f5f5f5;color:#999;" value="0">
                        <div class="hint">💡 自动统计，不可手动修改</div>
                    </div>
                    <div class="form-group">
                        <label>成交量</label>
                        <input type="text" id="formDealCount" disabled style="background:#f5f5f5;color:#999;" value="0">
                        <div class="hint">💡 自动统计，不可手动修改</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>最后成交日期</label>
                        <input type="text" id="formLastDealDate" disabled style="background:#f5f5f5;color:#999;">
                        <div class="hint">💡 自动记录，不可手动修改</div>
                    </div>
                    <div class="form-group">
                        <label>排序</label>
                        <input type="number" id="formSortOrder" value="0">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>个人介绍</label>
                        <textarea id="formRemark" rows="3" placeholder="请输入个人介绍，如专业背景、服务特色等"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="window.location.href='salesmen.php'">
                    取消
                </button>
                <?php if ($isEditMode): ?>
                <button type="button" class="btn btn-danger" onclick="deleteSalesman()" style="margin-right: auto;">
                    删除
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    💾 保存
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 加载动画 -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <div class="loading-text" id="loadingText">加载中...</div>
</div>

<script>
// 页面加载时加载数据
<?php if ($isEditMode): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadStores();
    loadSalesmanData(<?php echo $salesmanId; ?>);
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
    loadStores();
});
<?php endif; ?>

// 显示加载动画
function showLoading(text) {
    document.getElementById('loadingText').textContent = text || '加载中...';
    document.getElementById('loadingOverlay').classList.add('show');
}

// 隐藏加载动画
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

// 处理头像上传
function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // 验证文件类型
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        alert('❌ 请上传 JPG、PNG 或 GIF 格式的图片');
        event.target.value = '';
        return;
    }
    
    // 验证文件大小（最大 5MB）
    if (file.size > 5 * 1024 * 1024) {
        alert('❌ 图片大小不能超过 5MB');
        event.target.value = '';
        return;
    }
    
    showLoading('正在上传头像...');
    
    // 使用 FormData 上传文件
    const formData = new FormData();
    formData.append('avatar', file);
    
    // 使用相对路径，适应任何部署环境
    const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const uploadUrl = window.location.protocol + '//' + window.location.host + basePath.substring(0, basePath.lastIndexOf('/')) + '/api/upload_avatar.php';
    console.log('[handleAvatarUpload] 上传 URL:', uploadUrl);
    
    fetch(uploadUrl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.success) {
            const imageUrl = res.data.url;
            console.log('头像上传成功，URL:', imageUrl);
            
            // 显示预览
            const preview = document.getElementById('avatarPreview');
            const img = document.createElement('img');
            img.src = imageUrl;
            img.alt = '头像';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.display = 'block';
            
            img.onload = function() {
                console.log('上传预览加载成功:', imageUrl);
            };
            img.onerror = function() {
                console.error('上传预览加载失败:', imageUrl);
            };
            
            preview.innerHTML = '';
            preview.appendChild(img);
            
            // 保存 URL
            document.getElementById('formAvatar').value = imageUrl;
            
            // 显示删除按钮
            document.getElementById('removeAvatarBtn').style.display = 'inline-flex';
            
            alert('✅ 头像上传成功');
        } else {
            alert('❌ ' + res.message);
            event.target.value = '';
        }
    })
    .catch(err => {
        hideLoading();
        console.error('上传失败:', err);
        alert('❌ 网络错误：' + err.message);
        event.target.value = '';
    });
}

// 删除头像
function removeAvatar() {
    document.getElementById('avatarFileInput').value = '';
    document.getElementById('formAvatar').value = '';
    document.getElementById('avatarPreview').innerHTML = '<div class="placeholder">👤</div>';
    document.getElementById('removeAvatarBtn').style.display = 'none';
}

// 显示头像
function displayAvatar(avatarUrl) {
    const preview = document.getElementById('avatarPreview');
    console.log('[displayAvatar] 调用，URL:', avatarUrl);
    
    if (avatarUrl && avatarUrl.trim() !== '') {
        // 处理 URL 路径
        let finalUrl = avatarUrl;
        
        // 如果是完整 URL，直接使用
        if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
            finalUrl = avatarUrl;
        }
        // 如果是 / 开头，添加域名
        else if (avatarUrl.startsWith('/')) {
            finalUrl = window.location.protocol + '//' + window.location.host + avatarUrl;
        }
        // 如果是 /uploads/ 开头，使用动态路径
        else if (avatarUrl.startsWith('/uploads/')) {
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const projectPath = basePath.substring(0, basePath.lastIndexOf('/'));
            finalUrl = window.location.protocol + '//' + window.location.host + projectPath + avatarUrl;
        }
        // 其他情况，假设是相对路径
        else {
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const projectPath = basePath.substring(0, basePath.lastIndexOf('/'));
            finalUrl = window.location.protocol + '//' + window.location.host + projectPath + '/uploads/avatars/' + avatarUrl;
        }
        
        console.log('[displayAvatar] 最终 URL:', finalUrl);
        
        // 创建图片元素
        const img = document.createElement('img');
        img.src = finalUrl;
        img.alt = '头像';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.display = 'block';
        
        img.onload = function() {
            console.log('[displayAvatar] 图片加载成功:', finalUrl);
        };
        
        img.onerror = function() {
            console.error('[displayAvatar] 图片加载失败:', finalUrl);
            console.error('[displayAvatar] 原始 URL:', avatarUrl);
            preview.innerHTML = '<div class="placeholder">👤</div>';
        };
        
        preview.innerHTML = '';
        preview.appendChild(img);
        document.getElementById('removeAvatarBtn').style.display = 'inline-flex';
    } else {
        console.log('[displayAvatar] 无头像 URL，显示占位符');
        preview.innerHTML = '<div class="placeholder">👤</div>';
        document.getElementById('removeAvatarBtn').style.display = 'none';
    }
}

// 加载门店列表
function loadStores() {
    fetch('../api/store.php?action=list')
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data && res.data.list) {
                const select = document.getElementById('formStore');
                select.innerHTML = '<option value="">请选择门店</option>';
                res.data.list.forEach(store => {
                    const option = document.createElement('option');
                    option.value = store.id;
                    option.textContent = store.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('加载门店失败:', err));
}

// 加载销售顾问数据
function loadSalesmanData(id) {
    showLoading('加载销售顾问数据...');
    
    fetch(`../api/salesmen.php?action=get&id=${id}`)
        .then(res => res.json())
        .then(res => {
            hideLoading();
            
            if (!res.success) {
                alert('加载失败：' + (res.message || '未知错误'));
                return;
            }
            
            const data = res.data;
            console.log('销售顾问数据:', data);
            console.log('头像 URL:', data.avatar);
            
            // 填充表单字段
            document.getElementById('formId').value = data.id || '';
            document.getElementById('formName').value = data.name || '';
            document.getElementById('formPhone').value = data.phone || '';
            document.getElementById('formPassword').value = '';
            document.getElementById('formEmail').value = data.email || '';
            document.getElementById('formWechat').value = data.wechat || '';
            document.getElementById('formAvatar').value = data.avatar || '';
            document.getElementById('formLevel').value = data.level || '小白';
            document.getElementById('formStatus').value = data.status || '在职';
            document.getElementById('formEntryDate').value = data.entry_date || '';
            document.getElementById('formStore').value = data.store_id || '';
            document.getElementById('formSalesAmount').value = data.sales_amount || 0;
            document.getElementById('formDealCount').value = data.deal_count || 0;
            document.getElementById('formLastDealDate').value = data.last_deal_date || '';
            document.getElementById('formSortOrder').value = data.sort_order || 0;
            document.getElementById('formRemark').value = data.remark || '';
            
            // 显示头像
            displayAvatar(data.avatar);
            
            // 更新密码提示
            document.getElementById('passwordStatus').innerHTML = '🔑 留空表示不修改密码';
            document.getElementById('passwordResetTip').style.display = 'block';
        })
        .catch(err => {
            hideLoading();
            console.error('加载失败:', err);
            alert('网络错误：' + err.message);
        });
}

// 重置密码
function resetPassword() {
    const id = document.getElementById('formId').value;
    if (!id) {
        alert('请先保存销售顾问信息');
        return;
    }
    
    if (!confirm('⚠️ 确定要重置密码吗？密码将重置为 123456')) {
        return;
    }
    
    showLoading('正在重置密码...');
    
    fetch(`../api/salesmen.php?action=reset_password`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: parseInt(id)})
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        if (res.success) {
            alert('✅ 密码已重置为 123456');
        } else {
            alert('❌ ' + (res.message || '操作失败'));
        }
    })
    .catch(err => {
        hideLoading();
        alert('❌ 网络错误：' + err.message);
    });
}

// 保存销售顾问
function saveSalesman(event) {
    event.preventDefault();
    
    const id = document.getElementById('formId').value;
    const isEditMode = id && id != '0' && id != '';
    
    // 验证必填字段
    const name = document.getElementById('formName').value.trim();
    if (!name) {
        alert('请输入姓名');
        document.getElementById('formName').focus();
        return false;
    }
    
    const phone = document.getElementById('formPhone').value.trim();
    if (!phone) {
        alert('请输入手机号');
        document.getElementById('formPhone').focus();
        return false;
    }
    
    const password = document.getElementById('formPassword').value;
    if (!isEditMode && !password) {
        alert('请输入登录密码（新建时必填）');
        document.getElementById('formPassword').focus();
        return false;
    }
    
    // 构建数据对象
    const storeId = document.getElementById('formStore').value;
    const storeSelect = document.getElementById('formStore');
    const storeName = storeId ? storeSelect.options[storeSelect.selectedIndex]?.text || '' : '';
    
    const data = {
        name: name,
        phone: phone,
        role_id: document.getElementById('formRoleId').value,
        email: document.getElementById('formEmail').value.trim(),
        wechat: document.getElementById('formWechat').value.trim(),
        avatar: document.getElementById('formAvatar').value.trim(),
        level: document.getElementById('formLevel').value,
        status: document.getElementById('formStatus').value,
        entry_date: document.getElementById('formEntryDate').value || null,
        store_id: storeId || null,
        store_name: storeName,
        sales_amount: parseFloat(document.getElementById('formSalesAmount').value) || 0,
        deal_count: parseInt(document.getElementById('formDealCount').value) || 0,
        last_deal_date: document.getElementById('formLastDealDate').value || null,
        sort_order: parseInt(document.getElementById('formSortOrder').value) || 0,
        remark: document.getElementById('formRemark').value.trim()
    };
    
    if (password) {
        data.password = password;
    }
    
    if (isEditMode) {
        data.id = parseInt(id);
    }
    
    const action = isEditMode ? 'update' : 'create';
    
    showLoading('正在保存...');
    
    fetch(`../api/salesmen.php?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.success) {
            alert('✅ 保存成功');
            // 返回列表页面
            window.location.href = 'salesmen.php';
        } else {
            alert('❌ ' + (res.message || '操作失败'));
        }
    })
    .catch(err => {
        hideLoading();
        console.error('保存失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
    
    return false;
}

// 删除销售顾问
function deleteSalesman() {
    const id = document.getElementById('formId').value;
    
    if (!id) {
        alert('无效的销售顾问 ID');
        return;
    }
    
    if (!confirm('⚠️ 确定要删除这个销售顾问吗？此操作不可恢复！')) {
        return;
    }
    
    showLoading('正在删除...');
    
    fetch(`../api/salesmen.php?action=delete`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: parseInt(id)})
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.success) {
            alert('✅ 删除成功');
            window.location.href = 'salesmen.php';
        } else {
            alert('❌ ' + (res.message || '操作失败'));
        }
    })
    .catch(err => {
        hideLoading();
        console.error('删除失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php';
