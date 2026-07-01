-- 签务通角色管理表
-- 创建时间：2026-04-11

-- 角色表
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL COMMENT '角色名称',
  `role_key` varchar(50) NOT NULL COMMENT '角色标识',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `permissions` text COMMENT '权限配置（JSON 格式）',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `sort_order` int(11) DEFAULT 100 COMMENT '排序值',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_key` (`role_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL COMMENT '角色 ID',
  `permission_key` varchar(100) NOT NULL COMMENT '权限标识',
  `permission_name` varchar(100) NOT NULL COMMENT '权限名称',
  `module` varchar(50) DEFAULT NULL COMMENT '所属模块',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- 修改 users 表，添加 role_id 字段
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role_id` int(11) DEFAULT NULL COMMENT '关联角色 ID' AFTER `role`;

-- 插入默认角色数据
INSERT INTO `roles` (`role_name`, `role_key`, `description`, `permissions`, `status`, `sort_order`) VALUES
('超级管理员', 'super_admin', '拥有系统所有权限', '{"all": true}', 1, 1),
('系统管理员', 'admin', '拥有系统管理权限', '{"dashboard": true, "position": true, "customer": true, "user": true, "article": true, "store": true, "salesmen": true, "finance": true, "config": true}', 1, 2),
('运营人员', 'operator', '拥有运营相关权限', '{"dashboard": true, "position": true, "customer": true, "article": true, "store": true, "salesmen": true}', 1, 3),
('客服人员', 'service', '拥有客服相关权限', '{"dashboard": true, "customer": true}', 1, 4);

-- 插入默认权限数据
INSERT INTO `role_permissions` (`role_id`, `permission_key`, `permission_name`, `module`) VALUES
-- 超级管理员权限（角色 ID=1）
(1, 'view', '查看', 'all'),
(1, 'create', '创建', 'all'),
(1, 'edit', '编辑', 'all'),
(1, 'delete', '删除', 'all'),
(1, 'export', '导出', 'all'),
(1, 'import', '导入', 'all'),
-- 系统管理员权限（角色 ID=2）
(2, 'view', '查看', 'all'),
(2, 'create', '创建', 'all'),
(2, 'edit', '编辑', 'all'),
(2, 'delete', '删除', 'all'),
-- 运营人员权限（角色 ID=3）
(3, 'view', '查看', 'all'),
(3, 'create', '创建', 'position,customer,article,store,salesmen'),
(3, 'edit', '编辑', 'position,customer,article,store,salesmen'),
-- 客服人员权限（角色 ID=4）
(4, 'view', '查看', 'dashboard,customer'),
(4, 'edit', '编辑', 'customer');
