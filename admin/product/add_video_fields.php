<?php
/**
 * 添加视频字段到 products 表
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h1>添加视频字段</h1>";

// 检查字段是否存在
$columns = $db->fetchAll("SHOW COLUMNS FROM products LIKE 'video%'");
echo "<h2>当前 video 相关字段</h2>";
if (empty($columns)) {
    echo "<p>无 video 相关字段</p>";
} else {
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
}

echo "<h2>执行 ALTER TABLE</h2>";

// 添加 video_url 字段
$sql1 = "ALTER TABLE products ADD COLUMN IF NOT EXISTS `video_url` VARCHAR(500) DEFAULT '' COMMENT '主图视频 URL'";
try {
    $conn->query($sql1);
    echo "<p style='color:green;'>✅ video_url 字段添加成功（或已存在）</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ video_url 失败：" . htmlspecialchars($e->getMessage()) . "</p>";
}

// 添加 video_cover 字段
$sql2 = "ALTER TABLE products ADD COLUMN IF NOT EXISTS `video_cover` VARCHAR(500) DEFAULT '' COMMENT '视频封面图 URL'";
try {
    $conn->query($sql2);
    echo "<p style='color:green;'>✅ video_cover 字段添加成功（或已存在）</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ video_cover 失败：" . htmlspecialchars($e->getMessage()) . "</p>";
}

// 添加 selling_points 字段
$sql3 = "ALTER TABLE products ADD COLUMN IF NOT EXISTS `selling_points` TEXT COMMENT '商品买点'";
try {
    $conn->query($sql3);
    echo "<p style='color:green;'>✅ selling_points 字段添加成功（或已存在）</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ selling_points 失败：" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>验证结果</h2>";
$columns = $db->fetchAll("SHOW COLUMNS FROM products LIKE 'video%'");
if (empty($columns)) {
    echo "<p>仍无 video 相关字段</p>";
} else {
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
}

echo "<p><a href='check_table_structure.php'>查看完整表结构</a></p>";
