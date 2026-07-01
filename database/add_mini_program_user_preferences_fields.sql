-- 为小程序用户表添加特长和意向国家字段
-- 执行时间：2026-05-11
-- 数据库：qianwutong

USE `qianwutong`;

-- 检查表是否存在
CREATE TABLE IF NOT EXISTS `mini_program_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码',
  `nickname` VARCHAR(100) DEFAULT '微信用户' COMMENT '昵称',
  `avatar` VARCHAR(255) DEFAULT '' COMMENT '头像',
  `gender` TINYINT(1) DEFAULT 0 COMMENT '性别：0=未知，1=男，2=女',
  `wechat_openid` VARCHAR(100) DEFAULT NULL COMMENT '微信 OpenID',
  `wechat_session_key` VARCHAR(100) DEFAULT NULL COMMENT '微信 SessionKey',
  `wechat_id` VARCHAR(100) DEFAULT '' COMMENT '微信号',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `role` TINYINT(1) DEFAULT 1 COMMENT '角色：1=普通用户，2=管理员',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：0=禁用，1=正常',
  `last_login_at` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录 IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_wechat_openid` (`wechat_openid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序用户表';

-- 添加特长字段（JSON 数组）
ALTER TABLE `mini_program_users` 
ADD COLUMN `skills` TEXT COMMENT '特长列表（JSON 数组）' 
AFTER `phone`;

-- 添加意向国家字段（JSON 数组）
ALTER TABLE `mini_program_users` 
ADD COLUMN `intended_countries` TEXT COMMENT '意向国家列表（JSON 数组）' 
AFTER `skills`;

SELECT '小程序用户表字段添加完成！' AS message;
