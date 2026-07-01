-- 创建客户管理表
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '客户 ID',
  `name` VARCHAR(100) NOT NULL COMMENT '客户姓名',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `wechat` VARCHAR(50) DEFAULT NULL COMMENT '微信号',
  `position_id` INT(11) DEFAULT NULL COMMENT '申请岗位 ID',
  `position_name` VARCHAR(100) DEFAULT NULL COMMENT '申请岗位名称',
  `category` VARCHAR(50) DEFAULT NULL COMMENT '客户分类：意向客户、签约客户、面试中、已出境',
  `sales_user_id` INT(11) DEFAULT NULL COMMENT '所属业务员 ID',
  `sales_user_name` VARCHAR(50) DEFAULT NULL COMMENT '所属业务员姓名',
  `status` TINYINT(1) DEFAULT 1 COMMENT '客户状态：1=办理中，2=完结',
  `priority` TINYINT(1) DEFAULT 2 COMMENT '优先级：1=高，2=中，3=低',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `age` TINYINT(3) DEFAULT NULL COMMENT '年龄',
  `gender` TINYINT(1) DEFAULT NULL COMMENT '性别：1=男，2=女，0=未知',
  `education` VARCHAR(50) DEFAULT NULL COMMENT '学历',
  `country` VARCHAR(50) DEFAULT NULL COMMENT '意向国家',
  `remark` TEXT DEFAULT NULL COMMENT '备注',
  `follow_up_at` DATETIME DEFAULT NULL COMMENT '下次跟进时间',
  `last_follow_up` DATETIME DEFAULT NULL COMMENT '最后跟进时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间/申请时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_name` (`name`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_sales_user` (`sales_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户管理表';

-- 插入测试数据
INSERT INTO `customers` (`name`, `phone`, `wechat`, `country`, `position_id`, `position_name`, `category`, `sales_user_id`, `sales_user_name`, `status`, `priority`, `email`, `age`, `gender`, `education`, `remark`, `follow_up_at`) VALUES
('张三', '13800138001', 'zhangsan123', '新加坡', 1, '新加坡中文教师', '意向客户', 1, '管理员', 1, 1, 'zhangsan@example.com', 28, 1, '本科', '有意向，正在准备材料', '2026-04-10 10:00:00'),
('李四', '13900139002', 'lisi456', '日本', 2, '日本 IT 工程师', '签约客户', 1, '管理员', 1, 2, 'lisi@example.com', 32, 1, '硕士', '首次咨询', '2026-04-08 14:00:00'),
('王五', '13700137003', 'wangwu789', '韩国', 3, '韩国服务员', '面试中', 1, '管理员', 1, 1, 'wangwu@example.com', 25, 2, '大专', '已签约，等待面试', NULL),
('赵六', '13600136004', 'zhaoliu012', '澳大利亚', 4, '澳大利亚建筑工', '意向客户', 1, '管理员', 1, 3, 'zhaoliu@example.com', 35, 1, '高中', '需要进一步了解', '2026-04-12 09:00:00'),
('孙七', '13500135005', 'sunqi345', '新加坡', 5, '新加坡保洁', '已出境', 1, '管理员', 2, 2, 'sunqi@example.com', 42, 2, '初中', '咨询薪资待遇到', NULL);
