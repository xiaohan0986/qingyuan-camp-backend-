<?php
$currentPage = 'ai-article';
$pageTitle = 'AI 生文';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.ai-container {
    padding: 24px;
}

.ai-header {
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

.ai-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ai-title .ai-badge {
    font-size: 14px;
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
    background: linear-gradient(135deg, #f59e0b, #f97316);
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

.btn-ai {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
}

.main-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

/* 左侧输入区 */
.input-panel {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.panel-title {
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

/* 标签输入组件 */
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
    background: #fafafa;
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
    transition: all 0.3s ease;
    white-space: nowrap;
}

.btn-add-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24,144,255,0.4);
}

/* 标签列表 */
.tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 36px;
    max-height: 120px;
    overflow-y: auto;
    padding: 8px;
    background: #fafafa;
    border-radius: 8px;
    border: 2px dashed #e8e8e8;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.tag-list:has(.tag-item) {
    border-style: solid;
    border-color: #f0f0f0;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: linear-gradient(135deg, #f0f5ff, #e6f7ff);
    border: 1px solid #bae7ff;
    border-radius: 6px;
    font-size: 12px;
    color: #262626;
    animation: tagPop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
    background: #ff4d4f;
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.tag-item:hover .tag-delete {
    opacity: 1;
    transform: scale(1);
}

.tag-item .tag-delete:hover {
    background: #ff7875;
    transform: scale(1.1) rotate(90deg);
}

.tag-list:empty::before {
    content: '暂无标签，请在上方输入后添加';
    color: #bfbfbf;
    font-size: 13px;
    display: flex;
    align-items: center;
    padding: 8px 0;
}

/* 生成按钮区 */
.generate-section {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
}

.generate-btn {
    width: 100%;
    padding: 16px;
    font-size: 16px;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.generate-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(245,158,11,0.4);
}

.generate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.generate-btn .spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 右侧预览区 */
.preview-panel {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.preview-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-actions {
    display: flex;
    gap: 10px;
}

.preview-content {
    flex: 1;
    border: 2px solid #f0f0f0;
    border-radius: 12px;
    padding: 20px;
    background: #fafafa;
    overflow-y: auto;
    min-height: 500px;
}

.preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #bfbfbf;
    text-align: center;
}

.preview-empty .icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.preview-empty .text {
    font-size: 14px;
}

/* 生成结果样式 */
.article-result {
    font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Helvetica Neue', STHeiti, 'Microsoft Yahei', Tahoma, Simsun, sans-serif;
    line-height: 1.8;
    color: #262626;
}

.article-result h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 24px 0 16px;
    color: #262626;
}

.article-result h2 {
    font-size: 20px;
    font-weight: 600;
    margin: 20px 0 12px;
    color: #262626;
    padding-left: 12px;
    border-left: 3px solid #1890ff;
}

.article-result h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 16px 0 10px;
    color: #262626;
}

.article-result p {
    margin-bottom: 16px;
    text-align: justify;
    letter-spacing: 0.5px;
}

.article-result strong {
    font-weight: 600;
    color: #262626;
}

.article-result ul, .article-result ol {
    margin: 16px 0;
    padding-left: 24px;
}

.article-result li {
    margin-bottom: 8px;
}

.article-result blockquote {
    margin: 16px 0;
    padding: 12px 16px;
    background: #f7f8fa;
    border-left: 3px solid #1890ff;
    border-radius: 0 8px 8px 0;
    color: #595959;
    font-style: italic;
}

/* 加载状态 */
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
    z-index: 2000;
}

.loading-overlay.show {
    display: flex;
}

