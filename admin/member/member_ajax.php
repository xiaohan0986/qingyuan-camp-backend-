<?php
/**
 * 会员管理 AJAX 接口
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL); ini_set("display_errors", 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {
    $db = Database::getInstance();
    $admin = Auth::user();
} catch (Exception $e) {
    echo json_encode(['code' => -1, 'message' => '系统错误']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'detail':
        getDetail($db);
        break;
    
    case 'sendCode':
        sendVerifyCode($db);
        break;

    case 'add':
        addMember($db);
        break;


    case 'update':
        updateMember($db);
        break;

    case 'getDisableLogs':
        getDisableLogs($db);
        break;

    case 'getActivities':
        getMemberActivities($db);
        break;

    case 'getFavorites':
        echo json_encode(['code' => 0, 'data' => []]);
        break;

    case 'getFootprints':
        echo json_encode(['code' => 0, 'data' => []]);
        break;

    case 'getMemberPosts':
        getMemberPosts($db);
        break;

    case 'getMemberComments':
        echo json_encode(['code' => 0, 'data' => []]);
        break;

    case 'getMemberOrders':
        getMemberOrders($db);
        break;

    case 'getMemberCoupons':
        getMemberCoupons($db);
        break;

    case 'getMemberTransactions':
        getMemberTransactions($db);
        break;

    case 'getMemberPoints':
        getMemberPoints($db);
        break;

    case 'getFollowing':
        getFollowing($db);
        break;

    default:
        echo json_encode(['code' => -1, 'message' => '无效的操作']);
}

/**
 * 获取会员详情
 */
function getDetail($db) {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    
    try {
        // 获取会员基本信息
        $member = $db->fetchOne("SELECT * FROM members WHERE id = ?", [$id]);
        
        if (!$member) {
            echo json_encode(['code' => -1, 'message' => '用户不存在']);
            return;
        }
        
        // 检查禁用到期，自动解禁
        if ($member['status'] == 0 && !empty($member['disabled_until'])) {
            $disabledUntil = strtotime($member['disabled_until']);
            if ($disabledUntil > 0 && $disabledUntil <= time()) {
                $db->query("UPDATE members SET status = 1, disabled_until = NULL WHERE id = ?", [$id]);
                $db->query("UPDATE member_disable_logs SET status = 'unbanned', unban_time = NOW() WHERE member_id = ? AND status = 'active'", [$id]);
                $member['status'] = 1;
                $member['disabled_until'] = null;
            }
        }
        
        // 获取订单数量
        $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE member_id = ?", [$id]);
        $member['order_count'] = $orderCount['count'] ?? 0;
        
        // 社交数据（默认 0，后续可由关注/点赞表统计）
        $member['following_count'] = 0;
        $member['follower_count'] = 0;
        $member['likes_count'] = 0;
        
        // 格式化时间
        $member['created_at'] = date('Y-m-d H:i:s', strtotime($member['created_at']));
        if ($member['last_login_time']) {
            $member['last_login_time'] = date('Y-m-d H:i:s', strtotime($member['last_login_time']));
        }
        
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'member' => $member
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '查询失败：' . $e->getMessage()]);
    }
}

/**
 * 更新会员信息
 */
function updateMember($db) {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    
    $fields = ['nickname', 'phone', 'level', 'gender', 'birthday', 'status', 'avatar', 'balance', 'points', 'remark', 'disabled_until', 'disable_reason'];
    $updates = [];
    $params = [];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $updates[] = "$field = ?";
            $params[] = $_POST[$field];
        }
    }
    // 禁用时长为空字符串时设为 NULL（永久禁用/重新启用）
    if (isset($_POST['disabled_until']) && $_POST['disabled_until'] === '') {
        $updates[] = "disabled_until = NULL";
    }
    // 启用账户时自动清除禁用时长
    if (isset($_POST['status']) && $_POST['status'] == 1) {
        // disabled_until will be handled above if also sent
        $alreadySet = false;
        foreach ($updates as $u) {
            if (strpos($u, 'disabled_until') !== false) { $alreadySet = true; break; }
        }
        if (!$alreadySet) {
            $updates[] = "disabled_until = NULL";
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['code' => -1, 'message' => '没有需要更新的数据']);
        return;
    }
    
    $params[] = $id;
    $sql = "UPDATE members SET " . implode(', ', $updates) . " WHERE id = ?";
    
    try {
        $db->query($sql, $params);
        // 记录禁用操作到历史表
        if (isset($_POST['status']) && $_POST['status'] == 0) {
            $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员';
            $reason = $_POST['disable_reason'] ?? '';
            $until = !empty($_POST['disabled_until']) ? $_POST['disabled_until'] : null;
            $db->query("INSERT INTO member_disable_logs (member_id, disable_reason, disabled_at, disabled_until, operator, status) VALUES (?, ?, NOW(), ?, ?, 'active')",
                [$id, $reason, $until, $adminName]);
        }
        // 记录手动解禁
        if (isset($_POST['status']) && $_POST['status'] == 1) {
            $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? '管理员';
            $db->query("UPDATE member_disable_logs SET status = 'unbanned', unban_time = NOW(), operator = ? WHERE member_id = ? AND status = 'active'", [$adminName, $id]);
        }
        echo json_encode(['code' => 0, 'message' => '更新成功']);
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '更新失败：' . $e->getMessage()]);
    }
}


