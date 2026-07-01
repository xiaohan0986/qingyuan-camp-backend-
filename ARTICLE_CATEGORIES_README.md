# 📁 文章分类管理功能说明

## 功能概述

在文章管理页面新增**分类管理**功能，支持对文章分类进行增、改、删操作，并智能检测分类下是否存在文章，有文章时不可删除。

## 安装步骤

### 1. 执行数据库安装
访问：
```
http://localhost/qianwutong/admin/install_article_categories.php
```

这将创建 `article_categories` 表并初始化 6 个默认分类：
- 政策（#667eea）
- 热点（#f5222d）
- 公告（#faad14）
- 新闻（#52c41a）
- 通知（#13c2c2）
- 法规（#722ed1）

### 2. 检查安装状态
访问：
```
http://localhost/qianwutong/admin/check_article_categories.php
```

## 使用方法

### 进入分类管理
1. 打开文章管理页面：`http://localhost/qianwutong/admin/article.php`
2. 点击筛选栏右侧的 **📁 分类管理** 按钮

### 添加分类
1. 在分类管理弹窗顶部，输入分类名称
2. 选择分类颜色（可选，默认 #667eea）
3. 点击 **➕ 添加分类** 按钮

### 编辑分类
1. 在分类列表中，找到要编辑的分类
2. 点击 **✏️ 编辑** 按钮
3. 修改分类名称和颜色
4. 确认后自动保存

### 删除分类
1. 在分类列表中，找到要删除的分类
2. 点击 **🗑️ 删除** 按钮
3. 确认删除

**注意**：如果分类下存在文章，删除按钮将禁用，并提示需要先移动或删除相关文章。

## 技术实现

### 数据库表结构
```sql
CREATE TABLE article_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,      -- 分类名称
  color VARCHAR(20) DEFAULT '#667eea',   -- 分类颜色
  sort_order INT DEFAULT 0,              -- 排序
  article_count INT DEFAULT 0,           -- 文章数量（自动统计）
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### API 接口

#### 获取分类列表
```
GET ../api/article.php?action=categories
```

#### 添加分类
```
POST ../api/article.php?action=add_category
Content-Type: application/json

{
  "name": "分类名称",
  "color": "#667eea"
}
```

#### 更新分类
```
POST ../api/article.php?action=update_category
Content-Type: application/json

{
  "id": 1,
  "name": "新分类名称",
  "color": "#f5222d"
}
```

#### 删除分类
```
POST ../api/article.php?action=delete_category
Content-Type: application/json

{
  "id": 1
}
```

## 安全特性

✅ **防误删保护**：分类下有文章时禁止删除  
✅ **名称唯一性**：分类名称不能重复  
✅ **数据一致性**：更新分类名称时自动同步文章表  
✅ **权限验证**：需要管理员登录  

## 文件清单

### 新增文件
- `database/create_article_categories_table.sql` - 数据库表结构
- `admin/install_article_categories.php` - 安装脚本
- `admin/check_article_categories.php` - 检查脚本

### 修改文件
- `admin/article.php` - 添加分类管理按钮和弹窗
- `api/article.php` - 添加分类管理 API 接口

## 界面预览

### 分类管理弹窗
```
┌─────────────────────────────────────┐
│  📁 分类管理                    ×   │
├─────────────────────────────────────┤
│  [输入分类名称] [#667eea] [添加]   │
├─────────────────────────────────────┤
│  🟦 政策 (15 篇)     [✏️] [🗑️]     │
│  🟥 热点 (8 篇)      [✏️] [🗑️]     │
│  🟨 公告 (5 篇)      [✏️] [🗑️]     │
│  🟩 新闻 (12 篇)     [✏️] [🗑️]     │
└─────────────────────────────────────┘
```

## 注意事项

1. **首次使用必须先安装**：访问安装脚本创建分类表
2. **删除限制**：有文章的分类无法删除
3. **名称唯一**：不能添加同名分类
4. **颜色格式**：支持 Hex 颜色代码（如 #667eea）
5. **自动同步**：修改分类名称会自动更新文章表

## 常见问题

### Q: 点击分类管理按钮没反应？
A: 请先访问安装脚本创建分类表。

### Q: 为什么删除按钮是灰色的？
A: 该分类下存在文章，需要先处理文章才能删除。

### Q: 如何批量修改文章分类？
A: 在文章管理中筛选该分类的文章，批量编辑即可。

---

**更新时间**：2026-04-11  
**版本**：v1.0
