-- 添加 is_recommend 字段到 positions 表
-- 执行前先备份数据库：mysqldump -u root -p 数据库名 > backup_$(date +%Y%m%d).sql

-- 检查字段是否已存在
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'positions' AND COLUMN_NAME = 'is_recommend';

-- 添加字段（如果不存在）
ALTER TABLE positions 
ADD COLUMN IF NOT EXISTS is_recommend TINYINT(1) DEFAULT 0 COMMENT '是否推荐' AFTER status;

-- 验证字段已添加
DESCRIBE positions;

-- 查看当前推荐岗位数量
SELECT COUNT(*) as recommend_count FROM positions WHERE is_recommend = 1;

-- 测试：将 ID=21 的岗位设为推荐（可选）
-- UPDATE positions SET is_recommend = 1 WHERE id = 21;

-- 测试查询推荐岗位
SELECT id, title, is_recommend, created_at 
FROM positions 
WHERE is_recommend = 1 
ORDER BY created_at DESC;
