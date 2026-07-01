<?php
/**
 * 消息详情页
 */
require_once __DIR__ . '/includes/header.php';

$messageId = (int)($_GET['id'] ?? 0);

if (!$messageId) {
    echo "<script>alert('消息 ID 无效'); window.location.href='dashboard.php';</script>";
    exit;
}

// 获取消息详情
try {
    require_once __DIR__ . '/../includes/NotificationHelper.php';
    require_once __DIR__ . '/../includes/Database.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM system_notifications WHERE id = :id");
    $stmt->execute([':id' => $messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo "<script>alert('消息不存在'); window.location.href='dashboard.php';</script>";
        exit;
    }
    
    // 标记为已读
    if (!$message['is_read']) {
        NotificationHelper::markAsRead($messageId);
        $message['is_read'] = 1;
    }
    
    // 解析 extra_data
    if (!empty($message['extra_data'])) {
        $message['extra_data'] = json_decode($message['extra_data'], true);
    }
    
} catch (Exception $e) {
    echo "<script>alert('加载消息失败：" . $e->getMessage() . "'); window.location.href='dashboard.php';</script>";
    exit;
}

$pageTitle = $message['title'];
$currentPage = 'notification';
?>

<style>
.notification-detail {
    max-width: 800px;
    margin: 40px auto;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.notification-header {
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 20px;
}

.notification-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.notification-title {
    font-size: 24px;
    font-weight: 700;
    color: #262626;
    margin: 0 0 12px 0;
}

.notification-meta {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #8c8c8c;
}

.notification-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.notification-body {
    font-size: 16px;
    line-height: 1.8;
    color: #262626;
    padding: 20px 0;
}

.notification-actions {
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-default {
    background: #f5f5f5;
    color: #262626;
}

.btn-default:hover {
    background: #e8e8e8;
}

.btn-danger {
    background: #ff4d4f;
    color: white;
}

.btn-danger:hover {
    background: #ff7875;
}

.notification-extra {
    margin-top: 20px;
    padding: 16px;
    background: #fafafa;
    border-radius: 8px;
}

.notification-extra-title {
    font-size: 14px;
    font-weight: 600;
    color: #8c8c8c;
    margin-bottom: 8px;
}

.tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    background: #f0f5ff;
    color: #1890ff;
}

.read-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.read-status.read {
    background: #f6ffed;
    color: #52c41a;
}

.read-status.unread {
    background: #fff1f0;
    color: #f5222d;
}
</style>

<div class="notification-detail">
    <div class="notification-header">
        <div class="notification-icon"><?php echo htmlspecialchars($message['icon'] ?: '🔔'); ?></div>
        <h1 class="notification-title"><?php echo htmlspecialchars($message['title']); ?></h1>
        
        <div class="notification-meta">
            <div class="notification-meta-item">
                <span>📁</span>
                <span>分类：<?php echo htmlspecialchars($message['category']); ?></span>
            </div>
            <div class="notification-meta-item">
                <span>📅</span>
                <span><?php echo $message['created_at']; ?></span>
            </div>
            <div class="notification-meta-item">
                <span>📊</span>
                <span class="read-status <?php echo $message['is_read'] ? 'read' : 'unread'; ?>">
                    <?php echo $message['is_read'] ? '✅ 已读' : '🔴 未读'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="notification-body" style="line-height: 2;">
        <?php 
        // 内容包含 HTML，直接输出但进行 XSS 过滤
        $content = $message['content'];
        // 允许特定的 HTML 标签（span）
        $content = preg_replace('/<span[^>]*>/', '<span>', $content);
        echo $content;
        ?>
    </div>
    
    <?php if (!empty($message['extra_data'])): ?>
    <div class="notification-extra">
        <div class="notification-extra-title">📋 附加信息</div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <?php foreach ($message['extra_data'] as $key => $value): ?>
                <span class="tag"><?php echo htmlspecialchars($key); ?>: <?php echo htmlspecialchars(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="notification-actions">
        <?php if (!empty($message['extra_data']['customer_id'])): ?>
            <a href="customer.php?action=edit&id=<?php echo $message['extra_data']['customer_id']; ?>" class="btn btn-primary">
                👥 查看客户
            </a>
        <?php endif; ?>
        
        <a href="javascript:window.location.reload()" class="btn btn-default">
            🔄 刷新
        </a>
        
        <a href="dashboard.php" class="btn btn-default">
            📊 返回首页
        </a>
        
        <a href="javascript:deleteMessage(<?php echo $messageId; ?>)" class="btn btn-danger">
            删除消息
        </a>
    </div>
</div>

<script>
// 删除消息
function deleteMessage(id) {
    if (!confirm('确定要删除这条消息吗？')) return;
    
    fetch('../api/notification.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert('删除成功');
            window.location.href = 'dashboard.php';
        } else {
            alert('删除失败：' + res.message);
        }
    })
    .catch(err => {
        console.error('删除失败:', err);
        alert('删除失败，请重试');
    });
}

// 页面加载时自动刷新未读数量
fetch('../api/notification.php?action=unread_count')
    .then(res => res.json())
    .then(res => {
        if (res.code === 200 && window.opener) {
            // 如果是从弹窗打开，通知父窗口更新
            window.opener.postMessage({type: 'notification_update', count: res.data.count}, '*');
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php';
