# 岗位材料上传功能使用说明

## 功能概述

为岗位管理模块添加了**所需材料**和**附件文件**上传功能：

1. **所需材料**：文本列表，列出申请该岗位需要提供的材料（如：护照、简历、学历证书等）
2. **附件文件**：支持上传文件（图片、PDF、Word、Excel 等），可在页面中预览和下载

## 安装步骤

### 1. 运行数据库迁移

在浏览器中访问：
```
http://localhost/add_position_materials.php
```

这将：
- 在 `positions` 表中添加 `required_materials` 字段（JSON 格式）
- 在 `positions` 表中添加 `attachment_files` 字段（JSON 格式）
- 创建上传目录 `uploads/positions/`
- 创建安全配置文件防止上传目录执行 PHP

### 2. 检查上传目录权限

确保以下目录可写：
```
D:\phpstudy_pro\WWW\qianwutong\uploads\positions\
```

## 使用方法

### 添加岗位材料

1. 进入 **岗位管理** 页面
2. 点击 **新增岗位** 或 **编辑** 现有岗位
3. 在 **所需材料** 字段：
   - 输入材料名称（如：护照）
   - 按回车或点击"+ 添加"按钮
   - 可添加多个材料
   - 鼠标悬停在材料上可删除
4. 在 **附件文件** 区域：
   - 点击或拖拽文件到上传区域
   - 支持格式：JPG、PNG、PDF、DOC、DOCX、XLS、XLSX、TXT
   - 文件大小限制：10MB
   - 上传完成后可预览（图片）、下载或删除

### 查看和下载

- **材料清单**：在岗位详情页显示为列表
- **附件文件**：
  - 图片：点击预览按钮可查看大图
  - 所有文件：点击下载按钮可下载
  - 前端展示时可通过解析 JSON 字段获取

## 数据存储

- **所需材料**：存储为逗号分隔的文本（如：`护照，简历，学历证书`）
- **附件文件**：存储为 JSON 格式，包含：
  ```json
  [
    {
      "file_name": "原始文件名.pdf",
      "file_path": "uploads/positions/2026-04/file_xxx.pdf",
      "file_size": 123456,
      "mime_type": "application/pdf",
      "extension": ".pdf",
      "upload_time": "2026-04-09 12:00:00"
    }
  ]
  ```

## 前端调用示例

### 获取岗位详情时解析材料

```javascript
fetch('api/position.php?action=detail&id=1')
    .then(res => res.json())
    .then(res => {
        const data = res.data;
        
        // 解析所需材料
        const materials = data.required_materials 
            ? data.required_materials.split(',') 
            : [];
        
        // 解析附件文件
        const files = data.attachment_files 
            ? JSON.parse(data.attachment_files) 
            : [];
        
        // 显示材料列表
        materials.forEach(material => {
            console.log('所需材料：', material);
        });
        
        // 显示文件列表
        files.forEach(file => {
            console.log('附件：', file.file_name);
            console.log('路径：', file.file_path);
            console.log('预览链接：', file.file_path);
        });
    });
```

### 在前端页面展示

```html
<!-- 材料清单 -->
<div class="materials-section">
    <h3>所需材料</h3>
    <ul id="materialsList"></ul>
</div>

<!-- 附件文件 -->
<div class="attachments-section">
    <h3>附件文件</h3>
    <div id="filesList"></div>
</div>

<script>
// 假设 positionData 是从 API 获取的岗位数据
const materials = positionData.required_materials 
    ? positionData.required_materials.split(',') 
    : [];
const files = positionData.attachment_files 
    ? JSON.parse(positionData.attachment_files) 
    : [];

// 渲染材料列表
document.getElementById('materialsList').innerHTML = materials
    .map(m => `<li>${escapeHtml(m)}</li>`)
    .join('');

// 渲染文件列表
document.getElementById('filesList').innerHTML = files
    .map(f => `
        <div class="file-item">
            <a href="${f.file_path}" target="_blank">${escapeHtml(f.file_name)}</a>
            ${f.mime_type.startsWith('image/') 
                ? `<img src="${f.file_path}" alt="预览" style="max-width: 200px;">` 
                : ''}
        </div>
    `)
    .join('');
</script>
```

## 安全说明

1. **文件类型限制**：仅允许上传指定的 MIME 类型
2. **文件大小限制**：最大 10MB
3. **上传目录保护**：`.htaccess` 文件禁止执行 PHP
4. **文件名安全**：使用唯一文件名，避免文件名注入

## 常见问题

### Q: 上传失败，提示权限错误
A: 检查 `uploads/positions/` 目录是否有写入权限

### Q: 文件上传成功但无法预览
A: 检查文件路径是否正确，确保 Web 服务器可以访问 `uploads` 目录

### Q: 大文件上传超时
A: 调整 `php.ini` 中的以下配置：
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300
```

## 文件清单

- `add_position_materials.php` - 数据库迁移脚本
- `api/upload.php` - 文件上传 API
- `api/position.php` - 已更新，支持材料字段
- `admin/position.php` - 已更新，添加上传界面
