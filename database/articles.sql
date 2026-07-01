-- 签务通文章表
-- 创建时间：2026-04-07

CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '标题',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `author` varchar(100) DEFAULT NULL COMMENT '作者',
  `source` varchar(200) DEFAULT NULL COMMENT '来源',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图',
  `summary` varchar(500) DEFAULT NULL COMMENT '摘要',
  `content` text COMMENT '内容',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签',
  `view_count` int(11) DEFAULT 0 COMMENT '浏览数',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：0 草稿 1 发布',
  `published_at` timestamp NULL DEFAULT NULL COMMENT '发布时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- 访问统计表
CREATE TABLE IF NOT EXISTS `visit_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT '日期',
  `uv` int(11) DEFAULT 0 COMMENT '独立访客',
  `pv` int(11) DEFAULT 0 COMMENT '页面访问',
  `ip` int(11) DEFAULT 0 COMMENT 'IP 数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问统计表';

-- 营销配置表
CREATE TABLE IF NOT EXISTS `marketing_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='营销配置表';

-- 营销日志表
CREATE TABLE IF NOT EXISTS `marketing_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(100) DEFAULT NULL COMMENT '操作',
  `target` varchar(255) DEFAULT NULL COMMENT '目标',
  `result` text COMMENT '结果',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='营销日志表';
