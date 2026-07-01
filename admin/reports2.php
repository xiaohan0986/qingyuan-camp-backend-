<?php
$currentPage = 'customer';
$pageTitle = '客户信息';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 加载动画 -->
<div class="loading-backdrop" id="loadingBackdrop"></div>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text" id="loadingText">正在加载数据...</div>
    <div class="loading-steps" id="loadingSteps">
        <div class="step" id="step-init"><span class="icon">⏳</span> 初始化...</div>
        <div class="step" id="step-data"><span class="icon">⏳</span> 加载数据...</div>
        <div class="step" id="step-complete"><span class="icon">⏳</span> 完成</div>
    </div>
</div>

<style>
/* 加载动画 */
.loading-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9998;
    transition: opacity 0.3s ease;
}

.loading-backdrop.hidden {
    opacity: 0;
    pointer-events: none;
}

.loading-overlay {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80%;
    max-width: 600px;
    background: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.3s ease;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.2);
}

.loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

.loading-spinner {
    width: 64px;
    height: 64px;
    border: 4px solid #f0f0f0;
    border-top: 4px solid #1890ff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 20px;
    font-size: 16px;
    color: #262626;
    font-weight: 500;
}

.loading-steps {
    margin-top: 12px;
    font-size: 13px;
    color: #8c8c8c;
}

