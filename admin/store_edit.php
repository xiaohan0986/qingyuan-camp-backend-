<?php
$currentPage = 'store';
$pageTitle = '门店编辑';
require_once __DIR__ . '/includes/header.php';

// 获取门店 ID（如果有则是编辑模式）
$storeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $storeId > 0;
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

.header-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 11px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #52c41a, #73d13d);
    color: white;
}

.btn-default {
    background: #f0f0f0;
    color: #666;
}

.btn-danger {
    background: linear-gradient(135deg, #f5222d, #ff4d4f);
    color: white;
}

.edit-form {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

/* 标签输入组件样式 */
.tag-input-container {
    margin-top: 12px;
}

.tag-input-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    min-height: 44px;
    align-items: center;
    background: #fff;
}

.tag-input-wrapper input {
    flex: 1;
    min-width: 120px;
    border: none;
    outline: none;
    font-size: 14px;
    padding: 4px 0;
}

.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
}

.tag-item .tag-close {
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.tag-item .tag-close:hover {
    opacity: 1;
}
}

.form-section {
    margin-bottom: 32px;
}

.form-section-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-row.half {
    grid-template-columns: repeat(2, 1fr);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 10px;
    color: #262626;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-group label::before {
    content: '';
    width: 3px;
    height: 14px;
    background: linear-gradient(180deg, #1890ff, #40a9ff);
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
    border-radius: 10px;
    font-size: 14px;
    background: white;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1890ff;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group .hint {
    margin-top: 6px;
    font-size: 12px;
    color: #8c8c8c;
}

.form-actions {
    text-align: center;
    padding-top: 24px;
    border-top: 1px solid #f0f0f0;
    margin-top: 24px;
    display: flex;
    justify-content: center;
    gap: 16px;
}

.form-actions .btn {
    min-width: 140px;
}

/* 图片上传样式 */
.image-upload-container {
    margin-top: 8px;
}

.image-upload-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.image-upload-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.3s;
}

.image-upload-item:hover {
    border-color: #1890ff;
    box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
}

.image-upload-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.image-upload-item .delete-btn {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    cursor: pointer;
}

.image-upload-item:hover .delete-btn {
    opacity: 1;
}

.image-upload-item .delete-btn span {
    color: white;
    font-size: 24px;
    font-weight: bold;
}

.image-upload-trigger {
    border: 2px dashed #d9d9d9;
    border-radius: 8px;
    padding: 24px 16px;
    text-align: center;
    transition: all 0.3s;
    background: #fafafa;
}

.image-upload-container:hover .image-upload-trigger {
    border-color: #40a9ff;
    background: #e6f7ff;
}

.image-upload-trigger .upload-icon {
    font-size: 40px;
    margin-bottom: 8px;
}

.image-upload-trigger .upload-text {
    color: #262626;
    font-size: 14px;
    margin-bottom: 4px;
}

.image-upload-trigger .upload-hint {
    color: #8c8c8c;
    font-size: 12px;
}

.image-count-hint {
    color: #8c8c8c;
    font-size: 12px;
    margin-top: 8px;
}

/* 头像上传样式 */
.avatar-upload-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #fafafa;
    border-radius: 8px;
    border: 2px dashed #d9d9d9;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar-preview .placeholder {
    font-size: 48px;
    color: #d9d9d9;
}

.avatar-buttons {
    display: flex;
    gap: 8px;
}

.avatar-upload-btn,
.avatar-remove-btn {
    padding: 6px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
    white-space: nowrap;
}

.avatar-upload-btn {
    background: #1890ff;
    color: white;
}

.avatar-upload-btn:hover {
    background: #40a9ff;
}

.avatar-remove-btn {
    background: #fff2f0;
    color: #cf1322;
}

.avatar-remove-btn:hover {
    background: #ffccc7;
}
</style>

