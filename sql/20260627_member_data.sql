-- 运行此 SQL 前请先确认赵六的 member_id
-- 1. 创建表
CREATE TABLE IF NOT EXISTS `user_coupons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT UNSIGNED NOT NULL COMMENT '会员 ID',
  `coupon_name` VARCHAR(200) NOT NULL COMMENT '优惠券名称',
  `coupon_value` DECIMAL(10,2) NOT NULL COMMENT '优惠金额/折扣值',
  `coupon_type` VARCHAR(20) DEFAULT 'cash' COMMENT '类型：cash(满减)/discount(折扣)',
  `min_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '最低消费金额',
  `status` VARCHAR(20) DEFAULT 'unused' COMMENT '状态：unused/used/expired',
  `received_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '领取时间',
  `used_at` DATETIME COMMENT '使用时间',
  `valid_until` DATETIME COMMENT '有效期至',
  INDEX `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT UNSIGNED NOT NULL COMMENT '会员 ID',
  `type` VARCHAR(20) NOT NULL COMMENT '类型：payment(消费)/recharge(充值)/refund(退款)',
  `amount` DECIMAL(10,2) NOT NULL COMMENT '金额',
  `payment_method` VARCHAR(50) COMMENT '支付方式',
  `balance_after` DECIMAL(10,2) COMMENT '交易后余额',
  `payee` VARCHAR(200) COMMENT '收款方',
  `description` VARCHAR(500) COMMENT '交易说明',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='交易记录表';

CREATE TABLE IF NOT EXISTS `points_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT UNSIGNED NOT NULL COMMENT '会员 ID',
  `change` INT NOT NULL COMMENT '变动数量',
  `balance_after` INT COMMENT '变动后积分',
  `description` VARCHAR(500) COMMENT '变动说明',
  `type` VARCHAR(20) COMMENT '类型：earn(获得)/spend(消耗)',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分明细表';

-- 2. 插入测试数据（赵六）
SET @zhaoliu_id = (SELECT id FROM members WHERE nickname = '赵六' LIMIT 1);

INSERT INTO user_coupons (member_id, coupon_name, coupon_value, coupon_type, min_amount, status, received_at, valid_until) VALUES
(@zhaoliu_id, '新人满减券', 10.00, 'cash', 100.00, 'unused', '2026-06-01 10:00:00', '2026-07-31 23:59:59'),
(@zhaoliu_id, '夏日折扣券', 8.00, 'discount', 50.00, 'unused', '2026-06-10 14:30:00', '2026-07-15 23:59:59'),
(@zhaoliu_id, '无门槛红包', 5.00, 'cash', 0.00, 'used', '2026-06-15 09:00:00', '2026-06-30 23:59:59'),
(@zhaoliu_id, 'VIP专享券', 20.00, 'cash', 200.00, 'unused', '2026-06-20 16:00:00', '2026-08-01 23:59:59'),
(@zhaoliu_id, '会员日特惠', 15.00, 'cash', 150.00, 'expired', '2026-05-01 08:00:00', '2026-05-31 23:59:59');

INSERT INTO transactions (member_id, type, amount, payment_method, balance_after, payee, description, created_at) VALUES
(@zhaoliu_id, 'recharge', 200.00, '微信支付', 295.00, '-', '账户充值', '2026-06-01 10:00:00'),
(@zhaoliu_id, 'payment', 89.00, '余额支付', 206.00, '青园超市', '购买：日常用品套餐', '2026-06-05 14:30:00'),
(@zhaoliu_id, 'recharge', 100.00, '支付宝', 306.00, '-', '账户充值', '2026-06-10 09:15:00'),
(@zhaoliu_id, 'payment', 25.00, '微信支付', 281.00, '青园文具店', '购买：笔记本套装', '2026-06-15 16:20:00'),
(@zhaoliu_id, 'refund', 10.00, '原路退回', 291.00, '青园超市', '退款：商品质量问题', '2026-06-18 11:00:00'),
(@zhaoliu_id, 'payment', 56.00, '余额支付', 235.00, '青园食堂', '购买：周套餐', '2026-06-22 12:00:00'),
(@zhaoliu_id, 'recharge', 60.00, '微信支付', 295.00, '-', '账户充值', '2026-06-25 08:30:00');

INSERT INTO points_log (member_id, change, balance_after, description, type, created_at) VALUES
(@zhaoliu_id, 100, 100, '注册奖励', 'earn', '2026-06-01 10:00:00'),
(@zhaoliu_id, 20, 120, '每日签到', 'earn', '2026-06-02 09:00:00'),
(@zhaoliu_id, -50, 70, '兑换：青园定制笔记本', 'spend', '2026-06-08 14:00:00'),
(@zhaoliu_id, 200, 270, '订单消费奖励', 'earn', '2026-06-05 14:30:00'),
(@zhaoliu_id, 30, 300, '发表评论奖励', 'earn', '2026-06-06 10:00:00'),
(@zhaoliu_id, 15, 315, '每日签到', 'earn', '2026-06-10 09:00:00'),
(@zhaoliu_id, 10, 325, '完善个人信息', 'earn', '2026-06-12 15:00:00'),
(@zhaoliu_id, -100, 225, '兑换：青园T恤', 'spend', '2026-06-20 11:00:00'),
(@zhaoliu_id, 50, 275, '邀请好友注册奖励', 'earn', '2026-06-22 16:00:00'),
(@zhaoliu_id, 29, 304, '订单消费奖励', 'earn', '2026-06-22 12:00:00');
