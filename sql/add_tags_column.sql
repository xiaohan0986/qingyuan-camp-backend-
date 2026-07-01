-- 为 stores 表添加 tags 字段
-- 如果字段已存在会报错，可以忽略错误继续执行
ALTER TABLE stores 
ADD COLUMN tags TEXT COMMENT '门店标签（JSON 格式）' AFTER environment_images;

-- 如果上面报错说字段已存在，说明已经添加过了，无需处理