<div class="edit-container">
    <!-- 编辑头部 -->
    <div class="edit-header">
        <div class="edit-title">
            <?php if ($isEditMode): ?>
                <span>🏪 编辑门店</span>
                <span class="mode-tag edit">编辑模式</span>
            <?php else: ?>
                <span>🏪 新建门店</span>
                <span class="mode-tag new">新建模式</span>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <button class="btn btn-default" onclick="window.location.href='store.php'">
                ← 返回列表
            </button>
            <?php if ($isEditMode): ?>
                <button class="btn btn-danger" onclick="deleteStore()">
                    删除门店
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 编辑表单 -->
    <div class="edit-form">
        <form id="storeForm" onsubmit="return saveStore(event)">
            <input type="hidden" id="storeId" value="<?php echo $storeId; ?>">
            
            <div class="form-section">
                <div class="form-section-title">📍 基本信息</div>
                
                <!-- 头像上传区域 - 独立一行居中显示 -->
                <div class="form-row full" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: center;">
                        <div class="avatar-upload-container">
                            <div class="avatar-preview" id="avatarPreview">
                                <div class="placeholder">🏪</div>
                            </div>
                            <div class="avatar-buttons">
                                <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatarUploadInput').click()">
                                    📷 选择头像
                                </button>
                                <button type="button" class="avatar-remove-btn" id="removeAvatarBtn" onclick="removeAvatar()" style="display: none;">
                                    删除
                                </button>
                            </div>
                            <input type="file" id="avatarUploadInput" accept="image/*" style="display: none;" onchange="handleAvatarUpload(event)">
                            <input type="hidden" id="storeAvatar" value="">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="required">门店名称</label>
                    <input type="text" id="storeName" required placeholder="请输入门店名称">
                </div>
                
                <div class="form-row half">
                    <div class="form-group">
                        <label>负责人</label>
                        <input type="text" id="storeManager" placeholder="请输入负责人姓名">
                    </div>
                    <div class="form-group">
                        <label>负责人电话</label>
                        <input type="text" id="storeManagerPhone" placeholder="请输入负责人电话">
                    </div>
                </div>
                
                <div class="form-row half">
                    <div class="form-group">
                        <label class="required">国家</label>
                        <input type="text" id="storeCountry" required placeholder="请输入国家">
                    </div>
                    <div class="form-group">
                        <label class="required">城市</label>
                        <input type="text" id="storeCity" required placeholder="请输入城市">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label class="required">详细地址</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="storeAddress" required placeholder="请输入详细地址" style="flex: 1;">
                            <button type="button" class="btn btn-primary" onclick="openMapSelector()" style="white-space: nowrap;">
                                📍 地图选点
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 经纬度坐标（只读，通过地图选点自动填充） -->
                <div class="form-row half" id="coordinateRow" style="display: none;">
                    <div class="form-group">
                        <label>纬度</label>
                        <input type="text" id="storeLatitude" readonly placeholder="点击「地图选点」获取坐标" 
                            style="background: #f5f5f5; cursor: not-allowed; color: #595959;"
                            title="由地图选点自动获取，不可手动修改">
                    </div>
                    <div class="form-group">
                        <label>经度</label>
                        <input type="text" id="storeLongitude" readonly placeholder="点击「地图选点」获取坐标"
                            style="background: #f5f5f5; cursor: not-allowed; color: #595959;"
                            title="由地图选点自动获取，不可手动修改">
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>门店标签</label>
                        <div class="tag-input-container">
                            <div class="tag-input-wrapper">
                                <input type="text" id="storeTagsInput" placeholder="输入标签后按回车添加" style="flex: 1; border: none; outline: none; font-size: 14px;">
                            </div>
                            <div class="tag-list" id="storeTagsList"></div>
                            <input type="hidden" id="storeTags" value="">
                        </div>
                        <div class="hint" style="margin-top: 8px;">
                            例如：旗舰店、标准店、加盟店、面试店等，按回车键添加
                        </div>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>门店环境</label>
                        <div class="image-upload-container">
                            <div class="image-upload-list" id="environmentImagesList"></div>
                            <div class="image-upload-trigger" id="environmentUploadTrigger">
                                <input type="file" id="environmentUploadInput" accept="image/*" multiple style="display: none;">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">点击或拖拽上传门店环境照片</div>
                                <div class="upload-hint">最多 20 张，支持 JPG、PNG、GIF 格式</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row full">
                    <div class="form-group">
                        <label>门店介绍</label>
                        <textarea id="storeDescription" placeholder="请输入门店介绍" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="window.location.href='store.php'">
                    取消
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 门店标签管理
let storeTags = [];

// 渲染标签列表
function renderStoreTags() {
    const tagList = document.getElementById('storeTagsList');
    if (!tagList) return;
    
    tagList.innerHTML = '';
    storeTags.forEach((tag, index) => {
        const tagEl = document.createElement('div');
        tagEl.className = 'tag-item';
        tagEl.innerHTML = `
            <span>${tag}</span>
            <span class="tag-close" onclick="removeTag(${index})">×</span>
        `;
        tagList.appendChild(tagEl);
    });
    
    // 更新隐藏字段
    document.getElementById('storeTags').value = JSON.stringify(storeTags);
}

// 添加标签
function addTag(tag) {
    if (!tag || tag.trim() === '') return;
    tag = tag.trim();
    
    // 避免重复
    if (storeTags.includes(tag)) {
        return;
    }
    
    storeTags.push(tag);
    renderStoreTags();
}

// 删除标签
function removeTag(index) {
    if (index >= 0 && index < storeTags.length) {
        storeTags.splice(index, 1);
        renderStoreTags();
    }
}

// 标签输入框事件处理
function initTagInput() {
    const tagInput = document.getElementById('storeTagsInput');
    if (!tagInput) return;
    
    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addTag(value);
                this.value = '';
            }
        }
    });
    
    // 点击输入框时自动聚焦
    tagInput.addEventListener('click', function() {
        this.focus();
    });
}

// 页面加载时加载门店数据
<?php if ($isEditMode): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadStoreData(<?php echo $storeId; ?>);
    initEnvironmentUpload();
    initTagInput();
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
    // 新建模式
    initEnvironmentUpload();
    renderEnvironmentImages(); // 初始化显示
    initTagInput();
});
<?php endif; ?>

