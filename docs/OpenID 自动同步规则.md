# OpenID 自动同步规则

## 📋 功能概述

当用户在小程序中**登录**或**注册**时，系统会自动将 OpenID 同步到数据库，确保后台可以正确推送订阅消息。

---

## 🎯 核心规则

### **规则 1：数据库已存在 OpenID 则不变**
- ✅ 如果 `users.openid` 已有值，**不会覆盖**
- ✅ 如果 `mini_program_users.wechat_openid` 已有值，**不会覆盖**
- ✅ 只有在为空时才会写入

### **规则 2：自动关联两个用户表**
系统会自动关联以下两个表：
- `mini_program_users` - 小程序用户表
- `users` - 后台用户表

**关联方式**：
1. 优先使用 `mini_program_users.user_id` 字段（外键关联）
2. 如果 `user_id` 为空，通过手机号匹配 `users.phone`

### **规则 3：登录和注册都会同步**
- ✅ 用户登录时 → 同步 OpenID
- ✅ 用户注册时 → 同步 OpenID
- ✅ 已注册用户重新授权 → 更新 OpenID

---

## 📊 数据表结构

### **mini_program_users 表**
```sql
CREATE TABLE `mini_program_users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `phone` VARCHAR(20),
  `username` VARCHAR(50),
  `password` VARCHAR(255),
  `nickname` VARCHAR(100),
  `wechat_openid` VARCHAR(100),      -- 微信 OpenID
  `wechat_session_key` VARCHAR(100), -- 微信 Session Key
  `avatar` VARCHAR(255),
  `gender` TINYINT,
  `user_id` INT,                      -- 关联 users.id（关键字段）
  `skills` TEXT,
  `intended_countries` TEXT,
  `status` TINYINT DEFAULT 1,
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);
```

### **users 表**
```sql
CREATE TABLE `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `phone` VARCHAR(20),
  `username` VARCHAR(50),
  `password` VARCHAR(255),
  `nickname` VARCHAR(100),
  `openid` VARCHAR(100),              -- 微信 OpenID（用于推送）
  `role` INT DEFAULT 1,
  `status` INT DEFAULT 1,
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);
```

### **customers 表**
```sql
CREATE TABLE `customers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,                      -- 关联 users.id
  `name` VARCHAR(100),
  `phone` VARCHAR(20),
  ...
);
```

---

## 🔄 同步逻辑流程

### **场景 1：用户登录（已注册）**

```
用户点击登录
    ↓
微信返回 OpenID
    ↓
查询 mini_program_users（通过手机号）
    ↓
找到用户
    ↓
检查 user_id 是否存在？
    ├── 是 → 直接更新 users.openid
    └── 否 → 通过手机号查找 users 表
             ├── 找到 → 更新 users.openid + 关联 user_id
             └── 未找到 → 不处理（仅更新 mini_program_users）
```

**代码逻辑**（`wechat_login.php`）：
```php
// 1. 更新 mini_program_users.wechat_openid
if (empty($user['wechat_openid'])) {
    $updateStmt = $pdo->prepare("
        UPDATE mini_program_users 
        SET wechat_openid = ?, wechat_session_key = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$openid, $sessionKey, $user['id']]);
}

// 2. 同步到 users 表
if (!empty($user['user_id'])) {
    // 已有 user_id 关联，直接更新
    $syncStmt = $pdo->prepare("UPDATE users SET openid = ? WHERE id = ?");
    $syncStmt->execute([$openid, $user['user_id']]);
} else {
    // 通过手机号查找并关联
    $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $checkUserStmt->execute([$phone]);
    $userRecord = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userRecord) {
        // 更新 users.openid
        $syncStmt = $pdo->prepare("UPDATE users SET openid = ? WHERE id = ?");
        $syncStmt->execute([$openid, $userRecord['id']]);
        
        // 关联 user_id
        $linkStmt = $pdo->prepare("UPDATE mini_program_users SET user_id = ? WHERE id = ?");
        $linkStmt->execute([$userRecord['id'], $user['id']]);
    }
}
```

---

### **场景 2：用户注册（新用户）**

```
用户输入手机号 + 验证码
    ↓
