<?php
$currentPage = 'position';
$pageTitle = '岗位编辑';
require_once __DIR__ . '/includes/header.php';

// 获取岗位 ID（如果有则是编辑模式）
$positionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $positionId > 0;
?>

<style>
.edit-container {
    padding: 24px;
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

/* 自定义下拉容器 */
.custom-select-container {
    position: relative;
    flex: 1;
}

.custom-select {
    width: 100%;
    padding: 11px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    transition: all 0.3s;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238c8c8c' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
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

/* 选项列表 */
.custom-options {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    max-height: 280px;
    overflow-y: auto;
    z-index: 100;
    display: none;
}

.custom-options.show {
    display: block;
}

.custom-option {
    padding: 10px 14px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
    border-bottom: 1px solid #f5f5f5;
}

.custom-option:last-child {
    border-bottom: none;
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

.custom-option-delete {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #8c8c8c;
    font-size: 16px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    margin-left: 8px;
}

.custom-option:hover .custom-option-delete {
    display: flex;
}

.custom-option-delete:hover {
    background: #ff4d4f;
    color: white;
}

.custom-option-delete.default {
    display: none !important;
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

.btn {
    padding: 12px 32px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    background: #f5f5f5;
    color: #595959;
}

.btn-add {
    width: 42px;
    height: 42px;
    padding: 0;
    border: 2px dashed #d9d9d9;
    border-radius: 10px;
    background: white;
    color: #1890ff;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-add:hover {
    border-color: #1890ff;
    background: #e6f7ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(24,144,255,0.2);
}

/* 添加选项弹窗 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    padding: 32px;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-body {
    margin-bottom: 24px;
}

.modal-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.modal-input:focus {
    outline: none;
    border-color: #1890ff;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.modal-hint {
    margin-top: 8px;
    font-size: 13px;
    color: #8c8c8c;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.modal-actions .btn {
    padding: 10px 24px;
    font-size: 14px;
}

/* 下拉选项删除按钮 */
.select-option-wrapper {
    position: relative;
}

.select-option-delete {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: #ff4d4f;
    color: white;
    font-size: 14px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    z-index: 10;
}

.select-option-delete:hover {
    background: #ff7875;
    transform: translateY(-50%) scale(1.1);
}

select.select-with-delete {
    position: relative;
    padding-right: 40px;
}

/* 时间信息显示 */
.form-meta {
    margin-top: 32px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 24px;
    font-size: 12px;
    color: #8c8c8c;
}

/* ========== 标签组件样式 ========== */
.tag-input-container {
    margin-bottom: 10px;
}

.tag-input-wrapper {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.tag-input {
    flex: 1;
    padding: 10px 14px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    font-size: 14px;
    background: white;
    transition: all 0.3s ease;
}

.tag-input:focus {
    outline: none;
    border-color: #1890ff;
    background: white;
    box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
}

.btn-add-tag {
    padding: 10px 20px;
    background: linear-gradient(135deg, #1890ff, #40a9ff);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.btn-add-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.4);
}

.btn-add-tag:active {
    transform: translateY(0);
}

/* 标签列表 */
.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 12px;
    border: 2px dashed #f0f0f0;
    border-radius: 10px;
    min-height: 44px;
    background: #fafafa;
}

.tag-list:has(.tag-item) {
    border-style: solid;
    border-color: #f0f0f0;
}

/* 标签项 */
.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    font-size: 12px;
    color: #262626;
    animation: tagPop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    height: 28px;
    box-sizing: border-box;
}

@keyframes tagPop {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.tag-item:hover {
    background: linear-gradient(135deg, #e6f7ff, #bae7ff);
    border-color: #91d5ff;
}

.tag-item .tag-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tag-item .tag-delete {
    width: 16px;
    height: 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #8c8c8c;
    font-size: 16px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    flex-shrink: 0;
    padding: 0;
}

.tag-item .tag-delete:hover {
    background: #ff7875;
    color: white;
    transform: scale(1.1) rotate(90deg);
}

.tag-list:empty::before {
    content: '暂无标签，请在上方输入添加';
    color: #bfbfbf;
    font-size: 13px;
    display: flex;
    align-items: center;
    width: 100%;
}
</style>

<div class="edit-container" id="editContainer" style="opacity: 1;">
    <!-- 表单 -->
    <form id="positionForm" class="edit-form" onsubmit="return false;">
        <input type="hidden" id="positionId" value="<?php echo $positionId; ?>">
        
        <!-- 基本信息 -->
        <div class="form-section">
            <div class="form-section-title">📌 基本信息</div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label class="required">岗位名称</label>
                    <input type="text" id="title" required placeholder="例如：澳洲餐厅服务员">
                    <div class="hint">标题将显示在岗位列表和详情页</div>
                </div>
            </div>
            
            <div class="form-row half">
                <div class="form-group">
                    <label class="required">岗位分类</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div class="custom-select-container" id="category-container">
                            <select id="category" class="custom-select" required onchange="onSelectChange('category')" readonly>
                                <option value="">请选择分类</option>
                            </select>
                            <div class="custom-options" id="category-options"></div>
                        </div>
                        <button type="button" class="btn-add" onclick="addOption('category', '岗位分类')">➕</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="required">所属国家</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div class="custom-select-container" id="country-container">
                            <select id="country" class="custom-select" required onchange="onSelectChange('country')" readonly>
                                <option value="">请选择国家</option>
                            </select>
                            <div class="custom-options" id="country-options"></div>
                        </div>
                        <button type="button" class="btn-add" onclick="addOption('country', '国家')">➕</button>
                    </div>
                </div>
            </div>
            
            <div class="form-row half">
                <div class="form-group">
                    <label>签证类型</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div class="custom-select-container" id="visa_type-container">
                            <select id="visa_type" class="custom-select" onchange="onSelectChange('visa_type')" readonly>
                                <option value="">请选择签证类型</option>
                            </select>
                            <div class="custom-options" id="visa_type-options"></div>
                        </div>
                        <button type="button" class="btn-add" onclick="addOption('visa_type', '签证类型')">➕</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>学历要求</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div class="custom-select-container" id="education_required-container">
                            <select id="education_required" class="custom-select" onchange="onSelectChange('education_required')" readonly>
                                <option value="">不限</option>
                            </select>
                            <div class="custom-options" id="education_required-options"></div>
                        </div>
                        <button type="button" class="btn-add" onclick="addOption('education_required', '学历')">➕</button>
                    </div>
                </div>
            </div>
            
            <div class="form-row half">
                <div class="form-group">
                    <label>薪资范围（文本）</label>
                    <input type="text" id="salary_range" placeholder="例如：2000-3000 澳币/月 或 面议">
                </div>
                <div class="form-group">
                    <label>岗位标签</label>
                    <div class="tag-input-container" style="flex: 1; display: flex; flex-direction: column;">
                        <div class="tag-input-wrapper">
                            <input type="text" id="tagsInput" placeholder="输入标签后按回车" class="tag-input" style="padding: 8px 12px; font-size: 13px;">
                            <button type="button" onclick="addTag()" class="btn-add-tag" style="padding: 8px 16px; font-size: 13px;">+ 添加</button>
                        </div>
                        <div id="tagsList" class="tag-list" style="flex: 1; overflow-y: auto; min-height: 0;"></div>
                    </div>
                    <textarea id="tags" rows="2" style="display: none;"></textarea>
                    <div class="hint">例如：急聘，包食宿，可兼职</div>
                </div>
            </div>
        </div>

        <!-- 岗位详情 -->
        <div class="form-section">
            <div class="form-section-title">岗位详情</div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label class="required">岗位描述</label>
                    <textarea id="description" required placeholder="请详细描述岗位职责和要求..." style="min-height: 200px;"></textarea>
                    <div class="hint">详细描述工作职责、任职要求等</div>
                </div>
            </div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label>福利待遇</label>
                    <textarea id="benefits" placeholder="请描述公司提供的福利待遇..." style="min-height: 120px;"></textarea>
                    <div class="hint">例如：带薪假期、医疗保险、培训机会等</div>
                </div>
            </div>
        </div>
        
        <!-- 推荐业务员 -->
        <div class="form-section">
            <div class="form-section-title">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" fill="#1890ff"/>
                    <path d="M10 11C6.66667 11 4 12.3333 4 15V17H16V15C16 12.3333 13.3333 11 10 11Z" fill="#1890ff"/>
                </svg>
                推荐业务员
            </div>
            
            <div class="form-row full">
                <div class="form-group">
                    <label>选择推荐业务员</label>
                    <select id="recommend_salesman_id" class="custom-select">
                        <option value="">请选择业务员</option>
                    </select>
                    <div class="hint">选择负责该岗位推荐的业务员，将显示在岗位详情页</div>
                </div>
            </div>
            
            <div id="selected_salesman_info" style="display: none; margin-top: 16px; padding: 16px; background: #f5f5f5; border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img id="selected_avatar" src="" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;" />
                    <div>
                        <div id="selected_name" style="font-weight: 600; color: #262626;"></div>
                        <div id="selected_phone" style="color: #8c8c8c; font-size: 13px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="form-actions">
            <button type="button" class="btn btn-default" onclick="window.location.href='position.php'">
                ✖️ 取消
            </button>
            <button type="button" class="btn btn-primary" onclick="savePosition('draft')">
                💾 保存草稿
            </button>
            <button type="button" class="btn btn-success" onclick="savePosition('publish')">
                🚀 发布岗位
            </button>
        </div>
        
        <!-- 时间信息（仅编辑模式显示） -->
        <?php if ($isEditMode): ?>
        <div class="form-meta">
            <span>📅 创建时间：<span id="created_at" style="color: #595959;"></span></span>
            <span>🔄 更新时间：<span id="updated_at" style="color: #595959;"></span></span>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- 添加选项弹窗 -->
<div class="modal-overlay" id="addOptionModal">
    <div class="modal-content">
        <div class="modal-title">
            <span id="modalTitleIcon">➕</span>
            <span id="modalTitleText">添加选项</span>
        </div>
        <div class="modal-body">
            <input type="text" class="modal-input" id="newOptionInput" placeholder="请输入选项内容" maxlength="50">
            <div class="modal-hint">提示：添加后将立即显示在下拉列表中</div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-default" onclick="closeAddOptionModal()">取消</button>
            <button type="button" class="btn btn-primary" onclick="confirmAddOption()">确定添加</button>
        </div>
    </div>
</div>

<script>
// ========== 标签管理 ==========
let tagData = [];

// 添加标签
function addTag() {
    const input = document.getElementById('tagsInput');
    const value = input.value.trim();
    
    if (!value) {
        input.focus();
        return;
    }
    
    // 检查是否重复
    if (tagData.includes(value)) {
        input.value = '';
        input.focus();
        return;
    }
    
    tagData.push(value);
    renderTags();
    syncTagsToTextarea();
    
    input.value = '';
    input.focus();
}

// 删除标签
function deleteTag(index) {
    tagData.splice(index, 1);
    renderTags();
    syncTagsToTextarea();
}

// 渲染标签
function renderTags() {
    const container = document.getElementById('tagsList');
    if (!container) return;
    
    if (tagData.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = tagData.map((tag, index) => `
        <div class="tag-item">
            <span class="tag-text">${escapeHtml(tag)}</span>
            <button type="button" class="tag-delete" onclick="deleteTag(${index})" title="删除">×</button>
        </div>
    `).join('');
}

// 同步标签到 textarea
function syncTagsToTextarea() {
    const textarea = document.getElementById('tags');
    if (textarea) {
        textarea.value = tagData.join(',');
    }
}

// 加载业务员列表
function loadSalesmenList() {
    return fetch('/api/salesmen.php?action=list&status=1&page_size=100')
        .then(res => res.json())
        .then(res => {
            const select = document.getElementById('recommend_salesman_id');
            if (!select) return;
            
            // 清空选项（保留第一个"请选择"）
            select.innerHTML = '<option value="">请选择业务员</option>';
            
            if (res.success && res.data && res.data.length > 0) {
                res.data.forEach(salesman => {
                    const option = new Option(
                        salesman.name, 
                        salesman.id,
                        false,
                        false
                    );
                    option.dataset.avatar = salesman.avatar || '';
                    option.dataset.phone = salesman.phone || '';
                    option.dataset.wechat = salesman.wechat || '';
                    select.add(option);
                });
            }
        })
        .catch(err => console.error('加载业务员列表失败:', err));
}

// 更新选中的业务员信息显示
function updateSelectedSalesmanInfo(avatar, name, phone) {
    const infoDiv = document.getElementById('selected_salesman_info');
    if (!infoDiv) return;
    
    if (name) {
        document.getElementById('selected_name').textContent = name;
        document.getElementById('selected_phone').textContent = phone ? '📞 ' + phone : '';
        document.getElementById('selected_avatar').src = avatar || '/images/default-avatar.png';
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

// HTML 转义
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 回车添加标签
function initTagInput() {
    const input = document.getElementById('tagsInput');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag();
            }
        });
    }
}

// 从 textarea 加载标签数据
function loadTagsFromTextarea() {
    const textarea = document.getElementById('tags');
    if (textarea && textarea.value.trim()) {
        tagData = textarea.value.split(',').filter(t => t.trim()).map(t => t.trim());
        renderTags();
        syncTagsToTextarea();
    }
}

// ========== 其他变量 ==========
// 当前要添加选项的下拉框 ID 和名称
let currentSelectId = '';
let currentSelectName = '';

// 选项分类映射
const optionCategories = {
    'category': 'category',
    'country': 'country',
    'visa_type': 'visa_type',
    'education_required': 'education_required'
};

// 格式化日期时间
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// 加载下拉选项并渲染自定义列表（返回 Promise）
function loadOptions(selectId) {
    return new Promise((resolve, reject) => {
        const category = optionCategories[selectId];
        
        if (!category) {
            resolve();
            return;
        }
        
        fetch(`../api/dropdown_options.php?action=list&category=${category}`)
            .then(res => res.json())
            .then(res => {
                if (res.code !== 200) {
                    console.log('下拉选项加载失败:', res.message);
                    resolve();
                    return;
                }
                
                const select = document.getElementById(selectId);
                const optionsDiv = document.getElementById(selectId + '-options');
                
                if (!select || !optionsDiv) {
                    console.log('元素不存在:', selectId);
                    resolve();
                    return;
                }
                
                // 更新原生 select - 保留第一个选项（请选择）
                const defaultText = select.options[0]?.text || '请选择';
                select.innerHTML = '';
                select.add(new Option(defaultText, ''));
                
                // 更新自定义选项列表 - 完全清空后重新创建
                optionsDiv.innerHTML = '';
                const defaultOption = document.createElement('div');
                defaultOption.className = 'custom-option';
                defaultOption.dataset.value = '';
                defaultOption.dataset.id = '0';
                defaultOption.innerHTML = '<span class="custom-option-text">请选择</span>';
                defaultOption.onclick = () => selectOption(selectId, '');
                optionsDiv.appendChild(defaultOption);
                
                // 添加从数据库加载的选项
                res.data.forEach(opt => {
                    if (opt.status == 1) {
                        // 添加到原生 select
                        const option = new Option(opt.label, opt.value);
                        option.dataset.id = opt.id;
                        option.dataset.isDefault = opt.is_default;
                        select.add(option);
                        
                        // 添加到自定义列表
                        const optionEl = document.createElement('div');
                        optionEl.className = 'custom-option';
                        optionEl.dataset.value = opt.value;
                        optionEl.dataset.id = opt.id;
                        const labelEscaped = opt.label.replace(/'/g, "\\'");
                        optionEl.innerHTML = `
                            <span class="custom-option-text">${opt.label}</span>
                            <button type="button" class="custom-option-delete ${opt.is_default == 1 ? 'default' : ''}" 
                                    onclick="event.stopPropagation(); deleteOption(${opt.id}, '${labelEscaped}', '${selectId}')">
                                ✕
                            </button>
                        `;
                        optionEl.onclick = () => selectOption(selectId, opt.value);
                        optionsDiv.appendChild(optionEl);
                    }
                });
                
                resolve();
            })
            .catch(err => {
                console.log('解析 JSON 失败:', err);
                resolve();
            });
    });
}

// 选择选项
function onSelectChange(selectId) {
    const select = document.getElementById(selectId);
    const optionsDiv = document.getElementById(selectId + '-options');
    if (!select || !optionsDiv) return;
    
    // 更新自定义列表的选中状态
    const options = optionsDiv.querySelectorAll('.custom-option');
    options.forEach(opt => {
        if (opt.dataset.value === select.value) {
            opt.classList.add('selected');
        } else {
            opt.classList.remove('selected');
        }
    });
}

// 手动选择选项（从自定义列表）
function selectOption(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.value = value;
    onSelectChange(selectId);
    
    // 关闭选项列表
    const optionsDiv = document.getElementById(selectId + '-options');
    if (optionsDiv) {
        optionsDiv.classList.remove('show');
    }
}

// 点击 select 显示/隐藏选项列表
document.addEventListener('DOMContentLoaded', function() {
    // 初始化标签输入
    initTagInput();
    
    // 加载业务员列表（推荐业务员选择器）
    loadSalesmenList();
    
    // 监听推荐业务员选择变化
    const salesmanSelect = document.getElementById('recommend_salesman_id');
    if (salesmanSelect) {
        salesmanSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                updateSelectedSalesmanInfo(
                    selectedOption.dataset.avatar || '',
                    selectedOption.textContent,
                    selectedOption.dataset.phone || ''
                );
            } else {
                updateSelectedSalesmanInfo('', '', '');
            }
        });
    }
    
    Object.keys(optionCategories).forEach(selectId => {
        const select = document.getElementById(selectId);
        const optionsDiv = document.getElementById(selectId + '-options');
        
        if (select && optionsDiv) {
            // 使用 mousedown 而不是 click，更好地阻止原生行为
            select.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // 关闭其他选项列表
                document.querySelectorAll('.custom-options').forEach(div => {
                    if (div.id !== selectId + '-options') {
                        div.classList.remove('show');
                    }
                });
                
                // 切换当前选项列表
                const isShowing = optionsDiv.classList.contains('show');
                optionsDiv.classList.remove('show');
                if (!isShowing) {
                    optionsDiv.classList.add('show');
                }
                
                return false;
            });
            
            // 也监听 click 事件以防万一
            select.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        }
        
        // 加载选项
        loadOptions(selectId);
    });
    
    // 点击其他地方关闭选项列表
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-select-container')) {
            document.querySelectorAll('.custom-options').forEach(div => {
                div.classList.remove('show');
            });
        }
    });
});

