-- 创建仪表板布局配置表
CREATE TABLE IF NOT EXISTS dashboard_layout (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT '主键 ID',
    user_id INT NOT NULL COMMENT '用户 ID',
    first_row TEXT COMMENT '第一排卡片顺序 (JSON)',
    second_row TEXT COMMENT '第二排卡片顺序 (JSON)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仪表板布局配置表';
