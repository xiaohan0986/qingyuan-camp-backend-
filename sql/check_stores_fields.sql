-- 检查 stores 表的字段结构
DESCRIBE stores;

-- 查询所有门店的 tags 字段
SELECT id, name, country, city, tags FROM stores;

-- 如果 tags 字段有默认值，清空它们
-- UPDATE stores SET tags = '' WHERE tags = '["品质保证","专业服务"]' OR tags = '["品质保证", "专业服务"]';
