# 销售人员管理功能说明

## 📋 功能概述

销售人员管理模块用于管理公司的销售团队，包括销售人员的基本信息、等级、业绩等。

## 🎯 主要功能

### 1. 统计看板
- **总人数**：所有销售人员总数
- **在职人数**：当前在职的销售人员
- **离职人数**：已离职的销售人员
- **总销售额**：累计销售业绩
- **总成交量**：累计成交单数

### 2. 销售人员列表
- **筛选功能**：
  - 关键词搜索（姓名/手机号/微信号）
  - 等级筛选（小白/初级/中级/高级/金牌/王牌）
  - 状态筛选（在职/离职）
  
- **列表展示**：
  - ID、姓名、手机号、微信号
  - 等级（彩色标签区分）
  - 所属门店
  - 销售额、成交量
  - 状态标签
  - 操作按钮（编辑/删除）

- **分页功能**：支持翻页浏览

### 3. 销售人员操作
- **新增销售**：创建新的销售人员记录
- **编辑销售**：修改已有销售人员信息
- **删除销售**：删除销售人员记录

## 📊 字段说明

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| 姓名 | VARCHAR(50) | ✅ | 销售人员姓名 |
| 手机号 | VARCHAR(20) | ✅ | 联系方式（唯一） |
| 微信号 | VARCHAR(50) | ❌ | 微信联系方式 |
| 等级 | VARCHAR(20) | ❌ | 小白/初级/中级/高级/金牌/王牌，默认"小白" |
| 所属门店 | INT | ❌ | 关联门店表 ID |
| 所属门店名称 | VARCHAR(100) | ❌ | 门店名称（冗余字段） |
| 销售额 | DECIMAL(12,2) | ❌ | 累计销售金额，默认 0 |
| 成交量 | INT | ❌ | 累计成交单数，默认 0 |
| 状态 | VARCHAR(20) | ❌ | 在职/离职，默认"在职" |
| 备注 | TEXT | ❌ | 其他说明信息 |

## 🏷️ 等级体系

- **小白** - 新人销售（灰色）
- **初级** - 有一定经验（蓝色）
- **中级** - 成熟销售（绿色）
- **高级** - 资深销售（橙色）
- **金牌** - 优秀销售（粉色）
- **王牌** - 顶级销售（紫色）

## 🗄️ 数据库表结构

### salesmen 表

```sql
CREATE TABLE salesmen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT '姓名',
    phone VARCHAR(20) NOT NULL COMMENT '手机号',
    wechat VARCHAR(50) DEFAULT '' COMMENT '微信号',
    level VARCHAR(20) DEFAULT '小白' COMMENT '等级',
    store_id INT DEFAULT NULL COMMENT '所属门店 ID',
    store_name VARCHAR(100) DEFAULT '' COMMENT '所属门店名称',
    sales_amount DECIMAL(12,2) DEFAULT 0 COMMENT '销售额',
    deal_count INT DEFAULT 0 COMMENT '成交量',
    status VARCHAR(20) DEFAULT '在职' COMMENT '状态',
    remark TEXT DEFAULT NULL COMMENT '备注',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_store (store_id),
    INDEX idx_status (status),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🔌 API 接口

### 获取统计信息
```
GET /api/salesmen.php?action=stats
```

### 获取销售人员列表
```
GET /api/salesmen.php?action=list&page=1&page_size=20&keyword=&level=&status=
```

### 获取销售人员详情
```
GET /api/salesmen.php?action=get&id=1
```

### 创建销售人员
```
POST /api/salesmen.php?action=create
Content-Type: application/json

{
    "name": "张三",
    "phone": "13800138000",
    "wechat": "zhangsan123",
    "level": "初级",
    "store_id": 1,
    "store_name": "北京店",
    "sales_amount": 50000,
    "deal_count": 10,
    "status": "在职",
    "remark": "备注信息"
}
```

### 更新销售人员
```
POST /api/salesmen.php?action=update
Content-Type: application/json

{
    "id": 1,
    "name": "张三",
    ...
}
```

### 删除销售人员
```
POST /api/salesmen.php?action=delete
Content-Type: application/json

{
    "id": 1
}
```

### 获取等级选项
```
GET /api/salesmen.php?action=levels
```

### 获取门店列表
```
GET /api/salesmen.php?action=stores
```

## 📁 文件结构

```
qianwutong/
├── admin/
│   ├── salesmen.php                       # 销售人员管理页面
│   ├── install_salesmen.php               # 安装脚本
│   └── create_salesmen_table.php          # 数据库表创建脚本
├── api/
│   └── salesmen.php                       # 销售人员管理 API
├── database/
│   └── create_salesmen_table.sql          # SQL 建表脚本
└── SALESMEN_README.md                     # 功能说明文档
```

## 🚀 安装步骤

### 方法一：通过安装向导（推荐）

1. 访问：`http://localhost/admin/install_salesmen.php`
2. 系统会自动创建数据库表
3. 点击"进入销售人员管理"开始使用

### 方法二：手动执行 SQL

1. 打开 phpMyAdmin 或数据库管理工具
2. 选择项目数据库
3. 执行 `database/create_salesmen_table.sql` 文件中的 SQL 语句
4. 访问 `http://localhost/admin/salesmen.php` 开始使用

### 方法三：通过后台脚本

1. 访问：`http://localhost/admin/create_salesmen_table.php`
2. 系统会自动创建数据库表

## 📝 使用方法

### 1. 访问销售人员管理页面
- 登录后台管理系统
- 点击左侧菜单「销售人员管理」

### 2. 查看统计数据
- 页面顶部显示 5 个统计卡片
- 自动计算总人数、在职/离职人数、销售额、成交量

### 3. 筛选销售人员
- 输入关键词或选择筛选条件
- 点击「筛选」按钮
- 点击「重置」清空筛选条件

### 4. 新增销售人员
- 点击「➕ 新增销售」按钮
- 填写信息：
  - 姓名（必填）
  - 手机号（必填）
  - 微信号（可选）
  - 等级（默认小白）
  - 所属门店（从下拉列表选择）
  - 销售额（默认 0）
  - 成交量（默认 0）
  - 状态（默认为在职）
  - 备注（可选）
- 点击「保存」

### 5. 编辑销售人员
- 在列表中点击「✏️ 编辑」按钮
- 修改信息后保存
- 手机号必须唯一，不能与其他人重复

### 6. 删除销售人员
- 在列表中点击「🗑️ 删除」按钮
- 确认后删除

## ⚠️ 注意事项

1. **手机号唯一性**：销售人员的手机号不能重复
2. **必填字段**：姓名和手机号为必填项
3. **销售额格式**：支持小数，精确到分
4. **状态管理**：销售人员离职后建议改为"离职"状态，而不是直接删除
5. **门店关联**：如果选择了门店，会自动保存门店名称（冗余字段，方便查询）

## 🔗 与其他模块的关联

- **门店管理**：销售人员可以关联到具体门店
- **销售管理**：销售人员与客户的销售记录可以关联（后续开发）
- **操作日志**：所有操作会自动记录到操作日志

---

**最后更新**: 2026-04-11  
**版本**: v1.0  
**作者**: 旺财 🐶
