-- 为 roles 表添加 password 字段
ALTER TABLE roles ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL COMMENT '角色密码（加密存储）' AFTER role_key;

-- 为 roles 表添加 password_hint 字段（可选）
ALTER TABLE roles ADD COLUMN IF NOT EXISTS password_hint VARCHAR(255) DEFAULT NULL COMMENT '密码提示' AFTER password;
