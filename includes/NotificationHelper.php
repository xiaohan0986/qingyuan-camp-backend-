<?php
/**
 * 消息通知助手类
 * 用于创建和管理系统通知消息
 */

// 加载数据库类
require_once __DIR__ . '/Database.php';

class NotificationHelper {
    
    /**
     * 创建系统通知消息
     * 
     * @param int $userId 接收通知的用户 ID（salesmen 表或 users 表的 ID）
     * @param string $userType 用户类型：'salesman' 或 'user'
     * @param string $type 消息类型：system, customer, article, position, finance, security
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param string $category 消息分类
     * @param array $extraData 额外数据（用于跳转等）
     * @return bool 是否创建成功
     */
    public static function createNotification($userId, $userType, $type, $title, $content, $category = 'system', $extraData = []) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 插入通知记录
            $stmt = $pdo->prepare("
                INSERT INTO system_notifications (
                    user_id,
                    user_type,
                    type,
                    icon,
                    title,
                    content,
                    category,
                    extra_data,
                    is_read,
                    created_at
                ) VALUES (
                    :user_id,
                    :user_type,
                    :type,
                    :icon,
                    :title,
                    :content,
                    :category,
                    :extra_data,
                    0,
                    NOW()
                )
            ");
            
            // 根据类型设置图标
            $iconMap = [
                'system' => '📊',
                'customer' => '👥',
                'article' => '📰',
                'position' => '💼',
                'finance' => '💰',
                'security' => '🔒'
            ];
            $icon = $iconMap[$type] ?? '🔔';
            
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType,
                ':type' => $type,
                ':icon' => $icon,
                ':title' => $title,
                ':content' => $content,
                ':category' => $category,
                ':extra_data' => !empty($extraData) ? json_encode($extraData, JSON_UNESCAPED_UNICODE) : null
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("[Notification] 创建通知失败：" . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送客户转移通知给销售人员
     * 
     * @param int $salesmanId 销售人员 ID
     * @param string $customerName 客户姓名
     * @param int $customerId 客户 ID
     * @param string $fromUserName 原负责人姓名
     * @param string $salesmanName 销售人员姓名
     * @param string $salesmanLevel 销售人员等级（如：高级、初级、资深等）
     * @return bool 是否发送成功
     */
    public static function notifyCustomerTransfer($salesmanId, $customerName, $customerId, $fromUserName = '', $salesmanName = '', $salesmanLevel = '') {
        $title = '👥 新客户分配通知';
        
        // 构建通知内容，高亮显示关键信息
        $highlightStyle = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 8px; border-radius: 4px; font-weight: 600;';
        
        $content = '尊敬的';
        
        // 添加等级（如果有）
        if (!empty($salesmanLevel)) {
            $content .= '<span style="' . $highlightStyle . '">' . $salesmanLevel . '</span>';
        }
        
        $content .= '业务员';
        
        // 添加姓名
        if (!empty($salesmanName)) {
            $content .= '<span style="' . $highlightStyle . '">' . $salesmanName . '</span>';
        } else {
            // 如果没传姓名，尝试从数据库获取
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();
                $stmt = $pdo->prepare("SELECT name, level FROM salesmen WHERE id = :id");
                $stmt->execute([':id' => $salesmanId]);
                $salesman = $stmt->fetch();
                if ($salesman) {
                    if (empty($salesmanLevel) && !empty($salesman['level'])) {
                        $content .= '<span style="' . $highlightStyle . '">' . $salesman['level'] . '</span>';
                    }
                    $content .= '业务员<span style="' . $highlightStyle . '">' . $salesman['name'] . '</span>';
                } else {
                    $content .= '业务员';
                }
            } catch (Exception $e) {
                $content .= '业务员';
            }
        }
        
        $content .= '您好，有新的客户<span style="' . $highlightStyle . '">' . $customerName . '</span>通过管理员已分配给您（来自：青园营地）';
        
        $extraData = [
            'customer_id' => $customerId,
            'action' => 'view_customer',
            'salesman_id' => $salesmanId,
            'salesman_name' => $salesmanName,
            'customer_name' => $customerName
        ];
        
        return self::createNotification(
            $salesmanId,
            'salesman',
            'customer',
            $title,
            $content,
            'customer',
            $extraData
        );
    }
    
    /**
     * 获取用户的未读消息数量
     * 
     * @param int $userId 用户 ID
     * @param string $userType 用户类型
     * @return int 未读消息数量
     */
    public static function getUnreadCount($userId, $userType) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM system_notifications 
                WHERE user_id = :user_id 
                AND user_type = :user_type 
                AND is_read = 0
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);
            
            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
            
        } catch (PDOException $e) {
            error_log("[Notification] 获取未读数量失败：" . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 获取用户的消息列表
     * 
     * @param int $userId 用户 ID
     * @param string $userType 用户类型
     * @param int $limit 限制数量
     * @return array 消息列表
     */
    public static function getUserMessages($userId, $userType, $limit = 10) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT * 
                FROM system_notifications 
                WHERE user_id = :user_id 
                AND user_type = :user_type 
                ORDER BY created_at DESC 
                LIMIT :limit
            ");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_type', $userType, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 解析 extra_data
            foreach ($messages as &$msg) {
                if (!empty($msg['extra_data'])) {
                    $msg['extra_data'] = json_decode($msg['extra_data'], true);
                }
            }
            
            return $messages;
            
        } catch (PDOException $e) {
            error_log("[Notification] 获取消息列表失败：" . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 标记消息为已读
     * 
     * @param int $messageId 消息 ID
     * @return bool 是否成功
     */
    public static function markAsRead($messageId) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                UPDATE system_notifications 
                SET is_read = 1 
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $messageId]);
            return true;
            
        } catch (PDOException $e) {
            error_log("[Notification] 标记已读失败：" . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 批量标记用户消息为已读
     * 
     * @param int $userId 用户 ID
     * @param string $userType 用户类型
     * @return bool 是否成功
     */
    public static function markAllAsRead($userId, $userType) {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                UPDATE system_notifications 
                SET is_read = 1 
                WHERE user_id = :user_id 
                AND user_type = :user_type
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':user_type' => $userType
            ]);
            return true;
            
        } catch (PDOException $e) {
            error_log("[Notification] 批量标记已读失败：" . $e->getMessage());
            return false;
        }
    }
}
