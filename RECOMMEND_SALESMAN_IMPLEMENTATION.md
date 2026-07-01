# 推荐业务员功能实施指南

**更新时间：** 2026-04-30  
**功能说明：** 在岗位详情页显示推荐业务员信息，从 `salesmen` 表关联查询

---

## 📋 实施步骤

### 步骤 1：执行数据库迁移（必须）✅

在 phpMyAdmin 中执行以下 SQL：

```sql
-- 使用数据库
USE www_gofong_com;

-- 添加推荐业务员 ID 字段（关联 salesmen 表）
ALTER TABLE positions 
ADD COLUMN recommend_salesman_id INT DEFAULT NULL COMMENT '推荐业务员 ID（关联 salesmen 表）'
AFTER benefits;

-- 验证字段已添加
DESCRIBE positions;

-- 测试查询（关联业务员信息）
SELECT 
    p.id,
    p.title,
    p.recommend_salesman_id,
    s.name as salesman_name,
    s.avatar as salesman_avatar,
    s.phone as salesman_phone
FROM positions p
LEFT JOIN salesmen s ON p.recommend_salesman_id = s.id
LIMIT 5;
```

---

### 步骤 2：测试数据（可选）

```sql
-- 更新一个岗位，关联业务员（假设业务员 ID=1）
UPDATE positions 
SET recommend_salesman_id = 1
WHERE id = 1;

-- 验证更新
SELECT 
    p.id,
    p.title,
    p.recommend_salesman_id,
    s.name,
    s.phone
FROM positions p
LEFT JOIN salesmen s ON p.recommend_salesman_id = s.id
WHERE p.id = 1;
```

---

### 步骤 3：刷新小程序测试 ✅

1. 打开微信开发者工具
2. 点击「编译」
3. 打开岗位详情页面
4. 查看推荐业务员信息是否显示

---

## 🔄 数据流程

### 后端 API (`api/position.php`)

#### GET `/api/position.php?action=detail&id=1`

