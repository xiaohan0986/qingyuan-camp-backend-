-- 城市之光管理后台 - 数据库初始化脚本
-- 数据库：shopauba

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码',
  `name` VARCHAR(100) NOT NULL COMMENT '姓名',
  `email` VARCHAR(100) COMMENT '邮箱',
  `phone` VARCHAR(20) COMMENT '手机号',
  `role` VARCHAR(50) DEFAULT 'admin' COMMENT '角色',
  `avatar` VARCHAR(255) COMMENT '头像',
  `status` TINYINT DEFAULT 1 COMMENT '状态 1=启用 0=禁用',
  `last_login_at` DATETIME COMMENT '最后登录时间',
  `last_login_ip` VARCHAR(50) COMMENT '最后登录 IP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 插入默认管理员 (密码：admin123)
INSERT INTO `admins` (`username`, `password`, `name`, `email`, `role`, `status`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 'admin@shop.auba.cn', 'super_admin', 1);

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED COMMENT '管理员 ID',
  `action` VARCHAR(100) COMMENT '操作',
  `detail` TEXT COMMENT '详情',
  `ip` VARCHAR(50) COMMENT 'IP 地址',
  `user_agent` TEXT COMMENT 'User-Agent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员操作日志';

-- 板块管理表
CREATE TABLE IF NOT EXISTS `blocks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '板块名称',
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '板块标识',
  `type` VARCHAR(50) DEFAULT 'custom' COMMENT '类型：custom(自定义)/product(商品)/article(文章)',
  `config` TEXT COMMENT '配置 JSON',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT DEFAULT 1 COMMENT '状态 1=启用 0=禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='板块管理';

-- 商品表
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL COMMENT '商品名称',
  `category_id` INT UNSIGNED COMMENT '分类 ID',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格',
  `original_price` DECIMAL(10,2) COMMENT '原价',
  `stock` INT DEFAULT 0 COMMENT '库存',
  `sales` INT DEFAULT 0 COMMENT '销量',
  `images` TEXT COMMENT '图片 JSON',
  `description` TEXT COMMENT '商品描述',
  `content` LONGTEXT COMMENT '商品详情',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `is_hot` TINYINT DEFAULT 0 COMMENT '是否热销',
  `is_new` TINYINT DEFAULT 0 COMMENT '是否新品',
  `is_recommend` TINYINT DEFAULT 0 COMMENT '是否推荐',
  `status` TINYINT DEFAULT 1 COMMENT '状态 1=上架 0=下架',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';

-- 商品分类表
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
  `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父分类 ID',
  `icon` VARCHAR(255) COMMENT '图标',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类';

-- 订单表
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '订单号',
  `member_id` INT UNSIGNED COMMENT '会员 ID',
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '订单总额',
  `pay_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '实付金额',
  `discount_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '优惠金额',
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT '状态：pending(待付款)/paid(已付款)/shipped(已发货)/completed(已完成)/cancelled(已取消)',
  `pay_time` DATETIME COMMENT '支付时间',
  `ship_time` DATETIME COMMENT '发货时间',
  `complete_time` DATETIME COMMENT '完成时间',
  `cancel_time` DATETIME COMMENT '取消时间',
  `remark` TEXT COMMENT '备注',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- 订单商品表
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL COMMENT '订单 ID',
  `product_id` INT UNSIGNED COMMENT '商品 ID',
  `product_name` VARCHAR(200) COMMENT '商品名称',
  `product_image` VARCHAR(255) COMMENT '商品图片',
  `price` DECIMAL(10,2) NOT NULL COMMENT '单价',
  `quantity` INT NOT NULL DEFAULT 1 COMMENT '数量',
  `total` DECIMAL(10,2) NOT NULL COMMENT '小计',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单商品表';

