-- 签务通客户表
-- 创建时间：2026-04-07

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '客户姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `wechat` varchar(100) DEFAULT NULL COMMENT '微信',
  `qq` varchar(50) DEFAULT NULL COMMENT 'QQ',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `country` varchar(50) DEFAULT NULL COMMENT '意向国家',
  `visa_type` varchar(50) DEFAULT NULL COMMENT '签证类型',
  `source` varchar(50) DEFAULT 'online' COMMENT '客户来源',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：1 潜在 2 已联系 3 已成交 4 已流失',
  `sales_user_id` int(11) DEFAULT NULL COMMENT '销售负责人',
  `position_id` int(11) DEFAULT NULL COMMENT '关联岗位',
  `remark` text COMMENT '备注',
  `next_followup_at` timestamp NULL DEFAULT NULL COMMENT '下次跟进时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sales_user` (`sales_user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户表';

-- 客户跟进记录表
CREATE TABLE IF NOT EXISTS `customer_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户 ID',
  `user_id` int(11) NOT NULL COMMENT '跟进人',
  `content` text COMMENT '跟进内容',
  `next_followup_at` timestamp NULL DEFAULT NULL COMMENT '下次跟进时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户跟进记录表';
