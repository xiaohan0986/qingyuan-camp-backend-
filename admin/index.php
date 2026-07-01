<?php
/**
 * 智能安装检测
 * 自动判断是否已安装，未安装则显示安装向导
 */
session_start();

$installLockFile = __DIR__ . '/../install.lock';
$envFile = __DIR__ . '/../.env';
$isInstalled = file_exists($installLockFile) && file_exists($envFile);

// 如果已登录，直接跳转到 dashboard
if (isset($_SESSION['user_id']) && (isset($_SESSION['role_id']) || isset($_SESSION['role']))) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isInstalled ? '登录 - 青园营地' : '安装向导 - 青园营地'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
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
            background: rgba(255, 255, 255, 0.05);
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
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 40px 120px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
        }

        .brand-section {
            flex: 1;
            background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
            padding: 40px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            color: white;
            min-width: 380px;
        }

        .brand-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            to { transform: rotate(360deg); }
        }

        .brand-content {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            font-size: 64px;
            margin-bottom: 16px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .brand-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 1.5px;
        }

        .brand-subtitle {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .brand-features {
            list-style: none;
            margin-top: 24px;
        }

        .brand-features li {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-features li:last-child {
            border-bottom: none;
        }

        .brand-features .icon {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            font-size: 13px;
            opacity: 0.7;
            line-height: 1.8;
        }

        .login-section {
            flex: 1;
            padding: 60px 40px 30px 40px;
            background: white;
            min-width: 380px;
        }

        .login-header {
            margin-bottom: 28px;
        }

        .login-welcome {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .login-hint {
            font-size: 13px;
            color: #8c8c8c;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #262626;
            font-weight: 600;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #1890ff;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .input-wrapper .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #bfbfbf;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .input-wrapper:focus-within .icon {
            color: #1890ff;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #595959;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #1890ff;
        }

        .forgot-password {
            color: #1890ff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: #40a9ff;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .error-msg {
            background: linear-gradient(135deg, #fff1f0, #fff);
            border: 1px solid #ffa39e;
            border-radius: 12px;
            padding: 14px 18px;
            color: #f5222d;
            font-size: 14px;
            margin-bottom: 24px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-msg.show {
            display: flex;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }

            .brand-section {
                min-width: auto;
                padding: 30px 24px;
            }

            .brand-features {
                display: none;
            }

            .login-section {
                min-width: auto;
                padding: 30px 24px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                border-radius: 16px;
            }

            .brand-section,
            .login-section {
                padding: 30px 20px;
            }

            .brand-title {
                font-size: 28px;
            }

            .login-welcome {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <ul class="bg-animation">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>

    <div class="login-container">
        <div class="brand-section">
            <div class="brand-content">
                <div class="brand-logo">🚀</div>
                <h1 class="brand-title">青园营地</h1>
                <p class="brand-subtitle">专业的客户管理系统<br>让管理更高效，让业务更简单</p>
                
                <ul class="brand-features">
                    <li>
                        <span class="icon">📊</span>
                        <span>数据大屏 - 实时业务数据可视化</span>
                    </li>
                    <li>
                        <span class="icon">👥</span>
                        <span>客户管理 - 全生命周期客户跟踪</span>
                    </li>
                    <li>
                        <span class="icon">💼</span>
                        <span>岗位管理 - 智能化职位发布系统</span>
                    </li>
                    <li>
                        <span class="icon">🎯</span>
                        <span>营销获客 - 多渠道营销自动化</span>
                    </li>
                    <li>
                        <span class="icon">💰</span>
                        <span>财务管理 - 精细化财务核算体系</span>
                    </li>
                </ul>
            </div>
            
            <div class="brand-footer">
                <p>© 2026 青园营地管理后台</p>
                <p>Professional Visa Industry CRM Solution</p>
            </div>
        </div>

        <div class="login-section">
            <div class="login-header">
                <h2 class="login-welcome">欢迎使用</h2>
                <p class="login-hint">请输入用户名和密码登录</p>
            </div>

            <div class="error-msg" id="errorMsg">
                <span>⚠️</span>
                <span id="errorText"></span>
            </div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <div class="input-wrapper">
                        <span class="icon">👤</span>
                        <input type="text" id="username" name="username" required placeholder="请输入用户名" autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">密码</label>
                    <div class="input-wrapper">
                        <span class="icon">🔒</span>
                        <input type="password" id="password" name="password" required placeholder="请输入密码" autocomplete="current-password">
                    </div>
                </div>

                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>记住我</span>
                    </label>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    登录
                </button>
            </form>
        </div>
    </div>

    <script>
    const errorMsg = document.getElementById('errorMsg');
    const errorText = document.getElementById('errorText');
    const loginBtn = document.getElementById('loginBtn');
    const loginForm = document.getElementById('loginForm');

    function showError(msg) {
        errorText.textContent = msg;
        errorMsg.classList.add('show');
        setTimeout(() => {
            errorMsg.classList.remove('show');
        }, 3000);
    }

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showError('请输入用户名和密码');
            return;
        }

        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span style="display: inline-flex; align-items: center; gap: 8px;"><span class="spinner"></span> 登录中...</span>';

        console.log('[登录] 开始登录，用户名:', username);

        // 使用 XMLHttpRequest 避免被全局错误处理器拦截
        // 计算 API 的绝对路径（相对于网站根目录）
        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const apiPath = basePath.substring(0, basePath.lastIndexOf('/')) + '/api/auth.php';
        const xhr = new XMLHttpRequest();
        xhr.open('POST', apiPath + '?action=login', true);
        
        xhr.onload = function() {
            console.log('[登录] 响应状态:', xhr.status);
            console.log('[登录] 原始响应:', xhr.responseText);
            
            try {
                const result = JSON.parse(xhr.responseText);
                console.log('[登录] 解析结果:', result);
                
                if (result.code === 200) {
                    showError('✅ 登录成功，正在跳转...');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 500);
                } else {
                    showError('❌ ' + result.message);
                    loginBtn.disabled = false;
                    loginBtn.textContent = '登录';
                }
            } catch (e) {
                console.error('[登录] JSON 解析失败:', e);
                showError('服务器返回格式错误：' + xhr.responseText.substring(0, 100));
                loginBtn.disabled = false;
                loginBtn.textContent = '登录';
            }
        };
        
        xhr.onerror = function() {
            console.error('[登录] 网络错误');
            showError('网络错误，请检查网络连接');
            loginBtn.disabled = false;
            loginBtn.textContent = '登录';
        };
        
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        xhr.send(formData);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
    </script>

    <style>
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
</body>
</html>
