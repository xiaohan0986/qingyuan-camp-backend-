# 青园营地管理后台

基于 PHP + MySQL 的客户关系管理系统（Visa Industry CRM）

## 📋 功能模块

- **数据大屏** - 实时业务数据概览
- **客户管理** - 全生命周期客户跟踪
- **岗位管理** - 职位发布与管理
- **营销获客** - 多渠道营销自动化
- **商品管理** - 商品上下架、分类、库存管理
- **订单管理** - 订单处理、发货、售后
- **会员管理** - 会员信息、等级、积分管理
- **内容管理** - 文章、资讯、公告管理
- **店铺管理** - 门店信息、地址管理
- **系统设置** - 基础配置、权限管理

## 🚀 快速开始

### 1. 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- 支持 PDO 扩展

### 2. 安装步骤

#### 数据库初始化

```bash
# 导入数据库
mysql -h localhost -u shopauba -p shopauba < database/init.sql

# 创建安装锁文件（标记已安装）
echo "<?php echo date('Y-m-d H:i:s');" > install.lock
```

### 3. 访问后台

浏览器访问：`http://shop.auba.cn/admin/`

### 3. 访问后台

浏览器访问：`http://shop.auba.cn/admin/`

## 📁 目录结构

```
shop.auba.cn/
├── admin/                  # 后台管理
│   ├── assets/            # 静态资源
│   ├── css/               # 样式文件
│   ├── images/            # 图片资源
│   ├── js/                # JavaScript
│   ├── login.php          # 登录页
│   ├── dashboard.php      # 数据大屏
│   ├── customer.php       # 客户管理
│   └── position.php       # 岗位管理
├── api/                   # API 接口
├── config/                # 配置文件
│   ├── database.php       # 数据库配置（从 .env 读取）
│   └── config.php         # 系统配置
├── database/              # 数据库脚本
│   └── init.sql           # 初始化 SQL
├── includes/              # 核心类库
│   ├── Database.php       # 数据库类
│   ├── Auth.php           # 认证工具
│   ├── JWT.php            # JWT 实现
│   └── functions.php      # 公共函数
├── docs/                  # 开发文档
├── sql/                   # SQL 迁移脚本
├── uploads/               # 上传文件
└── .env                   # 环境变量（不提交到仓库）
```

## 🔧 配置说明

### 数据库配置

编辑 `config/config.php` 或 `.env` 文件：

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'shopauba');
define('DB_USER', 'shopauba');
define('DB_PASS', 'hb098634');
```

### 上传目录权限

确保以下目录可写：

```bash
chmod -R 755 uploads/
```

## 🔐 安全建议

1. **修改默认密码** - 首次登录后立即修改密码
2. **定期备份数据库** - 建议每日自动备份
3. **开启 HTTPS** - 生产环境必须使用 HTTPS
4. **配置 .env** - 将 `.env` 中的 `JWT_SECRET` 设为随机字符串
5. **限制访问 IP** - 可在服务器层面限制后台访问 IP
6. **删除安装脚本** - 生产环境确保 `install.lock` 存在且 `install.php` 不可访问

## 🛠️ 开发计划

- [ ] CRUD 完整实现
- [ ] 订单流程管理
- [ ] 会员等级系统
- [ ] 营销活动创建
- [ ] 数据统计报表
- [ ] 权限管理系统细化

## 📝 版本信息

- 版本：v1.0.0
- 技术栈：PHP + MySQL (原生无框架)

---

© 2026 青园营地管理后台
