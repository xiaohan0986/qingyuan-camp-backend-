-- 签务通系统配置表
-- 创建时间：2026-04-07

CREATE TABLE IF NOT EXISTS `system_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(50) DEFAULT 'string' COMMENT '配置类型',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置
INSERT INTO `system_configs` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('site_name', '签务通截流大师', 'string', '网站名称'),
('site_description', '专业的签证行业客户管理系统', 'string', '网站描述'),
('site_status', '1', 'boolean', '网站状态：1 开启 0 关闭')
ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`);
