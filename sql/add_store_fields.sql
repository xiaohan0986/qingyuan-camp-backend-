-- 为 stores 表添加小程序需要的字段
ALTER TABLE stores 
ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT '' COMMENT '门店头像 URL',
ADD COLUMN IF NOT EXISTS `en_name` VARCHAR(255) DEFAULT '' COMMENT '门店英文名称',
ADD COLUMN IF NOT EXISTS `tags` VARCHAR(500) DEFAULT '' COMMENT '门店标签（JSON 数组）',
ADD COLUMN IF NOT EXISTS `verified` TINYINT(1) DEFAULT 0 COMMENT '是否认证',
ADD COLUMN IF NOT EXISTS `lat` DECIMAL(10, 8) DEFAULT NULL COMMENT '纬度',
ADD COLUMN IF NOT EXISTS `lng` DECIMAL(11, 8) DEFAULT NULL COMMENT '经度';

-- 更新现有数据，设置默认值
UPDATE stores SET 
  avatar = '/images/store_avatar_1.png',
  en_name = name,
  tags = '["品质保证","专业服务"]',
  verified = 1
WHERE status = 1;
