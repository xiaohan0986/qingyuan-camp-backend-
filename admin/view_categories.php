<?php
/**
 * 查看分类数据
 */
require_once __DIR__ . '/../includes/Database.php';

echo "<h2>📁 分类数据查看</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'article_categories'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red; font-size: 18px;'>❌ 分类表不存在，请先安装</p>";
        echo "<p><a href='install_article_categories.php' style='padding: 12px 24px; background: #1890ff; color: white; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 10px;'>📦 安装分类表</a></p>";
        exit;
    }
    
    // 获取所有分类
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name,
            c.color,
            c.sort_order,
            c.created_at,
            c.updated_at,
            COUNT(a.id) as article_count
        FROM article_categories c
        LEFT JOIN articles a ON c.name = a.category
        GROUP BY c.id, c.name, c.color, c.sort_order, c.created_at, c.updated_at
        ORDER BY c.sort_order ASC, c.id ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green; font-size: 16px;'>✅ 分类表存在，共 <strong>" . count($categories) . "</strong> 个分类</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<table style='width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>";
    echo "<thead>";
    echo "<tr style='background: linear-gradient(135deg, #1890ff, #40a9ff); color: white;'>";
    echo "<th style='padding: 16px; text-align: left;'>ID</th>";
    echo "<th style='padding: 16px; text-align: left;'>分类名称</th>";
    echo "<th style='padding: 16px; text-align: left;'>颜色</th>";
    echo "<th style='padding: 16px; text-align: left;'>排序</th>";
    echo "<th style='padding: 16px; text-align: left;'>文章数</th>";
    echo "<th style='padding: 16px; text-align: left;'>创建时间</th>";
    echo "<th style='padding: 16px; text-align: left;'>更新时间</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($categories as $cat) {
        $bgColor = $cat['article_count'] > 0 ? '#f0f9ff' : '#ffffff';
        echo "<tr style='background: " . $bgColor . "; border-bottom: 1px solid #e5e7eb;'>";
        echo "<td style='padding: 16px;'>" . $cat['id'] . "</td>";
        echo "<td style='padding: 16px; font-weight: 600; color: #262626;'>" . htmlspecialchars($cat['name']) . "</td>";
        echo "<td style='padding: 16px;'><span style='display: inline-block; width: 24px; height: 24px; background: " . htmlspecialchars($cat['color']) . "; border-radius: 6px; vertical-align: middle;'></span> " . htmlspecialchars($cat['color']) . "</td>";
        echo "<td style='padding: 16px;'>" . $cat['sort_order'] . "</td>";
        echo "<td style='padding: 16px;'><span style='padding: 4px 12px; background: " . ($cat['article_count'] > 0 ? '#e6f7ff' : '#f5f5f5') . "; color: " . ($cat['article_count'] > 0 ? '#0050b3' : '#999') . "; border-radius: 12px; font-size: 13px; font-weight: 600;'>" . $cat['article_count'] . "</span></td>";
        echo "<td style='padding: 16px; color: #999; font-size: 13px;'>" . $cat['created_at'] . "</td>";
        echo "<td style='padding: 16px; color: #999; font-size: 13px;'>" . $cat['updated_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    // 测试添加分类
    echo "<hr style='margin: 30px 0; border: none; border-top: 2px solid #e5e7eb;'>";
    echo "<h3>🧪 测试添加分类</h3>";
    echo "<form method='POST' action='' style='display: flex; gap: 12px; margin-bottom: 20px;'>";
    echo "<input type='text' name='test_name' placeholder='分类名称' required style='flex: 1; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;'>";
    echo "<input type='color' name='test_color' value='#ff6b6b' style='width: 60px; padding: 4px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer;'>";
    echo "<button type='submit' name='add_test' style='padding: 12px 24px; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>➕ 添加测试</button>";
    echo "</form>";
    
    if (isset($_POST['add_test']) && !empty($_POST['test_name'])) {
        $testName = trim($_POST['test_name']);
        $testColor = trim($_POST['test_color']) ?: '#ff6b6b';
        
        // 检查是否已存在
        $stmt = $pdo->prepare('SELECT id FROM article_categories WHERE name = ?');
        $stmt->execute([$testName]);
        if ($stmt->fetch()) {
            echo "<p style='color: orange;'>⚠️ 分类已存在</p>";
        } else {
            // 获取最大排序
            $stmt = $pdo->query('SELECT MAX(sort_order) as max_order FROM article_categories');
            $row = $stmt->fetch();
            $sortOrder = ($row['max_order'] ?? 0) + 1;
            
            // 插入
            $stmt = $pdo->prepare('INSERT INTO article_categories (name, color, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$testName, $testColor, $sortOrder]);
            
            echo "<p style='color: green;'>✅ 测试分类添加成功！刷新页面查看</p>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        }
    }
    
    // 测试删除分类
    echo "<h3>测试删除分类</h3>";
    echo "<form method='POST' action='' style='display: flex; gap: 12px; align-items: center;'>";
    echo "<input type='number' name='delete_id' placeholder='分类 ID' min='1' required style='width: 120px; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;'>";
    echo "<button type='submit' name='delete_test' onclick='return confirm(\"确定要删除吗？\")' style='padding: 12px 24px; background: linear-gradient(135deg, #f5222d, #ff4d4f); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>删除测试</button>";
    echo "</form>";
    
    if (isset($_POST['delete_id'])) {
        $deleteId = intval($_POST['delete_id']);
        
        // 获取分类名称
        $stmt = $pdo->prepare('SELECT name FROM article_categories WHERE id = ?');
        $stmt->execute([$deleteId]);
        $cat = $stmt->fetch();
        
        if (!$cat) {
            echo "<p style='color: red;'>❌ 分类不存在</p>";
        } else {
            // 检查是否有文章
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category = ?');
            $stmt->execute([$cat['name']]);
            $row = $stmt->fetch();
            
            if ($row['count'] > 0) {
                echo "<p style='color: red;'>❌ 该分类下有 " . $row['count'] . " 篇文章，无法删除</p>";
            } else {
                $stmt = $pdo->prepare('DELETE FROM article_categories WHERE id = ?');
                $stmt->execute([$deleteId]);
                echo "<p style='color: green;'>✅ 分类删除成功！刷新页面查看</p>";
                echo "<script>setTimeout(() => location.reload(), 1000);</script>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 错误：" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr style='margin: 30px 0; border: none; border-top: 2px solid #e5e7eb;'>";
echo "<p><a href='article.php' style='padding: 12px 24px; background: #1890ff; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>返回文章管理</a></p>";
