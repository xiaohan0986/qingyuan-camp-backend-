-- 更新客户管理表，添加所有新字段
-- 执行时间：2026-04-09

-- 添加缺失的字段
ALTER TABLE `customers` 
ADD COLUMN IF NOT EXISTS `user_id` INT(11) DEFAULT NULL COMMENT '关联用户 ID' AFTER `name`,
ADD COLUMN IF NOT EXISTS `ethnicity` VARCHAR(50) DEFAULT NULL COMMENT '民族' AFTER `gender`,
ADD COLUMN IF NOT EXISTS `school` VARCHAR(200) DEFAULT NULL COMMENT '毕业院校' AFTER `education`,
ADD COLUMN IF NOT EXISTS `major` VARCHAR(100) DEFAULT NULL COMMENT '专业' AFTER `school`,
ADD COLUMN IF NOT EXISTS `expected_salary` VARCHAR(100) DEFAULT NULL COMMENT '期望薪资' AFTER `country`,
ADD COLUMN IF NOT EXISTS `work_status` VARCHAR(50) DEFAULT NULL COMMENT '工作状况' AFTER `children_status`,
ADD COLUMN IF NOT EXISTS `flow_status` VARCHAR(100) DEFAULT NULL COMMENT '流水状况' AFTER `work_status`,
ADD COLUMN IF NOT EXISTS `social_security_status` VARCHAR(100) DEFAULT NULL COMMENT '社保状况' AFTER `flow_status`,
ADD COLUMN IF NOT EXISTS `vehicle` VARCHAR(20) DEFAULT NULL COMMENT '车辆状况：有/无' AFTER `hometown`,
ADD COLUMN IF NOT EXISTS `property` VARCHAR(20) DEFAULT NULL COMMENT '房产状况：有/无' AFTER `vehicle`,
ADD COLUMN IF NOT EXISTS `financial_status` TEXT DEFAULT NULL COMMENT '财务状况' AFTER `property`;

-- 修改现有字段
ALTER TABLE `customers` 
MODIFY COLUMN `gender` VARCHAR(10) DEFAULT NULL COMMENT '性别：男/女',
MODIFY COLUMN `remark` TEXT DEFAULT NULL COMMENT '备注说明';

-- 添加索引
ALTER TABLE `customers` 
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`),
ADD INDEX IF NOT EXISTS `idx_country` (`country`);