/**
 * 获取会员禁用记录
 */
function getDisableLogs($db) {
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        $logs = $db->fetchAll("SELECT * FROM member_disable_logs WHERE member_id = ? ORDER BY disabled_at DESC", [$memberId]);
        // 计算禁用时长文本
        foreach ($logs as &$log) {
            if (!empty($log['disabled_until']) && !empty($log['disabled_at'])) {
                $start = strtotime($log['disabled_at']);
                $end = strtotime($log['disabled_until']);
                $diff = $end - $start;
                if ($diff > 0) {
                    $days = floor($diff / 86400);
                    $hours = floor(($diff % 86400) / 3600);
                    if ($days >= 365) $log['duration_text'] = floor($days / 365) . '年';
                    elseif ($days >= 30) $log['duration_text'] = floor($days / 30) . '个月';
                    elseif ($days >= 7) $log['duration_text'] = floor($days / 7) . '周';
                    elseif ($days > 0) $log['duration_text'] = $days . '天 ' . $hours . '小时';
                    elseif ($hours > 0) $log['duration_text'] = $hours . '小时';
                    else $log['duration_text'] = ceil($diff / 60) . '分钟';
                } else {
                    $log['duration_text'] = '永久';
                }
            } else {
                $log['duration_text'] = '永久';
            }
        }
        echo json_encode(['code' => 0, 'data' => $logs]);
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '查询失败：' . $e->getMessage()]);
    }
}

/**
 * 新增会员
 */
