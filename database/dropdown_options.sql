-- 签务通下拉选项表
-- 用于存储可自定义的下拉选项
-- 创建时间：2026-04-09

CREATE TABLE IF NOT EXISTS `dropdown_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL COMMENT '选项分类：category/country/visa_type/education_required',
  `value` varchar(100) NOT NULL COMMENT '选项值',
  `label` varchar(100) NOT NULL COMMENT '选项显示文本',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `is_default` tinyint(4) DEFAULT 0 COMMENT '是否默认选项：0-否 1-是',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：0-禁用 1-启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_category_value` (`category`, `value`),
  KEY `idx_category` (`category`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='下拉选项表';

-- 插入默认岗位分类选项
INSERT INTO `dropdown_options` (`category`, `value`, `label`, `sort_order`, `is_default`) VALUES
('category', '餐饮酒店', '餐饮酒店', 1, 1),
('category', '零售贸易', '零售贸易', 2, 1),
('category', '建筑工程', '建筑工程', 3, 1),
('category', '农业畜牧', '农业畜牧', 4, 1),
('category', '制造业', '制造业', 5, 1),
('category', '医疗保健', '医疗保健', 6, 1),
('category', '教育培训', '教育培训', 7, 1),
('category', 'IT 科技', 'IT 科技', 8, 1),
('category', '物流运输', '物流运输', 9, 1),
('category', '其他', '其他', 10, 1);

-- 插入默认国家选项
INSERT INTO `dropdown_options` (`category`, `value`, `label`, `sort_order`, `is_default`) VALUES
('country', 'Australia', '澳大利亚', 1, 1),
('country', 'New Zealand', '新西兰', 2, 1),
('country', 'Canada', '加拿大', 3, 1),
('country', 'United Kingdom', '英国', 4, 1),
('country', 'Singapore', '新加坡', 5, 1),
('country', 'Japan', '日本', 6, 1),
('country', 'South Korea', '韩国', 7, 1),
('country', 'Germany', '德国', 8, 1),
('country', 'France', '法国', 9, 1),
('country', 'Netherlands', '荷兰', 10, 1);

-- 插入默认签证类型选项
INSERT INTO `dropdown_options` (`category`, `value`, `label`, `sort_order`, `is_default`) VALUES
('visa_type', '482', '482 工作签证', 1, 1),
('visa_type', '494', '494 偏远地区签证', 2, 1),
('visa_type', '186', '186 雇主担保签证', 3, 1),
('visa_type', '407', '407 培训签证', 4, 1),
('visa_type', 'WHV', '打工度假签证', 5, 1),
('visa_type', '学生签', '学生签证', 6, 1),
('visa_type', '其他', '其他', 7, 1);

-- 插入默认学历要求选项
INSERT INTO `dropdown_options` (`category`, `value`, `label`, `sort_order`, `is_default`) VALUES
('education_required', '', '不限', 1, 1),
('education_required', '初中', '初中', 2, 1),
('education_required', '高中', '高中/中专', 3, 1),
('education_required', '大专', '大专', 4, 1),
('education_required', '本科', '本科', 5, 1),
('education_required', '硕士', '硕士', 6, 1),
('education_required', '博士', '博士', 7, 1);
