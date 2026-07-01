-- 创建用户收藏表
CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '主键 ID',
  `user_id` INT NOT NULL COMMENT '用户 ID',
  `type` VARCHAR(20) NOT NULL COMMENT '收藏类型：article=文章，position=岗位，store=门店',
  `article_id` INT DEFAULT NULL COMMENT '文章 ID（type=article 时使用）',
  `position_id` INT DEFAULT NULL COMMENT '岗位 ID（type=position 时使用）',
  `store_id` INT DEFAULT NULL COMMENT '门店 ID（type=store 时使用）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX `idx_user_type` (`user_id`, `type`),
  INDEX `idx_user_article` (`user_id`, `article_id`),
  INDEX `idx_user_position` (`user_id`, `position_id`),
  INDEX `idx_user_store` (`user_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收藏表';