// 打开添加选项弹窗
function addOption(selectId, selectName) {
    currentSelectId = selectId;
    currentSelectName = selectName;
    
    document.getElementById('modalTitleText').textContent = '添加' + selectName;
    document.getElementById('newOptionInput').value = '';
    document.getElementById('addOptionModal').classList.add('show');
    document.getElementById('newOptionInput').focus();
}

// 关闭添加选项弹窗
function closeAddOptionModal() {
    document.getElementById('addOptionModal').classList.remove('show');
    currentSelectId = '';
    currentSelectName = '';
}

// 确认添加选项
function confirmAddOption() {
    const newValue = document.getElementById('newOptionInput').value.trim();
    
    if (!newValue) {
        alert('请输入选项内容');
        document.getElementById('newOptionInput').focus();
        return false;
    }
    
    const category = optionCategories[currentSelectId];
    if (!category) {
        alert('无效的分类');
        return false;
    }
    
    // 保存到数据库
    const formData = new FormData();
    formData.append('category', category);
    formData.append('value', newValue);
    formData.append('label', newValue);
    
    fetch('../api/dropdown_options.php?action=add', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 重新加载选项
            loadOptions(currentSelectId);
            
            // 关闭弹窗
            closeAddOptionModal();
            
            // 显示成功提示
            alert('✅ 已添加 "' + newValue + '" 到' + currentSelectName + '列表');
        } else {
            alert('❌ 添加失败：' + res.message);
        }
    })
    .catch(err => {
        alert('网络错误：' + err.message);
    });
    
    return true;
}

