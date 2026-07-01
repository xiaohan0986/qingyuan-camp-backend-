-- 为 positions 表添加 category 字段
ALTER TABLE positions ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT '岗位分类' AFTER title;

-- 查看是否添加成功
DESCRIBE positions;
