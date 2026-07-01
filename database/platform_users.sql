-- 平台用户管理数据库表结构
-- 数据库名：qianwutong_platform

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `qianwutong_platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `qianwutong_platform`;

-- 用户表
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户 ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `nickname` varchar(50) DEFAULT NULL COMMENT '姓名/昵称',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像 URL',
  `role` tinyint(1) NOT NULL DEFAULT '1' COMMENT '角色：1=普通用户，2=管理员',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `last_login_at` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录 IP',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_phone` (`phone`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 插入默认管理员账户
-- 用户名：admin
-- 密码：admin123 (已加密)
INSERT INTO `users` (`username`, `password`, `nickname`, `role`, `status`, `created_at`, `updated_at`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 2, 1, NOW(), NOW());

-- 插入测试用户
-- 用户名：test
-- 密码：test123 (已加密)
INSERT INTO `users` (`username`, `password`, `nickname`, `phone`, `email`, `role`, `status`, `created_at`, `updated_at`) 
VALUES ('test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '测试用户', '13800138000', 'test@example.com', 1, 1, NOW(), NOW());
