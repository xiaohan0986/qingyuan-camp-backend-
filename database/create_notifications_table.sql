-- 系统通知表
-- 用于存储系统发送给用户的各类通知消息

CREATE TABLE IF NOT EXISTS `system_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '通知 ID',
  `user_id` int(11) NOT NULL COMMENT '接收用户 ID（salesmen 表或 users 表的 ID）',
  `user_type` varchar(20) NOT NULL DEFAULT 'user' COMMENT '用户类型：user, salesman',
  `type` varchar(50) NOT NULL DEFAULT 'system' COMMENT '消息类型：system, customer, article, position, finance, security',
  `icon` varchar(50) DEFAULT NULL COMMENT '消息图标 emoji',
  `title` varchar(200) NOT NULL COMMENT '消息标题',
  `content` text COMMENT '消息内容',
  `category` varchar(50) DEFAULT 'system' COMMENT '消息分类',
  `extra_data` text COMMENT '额外数据（JSON 格式，用于存储跳转链接等）',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0=未读，1=已读',
  `read_at` timestamp NULL DEFAULT NULL COMMENT '阅读时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';
