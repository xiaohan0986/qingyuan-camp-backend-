-- 为 stores 表添加经纬度字段
-- 执行时间：2026-05-03

ALTER TABLE `stores` 
ADD COLUMN `latitude` decimal(10,7) DEFAULT NULL COMMENT '纬度' AFTER `address`,
ADD COLUMN `longitude` decimal(10,7) DEFAULT NULL COMMENT '经度' AFTER `latitude`;
