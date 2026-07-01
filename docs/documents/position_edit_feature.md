# 岗位管理编辑功能说明

## 功能概述

岗位管理页面的编辑按钮已配置为跳转到独立的编辑页面 `position_edit.php`，并自动复用岗位信息。

## 使用流程

### 1. 访问岗位管理页面
```
http://localhost/qianwutong/admin/position.php
```

### 2. 点击编辑按钮
在岗位列表中，点击任意岗位行的"编辑"按钮。

### 3. 自动跳转并加载信息
系统会自动跳转到：
```
http://localhost/qianwutong/admin/position_edit.php?id={岗位 ID}
```

并自动加载该岗位的所有信息到表单中，包括：
- ✅ 岗位名称
- ✅ 岗位分类
- ✅ 所属国家/城市
- ✅ 签证类型
- ✅ 学历要求
- ✅ 薪资范围
- ✅ 岗位标签
- ✅ 岗位描述
- ✅ 福利待遇
- ✅ 状态

### 4. 编辑并保存
修改需要更新的字段后，点击：
- **💾 保存草稿** - 保存为草稿状态（status=0）
- **🚀 发布岗位** - 直接发布（status=1）

保存后自动返回岗位列表页。

## 技术实现

### position.php（第 1499 行）
```javascript
<a href="position_edit.php?id=${item.id}" style="color: #1890ff;">编辑</a>
```

### position_edit.php
- 通过 `$_GET['id']` 获取岗位 ID
- 判断是否为编辑模式（`$isEditMode = $positionId > 0`）
- 自动调用 `loadPositionData(id)` 加载岗位详情
- 填充所有表单字段

## 新增岗位
点击岗位列表页的"➕ 新增岗位"按钮，跳转到：
```
http://localhost/qianwutong/admin/position_edit.php
```
（无 ID 参数，为新增模式）

## 相关文件

- 列表页面：`admin/position.php`
- 编辑页面：`admin/position_edit.php`
- API 接口：`api/position.php`

## 更新时间
2026-04-10
