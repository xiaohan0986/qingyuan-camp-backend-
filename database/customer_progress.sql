-- 客户办理进度表
CREATE TABLE IF NOT EXISTS `customer_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户 ID',
  `user_id` int(11) NOT NULL COMMENT '所属用户 ID',
  `progress_stage` varchar(50) NOT NULL COMMENT '当前进度阶段：submitted 已提交/auditing 审核中/approved 已批准/visa_made 签证制作中/completed 已完成',
  `progress_name` varchar(100) NOT NULL COMMENT '进度阶段名称',
  `progress_color` varchar(20) DEFAULT '#007aff' COMMENT '进度颜色',
  `description` text COMMENT '进度描述',
  `operator_id` int(11) DEFAULT NULL COMMENT '操作人 ID',
  `operator_name` varchar(100) DEFAULT NULL COMMENT '操作人姓名',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_stage` (`progress_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户办理进度表';

-- 办理进度时间线表
CREATE TABLE IF NOT EXISTS `progress_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '客户 ID',
  `stage` varchar(50) NOT NULL COMMENT '阶段',
  `stage_name` varchar(100) NOT NULL COMMENT '阶段名称',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0=待处理，1=进行中，2=已完成',
  `description` text COMMENT '阶段描述',
  `completed_at` datetime DEFAULT NULL COMMENT '完成时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='办理进度时间线表';
