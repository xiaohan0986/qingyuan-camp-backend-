# 🚀 签务通 - 最终部署说明

## ✅ 已准备好的文件

### 核心配置文件
- `.env.production` - 生产环境配置模板
- `nginx.conf` - Nginx 虚拟主机配置
- `database/qianwutong.sql` - 完整数据库初始化脚本
- `server_setup.sh` - 服务器端一键配置脚本
- `QUICK_DEPLOY.bat` - Windows 一键部署脚本
- `DEPLOY_READY.md` - 详细部署指南

---

## 📦 快速部署（3 步完成）

### 方法 1：Windows 一键部署（推荐）

**双击运行：**
```
QUICK_DEPLOY.bat
```

会自动完成：
- ✅ 上传所有文件
- ✅ 设置权限
- ✅ 创建数据库
- ✅ 导入表结构
- ✅ 创建配置文件

---

### 方法 2：手动部署

#### 第 1 步：上传文件

**使用 WinSCP 或 FileZilla：**
- 主机：`gofong.com`
- 用户名：`root`
- 密码：服务器密码
- 端口：22

**上传目录：**
```
本地：D:\phpstudy_pro\WWW\qianwutong\
远程：/var/www/gofong.com/
```

**或使用命令行：**
```bash
# Windows PowerShell
scp -r D:\phpstudy_pro\WWW\qianwutong\* root@gofong.com:/var/www/gofong.com/

# 或使用 rsync（推荐）
rsync -avz -e ssh --exclude '.git' --exclude '*.md' D:/phpstudy_pro/WWW/qianwutong/ root@gofong.com:/var/www/gofong.com/
```

#### 第 2 步：SSH 登录并配置

```bash
# SSH 登录
ssh root@gofong.com

# 进入项目目录
cd /var/www/gofong.com

# 运行一键配置脚本
bash server_setup.sh
```

#### 第 3 步：配置 Nginx

```bash
# 复制 Nginx 配置
cp nginx.conf /etc/nginx/sites-available/gofong.com

# 启用站点
ln -s /etc/nginx/sites-available/gofong.com /etc/nginx/sites-enabled/

# 删除默认站点
rm -f /etc/nginx/sites-enabled/default

# 测试配置
nginx -t

# 重启 Nginx
systemctl restart nginx
```

#### 第 4 步：安装 SSL 证书

```bash
# 安装 Certbot
apt-get install certbot python3-certbot-nginx -y

# 获取证书
certbot --nginx -d gofong.com -d www.gofong.com

# 自动续期
echo "0 3 * * * certbot renew --quiet" | crontab -
```

---

## 🔐 默认账号

### 后台管理
- 网址：`https://gofong.com/admin/`
- 用户名：`admin`
- 密码：`Admin2026!@#`

### 数据库
- 数据库名：`qianwutong`
- 用户名：`qianwutong_user`
- 密码：`QwT_8xK#mP9$vL2nR5jH`

**⚠️ 部署后请立即修改密码！**

---

## 📋 部署后验证

### 1. 访问测试
```bash
# 后台登录页
curl -I https://gofong.com/admin/login.php

# API 接口
curl https://gofong.com/api/auth.php?action=login

# 首页
curl -I https://gofong.com/
```

### 2. 日志检查
```bash
# Nginx 访问日志
tail -f /var/log/nginx/gofong.com.access.log

# Nginx 错误日志
tail -f /var/log/nginx/gofong.com.error.log

# PHP 错误日志
tail -f /var/log/php/error.log
```

### 3. 权限检查
```bash
# 检查项目目录
ls -la /var/www/gofong.com/

# 检查 uploads 目录
ls -la /var/www/gofong.com/uploads/
# 应该是 drwxrwxrwx 或 drwxr-xr-x www-data www-data
```

---

## ⚠️ 安全加固

### 1. 修改默认密码

**数据库密码：**
```bash
# 编辑 .env 文件
nano /var/www/gofong.com/.env

# 修改 DB_PASS 为新密码
```

**管理员密码：**
- 登录后台 → 个人设置 → 修改密码

### 2. 删除敏感文件

```bash
# SSH 执行
cd /var/www/gofong.com

# 删除部署脚本
rm -f QUICK_DEPLOY.bat server_setup.sh deploy.bat deploy.sh

# 删除 SQL 文件（导入后）
rm -f database/qianwutong.sql

# 删除配置模板
rm -f .env.production nginx.conf

# 删除文档
rm -f *.md
```

### 3. 保护 .env 文件

```bash
chmod 600 /var/www/gofong.com/.env
chown www-data:www-data /var/www/gofong.com/.env
```

### 4. 配置防火墙

```bash
# UFW 防火墙
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw enable
ufw status
```

---

## 🆘 常见问题

### 问题 1：404 Not Found
```bash
# 检查 Nginx 配置
nginx -t
cat /etc/nginx/sites-available/gofong.com | grep root

# 检查文件是否存在
ls -la /var/www/gofong.com/api/auth.php
```

### 问题 2：403 Forbidden
```bash
# 检查权限
chmod -R 755 /var/www/gofong.com
chmod -R 777 /var/www/gofong.com/uploads
chown -R www-data:www-data /var/www/gofong.com
```

### 问题 3：500 Internal Server Error
```bash
# 查看错误日志
tail -f /var/log/nginx/gofong.com.error.log

# 检查 PHP-FPM
systemctl status php7.4-fpm

# 检查 .env 文件
cat /var/www/gofong.com/.env
```

### 问题 4：数据库连接失败
```bash
# 测试连接
mysql -u qianwutong_user -p -e "SELECT 1;"

# 检查用户权限
mysql -u root -e "SHOW GRANTS FOR 'qianwutong_user'@'localhost';"
```

---

## 📊 性能优化（可选）

### 1. 开启 Redis 缓存
```bash
# 安装 Redis
apt-get install redis-server php-redis -y

# 重启 PHP-FPM
systemctl restart php7.4-fpm
```

### 2. 配置 PHP OPcache
```ini
# /etc/php/7.4/fpm/php.ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### 3. 数据库优化
```sql
-- 添加索引
CREATE INDEX idx_phone ON customers(phone);
CREATE INDEX idx_status ON articles(status);

-- 定期优化表
OPTIMIZE TABLE customers;
OPTIMIZE TABLE articles;
```

---

## 📞 技术支持

遇到问题请查看：
1. 错误日志：`/var/log/nginx/gofong.com.error.log`
2. PHP 日志：`/var/log/php/error.log`
3. MySQL 日志：`/var/log/mysql/error.log`

---

**祝部署顺利！** 🎉

**最后更新：** 2026-04-14
