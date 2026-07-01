-- 门店管理表
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT '门店名称',
  `phone` varchar(50) DEFAULT '' COMMENT '联系电话',
  `country` varchar(100) NOT NULL COMMENT '国家',
  `city` varchar(100) NOT NULL COMMENT '城市',
  `address` varchar(500) DEFAULT '' COMMENT '详细地址',
  `latitude` decimal(10,7) DEFAULT NULL COMMENT '纬度',
  `longitude` decimal(10,7) DEFAULT NULL COMMENT '经度',
  `description` text COMMENT '门店简介',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1=营业中，0=已关闭',
  `sort` int(11) DEFAULT '0' COMMENT '排序，数字越小越靠前',
  `view_count` int(11) DEFAULT '0' COMMENT '浏览次数',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_country_city` (`country`, `city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店管理表';