.loading-steps .step {
    margin: 4px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.loading-steps .step.completed {
    color: #52c41a;
}

.loading-steps .step .icon {
    width: 16px;
    height: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
// 显示加载状态
function showLoading(text) {
    const overlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    if (loadingText) loadingText.textContent = text;
    if (overlay) overlay.classList.remove('hidden');
}

// 隐藏加载状态
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    const backdrop = document.getElementById('loadingBackdrop');
    
    if (overlay) {
        overlay.classList.add('hidden');
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
    }
    
    if (backdrop) {
        backdrop.classList.add('hidden');
        setTimeout(() => {
            backdrop.style.display = 'none';
        }, 300);
    }
}

// 标记步骤完成
function markStepComplete(stepId) {
    const step = document.getElementById(stepId);
    if (step) {
        step.classList.add('completed');
        step.querySelector('.icon').textContent = '✅';
    }
}

// 标记步骤加载中
function markStepLoading(stepId) {
    const step = document.getElementById(stepId);
    if (step) {
        step.classList.remove('completed');
        step.querySelector('.icon').textContent = '✅';
    }
}
</script>

<?php
// 获取客户 ID（如果有则是编辑模式）
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $customerId > 0;
?>

<style>
.edit-container {
    padding: 24px;
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

/* 材料标签样式 */
.material-tag {
    display: inline-block;
    padding: 8px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    background: #fff;
    color: #666;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.material-tag:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.material-tag.selected {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    border-color: #1890ff;
    color: #fff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* 进度项样式 */
.progress-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 12px;
}

.progress-time {
    font-size: 13px;
    color: #8c8c8c;
    white-space: nowrap;
    min-width: 160px;
}

.progress-text {
    font-size: 14px;
    color: #262626;
    flex: 1;
    line-height: 1.6;
}

/* 进度项中的材料显示 - 同一行 */
.progress-materials {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
}

.materials-label {
    font-size: 13px;
    color: #8c8c8c;
    white-space: nowrap;
}

.material-badge {
    display: inline-block;
    padding: 4px 12px;
    margin: 4px;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: #fff;
    border-radius: 12px;
    font-size: 12px;
}

.material-badge.uploaded {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
    position: relative;
    padding-left: 24px;
}

.material-badge.uploaded::before {
    content: '✓';
    position: absolute;
    left: 6px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
}

.material-badge.pending {
    background: #f0f0f0;
    color: #8c8c8c;
}

.materials-time {
    font-size: 11px;
    color: #8c8c8c;
    margin-left: 8px;
}

/* 材料预览图片 */
.material-preview-link {
    display: inline-block;
    margin-left: 4px;
    vertical-align: middle;
}

.material-preview-thumb {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: transform 0.2s;
}

.material-preview-thumb:hover {
    transform: scale(1.2);
    border-color: #fff;
}

.material-file-link {
    display: inline-block;
    margin-left: 4px;
    text-decoration: none;
    font-size: 14px;
}

.edit-form {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
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
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #262626;
    font-weight: 600;
    font-size: 14px;
}

.form-group label.required::after {
    content: ' *';
    color: #ff4d4f;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
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
    resize: vertical;
    min-height: 120px;
}

.form-group .hint {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 6px;
}

/* 用户搜索结果列表 */
#userSearchResults {
    display: none;
    max-height: 300px;
    overflow-y: auto;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    background: white;
    margin-top: 8px;
}

.user-result-item {
    padding: 11px 15px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.user-result-item:last-child {
    border-bottom: none;
}

.user-result-item:hover {
    background: #f9fafb;
}

.user-result-info {
    flex: 1;
}

.user-result-name {
    font-weight: 500;
    color: #111827;
    font-size: 14px;
    margin-bottom: 4px;
}

.user-result-meta {
    font-size: 12px;
    color: #6b7280;
}

.user-result-select {
    background: #2563eb;
    color: white;
    border: none;
    padding: 6px 13px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    margin-left: 12px;
}

.user-result-select:hover {
    background: #1d4ed8;
}

.user-no-results {
    padding: 22px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

.form-section {
    margin-bottom: 44px;
}

.form-section:last-child {
    margin-bottom: 0;
}

.section-title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 24px 0;
    padding: 0 0 10px 0;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 7px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px 28px;
}

.form-row.full-width {
    grid-template-columns: 1fr;
}

.form-row.two-columns {
    grid-template-columns: repeat(2, 1fr);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 9px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-group label.required::after {
    content: '*';
    color: #ef4444;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    transition: all 0.3s;
    color: #262626;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #bfbfbf;
}

.form-group input:hover,
.form-group select:hover {
    border-color: #d9d9d9;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

/* 自定义下拉框样式 */
.custom-select-container {
    position: relative;
    flex: 1;
}

.custom-select {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s;
    color: #262626;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%238c8c8c' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 36px;
}

.custom-select:hover {
    border-color: #d9d9d9;
}

.custom-select:focus {
    outline: none;
    border-color: #1890ff;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.custom-select:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    opacity: 0.6;
}

/* 下拉选项面板 */
.custom-options {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    max-height: 280px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.custom-options.show {
    display: block;
}

.custom-option {
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    color: #262626;
}

.custom-option:hover {
    background: #f5f5f5;
}

.custom-option.selected {
    background: #e6f7ff;
    color: #1890ff;
    font-weight: 600;
}

.custom-option-text {
    flex: 1;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.form-group small {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
}

/* 按钮样式 */
.btn-row {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 32px;
    padding-top: 28px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 11px 22px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-default {
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-default:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

/* 资料上传按钮 */
.doc-btn-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 14px;
}

.doc-btn {
    padding: 9px 17px;
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.2s;
}

.doc-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
    background: #eff6ff;
}

/* 文件上传框容器 */
#docFieldsContainer {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

@media (max-width: 1200px) {
    #docFieldsContainer {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    #docFieldsContainer {
        grid-template-columns: 1fr;
    }
}

.doc-field {
    padding: 14px;
    background: #fff;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

.doc-field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.doc-field-title {
    font-weight: 500;
    color: #111827;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.doc-field-remove {
    background: #fff;
    color: #6b7280;
    border: 1px solid #d1d5db;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    margin-left: 8px;
    flex-shrink: 0;
    transition: all 0.2s;
}

.doc-field-remove:hover {
    color: #ef4444;
    border-color: #ef4444;
    background: #fef2f2;
}

.doc-upload-area {
    border: 1px dashed #d1d5db;
    border-radius: 6px;
    padding: 14px 12px;
    text-align: center;
    cursor: pointer;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    background: #f9fafb;
}

.doc-upload-area:hover {
    border-color: #2563eb;
    background: #eff6ff;
}

.doc-upload-area input[type="file"] {
    display: none;
}

.doc-upload-area div:first-child {
    font-size: 20px;
    margin-bottom: 0;
}

.doc-upload-area div:nth-child(2) {
    font-size: 12px;
    color: #374151;
}

.doc-preview {
    margin-top: 12px;
    padding: 9px 11px;
    background: #f9fafb;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12px;
}

.doc-preview img {
    max-height: 65px;
    border-radius: 4px;
}

.doc-preview span:nth-child(2) {
    flex: 1;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.doc-preview span:nth-child(3) {
    color: #6b7280;
    font-size: 11px;
    flex-shrink: 0;
}

/* 进度区域 */
.progress-section {
    margin-bottom: 36px;
}

.progress-history {
    margin-bottom: 18px;
    padding: 14px;
    background: #f9fafb;
    border-radius: 5px;
    border: 1px solid #e5e7eb;
    max-height: 200px;
    overflow-y: auto;
}

.progress-item {
    padding: 9px 12px;
    background: #f9fafb;
    border-radius: 4px;
    margin-bottom: 7px;
    font-size: 13px;
    border-left: 3px solid #2563eb;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    position: relative;
}

.progress-time {
    color: #8c8c8c;
    font-size: 12px;
    white-space: nowrap;
    min-width: 120px;
}

.progress-text {
    flex: 1;
    color: #1f2937;
}

.progress-delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 2px 6px;
    font-size: 14px;
    opacity: 0.6;
    transition: opacity 0.2s;
    border-radius: 4px;
}

.progress-delete-btn:hover {
    opacity: 1;
    background: #fee2e2;
}

.progress-input-row {
    display: flex;
    gap: 12px;
}

.progress-input-row .btn {
    padding: 10px 20px;
}

.progress-input-row input {
    flex: 1;
    padding: 10px 13px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

/* 图片放大查看器 */
.image-viewer-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.image-viewer-overlay.show {
    display: flex;
}

.image-viewer-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.image-viewer-img {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
}

.image-viewer-close {
    position: absolute;
    top: -42px;
    right: 0;
    background: #fff;
    color: #111827;
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    font-size: 22px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-viewer-close:hover {
    background: #f3f4f6;
}

.image-viewer-info {
    color: #9ca3af;
    font-size: 14px;
    margin-top: 14px;
    text-align: center;
}

.image-viewer-zoom {
    position: absolute;
    bottom: -50px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    align-items: center;
}

.image-viewer-zoom button {
    background: #374151;
    color: white;
    border: none;
    width: 34px;
    height: 34px;
    border-radius: 4px;
    font-size: 17px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-viewer-zoom button:hover {
    background: #4b5563;
}

.image-viewer-zoom span {
    color: #9ca3af;
    font-size: 14px;
    min-width: 50px;
    text-align: center;
}

/* 图片预览卡片 - 可点击 */
.doc-preview img,
.doc-upload-area img {
    cursor: zoom-in;
    border-radius: 4px;
    max-height: 80px;
    max-width: 100%;
    object-fit: cover;
}

/* 响应式 */
@media (max-width: 1200px) {
    .form-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    #docFieldsContainer {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .page-container {
        padding: 20px 12px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 18px;
    }
    
    .btn-row {
        flex-direction: column;
    }
    
    .btn-row .btn {
        width: 100%;
        justify-content: center;
    }
    
    #docFieldsContainer {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .image-viewer-zoom {
        bottom: -58px;
    }
}
</style>

<!-- 图片放大查看器 -->
<div class="image-viewer-overlay" id="imageViewer" onclick="closeImageViewer(event)">
    <div class="image-viewer-content">
        <button class="image-viewer-close" onclick="closeImageViewer(event)">×</button>
        <img class="image-viewer-img" id="viewerImg" src="" alt="预览图片">
        <div class="image-viewer-info" id="viewerInfo"></div>
        <div class="image-viewer-zoom">
            <button onclick="zoomOut()" title="缩小">🔍</button>
            <span id="zoomLevel">100%</span>
            <button onclick="zoomIn()" title="放大">🔍+</button>
            <button onclick="resetZoom()" title="重置">↺</button>
            <button onclick="rotateImage()" title="旋转">↻</button>
        </div>
    </div>
</div>

<div class="edit-container">
    <form id="customerForm" class="edit-form">
        <!-- 第一部分：基本信息 -->
        <div class="form-section">
            <h3 class="form-section-title">👤 一、基本信息</h3>
            
            <div class="form-row full">
                <div class="form-group">
                    <label class="required">选择用户（从现有用户中选择）</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <input type="text" id="userSearch" placeholder="输入用户 ID、手机号、姓名搜索..." style="flex: 1; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; color: #262626;">
                        <button type="button" class="btn btn-primary" onclick="searchUsers()">🔍 搜索</button>
                    </div>
                    <div id="userSearchResults" style="display: none; max-height: 300px; overflow-y: auto; border: 2px solid #f0f0f0; border-radius: 10px; background: white;"></div>
                    
                    <!-- 已选用户信息卡片 -->
                    <div id="selectedUser" style="display: none; margin-top: 14px; padding: 16px 20px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; border: 1px solid #bae6fd; box-shadow: 0 2px 8px rgba(186,230,253,0.3);">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <!-- 头像 -->
                            <img id="selectedUserAvatar" src="" alt="头像" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;">
                            
                            <!-- 用户基本信息 -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                    <span style="font-weight: 600; color: #0369a1; font-size: 15px;" id="selectedUserName"></span>
                                    <span style="font-size: 12px; color: #64748b;">ID: <span id="selectedUserId"></span></span>
                                    <span style="font-size: 12px; color: #1890ff; font-weight: 600;">会员 ID: <span id="selectedUserMemberId"></span></span>
                                </div>
                                <!-- 详细信息一行显示 -->
                                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; font-size: 12px; color: #64748b;">
                                    <span style="display: flex; align-items: center; gap: 4px;">📱 <span id="selectedUserPhone" style="color: #0369a1;"></span></span>
                                    <span style="display: flex; align-items: center; gap: 4px;">💬 <span id="selectedUserWechat" style="color: #0369a1;"></span></span>
                                    <span style="display: flex; align-items: center; gap: 4px;">📧 <span id="selectedUserEmail" style="color: #0369a1;"></span></span>
                                    <span style="display: flex; align-items: center; gap: 4px;">🕐 <span id="selectedUserRegistered" style="color: #0369a1;"></span></span>
                                    <span style="display: flex; align-items: center; gap: 4px;">🔑 <span id="selectedUserLastLogin" style="color: #0369a1;"></span></span>
                                </div>
                            </div>
                            
                            <!-- 清除按钮 -->
                            <button type="button" class="btn btn-default" onclick="clearSelectedUser()" style="padding: 6px 12px; font-size: 12px; flex-shrink: 0;">✕ 清除</button>
                        </div>
                    </div>
                    
                    <input type="hidden" id="user_id">
                    <div class="hint">💡 提示：支持搜索用户 ID、手机号、用户名/昵称，将用户转化为客户</div>
                </div>
            </div>
            
            <div class="form-row full">
                <div class="form-group" style="flex: 2;">
                    <label class="required">客户姓名</label>
                    <input type="text" id="customer_name" placeholder="请输入客户真实姓名" style="padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; color: #262626;" required>
                    <div class="hint">💡 提示：请输入客户的真实姓名，用于办理业务（可以与用户名不同）</div>
                </div>
                <div class="form-group" style="flex: 1; margin-left: 16px;">
                    <label>📱 OpenID</label>
                    <input type="text" id="customer_openid" placeholder="自动获取" readonly style="padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 13px; color: #8c8c8c; background-color: #fafafa; cursor: not-allowed;">
                    <div class="hint">💡 用户登录小程序后自动获取</div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>微信号</label>
                    <input type="text" id="wechat" placeholder="请输入微信号" style="padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; color: #262626;">
                </div>
                <div class="form-group">
                    <label>手机号</label>
                    <input type="text" id="phone" placeholder="请输入手机号" style="padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; color: #262626;">
                </div>
                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" id="email" placeholder="请输入邮箱地址" style="padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; color: #262626;">
                </div>
            </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>年龄</label>
                        <input type="number" id="age" placeholder="请输入年龄" min="1" max="150">
                    </div>
                    <div class="form-group">
                        <label>性别</label>
                        <div class="custom-select-container" id="gender-container">
                            <select id="gender" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="1">男</option>
                                <option value="2">女</option>
                            </select>
                            <div class="custom-options" id="gender-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>民族</label>
                        <input type="text" id="ethnicity" placeholder="例如：汉族">
                    </div>
                </div>
            </div>
            
            <!-- 第二部分：意向国家/岗位 -->
            <div class="form-section">
                <h3 class="form-section-title">🌍 二、意向国家/岗位</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">意向国家</label>
                        <div class="custom-select-container" id="country_select-container">
                            <select id="country_select" class="custom-select" required readonly>
                                <option value="">请选择国家</option>
                            </select>
                            <div class="custom-options" id="country_select-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>意向岗位</label>
                        <div class="custom-select-container" id="position_id-container">
                            <select id="position_id" class="custom-select" disabled>
                                <option value="0">请先选择国家</option>
                            </select>
                            <div class="custom-options" id="position_id-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>签证类型</label>
                        <div class="custom-select-container" id="visa_type-container">
                            <select id="visa_type" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="工作签证">工作签证</option>
                                <option value="旅游签证">旅游签证</option>
                                <option value="留学签证">留学签证</option>
                                <option value="商务签证">商务签证</option>
                                <option value="探亲签证">探亲签证</option>
                                <option value="其他">其他</option>
                            </select>
                            <div class="custom-options" id="visa_type-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>期望薪资</label>
                        <input type="text" id="expected_salary" placeholder="例如：8000-12000 元">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>办理状态</label>
                        <div class="custom-select-container" id="status_select-container">
                            <select id="status_select" class="custom-select" readonly>
                                <option value="0">潜在客户</option>
                                <option value="1">已联系</option>
                                <option value="2">已成交</option>
                                <option value="3">已流失</option>
                            </select>
                            <div class="custom-options" id="status_select-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>负责人 <span style="color: #8c8c8c; font-weight: normal;">（可选）</span></label>
                        <div class="custom-select-container" id="owner_id-container">
                            <select id="owner_id" class="custom-select" readonly>
                                <option value="">未分配</option>
                            </select>
                            <div class="custom-options" id="owner_id-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>备注说明</label>
                        <input type="text" id="remark" placeholder="其他需求或说明">
                    </div>
                </div>
                
            </div>
            
            <!-- 第三部分：详细资料 -->
            <div class="form-section">
                <h3 class="form-section-title">📋 三、详细资料</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>最高学历</label>
                        <div class="custom-select-container" id="education-container">
                            <select id="education" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="小学">小学</option>
                                <option value="初中">初中</option>
                                <option value="高中/中专">高中/中专</option>
                                <option value="大专">大专</option>
                                <option value="本科">本科</option>
                                <option value="硕士">硕士</option>
                                <option value="博士">博士</option>
                            </select>
                            <div class="custom-options" id="education-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>毕业院校</label>
                        <input type="text" id="school" placeholder="请输入毕业院校">
                    </div>
                    <div class="form-group">
                        <label>专业</label>
                        <input type="text" id="major" placeholder="请输入专业">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>婚姻状况</label>
                        <div class="custom-select-container" id="marital_status-container">
                            <select id="marital_status" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="未婚">未婚</option>
                                <option value="已婚">已婚</option>
                                <option value="离异">离异</option>
                                <option value="丧偶">丧偶</option>
                            </select>
                            <div class="custom-options" id="marital_status-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>子女状况</label>
                        <div class="custom-select-container" id="children_status-container">
                            <select id="children_status" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="无子女">无子女</option>
                                <option value="有子女">有子女</option>
                            </select>
                            <div class="custom-options" id="children_status-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>工作状况</label>
                        <div class="custom-select-container" id="work_status-container">
                            <select id="work_status" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="在职">在职</option>
                                <option value="离职">离职</option>
                                <option value="自由职业">自由职业</option>
                                <option value="学生">学生</option>
                                <option value="退休">退休</option>
                                <option value="其他">其他</option>
                            </select>
                            <div class="custom-options" id="work_status-options"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>籍贯</label>
                        <input type="text" id="hometown" placeholder="例如：北京朝阳">
                    </div>
                    <div class="form-group">
                        <label>流水状况</label>
                        <input type="text" id="flow_status" placeholder="例如：月流水 2 万">
                    </div>
                    <div class="form-group">
                        <label>社保状况</label>
                        <input type="text" id="social_security_status" placeholder="例如：已交 5 年">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>技能</label>
                        <input type="text" id="skills" placeholder="例如：日语 N1、IT 技能、厨师证等">
                    </div>
                    <div class="form-group">
                        <label>车辆</label>
                        <div class="custom-select-container" id="vehicle-container">
                            <select id="vehicle" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="无">无</option>
                                <option value="有">有</option>
                            </select>
                            <div class="custom-options" id="vehicle-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>房产</label>
                        <div class="custom-select-container" id="property-container">
                            <select id="property" class="custom-select" readonly>
                                <option value="">请选择</option>
                                <option value="无">无</option>
                                <option value="有">有</option>
                            </select>
                            <div class="custom-options" id="property-options"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>财务状况</label>
                        <input type="text" id="financial_status" placeholder="例如：存款 50 万，年收入 30 万">
                    </div>
                </div>
            </div>
            
            <!-- 第四部分：所需资料 -->
            <div class="form-section">
                <h3 class="form-section-title">📎 四、所需资料</h3>
                
                <div class="doc-btn-container">
                    <button type="button" class="doc-btn" onclick="addDocField('无犯罪证明')">📄 无犯罪证明</button>
                    <button type="button" class="doc-btn" onclick="addDocField('工资流水')">💰 工资流水</button>
                    <button type="button" class="doc-btn" onclick="addDocField('社保证明')">🏥 社保证明</button>
                    <button type="button" class="doc-btn" onclick="addDocField('工作证明')">💼 工作证明</button>
                    <button type="button" class="doc-btn" onclick="addDocField('签证申请表')">签证申请表</button>
                    <button type="button" class="doc-btn" onclick="addDocField('身份证')">🆔 身份证</button>
                    <button type="button" class="doc-btn" onclick="addDocField('结婚证')">💍 结婚证</button>
                    <button type="button" class="doc-btn" onclick="addDocField('户口本')">📕 户口本</button>
                    <button type="button" class="doc-btn" onclick="addDocField('机动车证')">🚗 机动车证</button>
                    <button type="button" class="doc-btn" onclick="addDocField('房产证')">🏠 房产证</button>
                    <button type="button" class="doc-btn" onclick="addDocField('护照')">🛂 护照</button>
                    <button type="button" class="doc-btn" onclick="addDocField('2 寸白底照片')">📷 2 寸白底照片</button>
                    <button type="button" class="doc-btn" onclick="addDocField('其他')">📦 其他</button>
                </div>
                
                <div id="docFieldsContainer">
                    <!-- 动态生成的文件上传框 -->
                </div>
            </div>
            
            <!-- 第五部分：办理进度 -->
            <div class="form-section progress-section">
                <h3 class="form-section-title">📈 五、办理进度</h3>
                
                <div class="progress-history" id="progressHistory">
                    <div style="color: #8c8c8c; text-align: center; padding: 20px;">暂无进度记录</div>
                </div>
                
                <!-- 索要材料标签组件 -->
                <div class="materials-section" style="margin-bottom: 16px;">
                    <label class="form-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #262626; font-size: 14px;">📋 索要材料</label>
                    <div id="materialsTags" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <button type="button" class="material-tag" data-value="无犯罪证明" onclick="toggleMaterialTag(this)">无犯罪证明</button>
                        <button type="button" class="material-tag" data-value="工资流水" onclick="toggleMaterialTag(this)">工资流水</button>
                        <button type="button" class="material-tag" data-value="社保证明" onclick="toggleMaterialTag(this)">社保证明</button>
                        <button type="button" class="material-tag" data-value="工作证明" onclick="toggleMaterialTag(this)">工作证明</button>
                        <button type="button" class="material-tag" data-value="签证申请表" onclick="toggleMaterialTag(this)">签证申请表</button>
                        <button type="button" class="material-tag" data-value="身份证" onclick="toggleMaterialTag(this)">身份证</button>
                        <button type="button" class="material-tag" data-value="结婚证" onclick="toggleMaterialTag(this)">结婚证</button>
                        <button type="button" class="material-tag" data-value="户口本" onclick="toggleMaterialTag(this)">户口本</button>
                        <button type="button" class="material-tag" data-value="机动车证" onclick="toggleMaterialTag(this)">机动车证</button>
                        <button type="button" class="material-tag" data-value="房产证" onclick="toggleMaterialTag(this)">房产证</button>
                        <button type="button" class="material-tag" data-value="护照" onclick="toggleMaterialTag(this)">护照</button>
                        <button type="button" class="material-tag" data-value="2 寸证件照" onclick="toggleMaterialTag(this)">2 寸证件照</button>
                        <button type="button" class="material-tag" data-value="其他" onclick="toggleMaterialTag(this)">其他</button>
                    </div>
                    <div style="margin-top: 8px; font-size: 12px; color: #8c8c8c;">💡 可多选，点击标签切换选中状态（蓝色为选中）</div>
                </div>
                
                <div class="progress-input-row">
                    <input type="text" id="progressInput" placeholder="输入正在办理的项目，如：提交移民局审核资料" style="flex: 1; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px;">
                    <button type="button" class="btn btn-primary" onclick="addProgress()">⏱️ 更新进度</button>
                </div>
            </div>
            
            <!-- 底部保存按钮 -->
            <div class="form-section" style="margin-top: 40px; padding-top: 32px; border-top: 2px solid #f0f0f0;">
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" class="btn btn-default" onclick="resetForm()" style="padding: 14px 32px; font-size: 15px;">🔄 重置</button>
                    <button type="submit" form="customerForm" class="btn btn-primary" style="padding: 14px 48px; font-size: 15px;">💾 保存客户信息</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// 页面加载
document.addEventListener('DOMContentLoaded', function() {
    // 步骤 1：初始化
    markStepComplete('step-init');
    
    // 步骤 2：加载数据
    markStepLoading('step-data');
    
    // 先加载当前用户信息
    loadCurrentUserInfo().then(() => {
        // 如果是销售人员，自动设置 owner_id 并锁定
        if (currentLoginType === 'salesman' && currentUserId) {
            const ownerSelect = document.getElementById('owner_id');
            if (ownerSelect) {
                ownerSelect.value = currentUserId;
                ownerSelect.disabled = true;
                ownerSelect.title = '销售人员不可修改负责人';
                ownerSelect.style.backgroundColor = '#f5f5f5';
                ownerSelect.style.cursor = 'not-allowed';
            }
        }
        
        return Promise.all([
            loadCountries(),
            loadPositions(),
            loadSalesUsers(),
            loadSalesOwners()
        ]);
    }).then(() => {
        markStepComplete('step-data');
        markStepComplete('step-complete');
        
        // 隐藏加载动画
        setTimeout(() => {
            hideLoading();
        }, 500);
    }).catch(err => {
        console.error('加载失败:', err);
        markStepComplete('step-data');
        markStepComplete('step-complete');
        setTimeout(() => {
            hideLoading();
        }, 500);
    });
    
    // 回车搜索
    document.getElementById('userSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchUsers();
        }
    });
    
    // 监听国家选择变化，自动加载对应岗位
    const countrySelect = document.getElementById('country_select');
    if (countrySelect) {
        countrySelect.addEventListener('change', function() {
            loadPositionsByCountry();
        });
    }
    
    // 检查 URL 参数是否有 id，如果有则加载客户数据进行编辑
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('id');
    if (customerId) {
        loadCustomerForEdit(customerId);
    }
});

// 当前登录用户信息
let currentUserId = null;
let currentLoginType = null;

// 获取当前登录用户信息
function loadCurrentUserInfo() {
    return new Promise((resolve, reject) => {
        fetch('../api/auth.php?action=userinfo')
            .then(res => res.json())
            .then(res => {
                if (res.code === 200) {
                    currentUserId = res.data.id;
                    currentLoginType = res.data.login_type;
                }
                resolve();
            })
            .catch(err => {
                console.error('获取用户信息失败:', err);
                resolve();
            });
    });
}

// 加载客户数据进行编辑
function loadCustomerForEdit(customerId) {
    // 先获取当前登录用户信息
    loadCurrentUserInfo().then(() => {
        // 显示加载提示
        const pageTitle = document.querySelector('.page-container h2') || document.querySelector('.page-title');
        if (pageTitle) {
            pageTitle.textContent = '编辑客户 (ID: ' + customerId + ')';
        }
        
        // 获取客户详情
        fetch(`../api/customer.php?action=detail&id=${customerId}`)
            .then(res => res.json())
            .then(res => {
                if (res.code !== 200) {
                    alert('获取客户信息失败：' + res.message);
                    return;
                }
                
                const data = res.data;
                
                // 检查权限：销售人员只能编辑自己的客户
                if (currentLoginType === 'salesman' && data.owner_id != currentUserId) {
                    alert('❌ 无权编辑此客户！该客户不属于您。');
                    window.location.href = 'customer.php';
                    return;
                }
                
                // 填充用户信息
                if (data.user_id) {
                    document.getElementById('user_id').value = data.user_id;
                    document.getElementById('selectedUserName').textContent = data.name;
                    document.getElementById('selectedUser').style.display = 'block';
                }
                
                // 填充基本信息
            const setFieldValue = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.value = value || '';
            };
            
            // 填充客户姓名
            setFieldValue('customer_name', data.name);
            setFieldValue('phone', data.phone);
            setFieldValue('wechat', data.wechat);
            setFieldValue('email', data.email);
            
            // 填充 OpenID（通过手机号查询 users 表，自动匹配）
            if (data.phone) {
                fetch('../api/get_user_openid.php?phone=' + encodeURIComponent(data.phone))
                    .then(res => res.json())
                    .then(res => {
                        if (res.code === 200 && res.data && res.data.openid) {
                            document.getElementById('customer_openid').value = res.data.openid;
                        } else {
                            document.getElementById('customer_openid').value = '未获取到 OpenID';
                        }
                    })
                    .catch(err => {
                        console.error('获取 OpenID 失败:', err);
                        document.getElementById('customer_openid').value = '获取失败';
                    });
            }
            setFieldValue('age', data.age);
            // 性别字段转换：数据库 1=男，2=女
            if (data.gender !== null && data.gender !== '') {
                setFieldValue('gender', data.gender.toString());
            }
            setFieldValue('ethnicity', data.ethnicity);
            setFieldValue('birthday', data.birthday);
            
            // 填充意向信息
            setFieldValue('country_select', data.country);
            setFieldValue('position_id', data.position_id || '0');
            setFieldValue('visa_type', data.visa_type);
            setFieldValue('status_select', data.status || '0');
            setFieldValue('expected_salary', data.expected_salary);
            setFieldValue('expected_city', data.expected_city);
            setFieldValue('source', data.source);
            setFieldValue('sales_user_id', data.sales_user_id || '0');
            
            // 填充教育信息
            setFieldValue('education', data.education);
            setFieldValue('school', data.school);
            setFieldValue('major', data.major);
            setFieldValue('graduate_year', data.graduate_year);
            
            // 填充婚姻子女
            setFieldValue('marital_status', data.marital_status);
            setFieldValue('children_status', data.children_status);
            
            // 填充工作生活
            setFieldValue('work_status', data.work_status);
            setFieldValue('hometown', data.hometown);
            setFieldValue('flow_status', data.flow_status);
            setFieldValue('social_security_status', data.social_security_status);
            setFieldValue('skills', data.skills);
            setFieldValue('work_experience', data.work_experience);
            
            // 填充资产信息
            setFieldValue('vehicle', data.vehicle);
            setFieldValue('property', data.property);
            setFieldValue('financial_status', data.financial_status);
            
            // 填充备注
            setFieldValue('remark', data.remark);
            
            // 加载负责人信息
            if (data.owner_id) {
                document.getElementById('owner_id').value = data.owner_id;
            }
            
            // 锁定负责人字段：销售人员不可修改
            const ownerSelect = document.getElementById('owner_id');
            if (currentLoginType === 'salesman') {
                ownerSelect.disabled = true;
                ownerSelect.title = '销售人员不可修改负责人';
                ownerSelect.style.backgroundColor = '#f5f5f5';
                ownerSelect.style.cursor = 'not-allowed';
            }
            
            // 如果国家已选择，先设置国家值，然后加载岗位，加载完成后再设置 position_id
            if (data.country) {
                setFieldValue('country_select', data.country);
                // 加载岗位，加载完成后再设置 position_id
                loadPositionsByCountry().then(() => {
                    // 岗位加载完成后，设置 position_id
                    if (data.position_id) {
                        setFieldValue('position_id', data.position_id.toString());
                    }
                    // 继续加载其他意向字段
                    setFieldValue('visa_type', data.visa_type);
                    setFieldValue('status_select', data.status || '0');
                    setFieldValue('expected_salary', data.expected_salary);
                    setFieldValue('expected_city', data.expected_city);
                    setFieldValue('source', data.source);
                    setFieldValue('sales_user_id', data.sales_user_id || '0');
                });
            } else {
                // 没有国家，直接设置其他字段
                setFieldValue('position_id', data.position_id || '0');
                setFieldValue('visa_type', data.visa_type);
                setFieldValue('status_select', data.status || '0');
                setFieldValue('expected_salary', data.expected_salary);
                setFieldValue('expected_city', data.expected_city);
                setFieldValue('source', data.source);
                setFieldValue('sales_user_id', data.sales_user_id || '0');
            }
            
            // 加载资料列表
            loadCustomerDocuments(customerId);
            
            // 加载进度历史
            loadCustomerProgress(customerId);
            
            console.log('客户数据已加载:', data);
        })
        .catch(err => {
            console.error('加载客户数据失败:', err);
            alert('加载客户数据失败，请刷新重试');
        });
    });
}

// 加载客户资料
function loadCustomerDocuments(customerId) {
    fetch(`../api/customer.php?action=documents&id=${customerId}`)
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data && res.data.length > 0) {
                const container = document.getElementById('docFieldsContainer');
                res.data.forEach((doc, index) => {
                    const fieldId = `doc_${index + 1}`;
                    
                    // 判断是否是图片
                    const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(doc.file_path);
                    const imgUrl = isImage ? (doc.file_path.startsWith('http') ? doc.file_path : window.location.origin + '/' + doc.file_path) : '';
                    
                    const html = `
                        <div class="doc-field" data-doc-type="${doc.doc_type}" id="${fieldId}">
                            <div class="doc-field-header">
                                <span class="doc-field-title" title="${doc.doc_type}">${doc.doc_type}</span>
                                <button type="button" class="doc-field-remove" onclick="removeDocField('${fieldId}')" title="删除">✕</button>
                            </div>
                            <div class="doc-preview" id="preview_${fieldId}" style="display: block;">
                                ${isImage ? `
                                    <div style="position: relative; width: 100%; max-width: 200px; border-radius: 8px; overflow: hidden; border: 2px solid #f0f0f0;">
                                        <img src="${imgUrl}" alt="${doc.file_name}" onclick="openImageViewer('${imgUrl}', '${doc.file_name.replace(/'/g, "\\'")}', '未知')" style="width: 100%; height: auto; display: block; border-radius: 6px; cursor: zoom-in;">
                                    </div>
                                ` : `
                                    <div style="font-size: 28px; color: #6b7280;">📄</div>
                                `}
                                <div style="font-size: 11px; color: #374151; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 8px;">${doc.file_name}</div>
                                <div style="font-size: 10px; color: #52c41a; margin-top: 4px;">✅ 已上传</div>
                                <!-- 隐藏字段保存文件路径 -->
                                <input type="hidden" class="existing-file-path" value="${doc.file_path}">
                                <input type="hidden" class="existing-file-name" value="${doc.file_name}">
                                <input type="hidden" class="existing-doc-id" value="${doc.id || ''}">
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', html);
                });
            }
        })
        .catch(err => console.error('加载资料失败:', err));
}

// 加载客户进度
function loadCustomerProgress(customerId) {
    console.log('加载进度，客户 ID:', customerId);
    
    fetch(`../api/customer.php?action=progress&id=${customerId}`)
        .then(res => res.json())
        .then(res => {
            console.log('API 响应:', res);
            
            if (res.code === 200 && res.data && res.data.length > 0) {
                const history = document.getElementById('progressHistory');
                history.innerHTML = res.data.map(record => {
                    console.log('处理记录:', record.id, 'materials:', record.materials);
                    const progressId = 'progress_' + record.id;
                    
                    // 解析材料（如果有）
                    let materialsHtml = '';
                    if (record.materials) {
                        try {
                            const materials = typeof record.materials === 'string' ? JSON.parse(record.materials) : record.materials;
                            const materialsStatus = record.materials_status ? (typeof record.materials_status === 'string' ? JSON.parse(record.materials_status) : record.materials_status) : {};
                            const materialsUploadedAt = record.materials_uploaded_at ? (typeof record.materials_uploaded_at === 'string' ? JSON.parse(record.materials_uploaded_at) : record.materials_uploaded_at) : {};
                            const materialsFiles = record.materials_files ? (typeof record.materials_files === 'string' ? JSON.parse(record.materials_files) : record.materials_files) : {};
                            
                            if (Array.isArray(materials) && materials.length > 0) {
                                const badges = materials.map(m => {
                                    const status = materialsStatus[m] || 'pending';
                                    const uploadedAt = materialsUploadedAt[m] || '';
                                    const fileUrl = materialsFiles[m] || '';
                                    
                                    if (status === 'uploaded' || status === 'approved') {
                                        // 判断是否为图片
                                        const isImage = fileUrl && (fileUrl.match(/\.(jpg|jpeg|png|gif|webp)$/i));
                                        const previewHtml = isImage 
                                            ? `<a href="${fileUrl}" target="_blank" class="material-preview-link" title="点击查看大图"><img src="${fileUrl}" class="material-preview-thumb" onerror="this.style.display='none'"></a>`
                                            : (fileUrl ? `<a href="${fileUrl}" target="_blank" class="material-file-link" title="下载文件">📎</a>` : '');
                                        
                                        return `<span class="material-badge uploaded" title="已上传${uploadedAt ? ' · ' + uploadedAt : ''}">${m}${previewHtml}</span>`;
                                    } else {
                                        return `<span class="material-badge pending" title="未上传">${m}</span>`;
                                    }
                                }).join('');
                                
                                materialsHtml = `
                                    <div class="progress-materials">
                                        <span class="materials-label">📋</span>
                                        ${badges}
                                    </div>
                                `;
                            }
                        } catch (e) {
                            console.error('解析材料失败:', e);
                        }
                    }
                    
                    return `
                        <div class="progress-item existing-progress" id="${progressId}">
                            <span class="progress-time">${record.created_at}</span>
                            <span class="progress-text">${escapeHtml(record.progress_text)}</span>
                            ${materialsHtml}
                            <button type="button" class="progress-delete-btn" onclick="removeProgress('${progressId}', ${customerId})" title="删除此进度">🗑️</button>
                        </div>
                    `;
                }).join('');
            } else {
                const history = document.getElementById('progressHistory');
                history.innerHTML = '<div style="color: #8c8c8c; text-align: center; padding: 20px;">暂无进度记录</div>';
            }
        })
        .catch(err => {
            console.error('加载进度失败:', err);
        });
}

// 搜索用户
function searchUsers() {
    const keyword = document.getElementById('userSearch').value.trim();
    
    if (!keyword) {
        alert('请输入搜索关键词（用户 ID、手机号、姓名）');
        return;
    }
    
    const resultsDiv = document.getElementById('userSearchResults');
    resultsDiv.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">🔍 搜索中...</div>';
    resultsDiv.style.display = 'block';
    
    fetch('../api/user.php?action=search&keyword=' + encodeURIComponent(keyword))
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                if (res.data && res.data.length > 0) {
                    resultsDiv.innerHTML = res.data.map(user => `
                        <div class="user-result-item" onclick="selectUser(${user.id}, '${escapeHtml(user.username || user.nickname)}', '${escapeHtml(user.phone || '')}', '${escapeHtml(user.wechat || '')}')">
                            <div class="user-result-info">
                                <div class="user-result-name">👤 ${escapeHtml(user.nickname || user.username)}</div>
                                <div class="user-result-meta">
                                    📱 ${escapeHtml(user.phone || '未填写')} | 
                                    👤 ID: ${user.id} | 
                                    🎫 会员 ID: ${user.member_id || '无'} | 
                                    🎭 ${user.role === 2 ? '管理员' : '普通用户'}
                                </div>
                            </div>
                            <button class="user-result-select">选择</button>
                        </div>
                    `).join('');
                } else {
                    resultsDiv.innerHTML = '<div class="user-no-results">😕 未找到匹配的用户，请检查关键词或先创建用户</div>';
                }
            } else {
                resultsDiv.innerHTML = '<div class="user-no-results">❌ 搜索失败：' + (res.message || '未知错误') + '</div>';
            }
        })
        .catch(err => {
            console.error('搜索用户失败:', err);
            resultsDiv.innerHTML = '<div class="user-no-results">❌ 搜索失败，请检查网络连接</div>';
        });
}

// 选择用户
function selectUser(userId, userName, userPhone, userWechat = '') {
    document.getElementById('user_id').value = userId;
    document.getElementById('selectedUserName').textContent = userName;
    document.getElementById('selectedUserPhone').textContent = userPhone || '暂未获取';
    document.getElementById('selectedUserId').textContent = userId;
    // 会员 ID 初始显示为加载中
    document.getElementById('selectedUserMemberId').textContent = '加载中...';
    
    // 先使用搜索时传入的数据填充卡片和表单
    document.getElementById('selectedUserWechat').textContent = userWechat || '暂未获取';
    document.getElementById('selectedUserEmail').textContent = '暂未获取';
    document.getElementById('selectedUserRegistered').textContent = '暂未获取';
    document.getElementById('selectedUserLastLogin').textContent = '暂未获取';
    
    // 自动填充手机号、微信号、邮箱到表单字段
    if (userPhone) {
        document.getElementById('phone').value = userPhone;
    }
    if (userWechat) {
        document.getElementById('wechat').value = userWechat;
    }
    
    // 获取详细用户信息
    fetch(`../api/user.php?action=detail&id=${userId}`)
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data) {
                const user = res.data;
                // 头像
                document.getElementById('selectedUserAvatar').src = user.avatar || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23e0f2fe"/><text x="50" y="55" text-anchor="middle" font-size="40" fill="%230369a1">👤</text></svg>';
                // 微信号 - 数据库字段是 wechat_id
                if (user.wechat_id) {
                    document.getElementById('selectedUserWechat').textContent = user.wechat_id;
                    document.getElementById('wechat').value = user.wechat_id;
                } else if (document.getElementById('wechat').value) {
                    // 如果表单已有值，同步到卡片
                    document.getElementById('selectedUserWechat').textContent = document.getElementById('wechat').value;
                }
                // 邮箱
                if (user.email) {
                    document.getElementById('selectedUserEmail').textContent = user.email;
                    document.getElementById('email').value = user.email;
                } else if (document.getElementById('email').value) {
                    // 如果表单已有值，同步到卡片
                    document.getElementById('selectedUserEmail').textContent = document.getElementById('email').value;
                }
                // 注册时间
                document.getElementById('selectedUserRegistered').textContent = user.created_at || '暂未获取';
                // 最后登录时间
                document.getElementById('selectedUserLastLogin').textContent = user.last_login_at || '暂未获取';
                // 会员 ID
                document.getElementById('selectedUserMemberId').textContent = user.member_id || '暂未获取';
            }
        })
        .catch(err => console.error('获取用户详情失败:', err));
    
    document.getElementById('selectedUser').style.display = 'block';
    document.getElementById('userSearchResults').style.display = 'none';
    document.getElementById('userSearch').value = '';
}

// 清除选择的用户
function clearSelectedUser() {
    document.getElementById('user_id').value = '';
    document.getElementById('selectedUser').style.display = 'none';
    // 清空自动填充的手机号和微信号
    document.getElementById('phone').value = '';
    document.getElementById('wechat').value = '';
}

// 加载国家列表（从 dropdown_options 表）
function loadCountries() {
    return new Promise((resolve, reject) => {
        fetch('../api/dropdown_options.php?action=list&category=country')
            .then(res => res.json())
            .then(res => {
                if (res.code === 200) {
                    const select = document.getElementById('country_select');
                    const optionsDiv = document.getElementById('country_select-options');
                    
                    // 更新原生 select
                    select.innerHTML = '<option value="">请选择国家</option>' + 
                        res.data.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('');
                    
                    // 更新自定义选项列表
                    if (optionsDiv) {
                        optionsDiv.innerHTML = '';
                        res.data.forEach(opt => {
                            const optionEl = document.createElement('div');
                            optionEl.className = 'custom-option';
                            optionEl.dataset.value = opt.value;
                            optionEl.innerHTML = '<span class="custom-option-text">' + escapeHtml(opt.label) + '</span>';
                            optionEl.onclick = () => selectOption('country_select', opt.value);
                            optionsDiv.appendChild(optionEl);
                        });
                    }
                }
                resolve();
            }).catch(err => {
                console.error('加载国家失败:', err);
                resolve();
            });
    });
}

// 加载岗位列表（全部）
function loadPositions() {
    return new Promise((resolve, reject) => {
        fetch('../api/customer.php?action=positions').then(res => res.json()).then(res => {
            if (res.code === 200) {
                const select = document.getElementById('position_id');
                select.innerHTML = '<option value="0">请选择</option>' + 
                    res.data.map(p => `<option value="${p.id}">${escapeHtml(p.title)}</option>`).join('');
            }
            resolve();
        }).catch(err => {
            console.error('加载岗位失败:', err);
            resolve();
        });
    });
}

// 根据国家加载对应岗位（返回 Promise）
function loadPositionsByCountry() {
    const country = document.getElementById('country_select').value;
    const positionSelect = document.getElementById('position_id');
    const positionOptionsDiv = document.getElementById('position_id-options');
    
    if (!country) {
        positionSelect.innerHTML = '<option value="0">请先选择国家</option>';
        if (positionOptionsDiv) positionOptionsDiv.innerHTML = '';
        return Promise.resolve();
    }
    
    // 检查是否选择了国家
    if (country === '' || country === '0') {
        alert('⚠️ 请先选择意向国家，然后再选择岗位！');
        positionSelect.innerHTML = '<option value="0">请先选择国家</option>';
        if (positionOptionsDiv) positionOptionsDiv.innerHTML = '';
        positionSelect.disabled = true;
        return Promise.resolve();
    }
    
    // 启用岗位选择
    positionSelect.disabled = false;
    positionSelect.innerHTML = '<option value="0">加载中...</option>';
    
    return fetch('../api/customer.php?action=positions&country=' + encodeURIComponent(country))
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                if (res.data.length === 0) {
                    positionSelect.innerHTML = '<option value="0">该国暂无岗位</option>';
                    if (positionOptionsDiv) positionOptionsDiv.innerHTML = '';
                } else {
                    // 更新原生 select
                    positionSelect.innerHTML = '<option value="0">请选择岗位</option>' + 
                        res.data.map(p => `<option value="${p.id}">${escapeHtml(p.title)}</option>`).join('');
                    
                    // 更新自定义选项列表
                    if (positionOptionsDiv) {
                        positionOptionsDiv.innerHTML = '';
                        res.data.forEach(p => {
                            const optionEl = document.createElement('div');
                            optionEl.className = 'custom-option';
                            optionEl.dataset.value = p.id;
                            optionEl.innerHTML = '<span class="custom-option-text">' + escapeHtml(p.title) + '</span>';
                            optionEl.onclick = () => selectOption('position_id', p.id);
                            positionOptionsDiv.appendChild(optionEl);
                        });
                    }
                }
            } else {
                positionSelect.innerHTML = '<option value="0">加载失败</option>';
                if (positionOptionsDiv) positionOptionsDiv.innerHTML = '';
            }
        })
        .catch(err => {
            console.error('加载岗位失败:', err);
            positionSelect.innerHTML = '<option value="0">加载失败</option>';
            if (positionOptionsDiv) positionOptionsDiv.innerHTML = '';
        });
}

// 加载销售用户列表
function loadSalesUsers() {
    return new Promise((resolve, reject) => {
        fetch('../api/customer.php?action=sales_users').then(res => res.json()).then(res => {
            if (res.code === 200) {
                // 可选：如果有销售用户选择功能，可以在这里处理
            }
            resolve();
        }).catch(err => {
            console.error('加载销售用户失败:', err);
            resolve();
        });
    });
}

// 加载销售人员列表（用于负责人选择）
function loadSalesOwners() {
    return new Promise((resolve, reject) => {
        fetch('../api/customer.php?action=salesmen_list').then(res => res.json()).then(res => {
            if (res.code === 200) {
                const select = document.getElementById('owner_id');
                const optionsDiv = document.getElementById('owner_id-options');
                
                // 更新原生 select
                select.innerHTML = '<option value="">未分配</option>' + 
                    res.data.map(s => `<option value="${s.id}">${escapeHtml(s.name)} - ${escapeHtml(s.phone)}</option>`).join('');
                
                // 更新自定义选项列表
                if (optionsDiv) {
                    optionsDiv.innerHTML = '';
                    res.data.forEach(s => {
                        const optionEl = document.createElement('div');
                        optionEl.className = 'custom-option';
                        optionEl.dataset.value = s.id;
                        optionEl.innerHTML = '<span class="custom-option-text">' + escapeHtml(s.name) + ' - ' + escapeHtml(s.phone) + '</span>';
                        optionEl.onclick = () => selectOption('owner_id', s.id);
                        optionsDiv.appendChild(optionEl);
                    });
                }
            }
            resolve();
        }).catch(err => {
            console.error('加载销售人员失败:', err);
            resolve();
        });
    });
}

// 转义 HTML
function escapeHtml(text) {
    if (!text) return '-';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 添加资料上传框
function addDocField(docType) {
    const container = document.getElementById('docFieldsContainer');
    
    // 检查是否已存在该类型的上传框（"其他"除外）
    const existing = document.querySelector(`.doc-field[data-doc-type="${docType}"]`);
    if (existing && docType !== '其他') {
        alert('该资料类型已添加，每个类型只能上传一个文件');
        return;
    }
    
    const fieldId = 'doc_' + Date.now();
    const html = `
        <div class="doc-field" data-doc-type="${docType}" id="${fieldId}">
            <div class="doc-field-header">
                <span class="doc-field-title" title="${docType}">${docType}</span>
                <button type="button" class="doc-field-remove" onclick="removeDocField('${fieldId}')" title="删除">✕</button>
            </div>
            <div class="doc-upload-area" onclick="document.getElementById('file_${fieldId}').click()">
                <div>📁</div>
                <div style="font-size: 11px; color: #6b7280;">上传文件</div>
                <input type="file" id="file_${fieldId}" onchange="handleFileUpload(this, '${fieldId}')" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </div>
            <div class="doc-preview" id="preview_${fieldId}" style="display: none;"></div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}

// 删除资料字段
function removeDocField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        // 释放预览资源
        const previewImg = document.querySelector(`#preview_${fieldId} img`);
        if (previewImg && previewImg.src.startsWith('blob:')) {
            URL.revokeObjectURL(previewImg.src);
        }
        // 清理 pendingFiles
        delete window.pendingFiles[fieldId];
        field.remove();
    }
}

// 图片查看器状态
let currentZoom = 1;
let currentRotation = 0;

// 存储当前所有待上传的文件
window.pendingFiles = {};

// 处理文件上传
function handleFileUpload(input, fieldId) {
    const file = input.files[0];
    if (!file) return;
    
    const previewDiv = document.getElementById('preview_' + fieldId);
    const field = input.closest('.doc-field');
    
    // 校验：类型
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedTypes.includes(file.type)) {
        alert('不支持的文件类型，请上传 JPG、PNG、PDF 或 Word 文件');
        input.value = ''; // 清空 input
        return;
    }
    
    // 校验：大小（最大 10MB）
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('文件过大，请上传小于 10MB 的文件');
        input.value = '';
        return;
    }
    
    // 重要：如果是替换已存在的文件，清除旧文件的隐藏字段
    // 这样保存时就不会把旧文件加入 existingDocuments 数组
    const existingPath = field.querySelector('.existing-file-path');
    const existingName = field.querySelector('.existing-file-name');
    const existingDocId = field.querySelector('.existing-doc-id');
    if (existingPath) existingPath.remove();
    if (existingName) existingName.remove();
    if (existingDocId) existingDocId.remove();
    
    // 保存文件引用到全局对象（用于后续上传）
    window.pendingFiles[fieldId] = file;
    
    // 生成预览地址并渲染
    if (file.type.startsWith('image/')) {
        // 图片：使用 URL.createObjectURL
        const imgUrl = URL.createObjectURL(file);
        const fileSizeKB = (file.size / 1024).toFixed(1);
        
        previewDiv.innerHTML = `
            <div style="position: relative; width: 100%; max-width: 200px; border-radius: 8px; overflow: hidden; border: 2px solid #f0f0f0;">
                <img src="${imgUrl}" alt="${file.name}" onclick="openImageViewer('${imgUrl}', '${file.name.replace(/'/g, "\\'")}', '${fileSizeKB}')" style="width: 100%; height: auto; display: block; border-radius: 6px; cursor: zoom-in;">
            </div>
            <div style="font-size: 11px; color: #374151; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 8px;">${file.name}</div>
            <div style="font-size: 10px; color: #52c41a; margin-top: 4px;">✅ 待上传</div>
            <!-- 隐藏字段保存文件信息 -->
            <input type="hidden" class="new-file-name" value="${file.name}">
            <input type="hidden" class="new-file-size" value="${file.size}">
            <input type="hidden" class="new-file-type" value="${file.type}">
        `;
        previewDiv.style.display = 'block';
    } else {
        // 非图片文件：显示文件信息
        const fileSizeKB = (file.size / 1024).toFixed(1);
        previewDiv.innerHTML = `
            <div style="font-size: 28px; color: #6b7280;">📄</div>
            <div style="font-size: 11px; color: #374151; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</div>
            <div style="font-size: 10px; color: #52c41a; margin-top: 4px;">✅ 待上传 (${fileSizeKB} KB)</div>
            <!-- 隐藏字段保存文件信息 -->
            <input type="hidden" class="new-file-name" value="${file.name}">
            <input type="hidden" class="new-file-size" value="${file.size}">
            <input type="hidden" class="new-file-type" value="${file.type}">
        `;
        previewDiv.style.display = 'block';
    }
    
    // 隐藏上传按钮区域
    const uploadArea = field.querySelector('.doc-upload-area');
    if (uploadArea) {
        uploadArea.style.display = 'none';
    }
}

// 打开图片查看器
function openImageViewer(imgSrc, fileName, fileSize) {
    const viewer = document.getElementById('imageViewer');
    const img = document.getElementById('viewerImg');
    const info = document.getElementById('viewerInfo');
    
    img.src = imgSrc;
    info.textContent = `${fileName} (${fileSize} KB)`;
    
    currentZoom = 1;
    currentRotation = 0;
    updateImageViewer();
    
    viewer.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// 关闭图片查看器
function closeImageViewer(event) {
    if (event && event.target !== event.currentTarget) return;
    
    const viewer = document.getElementById('imageViewer');
    viewer.classList.remove('show');
    document.body.style.overflow = '';
}

// 更新图片查看器样式
function updateImageViewer() {
    const img = document.getElementById('viewerImg');
    img.style.transform = `scale(${currentZoom}) rotate(${currentRotation}deg)`;
    document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100) + '%';
}

// 放大
function zoomIn() {
    currentZoom = Math.min(currentZoom + 0.25, 5);
    updateImageViewer();
}

// 缩小
function zoomOut() {
    currentZoom = Math.max(currentZoom - 0.25, 0.25);
    updateImageViewer();
}

// 重置缩放
function resetZoom() {
    currentZoom = 1;
    currentRotation = 0;
    updateImageViewer();
}

// 旋转图片
function rotateImage() {
    currentRotation = (currentRotation + 90) % 360;
    updateImageViewer();
}

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    const viewer = document.getElementById('imageViewer');
    if (!viewer.classList.contains('show')) return;
    
    if (e.key === 'Escape') {
        closeImageViewer();
    } else if (e.key === '+' || e.key === '=') {
        zoomIn();
    } else if (e.key === '-' || e.key === '_') {
        zoomOut();
    } else if (e.key === '0') {
        resetZoom();
    } else if (e.key === 'r' || e.key === 'R') {
        rotateImage();
    }
});

