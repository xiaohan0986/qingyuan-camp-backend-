-- ============================================
-- 检查并修复 is_recommend 字段
-- 数据库：qianwutong
-- ============================================

-- 1. 检查字段是否存在
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'qianwutong' 
  AND TABLE_NAME = 'positions' 
  AND COLUMN_NAME = 'is_recommend';

-- 2. 如果字段不存在，添加字段
ALTER TABLE positions 
ADD COLUMN IF NOT EXISTS is_recommend TINYINT(1) DEFAULT 0 COMMENT '是否推荐' AFTER status;

-- 3. 查看当前推荐岗位（应该显示已设置的推荐岗位）
SELECT id, title, is_recommend, created_at 
FROM positions 
WHERE is_recommend = 1 
ORDER BY created_at DESC;

-- 4. 统计推荐岗位数量
SELECT COUNT(*) as recommend_count FROM positions WHERE is_recommend = 1;

-- 5. 查看所有岗位字段（确认字段结构）
DESCRIBE positions;
