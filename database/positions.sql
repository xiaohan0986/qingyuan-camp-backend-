-- 签务通岗位表
-- 创建时间：2026-04-07

CREATE TABLE IF NOT EXISTS `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '岗位标题',
  `company` varchar(200) DEFAULT NULL COMMENT '公司名',
  `country` varchar(50) DEFAULT NULL COMMENT '国家',
  `city` varchar(100) DEFAULT NULL COMMENT '城市',
  `industry` varchar(100) DEFAULT NULL COMMENT '行业',
  `visa_type` varchar(50) DEFAULT NULL COMMENT '签证类型',
  `education_required` varchar(50) DEFAULT NULL COMMENT '学历要求',
  `age_min` int(11) DEFAULT NULL COMMENT '最小年龄',
  `age_max` int(11) DEFAULT NULL COMMENT '最大年龄',
  `languages` varchar(200) DEFAULT NULL COMMENT '语言要求',
  `skills` text DEFAULT NULL COMMENT '技能要求',
  `benefits` text DEFAULT NULL COMMENT '福利待遇',
  `salary` varchar(100) DEFAULT NULL COMMENT '薪资',
  `requirements` text COMMENT '要求',
  `description` text COMMENT '描述',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：0 隐藏 1 发布',
  `view_count` int(11) DEFAULT 0 COMMENT '浏览数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_country` (`country`),
  KEY `idx_industry` (`industry`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='岗位表';
