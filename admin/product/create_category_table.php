<?php
/**
 * 创建商品分类表
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

try {
    // 检查表是否存在
    $exists = $db->fetchOne("SHOW TABLES LIKE 'product_categories'");
    
    if ($exists) {
        echo "<h2>✅ 表已存在</h2>";
        echo "<p>product_categories 表已存在，无需创建。</p>";
    } else {
        // 创建表
        $sql = "CREATE TABLE `product_categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '分类 ID',
            `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
            `sort` INT DEFAULT 0 COMMENT '排序（数字越小越靠前）',
            `status` TINYINT DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类表'";
        
        $db->query($sql);
        
        echo "<h2>✅ 表创建成功</h2>";
        echo "<p>product_categories 表已创建成功！</p>";
        
        // 插入示例数据
        $examples = [
            ['水果', 1, 1],
            ['蔬菜', 2, 1],
            ['肉类', 3, 1],
            ['海鲜', 4, 1],
            ['零食', 5, 1],
            ['饮料', 6, 1],
        ];
        
        $db->insert('product_categories', ['name' => $ex[0], 'sort' => $ex[1], 'status' => $ex[2]]);
        
        echo "<h3>✅ 已插入 " . count($examples) . " 条示例数据</h3>";
        echo "<ul>";
        foreach ($examples as $ex) {
            echo "<li>{$ex[0]}</li>";
        }
        echo "</ul>";
        
        echo "<hr>";
        echo "<h3>📋 表结构：</h3>";
        echo "<pre>";
        $columns = $db->fetchAll("DESCRIBE product_categories");
        foreach ($columns as $col) {
            echo sprintf("%-20s %-15s %-10s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key']
            );
        }
        echo "</pre>";
    }
    
    echo "<hr>";
    echo "<h3>📊 当前分类数据：</h3>";
    $categories = $db->fetchAll("SELECT * FROM product_categories ORDER BY sort ASC, id DESC");
    if (empty($categories)) {
        echo "<p>暂无数据</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>名称</th><th>排序</th><th>状态</th><th>创建时间</th></tr>";
        foreach ($categories as $c) {
            echo "<tr>";
            echo "<td>{$c['id']}</td>";
            echo "<td>{$c['name']}</td>";
            echo "<td>{$c['sort']}</td>";
            echo "<td>" . ($c['status'] ? '启用' : '禁用') . "</td>";
            echo "<td>{$c['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<a href='product_categories.php'>👉 进入分类管理页面</a>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ 错误</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
