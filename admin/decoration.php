<?php
/**
 * 程序装修页面 - 界面 customization
 */
require_once __DIR__ . '/includes/header.php';

$pageTitle = '程序装修';
$currentPage = 'decoration';

// 处理保存配置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_theme') {
        // TODO: 保存主题配置
        $success = true;
        $message = '装修配置保存成功！';
    }
}

// 加载当前装修配置
$decoration = [
    'theme_color' => '#1890ff',
    'theme_gradient' => 'linear-gradient(135deg, #1890ff 0%, #40a9ff 100%)',
    'logo_type' => 'text',
    'logo_text' => '🚀',
    'sidebar_theme' => 'dark',
    'header_theme' => 'light',
    'font_size' => '14',
    'layout_width' => 'fluid'
];
?>

<style>
.decoration-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.preview-section {
    background: white;
    border-radius: 12px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
}

.section-icon {
    font-size: 24px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    border-radius: 12px;
    color: white;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
}

.section-desc {
    font-size: 13px;
    color: #8c8c8c;
    margin-top: 4px;
}

.preview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.preview-card {
    border: 2px solid #f0f0f0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.preview-card:hover {
    border-color: #1890ff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.preview-card.selected {
    border-color: #1890ff;
    background: #f0f5ff;
}

.preview-thumbnail {
    height: 150px;
    background: #fafafa;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    overflow: hidden;
}

.preview-name {
    font-size: 15px;
    font-weight: 600;
    color: #262626;
    margin-bottom: 4px;
}

.preview-desc {
    font-size: 12px;
    color: #8c8c8c;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 14px;
    color: #262626;
    font-weight: 600;
}

.color-picker-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    cursor: pointer;
    padding: 2px;
}

.color-input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    font-size: 14px;
}

.form-input, .form-select {
    padding: 10px 14px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
}

.form-input:focus, .form-select:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.form-hint {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 4px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
    margin-top: 24px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
}

.btn-default {
    background: #f5f5f5;
    color: #262626;
}

.btn-default:hover {
    background: #e8e8e8;
}

.theme-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.theme-option {
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.theme-option:hover {
    border-color: #1890ff;
}

.theme-option.selected {
    border-color: #1890ff;
    background: #f0f5ff;
}

.theme-preview {
    height: 60px;
    border-radius: 6px;
    margin-bottom: 12px;
}

.theme-name {
    font-size: 13px;
    font-weight: 600;
    color: #262626;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #f6ffed;
    border: 1px solid #b7eb8f;
    color: #52c41a;
}

.live-preview {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 20px;
    margin-top: 16px;
}

.preview-sidebar {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 8px;
    padding: 16px;
    color: white;
    margin-bottom: 12px;
}

.preview-header {
    background: white;
    border-radius: 8px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* 首页背景图 */
.bg-upload-area {
    border: 2px dashed #d9d9d9;
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #fafafa;
    margin-bottom: 24px;
}
.bg-upload-area:hover {
    border-color: #1890ff;
    background: #f0f5ff;
}
.bg-upload-area.dragover {
    border-color: #1890ff;
    background: #e6f7ff;
}
.bg-upload-area .upload-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.bg-upload-area .upload-text {
    font-size: 16px;
    color: #595959;
    font-weight: 600;
}
.bg-upload-area .upload-hint {
    font-size: 13px;
    color: #8c8c8c;
    margin-top: 8px;
}
.bg-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}
.bg-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 3px solid #f0f0f0;
    transition: all 0.3s;
    background: #f5f5f5;
    aspect-ratio: 16 / 9;
    cursor: pointer;
}
.bg-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.bg-card.active {
    border-color: #1890ff;
    box-shadow: 0 0 0 3px rgba(24,144,255,0.2);
}
.bg-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.bg-card .bg-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    opacity: 0;
    transition: opacity 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.bg-card:hover .bg-overlay {
    opacity: 1;
}
.bg-card .bg-overlay button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.bg-card .bg-select-btn {
    background: #1890ff;
    color: white;
}
.bg-card .bg-select-btn:hover {
    background: #096dd9;
}
.bg-card .bg-delete-btn {
    background: #ff4d4f;
    color: white;
}
.bg-card .bg-delete-btn:hover {
    background: #cf1322;
}
.bg-card .bg-active-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #1890ff;
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.bg-empty {
    text-align: center;
    padding: 40px;
    color: #8c8c8c;
}
.bg-empty .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
</style>

