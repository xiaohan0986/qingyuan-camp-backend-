-- 为用户表添加特长和意向国家字段
-- 执行时间：2026-05-11

USE `qianwutong`;

-- 添加特长字段（JSON 数组）
ALTER TABLE `users` 
ADD COLUMN `skills` TEXT COMMENT '特长列表（JSON 数组）' 
AFTER `phone`;

-- 添加意向国家字段（JSON 数组）
ALTER TABLE `users` 
ADD COLUMN `intended_countries` TEXT COMMENT '意向国家列表（JSON 数组）' 
AFTER `skills`;

-- 添加微信 openid 字段
ALTER TABLE `users` 
ADD COLUMN `wechat_openid` VARCHAR(100) COMMENT '微信 OpenID' 
AFTER `wechat_id`;

SELECT '字段添加完成！' AS message;