// 加载门店数据
function loadStoreData(id) {
    showLoading('加载门店数据...');
    
    fetch(`../api/store.php?action=detail&id=${id}`)
        .then(res => res.json())
        .then(res => {
            hideLoading();
            
            if (res.code !== 200) {
                alert('加载失败：' + res.message);
                return;
            }
            
            const data = res.data;
            
            // 填充表单字段
            document.getElementById('storeId').value = data.id || '';
            document.getElementById('storeName').value = data.name || '';
            document.getElementById('storeManager').value = data.manager || '';
            document.getElementById('storeManagerPhone').value = data.manager_phone || '';
            document.getElementById('storeCountry').value = data.country || '';
            document.getElementById('storeCity').value = data.city || '';
            document.getElementById('storeAddress').value = data.address || '';
            document.getElementById('storeAvatar').value = data.avatar || '';
            storeAvatarUrl = data.avatar || '';
            displayStoreAvatar(data.avatar || '');
            document.getElementById('storeDescription').value = data.description || '';
            
            // 加载经纬度坐标
            if (data.latitude && data.longitude) {
                document.getElementById('storeLatitude').value = parseFloat(data.latitude).toFixed(6);
                document.getElementById('storeLongitude').value = parseFloat(data.longitude).toFixed(6);
                document.getElementById('coordinateRow').style.display = 'grid';
            }
            
            // 加载门店标签
            if (data.tags) {
                let tagsArray = [];
                try {
                    tagsArray = JSON.parse(data.tags);
                } catch (e) {
                    tagsArray = data.tags.split(/[,,]/).map(t => t.trim()).filter(t => t);
                }
                storeTags = tagsArray;
                renderStoreTags();
            }
            
            // 加载环境图片
            if (data.environment_images) {
                try {
                    const images = typeof data.environment_images === 'string' ? 
                        JSON.parse(data.environment_images) : data.environment_images;
                    loadEnvironmentImages(images);
                } catch(e) {
                    console.error('加载环境图片失败:', e);
                    environmentImages = [];
                    renderEnvironmentImages();
                }
            } else {
                environmentImages = [];
                renderEnvironmentImages();
            }
        })
        .catch(err => {
            hideLoading();
            console.error('加载失败:', err);
            alert('网络错误：' + err.message);
        });
}