<div class="decoration-container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <span>✅</span>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>
    
    <!-- 主题选择 -->
    <div class="preview-section">
        <div class="section-header">
            <div class="section-icon">🎨</div>
            <div>
                <div class="section-title">主题配色</div>
                <div class="section-desc">选择系统主题颜色</div>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_theme">
            
            <div class="theme-options">
                <div class="theme-option selected" onclick="selectTheme(this, 'default')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);"></div>
                    <div class="theme-name">默认紫蓝</div>
                </div>
                <div class="theme-option" onclick="selectTheme(this, 'blue')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                    <div class="theme-name">清新蓝</div>
                </div>
                <div class="theme-option" onclick="selectTheme(this, 'green')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);"></div>
                    <div class="theme-name">活力绿</div>
                </div>
                <div class="theme-option" onclick="selectTheme(this, 'orange')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);"></div>
                    <div class="theme-name">温暖橙</div>
                </div>
                <div class="theme-option" onclick="selectTheme(this, 'red')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%);"></div>
                    <div class="theme-name">热情红</div>
                </div>
                <div class="theme-option" onclick="selectTheme(this, 'dark')">
                    <div class="theme-preview" style="background: linear-gradient(135deg, #434343 0%, #000000 100%);"></div>
                    <div class="theme-name">经典黑</div>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 24px;">
                <label class="form-label">自定义主题色</label>
                <div class="color-picker-wrapper">
                    <input type="color" class="color-picker" id="themeColorPicker" value="<?php echo $decoration['theme_color']; ?>">
                    <input type="text" class="form-input" id="themeColorInput" value="<?php echo $decoration['theme_color']; ?>" style="flex: 1;">
                </div>
                <div class="form-hint">选择自定义主题颜色</div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 预览</button>
                <button type="submit" class="btn btn-primary">💾 保存主题</button>
            </div>
        </form>
    </div>
    
    <!-- 布局设置 -->
    <div class="preview-section">
        <div class="section-header">
            <div class="section-icon">📐</div>
            <div>
                <div class="section-title">布局设置</div>
                <div class="section-desc">界面布局相关配置</div>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_layout">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">侧边栏主题</label>
                    <select class="form-select" name="sidebar_theme">
                        <option value="dark" <?php echo $decoration['sidebar_theme'] === 'dark' ? 'selected' : ''; ?>>深色主题</option>
                        <option value="light" <?php echo $decoration['sidebar_theme'] === 'light' ? 'selected' : ''; ?>>浅色主题</option>
                        <option value="blue" <?php echo $decoration['sidebar_theme'] === 'blue' ? 'selected' : ''; ?>>蓝色主题</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">顶部栏主题</label>
                    <select class="form-select" name="header_theme">
                        <option value="light" <?php echo $decoration['header_theme'] === 'light' ? 'selected' : ''; ?>>浅色主题</option>
                        <option value="dark" <?php echo $decoration['header_theme'] === 'dark' ? 'selected' : ''; ?>>深色主题</option>
                        <option value="gradient" <?php echo $decoration['header_theme'] === 'gradient' ? 'selected' : ''; ?>>渐变主题</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">字体大小 (px)</label>
                    <input type="number" class="form-input" name="font_size" value="<?php echo $decoration['font_size']; ?>" min="12" max="18">
                </div>
                
                <div class="form-group">
                    <label class="form-label">页面宽度</label>
                    <select class="form-select" name="layout_width">
                        <option value="fluid" <?php echo $decoration['layout_width'] === 'fluid' ? 'selected' : ''; ?>>流式布局</option>
                        <option value="boxed">固定宽度</option>
                        <option value="wide">宽屏模式</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存布局</button>
            </div>
        </form>
    </div>
    
    <!-- Logo 设置 -->
    <div class="preview-section">
        <div class="section-header">
            <div class="section-icon">🏷️</div>
            <div>
                <div class="section-title">Logo 设置</div>
                <div class="section-desc">系统 Logo 配置</div>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_logo">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Logo 类型</label>
                    <select class="form-select" name="logo_type" id="logoType" onchange="toggleLogoOptions()">
                        <option value="text" <?php echo $decoration['logo_type'] === 'text' ? 'selected' : ''; ?>>文字 Logo</option>
                        <option value="image" <?php echo $decoration['logo_type'] === 'image' ? 'selected' : ''; ?>>图片 Logo</option>
                        <option value="emoji" <?php echo $decoration['logo_type'] === 'emoji' ? 'selected' : ''; ?>>Emoji Logo</option>
                    </select>
                </div>
                
                <div class="form-group" id="logoTextGroup">
                    <label class="form-label">Logo 文字</label>
                    <input type="text" class="form-input" name="logo_text" value="<?php echo htmlspecialchars($decoration['logo_text']); ?>">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">实时预览</label>
                    <div class="live-preview">
                        <div class="preview-sidebar">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                                <div style="font-size: 32px;"><?php echo htmlspecialchars($decoration['logo_text']); ?></div>
                                <div style="font-size: 16px; font-weight: 700;">青园营地后台管理系统</div>
                            </div>
                        </div>
                        <div class="preview-header">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="font-size: 24px;"><?php echo htmlspecialchars($decoration['logo_text']); ?></div>
                                <div style="font-size: 14px; color: #666;">首页 / 个人中心</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 预览</button>
                <button type="submit" class="btn btn-primary">💾 保存 Logo</button>
            </div>
        </form>
    </div>

    <!-- 首页背景图 -->
    <div class="preview-section">
        <div class="section-header">
            <div class="section-icon">🖼️</div>
            <div>
                <div class="section-title">首页背景图</div>
                <div class="section-desc">设置小程序首页顶部背景图，支持上传多张图片并选择一张为当前背景</div>
            </div>
        </div>

        <div class="bg-upload-area" id="bgUploadArea">
            <div class="upload-icon">📤</div>
            <div class="upload-text">点击或拖拽上传图片</div>
            <div class="upload-hint">支持 JPG / PNG / GIF / WebP，单张不超过 10MB</div>
            <input type="file" id="bgFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" multiple>
        </div>

        <div id="bgLoading" style="display:none; text-align:center; padding:24px;">
            <div style="font-size:32px; margin-bottom:8px;">⏳</div>
            <div style="color:#8c8c8c;">上传中，请稍候…</div>
        </div>

        <div id="bgGallery" class="bg-gallery">
            <div class="bg-empty" id="bgEmpty">
                <div class="empty-icon">🏞️</div>
                <div>暂无背景图，请上传</div>
            </div>
        </div>
    </div>
