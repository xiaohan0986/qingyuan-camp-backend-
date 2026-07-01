-- 添加推荐业务员字段到岗位表
ALTER TABLE `positions` 
ADD COLUMN `recommend_salesman_id` int(11) DEFAULT NULL COMMENT '推荐业务员 ID' AFTER `status`,
ADD COLUMN `latitude` decimal(10,7) DEFAULT NULL COMMENT '纬度' AFTER `recommend_salesman_id`,
ADD COLUMN `longitude` decimal(10,7) DEFAULT NULL COMMENT '经度' AFTER `latitude`,
ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT '创建人 ID' AFTER `longitude`,
ADD COLUMN `category` varchar(100) DEFAULT NULL COMMENT '岗位类别' AFTER `title`,
ADD COLUMN `major_required` varchar(200) DEFAULT NULL COMMENT '专业要求' AFTER `education_required`,
ADD COLUMN `salary_range` varchar(100) DEFAULT NULL COMMENT '薪资范围' AFTER `salary`;

-- 添加索引
ALTER TABLE `positions` ADD KEY `idx_recommend_salesman` (`recommend_salesman_id`);
ALTER TABLE `positions` ADD KEY `idx_created_by` (`created_by`);
