# 📚 签务通项目文档

本项目文档包含对象存储系统（DX Storage）的完整 API 文档和使用指南。

---

## 📋 文档列表

### 1. DX Storage API 文档

| 文档 | 说明 | 适用场景 |
|------|------|----------|
| [DX_STORAGE_API.md](./DX_STORAGE_API.md) | **完整 API 文档** | 详细开发参考 |
| [DX_STORAGE_QUICK_REFERENCE.md](./DX_STORAGE_QUICK_REFERENCE.md) | **快速参考手册** | 日常开发速查 |

---

## 🚀 快速开始

### 查看完整文档

打开 [`DX_STORAGE_API.md`](./DX_STORAGE_API.md) 查看：
- 完整的 API 接口说明
- 请求/响应示例
- 数据库结构
- 配置说明
- 错误码说明

### 日常开发速查

打开 [`DX_STORAGE_QUICK_REFERENCE.md`](./DX_STORAGE_QUICK_REFERENCE.md) 查看：
- 接口总览表
- 常用代码片段
- cURL 测试命令
- 快速配置指南

---

## 🔗 API 接口总览

### 文件管理

| 功能 | 方法 | 接口 |
|------|------|------|
| 上传文件 | POST | `/api/upload.php` |
| 获取列表 | GET | `/api/files.php` |
| 删除文件 | POST | `/api/delete.php` |
| 批量删除 | POST | `/api/batch_delete.php` |
| 移动文件 | POST | `/api/move_file.php` |

### 分组管理

| 功能 | 方法 | 接口 |
|------|------|------|
| 获取分组 | GET | `/api/groups.php?action=list` |
| 创建分组 | POST | `/api/groups.php?action=create` |
| 更新分组 | POST | `/api/groups.php?action=update` |
| 删除分组 | POST | `/api/groups.php?action=delete` |

---

## 📁 项目结构

```
dx.gofong.com/
├── api/                      # API 接口
│   ├── upload.php           # 上传文件
│   ├── files.php            # 文件列表
│   ├── delete.php           # 删除文件
│   ├── batch_delete.php     # 批量删除
│   ├── move_file.php        # 移动文件
│   ├── groups.php           # 分组管理
│   └── delete_group.php     # 删除分组
├── admin/                    # 管理后台
│   ├── folder_view.php      # 文件夹视图
│   ├── install.php          # 安装脚本
│   └── *.php                # 各种测试/诊断工具
├── config/                   # 配置文件
│   ├── database.php         # 数据库配置
│   ├── app.php              # 应用配置
│   └── config.app.php       # 配置覆盖（可选）
├── utils/                    # 工具类
│   └── pinyin.php           # 拼音转换
├── uploads/                  # 上传文件目录
└── logs/                     # 日志目录
```

---

## 🛠️ 开发工具

### 测试页面

访问以下页面进行测试：

- `http://localhost/dx.gofong.com/admin/folder_view.php` - 文件夹视图
- `http://localhost/dx.gofong.com/admin/check_db.php` - 数据库检查
- `http://localhost/dx.gofong.com/admin/diagnose.php` - 诊断工具

### 日志文件

- `logs/upload_error.log` - 上传错误日志

---

## 📞 技术支持

遇到问题时：

1. 查看 [完整 API 文档](./DX_STORAGE_API.md) 的错误码说明
2. 检查数据库配置（`config/database.php`）
3. 检查 `uploads/` 目录权限
4. 查看 PHP 错误日志
5. 检查浏览器控制台网络请求

---

## 📝 更新日志

### 2026-05-05
- ✅ 创建完整的 API 文档
- ✅ 创建快速参考手册
- ✅ 整理项目结构和配置说明

---

**文档维护：** 签务通开发团队  
**最后更新：** 2026-05-05