// 切换材料标签选中状态
function toggleMaterialTag(btn) {
    btn.classList.toggle('selected');
}

// 获取选中的材料列表
function getSelectedMaterials() {
    const selected = [];
    document.querySelectorAll('#materialsTags .material-tag.selected').forEach(btn => {
        selected.push(btn.dataset.value);
    });
    return selected;
}

// 添加进度（立即保存到数据库）
function addProgress() {
    const input = document.getElementById('progressInput');
    const progressText = input.value.trim();
    
    if (!progressText) {
        alert('请输入进度内容');
        return;
    }
    
    // 检查是否是编辑模式（有客户 ID）
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('id');
    
    if (!customerId) {
        alert('请先保存客户，然后再添加进度');
        return;
    }
    
    // 获取选中的材料
    const selectedMaterials = getSelectedMaterials();
    const materialsJson = selectedMaterials.length > 0 ? JSON.stringify(selectedMaterials) : null;
    
    console.log('选中的材料:', selectedMaterials);
    console.log('材料 JSON:', materialsJson);
    
    const history = document.getElementById('progressHistory');
    const now = new Date();
    const timeStr = now.toLocaleString('zh-CN', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    // 立即保存到数据库
    const progressForm = new FormData();
    progressForm.append('customer_id', customerId);
    progressForm.append('progress_text', progressText);
    if (materialsJson) {
        progressForm.append('materials', materialsJson);
    }
    
    fetch('../api/customer.php?action=add_progress', {
        method: 'POST',
        body: progressForm
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 如果是第一条记录，清空提示
            if (history.querySelector('.progress-item') === null) {
                history.innerHTML = '';
            }
            
            const progressId = 'progress_' + Date.now();
            
            // 构建材料标签 HTML
            let materialsHtml = '';
            if (selectedMaterials.length > 0) {
                const materialsBadges = selectedMaterials.map(m => 
                    `<span class="material-badge" title="待上传">${m}</span>`
                ).join('');
                materialsHtml = `
                    <div class="progress-materials">
                        <span class="materials-label">📋</span>
                        ${materialsBadges}
                    </div>
                `;
            }
            
            const html = `
                <div class="progress-item existing-progress" id="${progressId}">
                    <span class="progress-time">${timeStr}</span>
                    <span class="progress-text">${escapeHtml(progressText)}</span>
                    ${materialsHtml}
                    <button type="button" class="progress-delete-btn" onclick="removeProgress('${progressId}', ${customerId})" title="删除此进度">🗑️</button>
                </div>
            `;
            
            history.insertAdjacentHTML('afterbegin', html);
            input.value = '';
            
            // 重置材料标签选择
            document.querySelectorAll('#materialsTags .material-tag.selected').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            console.log('进度已保存，材料标签：', selectedMaterials);
        } else {
            alert('保存进度失败：' + (res.message || '未知错误'));
        }
    })
    .catch(err => {
        console.error('保存进度失败:', err);
        alert('保存进度失败，请检查网络连接');
    });
}

