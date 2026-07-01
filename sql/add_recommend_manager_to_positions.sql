-- =====================================================
-- 为 positions 表添加推荐经理相关字段
-- 执行时间：2026-04-30
-- =====================================================

-- 添加推荐经理姓名字段
ALTER TABLE positions 
ADD COLUMN recommend_manager_name VARCHAR(100) DEFAULT NULL COMMENT '推荐经理姓名'
AFTER benefits;

-- 添加推荐经理头像字段
ALTER TABLE positions 
ADD COLUMN recommend_manager_avatar VARCHAR(500) DEFAULT NULL COMMENT '推荐经理头像 URL'
AFTER recommend_manager_name;

-- 添加推荐经理电话字段
ALTER TABLE positions 
ADD COLUMN recommend_manager_phone VARCHAR(20) DEFAULT NULL COMMENT '推荐经理电话'
AFTER recommend_manager_avatar;

-- 添加推荐经理备注字段
ALTER TABLE positions 
ADD COLUMN recommend_manager_remark TEXT DEFAULT NULL COMMENT '推荐经理备注'
AFTER recommend_manager_phone;

-- 查询验证
SELECT 
    id,
    title,
    recommend_manager_name,
    recommend_manager_avatar,
    recommend_manager_phone
FROM positions
LIMIT 5;
