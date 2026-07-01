<?php
/**
 * 更新办理进度并发送订阅消息
 * 
 * 接收 POST 请求：
 * - order_id: 订单 ID
 * - stage: 进度阶段
 * - content: 进度内容
 * - tags: 需补充材料标签（可选）
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SubscribeMessageSender.php';

header('Content-Type: application/json; charset=utf-8');

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 405, 'message' => '请求方法不允许']);
    exit;
}

// 获取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['code' => 400, 'message' => '无效的请求数据']);
    exit;
}

$order_id = $data['order_id'] ?? null;
$stage = $data['stage'] ?? null;
$content = $data['content'] ?? '';
$tags = $data['tags'] ?? [];

if (!$order_id || !$stage) {
    echo json_encode(['code' => 400, 'message' => '缺少必要参数：order_id, stage']);
    exit;
}

try {
    // 连接数据库
    $db = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 开启事务
    $db->beginTransaction();
    
    // 1. 获取订单信息
    $stmt = $db->prepare("SELECT user_id, order_no FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('订单不存在');
    }
    
    // 2. 获取用户信息（包括 openid）
    $stmt = $db->prepare("SELECT openid, phone FROM users WHERE id = ?");
    $stmt->execute([$order['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['openid']) {
        throw new Exception('用户不存在或未绑定微信');
    }
    
    // 3. 插入进度记录
    $stmt = $db->prepare("
        INSERT INTO order_progress (order_id, stage, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$order_id, $stage, $content]);
    $progress_id = $db->lastInsertId();
    
    // 4. 如果需要补充材料，插入材料标签
    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $stmt = $db->prepare("
                INSERT INTO order_materials (order_id, progress_id, material_name, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$order_id, $progress_id, $tag]);
        }
    }
    
    // 提交事务
    $db->commit();
    
    // 5. 发送订阅消息（异步，不影响主流程）
    try {
        // 读取订阅配置
        $configFile = __DIR__ . '/../config/subscribe.json';
        if (file_exists($configFile)) {
            $subscribeConfig = json_decode(file_get_contents($configFile), true);
            
            // 检查是否启用订阅推送
            if (($subscribeConfig['enabled'] ?? '0') === '1') {
                // 读取小程序配置
                $miniprogramConfigFile = __DIR__ . '/../config/miniprogram.json';
                if (file_exists($miniprogramConfigFile)) {
                    $miniprogramConfig = json_decode(file_get_contents($miniprogramConfigFile), true);
                    
                    $appId = $miniprogramConfig['wechat_appid'] ?? '';
                    $appSecret = $miniprogramConfig['wechat_secret'] ?? '';
                    $templateId = $subscribeConfig['template_id'] ?? '';
                    
                    if ($appId && $appSecret && $templateId) {
                        $sender = new SubscribeMessageSender($appId, $appSecret, $templateId);
                        
                        // 构造进度数据
                        $progressData = [
                            'progress_name' => $stage,
                            'content' => mb_substr($content, 0, 20, 'utf-8') . (mb_strlen($content, 'utf-8') > 20 ? '...' : ''),
                            'update_time' => date('Y-m-d H:i:s')
                        ];
                        
                        // 如果有材料标签，添加到备注
                        if (!empty($tags)) {
                            $progressData['remark'] = '需补充材料：' . implode('、', $tags);
                        }
                        
                        // 发送消息
                        $result = $sender->sendProgressNotification($user['openid'], $progressData);
                        
                        // 记录发送日志
                        if (isset($result['errcode']) && $result['errcode'] == 0) {
                            error_log("订阅消息发送成功：openid={$user['openid']}, order_id={$order_id}");
                        } else {
                            error_log("订阅消息发送失败：openid={$user['openid']}, error=" . ($result['errmsg'] ?? 'unknown'));
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // 订阅消息发送失败不影响主流程，只记录日志
        error_log("发送订阅消息异常：" . $e->getMessage());
    }
    
    // 返回成功结果
    echo json_encode([
        'code' => 200,
        'message' => '进度更新成功',
        'data' => [
            'progress_id' => $progress_id,
            'order_id' => $order_id,
            'stage' => $stage,
            'content' => $content
        ]
    ]);
    
} catch (Exception $e) {
    // 回滚事务
    if (isset($db)) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackEx) {
            // 忽略回滚错误
        }
    }
    
    echo json_encode([
        'code' => 500,
        'message' => '更新失败：' . $e->getMessage()
    ]);
}