.loading-spinner {
    text-align: center;
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.loading-spinner .spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f0f0f0;
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.loading-spinner .text {
    font-size: 16px;
    color: #595959;
    font-weight: 600;
}

.loading-spinner .tip {
    font-size: 13px;
    color: #8c8c8c;
    margin-top: 12px;
    max-width: 300px;
}

/* 移动端适配 */
@media (max-width: 1200px) {
    .main-content {
        grid-template-columns: 1fr;
    }
    
    .preview-panel {
        min-height: 400px;
    }
}

/* 成功提示 */
.success-tip {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #52c41a, #73d13d);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(82,196,26,0.3);
    transform: translateY(-100px);
    transition: transform 0.3s;
    z-index: 3000;
}

.success-tip.show {
    transform: translateY(0);
}
</style>

<div class="ai-container">
    <!-- 头部 -->
    <div class="ai-header">
        <div class="ai-title">
            <span>🤖 AI 智能生文</span>
            <span class="ai-badge">Beta</span>
        </div>
        <div class="header-actions">
            <button class="btn btn-default" onclick="window.location.href='article.php'">📋 返回文章管理</button>
            <button class="btn btn-success" onclick="saveToArticle()">💾 保存到文章</button>
        </div>
    </div>

    <!-- 主内容区 -->
    <div class="main-content">
        <!-- 左侧输入面板 -->
        <div class="input-panel">
            <div class="panel-title">创作要求</div>
            
            <div class="form-group">
                <label class="required">文章标题</label>
                <input type="text" id="articleTitle" placeholder="请输入文章标题">
            </div>
            
            <div class="form-group">
                <label class="required">关键词标签</label>
                <div class="tag-input-container">
                    <div class="tag-input-wrapper">
                        <input type="text" id="keywordInput" placeholder="输入关键词后按回车" class="tag-input">
                        <button type="button" onclick="addKeyword()" class="btn-add-tag">+ 添加</button>
                    </div>
                    <div id="keywordList" class="tag-list"></div>
                </div>
                <div class="hint">添加 3-5 个关键词，帮助 AI 更好地理解主题</div>
            </div>
            
            <div class="form-group">
                <label>分类</label>
                <select id="category" style="width: 100%; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">请选择分类</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>写作风格</label>
                <select id="writingStyle">
                    <option value="professional">专业严谨</option>
                    <option value="casual">轻松活泼</option>
                    <option value="storytelling">故事叙述</option>
                    <option value="news">新闻资讯</option>
                    <option value="tutorial">教程指南</option>
                    <option value="review">评测分析</option>
                </select>
                <div class="hint">选择文章的整体风格和语调</div>
            </div>
            
            <div class="form-group">
                <label>补充说明</label>
                <textarea id="additionalInfo" placeholder="请输入更多创作要求，例如：&#10;- 目标读者群体&#10;- 文章长度要求&#10;- 需要包含的要点&#10;- 其他特殊要求" style="min-height: 150px;"></textarea>
            </div>
            
            <!-- 生成按钮 -->
            <div class="generate-section">
                <button class="generate-btn" id="generateBtn" onclick="generateArticle()">
                    <span>✨ 开始生成文章</span>
                </button>
            </div>
        </div>

        <!-- 右侧预览面板 -->
        <div class="preview-panel">
            <div class="preview-header">
                <div class="preview-title">文章预览</div>
                <div class="preview-actions">
                    <button class="btn btn-sm" onclick="copyContent()" style="background: #f0f0f0; color: #666;">📋 复制</button>
                    <button class="btn btn-sm" onclick="clearPreview()" style="background: #f0f0f0; color: #666;">清空</button>
                </div>
            </div>
            
            <div class="preview-content" id="previewContent">
                <div class="preview-empty">
                    <div class="icon">📝</div>
                    <div class="text">请在左侧输入创作要求<br>然后点击"开始生成文章"</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 加载状态 -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="text" id="loadingText">AI 正在创作中...</div>
        <div class="tip">AI 正在根据您的要求撰写文章，这可能需要 10-30 秒，请耐心等待</div>
    </div>
</div>

<!-- 成功提示 -->
<div class="success-tip" id="successTip">✅ 已保存成功</div>

<!-- 隐藏的 textarea 用于存储生成的内容 -->
<textarea id="generatedContent" style="display: none;"></textarea>
<textarea id="generatedSummary" style="display: none;"></textarea>

<script>
// 标签数据
let keywordData = [];

// 加载分类列表
function loadCategories() {
    fetch('../api/article.php?action=categories')
        .then(res => res.json())
        .then(result => {
            const categorySelect = document.getElementById('category');
            if (!categorySelect) return;
            
            const currentValue = categorySelect.value;
            categorySelect.innerHTML = '<option value="">请选择分类</option>';
            
            let categories = [];
            if (result.success && Array.isArray(result.data)) {
                categories = result.data;
            } else if (result.code === 200 && Array.isArray(result.data)) {
                categories = result.data.map(name => ({ name }));
            }
            
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.name || '';
                option.textContent = cat.name || '';
                categorySelect.appendChild(option);
            });
            
            if (currentValue) {
                categorySelect.value = currentValue;
            }
        })
        .catch(err => {
            console.error('加载分类失败:', err);
        });
}