</div>

<script>
// 颜色选择器同步
const colorPicker = document.getElementById('themeColorPicker');
const colorInput = document.getElementById('themeColorInput');

colorPicker.addEventListener('input', function() {
    colorInput.value = this.value;
});

colorInput.addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        colorPicker.value = this.value;
    }
});

// 主题选择
function selectTheme(element, themeName) {
    document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    
    // 更新颜色选择器
    const gradient = element.querySelector('.theme-preview').style.background;
    const color = extractColorFromGradient(gradient);
    if (color) {
        colorPicker.value = color;
        colorInput.value = color;
    }
}

function extractColorFromGradient(gradient) {
    const match = gradient.match(/#([0-9A-Fa-f]{6})/);
    return match ? '#' + match[1] : null;
}

// Logo 选项切换
function toggleLogoOptions() {
    const logoType = document.getElementById('logoType').value;
    const logoTextGroup = document.getElementById('logoTextGroup');
    
    if (logoType === 'text' || logoType === 'emoji') {
        logoTextGroup.style.display = 'flex';
    } else {
        logoTextGroup.style.display = 'none';
    }
}

// 实时更新预览
document.querySelector('input[name="logo_text"]').addEventListener('input', function(e) {
    const value = e.target.value;
    document.querySelectorAll('.live-preview .preview-sidebar div:first-child, .live-preview .preview-header div:first-child')
        .forEach(el => el.textContent = value || '🚀');
});

/* ========== 首页背景图管理 ========== */
const bgUploadArea = document.getElementById('bgUploadArea');
const bgFileInput = document.getElementById('bgFileInput');
const bgLoading = document.getElementById('bgLoading');
const bgGallery = document.getElementById('bgGallery');
const bgEmpty = document.getElementById('bgEmpty');

// 点击上传
bgUploadArea.addEventListener('click', () => bgFileInput.click());

// 拖拽上传
bgUploadArea.addEventListener('dragover', e => {
    e.preventDefault();
    bgUploadArea.classList.add('dragover');
});
bgUploadArea.addEventListener('dragleave', () => {
    bgUploadArea.classList.remove('dragover');
});
bgUploadArea.addEventListener('drop', e => {
    e.preventDefault();
    bgUploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        uploadBgFile(e.dataTransfer.files[0]);
    }
});

