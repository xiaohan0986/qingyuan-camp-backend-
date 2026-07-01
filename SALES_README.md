# 销售管理功能说明

## 📋 功能概述

销售管理模块用于跟踪和管理客户的销售转化流程，包括销售记录创建、编辑、查询和统计。

## 🎯 主要功能

### 1. 销售统计看板
- **总销售额**：累计成交金额
- **本月销售额**：当月成交金额及环比增长
- **成交客户数**：已成交客户总数和转化率
- **跟进中客户**：当前正在跟进的潜在客户数

### 2. 销售列表管理
- **筛选功能**：
  - 关键词搜索（客户姓名/手机号）
  - 状态筛选（已成交/跟进中/已流失/已退款）
  - 销售顾问筛选
  - 日期范围筛选
  
- **列表展示**：
  - ID、客户姓名、客户手机号
  - 销售顾问、销售金额
  - 产品类型、成交日期
  - 状态标签（彩色区分）
  - 操作按钮（查看/编辑）

- **分页功能**：支持翻页浏览

### 3. 销售记录操作
- **新增销售**：创建新的销售记录
- **编辑销售**：修改已有销售记录
- **查看详情**：查看销售记录完整信息
- **数据导出**：导出 CSV 格式销售数据

## 📊 产品类型

支持的产品类型包括：
- 移民咨询
- 留学服务
- 签证代办
- 职业规划
- 其他

## 🏷️ 销售状态

- **跟进中**：正在跟进的潜在客户
- **已成交**：已成功签约的客户
- **已流失**：流失的潜在客户
- **已退款**：已退款的客户

## 🗄️ 数据库表结构

### sales 表

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | INT | 主键 ID |
| customer_name | VARCHAR(100) | 客户姓名 |
| customer_phone | VARCHAR(20) | 客户手机号 |
| salesman_id | INT | 销售顾问 ID（关联 users 表） |
| amount | DECIMAL(10,2) | 销售金额 |
| product_type | VARCHAR(50) | 产品类型 |
| status | VARCHAR(20) | 销售状态 |
| close_date | DATE | 成交日期 |
| remark | TEXT | 备注信息 |
| created_at | DATETIME | 创建时间 |
| updated_at | DATETIME | 更新时间 |

## 🔌 API 接口

### 获取统计信息
```
GET /api/sales.php?action=stats
```

### 获取销售列表
```
GET /api/sales.php?action=list&page=1&page_size=20&keyword=&status=&salesman=&start_date=&end_date=
```

### 获取销售详情
```
GET /api/sales.php?action=get&id=1
```

### 创建销售记录
```
POST /api/sales.php?action=create
Content-Type: application/json

{
    "customer_name": "张三",
    "customer_phone": "13800138000",
    "salesman_id": 1,
    "amount": 50000,
    "product_type": "移民咨询",
    "status": "已成交",
    "close_date": "2026-04-11",
    "remark": "备注信息"
}
```

### 更新销售记录
```
POST /api/sales.php?action=update
Content-Type: application/json

{
    "id": 1,
    "customer_name": "张三",
    ...
}
```

### 获取销售顾问列表
```
GET /api/sales.php?action=salesmen
```

### 导出销售数据
```
GET /api/sales.php?action=export
```

## 📁 文件结构

```
qianwutong/
├── admin/
│   ├── sales.php                          # 销售管理页面
│   └── create_sales_table.php             # 数据库表创建脚本
├── api/
│   └── sales.php                          # 销售管理 API
├── database/
│   └── create_sales_table.sql             # SQL 脚本
└── includes/
    └── header.php                         # 侧边栏菜单（已添加销售管理）
```

## 🚀 使用方法

1. **访问销售管理页面**：
   - 登录后台管理系统
   - 点击左侧菜单「销售管理」

2. **查看销售统计**：
   - 页面顶部显示 4 个统计卡片
   - 自动计算总销售额、本月销售额等指标

3. **筛选销售记录**：
   - 输入关键词或选择筛选条件
   - 点击「筛选」按钮
   - 点击「重置」清空筛选条件

4. **新增销售记录**：
   - 点击「➕ 新增销售」按钮
   - 填写客户信息、销售信息等必填项
   - 点击「保存」

5. **编辑销售记录**：
   - 在列表中点击「✏️ 编辑」按钮
   - 修改信息后保存

6. **导出数据**：
   - 点击「📥 导出」按钮
   - 自动下载 CSV 格式的销售数据

## 📝 操作日志

所有销售相关的操作都会自动记录到操作日志中：
- 创建销售记录
- 更新销售记录
- 进入销售管理页面

## ⚠️ 注意事项

1. 客户姓名、手机号、销售顾问、销售金额为必填项
2. 销售金额支持小数（精确到分）
3. 状态变更会影响统计数据的计算
4. 导出的 CSV 文件包含 UTF-8 BOM，可直接用 Excel 打开

---

**最后更新**: 2026-04-11
**版本**: v1.0
