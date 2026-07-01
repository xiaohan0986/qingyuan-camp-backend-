-- 为 positions 表添加缺失的字段
-- 执行时间：2026-04-16

USE qianwutong;

-- 添加 category 字段（岗位分类）
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `category` varchar(100) DEFAULT NULL COMMENT '岗位分类' AFTER `title`;

-- 添加 salary_range 字段（替换旧的 salary 字段）
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `salary_range` varchar(100) DEFAULT NULL COMMENT '薪资范围' AFTER `skills`;

-- 添加 tags 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `tags` varchar(500) DEFAULT NULL COMMENT '岗位标签' AFTER `benefits`;

-- 添加 required_materials 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `required_materials` text DEFAULT NULL COMMENT '所需材料（JSON）' AFTER `tags`;

-- 添加 attachment_files 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `attachment_files` text DEFAULT NULL COMMENT '附件文件（JSON）' AFTER `required_materials`;

-- 添加 latitude 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `latitude` decimal(10, 8) DEFAULT NULL COMMENT '纬度' AFTER `attachment_files`;

-- 添加 longitude 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `longitude` decimal(11, 8) DEFAULT NULL COMMENT '经度' AFTER `latitude`;

-- 添加 major_required 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `major_required` varchar(200) DEFAULT NULL COMMENT '专业要求' AFTER `education_required`;

-- 添加 created_by 字段
ALTER TABLE `positions` 
ADD COLUMN IF NOT EXISTS `created_by` int(11) DEFAULT NULL COMMENT '创建人 ID' AFTER `status`;

-- 修改 description 字段为 text 类型（如果还不是）
ALTER TABLE `positions` 
MODIFY COLUMN `description` text COMMENT '岗位描述';

-- 修改 requirements 字段为 text 类型
ALTER TABLE `positions` 
MODIFY COLUMN `requirements` text COMMENT '岗位要求';

-- 查看最终表结构
DESCRIBE `positions`;