-- 会员表
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE COMMENT '用户名',
  `phone` VARCHAR(20) UNIQUE COMMENT '手机号',
  `password` VARCHAR(255) COMMENT '密码',
  `nickname` VARCHAR(100) COMMENT '昵称',
  `avatar` VARCHAR(255) COMMENT '头像',
  `gender` TINYINT DEFAULT 0 COMMENT '性别 0=未知 1=男 2=女',
  `birthday` DATE COMMENT '生日',
  `level` INT DEFAULT 1 COMMENT '会员等级',
  `points` INT DEFAULT 0 COMMENT '积分',
  `balance` DECIMAL(10,2) DEFAULT 0 COMMENT '余额',
  `total_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '累计消费',
  `order_count` INT DEFAULT 0 COMMENT '订单数',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `last_login_at` DATETIME COMMENT '最后登录时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员表';

-- 文章表 (内容管理)
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL COMMENT '标题',
  `category_id` INT UNSIGNED COMMENT '分类 ID',
  `cover` TEXT COMMENT '封面图（JSON数组，支持多图）',
  `summary` VARCHAR(500) COMMENT '摘要',
  `tags` VARCHAR(255) COMMENT '标签（逗号分隔）',
  `content` LONGTEXT COMMENT '内容',
  `author` VARCHAR(100) COMMENT '作者',
  `views` INT DEFAULT 0 COMMENT '浏览量',
  `likes` INT DEFAULT 0 COMMENT '点赞数',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `is_recommend` TINYINT DEFAULT 0 COMMENT '是否推荐',
  `status` TINYINT DEFAULT 1 COMMENT '状态 1=发布 0=草稿',
  `published_at` DATETIME COMMENT '发布时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- 文章分类表
CREATE TABLE IF NOT EXISTS `article_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
  `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父分类 ID',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章分类';

-- 营销活动表
CREATE TABLE IF NOT EXISTS `marketing_activities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL COMMENT '活动名称',
  `type` VARCHAR(50) NOT NULL COMMENT '类型：seckill(秒杀)/group(拼团)/coupon(优惠券)',
  `config` TEXT COMMENT '活动配置 JSON',
  `start_time` DATETIME COMMENT '开始时间',
  `end_time` DATETIME COMMENT '结束时间',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='营销活动表';

-- 优惠券表
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '券名称',
  `type` VARCHAR(50) DEFAULT 'discount' COMMENT '类型：discount(折扣)/fixed(满减)',
  `value` DECIMAL(10,2) NOT NULL COMMENT '面值/折扣',
  `min_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '最低消费',
  `max_amount` DECIMAL(10,2) COMMENT '最高抵扣',
  `total` INT DEFAULT 0 COMMENT '发放总量',
  `used` INT DEFAULT 0 COMMENT '已使用',
  `start_time` DATETIME COMMENT '开始时间',
  `end_time` DATETIME COMMENT '结束时间',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券表';

-- 店铺表
CREATE TABLE IF NOT EXISTS `shops` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL COMMENT '店铺名称',
  `logo` VARCHAR(255) COMMENT 'Logo',
  `description` TEXT COMMENT '店铺描述',
  `phone` VARCHAR(20) COMMENT '联系电话',
  `address` VARCHAR(500) COMMENT '地址',
  `latitude` DECIMAL(10,8) COMMENT '纬度',
  `longitude` DECIMAL(11,8) COMMENT '经度',
  `business_hours` VARCHAR(100) COMMENT '营业时间',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='店铺表';

-- 应用表
CREATE TABLE IF NOT EXISTS `apps` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '应用名称',
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT '应用标识',
  `icon` VARCHAR(255) COMMENT '图标',
  `url` VARCHAR(500) COMMENT '访问地址',
  `config` TEXT COMMENT '配置 JSON',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT DEFAULT 1 COMMENT '状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_configs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `value` TEXT COMMENT '配置值',
  `type` VARCHAR(50) DEFAULT 'text' COMMENT '类型：text/number/boolean/json',
  `group` VARCHAR(50) DEFAULT 'basic' COMMENT '分组',
  `name` VARCHAR(100) COMMENT '配置名称',
  `description` VARCHAR(500) COMMENT '说明',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置
INSERT INTO `system_configs` (`key`, `value`, `type`, `group`, `name`) VALUES
('site_name', '城市之光', 'text', 'basic', '站点名称'),
('site_logo', '', 'text', 'basic', '站点 Logo'),
('site_description', '城市之光商城', 'text', 'basic', '站点描述'),
('customer_service_phone', '400-XXX-XXXX', 'text', 'basic', '客服电话'),
('order_auto_cancel_minutes', '30', 'number', 'order', '订单自动取消时间 (分钟)'),
('freight_threshold', '99', 'number', 'order', '包邮门槛'),
('points_rate', '1', 'number', 'points', '积分比例 (1 元=1 分)');
