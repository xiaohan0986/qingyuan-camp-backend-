-- 文章分类表
-- 创建时间：2026-04-11

CREATE TABLE IF NOT EXISTS `article_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `color` varchar(20) DEFAULT '#667eea' COMMENT '分类颜色',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `article_count` int(11) DEFAULT 0 COMMENT '文章数量',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章分类表';

-- 初始化默认分类
INSERT INTO `article_categories` (`name`, `color`, `sort_order`) VALUES
('政策', '#667eea', 1),
('热点', '#f5222d', 2),
('公告', '#faad14', 3),
('新闻', '#52c41a', 4),
('通知', '#13c2c2', 5),
('法规', '#722ed1', 6);