// 选择文件上传
bgFileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        uploadBgFile(this.files[0]);
    }
});

function uploadBgFile(file) {
    if (!file) return;
    if (!file.type.match(/^image\//)) {
        alert('请选择图片文件');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('图片大小不能超过 10MB');
        return;
    }

    bgLoading.style.display = 'block';
    bgUploadArea.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('file', file);

    fetch('api/decoration_bg.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadBgImages();
        } else {
            alert('上传失败：' + (res.message || '未知错误'));
        }
    })
    .catch(err => {
        alert('上传失败：网络错误');
        console.error(err);
    })
    .finally(() => {
        bgLoading.style.display = 'none';
        bgUploadArea.style.display = 'block';
        bgFileInput.value = '';
    });
}

function loadBgImages() {
    fetch('api/decoration_bg.php?action=list')
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;
        renderBgGallery(res.data || []);
    });
}

function renderBgGallery(items) {
    bgGallery.innerHTML = '';
    if (items.length === 0) {
        bgGallery.appendChild(bgEmpty);
        return;
    }
    items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'bg-card' + (item.is_active == 1 ? ' active' : '');
        card.innerHTML = `
            ${item.is_active == 1 ? '<div class="bg-active-badge">✅ 当前背景</div>' : ''}
            <img src="${item.image_url}" alt="背景图" loading="lazy">
            <div class="bg-overlay">
                ${item.is_active != 1 ? `<button class="bg-select-btn" onclick="setActiveBg(${item.id})">✅ 设为首页图</button>` : ''}
                <button class="bg-delete-btn" onclick="deleteBg(${item.id})">🗑️ 删除</button>
            </div>
        `;
        bgGallery.appendChild(card);
    });
}

function setActiveBg(id) {
    const formData = new FormData();
    formData.append('action', 'set_active');
    formData.append('id', id);
    fetch('api/decoration_bg.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadBgImages();
        } else {
            alert('设置失败：' + (res.message || '未知错误'));
        }
    });
}

function deleteBg(id) {
    if (!confirm('确定要删除这张背景图吗？')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    fetch('api/decoration_bg.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadBgImages();
        } else {
            alert('删除失败：' + (res.message || '未知错误'));
        }
    });
}

// 页面加载后加载已有背景图
loadBgImages();
</script>

<?php require_once __DIR__ . '/includes/footer.php';
