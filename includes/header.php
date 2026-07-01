<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>青园营地管理后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            font-size: 20px;
            font-weight: 600;
        }
        
        .admin-nav {
            margin-top: 12px;
            display: flex;
            gap: 16px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-nav a.active {
            background: rgba(255,255,255,0.3);
        }
        
        .admin-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>青园营地管理后台</h1>
        <nav class="admin-nav">
            <a href="index.php">首页</a>
            <a href="reports.php">客户管理</a>
            <a href="position.php">岗位管理</a>
            <a href="user.php">用户管理</a>
            <a href="login.php?action=logout">退出</a>
        </nav>
    </header>
    <main class="admin-content">
