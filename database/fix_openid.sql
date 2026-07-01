-- 修复 users 表 openid 字段
-- 将 NOT NULL 改为 DEFAULT NULL，允许创建用户时不提供 openid

ALTER TABLE users MODIFY openid varchar(100) DEFAULT NULL COMMENT '微信 openid';

-- 验证修复结果
DESCRIBE users;