function addMember($db) {
    $nickname = trim($_POST['nickname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $campus = trim($_POST['campus'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $avatar = trim($_POST['avatar'] ?? '');
    $verifyCode = trim($_POST['verify_code'] ?? '');
    
    // 表单验证
    if (empty($nickname)) {
        echo json_encode(['code' => -1, 'message' => '请输入昵称']);
        return;
    }
    if (empty($phone)) {
        echo json_encode(['code' => -1, 'message' => '请输入手机号']);
        return;
    }
    if (empty($password)) {
        echo json_encode(['code' => -1, 'message' => '请设置密码']);
        return;
    }
    if (strlen($password) < 6) {
        echo json_encode(['code' => -1, 'message' => '密码长度不能少于6位']);
        return;
    }
    if (empty($verifyCode)) {
        echo json_encode(['code' => -1, 'message' => '请输入验证码']);
        return;
    }
    
    // 验证验证码
    session_start();
    $sessionKey = 'verify_code_' . $phone;
    if (!isset($_SESSION[$sessionKey])) {
        echo json_encode(['code' => -1, 'message' => '验证码已过期，请重新发送']);
        return;
    }
    $savedCode = $_SESSION[$sessionKey];
    $codeTime = $_SESSION[$sessionKey . '_time'] ?? 0;
    // 验证码5分钟内有效
    if (time() - $codeTime > 300) {
        unset($_SESSION[$sessionKey]);
        unset($_SESSION[$sessionKey . '_time']);
        echo json_encode(['code' => -1, 'message' => '验证码已过期，请重新发送']);
        return;
    }
    if ((string)$savedCode !== $verifyCode) {
        echo json_encode(['code' => -1, 'message' => '验证码错误']);
        return;
    }
    // 验证通过后清除验证码
    unset($_SESSION[$sessionKey]);
    unset($_SESSION[$sessionKey . '_time']);
    
    // 检查手机号是否已存在
    try {
        $existing = $db->fetchOne("SELECT id FROM members WHERE phone = ?", [$phone]);
        if ($existing) {
            echo json_encode(['code' => -1, 'message' => '该手机号已被注册']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '查询失败：' . $e->getMessage()]);
        return;
    }
    
    // 密码哈希
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $data = [
        'nickname' => $nickname,
        'phone' => $phone,
        'password' => $passwordHash,
        'avatar' => $avatar,
        'campus' => $campus,
        'bio' => $bio,
        'status' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // 检查并自动添加缺失的字段
    try {
        $columns = $db->fetchAll("SHOW COLUMNS FROM members");
        $colNames = array_column($columns, 'Field');
        $missingCols = array_diff(['campus', 'bio'], $colNames);
        foreach ($missingCols as $col) {
            $type = ($col === 'bio') ? 'TEXT' : 'VARCHAR(100)';
            $db->query("ALTER TABLE members ADD COLUMN `{$col}` {$type} DEFAULT NULL COMMENT '{$col}'");
        }
    } catch (Exception $e) {
        // 列检查失败不影响主流程
    }
    
    try {
        $insertId = $db->insert('members', $data);
        echo json_encode([
            'code' => 0,
            'message' => '新增成功',
            'data' => ['id' => $insertId]
        ]);
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '新增失败：' . $e->getMessage()]);
    }
}

/**
 * 发送验证码
 */
function sendVerifyCode($db) {

/**
 * 获取会员动态（订单、登录、操作记录）
 */
function getMemberActivities($db) {
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    
    $activities = [];
    
    try {
        // 查询最近订单
        $orders = $db->fetchAll("SELECT order_no, total_amount, status, created_at FROM orders WHERE member_id = ? ORDER BY created_at DESC LIMIT 10", [$memberId]);
        foreach ($orders as $o) {
            $statusMap = [0 => '待付款', 1 => '已付款', 2 => '已发货', 3 => '已完成', 4 => '已取消'];
            $activities[] = [
                'type' => 'order',
                'content' => '下单 #' . ($o['order_no'] ?? '') . ' / ¥' . number_format($o['total_amount'], 2) . ' / ' . ($statusMap[$o['status']] ?? '未知'),
                'created_at' => $o['created_at']
            ];
        }
    } catch (Exception $e) {
        // 订单表可能不存在，忽略
    }
    
    try {
        // 查询禁用记录
        $logs = $db->fetchAll("SELECT disable_reason, disabled_at, status FROM member_disable_logs WHERE member_id = ? ORDER BY disabled_at DESC LIMIT 10", [$memberId]);
        foreach ($logs as $l) {
            $activities[] = [
                'type' => 'disable',
                'content' => $l['status'] === 'unbanned' ? '账户已解禁' : ('账户被禁用：' . ($l['disable_reason'] ?: '无原因')),
                'created_at' => $l['disabled_at']
            ];
        }
    } catch (Exception $e) {
        // 禁用日志表可能不存在，忽略
    }
    
    // 按时间排序
    usort($activities, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });
    
    echo json_encode(['code' => 0, 'data' => $activities]);
}
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) {
        echo json_encode(['code' => -1, 'message' => '请输入手机号']);
        return;
    }
    if (!preg_match('/^1\d{10}$/', $phone)) {
        echo json_encode(['code' => -1, 'message' => '手机号格式不正确']);
        return;
    }
    
    // 检查手机号是否已被注册
    try {
        $existing = $db->fetchOne("SELECT id FROM members WHERE phone = ?", [$phone]);
        if ($existing) {
            echo json_encode(['code' => -1, 'message' => '该手机号已被注册']);
            return;
        }
    } catch (Exception $e) {
        echo json_encode(['code' => -1, 'message' => '查询失败：' . $e->getMessage()]);
        return;
    }
    
    // 生成6位验证码
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // 存入 session
    session_start();
    $sessionKey = 'verify_code_' . $phone;
    $_SESSION[$sessionKey] = $code;
    $_SESSION[$sessionKey . '_time'] = time();
    
    // 在实际生产环境中，这里会调用短信 API 发送验证码
    // 开发环境直接返回验证码方便测试
    echo json_encode([
        'code' => 0,
        'message' => '验证码已发送',
        'code_value' => $code
    ]);
}

/**
 * 获取会员发布的文章（帖子）
 */
