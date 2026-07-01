# 📦 签务通 - 服务器部署修复包

## 📋 问题说明

**症状：** 部署到服务器后，登录时 `/api/auth.php` 返回 404 错误

**根本原因：** nginx 配置中 `root` 路径指向了 `/admin` 子目录，导致 API 路径解析错误

---

## 📁 修复包文件清单

### 必须上传的文件

| 文件 | 说明 | 用途 |
|------|------|------|
| `admin/index.php` | 登录页面（已优化） | 修复 API 路径计算 + 移除角色登录 |
| `api/auth.php` | 认证 API（已优化） | 移除角色登录逻辑 |
| `index.php` | 根目录入口（已修改） | 自动跳转到 /admin/ |
| `fix_nginx_root.sh` | nginx 修复脚本 | **核心修复工具** |
| `nginx_baota.conf` | nginx 配置模板 | 宝塔面板配置参考 |

### 参考文档（可选上传）

| 文件 | 说明 |
|------|------|
| `NGINX_ROOT_FIX_GUIDE.md` | 完整修复指南 |
| `DEPLOYMENT_FIX_GUIDE.md` | 部署检查清单 |
| `deploy_fix.sh` | 完整部署脚本 |

---

## 🚀 快速修复步骤（3 分钟）

### 步骤 1：上传文件到服务器

**使用 scp 命令（推荐）：**

```powershell
# 在本地 PowerShell 执行（Windows）
scp D:\phpstudy_pro\WWW\www.gofong.com\admin\index.php root@你的服务器 IP:/www/wwwroot/www.gofong.com/admin/
scp D:\phpstudy_pro\WWW\www.gofong.com\api\auth.php root@你的服务器 IP:/www/wwwroot/www.gofong.com/api/
scp D:\phpstudy_pro\WWW\www.gofong.com\index.php root@你的服务器 IP:/www/wwwroot/www.gofong.com/
scp D:\phpstudy_pro\WWW\www.gofong.com\fix_nginx_root.sh root@你的服务器 IP:/tmp/
```

**或使用 FTP 工具：**
- FileZilla、WinSCP 等
- 上传到对应目录

---

### 步骤 2：执行修复脚本

**SSH 登录服务器后执行：**

```bash
# 给脚本添加权限
chmod +x /tmp/fix_nginx_root.sh

# 执行修复
bash /tmp/fix_nginx_root.sh
```

脚本会自动：
- ✅ 备份当前 nginx 配置
- ✅ 修复 root 路径（/admin → 根目录）
- ✅ 修复 API 配置
- ✅ 测试 nginx 配置
- ✅ 重载 nginx 服务
- ✅ 测试 API 接口

---

### 步骤 3：验证修复

```bash
# 测试 API
curl -v https://gofong.com/api/auth.php

# 预期：返回 JSON（不是 404 HTML）
```

**浏览器测试：**
1. 访问 `https://gofong.com/admin/`
2. 打开 F12 开发者工具
3. 执行登录
4. 查看 Network 标签，确认 `auth.php` 请求成功

---

## 🔧 手动修复（如果不使用脚本）

### 1. 修改 nginx 配置

```bash
vi /www/server/panel/vhost/nginx/www.gofong.com.conf
```

### 2. 修改两处配置

**修改 1：root 路径**
```nginx
# 修改前
root /www/wwwroot/www.gofong.com/admin;

# 修改后
root /www/wwwroot/www.gofong.com;
```

**修改 2：API 配置**
```nginx
# 修改前
location /api/ {
    try_files $uri $uri/ /api/bootstrap.php?$query_string;
}

# 修改后
location /api/ {
    try_files $uri $uri/ =404;
}
```

### 3. 测试并重载

```bash
nginx -t
systemctl reload nginx
```

---

## ✅ 验证清单

修复完成后，请确认以下项目：

- [ ] `nginx -t` 测试通过
- [ ] `curl https://gofong.com/api/auth.php` 返回 JSON
- [ ] 访问 `https://gofong.com/admin/` 显示登录页面
- [ ] 登录功能正常工作
- [ ] 浏览器 Console 没有 404 错误
- [ ] 登录成功跳转到 dashboard

---

## 🔄 回滚方案

如果修复后出现问题：

```bash
# 恢复备份（自动创建的备份）
cp /www/server/panel/vhost/nginx/www.gofong.com.conf.backup.* \
   /www/server/panel/vhost/nginx/www.gofong.com.conf

# 重载 nginx
systemctl reload nginx
```

---

## 📞 常见问题

### Q: API 仍然返回 404

**检查：**
```bash
# 1. 确认 root 路径
grep "^root" /www/server/panel/vhost/nginx/www.gofong.com.conf

# 2. 确认文件存在
ls -la /www/wwwroot/www.gofong.com/api/auth.php

# 3. 查看错误日志
tail -f /www/wwwlogs/www.gofong.com.error.log
```

### Q: 502 Bad Gateway

**解决：**
```bash
# 检查 PHP-FPM
systemctl status php-fpm-74

# 启动 PHP-FPM
systemctl start php-fpm-74
```

### Q: 权限错误

**修复：**
```bash
chown -R www:www /www/wwwroot/www.gofong.com
find /www/wwwroot/www.gofong.com -type f -exec chmod 644 {} \;
find /www/wwwroot/www.gofong.com -type d -exec chmod 755 {} \;
```

---

## 📝 修复原理

### 问题根源

```
访问：https://gofong.com/api/auth.php

修复前：
nginx root: /www/wwwroot/www.gofong.com/admin
实际路径：/www/wwwroot/www.gofong.com/admin/api/auth.php ❌ 不存在

修复后：
nginx root: /www/wwwroot/www.gofong.com
实际路径：/www/wwwroot/www.gofong.com/api/auth.php ✅ 正确
```

### 关键配置对比

| 配置项 | 修复前 | 修复后 |
|--------|--------|--------|
| root | `/www/wwwroot/www.gofong.com/admin` | `/www/wwwroot/www.gofong.com` |
| location /api/ | `try_files ... /api/bootstrap.php` | `try_files ... =404` |

---

## 🎯 上传命令速查

### Windows PowerShell

```powershell
# 设置服务器 IP（替换为你的实际 IP）
$SERVER_IP="你的服务器 IP"

# 上传文件
scp D:\phpstudy_pro\WWW\www.gofong.com\admin\index.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/admin/
scp D:\phpstudy_pro\WWW\www.gofong.com\api\auth.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/api/
scp D:\phpstudy_pro\WWW\www.gofong.com\index.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/
scp D:\phpstudy_pro\WWW\www.gofong.com\fix_nginx_root.sh root@$SERVER_IP:/tmp/
```

### Linux/Mac

```bash
# 设置服务器 IP
SERVER_IP="你的服务器 IP"

# 上传文件
scp admin/index.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/admin/
scp api/auth.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/api/
scp index.php root@$SERVER_IP:/www/wwwroot/www.gofong.com/
scp fix_nginx_root.sh root@$SERVER_IP:/tmp/
```

---

## 🎉 完成！

修复成功后，你可以：

1. 删除服务器上的临时文件：
   ```bash
   rm /tmp/fix_nginx_root.sh
   ```

2. 删除项目中的参考文档（可选）：
   ```bash
   rm NGINX_ROOT_FIX_GUIDE.md
   rm DEPLOYMENT_FIX_GUIDE.md
   rm nginx_baota.conf
   ```

3. 享受正常工作的登录功能！✅

---

**最后更新：** 2026-04-30  
**适用版本：** 签务通 v1.0  
**支持面板：** 宝塔面板 7.x+