// 删除进度（同时从数据库删除）
function removeProgress(progressId, customerId) {
    const progress = document.getElementById(progressId);
    if (progress) {
        // 从数据库删除
        const progressText = progress.querySelector('.progress-text')?.textContent || '';
        
        const deleteForm = new FormData();
        deleteForm.append('customer_id', customerId);
        deleteForm.append('progress_text', progressText);
        
        fetch('../api/customer.php?action=delete_progress', {
            method: 'POST',
            body: deleteForm
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                progress.remove();
                // 如果删除后没有进度了，显示提示
                // 获取选中的材料
    const selectedMaterials = getSelectedMaterials();
    const materialsJson = selectedMaterials.length > 0 ? JSON.stringify(selectedMaterials) : null;
    
    console.log('选中的材料:', selectedMaterials);
    console.log('材料 JSON:', materialsJson);
    
    const history = document.getElementById('progressHistory');
                if (history.querySelectorAll('.progress-item').length === 0) {
                    history.innerHTML = '<div style="color: #8c8c8c; text-align: center; padding: 20px;">暂无进度记录</div>';
                }
                console.log('进度已删除');
            } else {
                alert('删除进度失败：' + (res.message || '未知错误'));
            }
        })
        .catch(err => {
            console.error('删除进度失败:', err);
            alert('删除进度失败，请检查网络连接');
        });
    }
}

