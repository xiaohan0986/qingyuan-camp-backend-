<?php
/**
 * 修改 images 字段为 MEDIUMTEXT
 */
require_once __DIR__ . '/../../config/config.php';

echo "<h1>🔧 修改数据库字段</h1>";

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h3>当前字段信息：</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM products WHERE Field = 'images'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>字段：<strong>{$row['Field']}</strong></p>";
    echo "<p>类型：<strong>{$row['Type']}</strong></p>";
    echo "<p>TEXT 类型最大：" . number_format(65535) . " 字节 (64KB)</p>";
    echo "<p>MEDIUMTEXT 类型最大：" . number_format(16777215) . " 字节 (16MB)</p>";
    
    echo "<h3>执行修改：</h3>";
    $pdo->exec("ALTER TABLE products MODIFY COLUMN images MEDIUMTEXT COMMENT '商品图片 (JSON)'");
    
    echo "<p style='color:green;font-size:18px;'>✅ 修改成功！</p>";
    echo "<p>现在 images 字段可以存储最大 16MB 的数据，足够存 10 张 Base64 图片了。</p>";
    
    // 验证修改
    $stmt = $pdo->query("SHOW COLUMNS FROM products WHERE Field = 'images'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>修改后类型：<strong>{$row['Type']}</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ 错误：" . htmlspecialchars($e->getMessage()) . "</p>";
}
