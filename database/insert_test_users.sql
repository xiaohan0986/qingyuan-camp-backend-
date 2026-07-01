-- 插入测试用户数据
-- 密码都是 123456 (已加密)

USE `qianwutong_platform`;

-- 清空现有测试数据（可选）
-- DELETE FROM users WHERE username IN ('zhangsan', 'lisi', 'wangwu', 'zhaoliu', 'sunqi', 'zhouba', 'wujiu', 'zhengshi', 'qianyi', 'shier');

-- 插入 10 个测试用户
INSERT INTO `users` (`username`, `password`, `nickname`, `phone`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
('zhangsan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '张三', '13800138001', 'zhangsan@example.com', 1, 1, '2026-04-01 10:00:00', '2026-04-01 10:00:00'),
('lisi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '李四', '13800138002', 'lisi@example.com', 1, 1, '2026-04-02 11:00:00', '2026-04-02 11:00:00'),
('wangwu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '王五', '13800138003', 'wangwu@example.com', 1, 1, '2026-04-03 12:00:00', '2026-04-03 12:00:00'),
('zhaoliu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '赵六', '13800138004', 'zhaoliu@example.com', 1, 1, '2026-04-04 13:00:00', '2026-04-04 13:00:00'),
('sunqi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '孙七', '13800138005', 'sunqi@example.com', 1, 1, '2026-04-05 14:00:00', '2026-04-05 14:00:00'),
('zhouba', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '周八', '13800138006', 'zhouba@example.com', 1, 0, '2026-04-05 15:00:00', '2026-04-05 15:00:00'),
('wujiu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '吴九', '13800138007', 'wujiu@example.com', 2, 1, '2026-04-06 09:00:00', '2026-04-06 09:00:00'),
('zhengshi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '郑十', '13800138008', 'zhengshi@example.com', 1, 1, '2026-04-06 10:00:00', '2026-04-06 10:00:00'),
('qianyi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '钱一', '13800138009', 'qianyi@example.com', 1, 1, '2026-04-06 11:00:00', '2026-04-06 11:00:00'),
('shier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '十二', '13800138010', 'shier@example.com', 1, 0, '2026-04-06 12:00:00', '2026-04-06 12:00:00'),
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', '13800138000', 'admin@example.com', 2, 1, '2026-04-01 08:00:00', '2026-04-01 08:00:00');

-- 显示插入结果
SELECT id, username, nickname, phone, role, status, created_at FROM users ORDER BY id DESC;