// 重置表单
function resetForm() {
    if (confirm('确定要清空表单吗？')) {
        document.getElementById('customerForm').reset();
        document.getElementById('docFieldsContainer').innerHTML = '';
        document.getElementById('progressHistory').innerHTML = '<div style="color: #8c8c8c; text-align: center;">暂无进度记录</div>';
        // 重置用户选择状态
        clearSelectedUser();
    }
}

// 表单提交
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('customerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
    
    // 验证必须选择用户
    const userId = document.getElementById('user_id').value;
    if (!userId) {
        alert('❌ 请先搜索并选择一个用户！\n\n客户必须从现有用户中选择，不能自行录入。');
        document.getElementById('userSearch').focus();
        return;
    }
    
    // 验证必填字段：客户姓名
    const customerName = document.getElementById('customer_name').value;
    if (!customerName) {
        alert('请输入客户姓名');
        document.getElementById('customer_name').focus();
        return;
    }
    
    // 验证必填字段
    const country = document.getElementById('country_select').value;
    if (!country) {
        alert('请选择意向国家');
        document.getElementById('country_select').focus();
        return;
    }
    
    // 验证岗位是否已选择
    const positionId = document.getElementById('position_id').value;
    if (!positionId || positionId === '0') {
        alert('请选择意向岗位');
        document.getElementById('position_id').focus();
        return;
    }
    
    // 收集表单数据
    const getFieldValue = (id) => {
        const el = document.getElementById(id);
        return el ? el.value : '';
    };
    
    const formData = {
        user_id: userId,
        name: customerName,
        country: country,
        position_id: getFieldValue('position_id'),
        visa_type: getFieldValue('visa_type'),
        status: getFieldValue('status_select'),
        owner_id: getFieldValue('owner_id'),
        expected_salary: getFieldValue('expected_salary'),
        remark: getFieldValue('remark'),
        education: getFieldValue('education'),
        school: getFieldValue('school'),
        major: getFieldValue('major'),
        marital_status: getFieldValue('marital_status'),
        children_status: getFieldValue('children_status'),
        work_status: getFieldValue('work_status'),
        hometown: getFieldValue('hometown'),
        flow_status: getFieldValue('flow_status'),
        social_security_status: getFieldValue('social_security_status'),
        skills: getFieldValue('skills'),
        vehicle: getFieldValue('vehicle'),
        property: getFieldValue('property'),
        financial_status: getFieldValue('financial_status'),
        gender: getFieldValue('gender'),
        ethnicity: getFieldValue('ethnicity'),
        age: getFieldValue('age'),
        email: getFieldValue('email'),
        wechat: getFieldValue('wechat'),
        phone: getFieldValue('phone'),
        source: getFieldValue('source'),
        sales_user_id: getFieldValue('sales_user_id'),
        expected_city: getFieldValue('expected_city'),
        graduate_year: getFieldValue('graduate_year'),
        work_experience: getFieldValue('work_experience'),
        birthday: getFieldValue('birthday')
    };
    
    // 收集资料上传
    const documents = [];
    const existingDocuments = [];
    
    document.querySelectorAll('.doc-field').forEach(field => {
        const docType = field.getAttribute('data-doc-type');
        
        // 检查是否有已存在的文件（编辑时加载的）
        const existingPath = field.querySelector('.existing-file-path');
        const existingName = field.querySelector('.existing-file-name');
        const existingDocId = field.querySelector('.existing-doc-id');
        
        // 如果是已存在的文件（编辑时加载的）
        if (existingPath && existingPath.value) {
            existingDocuments.push({
                type: docType,
                file_path: existingPath.value,
                file_name: existingName ? existingName.value : '',
                doc_id: existingDocId ? existingDocId.value : ''
            });
        }
        // 如果是新上传的文件（从全局 pendingFiles 获取）
        else {
            const newFileName = field.querySelector('.new-file-name');
            const fieldId = field.id; // 从 field 元素获取 ID
            
            // 从全局 pendingFiles 获取文件引用
            const fileToUpload = window.pendingFiles[fieldId];
            
            if (newFileName && newFileName.value && fileToUpload) {
                documents.push({
                    type: docType,
                    file: fileToUpload
                });
            }
        }
    });
    
    // 收集进度记录（不再需要，进度已实时保存）
    const progressRecords = [];
    
    console.log('客户数据:', formData);
    console.log('新上传资料:', documents);
    console.log('已存在资料:', existingDocuments);
    console.log('进度记录:', progressRecords);
    
    // 检查是否是编辑模式
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('id');
    const isEditMode = !!customerId;
    
    // 构建 FormData 对象
    const form = new FormData();
    for (const key in formData) {
        if (formData[key] !== '' && formData[key] !== null) {
            form.append(key, formData[key]);
        }
    }
    
    // 添加已存在的资料信息（编辑时保留的文件）
    // 始终发送，即使是空数组（表示用户删除了所有文件）
    form.append('existing_documents', JSON.stringify(existingDocuments));
    
    // 注意：新资料文件会在客户保存成功后再上传
    // 这里先记录资料信息，等保存成功后再处理
    const docsToUpload = documents.map(doc => ({
        type: doc.type,
        file: doc.file
    }));
    
    // 进度已实时保存，不需要再添加
    
    // 编辑模式需要添加 id 和更新动作
    if (isEditMode) {
        form.append('id', customerId);
    }
    
    // 发送保存请求
    const action = isEditMode ? 'update' : 'create';
    fetch(`../api/customer.php?action=${action}`, {
        method: 'POST',
        body: form
    })
    .then(res => res.json())
    .then(async res => {
        if (res.code === 200) {
            const savedCustomerId = res.data.id || customerId;
            let uploadSuccess = true;
            
            // 如果有文件需要上传，在保存成功后上传
            if (docsToUpload.length > 0 && savedCustomerId) {
                for (const doc of docsToUpload) {
                    const uploadForm = new FormData();
                    uploadForm.append('customer_id', savedCustomerId);
                    uploadForm.append('doc_type', doc.type);
                    uploadForm.append('file', doc.file);
                    
                    try {
                        const uploadRes = await fetch('../api/customer.php?action=upload_document', {
                            method: 'POST',
                            body: uploadForm
                        });
                        const uploadResult = await uploadRes.json();
                        if (uploadResult.code !== 200) {
                            console.error('文件上传失败:', doc.file.name, uploadResult.message);
                            uploadSuccess = false;
                        }
                    } catch (err) {
                        console.error('文件上传异常:', doc.file.name, err);
                        uploadSuccess = false;
                    }
                }
                
                // 上传完成后，释放预览资源并清理 pendingFiles
                docsToUpload.forEach(doc => {
                    // 如果是 Blob 对象（通过 createObjectURL 创建），释放它
                    if (doc.file instanceof Blob && doc.file.type.startsWith('image/')) {
                        // 找到对应的 fieldId 并释放
                        for (const fieldId in window.pendingFiles) {
                            if (window.pendingFiles[fieldId] === doc.file) {
                                const previewImg = document.querySelector(`#preview_${fieldId} img`);
                                if (previewImg && previewImg.src.startsWith('blob:')) {
                                    URL.revokeObjectURL(previewImg.src);
                                }
                                delete window.pendingFiles[fieldId];
                                break;
                            }
                        }
                    }
                });
            }
            
            // 保存成功后，重新加载进度列表（确保显示最新数据）
            if (savedCustomerId) {
                loadCustomerProgress(savedCustomerId);
            }
            
            if (!uploadSuccess) {
                alert('⚠️ 客户' + (isEditMode ? '更新' : '保存') + '成功，但部分文件上传失败！\n\n客户 ID: ' + savedCustomerId);
            } else {
                alert('✅ 客户' + (isEditMode ? '更新' : '保存') + '成功！\n\n客户 ID: ' + savedCustomerId + '\n姓名：' + (res.data.name || formData.name));
            }
            
            // 跳转到客户管理页面
            window.location.href = 'customer.php';
        } else {
            alert('❌ 保存失败：' + (res.message || '未知错误'));
        }
    })
    .catch(err => {
        console.error('保存失败:', err);
        alert('❌ 保存失败，请检查网络连接');
    });
    });
});