微信返回 OpenID
    ↓
创建 mini_program_users 记录
    ↓
检查 users 表是否存在同手机号用户？
    ├── 是 → 更新 users.openid + 关联 user_id
    └── 否 → 创建新的 users 记录 + 关联 user_id
```

**代码逻辑**（`wechat_quick_register.php`）：
```php
// 1. 创建 mini_program_users 记录
$insertStmt = $pdo->prepare("
    INSERT INTO mini_program_users (phone, username, password, nickname, wechat_openid, ...) 
    VALUES (?, ?, ?, ?, ?, ...)
");
$insertStmt->execute([...]);
$userId = $pdo->lastInsertId();

// 2. 同步到 users 表
$checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$checkUserStmt->execute([$phone]);
$userRecord = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

if ($userRecord) {
    // 已存在，更新 openid
    $syncStmt = $pdo->prepare("UPDATE users SET openid = ? WHERE id = ?");
    $syncStmt->execute([$openid, $userRecord['id']]);
    
    // 关联 user_id
    $linkStmt = $pdo->prepare("UPDATE mini_program_users SET user_id = ? WHERE id = ?");
    $linkStmt->execute([$userRecord['id'], $userId]);
} else {
    // 不存在，创建新用户
    $createUserStmt = $pdo->prepare("
        INSERT INTO users (phone, username, password, nickname, openid, role, status) 
        VALUES (?, ?, ?, ?, ?, 1, 1)
    ");
    $createUserStmt->execute([...]);
    $newUserId = $pdo->lastInsertId();
    
    // 关联 user_id
    $linkStmt = $pdo->prepare("UPDATE mini_program_users SET user_id = ? WHERE id = ?");
    $linkStmt->execute([$newUserId, $userId]);
}
```

---

### **场景 3：已注册用户重新授权**

```
用户再次登录
    ↓
微信返回新的 OpenID（可能变化）
    ↓
更新 mini_program_users.wechat_openid
    ↓
检查 users.openid 是否为空？
    ├── 是 → 写入新的 OpenID
    └── 否 → 保持不变（不覆盖）
```

**核心原则**：
- ✅ `users.openid` 已有值 → **不覆盖**
- ✅ `mini_program_users.wechat_openid` 已有值 → **不覆盖**
- ✅ 只有在为空时才写入

---

## 🎯 数据流转示例

### **示例 1：完整流程**

**步骤 1：用户注册**
```
用户：马坤
手机号：15262156767
微信 OpenID: oWsuE1-k24rNuEwjwPqj7WCgwaGg
```

**注册后数据库状态**：
```sql
-- mini_program_users 表
id: 13
phone: '15262156767'
wechat_openid: 'oWsuE1-k24rNuEwjwPqj7WCgwaGg'
user_id: 25

-- users 表
id: 25
phone: '15262156767'
openid: 'oWsuE1-k24rNuEwjwPqj7WCgwaGg'
nickname: '马坤'

-- customers 表
id: 13
user_id: 25
name: '马坤'
phone: '15262156767'
```

**步骤 2：后台推送订阅消息**
```sql
-- 查询客户 13 的 OpenID
SELECT c.name, c.phone, c.user_id, u.openid
FROM customers c
LEFT JOIN users u ON c.user_id = u.id
WHERE c.id = 13;

-- 结果
name: '马坤'
phone: '15262156767'
user_id: 25
openid: 'oWsuE1-k24rNuEwjwPqj7WCgwaGg' ✅
```

**步骤 3：发送订阅消息**
```php
$sender->sendProgressNotification('oWsuE1-k24rNuEwjwPqj7WCgwaGg', $progressData);
```

---

## ⚠️ 注意事项

### **1. OpenID 唯一性**
- 每个小程序有独立的 OpenID
- 不同小程序的 OpenID **不能混用**
- 本系统的 OpenID 仅对应小程序 APPID：`wxa1d87433a193a38b`

### **2. 数据一致性**
- `mini_program_users.user_id` 必须关联 `users.id`
- `customers.user_id` 必须关联 `users.id`
- `users.openid` 用于推送，必须准确

### **3. 手动修复**
如果发现 OpenID 为空，可以手动执行 SQL：

```sql
-- 方案 1：直接更新 users.openid
UPDATE users 
SET openid = 'oWsuE1-k24rNuEwjwPqj7WCgwaGg' 
WHERE id = 25;

-- 方案 2：通过手机号更新
UPDATE users u
INNER JOIN mini_program_users m ON u.phone = m.phone
SET u.openid = m.wechat_openid
WHERE u.phone = '15262156767';

-- 方案 3：批量修复所有缺失的 openid
UPDATE users u
INNER JOIN mini_program_users m ON u.phone = m.phone
SET u.openid = m.wechat_openid
WHERE u.openid IS NULL AND m.wechat_openid IS NOT NULL;
```

---

## 🧪 测试方法

### **测试 1：新用户注册**

1. 小程序端：使用新手机号注册
2. 数据库查询：
   ```sql
   SELECT m.id, m.phone, m.wechat_openid, m.user_id, 
          u.id as uid, u.openid, u.phone as uphone
   FROM mini_program_users m
   LEFT JOIN users u ON m.user_id = u.id
   WHERE m.phone = '新手机号'
   ORDER BY m.id DESC LIMIT 1;
   ```
3. 验证：
   - ✅ `m.wechat_openid` 有值
   - ✅ `m.user_id` 有值
   - ✅ `u.openid` 有值且等于 `m.wechat_openid`

---

### **测试 2：已注册用户登录**

1. 小程序端：使用已注册手机号登录
2. 数据库查询：
   ```sql
   SELECT * FROM users WHERE phone = '手机号';
   SELECT * FROM mini_program_users WHERE phone = '手机号';
   ```
3. 验证：
   - ✅ `users.openid` 有值
   - ✅ `mini_program_users.wechat_openid` 有值
   - ✅ 两个 OpenID 一致

---

### **测试 3：后台推送**

1. 后台选择一个客户（该客户有 OpenID）
2. 更新进度
3. 验证：
   - ✅ 微信收到订阅消息
   - ✅ 消息内容正确

---

## 📝 日志记录

所有同步操作都会记录到错误日志（如果失败）：

```
error_log('同步 openid 失败：' . $e->getMessage());
error_log('同步 users 表失败：' . $e->getMessage());
```

**日志位置**：
- 宝塔面板：网站 → 日志 → 错误日志
- 或文件：`/www/wwwroot/www.gofong.com/logs/error.log`

---

## 🔧 文件清单

### **修改的文件**

1. `D:\phpstudy_pro\WWW\www.gofong.com\api\wechat_login.php`
   - 添加 OpenID 同步逻辑

2. `D:\phpstudy_pro\WWW\www.gofong.com\api\wechat_quick_register.php`
   - 添加 OpenID 同步逻辑
   - 自动创建/关联 users 用户

3. `D:\phpstudy_pro\WWW\www.gofong.com\api\customer.php`
   - 添加订阅消息推送逻辑（之前已完成）

---

## 🚀 上传文件

请上传以下文件到服务器：

```
/www/wwwroot/www.gofong.com/api/wechat_login.php
/www/wwwroot/www.gofong.com/api/wechat_quick_register.php
```

---

## 🎯 预期效果

### **之前的问题**
```
客户 13（马坤）
├── customers.user_id: 25
├── users.id: 25
└── users.openid: NULL ❌ 无法推送
```

### **修复后的效果**
```
用户登录/注册后
├── mini_program_users.wechat_openid: oWsuE1-xxx ✅
├── mini_program_users.user_id: 25 ✅
└── users.openid: oWsuE1-xxx ✅ 可以推送
```

---

**文档创建时间**：2026-05-20  
**最后更新**：2026-05-20  
**负责人**：签务通技术团队
