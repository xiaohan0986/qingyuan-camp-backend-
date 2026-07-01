-- ===========================================
-- 签务通 (QianWuTong) 数据库初始化脚本
-- 版本：2026.01
-- 字符集：utf8mb4
-- ===========================================

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `qianwutong` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `qianwutong`;

-- ===========================================
-- 1. 用户表
-- ===========================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码（加密）',
  `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `role_id` INT(11) DEFAULT 1 COMMENT '角色 ID',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=正常，0=禁用',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录 IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ===========================================
-- 2. 角色表
-- ===========================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `description` TEXT COMMENT '角色描述',
  `permissions` TEXT COMMENT '权限配置（JSON）',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 插入默认角色
INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
('超级管理员', '拥有系统所有权限', '{"all": true}'),
('管理员', '拥有大部分管理权限', '{"dashboard": true, "user": true, "customer": true, "article": true, "position": true}'),
('普通用户', '基础查看权限', '{"dashboard": false, "customer": "read", "article": "read"}');

-- ===========================================
-- 3. 客户表
-- ===========================================
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '客户姓名',
  `gender` TINYINT(1) DEFAULT NULL COMMENT '性别：1=男，2=女',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `wechat` VARCHAR(50) DEFAULT NULL COMMENT '微信号',
  `qq` VARCHAR(20) DEFAULT NULL COMMENT 'QQ 号',
  `country` VARCHAR(50) DEFAULT NULL COMMENT '意向国家',
  `visa_type` VARCHAR(50) DEFAULT NULL COMMENT '签证类型',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=正常，0=无效',
  `source` VARCHAR(50) DEFAULT NULL COMMENT '客户来源',
  `remark` TEXT COMMENT '备注',
  `owner_id` INT(11) DEFAULT NULL COMMENT '负责人 ID',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户表';

-- ===========================================
-- 4. 岗位表
-- ===========================================
DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL COMMENT '岗位标题',
  `category` VARCHAR(50) DEFAULT NULL COMMENT '岗位类别',
  `country` VARCHAR(50) DEFAULT NULL COMMENT '工作国家',
  `city` VARCHAR(50) DEFAULT NULL COMMENT '工作城市',
  `education` VARCHAR(50) DEFAULT NULL COMMENT '学历要求',
  `salary_min` DECIMAL(10,2) DEFAULT NULL COMMENT '最低薪资',
  `salary_max` DECIMAL(10,2) DEFAULT NULL COMMENT '最高薪资',
  `requirements` TEXT COMMENT '岗位要求',
  `description` TEXT COMMENT '岗位描述',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=招聘中，0=已结束',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_country` (`country`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='岗位表';

-- ===========================================
-- 5. 文章表
-- ===========================================
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL COMMENT '文章标题',
  `content` TEXT COMMENT '文章内容',
  `category_id` INT(11) DEFAULT NULL COMMENT '分类 ID',
  `author_id` INT(11) DEFAULT NULL COMMENT '作者 ID',
  `cover_image` VARCHAR(255) DEFAULT NULL COMMENT '封面图片',
  `view_count` INT(11) DEFAULT 0 COMMENT '浏览次数',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=发布，0=草稿',
  `published_at` DATETIME DEFAULT NULL COMMENT '发布时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

-- ===========================================
-- 6. 文章分类表
-- ===========================================
DROP TABLE IF EXISTS `article_categories`;
CREATE TABLE `article_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
  `parent_id` INT(11) DEFAULT 0 COMMENT '父分类 ID',
  `sort_order` INT(11) DEFAULT 0 COMMENT '排序',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章分类表';

-- 插入默认分类
INSERT INTO `article_categories` (`name`, `parent_id`, `sort_order`) VALUES
('公司新闻', 0, 1),
('签证资讯', 0, 2),
('政策解读', 0, 3),
('成功案例', 0, 4),
('常见问题', 0, 5);

-- ===========================================
-- 7. 客户跟进记录表
-- ===========================================
DROP TABLE IF EXISTS `customer_followups`;
CREATE TABLE `customer_followups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL COMMENT '客户 ID',
  `user_id` INT(11) NOT NULL COMMENT '跟进人 ID',
  `type` VARCHAR(50) DEFAULT NULL COMMENT '跟进类型',
  `content` TEXT COMMENT '跟进内容',
  `next_followup_at` DATETIME DEFAULT NULL COMMENT '下次跟进时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户跟进记录表';

-- ===========================================
-- 8. 访问统计表
-- ===========================================
DROP TABLE IF EXISTS `visit_stats`;
CREATE TABLE `visit_stats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL COMMENT '日期',
  `page` VARCHAR(100) DEFAULT NULL COMMENT '页面',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'User-Agent',
  `referer` VARCHAR(255) DEFAULT NULL COMMENT '来源页面',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访问统计表';

-- ===========================================
-- 9. 系统设置表
-- ===========================================
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(100) NOT NULL COMMENT '配置键名',
  `key_value` TEXT COMMENT '配置值',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '配置描述',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- 插入默认配置
INSERT INTO `system_settings` (`key_name`, `key_value`, `description`) VALUES
('site_name', '签务通', '网站名称'),
('site_title', '签务通 - 专业的签证行业客户管理系统', '网站标题'),
('site_description', '专业的签证行业客户管理系统，让管理更高效，让业务更简单', '网站描述'),
('icp_number', '', 'ICP 备案号'),
('contact_phone', '', '联系电话'),
('contact_email', '', '联系邮箱');

-- ===========================================
-- 10. 操作日志表
-- ===========================================
DROP TABLE IF EXISTS `operation_logs`;
CREATE TABLE `operation_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL COMMENT '操作人 ID',
  `action` VARCHAR(100) NOT NULL COMMENT '操作类型',
  `module` VARCHAR(50) DEFAULT NULL COMMENT '模块',
  `description` VARCHAR(255) DEFAULT NULL COMMENT '操作描述',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'User-Agent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ===========================================
-- 11. 管理员账号（默认密码：Admin2026!@#）
-- ===========================================
-- 密码是使用 PHP password_hash() 生成的
INSERT INTO `users` (`username`, `password`, `real_name`, `role_id`, `status`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', 1, 1)
ON DUPLICATE KEY UPDATE `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ===========================================
-- 完成
-- ===========================================
SELECT '数据库初始化完成！' AS message;
