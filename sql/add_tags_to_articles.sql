-- 为 articles 表添加 tags 字段
-- 用于存储文章标签，逗号分隔

ALTER TABLE articles 
ADD COLUMN tags TEXT NULL COMMENT '文章标签，逗号分隔' 
AFTER category;

-- 查看字段是否添加成功
SHOW COLUMNS FROM articles LIKE 'tags';
