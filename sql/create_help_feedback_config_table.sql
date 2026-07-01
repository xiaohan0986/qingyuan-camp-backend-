-- 创建帮助与反馈配置表
CREATE TABLE IF NOT EXISTS `help_feedback_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键 ID',
  `config_key` VARCHAR(50) NOT NULL COMMENT '配置键：faq/company_phone/company_email/company_intro',
  `config_value` TEXT COMMENT '配置值（JSON 或文本）',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT(1) DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帮助与反馈配置表';

-- 插入默认数据
INSERT INTO `help_feedback_config` (`config_key`, `config_value`, `sort`, `status`) VALUES
('company_phone', '400-7676-518', 0, 1),
('company_email', '5937658@qq.com', 0, 1),
('company_intro', '我们是一家专业的海外劳务服务公司，致力于为客户提供优质的海外就业机会和专业的劳务服务。', 0, 1),
('faq_1', '{"question":"如何申请职位？","answer":"在首页或岗位列表页点击心仪的岗位，进入详情页后点击\"立即沟通\"或\"申请职位\"按钮即可。"}', 1, 1),
('faq_2', '{"question":"申请后多久有回复？","answer":"一般情况下，我们会在 1-3 个工作日内审核您的申请，并通过微信或电话与您联系。"}', 2, 1),
('faq_3', '{"question":"如何查看申请进度？","answer":"在\"我的\"页面点击\"办理进度\"，即可查看您的申请进度和当前状态。"}', 3, 1),
('faq_4', '{"question":"需要支付费用吗？","answer":"我们的咨询服务是免费的，但在办理过程中可能会产生一些官方费用，具体费用会在办理前告知。"}', 4, 1),
('faq_5', '{"question":"可以取消申请吗？","answer":"可以的，您可以在\"办理进度\"页面联系客服取消申请，或在申请进度允许的情况下自行取消。"}', 5, 1),
('faq_6', '{"question":"如何修改个人信息？","answer":"在\"我的\"页面点击头像或个人信息，进入编辑页面即可修改您的个人信息。"}', 6, 1);
