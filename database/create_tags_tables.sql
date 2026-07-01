-- 创建技能标签表
CREATE TABLE IF NOT EXISTS `skill_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(50) NOT NULL COMMENT '技能名称',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_skill_name` (`skill_name`),
  KEY `sort_order` (`sort_order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='技能标签表';

-- 创建国家标签表
CREATE TABLE IF NOT EXISTS `country_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(50) NOT NULL COMMENT '国家名称',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_country_name` (`country_name`),
  KEY `sort_order` (`sort_order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='国家标签表';

-- 插入默认技能标签（19 个）
INSERT INTO `skill_tags` (`skill_name`, `sort_order`) VALUES
('装修木工', 1),
('瓦工', 2),
('油漆工', 3),
('厨具安装', 4),
('建筑木工', 5),
('铝合金门窗安装工', 6),
('焊工', 7),
('厨师', 8),
('脚手架工', 9),
('内外水管道工', 10),
('汽修工', 11),
('贴膜师', 12),
('钣金喷漆工', 13),
('司机', 14),
('防水工', 15),
('挖掘机司机', 16),
('钢筋工', 17),
('按摩师', 18),
('其他', 19)
ON DUPLICATE KEY UPDATE `skill_name` = VALUES(`skill_name`);

-- 插入默认国家标签（17 个）
INSERT INTO `country_tags` (`country_name`, `sort_order`) VALUES
('日本', 1),
('新加坡', 2),
('韩国', 3),
('澳大利亚', 4),
('新西兰', 5),
('美国', 6),
('加拿大', 7),
('英国', 8),
('德国', 9),
('法国', 10),
('荷兰', 11),
('瑞士', 12),
('瑞典', 13),
('挪威', 14),
('芬兰', 15),
('丹麦', 16),
('其他', 17)
ON DUPLICATE KEY UPDATE `country_name` = VALUES(`country_name`);
