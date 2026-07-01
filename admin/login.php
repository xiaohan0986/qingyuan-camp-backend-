<?php
// 确保会话在输出 HTML 之前启动（供 CSRF Token 使用）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 青园营地管理后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1890ff 0%, #40a9ff 50%, #f093fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .bg-animation li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: animate 25s linear infinite;
            bottom: 0;
            border-radius: 4px;
        }

        .bg-animation li:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .bg-animation li:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .bg-animation li:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .bg-animation li:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .bg-animation li:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .bg-animation li:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .bg-animation li:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .bg-animation li:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .bg-animation li:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .bg-animation li:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 4px;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 40px 120px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
            border-radius: 16px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .logo p {
            color: #8c8c8c;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #262626;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: #1890ff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #8c8c8c;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fff2f0;
            border: 1px solid #ffccc7;
            color: #cf1322;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            color: #8c8c8c;
            font-size: 13px;
        }

        .footer a {
            color: #1890ff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <ul class="bg-animation">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>

    <div class="login-container">
        <div class="logo">
            <picture>
                <source srcset="images/logo.webp" type="image/webp">
                <img src="images/logo.png" alt="青园营地" class="logo-img" width="120" height="120" decoding="async">
            </picture>
            <h1>青园营地管理后台</h1>
            <p>欢迎回来，请登录您的账号</p>
        </div>

        <div class="error-message" id="errorMsg"></div>

        <form id="loginForm" method="POST" action="login_action.php">
            <?php require_once __DIR__ . '/../includes/CsrfHelper.php'; echo CsrfHelper::hiddenField(); ?>
            <div class="form-group">
                <label>用户名</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" required placeholder="请输入用户名" autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label>密码</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" required placeholder="请输入密码" autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="submit-btn">登 录</button>
        </form>

        <div class="footer">
            <p>© 2026 青园营地管理后台 v1.0.0</p>
        </div>
    </div>

    <script>
        // 检查 URL 参数是否有错误信息
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error) {
            const errorMap = {
                'csrf': '安全验证失败，请刷新页面后重试',
                'invalid': '用户名或密码错误',
                'empty': '用户名和密码不能为空',
                'locked': '账号已被锁定，请联系管理员'
            };
            const errorMsg = document.getElementById('errorMsg');
            errorMsg.textContent = errorMap[error] || '登录失败，请重试';
            errorMsg.style.display = 'block';
        }

        // 表单提交验证
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.querySelector('[name="username"]').value.trim();
            const password = this.querySelector('[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                const errorMsg = document.getElementById('errorMsg');
                errorMsg.textContent = '用户名和密码不能为空';
                errorMsg.style.display = 'block';
            }
        });
    </script>
</body>
</html>
