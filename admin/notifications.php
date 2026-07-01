<?php
/**
 * 消息通知页面
 */
require_once __DIR__ . '/includes/header.php';

$pageTitle = '消息通知';
$currentPage = 'notifications';

// 消息数据（从数据库读取）
$messages = [];

// 统计
$totalCount = count($messages);
$unreadCount = count(array_filter($messages, function($m) { return !$m['is_read']; }));

// 分类统计
$categoryStats = [
    'all' => $totalCount,
    'system' => count(array_filter($messages, function($m) { return $m['category'] === 'system'; })),
    'customer' => count(array_filter($messages, function($m) { return $m['category'] === 'customer'; })),
    'article' => count(array_filter($messages, function($m) { return $m['category'] === 'article'; })),
    'position' => count(array_filter($messages, function($m) { return $m['category'] === 'position'; })),
    'finance' => count(array_filter($messages, function($m) { return $m['category'] === 'finance'; })),
    'security' => count(array_filter($messages, function($m) { return $m['category'] === 'security'; }))
];

// 当前筛选
$currentFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if ($currentFilter === 'unread') {
    $filteredMessages = array_filter($messages, function($m) { return !$m['is_read']; });
} else {
    $filteredMessages = $currentFilter === 'all' ? $messages : array_filter($messages, function($m) use ($currentFilter) { return $m['category'] === $currentFilter; });
}

// 辅助函数：分类名称
function getCategoryName($category) {
    $names = [
        'system' => '系统通知',
        'customer' => '客户动态',
        'article' => '文章更新',
        'position' => '岗位提醒',
        'finance' => '财务通知',
        'security' => '安全提醒'
    ];
    return isset($names[$category]) ? $names[$category] : $category;
}
?>

<style>
.notifications-container {
    width: 100%;
    margin: 0;
    padding: 24px;
}

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
    display: flex;
    align-items: center;
    gap: 12px;
}