function getMemberPosts($db) {
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    
    try {
        // 先获取会员昵称
        $member = $db->fetchOne("SELECT nickname FROM members WHERE id = ?", [$memberId]);
        if (!$member) {
            echo json_encode(['code' => 0, 'data' => []]);
            return;
        }
        $nickname = $member['nickname'];
        
        // 查询 articles 表中作者匹配的文章
        $posts = $db->fetchAll(
            "SELECT id, title, summary, published_at as created_at FROM articles WHERE author = ? AND status = 1 ORDER BY published_at DESC LIMIT 20",
            [$nickname]
        );
        
        echo json_encode(['code' => 0, 'data' => $posts]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取会员订单
 */
function getMemberOrders($db) {
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        $orders = $db->fetchAll(
            "SELECT id, order_no, total_amount, pay_amount, status, created_at FROM orders WHERE member_id = ? ORDER BY created_at DESC LIMIT 20",
            [$memberId]
        );
        echo json_encode(['code' => 0, 'data' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取关注用户列表（需先创建 member_follows 表）
 * 表结构: id, follower_id(关注者), following_id(被关注者), created_at
 */
function getFollowing($db) {
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        // 关联查询关注用户的昵称、头像
        $list = $db->fetchAll(
            "SELECT m.id, m.nickname, m.avatar, f.created_at 
             FROM member_follows f 
             JOIN members m ON m.id = f.following_id 
             WHERE f.follower_id = ? 
             ORDER BY f.created_at DESC 
             LIMIT 50",
            [$memberId]
        );
        echo json_encode(['code' => 0, 'data' => $list]);
    } catch (Exception $e) {
        // 表不存在或查询失败时返回空
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取订单商品
 */
function getOrderItems($db) {
    $orderId = intval($_GET['order_id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的订单 ID']);
        return;
    }
    try {
        $items = $db->fetchAll(
            "SELECT product_name, price, quantity, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC",
            [$orderId]
        );
        echo json_encode(['code' => 0, 'data' => $items]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取用户优惠券列表
 */
function getMemberCoupons($db) {
    $conn = $db->getConnection();
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `user_coupons` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `member_id` INT UNSIGNED NOT NULL,
          `coupon_name` VARCHAR(200) NOT NULL,
          `coupon_value` DECIMAL(10,2) NOT NULL,
          `coupon_type` VARCHAR(20) DEFAULT 'cash',
          `min_amount` DECIMAL(10,2) DEFAULT 0,
          `status` VARCHAR(20) DEFAULT 'unused',
          `received_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `used_at` DATETIME,
          `valid_until` DATETIME,
          INDEX `idx_member` (`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // 插入测试数据（仅当该会员无数据时）
        $existing = $db->fetchOne("SELECT COUNT(*) as c FROM user_coupons WHERE member_id = ?", [$memberId]);
        if ($existing['c'] == 0 && $memberId > 0) {
            $conn->query("INSERT INTO user_coupons (member_id, coupon_name, coupon_value, coupon_type, min_amount, status, received_at, valid_until) VALUES 
                ($memberId, '新人满减券', 10.00, 'cash', 100.00, 'unused', '2026-06-01 10:00:00', '2026-07-31 23:59:59'),
                ($memberId, '夏日折扣券', 8.00, 'discount', 50.00, 'unused', '2026-06-10 14:30:00', '2026-07-15 23:59:59'),
                ($memberId, '无门槛红包', 5.00, 'cash', 0.00, 'used', '2026-06-15 09:00:00', '2026-06-30 23:59:59'),
                ($memberId, 'VIP专享券', 20.00, 'cash', 200.00, 'unused', '2026-06-20 16:00:00', '2026-08-01 23:59:59'),
                ($memberId, '会员日特惠', 15.00, 'cash', 150.00, 'expired', '2026-05-01 08:00:00', '2026-05-31 23:59:59')");
        }
        $list = $db->fetchAll(
            "SELECT coupon_name, coupon_value, coupon_type, min_amount, status, received_at, valid_until FROM user_coupons WHERE member_id = ? ORDER BY received_at DESC",
            [$memberId]
        );
        echo json_encode(['code' => 0, 'data' => $list]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取交易记录
 */
function getMemberTransactions($db) {
    $conn = $db->getConnection();
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `transactions` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `member_id` INT UNSIGNED NOT NULL,
          `type` VARCHAR(20) NOT NULL,
          `amount` DECIMAL(10,2) NOT NULL,
          `payment_method` VARCHAR(50),
          `balance_after` DECIMAL(10,2),
          `payee` VARCHAR(200),
          `description` VARCHAR(500),
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_member` (`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $existing = $db->fetchOne("SELECT COUNT(*) as c FROM transactions WHERE member_id = ?", [$memberId]);
        if ($existing['c'] == 0 && $memberId > 0) {
            $conn->query("INSERT INTO transactions (member_id, type, amount, payment_method, balance_after, payee, description, created_at) VALUES 
                ($memberId, 'recharge', 200.00, '微信支付', 295.00, '-', '账户充值', '2026-06-01 10:00:00'),
                ($memberId, 'payment', 89.00, '余额支付', 206.00, '青园超市', '购买：日常用品套餐', '2026-06-05 14:30:00'),
                ($memberId, 'recharge', 100.00, '支付宝', 306.00, '-', '账户充值', '2026-06-10 09:15:00'),
                ($memberId, 'payment', 25.00, '微信支付', 281.00, '青园文具店', '购买：笔记本套装', '2026-06-15 16:20:00'),
                ($memberId, 'refund', 10.00, '原路退回', 291.00, '青园超市', '退款：商品质量问题', '2026-06-18 11:00:00'),
                ($memberId, 'payment', 56.00, '余额支付', 235.00, '青园食堂', '购买：周套餐', '2026-06-22 12:00:00'),
                ($memberId, 'recharge', 60.00, '微信支付', 295.00, '-', '账户充值', '2026-06-25 08:30:00')");
        }
        $list = $db->fetchAll(
            "SELECT type, amount, payment_method, balance_after, payee, description, created_at FROM transactions WHERE member_id = ? ORDER BY created_at DESC LIMIT 50",
            [$memberId]
        );
        echo json_encode(['code' => 0, 'data' => $list]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}

/**
 * 获取积分明细
 */
function getMemberPoints($db) {
    $conn = $db->getConnection();
    $memberId = intval($_GET['member_id'] ?? 0);
    if ($memberId <= 0) {
        echo json_encode(['code' => -1, 'message' => '无效的会员 ID']);
        return;
    }
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `points_log` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `member_id` INT UNSIGNED NOT NULL,
          `change` INT NOT NULL,
          `balance_after` INT,
          `description` VARCHAR(500),
          `type` VARCHAR(20),
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_member` (`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $existing = $db->fetchOne("SELECT COUNT(*) as c FROM points_log WHERE member_id = ?", [$memberId]);
        if ($existing['c'] == 0 && $memberId > 0) {
            $conn->query("INSERT INTO points_log (member_id, change, balance_after, description, type, created_at) VALUES 
                ($memberId, 100, 100, '注册奖励', 'earn', '2026-06-01 10:00:00'),
                ($memberId, 20, 120, '每日签到', 'earn', '2026-06-02 09:00:00'),
                ($memberId, -50, 70, '兑换：青园定制笔记本', 'spend', '2026-06-08 14:00:00'),
                ($memberId, 200, 270, '订单消费奖励', 'earn', '2026-06-05 14:30:00'),
                ($memberId, 30, 300, '发表评论奖励', 'earn', '2026-06-06 10:00:00'),
                ($memberId, 15, 315, '每日签到', 'earn', '2026-06-10 09:00:00'),
                ($memberId, 10, 325, '完善个人信息', 'earn', '2026-06-12 15:00:00'),
                ($memberId, -100, 225, '兑换：青园T恤', 'spend', '2026-06-20 11:00:00'),
                ($memberId, 50, 275, '邀请好友注册奖励', 'earn', '2026-06-22 16:00:00'),
                ($memberId, 29, 304, '订单消费奖励', 'earn', '2026-06-22 12:00:00'),
                ($memberId, -30, 274, '兑换：青园马克杯', 'spend', '2026-06-24 14:00:00'),
                ($memberId, 8, 282, '每日签到', 'earn', '2026-06-25 09:00:00'),
                ($memberId, 18, 300, '商品评价奖励', 'earn', '2026-06-26 16:00:00'),
                ($memberId, 4, 304, '每日签到', 'earn', '2026-06-27 08:30:00')");
        }
        $list = $db->fetchAll(
            "SELECT change, balance_after, description, type, created_at FROM points_log WHERE member_id = ? ORDER BY created_at DESC LIMIT 50",
            [$memberId]
        );
        echo json_encode(['code' => 0, 'data' => $list]);
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'data' => []]);
    }
}
