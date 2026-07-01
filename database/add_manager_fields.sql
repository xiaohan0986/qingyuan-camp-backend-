-- 添加门店负责人字段
ALTER TABLE `stores` 
ADD COLUMN `manager` varchar(100) DEFAULT '' COMMENT '负责人姓名' AFTER `phone`,
ADD COLUMN `manager_phone` varchar(50) DEFAULT '' COMMENT '负责人电话' AFTER `manager`;

-- 添加索引
ALTER TABLE `stores` ADD KEY `idx_manager` (`manager`);
