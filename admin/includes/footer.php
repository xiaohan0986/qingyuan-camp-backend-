</main>
    
    <!-- 用户下拉菜单 -->
    <div id="userMenu" style="display: none; position: fixed; top: 60px; right: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.15); z-index: 1001; min-width: 150px; overflow: hidden;">
        <a href="profile.php" style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
            👤 个人中心
        </a>
        <a href="settings.php" style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
            ⚙️ 系统设置
        </a>
        <a href="api/auth.php?action=logout" style="display: block; padding: 12px 20px; color: #f5222d; text-decoration: none;">
            🚪 退出登录
        </a>
    </div>
    
    <script>
        // 侧边栏切换 + 状态检测
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        function updateSidebarState() {
            const isOpen = sidebar.classList.contains('open');
            
            if (menuToggle) {
                if (isOpen) {
                    menuToggle.innerHTML = '✕';
                    menuToggle.title = '隐藏菜单';
                    menuToggle.setAttribute('aria-label', '隐藏菜单');
                    localStorage.setItem('sidebarOpen', 'true');
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.title = '显示菜单';
                    menuToggle.setAttribute('aria-label', '显示菜单');
                    localStorage.setItem('sidebarOpen', 'false');
                }
            }
            
            document.body.classList.toggle('sidebar-collapsed', !isOpen);
        }
        
        menuToggle?.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            updateSidebarState();
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            const savedState = localStorage.getItem('sidebarOpen');
            if (savedState === 'false') {
                sidebar.classList.remove('open');
            } else {
                sidebar.classList.add('open');
            }
            updateSidebarState();
            console.log('页面加载完成，侧边栏状态已恢复');
        });
        
        // 用户菜单（如果 header.php 中没有定义）
        if (typeof userDropdown === 'undefined') {
            const userDropdown = document.getElementById('userDropdown');
            const userMenu = document.getElementById('userMenu');
            
            userDropdown?.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.style.display = userMenu.style.display === 'none' ? 'block' : 'none';
            });
            
            document.addEventListener('click', function() {
                userMenu.style.display = 'none';
            });
        }
    </script>
    
    <?php if (isset($pageJs)): ?>
    <script src="js/<?php echo $pageJs; ?>"></script>
    <?php endif; ?>
    
    <!-- 底部版权 + 备案号（已隐藏） -->
    <footer id="mainFooter" style="display: none;">
        <div style="margin-bottom: 8px;">© 2026 青园营地</div>
        <a href="https://beian.miit.gov.cn/" target="_blank" style="color: #999; text-decoration: none;">
            苏 ICP 备 2025207895 号 -1
        </a>
    </footer>
    
    <script>
        // 底部跟随侧边栏状态调整
        function updateFooterPosition() {
            // footer 已放在 main-content 内部，由 CSS margin-left 控制，无需 JS 调整
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateFooterPosition();
            
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                const observer = new MutationObserver(updateFooterPosition);
                observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
            }
        });
    </script>
</body>
</html>