.unread-badge {
    background: linear-gradient(135deg, #ff4d4f, #ff7875);
    color: white;
    font-size: 14px;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.page-actions {
    display: flex;
    gap: 12px;
}

/* 分类筛选 */
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    background: white;
    padding: 16px 24px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    color: #595959;
    border: 1px solid transparent;
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.filter-tab:hover {
    background: #f5f5f5;
}

.filter-tab.active {
    background: #e6f4ff;
    color: #1890ff;
    border-color: #1890ff;
}

.filter-tab .count {
    font-size: 12px;
    padding: 2px 6px;
    background: #f5f5f5;
    border-radius: 10px;
    margin-left: 4px;
}

.filter-tab.active .count {
    background: #1890ff;
    color: white;
}

/* 消息列表 */
.messages-container {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.message-item {
    display: flex;
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.message-item:last-child {
    border-bottom: none;
}

.message-item:hover {
    background: #fafafa;
}

.message-item.unread {
    background: #f0f5ff;
}

.message-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #1890ff, #096dd9);
}

.message-checkbox {
    margin-right: 16px;
    display: flex;
    align-items: center;
}

.message-icon {
    font-size: 32px;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 12px;
    margin-right: 16px;
    flex-shrink: 0;
}

.message-content {
    flex: 1;
    min-width: 0;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.message-title {
    font-size: 16px;
    font-weight: 600;
    color: #262626;
    margin: 0;
}

.message-time {
    font-size: 13px;
    color: #8c8c8c;
    white-space: nowrap;
    margin-left: 12px;
}

.message-text {
    font-size: 14px;
    color: #595959;
    line-height: 1.6;
    margin: 0 0 8px 0;
}

.message-meta {
    display: flex;
    gap: 12px;
    align-items: center;
}

.message-category {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    background: #f5f5f5;
    color: #8c8c8c;
}

.message-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s;
}

.message-item:hover .message-actions {
    opacity: 1;
}

.message-action-btn {
    padding: 4px 12px;
    border-radius: 6px;
    border: 1px solid #d9d9d9;
    background: white;
    color: #595959;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.message-action-btn:hover {
    border-color: #1890ff;
    color: #1890ff;
    background: #f0f5ff;
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 80px 24px;
    color: #8c8c8c;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 18px;
    font-weight: 600;
    color: #262626;
    margin-bottom: 8px;
}

.empty-state-desc {
    font-size: 14px;
}

/* 批量操作栏 */
.batch-action-bar {
    display: none;
    position: sticky;
    top: 0;
    background: #1890ff;
    color: white;
    padding: 12px 24px;
    z-index: 100;
    align-items: center;
    justify-content: space-between;
}

.batch-action-bar.active {
    display: flex;
}

.batch-info {
    font-size: 14px;
}

.batch-actions {
    display: flex;
    gap: 12px;
}

.batch-btn {
    padding: 6px 16px;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    background: transparent;
    color: white;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
}

.batch-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* 分页 */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    border-top: 1px solid #f0f0f0;
}

.pagination-btn {
    padding: 8px 16px;
    border: 1px solid #d9d9d9;
    background: white;
    border-radius: 8px;
    color: #595959;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    font-size: 14px;
}

.pagination-btn:hover {
    border-color: #1890ff;
    color: #1890ff;
}

.pagination-btn.active {
    background: #1890ff;
    color: white;
    border-color: #1890ff;
}
</style>

<div class="notifications-container">
    <!-- 页面头部 -->
    <div class="page-header">
        <div class="page-title">
            📬 消息通知
            <span class="unread-badge" id="unreadBadge"><?php echo $unreadCount; ?> 条未读</span>
        </div>
        <div class="page-actions">
            <button class="btn" onclick="markAllRead()" style="background: #f5f5f5; color: #262626; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                📖 全部已读
            </button>
            <button class="btn" onclick="deleteRead()" style="background: #fff2f0; color: #ff4d4f; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                清空已读
            </button>
        </div>
    </div>
    
    <!-- 分类筛选 -->
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">
            📋 全部消息
            <span class="count"><?php echo $categoryStats['all']; ?></span>
        </a>
        <a href="?filter=unread" class="filter-tab <?php echo $currentFilter === 'unread' ? 'active' : ''; ?>">
            🔔 未读消息
            <span class="count"><?php echo $unreadCount; ?></span>
        </a>
        <a href="?filter=system" class="filter-tab <?php echo $currentFilter === 'system' ? 'active' : ''; ?>">
            📢 系统通知
            <span class="count"><?php echo $categoryStats['system']; ?></span>
        </a>
        <a href="?filter=customer" class="filter-tab <?php echo $currentFilter === 'customer' ? 'active' : ''; ?>">
            👥 客户动态
            <span class="count"><?php echo $categoryStats['customer']; ?></span>
        </a>
        <a href="?filter=article" class="filter-tab <?php echo $currentFilter === 'article' ? 'active' : ''; ?>">
            📰 文章更新
            <span class="count"><?php echo $categoryStats['article']; ?></span>
        </a>
        <a href="?filter=position" class="filter-tab <?php echo $currentFilter === 'position' ? 'active' : ''; ?>">
            💼 岗位提醒
            <span class="count"><?php echo $categoryStats['position']; ?></span>
        </a>
        <a href="?filter=finance" class="filter-tab <?php echo $currentFilter === 'finance' ? 'active' : ''; ?>">
            💰 财务通知
            <span class="count"><?php echo $categoryStats['finance']; ?></span>
        </a>
        <a href="?filter=security" class="filter-tab <?php echo $currentFilter === 'security' ? 'active' : ''; ?>">
            🔒 安全提醒
            <span class="count"><?php echo $categoryStats['security']; ?></span>
        </a>
    </div>
    
    <!-- 批量操作栏 -->
    <div class="batch-action-bar" id="batchActionBar">
        <div class="batch-info">已选择 <span id="selectedCount">0</span> 条消息</div>
        <div class="batch-actions">
            <button class="batch-btn" onclick="markSelectedRead()">✅ 标记已读</button>
            <button class="batch-btn" onclick="deleteSelected()">删除</button>
            <button class="batch-btn" onclick="cancelSelection()">❌ 取消选择</button>
        </div>
    </div>
    
    <!-- 消息列表 -->
    <div class="messages-container" id="messagesContainer">
        <?php if (empty($filteredMessages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-title">暂无消息</div>
                <div class="empty-state-desc">
                    <?php echo $currentFilter === 'unread' ? '所有未读消息已处理完毕' : '还没有消息通知'; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($filteredMessages as $msg): ?>
                <div class="message-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $msg['id']; ?>" onclick="navigateToDetail('<?php echo $msg['id']; ?>')">
                    <div class="message-checkbox">
                        <input type="checkbox" class="message-checkbox-input" value="<?php echo $msg['id']; ?>" onclick="event.stopPropagation()">
                    </div>
                    <div class="message-icon"><?php echo $msg['icon']; ?></div>
                    <div class="message-content">
                        <div class="message-header">
                            <h4 class="message-title"><?php echo htmlspecialchars($msg['title']); ?></h4>
                            <span class="message-time"><?php echo $msg['time']; ?></span>
                        </div>
                        <p class="message-text"><?php echo htmlspecialchars($msg['content']); ?></p>
                        <div class="message-meta">
                            <span class="message-category"><?php echo getCategoryName($msg['category']); ?></span>
                            <div class="message-actions">
                                <?php if (!$msg['is_read']): ?>
                                    <button class="message-action-btn" onclick="markAsRead('<?php echo $msg['id']; ?>', event)">📖 标记已读</button>
                                <?php endif; ?>
                                <button class="message-action-btn" onclick="deleteMessage('<?php echo $msg['id']; ?>', event)">删除</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- 分页 -->
            <div class="pagination">
                <a href="#" class="pagination-btn">上一页</a>
                <a href="#" class="pagination-btn active">1</a>
                <a href="#" class="pagination-btn">2</a>
                <a href="#" class="pagination-btn">3</a>
                <a href="#" class="pagination-btn">下一页</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 跳转到详情页（全局函数）
function navigateToDetail(messageId) {
    console.log('跳转到详情页:', messageId);
    window.location.href = 'notification_detail.php?id=' + messageId;
}

// 复选框选择
let selectedMessages = new Set();

document.querySelectorAll('.message-checkbox-input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const messageId = this.value;
        if (this.checked) {
            selectedMessages.add(messageId);
        } else {
            selectedMessages.delete(messageId);
        }
        updateBatchActionBar();
    });
});

function updateBatchActionBar() {
    const batchActionBar = document.getElementById('batchActionBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedMessages.size > 0) {
        batchActionBar.classList.add('active');
        selectedCount.textContent = selectedMessages.size;
    } else {
        batchActionBar.classList.remove('active');
    }
}

function cancelSelection() {
    selectedMessages.clear();
    document.querySelectorAll('.message-checkbox-input').forEach(cb => cb.checked = false);
    updateBatchActionBar();
}

// 标记单条已读
function markAsRead(messageId, event) {
    event.stopPropagation();
    const messageItem = document.querySelector(`.message-item[data-id="${messageId}"]`);
    if (messageItem) {
        messageItem.classList.remove('unread');
        messageItem.querySelector('.message-action-btn')?.remove();
        updateUnreadCount();
        saveReadMessage(messageId);
    }
}

// 标记选中已读
function markSelectedRead() {
    selectedMessages.forEach(messageId => {
        const messageItem = document.querySelector(`.message-item[data-id="${messageId}"]`);
        if (messageItem) {
            messageItem.classList.remove('unread');
            saveReadMessage(messageId);
        }
    });
    updateUnreadCount();
    cancelSelection();
}

// 删除单条
function deleteMessage(messageId, event) {
    event.stopPropagation();
    if (confirm('确定要删除这条消息吗？')) {
        const messageItem = document.querySelector(`.message-item[data-id="${messageId}"]`);
        if (messageItem) {
            messageItem.style.opacity = '0';
            messageItem.style.transform = 'translateX(-20px)';
            setTimeout(() => messageItem.remove(), 300);
        }
    }
}

// 删除选中
function deleteSelected() {
    if (confirm(`确定要删除选中的 ${selectedMessages.size} 条消息吗？`)) {
        selectedMessages.forEach(messageId => {
            const messageItem = document.querySelector(`.message-item[data-id="${messageId}"]`);
            if (messageItem) {
                messageItem.style.opacity = '0';
                messageItem.style.transform = 'translateX(-20px)';
                setTimeout(() => messageItem.remove(), 300);
            }
        });
        updateUnreadCount();
        cancelSelection();
    }
}

// 全部已读
function markAllRead() {
    if (confirm('确定要将所有消息标记为已读吗？')) {
        document.querySelectorAll('.message-item.unread').forEach(item => {
            item.classList.remove('unread');
            saveReadMessage(item.getAttribute('data-id'));
        });
        updateUnreadCount();
    }
}

// 删除已读
function deleteRead() {
    if (confirm('确定要清空所有已读消息吗？此操作不可恢复！')) {
        document.querySelectorAll('.message-item:not(.unread)').forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            setTimeout(() => item.remove(), 300);
        });
        updateUnreadCount();
    }
}

// 更新未读数量显示
function updateUnreadCount() {
    const unreadCount = document.querySelectorAll('.message-item.unread').length;
    document.getElementById('unreadBadge').textContent = unreadCount + ' 条未读';
    
    // 保存到 localStorage
    localStorage.setItem('notificationUnreadCount', unreadCount);
}

// 保存已读状态
function saveReadMessage(messageId) {
    const readMessages = JSON.parse(localStorage.getItem('readMessages') || '[]');
    if (!readMessages.includes(messageId)) {
        readMessages.push(messageId);
        localStorage.setItem('readMessages', JSON.stringify(readMessages));
    }
}

// 页面加载时同步未读状态
document.addEventListener('DOMContentLoaded', function() {
    const readMessages = JSON.parse(localStorage.getItem('readMessages') || '[]');
    readMessages.forEach(messageId => {
        const item = document.querySelector(`.message-item[data-id="${messageId}"]`);
        if (item) {
            item.classList.remove('unread');
        }
    });
    updateUnreadCount();
    
    console.log('消息列表已初始化');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';
