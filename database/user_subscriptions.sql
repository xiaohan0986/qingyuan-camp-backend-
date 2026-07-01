-- 用户订阅消息表
-- 用于存储用户对订阅消息的授权状态

CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT '用户 ID',
  `openid` VARCHAR(64) NOT NULL COMMENT '用户微信 openid',
  `template_id` VARCHAR(64) NOT NULL COMMENT '订阅消息模板 ID',
  `status` ENUM('active', 'inactive') DEFAULT 'active' COMMENT '订阅状态：active-有效，inactive-已取消',
  `subscribe_count` INT DEFAULT 1 COMMENT '订阅次数（用户可多次订阅）',
  `last_used_at` TIMESTAMP NULL COMMENT '最后一次发送时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_template` (`user_id`, `template_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户订阅消息表';

-- 插入示例数据（可选）
-- INSERT INTO `user_subscriptions` (`user_id`, `openid`, `template_id`, `status`) VALUES
-- (1, 'oXXXX_test_openid', 'wXXXX_test_template', 'active');