// 删除选项
function deleteOption(optionId, optionLabel, selectId) {
    if (!confirm(`确定要删除 "${optionLabel}" 吗？\n\n注意：删除后已选择该选项的岗位数据不会受影响，但新岗位将无法再选择此选项。`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', optionId);
    
    fetch('../api/dropdown_options.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            // 重新加载选项
            loadOptions(selectId);
            
            alert('✅ 已删除 "' + optionLabel + '"');
        } else {
            alert('❌ 删除失败：' + res.message);
        }
    })
    .catch(err => {
        alert('网络错误：' + err.message);
    });
}

// 按 Enter 键确认添加
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && document.getElementById('addOptionModal').classList.contains('show')) {
        confirmAddOption();
    }
    if (e.key === 'Escape' && document.getElementById('addOptionModal').classList.contains('show')) {
        closeAddOptionModal();
    }
});

function loadPositionData(id) {
    console.log('loadPositionData 被调用，id=', id);
    
    // 步骤 1：初始化
    
    console.log('步骤 1 完成：初始化');
    
    // 步骤 2：加载岗位信息
    
    console.log('步骤 2：开始加载岗位信息');
    
    fetch(`../api/position.php?action=detail&id=${id}`)
        .then(res => {
            console.log('API 响应状态:', res.status);
            return res.json();
        })
        .then(res => {
            console.log('API 响应数据:', res);
            
            
            if (res.code === 200) {
                // 步骤 3：加载下拉选项
                
                console.log('步骤 3：开始加载下拉选项');
                
                // 等待所有下拉选项加载完成
                const optionPromises = Object.keys(optionCategories).map(selectId => {
                    return new Promise(resolve => {
                        loadOptions(selectId).finally(() => {
                            resolve();
                        });
                    });
                });
                
                
                Promise.all(optionPromises).then(() => {
                    console.log('所有下拉选项加载完成');
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    // 填充表单数据
                    const data = res.data;
                    console.log('岗位数据:', data);
                    
                    // 自定义下拉框需要特殊处理
                    const setSelectVal = (selectId, val) => {
                        const select = document.getElementById(selectId);
                        console.log(`设置 ${selectId} = ${val}`);
                        if (select && val) {
                            // 先尝试设置值
                            select.value = val;
                            // 如果设置失败（选项不存在），添加选项后再设置
                            if (select.value !== val) {
                                const option = document.createElement('option');
                                option.value = val;
                                option.textContent = val;
                                option.selected = true;
                                select.appendChild(option);
                            }
                            onSelectChange(selectId);
                        }
                    };
                    
                    setSelectVal('category', data.category);
                    setSelectVal('country', data.country);
                    setSelectVal('visa_type', data.visa_type);
                    setSelectVal('education_required', data.education_required);
                    
                    // 填充其他字段
                    const setVal = (id, val) => {
                        const el = document.getElementById(id);
                        if (el && val) {
                            el.value = val;
                        }
                    };
                    setVal('title', data.title);
                    setVal('city', data.city);
                    setVal('industry', data.industry);
                    setVal('salary_range', data.salary_range);
                    // 加载标签数据
                    if (data.tags) {
                        tagData = data.tags.split(',').filter(t => t.trim()).map(t => t.trim());
                        renderTags();
                        syncTagsToTextarea();
                    }
                    setVal('description', data.description);
                    setVal('requirements', data.requirements);
                    setVal('benefits', data.benefits);
                    
                    // 加载推荐业务员信息
                    if (data.recommend_salesman_id) {
                        document.getElementById('recommend_salesman_id').value = data.recommend_salesman_id;
                        // 触发 change 事件以显示选中业务员信息
                        const selectEl = document.getElementById('recommend_salesman_id');
                        const selectedOption = selectEl.options[selectEl.selectedIndex];
                        if (selectedOption.value) {
                            updateSelectedSalesmanInfo(
                                selectedOption.dataset.avatar || '',
                                selectedOption.textContent,
                                selectedOption.dataset.phone || ''
                            );
                        }
                    }
                    
                    // 步骤 4：完成
                    
                    
                    
                });
            } else {
                
                alert('加载岗位失败：' + res.message);
                setTimeout(() => window.location.href = 'position.php', 1500);
            }
        })
        .catch(err => {
            console.error('加载岗位失败:', err);
            
            
            alert('网络错误：' + err.message);
        });
}