// 保存门店
function saveStore(event) {
    event.preventDefault();
    
    const storeId = document.getElementById('storeId').value;
    const isEditMode = storeId && storeId != '0' && storeId != '';
    
    // 验证必填字段
    const storeName = document.getElementById('storeName').value.trim();
    if (!storeName) {
        alert('请输入门店名称');
        document.getElementById('storeName').focus();
        return false;
    }
    
    const country = document.getElementById('storeCountry').value.trim();
    if (!country) {
        alert('请输入国家');
        document.getElementById('storeCountry').focus();
        return false;
    }
    
    const city = document.getElementById('storeCity').value.trim();
    if (!city) {
        alert('请输入城市');
        document.getElementById('storeCity').focus();
        return false;
    }
    
    const address = document.getElementById('storeAddress').value.trim();
    if (!address) {
        alert('请输入详细地址');
        document.getElementById('storeAddress').focus();
        return false;
    }
    
    const action = isEditMode ? 'update' : 'create';
    const actionText = isEditMode ? '更新' : '创建';
    
    // 构建数据对象（使用 JSON 格式）
    const latitude = document.getElementById('storeLatitude').value;
    const longitude = document.getElementById('storeLongitude').value;
    
    // 提取图片数据：新上传的提取 base64，已有的提取文件名
    const envImagesToSend = environmentImages.map(img => {
        // 如果是新上传的图片（有 data 字段且是 base64）
        if (img.data && img.data.startsWith('data:image')) {
            return {
                type: 'new',
                data: img.data,
                filename: img.filename || null
            };
        }
        // 如果是已有的图片（有 filename 字段）
        else if (img.filename) {
            return {
                type: 'existing',
                filename: img.filename
            };
        }
        // 兼容旧格式（URL 格式）
        else if (img.url) {
            return {
                type: 'existing',
                filename: img.url
            };
        }
        return img;
    });
    
    const data = {
        name: storeName,
        manager: document.getElementById('storeManager').value.trim(),
        manager_phone: document.getElementById('storeManagerPhone').value.trim(),
        country: country,
        city: city,
        address: address,
        latitude: latitude ? parseFloat(latitude) : null,
        longitude: longitude ? parseFloat(longitude) : null,
        avatar: document.getElementById('storeAvatar').value.trim(),
        description: document.getElementById('storeDescription').value.trim(),
        tags: document.getElementById('storeTags').value.trim(),
        environment_images: envImagesToSend
    };
    
    if (isEditMode) {
        data.id = parseInt(storeId);
    }
    
    showLoading('正在保存...');
    
    fetch(`../api/store.php?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.code === 200) {
            // 记录操作日志
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: isEditMode ? '编辑门店' : '新增门店',
                    detail: '门店 ID:' + storeId + '，名称:' + storeName
                })
            }).catch(err => console.error('日志记录失败:', err));
            
            alert('✅ 保存成功！');
            window.location.href = 'store.php';
        } else {
            alert('❌ 保存失败：' + res.message);
        }
    })
    .catch(err => {
        hideLoading();
        console.error('保存失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
    
    return false;
}

// 删除门店
function deleteStore() {
    const storeId = document.getElementById('storeId').value;
    
    if (!storeId) {
        alert('门店 ID 无效');
        return;
    }
    
    if (!confirm('确定要删除该门店吗？此操作不可恢复！')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', storeId);
    
    showLoading('正在删除...');
    
    fetch('../api/store.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        hideLoading();
        
        if (res.code === 200) {
            // 记录操作日志
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: '删除门店',
                    detail: '门店 ID:' + storeId
                })
            }).catch(err => console.error('日志记录失败:', err));
            
            alert('✅ 删除成功！');
            window.location.href = 'store.php';
        } else {
            alert('❌ 删除失败：' + res.message);
        }
    })
    .catch(err => {
        hideLoading();
        console.error('删除失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
}

// 门店头像管理
let storeAvatarUrl = '';

// 显示头像
function displayStoreAvatar(avatarUrl) {
    const preview = document.getElementById('avatarPreview');
    console.log('[displayStoreAvatar] 调用，URL:', avatarUrl);
    
    if (avatarUrl && avatarUrl.trim() !== '') {
        // 处理 URL 路径
        let finalUrl = avatarUrl;
        
        // 如果是完整 URL，直接使用
        if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
            finalUrl = avatarUrl;
        }
        // 如果是 /uploads/ 开头，使用动态路径
        else if (avatarUrl.startsWith('/uploads/')) {
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const projectPath = basePath.substring(0, basePath.lastIndexOf('/'));
            finalUrl = window.location.protocol + '//' + window.location.host + projectPath + avatarUrl;
        }
        
        console.log('[displayStoreAvatar] 最终 URL:', finalUrl);
        
        // 创建图片元素
        const img = document.createElement('img');
        img.src = finalUrl;
        img.alt = '门店头像';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.display = 'block';
        
        img.onload = function() {
            console.log('[displayStoreAvatar] 图片加载成功:', finalUrl);
        };
        
        img.onerror = function() {
            console.error('[displayStoreAvatar] 图片加载失败:', finalUrl);
            preview.innerHTML = '<div class="placeholder">🏪</div>';
        };
        
        preview.innerHTML = '';
        preview.appendChild(img);
        document.getElementById('removeAvatarBtn').style.display = 'inline-block';
    } else {
        console.log('[displayStoreAvatar] 无头像 URL，显示占位符');
        preview.innerHTML = '<div class="placeholder">🏪</div>';
        document.getElementById('removeAvatarBtn').style.display = 'none';
    }
}

// 处理头像上传
function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // 验证文件类型
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        alert('只支持 JPG、PNG、GIF 格式图片');
        return;
    }
    
    // 验证文件大小（5MB）
    if (file.size > 5 * 1024 * 1024) {
        alert('图片大小不能超过 5MB');
        return;
    }
    
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
        console.log('[头像上传响应]', res);
        
        // 兼容两种格式：{code: 200, ...} 或 {success: true, ...}
        if (res.code === 200 || res.success) {
            const imageUrl = res.data.url;
            console.log('头像上传成功，URL:', imageUrl);
            
            // 显示预览
            displayStoreAvatar(imageUrl);
            
            // 保存 URL
            document.getElementById('storeAvatar').value = imageUrl;
            storeAvatarUrl = imageUrl;
            
            alert('✅ 头像上传成功');
        } else {
            alert('❌ 上传失败：' + (res.message || '未知错误'));
        }
    })
    .catch(err => {
        console.error('上传失败:', err);
        alert('❌ 网络错误：' + err.message);
    });
    
    // 清空 input，允许重复选择同一文件
    event.target.value = '';
}

// 删除头像
function removeAvatar() {
    if (confirm('确定要删除门店头像吗？')) {
        document.getElementById('storeAvatar').value = '';
        storeAvatarUrl = '';
        displayStoreAvatar('');
    }
}

// 环境图片管理
let environmentImages = [];
const MAX_ENV_IMAGES = 20;

// 初始化环境图片上传
function initEnvironmentUpload() {
    const trigger = document.getElementById('environmentUploadTrigger');
    const input = document.getElementById('environmentUploadInput');
    
    // 点击触发上传
    trigger.addEventListener('click', function(e) {
        if (e.target !== input) {
            input.click();
        }
    });
    
    // 文件选择
    input.addEventListener('change', function(e) {
        handleFiles(e.target.files);
        input.value = ''; // 清空，允许重复选择同一文件
    });
    
    // 拖拽上传
    trigger.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#1890ff';
        this.style.background = '#e6f7ff';
    });
    
    trigger.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d9d9d9';
        this.style.background = '#fafafa';
    });
    
    trigger.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d9d9d9';
        this.style.background = '#fafafa';
        handleFiles(e.dataTransfer.files);
    });
}

// 处理上传的文件
function handleFiles(files) {
    if (environmentImages.length + files.length > MAX_ENV_IMAGES) {
        alert(`最多只能上传${MAX_ENV_IMAGES}张图片，当前已上传${environmentImages.length}张`);
        return;
    }
    
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    Array.from(files).forEach(file => {
        if (!validTypes.includes(file.type)) {
            alert(`不支持的文件格式：${file.name}，只支持 JPG、PNG、GIF`);
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert(`文件过大：${file.name}，单张图片不能超过 5MB`);
            return;
        }
        
        // 读取文件并添加到预览列表
        const reader = new FileReader();
        reader.onload = function(e) {
            // 压缩图片
            compressImage(e.target.result, file).then(compressedData => {
                const imageData = {
                    id: 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    data: compressedData, // base64
                    file: file,
                    filename: file.name, // 临时文件名，保存时会被服务器生成的文件名替换
                    type: 'new'
                };
                environmentImages.push(imageData);
                renderEnvironmentImages();
            });
        };
        reader.readAsDataURL(file);
    });
}

// 压缩图片
function compressImage(dataUrl, file) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // 最大尺寸 1920px
            const maxSize = 1920;
            let width = img.width;
            let height = img.height;
            
            if (width > maxSize || height > maxSize) {
                if (width > height) {
                    height = height * (maxSize / width);
                    width = maxSize;
                } else {
                    width = width * (maxSize / height);
                    height = maxSize;
                }
            }
            
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);
            
            // 压缩为 JPEG，质量 0.8
            resolve(canvas.toDataURL('image/jpeg', 0.8));
        };
        img.src = dataUrl;
    });
}

// 渲染环境图片列表
function renderEnvironmentImages() {
    const list = document.getElementById('environmentImagesList');
    const trigger = document.getElementById('environmentUploadTrigger');
    const countHint = document.getElementById('envImageCountHint');
    
    // 图片存储基础路径（用于生成完整 URL）
    const basePath = window.location.protocol + '//' + window.location.host;
    const projectPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const baseImagePath = basePath + projectPath.substring(0, projectPath.lastIndexOf('/')) + '/uploads/store_environments/';
    
    list.innerHTML = environmentImages.map((img, index) => {
        // 生成完整 URL（用于复制）
        const fullUrl = img.filename ? baseImagePath + img.filename : (img.url || img.data).split('?t=')[0];
        
        return `
        <div class="image-upload-item-wrapper" style="display: flex; flex-direction: column; gap: 6px;">
            <div class="image-upload-item" onclick="removeEnvironmentImage(${index})" style="margin: 0; cursor: pointer;">
                <img src="${img.data}" alt="环境照片" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9Ijc1IiB5PSI4MCIgZm9udC1zaXplPSIxNCIgZm9udC1mYW1pbHk9ImFyaWFsIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij7lm77nmbTorrDmlrk8L3RleHQ+PC9zdmc+'">
                <div class="delete-btn">
                    <span>🗑️</span>
                </div>
            </div>
            <div style="display: flex; gap: 4px; align-items: center;">
                <input type="text" value="${fullUrl}" readonly 
                    style="flex: 1; padding: 4px 8px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 11px; color: #666; background: #fafafa;"
                    onclick="this.select()">
                <button type="button" onclick="copyImageUrl('${fullUrl}', this)" 
                    style="padding: 4px 8px; border: 1px solid #1890ff; border-radius: 4px; background: #1890ff; color: white; font-size: 11px; cursor: pointer; white-space: nowrap;">
                    📋 复制
                </button>
            </div>
        </div>
    `}).join('');
    
    // 更新数量提示
    if (countHint) {
        countHint.textContent = `已上传 ${environmentImages.length} / ${MAX_ENV_IMAGES} 张`;
    } else {
        const hint = document.createElement('div');
        hint.id = 'envImageCountHint';
        hint.className = 'image-count-hint';
        hint.textContent = `已上传 ${environmentImages.length} / ${MAX_ENV_IMAGES} 张`;
        trigger.parentNode.insertBefore(hint, trigger.nextSibling);
    }
    
    // 达到上限时隐藏上传按钮
    if (environmentImages.length >= MAX_ENV_IMAGES) {
        trigger.style.display = 'none';
    } else {
        trigger.style.display = 'block';
    }
}

// 删除环境图片
function removeEnvironmentImage(index) {
    const img = environmentImages[index];
    
    if (confirm('确定要删除这张照片吗？')) {
        // 如果是已保存的图片（从数据库加载的），需要先删除服务器文件
        if (img.type === 'existing' && img.filename) {
            const storeId = document.getElementById('storeId').value;
            if (storeId && storeId != '0' && storeId != '') {
                // 调用 API 删除图片文件
                fetch('../api/store.php?action=delete_image', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        store_id: parseInt(storeId),
                        filename: img.filename
                    })
                }).then(res => res.json()).then(res => {
                    if (res.code === 200) {
                        console.log('图片文件已删除:', img.filename);
                    } else {
                        console.error('删除图片文件失败:', res.message);
                    }
                }).catch(err => {
                    console.error('删除图片文件错误:', err);
                });
            }
        }
        
        // 从数组中移除
        environmentImages.splice(index, 1);
        renderEnvironmentImages();
    }
}

// 加载环境图片
function loadEnvironmentImages(imageList) {
    if (!imageList || !Array.isArray(imageList)) {
        environmentImages = [];
        renderEnvironmentImages();
        return;
    }
    
    // 图片存储基础路径（相对路径，从 admin 目录访问）
    const baseImagePath = '../uploads/store_environments/';
    
    // 加载现有图片
    environmentImages = imageList.map((item, index) => {
        let filename = '';
        let imageUrl = '';
        
        // 如果是新格式（对象格式）
        if (typeof item === 'object' && item.filename) {
            filename = item.filename;
            imageUrl = baseImagePath + filename + '?t=' + Date.now();
        }
        // 如果是旧格式（字符串格式，直接是文件名）
        else if (typeof item === 'string') {
            // 提取文件名（去除路径）
            filename = item.substring(item.lastIndexOf('/') + 1);
            imageUrl = baseImagePath + filename + '?t=' + Date.now();
        }
        
        return {
            id: 'existing_' + index,
            data: imageUrl,
            filename: filename,
            type: 'existing'
        };
    });
    renderEnvironmentImages();
}

// 复制图片 URL 到剪贴板
function copyImageUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '✅ 已复制';
        btn.style.background = '#52c41a';
        btn.style.borderColor = '#52c41a';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '#1890ff';
            btn.style.borderColor = '#1890ff';
        }, 1500);
    }).catch(err => {
        alert('复制失败，请手动复制');
        console.error('复制失败:', err);
    });
}

// 显示加载提示
function showLoading(message) {
    const loading = document.createElement('div');
    loading.id = 'loadingOverlay';
    loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
    `;
    loading.innerHTML = `
        <div style="
            background: white;
            padding: 24px 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            text-align: center;
        ">
            <div style="
                width: 40px;
                height: 40px;
                border: 4px solid #f0f0f0;
                border-top-color: #1890ff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 16px;
            "></div>
            <div style="color: #262626; font-size: 15px; font-weight: 600;">${message}</div>
        </div>
    `;
    document.body.appendChild(loading);
}

// 隐藏加载提示
function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.remove();
    }
}

