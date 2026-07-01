<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

$memberId = intval($_GET['member_id'] ?? 0);
if ($memberId <= 0) die('请指定会员 ID，例如: member_data_init.php?member_id=5');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<h2>会员数据初始化</h2><p>目标会员 ID: {$memberId}</p><hr>";

    // 1. user_coupons
    $conn->query("CREATE TABLE IF NOT EXISTS user_coupons (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      member_id INT UNSIGNED NOT NULL,
      coupon_name VARCHAR(200) NOT NULL,
      coupon_value DECIMAL(10,2) NOT NULL,
      min_amount DECIMAL(10,2) DEFAULT 0,
      status VARCHAR(20) DEFAULT 'unused',
      valid_until DATETIME,
      INDEX idx_member (member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ user_coupons 表创建成功<br>";

    // 2. transactions
    $conn->query("CREATE TABLE IF NOT EXISTS transactions (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      member_id INT UNSIGNED NOT NULL,
      type VARCHAR(20) NOT NULL,
      amount DECIMAL(10,2) NOT NULL,
      payment_method VARCHAR(50),
      balance_after DECIMAL(10,2),
      payee VARCHAR(200),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_member (member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ transactions 表创建成功<br>";

    // 3. points_log
    $conn->query("CREATE TABLE IF NOT EXISTS points_log (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      member_id INT UNSIGNED NOT NULL,
      change INT NOT NULL,
      balance_after INT,
      description VARCHAR(500),
      type VARCHAR(20),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_member (member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ points_log 表创建成功<br><hr>";

    // 插入优惠券
    $ck = $db->fetchOne("SELECT COUNT(*) as c FROM user_coupons WHERE member_id = ?", [$memberId]);
    if ($ck['c'] == 0) {
        $conn->query("INSERT INTO user_coupons VALUES
            (NULL,$memberId,'新人满减券',10.00,100.00,'unused','2026-07-31'),
            (NULL,$memberId,'夏日折扣券',8.00,50.00,'unused','2026-07-15'),
            (NULL,$memberId,'无门槛红包',5.00,0.00,'used','2026-06-30'),
            (NULL,$memberId,'VIP专享券',20.00,200.00,'unused','2026-08-01'),
            (NULL,$memberId,'会员日特惠',15.00,150.00,'expired','2026-05-31')");
        echo "✅ 插入 5 条优惠券<br>";
    } else echo "⏩ 优惠券已存在 {$ck['c']} 条<br>";

    // 插入交易记录
    $ck = $db->fetchOne("SELECT COUNT(*) as c FROM transactions WHERE member_id = ?", [$memberId]);
    if ($ck['c'] == 0) {
        $conn->query("INSERT INTO transactions VALUES
            (NULL,$memberId,'recharge',200.00,'微信支付',295.00,'-','2026-06-01 10:00:00'),
            (NULL,$memberId,'payment',89.00,'余额支付',206.00,'青园超市','2026-06-05 14:30:00'),
            (NULL,$memberId,'recharge',100.00,'支付宝',306.00,'-','2026-06-10 09:15:00'),
            (NULL,$memberId,'payment',25.00,'微信支付',281.00,'青园文具店','2026-06-15 16:20:00'),
            (NULL,$memberId,'refund',10.00,'原路退回',291.00,'青园超市','2026-06-18 11:00:00'),
            (NULL,$memberId,'payment',56.00,'余额支付',235.00,'青园食堂','2026-06-22 12:00:00'),
            (NULL,$memberId,'recharge',60.00,'微信支付',295.00,'-','2026-06-25 08:30:00')");
        echo "✅ 插入 7 条交易记录<br>";
    } else echo "⏩ 交易记录已存在 {$ck['c']} 条<br>";

    // 插入积分明细
    $ck = $db->fetchOne("SELECT COUNT(*) as c FROM points_log WHERE member_id = ?", [$memberId]);
    if ($ck['c'] == 0) {
        $conn->query("INSERT INTO points_log VALUES
            (NULL,$memberId,100,100,'注册奖励','earn','2026-06-01 10:00:00'),
            (NULL,$memberId,20,120,'每日签到','earn','2026-06-02 09:00:00'),
            (NULL,$memberId,-50,70,'兑换笔记本','spend','2026-06-08 14:00:00'),
            (NULL,$memberId,200,270,'订单消费奖励','earn','2026-06-05 14:30:00'),
            (NULL,$memberId,30,300,'发表评论奖励','earn','2026-06-06 10:00:00'),
            (NULL,$memberId,15,315,'每日签到','earn','2026-06-10 09:00:00'),
            (NULL,$memberId,10,325,'完善个人信息','earn','2026-06-12 15:00:00'),
            (NULL,$memberId,-100,225,'兑换T恤','spend','2026-06-20 11:00:00'),
            (NULL,$memberId,50,275,'邀请好友注册奖励','earn','2026-06-22 16:00:00'),
            (NULL,$memberId,29,304,'订单消费奖励','earn','2026-06-22 12:00:00')");
        echo "✅ 插入 10 条积分明细<br>";
    } else echo "⏩ 积分明细已存在 {$ck['c']} 条<br>";

    echo "<hr><p style='color:green;font-size:18px;font-weight:bold;'>✅ 初始化完成！</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ 失败: " . $e->getMessage() . "</p>";
}