// 保存岗位
function savePosition(mode) {
    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value.trim();
    const country = document.getElementById('country').value.trim();
    const description = document.getElementById('description').value.trim();
    
    // 验证必填字段
    if (!title) {
        alert('请输入岗位名称');
        document.getElementById('title').focus();
        return false;
    }
    if (!country) {
        alert('请选择所属国家');
        document.getElementById('country').focus();
        return false;
    }
    
    const positionId = document.getElementById('positionId').value;
    const status = mode === 'publish' ? '1' : '0';
    
    
    const formData = new FormData();
    // 只在编辑模式添加 id 字段
    if (positionId && positionId !== '0' && positionId !== '') {
        formData.append('id', positionId);
    } else {
    }
    formData.append('title', title);
    formData.append('category', category);
    formData.append('country', country);
    
    // 安全获取字段值（检查元素是否存在）
    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    };
    
    formData.append('city', getVal('city'));
    formData.append('industry', getVal('industry'));
    formData.append('visa_type', getVal('visa_type'));
    formData.append('education_required', getVal('education_required'));
    formData.append('major_required', getVal('major_required'));
    formData.append('age_min', getVal('age_min') || 0);
    formData.append('age_max', getVal('age_max') || 0);
    formData.append('languages', getVal('languages'));
    formData.append('skills', getVal('skills'));
    formData.append('salary_range', getVal('salary_range'));
    formData.append('tags', getVal('tags'));
    formData.append('description', description);
    formData.append('requirements', getVal('requirements'));
    formData.append('benefits', getVal('benefits'));
    formData.append('required_materials', getVal('required_materials'));
    formData.append('attachment_files', getVal('attachment_files'));
    // 推荐业务员 ID
    const recommendSalesmanId = getVal('recommend_salesman_id');
    formData.append('recommend_salesman_id', recommendSalesmanId || '');
    // latitude/longitude 为空时不传或传 0
    const lat = getVal('latitude');
    const lng = getVal('longitude');
    formData.append('latitude', lat || '0');
    formData.append('longitude', lng || '0');
    formData.append('status', status);
    
    const isEditMode = positionId && positionId != '0' && positionId != '';
    const action = isEditMode ? 'update' : 'create';
    
    // 发送到正式 API
    fetch(`../api/position.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(res => {
        return res.text();
    })
    .then(text => {
        const res = JSON.parse(text);
        if (res.code === 200) {
            // 记录操作日志
            fetch('../api/log_operation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: positionId ? '更新岗位' : '创建岗位',
                    detail: `岗位标题：${title}，状态：${status == 1 ? '已发布' : '草稿'}`
                })
            }).catch(() => {});
            
            const statusText = status == 1 ? '🚀 发布成功' : '💾 保存成功';
            alert(statusText);
            
            // 保存后跳转到岗位列表
            window.location.href = 'position.php';
        } else {
            alert('❌ 操作失败：' + res.message);
        }
    })
    .catch(err => {
        
        alert('网络错误：' + err.message);
    });
    
    return false;
}

// 将需要被 onclick 调用的函数挂载到 window 对象，确保全局可访问
window.addOption = addOption;
window.closeAddOptionModal = closeAddOptionModal;
window.confirmAddOption = confirmAddOption;
window.deleteOption = deleteOption;
window.addTag = addTag;
window.deleteTag = deleteTag;
</script>

</body>
</html>

