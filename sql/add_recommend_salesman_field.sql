-- =====================================================
-- 为 positions 表添加推荐业务员关联字段
-- 执行时间：2026-04-30
-- =====================================================

USE www_gofong_com;

-- 添加推荐业务员 ID 字段（关联 salesmen 表）
ALTER TABLE positions 
ADD COLUMN recommend_salesman_id INT DEFAULT NULL COMMENT '推荐业务员 ID（关联 salesmen 表）'
AFTER benefits;

-- 添加外键约束（可选，确保数据完整性）
-- ALTER TABLE positions 
-- ADD CONSTRAINT fk_position_recommend_salesman 
-- FOREIGN KEY (recommend_salesman_id) REFERENCES salesmen(id) ON DELETE SET NULL;

-- 验证字段已添加
DESCRIBE positions;

-- 测试查询（关联业务员信息）
SELECT 
    p.id,
    p.title,
    p.recommend_salesman_id,
    s.name as salesman_name,
    s.avatar as salesman_avatar,
    s.phone as salesman_phone
FROM positions p
LEFT JOIN salesmen s ON p.recommend_salesman_id = s.id
LIMIT 5;
