# DX Storage 对象存储 API 文档

**版本：** v1.0  
**最后更新：** 2026-05-05  
**项目地址：** `D:\phpstudy_pro\WWW\dx.gofong.com`  
**API 基础地址：** `https://dx.gofong.com`

---

## 📋 目录

- [概述](#概述)
- [快速开始](#快速开始)
- [文件管理 API](#文件管理-api)
  - [上传文件](#上传文件)
  - [获取文件列表](#获取文件列表)
  - [删除文件](#删除文件)
  - [批量删除](#批量删除)
  - [移动文件到分组](#移动文件到分组)
- [分组管理 API](#分组管理-api)
  - [获取分组列表](#获取分组列表)
  - [创建分组](#创建分组)
  - [更新分组](#更新分组)
  - [删除分组](#删除分组)
- [数据库结构](#数据库结构)
- [配置说明](#配置说明)
- [错误码说明](#错误码说明)

---

## 概述

DX Storage 是一个轻量级对象存储系统，支持图片上传、分组管理、批量操作等功能。

### 特性

- ✅ 支持 JPG、JPEG、PNG、GIF、WebP、BMP 格式
- ✅ 最大文件大小：10MB
- ✅ 自动重命名文件（4 字母 +4 数字）
- ✅ 分组管理（支持层级结构）
- ✅ 批量操作（删除、移动）
- ✅ CORS 跨域支持
- ✅ 自动环境检测（本地/线上）

### 请求格式

- **Content-Type:** `application/json`
- **字符编码:** `UTF-8`
- **跨域:** 支持（`Access-Control-Allow-Origin: *`）

---

## 快速开始

### 1. 配置文件

编辑 `config/database.php`：

```php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'qianwutong',
    'username' => 'qianwutong',
    'password' => 'hb098634',
    'charset' => 'utf8mb4',
];
```

### 2. 初始化数据库

运行 `admin/install.php` 创建数据表。

### 3. 测试 API

```bash
# 获取文件列表
curl "https://dx.gofong.com/api/files.php?action=list&page=1&page_size=10"

# 上传文件
curl -X POST "https://dx.gofong.com/api/upload.php" \
  -F "file=@/path/to/image.jpg"
```

---

## 文件管理 API

### 上传文件

**接口地址：** `POST /api/upload.php`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| file | File | 是 | 上传的文件（表单字段名必须为 `file`） |

**请求示例：**

```javascript
// JavaScript Fetch
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('https://dx.gofong.com/api/upload.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => console.log(data));
```

```bash
# cURL
curl -X POST "https://dx.gofong.com/api/upload.php" \
  -F "file=@/path/to/image.jpg"
```

**响应示例：**

```json
{
    "code": 200,
    "message": "上传成功",
    "data": {
        "id": 123,
        "filename": "original_name.jpg",
        "store_name": "abcd1234.jpg",
        "file_path": "/uploads/abcd1234.jpg",
        "file_url": "/uploads/abcd1234.jpg",
        "full_url": "https://dx.gofong.com/uploads/abcd1234.jpg",
        "file_size": 102400,
        "width": 1920,
        "height": 1080,
        "url": "https://dx.gofong.com/uploads/abcd1234.jpg"
    },
    "timestamp": 1777960800
}
```

**响应字段说明：**

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 文件 ID（数据库主键） |
| filename | string | 原始文件名 |
| store_name | string | 存储文件名（自动重命名） |
| file_path | string | 相对路径（`/uploads/xxx`） |
| file_url | string | 相对路径（同 `file_path`） |
| full_url | string | 完整 URL（包含协议和域名） |
| file_size | int | 文件大小（字节） |
| width | int | 图片宽度（像素） |
| height | int | 图片高度（像素） |
| url | string | 完整 URL（兼容旧版本） |

**错误响应：**

```json
{
    "code": 500,
    "message": "没有上传文件",
    "debug": {
        "time": "2026-05-05 14:00:00",
        "POST": {},
        "FILES": {}
    }
}
```

---

### 获取文件列表

**接口地址：** `GET /api/files.php`

**请求参数：**

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| action | string | 否 | - | 固定为 `list`（可省略） |
| search | string | 否 | - | 搜索关键词（匹配文件名） |
| type | string | 否 | - | 文件类型过滤（如 `png`, `jpg`） |
| group_id | string | 否 | - | 分组 ID（`0`=只看根目录） |
| page | int | 否 | 1 | 页码 |
| page_size | int | 否 | 20 | 每页数量（最大 100） |

**请求示例：**

```javascript
// 获取第一页，每页 20 个
fetch('https://dx.gofong.com/api/files.php?page=1&page_size=20')
    .then(res => res.json())
    .then(data => console.log(data));

// 搜索包含 "test" 的 PNG 文件
fetch('https://dx.gofong.com/api/files.php?search=test&type=png')
    .then(res => res.json())
    .then(data => console.log(data));

// 获取分组 ID 为 5 的文件
fetch('https://dx.gofong.com/api/files.php?group_id=5')
    .then(res => res.json())
    .then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "files": [
            {
                "id": 123,
                "filename": "test.png",
                "store_name": "abcd1234.png",
                "file_path": "/uploads/abcd1234.png",
                "file_url": "/uploads/abcd1234.png",
                "file_size": 102400,
                "file_type": "image/png",
                "extension": "png",
                "width": 1920,
                "height": 1080,
                "category": "",
                "tags": "",
                "download_count": 0,
                "created_at": "2026-05-05 14:00:00",
                "group_id": 5,
                "group_path": "test_group"
            }
        ],
        "pagination": {
            "page": 1,
            "page_size": 20,
            "total": 100,
            "total_pages": 5
        }
    },
    "timestamp": 1777960800
}
```

**分页说明：**

- `total`: 总记录数
- `total_pages`: 总页数
- `page`: 当前页码
- `page_size`: 每页数量

---

### 删除文件

**接口地址：** `POST /api/delete.php`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 否 | 单个文件 ID |
| ids | array | 否 | 文件 ID 数组（批量删除） |

**注意：** `id` 和 `ids` 至少提供一个，`id` 优先级更高。

**请求示例：**

```javascript
// 删除单个文件
fetch('https://dx.gofong.com/api/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id: 123})
})
.then(res => res.json())
.then(data => console.log(data));

// 批量删除
fetch('https://dx.gofong.com/api/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ids: [1, 2, 3]})
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "删除成功",
    "data": {
        "deleted_count": 3,
        "files": ["test1.png", "test2.png", "test3.png"]
    },
    "timestamp": 1777960800
}
```

---

### 批量删除

**接口地址：** `POST /api/batch_delete.php`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | 是 | 文件 ID 数组 |

**请求示例：**

```javascript
fetch('https://dx.gofong.com/api/batch_delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ids: [1, 2, 3, 4, 5]})
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "成功删除 5 个文件",
    "deleted": 5,
    "timestamp": 1777960800
}
```

---

### 移动文件到分组

**接口地址：** `POST /api/move_file.php`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | 是 | 文件 ID 数组 |
| group_id | int | 否 | 目标分组 ID（`0` 或 `null`=移动到根目录） |

**请求示例：**

```javascript
// 移动到分组 ID 为 5
fetch('https://dx.gofong.com/api/move_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        ids: [1, 2, 3],
        group_id: 5
    })
})
.then(res => res.json())
.then(data => console.log(data));

// 移动到根目录（取消分组）
fetch('https://dx.gofong.com/api/move_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        ids: [1, 2, 3],
        group_id: 0
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "移动成功",
    "data": {
        "moved_count": 3,
        "group_name": "测试分组"
    },
    "timestamp": 1777960800
}
```

---

## 分组管理 API

### 获取分组列表

**接口地址：** `GET /api/groups.php?action=list`

**请求参数：** 无

**请求示例：**

```javascript
fetch('https://dx.gofong.com/api/groups.php?action=list')
    .then(res => res.json())
    .then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "data": [
        {
            "id": 1,
            "name": "测试分组",
            "pinyin": "cszf",
            "parent_id": 0,
            "sort_order": 0,
            "created_at": "2026-05-05 10:00:00",
            "updated_at": "2026-05-05 10:00:00"
        },
        {
            "id": 2,
            "name": "产品图片",
            "pinyin": "cptp",
            "parent_id": 0,
            "sort_order": 1,
            "created_at": "2026-05-05 11:00:00",
            "updated_at": "2026-05-05 11:00:00"
        }
    ]
}
```

---

### 创建分组

**接口地址：** `POST /api/groups.php?action=create`

**请求参数：**

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| name | string | 是 | - | 分组名称 |
| parent_id | int | 否 | 0 | 父分组 ID（支持层级） |

**请求示例：**

```javascript
fetch('https://dx.gofong.com/api/groups.php?action=create', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        name: '新产品',
        parent_id: 0
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 3,
        "name": "新产品",
        "pinyin": "xcp"
    }
}
```

**说明：**

- 自动根据中文名称生成拼音文件夹名
- 会在 `uploads/` 目录下创建对应的物理文件夹

---

### 更新分组

**接口地址：** `POST /api/groups.php?action=update`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 分组 ID |
| name | string | 是 | 新的分组名称 |

**请求示例：**

```javascript
fetch('https://dx.gofong.com/api/groups.php?action=update', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        id: 3,
        name: '最新产品'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "更新成功"
}
```

**说明：**

- 更新分组名称时，会自动重命名物理文件夹
- 拼音会自动重新生成

---

### 删除分组

**接口地址：** `POST /api/groups.php?action=delete` 或 `POST /api/delete_group.php`

**请求参数：**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| group_id | int | 是 | 分组 ID |
| id | int | 是 | 分组 ID（`delete_group.php` 使用此参数） |

**请求示例：**

```javascript
// 使用 groups.php
fetch('https://dx.gofong.com/api/groups.php?action=delete', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({group_id: 3})
})
.then(res => res.json())
.then(data => console.log(data));

// 使用 delete_group.php
fetch('https://dx.gofong.com/api/delete_group.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({group_id: 3})
})
.then(res => res.json())
.then(data => console.log(data));
```

**响应示例：**

```json
{
    "code": 200,
    "message": "分组删除成功",
    "data": {
        "deleted_group": {
            "id": 3,
            "name": "测试分组",
            "pinyin": "cszf"
        },
        "file_count": 0
    }
}
```

**限制：**

- 只能删除空分组（没有文件的分组）
- 如果分组下有文件，会返回错误提示

---

## 数据库结构

### dx_images 表（文件表）

```sql
CREATE TABLE `dx_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL COMMENT '原始文件名',
    `store_name` VARCHAR(100) NOT NULL COMMENT '存储文件名',
    `file_path` VARCHAR(500) NOT NULL COMMENT '文件路径（相对路径）',
    `file_url` VARCHAR(500) NOT NULL COMMENT '文件 URL（相对路径）',
    `file_size` INT NOT NULL COMMENT '文件大小（字节）',
    `file_type` VARCHAR(100) COMMENT 'MIME 类型',
    `extension` VARCHAR(10) COMMENT '文件扩展名',
    `width` INT COMMENT '图片宽度',
    `height` INT COMMENT '图片高度',
    `category` VARCHAR(50) DEFAULT '' COMMENT '分类',
    `tags` TEXT COMMENT '标签',
    `download_count` INT DEFAULT 0 COMMENT '下载次数',
    `created_at` DATETIME NOT NULL COMMENT '创建时间',
    `updated_at` DATETIME COMMENT '更新时间',
    `upload_ip` VARCHAR(50) COMMENT '上传 IP',
    `group_id` INT DEFAULT 0 COMMENT '分组 ID',
    `group_path` VARCHAR(100) COMMENT '分组路径（拼音）',
    `status` TINYINT DEFAULT 1 COMMENT '状态：1=正常，0=删除',
    INDEX `idx_group` (`group_id`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件管理表';
```

### dx_groups 表（分组表）

```sql
CREATE TABLE `dx_groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '分组名称',
    `pinyin` VARCHAR(100) NOT NULL COMMENT '拼音文件夹名',
    `parent_id` INT DEFAULT 0 COMMENT '父分组 ID',
    `sort_order` INT DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL COMMENT '创建时间',
    `updated_at` DATETIME COMMENT '更新时间',
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件分组表';
```

---

## 配置说明

### config/database.php

数据库连接配置：

```php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'qianwutong',
    'username' => 'qianwutong',
    'password' => 'hb098634',
    'charset' => 'utf8mb4',
];
```

### config/app.php

系统配置：

```php
return [
    // 上传配置
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
        'base_dir' => __DIR__ . '/../uploads',
    ],
    
    // 存储配置
    'storage' => [
        'base_path' => __DIR__ . '/../uploads',  // 物理路径
        'base_url' => '/uploads',  // Web 路径
    ],
];
```

### 环境适配

**本地开发：**
- 网站根目录：`D:/phpstudy_pro/WWW/`
- 项目路径：`D:/phpstudy_pro/WWW/dx.gofong.com/`
- 访问地址：`http://localhost/dx.gofong.com/`

**线上环境（独立域名）：**
- 网站根目录：`/var/www/dx.gofong.com/`
- 访问地址：`https://dx.gofong.com/`
- `base_url`: `/uploads`（无需修改）

**线上环境（子目录）：**
- 网站根目录：`/var/www/gofong.com/`
- 项目路径：`/var/www/gofong.com/dx/`
- 访问地址：`https://gofong.com/dx/`
- `base_url`: `/dx/uploads`（需要修改）

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 405 | 请求方法不允许 |
| 500 | 服务器内部错误 |

### 常见错误信息

| 错误信息 | 原因 | 解决方案 |
|----------|------|----------|
| 没有上传文件 | 请求中没有 `file` 字段 | 确保表单字段名为 `file` |
| 文件过大 | 文件超过 10MB | 压缩文件或修改配置 |
| 不支持的文件类型 | 文件扩展名不在允许列表中 | 检查文件类型或修改配置 |
| 分组名称不能为空 | 创建分组时 `name` 为空 | 提供分组名称 |
| 该分组下还有 X 个文件 | 尝试删除非空分组 | 先移走或删除分组内文件 |
| 分组不存在 | 指定的分组 ID 无效 | 检查分组 ID 是否正确 |
| 数据库错误 | 数据库连接失败或 SQL 错误 | 检查数据库配置 |

---

## 📝 使用示例

### 完整的前端上传示例

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>文件上传示例</title>
</head>
<body>
    <input type="file" id="fileInput" accept="image/*">
    <button onclick="uploadFile()">上传</button>
    <div id="result"></div>

    <script>
        function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('请选择文件');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            
            fetch('https://dx.gofong.com/api/upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.code === 200) {
                    document.getElementById('result').innerHTML = 
                        '<img src="' + data.data.full_url + '" style="max-width:300px;">' +
                        '<p>上传成功！</p>';
                } else {
                    alert('上传失败：' + data.message);
                }
            })
            .catch(err => alert('上传错误：' + err));
        }
    </script>
</body>
</html>
```

### 获取并展示文件列表

```javascript
async function loadFiles(page = 1, pageSize = 20) {
    const response = await fetch(
        `https://dx.gofong.com/api/files.php?page=${page}&page_size=${pageSize}`
    );
    const data = await response.json();
    
    if (data.code === 200) {
        const files = data.data.files;
        const html = files.map(file => `
            <div class="file-item">
                <img src="https://dx.gofong.com${file.file_url}" alt="${file.filename}">
                <p>${file.filename}</p>
                <p>大小：${(file.file_size / 1024).toFixed(2)} KB</p>
                <p>上传时间：${file.created_at}</p>
            </div>
        `).join('');
        
        document.getElementById('fileList').innerHTML = html;
        
        // 更新分页信息
        document.getElementById('totalPages').textContent = 
            `共 ${data.data.pagination.total_pages} 页`;
    }
}

// 加载第一页
loadFiles(1);
```

---

## 🔧 开发工具

### 测试页面

项目包含以下测试/调试页面：

- `/admin/check_all_files.php` - 检查所有文件
- `/admin/check_db.php` - 检查数据库记录
- `/admin/diagnose.php` - 诊断工具
- `/admin/folder_view.php` - 文件夹视图

### 日志文件

- `/logs/upload_error.log` - 上传错误日志

---

## 📞 技术支持

如有问题，请检查：

1. 数据库配置是否正确
2. `uploads/` 目录是否有写入权限
3. PHP 错误日志
4. 浏览器控制台网络请求

---

**文档生成时间：** 2026-05-05  
**API 版本：** v1.0  
**最后更新：** 2026-05-05
