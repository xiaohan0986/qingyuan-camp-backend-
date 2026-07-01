-- 检查并添加 category 字段
-- 在 phpMyAdmin 中执行以下 SQL：

-- 1. 检查字段是否存在
SHOW COLUMNS FROM positions LIKE 'category';

-- 2. 如果不存在，添加字段
ALTER TABLE positions ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT '岗位分类' AFTER title;

-- 3. 验证添加成功
DESCRIBE positions;
