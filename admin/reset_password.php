<?php
/**
 * 快速重置密码页面
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PasswordHelper.php';

session_start();

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    die('<h1>❌ 请先登录</h1>');
}

$id = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 获取销售信息
    $stmt = $pdo->prepare("SELECT id, name, phone, role_name, password_view FROM salesmen WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $salesman = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$salesman) {
        die('<h1>❌ 销售不存在</h1>');
    }
    
    // 处理重置
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($newPassword)) {
            $message = '❌ 密码不能为空';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = '❌ 两次输入的密码不一致';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = '❌ 密码长度至少 6 位';
            $messageType = 'error';
        } else {
            // 更新密码
            $bcryptPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $aesPassword = encryptPassword($newPassword);
            
            $updateStmt = $pdo->prepare("
                UPDATE salesmen 
                SET password = :password, password_view = :password_view 
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':password' => $bcryptPassword,
                ':password_view' => $aesPassword,
                ':id' => $id
            ]);
            
            $message = '✅ 密码重置成功！新密码：<strong style="color: #1890ff; font-size: 16px;">' . htmlspecialchars($newPassword) . '</strong>';
            $messageType = 'success';
            
            // 重新获取销售信息
            $stmt->execute([':id' => $id]);
            $salesman = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
} catch (Exception $e) {
    die('<h1>❌ 错误：' . $e->getMessage() . '</h1>');
}

echo "<!DOCTYPE html>
<html lang='zh-CN'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔑 重置密码</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 40px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1890ff;
            margin-bottom: 10px;
        }
        .info {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type='password'],
        input[type='text'] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            border-color: #1890ff;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .btn:hover {
            background: #40a9ff;
        }
        .btn-cancel {
            background: #f5f5f5;
            color: #666;
        }
        .btn-cancel:hover {
            background: #d9d9d9;
        }
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #f6ffed;
            border: 1px solid #b7eb8f;
            color: #389e0d;
        }
        .message.error {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            color: #cf1322;
        }
        .salesman-info {
            background: #fafafa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .salesman-info p {
            margin: 8px 0;
            color: #666;
        }
        .password-display {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Consolas', monospace;
            font-size: 16px;
            font-weight: bold;
            color: #1890ff;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔑 重置密码</h1>
        
        <div class='salesman-info'>
            <p><strong>姓名：</strong>" . htmlspecialchars($salesman['name']) . "</p>
            <p><strong>手机号：</strong>" . htmlspecialchars($salesman['phone']) . "</p>
            <p><strong>角色：</strong>" . ($salesman['role_name'] ?? '未分配') . "</p>";
            
            if (!empty($salesman['password_view'])) {
                $plainPassword = decryptPassword($salesman['password_view']);
                echo "<p><strong>当前密码：</strong><div class='password-display'>" . htmlspecialchars($plainPassword) . "</div></p>";
            } else {
                echo "<p><strong>当前密码：</strong><span style='color: #999;'>未设置或需要重置</span></p>";
            }
        echo "</div>";
        
        if ($message) {
            echo "<div class='message $messageType'>$message</div>";
        }
        
        echo "<form method='POST'>
            <div class='form-group'>
                <label>新密码 *</label>
                <input type='password' name='password' required placeholder='至少 6 位'>
            </div>
            
            <div class='form-group'>
                <label>确认密码 *</label>
                <input type='password' name='confirm_password' required placeholder='再次输入新密码'>
            </div>
            
            <button type='submit' class='btn'>✅ 重置密码</button>
            <a href='view_all_passwords.php' class='btn btn-cancel'>❌ 取消</a>
        </form>
    </div>
<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>";
