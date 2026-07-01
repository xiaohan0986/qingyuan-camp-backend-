<?php
/**
 * 文章编辑页面（用于抽屉加载）
 * 仿 order_detail.php 设计模式
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$db = Database::getInstance();
$admin = Auth::user();
$currentAdminName = $admin['name'] ?? '管理员';
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $articleId > 0;

// 获取分类列表
$categories = $db->fetchAll("SELECT id, name FROM article_categories WHERE status = 1 ORDER BY sort ASC, id DESC");

// 如果是编辑模式，加载文章数据
$article = null;
if ($isEditMode) {
    $article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$articleId]);
    if (!$article) {
        echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">文章不存在</div>';
        exit;
    }
}
?>

<style>
    .detail-section {
        margin-bottom: 24px;
        padding-bottom: 0;
        border-bottom: none;
    }

    .detail-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 16px;
    }

    .section-mark {
        display: inline-block;
        width: 4px;
        height: 16px;
        background: #1890ff;
        margin-right: 8px;
        vertical-align: middle;
        border-radius: 2px;
    }

    /* TAB 切换 */
    .detail-tabs {
        display: flex;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 24px;
    }

    .tab-btn {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        font-size: 14px;
        color: #8c8c8c;
        transition: all 0.3s;
    }

    .tab-btn:hover { color: #1890ff; }
    .tab-btn.active { color: #1890ff; border-bottom-color: #1890ff; font-weight: 600; }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* info-row 三列 */
    .info-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }

    .info-row.full { grid-template-columns: 1fr; }
    .info-row.half { grid-template-columns: 1fr 1fr; }

    .info-label {
        color: #8c8c8c;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .info-value {
        color: #262626;
        font-size: 14px;
    }

    /* 表单控件 */
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        font-family: inherit;
        box-sizing: border-box;
        background: #fafafa;
    }

    .form-control:focus {
        outline: none;
        border-color: #1890ff;
        background: white;
        box-shadow: 0 0 0 3px rgba(24,144,255,0.08);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    /* 正文编辑器工具栏 */
    .editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: 8px 10px;
        background: #fafafa;
        border: 2px solid #f0f0f0;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
    }
    .editor-toolbar .tb-group {
        display: flex;
        gap: 2px;
        padding-right: 8px;
        margin-right: 8px;
        border-right: 1px solid #e8e8e8;
    }
    .editor-toolbar .tb-group:last-child {
        border-right: none;
        margin-right: 0;
        padding-right: 0;
    }
    .editor-toolbar button {
        width: 32px;
        height: 32px;
        border: none;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        color: #595959;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .editor-toolbar button:hover {
        background: #e6e6e6;
        color: #262626;
    }
    .editor-toolbar button.tb-label {
        width: auto;
        padding: 0 8px;
        font-size: 12px;
        font-weight: 600;
        color: #8c8c8c;
    }
    .editor-toolbar button.tb-label:hover {
        background: transparent;
        color: #595959;
    }
    textarea.content-editor {
        min-height: 350px;
        font-family: 'Consolas', 'Monaco', monospace;
        line-height: 1.6;
        border-radius: 0 0 8px 8px;
        border-top: none;
    }

    select.form-control {
        background: #fafafa;
        cursor: pointer;
        -webkit-appearance: auto;
    }

    .form-control-sm {
        padding: 8px 10px;
        font-size: 13px;
    }

    .hint {
        font-size: 12px;
        color: #8c8c8c;
        margin-top: 4px;
    }

    label.required::after {
        content: ' *';
        color: #ff4d4f;
    }

    /* 拨动开关 */
    #articleDrawerBody .toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        flex-shrink: 0;
        background: #CBCED4;
        border-radius: 24px;
        transition: all 0.3s;
        cursor: pointer;
    }
    #articleDrawerBody .toggle-switch.on {
        background: #FF6B35;
    }
    #articleDrawerBody .toggle-switch .knob {
        position: absolute;
        width: 18px;
        height: 18px;
        left: 3px;
        top: 3px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    #articleDrawerBody .toggle-switch.on .knob {
        transform: translateX(20px);
    }
    #articleDrawerBody .toggle-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 6px;
        cursor: pointer;
        user-select: none;
    }
    #articleDrawerBody .toggle-row .toggle-label {
        font-size: 14px;
        color: #262626;
        font-weight: 500;
    }

    /* 操作按钮 */
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #f0f0f0;
    }

    .btn {
        padding: 10px 24px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
        font-weight: 600;
    }

    .btn-primary { background: #1890ff; color: white; }
    .btn-primary:hover { background: #40a9ff; box-shadow: 0 4px 12px rgba(24,144,255,0.3); }

    .btn-success { background: #52c41a; color: white; }
    .btn-success:hover { background: #73d13d; box-shadow: 0 4px 12px rgba(82,196,26,0.3); }

    .btn-default { background: #f5f5f5; color: #666; }
    .btn-default:hover { background: #e6e6e6; }

    .btn-danger { background: #ff4d4f; color: white; }
    .btn-danger:hover { background: #ff7875; }

    /* ProductImageUpload 组件样式 */
    .piu-wrapper { font-family: inherit; font-size: 14px; color: #262626; line-height: 1.5; }
    .piu-label { display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px; }
    .piu-label__text { font-size: 14px; font-weight: 600; color: #262626; }
    .piu-label__hint { font-size: 12px; color: #8c8c8c; font-weight: 400; }
    .piu-label__count { margin-left: auto; font-size: 12px; color: #8c8c8c; }
    .piu-grid { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; min-height: 80px; }
    .piu-item { position: relative; width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #d9d9d9; flex-shrink: 0; cursor: grab; transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s; background: #fff; }
    .piu-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    .piu-item:active { cursor: grabbing; }
    .piu-item img { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
    .piu-cover-badge { position: absolute; top: 4px; left: 4px; z-index: 2; background: #1890ff; color: #fff; font-size: 10px; padding: 1px 7px; border-radius: 4px; line-height: 1.6; pointer-events: none; }
    .piu-remove-btn { position: absolute; top: 4px; right: 4px; z-index: 3; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.55); color: #fff; border: none; cursor: pointer; font-size: 14px; line-height: 1; display: flex; align-items: center; justify-content: center; transition: background 0.15s; padding: 0; }
    .piu-remove-btn:hover { background: rgba(255,77,79,0.9); }
    .piu-remove-btn::before { content: '×'; font-size: 15px; }
    .piu-item.piu-dragging { opacity: 0.35; }
    .piu-item.piu-drag-over { transform: scale(1.05); box-shadow: 0 4px 16px rgba(24,144,255,0.35); }
    .piu-uploader { width: 120px; height: 120px; flex-shrink: 0; border: 2px dashed #d9d9d9; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; background: #fafafa; transition: all 0.25s ease; user-select: none; }
    .piu-uploader:hover { border-color: #1890ff; background: #f0f5ff; }
    .piu-uploader.piu-drag-over-upload { border-color: #1890ff; background: #f0f5ff; transform: scale(1.03); }
    .piu-uploader__icon { font-size: 28px; margin-bottom: 4px; line-height: 1; }
    .piu-uploader__text { font-size: 12px; font-weight: 500; color: #262626; }
    .piu-uploader__hint { font-size: 10px; color: #8c8c8c; margin-top: 2px; }
    .piu-preview-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; cursor: zoom-out; animation: piu-fadeIn 0.2s ease; }
    .piu-preview-overlay img { max-width: 85vw; max-height: 85vh; border-radius: 8px; box-shadow: 0 8px 40px rgba(0,0,0,0.4); }
    .piu-preview-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.15); color: #fff; border: none; font-size: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
    .piu-preview-nav:hover { background: rgba(255,255,255,0.3); }
    .piu-preview-nav--prev { left: 20px; }
    .piu-preview-nav--next { right: 20px; }
    .piu-hidden-input { display: none; }
    @keyframes piu-fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* 标签组件 */
    .tag-input-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .tag-input-row .form-control {
        flex: 1;
    }

    .btn-add-tag {
        padding: 8px 16px;
        background: #1890ff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .btn-add-tag:hover { background: #40a9ff; }

    .tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        min-height: 32px;
        padding: 6px 8px;
        background: #fafafa;
        border-radius: 6px;
        border: 2px dashed #e8e8e8;
    }

    .tag-list:empty::before {
        content: '暂无标签，输入后点击"添加"';
        color: #bfbfbf;
        font-size: 12px;
        display: flex;
        align-items: center;
    }

    .tag-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        background: #f0f5ff;
        border: 1px solid #bae7ff;
        border-radius: 4px;
        font-size: 12px;
        color: #262626;
        height: 24px;
    }

    .tag-item .tag-delete {
        width: 14px; height: 14px;
        border: none;
        background: #ff4d4f;
        color: white;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
        line-height: 1;
    }

    .tag-item .tag-delete:hover { background: #ff7875; }

    /* 时间信息 */
    .meta-row {
        display: flex;
        gap: 24px;
        font-size: 12px;
        color: #8c8c8c;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    /* 加载遮罩 */
    .loading-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.85);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-overlay.show { display: flex; }

    .loading-spinner { text-align: center; }

    .loading-spinner .spinner {
        width: 40px; height: 40px;
        border: 3px solid #f0f0f0;
        border-top-color: #1890ff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 12px;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .loading-spinner .text {
        font-size: 14px;
        color: #595959;
        font-weight: 600;
    }

    /* 自动保存提示 */
    .auto-save-tip {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #52c41a;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        box-shadow: 0 4px 16px rgba(82,196,26,0.3);
        transform: translateY(-100px);
        transition: transform 0.3s;
        z-index: 9998;
    }

    .auto-save-tip.show { transform: translateY(0); }

    /* 手机预览外壳 */
    .phone-shell {
        width: 320px;
        height: 640px;
        margin: 0 auto;
        background: #1a1a1a;
        border-radius: 44px;
        padding: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2), 0 0 0 1px rgba(255,255,255,0.1);
        position: relative;
        box-sizing: content-box;
    }

    .phone-screen {
        width: 100%;
        height: 100%;
        border-radius: 32px;
        overflow: hidden;
        background: #f7f8fa;
        position: relative;
    }

    .phone-screen iframe {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    /* 刘海 */
    .phone-notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 24px;
        background: #1a1a1a;
        border-radius: 0 0 16px 16px;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .phone-notch .camera {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2a2a3a;
        border: 1px solid #333;
    }

    .phone-notch .speaker {
        width: 40px;
        height: 4px;
        border-radius: 2px;
        background: #2a2a3a;
    }

    /* 底部虚拟 HOME 条 */
    .phone-home {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 4px;
        border-radius: 2px;
        background: rgba(0,0,0,0.15);
        z-index: 10;
    }

    /* 预览居中对齐 */
    .preview-wrapper {
        display: flex;
        justify-content: center;
        padding: 20px 0;
    }

    /* 手机预览弹窗 */
    .mobile-preview-modal {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 320px; height: 640px;
        background: white;
        border-radius: 34px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        border: 10px solid #1a1a1a;
        z-index: 9997;
        display: none;
        overflow: hidden;
    }

    .mobile-preview-modal.show { display: block; }

    .mobile-preview-modal::before {
        content: '';
        position: absolute;
        top: 0; left: 50%;
        transform: translateX(-50%);
        width: 150px; height: 30px;
        background: #1a1a1a;
        border-radius: 0 0 20px 20px;
        z-index: 1;
    }

    .mobile-preview-content {
        width: 100%; height: 100%;
        border-radius: 28px;
        overflow: hidden;
        background: #fafafa;
    }

    .mobile-preview-iframe {
        width: 100%; height: 100%;
        border: none;
    }

    .mobile-preview-close {
        position: absolute;
        top: -16px; right: -16px;
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #ff4d4f;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 2;
    }

    .mobile-preview-close:hover { transform: scale(1.1) rotate(90deg); }

    .preview-fab {
        position: fixed;
        right: 20px;
        bottom: 80px;
        width: 50px; height: 50px;
        border-radius: 50%;
        background: #1890ff;
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(24,144,255,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        z-index: 9996;
        transition: all 0.3s;
    }

    .preview-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(24,144,255,0.5);
    }
</style>

<!-- TAB 切换 -->
<div class="detail-tabs">
    <button class="tab-btn active" data-tab="info">文章信息</button>
    <button class="tab-btn" data-tab="content">正文内容</button>
</div>

<!-- ===== TAB1: 文章信息 ===== -->
<div id="tab-info" class="tab-content active">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>基本信息</div>

        <div class="info-row full">
            <div>
                <div class="info-label"><label class="required">文章标题</label></div>
                <input type="hidden" id="articleId" value="<?= $articleId ?>">
                <input type="text" id="title" class="form-control" required placeholder="请输入文章标题（建议 20 字以内）"
                       value="<?= htmlspecialchars($article['title'] ?? '') ?>">
                <div class="hint">标题将显示在文章列表和详情页</div>
            </div>
        </div>

        <div class="info-row">
            <div>
                <div class="info-label"><label class="required">分类</label></div>
                <select id="category" class="form-control">
                    <option value="">请选择分类</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($article['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <div class="info-label"><label>作者</label></div>
                <input type="text" id="author" class="form-control" readonly style="background:#f5f5f5;cursor:not-allowed;"
                       value="<?= htmlspecialchars($article['author'] ?? $currentAdminName) ?>">
            </div>
            <div>
                <div class="info-label"><label>排序</label></div>
                <input type="number" id="sort" class="form-control" placeholder="数值越大越靠前" value="0"
                       value="<?= intval($article['sort'] ?? 0) ?>">
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>封面图片</div>
        <div class="info-row half">
            <div>
                <div class="piu-wrapper" id="coverUploader"></div>
                <input type="hidden" id="cover_image" value="<?= htmlspecialchars($article['cover'] ?? '') ?>">
            </div>
            <div>
                <div class="info-label"><label>文章摘要</label></div>
                <textarea id="summary" class="form-control" placeholder="200 字以内的文章摘要" maxlength="500" style="min-height:120px;"><?= htmlspecialchars($article['summary'] ?? '') ?></textarea>
                <div class="hint">摘要将显示在文章列表中</div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>标签</div>
        <div class="info-row full">
            <div>
                <div class="tag-input-row">
                    <input type="text" id="tagInput" class="form-control form-control-sm" placeholder="输入标签后点击添加">
                    <button type="button" onclick="addTag()" class="btn-add-tag">+ 添加</button>
                </div>
                <div class="tag-list" id="tagList"></div>
                <textarea id="tagsData" style="display:none;"><?= htmlspecialchars($article['tags'] ?? '') ?></textarea>
                <div class="hint">标签用于筛选和识别</div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>发布设置</div>
        <div class="info-row">
            <div>
                <div class="info-label">状态</div>
                <div class="toggle-row" onclick="toggleSwitch(this, 'status')">
                    <div class="toggle-switch <?= (!isset($article['status']) || $article['status'] == 1) ? 'on' : '' ?>" data-name="status">
                        <span class="knob"></span>
                    </div>
                    <span class="toggle-label" id="statusLabel"><?= (!isset($article['status']) || $article['status'] == 1) ? '已发布' : '下架' ?></span>
                    <input type="hidden" name="status" value="<?= (!isset($article['status']) || $article['status'] == 1) ? '1' : '0' ?>">
                </div>
            </div>
            <div>
                <div class="info-label">推荐</div>
                <div class="toggle-row" onclick="toggleSwitch(this, 'recommend')">
                    <div class="toggle-switch <?= (isset($article['is_recommend']) && $article['is_recommend'] == 1) ? 'on' : '' ?>" data-name="recommend">
                        <span class="knob"></span>
                    </div>
                    <span class="toggle-label" id="recommendLabel"><?= (isset($article['is_recommend']) && $article['is_recommend'] == 1) ? '推荐' : '不推荐' ?></span>
                    <input type="hidden" name="is_recommend" value="<?= (isset($article['is_recommend']) && $article['is_recommend'] == 1) ? '1' : '0' ?>">
                </div>
            </div>
            <div>
                <div class="info-label">发布时间</div>
                <input type="datetime-local" id="published_at" class="form-control form-control-sm"
                       value="<?= !empty($article['published_at']) ? date('Y-m-d\TH:i', strtotime($article['published_at'])) : '' ?>">
            </div>
        </div>
    </div>

    <?php if ($isEditMode): ?>
    <div class="meta-row">
        <span>创建时间：<span id="createdAt"><?= !empty($article['created_at']) ? date('Y-m-d H:i:s', strtotime($article['created_at'])) : '' ?></span></span>
        <span>更新时间：<span id="updatedAt"><?= !empty($article['updated_at']) ? date('Y-m-d H:i:s', strtotime($article['updated_at'])) : '' ?></span></span>
        <span>浏览量：<?= intval($article['views'] ?? 0) ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- ===== TAB2: 正文内容 ===== -->
<div id="tab-content" class="tab-content">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>正文内容</div>
        <div class="info-row full">
            <div>
                <div class="editor-toolbar">
                    <div class="tb-group">
                        <button type="button" title="加粗" onclick="insertEditorTag('strong')"><b>B</b></button>
                        <button type="button" title="斜体" onclick="insertEditorTag('em')"><i>I</i></button>
                        <button type="button" title="下划线" onclick="insertEditorTag('u')"><u>U</u></button>
                    </div>
                    <div class="tb-group">
                        <button type="button" title="标题" onclick="insertEditorTag('h2')">H2</button>
                        <button type="button" title="小标题" onclick="insertEditorTag('h3')">H3</button>
                        <button type="button" title="段落" onclick="insertEditorTag('p')">P</button>
                    </div>
                    <div class="tb-group">
                        <button type="button" title="引用" onclick="insertEditorTag('blockquote')">❝</button>
                        <button type="button" title="列表" onclick="insertEditorTag('li')">•</button>
                        <button type="button" title="分割线" onclick="insertAtCursor('\n<hr>\n')">—</button>
                    </div>
                    <div class="tb-group">
                        <button type="button" title="插入图片" onclick="insertEditorImage()">🖼️</button>
                        <button type="button" title="插入链接" onclick="insertEditorLink()">🔗</button>
                    </div>
                    <div class="tb-group">
                        <span class="tb-label">HTML</span>
                    </div>
                </div>
                <textarea id="content" class="form-control content-editor" placeholder="请输入文章内容...
支持 HTML 格式\n可直接粘贴 HTML"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
                <div class="hint">支持 HTML 格式，标题用 &lt;h1&gt;~&lt;h6&gt;，段落用 &lt;p&gt;，加粗用 &lt;strong&gt;，链接用 &lt;a&gt;</div>
            </div>
        </div>
    </div>
</div>

<!-- ===== TAB3: 手机预览 ===== -->
<!-- 操作按钮 -->
<div class="action-buttons">
    <?php if ($isEditMode): ?>
        <button class="btn btn-danger" onclick="deleteArticle()">🗑️ 删除</button>
    <?php endif; ?>
    <button class="btn btn-default" onclick="closeArticleDrawer()">取消</button>
    <button class="btn btn-primary" onclick="saveArticle('draft')">💾 保存草稿</button>
    <button class="btn btn-success" onclick="saveArticle('publish')">🚀 发布</button>
</div>

<!-- 加载遮罩 -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="text" id="loadingText">加载中...</div>
    </div>
</div>

<div class="auto-save-tip" id="autoSaveTip">✅ 已自动保存</div>

<!-- 标签初始化数据已移至 tagsData textarea -->
<script>
// 标签初始化数据<?= json_encode(!empty($article['tags']) ? array_filter(array_map('trim', explode(',', $article['tags']))) : []) ?>;
</script>