// ==================== 地图选点器功能 ====================

let mapSelector = null;
let mapMarker = null;

// 打开地图选点器
function openMapSelector() {
    const overlay = document.getElementById('mapSelectorOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        
        // 重置地址显示
        const addressDisplay = document.getElementById('mapAddressDisplay');
        if (addressDisplay) {
            addressDisplay.textContent = '💡 点击地图或拖拽标记，自动获取地址';
        }
        
        // 清空隐藏字段
        const addressHidden = document.getElementById('mapAddress');
        if (addressHidden) {
            addressHidden.value = '';
        }
        
        // 延迟初始化地图，确保容器已显示
        setTimeout(function() {
            // 优先使用已有坐标，否则使用默认值
            const lat = document.getElementById('storeLatitude').value;
            const lng = document.getElementById('storeLongitude').value;
            
            if (lat && lng) {
                // 已有坐标，定位到该位置
                initMapSelector(parseFloat(lat), parseFloat(lng));
            } else {
                // 使用默认坐标（北京）
                initMapSelector(39.9042, 116.4074);
            }
        }, 100);
    }
}

// 关闭地图选点器
function closeMapSelector() {
    const overlay = document.getElementById('mapSelectorOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// 地图加载状态
let mapLoading = false;
let mapInitialized = false;
let geocoder = null; // 地理编码器

// 高德地图配置
const AMAP_KEY = '5f3decaa001f5009142ed8e29f8e6699'; // 高德地图 Web 端 JS API Key
const AMAP_SECURITY_CODE = '4062a22b990bc5a8df8bad95761bce4e'; // 高德地图安全密钥

// 初始化地图选点器
function initMapSelector(lat = 39.9042, lng = 116.4074) {
    const mapContainer = document.getElementById('mapContainer');
    if (!mapContainer) {
        console.error('地图容器未找到');
        return;
    }
    
    // 如果已经初始化过，只需更新视图
    if (mapInitialized && mapSelector) {
        mapSelector.setZoomAndCenter(15, [lng, lat]);
        if (mapMarker) {
            mapMarker.setPosition(new AMap.LngLat(lng, lat));
        }
        updateMapCoordinateDisplay(lat, lng);
        return;
    }
    
    // 防止重复加载
    if (mapLoading) {
        return;
    }
    
    mapLoading = true;
    
    // 显示加载提示
    const mapInfo = document.getElementById('mapCoordinateDisplay');
    if (mapInfo) {
        mapInfo.textContent = '🗺️ 地图加载中...';
    }
    
    // 检查 Key 是否配置
    if (AMAP_KEY === 'YOUR_AMAP_KEY') {
        mapLoading = false;
        if (mapInfo) {
            mapInfo.textContent = '⚠️ 请先配置高德地图 Key';
        }
        alert('⚠️ 请先在 store_edit.php 文件中配置高德地图 API Key 和安全密钥！\n\n配置位置：\nconst AMAP_KEY = "你的 Key";\nconst AMAP_SECURITY_CODE = "你的安全密钥";');
        return;
    }
    
    // 使用高德地图
    if (typeof AMap === 'undefined') {
        // 设置安全密钥（v3.0+ 需要）
        window._AMapSecurityConfig = {
            securityJsCode: AMAP_SECURITY_CODE
        };
        
        // 加载高德地图 JS
        const script = document.createElement('script');
        script.src = 'https://webapi.amap.com/maps?v=2.0&key=' + AMAP_KEY;
        script.onload = function() {
            console.log('高德地图 SDK 加载成功');
            createMap(lat, lng);
        };
        script.onerror = function() {
            mapLoading = false;
            console.error('高德地图 SDK 加载失败');
            if (mapInfo) {
                mapInfo.textContent = '❌ 地图加载失败，请检查 Key 和网络连接';
            }
            alert('地图加载失败，请检查：\n1. API Key 是否正确\n2. 网络连接是否正常\n3. Key 是否已启用 Web 端 JS API');
        };
        document.head.appendChild(script);
    } else {
        console.log('高德地图 SDK 已加载，创建地图');
        createMap(lat, lng);
    }
}

// 创建地图
function createMap(lat, lng) {
    try {
        const mapContainer = document.getElementById('mapContainer');
        if (!mapContainer) {
            console.error('地图容器不存在');
            return;
        }
        
        // 初始化地图
        mapSelector = new AMap.Map('mapContainer', {
            zoom: 15,
            center: [lng, lat],
            viewMode: '2D',
            mapStyle: 'amap://styles/normal'
        });
        
        // 添加标记（可拖拽）
        mapMarker = new AMap.Marker({
            position: [lng, lat],
            map: mapSelector,
            draggable: true,
            cursor: 'pointer'
        });
        
        // 点击地图移动标记
        mapSelector.on('click', function(e) {
            mapMarker.setPosition(e.lnglat);
            updateMapCoordinateDisplay(e.lnglat.lat, e.lnglat.lng);
        });
        
        // 拖拽标记更新坐标
        mapMarker.on('dragend', function(e) {
            const lnglat = e.target.getLnglat();
            updateMapCoordinateDisplay(lnglat.lat, lnglat.lng);
        });
        
        // 地图加载完成
        mapSelector.on('complete', function() {
            mapLoading = false;
            mapInitialized = true;
            console.log('地图初始化完成');
            
            // 强制刷新地图尺寸
            setTimeout(function() {
                if (mapSelector) {
                    mapSelector.resize();
                }
            }, 200);
        });
        
        // 初始化显示坐标
        updateMapCoordinateDisplay(lat, lng);
        
        console.log('✅ 高德地图创建成功');
        
    } catch (err) {
        mapLoading = false;
        console.error('地图创建失败:', err);
        const mapInfo = document.getElementById('mapCoordinateDisplay');
        if (mapInfo) {
            mapInfo.textContent = '❌ 地图创建失败：' + err.message;
        }
        alert('地图创建失败：' + err.message);
    }
}

// 更新地图坐标显示（带逆地理编码）
function updateMapCoordinateDisplay(lat, lng) {
    // 更新显示框
    const latDisplay = document.getElementById('mapLatDisplay');
    const lngDisplay = document.getElementById('mapLngDisplay');
    if (latDisplay && lngDisplay) {
        latDisplay.value = lat.toFixed(6);
        lngDisplay.value = lng.toFixed(6);
    }
    
    // 更新隐藏字段
    const latInput = document.getElementById('mapLatitude');
    const lngInput = document.getElementById('mapLongitude');
    if (latInput && lngInput) {
        latInput.value = lat;
        lngInput.value = lng;
    }
    
    // 逆地理编码：获取文字地址
    getAddressFromCoordinates(lng, lat);
}

// 逆地理编码：将坐标转换为地址
function getAddressFromCoordinates(lng, lat) {
    const addressDisplay = document.getElementById('mapAddressDisplay');
    const addressHidden = document.getElementById('mapAddress');
    
    if (addressDisplay) {
        addressDisplay.textContent = '📍 正在获取地址...';
    }
    
    // 使用 AMap.Geocoder 获取地址
    AMap.plugin('AMap.Geocoder', function() {
        const geocoder = new AMap.Geocoder({
            city: '全国' // 可选，限定城市范围
        });
        
        geocoder.getAddress([lng, lat], function(status, result) {
            if (status === 'complete' && result.regeocode) {
                const address = result.regeocode.formattedAddress;
                console.log('地址解析成功:', address);
                
                if (addressDisplay) {
                    addressDisplay.textContent = '✅ ' + address;
                }
                
                // 保存到隐藏字段
                if (addressHidden) {
                    addressHidden.value = address;
                }
            } else {
                console.warn('地址解析失败:', status);
                if (addressDisplay) {
                    addressDisplay.textContent = '⚠️ 地址解析失败，请手动输入';
                }
                if (addressHidden) {
                    addressHidden.value = '';
                }
            }
        });
    });
}

// 确认选择坐标
function confirmMapSelection() {
    const lat = document.getElementById('mapLatitude').value;
    const lng = document.getElementById('mapLongitude').value;
    const address = document.getElementById('mapAddress').value;
    
    if (!lat || !lng) {
        alert('请选择位置');
        return;
    }
    
    // 保存坐标到表单字段（显示6位小数到只读输入框）
    document.getElementById('storeLatitude').value = parseFloat(lat).toFixed(6);
    document.getElementById('storeLongitude').value = parseFloat(lng).toFixed(6);
    
    // 保存地址到详细地址字段
    if (address) {
        document.getElementById('storeAddress').value = address;
    }
    
    // 显示坐标行
    document.getElementById('coordinateRow').style.display = 'grid';
    
    closeMapSelector();
    
    const msg = address 
        ? '✅ 位置已保存\n\n📍 地址：' + address + '\n📌 纬度：' + parseFloat(lat).toFixed(6) + '\n📌 经度：' + parseFloat(lng).toFixed(6)
        : '✅ 坐标已保存\n\n📌 纬度：' + parseFloat(lat).toFixed(6) + '\n📌 经度：' + parseFloat(lng).toFixed(6);
    alert(msg);
}

// 清除坐标
function clearCoordinates() {
    if (confirm('确定要清除已选择的坐标吗？')) {
        document.getElementById('storeLatitude').value = '';
        document.getElementById('storeLongitude').value = '';
        document.getElementById('coordinateRow').style.display = 'none';
    }
}

// ==================== 地图搜索功能 ====================

// 搜索地址
function searchAddress() {
    const keyword = document.getElementById('mapSearchInput').value.trim();
    
    if (!keyword) {
        alert('请输入搜索关键词');
        return;
    }
    
    const resultsDiv = document.getElementById('mapSearchResults');
    resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #8c8c8c;">🔍 搜索中...</div>';
    resultsDiv.style.display = 'block';
    
    // 使用高德地图的地点搜索
    AMap.plugin('AMap.Geocoder', function() {
        const geocoder = new AMap.Geocoder({
            city: '全国'
        });
        
        geocoder.getLocation(keyword, function(status, result) {
            if (status === 'complete' && result.geocodes && result.geocodes.length > 0) {
                displaySearchResults(result.geocodes);
            } else {
                resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px; color: #ff4d4f;">❌ 未找到相关地址</div>';
            }
        });
    });
}

// 显示搜索结果
function displaySearchResults(geocodes) {
    const resultsDiv = document.getElementById('mapSearchResults');
    
    if (!geocodes || geocodes.length === 0) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
    
    geocodes.slice(0, 5).forEach((geo, index) => {
        const address = geo.formattedAddress;
        const location = geo.location;
        
        html += `
            <div onclick="selectSearchResult(${location.lat}, ${location.lng}, '${address.replace(/'/g, "\\'")}')" 
                style="padding: 12px 16px; background: white; border: 1px solid #e8e8e8; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-size: 13px; line-height: 1.5;"
                onmouseover="this.style.background='#f0f5ff'; this.style.borderColor='#1890ff'"
                onmouseout="this.style.background='white'; this.style.borderColor='#e8e8e8'">
                <div style="display: flex; align-items: start; gap: 8px;">
                    <span style="color: #1890ff; font-weight: 600;">${index + 1}.</span>
                    <div style="flex: 1;">
                        <div style="color: #262626; font-weight: 500; margin-bottom: 4px;">${address}</div>
                        <div style="color: #8c8c8c; font-size: 12px;">
                            📍 纬度：${location.lat.toFixed(6)} | 经度：${location.lng.toFixed(6)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    resultsDiv.innerHTML = html;
}

// 选择搜索结果
function selectSearchResult(lat, lng, address) {
    console.log('选择搜索结果:', { lat, lng, address });
    
    // 移动地图标记
    if (mapSelector && mapMarker) {
        mapMarker.setPosition(new AMap.LngLat(lng, lat));
        mapSelector.setZoomAndCenter(15, [lng, lat]);
        
        // 更新坐标显示
        updateMapCoordinateDisplay(lat, lng);
        
        // 清空搜索框和结果
        document.getElementById('mapSearchInput').value = '';
        document.getElementById('mapSearchResults').style.display = 'none';
        document.getElementById('mapSearchResults').innerHTML = '';
        
        alert('✅ 已定位到：' + address);
    } else {
        alert('❌ 地图未初始化，请先打开地图选点器');
    }
}

// 页面加载时如果有坐标，显示提示
function initCoordinateDisplay() {
    const lat = document.getElementById('storeLatitude').value;
    const lng = document.getElementById('storeLongitude').value;
    
    if (lat && lng) {
        document.getElementById('coordinateRow').style.display = 'grid';
    }
}

// 在加载门店数据后初始化坐标显示
const originalLoadStoreData = window.loadStoreData;
if (originalLoadStoreData) {
    window.loadStoreData = function(id) {
        originalLoadStoreData(id);
        // 延迟执行，确保数据已加载
        setTimeout(initCoordinateDisplay, 500);
    };
} else {
    // 如果是新建模式，直接初始化
    document.addEventListener('DOMContentLoaded', initCoordinateDisplay);
}
</script>

<!-- 地图选点器弹窗 -->
<div id="mapSelectorOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 1000px; max-height: 90vh; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
        <div style="padding: 20px 24px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 18px; color: #262626;">📍 选择门店位置</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="confirmMapSelection()" class="btn btn-primary" style="padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white;">✅ 确定</button>
                <button onclick="closeMapSelector()" style="background: none; border: none; font-size: 24px; color: #8c8c8c; cursor: pointer; line-height: 1;">×</button>
            </div>
        </div>
        
        <!-- 地址搜索框 -->
        <div style="padding: 16px 24px; background: #fafafa; border-bottom: 1px solid #f0f0f0;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="mapSearchInput" 
                    placeholder="🔍 输入地址搜索，例如：北京市朝阳区三里屯" 
                    style="flex: 1; padding: 10px 16px; border: 2px solid #d9d9d9; border-radius: 8px; font-size: 14px; outline: none; transition: all 0.3s;"
                    onkeypress="if(event.keyCode===13) searchAddress()">
                <button onclick="searchAddress()" 
                    style="padding: 10px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; white-space: nowrap;">
                    🔍 搜索
                </button>
            </div>
            <!-- 搜索结果列表 -->
            <div id="mapSearchResults" style="margin-top: 10px; max-height: 200px; overflow-y: auto; display: none;"></div>
        </div>
        
        <!-- 坐标显示区域 -->
        <div style="padding: 16px 24px; background: #fafafa; border-bottom: 1px solid #f0f0f0;">
            <div style="display: flex; gap: 12px; margin-bottom: 10px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: #262626;">
                        纬度
                    </label>
                    <input type="text" id="mapLatDisplay" readonly 
                        style="width: 100%; padding: 8px 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; background: #fafafa; color: #1890ff; font-weight: 600; box-sizing: border-box;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: #262626;">
                        经度
                    </label>
                    <input type="text" id="mapLngDisplay" readonly 
                        style="width: 100%; padding: 8px 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; background: #fafafa; color: #1890ff; font-weight: 600; box-sizing: border-box;">
                </div>
            </div>
            <p id="mapAddressDisplay" style="margin: 0; font-size: 14px; color: #8c8c8c; line-height: 1.6;">
                💡 点击地图或拖拽标记，自动获取地址
            </p>
            <input type="hidden" id="mapLatitude" value="">
            <input type="hidden" id="mapLongitude" value="">
            <input type="hidden" id="mapAddress" value="">
        </div>
        
        <!-- 地图容器 -->
        <div id="mapContainer" style="width: 100%; height: 500px;"></div>
    </div>
</div>

</body>
</html>