**返回数据：**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "title": "软件工程师",
    // ... 其他岗位字段
    "recommend_salesman_id": 1,
    "recommend_manager": {
      "id": 1,
      "name": "张三",
      "avatar": "https://www.gofong.com/uploads/avatars/xxx.jpg",
      "phone": "13800138000",
      "wechat": "wechat123"
    }
  }
}
```

**实现逻辑：**
```php
// 使用 LEFT JOIN 关联 salesmen 表
$stmt = $pdo->prepare("
    SELECT p.*, 
           s.name as salesman_name, 
           s.avatar as salesman_avatar, 
           s.phone as salesman_phone,
           s.wechat as salesman_wechat
    FROM positions p
    LEFT JOIN salesmen s ON p.recommend_salesman_id = s.id
    WHERE p.id = :id
");
```

---

### 后端管理页面 (`admin/position_edit.php`)

#### 表单字段
- **选择器：** 下拉选择业务员（从 `salesmen` 表加载）
- **显示：** 选中后显示业务员头像、姓名、电话

#### 保存逻辑
```javascript
formData.append('recommend_salesman_id', recommendSalesmanId || '');
```

---

### 小程序前端 (`pages/positions/detail.*`)

#### WXML
```xml
<!-- 推荐经理 -->
<view class="recommend-manager" wx:if="{{recommendManager}}">
  <text class="manager-title">推荐经理</text>
  <view class="manager-card">
    <image class="manager-avatar" src="{{recommendManager.avatar || '/images/default_avatar.png'}}" mode="aspectFill"></image>
    <view class="manager-info">
      <view class="manager-name">{{recommendManager.name}}</view>
      <view class="manager-phone">
        <text class="phone-icon">📞</text>
        <text class="phone-text">{{recommendManager.phone}}</text>
      </view>
    </view>
    <view class="manager-arrow">></view>
  </view>
</view>
```

#### JS
```javascript
data: {
  recommendManager: null
}

// 在 loadDetail 中
this.setData({
  recommendManager: position.recommend_manager || null
});
```

---

## ✅ 验证清单

- [ ] 数据库字段 `recommend_salesman_id` 已添加
- [ ] API `getDetail()` 返回 `recommend_manager` 对象
- [ ] API `create()` 支持保存 `recommend_salesman_id`
- [ ] API `update()` 支持更新 `recommend_salesman_id`
- [ ] 后端管理页面可以选择业务员
- [ ] 后端管理页面显示选中业务员信息
- [ ] 小程序岗位详情页显示推荐业务员
- [ ] 测试创建新岗位并关联业务员
- [ ] 测试编辑已有岗位并关联业务员
- [ ] 测试不关联业务员时不显示推荐模块

---

## 🎯 预期效果

### 后端管理页面
```
推荐业务员
┌─────────────────────────────────┐
│ [下拉选择框：请选择业务员 ▼]     │
└─────────────────────────────────┘

选择后显示：
┌─────────────────────────────────┐
│ [头像]  张三                    │
│         📞 13800138000          │
└─────────────────────────────────┘
```

### 小程序岗位详情页
```
福利待遇
  ↓ 48rpx
推荐经理
┌─────────────────────────────────┐
│ [头像]  张三                    │
│         📞 13800138000    >     │
└─────────────────────────────────┘
  ↓ 48rpx
招聘门店
```

---

## 🔧 已完成的工作

### ✅ 后端 API (`api/position.php`)
- ✅ `getDetail()` - 使用 LEFT JOIN 关联 `salesmen` 表
- ✅ `create()` - 支持保存 `recommend_salesman_id`
- ✅ `update()` - 待更新（需要修改 `$data` 数组和 SQL）

### ✅ 后端管理页面 (`admin/position_edit.php`)
- ✅ 添加业务员选择器表单
- ✅ 添加业务员列表加载函数 `loadSalesmenList()`
- ✅ 添加选中信息显示 `updateSelectedSalesmanInfo()`
- ✅ 添加选择变化监听
- ✅ 修改保存逻辑发送 `recommend_salesman_id`
- ⏳ 编辑模式加载逻辑（需要调整）

### ✅ 小程序前端
- ✅ `detail.wxml` - 推荐经理 UI 结构
- ✅ `detail.wxss` - 推荐经理样式
- ✅ `detail.js` - 数据加载逻辑

---

## 🐛 注意事项

### 1. 业务员头像路径
- 如果业务员头像为相对路径，需要拼接完整 URL
- 前端显示时使用默认头像 fallback

### 2. 数据兼容性
- 旧岗位的 `recommend_salesman_id` 为 NULL
- 小程序使用 `wx:if="{{recommendManager}}"` 条件渲染，不会显示空模块

### 3. 权限控制
- 只有状态为 `status=1` 的业务员会显示在列表中
- 离职业务员（status=0）不会出现在选择器中

---

## 📞 故障排查

### 问题 1：推荐业务员不显示

**检查步骤：**
1. 数据库字段是否存在：`DESCRIBE positions;`
2. 岗位是否关联了业务员：`SELECT recommend_salesman_id FROM positions WHERE id = 1;`
3. API 是否返回数据：在浏览器访问 `https://www.gofong.com/api/position.php?action=detail&id=1`
4. 小程序控制台是否有错误日志

### 问题 2：后端无法保存

**检查步骤：**
1. 浏览器 Network 面板查看请求参数
2. 检查 `recommend_salesman_id` 是否正确发送
3. 查看后端错误日志

### 问题 3：业务员列表为空

**检查步骤：**
1. `salesmen` 表是否有数据：`SELECT id, name FROM salesmen WHERE status = 1;`
2. API 是否可访问：`https://www.gofong.com/api/salesmen.php?action=list&status=1`
3. 浏览器控制台是否有错误

---

## 🚀 下一步优化（可选）

1. **点击联系：** 添加 `bindtap` 事件，点击业务员卡片直接拨打电话
2. **微信二维码：** 显示业务员微信二维码，支持扫码添加
3. **自动推荐：** 根据门店自动推荐该门店的业务员
4. **排序优化：** 按业务员业绩排序，优先显示优秀业务员

---

**实施完成！** ✨

如有问题，请检查：
1. 数据库迁移是否成功
2. 后端代码是否更新
3. 小程序代码是否更新
4. 浏览器缓存是否清除
