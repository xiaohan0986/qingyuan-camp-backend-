<?php
// 重定向到后台登录页面
header('Location: admin/index.php');
exit;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>青园营地管理后台</title>
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

        /* 动态背景 */
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

        /* 主容器 */
        .main-container {
            position: relative;
            z-index: 1;
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 40px 120px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
        }

        /* 左侧品牌区 */
        .brand-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            color: white;
            min-width: 450px;
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
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: 2px;
        }

        .brand-subtitle {
            font-size: 16px;
            opacity: 0.95;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .brand-features {
            list-style: none;
            margin-top: 32px;
        }

        .brand-features li {
            padding: 14px 0;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-features li:last-child {
            border-bottom: none;
        }

        .brand-features .icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .brand-footer {
            position: relative;
            z-index: 1;
            font-size: 14px;
            opacity: 0.8;
            line-height: 1.8;
        }

        /* 右侧登录选择区 */
        .login-choice-section {
            flex: 1;
            padding: 60px 50px;
            background: white;
            min-width: 450px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .welcome-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: wave 2s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-15deg); }
        }

        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 14px;
            color: #8c8c8c;
        }

        /* 登录选项卡片 */
        .login-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .login-option-card {
            display: flex;
            align-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border: 2px solid #e9ecef;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .login-option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-option-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }

        .login-option-card:hover::before {
            opacity: 1;
        }

        .option-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-right: 20px;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .option-content {
            flex: 1;
        }

        .option-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .option-description {
            font-size: 13px;
            color: #8c8c8c;
            line-height: 1.5;
        }

        .option-arrow {
            font-size: 24px;
            color: #667eea;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }

        .login-option-card:hover .option-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        /* 底部提示 */
        .footer-note {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }

        .footer-note p {
            font-size: 13px;
            color: #8c8c8c;
            line-height: 1.6;
        }

        .footer-note strong {
            color: #667eea;
        }

        /* 响应式设计 */
        @media (max-width: 1000px) {
            .main-container {
                flex-direction: column;
                max-width: 600px;
            }

            .brand-section {
                min-width: auto;
                padding: 40px 30px;
            }

            .brand-features {
                display: none;
            }

            .login-choice-section {
                min-width: auto;
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                border-radius: 16px;
            }

            .brand-section,
            .login-choice-section {
                padding: 30px 20px;
            }

            .brand-title {
                font-size: 28px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .login-option-card {
                padding: 20px;
            }

            .option-icon {
                width: 56px;
                height: 56px;
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- 动态背景 -->
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

    <!-- 主容器 -->
    <div class="main-container">
        <!-- 左侧品牌区 -->
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

        <!-- 右侧登录选择区 -->
        <div class="login-choice-section">
            <div class="welcome-header">
                <div class="welcome-icon">👋</div>
                <h2 class="welcome-title">欢迎使用青园营地</h2>
                <p class="welcome-subtitle">请输入账号和密码登录系统</p>
            </div>

            <div class="login-options">
                <!-- 立即登录选项 -->
                <a href="admin/login.php" class="login-option-card">
                    <div class="option-icon">👤</div>
                    <div class="option-content">
                        <div class="option-title">立即登录</div>
                        <div class="option-description">使用个人账号和密码登录<br>适合系统管理员和工作人员</div>
                    </div>
                    <div class="option-arrow">→</div>
                </a>


    <script>
    // 添加点击动画效果
    document.querySelectorAll('.login-option-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // 可以在这里添加点击效果
            console.log('Navigating to:', this.href);
        });
    });

    // 键盘导航支持
    document.addEventListener('keydown', function(e) {
        const cards = document.querySelectorAll('.login-option-card');
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
            e.preventDefault();
            const current = document.activeElement;
            const currentIndex = Array.from(cards).indexOf(current);
            const nextIndex = (currentIndex + 1) % cards.length;
            cards[nextIndex].focus();
        } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
            e.preventDefault();
            const current = document.activeElement;
            const currentIndex = Array.from(cards).indexOf(current);
            const prevIndex = (currentIndex - 1 + cards.length) % cards.length;
            cards[prevIndex].focus();
        } else if (e.key === 'Enter' && document.activeElement.classList.contains('login-option-card')) {
            document.activeElement.click();
        }
    });
    </script>
<?php include __DIR__ . '/includes/error_handler_include.php'; ?>
</body>
</html>