// 页面加载
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    
    // 监听回车键
    const keywordInput = document.getElementById('keywordInput');
    if (keywordInput) {
        keywordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addKeyword();
            }
        });
    }
});

// 添加关键词
function addKeyword() {
    const input = document.getElementById('keywordInput');
    const value = input.value.trim();
    
    if (!value) {
        input.focus();
        return;
    }
    
    if (keywordData.includes(value)) {
        input.value = '';
        input.focus();
        return;
    }
    
    keywordData.push(value);
    renderKeywords();
    
    input.value = '';
    input.focus();
}

// 删除关键词
function deleteKeyword(index) {
    keywordData.splice(index, 1);
    renderKeywords();
}

// 渲染关键词
function renderKeywords() {
    const container = document.getElementById('keywordList');
    if (!container) return;
    
    if (keywordData.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = keywordData.map((tag, index) => `
        <div class="tag-item">
            <span class="tag-text">${escapeHtml(tag)}</span>
            <button type="button" class="tag-delete" onclick="deleteKeyword(${index})">×</button>
        </div>
    `).join('');
}

// HTML 转义
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 生成文章
function generateArticle() {
    const title = document.getElementById('articleTitle').value.trim();
    
    if (!title) {
        alert('请输入文章标题');
        document.getElementById('articleTitle').focus();
        return;
    }
    
    if (keywordData.length === 0) {
        alert('请至少添加一个关键词');
        document.getElementById('keywordInput').focus();
        return;
    }
    
    const category = document.getElementById('category').value;
    const writingStyle = document.getElementById('writingStyle').value;
    const additionalInfo = document.getElementById('additionalInfo').value.trim();
    
    // 显示加载状态
    showLoading('AI 正在创作中...');
    
    // 模拟 AI 生成（实际应该调用 AI API）
    // TODO: 替换为真实的 AI API 调用
    setTimeout(() => {
        const mockContent = generateMockContent(title, keywordData, writingStyle, additionalInfo);
        displayResult(mockContent);
        hideLoading();
    }, 2000);
}

// 生成模拟内容（临时用，后续替换为真实 AI API）
function generateMockContent(title, keywords, style, additionalInfo) {
    const styleNames = {
        'professional': '专业严谨',
        'casual': '轻松活泼',
        'storytelling': '故事叙述',
        'news': '新闻资讯',
        'tutorial': '教程指南',
        'review': '评测分析'
    };
    
    const summary = `本文围绕"${keywords.join('、')}"等关键词，为您详细介绍${title}的相关内容。`;
    
    const content = `
<h1>${title}</h1>

<p>【AI 生成内容 - 演示版本】</p>

<h2>一、引言</h2>
<p>随着时代的发展，${keywords[0] || '相关领域'}越来越受到人们的关注。本文将从多个角度为您详细解析${title}。</p>

<h2>二、核心概念</h2>
<p>${keywords.map(k => `<strong>${k}</strong>`).join('、')} 是理解本文的关键。让我们逐一了解这些概念：</p>
<ul>
    <li><strong>${keywords[0] || '概念一'}</strong>：这是基础且重要的概念，它指的是...</li>
    <li><strong>${keywords[1] || '概念二'}</strong>：在此基础上，我们进一步探讨...</li>
    <li><strong>${keywords[2] || '概念三'}</strong>：这一概念与前两者密切相关...</li>
</ul>

<h2>三、详细分析</h2>
<p>从${styleNames[style] || '专业'}的角度来看，${title} 涉及多个层面的内容。首先，我们需要了解其背景和发展历程...</p>

<blockquote>
    <p>💡 <strong>提示</strong>：${additionalInfo || '这是 AI 根据您提供的关键词自动生成的内容示例。实际使用时将调用真实的 AI 接口生成更优质的内容。'}</p>
</blockquote>

<h2>四、实践应用</h2>
<p>理论知识最终要服务于实践。在实际应用中，我们需要注意以下几点：</p>
<ol>
    <li>明确目标和需求</li>
    <li>选择合适的方法和工具</li>
    <li>持续优化和改进</li>
    <li>总结经验教训</li>
</ol>

<h2>五、总结</h2>
<p>通过本文的介绍，相信您对${title} 有了更深入的了解。${keywords.join('、')} 等关键词将帮助您在实际工作中更好地应用所学知识。</p>

<p><em>（完）</em></p>
    `.trim();
    
    return { title, summary, content };
}

// 显示结果
function displayResult(result) {
    const preview = document.getElementById('previewContent');
    const contentTextarea = document.getElementById('generatedContent');
    const summaryTextarea = document.getElementById('generatedSummary');
    
    preview.innerHTML = `
        <div class="article-result">
            ${result.content}
        </div>
    `;
    
    contentTextarea.value = result.content;
    summaryTextarea.value = result.summary;
}

// 保存到文章
function saveToArticle() {
    const title = document.getElementById('articleTitle').value.trim();
    const content = document.getElementById('generatedContent').value.trim();
    const summary = document.getElementById('generatedSummary').value.trim();
    const category = document.getElementById('category').value;
    
    if (!title || !content) {
        alert('请先生成文章');
        return;
    }
    
    if (!category) {
        alert('请选择文章分类');
        document.getElementById('category').focus();
        return;
    }
    
    // 跳转到编辑页面并传递数据
    const params = new URLSearchParams({
        ai_title: title,
        ai_content: content,
        ai_summary: summary,
        ai_category: category,
        ai_keywords: keywordData.join(',')
    });
    
    window.location.href = `article_edit.php?${params.toString()}`;
}

// 复制内容
function copyContent() {
    const content = document.getElementById('generatedContent').value;
    if (!content) {
        alert('没有可复制的内容');
        return;
    }
    
    navigator.clipboard.writeText(content).then(() => {
        alert('✅ 已复制到剪贴板');
    }).catch(() => {
        alert('❌ 复制失败');
    });
}

// 清空预览
function clearPreview() {
    document.getElementById('previewContent').innerHTML = `
        <div class="preview-empty">
            <div class="icon">📝</div>
            <div class="text">请在左侧输入创作要求<br>然后点击"开始生成文章"</div>
        </div>
    `;
    document.getElementById('generatedContent').value = '';
    document.getElementById('generatedSummary').value = '';
}

// 显示加载状态
function showLoading(text) {
    document.getElementById('loadingText').textContent = text;
    document.getElementById('loadingOverlay').classList.add('show');
    document.getElementById('generateBtn').disabled = true;
    document.getElementById('generateBtn').innerHTML = '<div class="spinner"></div><span>生成中...</span>';
}

// 隐藏加载状态
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
    document.getElementById('generateBtn').disabled = false;
    document.getElementById('generateBtn').innerHTML = '<span>✨ 开始生成文章</span>';
}

// 显示成功提示
function showSuccessTip() {
    const tip = document.getElementById('successTip');
    tip.classList.add('show');
    setTimeout(() => {
        tip.classList.remove('show');
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php';
