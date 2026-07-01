# DX Storage API 快速参考

**API 基础地址：** `https://dx.gofong.com`

---

## 📋 接口总览

| 功能 | 方法 | 接口地址 | 说明 |
|------|------|----------|------|
| **文件管理** | | | |
| 上传文件 | POST | `/api/upload.php` | 表单上传 |
| 获取列表 | GET | `/api/files.php` | 支持分页、搜索、过滤 |
| 删除文件 | POST | `/api/delete.php` | 支持批量 |
| 批量删除 | POST | `/api/batch_delete.php` | 批量删除 |
| 移动文件 | POST | `/api/move_file.php` | 移动到分组 |
| **分组管理** | | | |
| 获取分组 | GET | `/api/groups.php?action=list` | 所有分组 |
| 创建分组 | POST | `/api/groups.php?action=create` | JSON |
| 更新分组 | POST | `/api/groups.php?action=update` | JSON |
| 删除分组 | POST | `/api/groups.php?action=delete` | JSON |

---

## 🔥 常用接口

### 1. 上传文件

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('https://dx.gofong.com/api/upload.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    console.log('上传成功:', data.data.full_url);
});
```

**响应：**
```json
{
    "code": 200,
    "data": {
        "id": 123,
        "full_url": "https://dx.gofong.com/uploads/abcd1234.jpg",
        "file_size": 102400,
        "width": 1920,
        "height": 1080
    }
}
```

---

### 2. 获取文件列表

```javascript
// 基本查询
fetch('https://dx.gofong.com/api/files.php?page=1&page_size=20')
    .then(res => res.json())
    .then(data => console.log(data.data.files));

// 带搜索
fetch('https://dx.gofong.com/api/files.php?search=test&type=png')
    .then(res => res.json())
    .then(data => console.log(data.data.files));

// 指定分组
fetch('https://dx.gofong.com/api/files.php?group_id=5')
    .then(res => res.json())
    .then(data => console.log(data.data.files));
```

**查询参数：**
- `page` - 页码（默认 1）
- `page_size` - 每页数量（默认 20，最大 100）
- `search` - 搜索关键词
- `type` - 文件类型（png, jpg 等）
- `group_id` - 分组 ID（0=根目录）

---

### 3. 删除文件

```javascript
// 单个删除
fetch('https://dx.gofong.com/api/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id: 123})
});

// 批量删除
fetch('https://dx.gofong.com/api/delete.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ids: [1, 2, 3]})
});
```

---

### 4. 移动文件到分组

```javascript
// 移动到分组
fetch('https://dx.gofong.com/api/move_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        ids: [1, 2, 3],
        group_id: 5
    })
});

// 移动到根目录（取消分组）
fetch('https://dx.gofong.com/api/move_file.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        ids: [1, 2, 3],
        group_id: 0
    })
});
```

---

### 5. 分组管理

```javascript
// 获取所有分组
fetch('https://dx.gofong.com/api/groups.php?action=list')
    .then(res => res.json())
    .then(data => console.log(data.data));

// 创建分组
fetch('https://dx.gofong.com/api/groups.php?action=create', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        name: '新产品',
        parent_id: 0
    })
});

// 更新分组
fetch('https://dx.gofong.com/api/groups.php?action=update', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        id: 5,
        name: '最新产品'
    })
});

// 删除分组
fetch('https://dx.gofong.com/api/groups.php?action=delete', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({group_id: 5})
});
```

---

## 📊 数据库表

### dx_images（文件表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| filename | VARCHAR | 原始文件名 |
| store_name | VARCHAR | 存储文件名 |
| file_path | VARCHAR | 相对路径 |
| file_url | VARCHAR | 相对 URL |
| file_size | INT | 文件大小（字节） |
| file_type | VARCHAR | MIME 类型 |
| extension | VARCHAR | 扩展名 |
| width | INT | 宽度 |
| height | INT | 高度 |
| group_id | INT | 分组 ID |
| group_path | VARCHAR | 分组路径 |
| created_at | DATETIME | 创建时间 |

### dx_groups（分组表）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| name | VARCHAR | 分组名称 |
| pinyin | VARCHAR | 拼音文件夹名 |
| parent_id | INT | 父分组 ID |
| sort_order | INT | 排序 |
| created_at | DATETIME | 创建时间 |

---

## ⚙️ 配置

### config/database.php

```php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'qianwutong',
    'username' => 'qianwutong',
    'password' => 'hb098634',
];
```

### config/app.php

```php
return [
    'upload' => [
        'max_size' => 10 * 1024 * 1024,  // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
    ],
    'storage' => [
        'base_path' => __DIR__ . '/../uploads',
        'base_url' => '/uploads',
    ],
];
```

---

## ❌ 错误处理

```javascript
fetch('https://dx.gofong.com/api/upload.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    if (data.code === 200) {
        // 成功
        console.log('成功:', data.data);
    } else {
        // 失败
        console.error('错误:', data.message);
    }
})
.catch(err => {
    console.error('网络错误:', err);
});
```

### 常见错误

| 错误信息 | 原因 |
|----------|------|
| 没有上传文件 | 缺少 `file` 字段 |
| 文件过大 | 超过 10MB |
| 不支持的文件类型 | 非图片格式 |
| 分组名称不能为空 | 创建分组未提供名称 |
| 该分组下还有 X 个文件 | 无法删除非空分组 |

---

## 🛠️ 工具

### cURL 测试

```bash
# 上传文件
curl -X POST "https://dx.gofong.com/api/upload.php" \
  -F "file=@/path/to/image.jpg"

# 获取文件列表
curl "https://dx.gofong.com/api/files.php?page=1&page_size=10"

# 删除文件
curl -X POST "https://dx.gofong.com/api/delete.php" \
  -H "Content-Type: application/json" \
  -d '{"id":123}'

# 创建分组
curl -X POST "https://dx.gofong.com/api/groups.php?action=create" \
  -H "Content-Type: application/json" \
  -d '{"name":"测试分组","parent_id":0}'
```

---

## 📝 注意事项

1. **文件上传：** 表单字段名必须为 `file`
2. **路径格式：** 返回的 `file_path` 和 `file_url` 都是相对路径（`/uploads/xxx`）
3. **CORS：** API 支持跨域请求
4. **文件大小：** 最大 10MB
5. **文件类型：** 仅支持图片（jpg, jpeg, png, gif, webp, bmp）
6. **分组删除：** 只能删除空分组

---

**更新时间：** 2026-05-05  
**版本：** v1.0