// 初始化自定义下拉框
document.addEventListener('DOMContentLoaded', function() {
    const customSelects = [
        'gender', 'country_select', 'position_id', 'visa_type',
        'status_select', 'owner_id', 'education', 'marital_status',
        'children_status', 'work_status', 'vehicle', 'property'
    ];
    
    customSelects.forEach(selectId => {
        initCustomSelect(selectId);
    });
});

// 初始化单个自定义下拉框
function initCustomSelect(selectId) {
    const select = document.getElementById(selectId);
    const optionsDiv = document.getElementById(selectId + '-options');
    
    if (!select || !optionsDiv) return;
    
    // 渲染选项列表
    renderCustomOptions(selectId);
    
    // 点击 select 显示/隐藏选项
    select.addEventListener('mousedown', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // 关闭其他下拉框
        document.querySelectorAll('.custom-options').forEach(div => {
            if (div.id !== selectId + '-options') {
                div.classList.remove('show');
            }
        });
        
        // 切换当前下拉框
        optionsDiv.classList.toggle('show');
    });
    
    // 也绑定 click 事件防止原生行为
    select.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
}

// 渲染自定义选项列表
function renderCustomOptions(selectId) {
    const select = document.getElementById(selectId);
    const optionsDiv = document.getElementById(selectId + '-options');
    
    if (!select || !optionsDiv) return;
    
    optionsDiv.innerHTML = '';
    
    // 添加所有选项
    Array.from(select.options).forEach((option, index) => {
        const optionEl = document.createElement('div');
        optionEl.className = 'custom-option';
        optionEl.dataset.value = option.value;
        optionEl.dataset.index = index;
        
        if (option.value === select.value) {
            optionEl.classList.add('selected');
        }
        
        optionEl.innerHTML = '<span class="custom-option-text">' + escapeHtml(option.text) + '</span>';
        
        optionEl.onclick = () => selectOption(selectId, option.value);
        optionsDiv.appendChild(optionEl);
    });
}

// 选择选项
function selectOption(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.value = value;
    
    // 更新选中状态
    const optionsDiv = document.getElementById(selectId + '-options');
    if (optionsDiv) {
        optionsDiv.querySelectorAll('.custom-option').forEach(opt => {
            if (opt.dataset.value === value) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
        optionsDiv.classList.remove('show');
    }
    
    // 触发 change 事件（如果有）
    const event = new Event('change', { bubbles: true });
    select.dispatchEvent(event);
}

// 点击外部关闭下拉框
document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-select-container')) {
        document.querySelectorAll('.custom-options').forEach(div => {
            div.classList.remove('show');
        });
    }
});

// 转义 HTML 辅助函数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/includes/footer.php';
