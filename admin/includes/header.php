<?php
/**
 * 顶部导航栏
 */
// 安全获取管理员信息
if (isset($admin) && is_array($admin)) {
    // 已传入 admin 数据
} elseif (class_exists('Auth') && method_exists('Auth', 'user')) {
    try {
        $admin = Auth::user();
    } catch (Exception $e) {
        $admin = ['name' => '用户', 'role' => '用户'];
    }
} else {
    $admin = ['name' => '访客', 'role' => '访客'];
}

// 获取未读通知数量
$unreadNotifCount = 0;
if (!empty($admin['id'])) {
    try {
        $notifDb = Database::getInstance();
        $notifRow = $notifDb->fetchOne("SELECT COUNT(*) as cnt FROM admin_notifications WHERE admin_id = ? AND is_read = 0", [$admin['id']]);
        $unreadNotifCount = intval($notifRow['cnt'] ?? 0);
    } catch (Exception $e) {
        // 数据库查询失败，静默处理
        $unreadNotifCount = 0;
    }
}
// 获取当前页面路径
$currentPath = $_SERVER['PHP_SELF'];

// 引入菜单配置（与 sidebar.php 保持一致）
$menuModules = [
    'dashboard' => ['name' => '数据大屏', 'children' => null],
    'product' => [
        'name' => '商品管理',
        'children' => [
            ['name' => '商品列表', 'url' => '/admin/product/index.php'],
            ['name' => '商品分类', 'url' => '/admin/product/product_category.php'],
            ['name' => '商品标签', 'url' => '/admin/product/product_tag.php'],
        ]
    ],
    'order' => [
        'name' => '订单管理',
        'children' => [
            ['name' => '订单列表', 'url' => '/admin/order/index.php'],
            ['name' => '售后管理', 'url' => '/admin/order/order_after.php'],
            ['name' => '发货管理', 'url' => '/admin/order/order_delivery.php'],
        ]
    ],
    'member' => [
        'name' => '用户管理',
        'children' => [
            ['name' => '用户列表', 'url' => '/admin/member/index.php'],
            ['name' => '用户积分', 'url' => '/admin/member/member_points.php'],
            ['name' => '用户规则', 'url' => '/admin/member/member_rules.php'],
            ['name' => '用户充值', 'url' => '/admin/member/member_recharge.php'],
            ['name' => '充值记录', 'url' => '/admin/member/member_recharge_log.php'],
            ['name' => '消费明细', 'url' => '/admin/member/member_consumption.php'],
        ]
    ],
    'content' => [
        'name' => '内容管理',
        'children' => [
            ['name' => '文章列表', 'url' => '/admin/content/index.php'],
            ['name' => '文章分类', 'url' => '/admin/content/article_category.php'],
        ]
    ],
    'marketing' => [
        'name' => '营销管理',
        'children' => [
            ['name' => '优惠发放', 'url' => '/admin/marketing/index.php'],
            ['name' => '领券记录', 'url' => '/admin/marketing/marketing_coupon_log.php'],
            ['name' => '开屏推广', 'url' => '/admin/marketing/marketing_ad.php'],
            ['name' => '活动列表', 'url' => '/admin/marketing/marketing_activity.php'],
        ]
    ],
    'block' => [
        'name' => '板块管理',
        'children' => [
            ['name' => '回收板块', 'url' => '/admin/block/index.php'],
            ['name' => '校园墙', 'url' => '/admin/block/block_wall.php'],
            ['name' => '校务通', 'url' => '/admin/block/block_affairs.php'],
            ['name' => '失物认领', 'url' => '/admin/block/block_lost.php'],
        ]
    ],
    'shop' => [
        'name' => '店铺管理',
        'children' => [
            ['name' => '门店列表', 'url' => '/admin/shop/index.php'],
            ['name' => '店员管理', 'url' => '/admin/shop/shop_staff.php'],
            ['name' => '核销记录', 'url' => '/admin/shop/shop_verify.php'],
        ]
    ],
    'app' => [
        'name' => '应用中心',
        'children' => [
            ['name' => '数据统计', 'url' => '/admin/app/index.php'],
            ['name' => '入驻管理', 'url' => '/admin/app/app 入驻.php'],
            ['name' => '合伙用户', 'url' => '/admin/app/app_partner.php'],
            ['name' => '提现申请', 'url' => '/admin/app/app_withdraw.php'],
            ['name' => '退出申请', 'url' => '/admin/app/app_quit.php'],
        ]
    ],
    'system' => [
        'name' => '系统设置',
        'children' => [
            ['name' => '交易设置', 'url' => '/admin/system/index.php'],
            ['name' => '客服设置', 'url' => '/admin/system/system_service.php'],
            ['name' => '上传设置', 'url' => '/admin/system/system_upload.php'],
            ['name' => '短信通知', 'url' => '/admin/system/system_sms.php'],
            ['name' => '配送设置', 'url' => '/admin/system/system_delivery.php'],
            ['name' => '支付设置', 'url' => '/admin/system/system_payment.php'],
            ['name' => '其他配置', 'url' => '/admin/system/system_other.php'],
        ]
    ],
];

// 查找当前页面对应的一级菜单和二级菜单
$currentModule = '';
$currentModuleName = '首页';
$currentChildName = '';
$currentChildUrl = '';

foreach ($menuModules as $key => $info) {
    if ($info['children'] === null) {
        // 无二级菜单
        if (strpos($currentPath, "/admin/$key.php") !== false || strpos($currentPath, "\\admin\\$key.php") !== false) {
            $currentModule = $key;
            $currentModuleName = $info['name'];
            break;
        }
    } else {
        // 有二级菜单
        if (strpos($currentPath, "/admin/$key/") !== false || strpos($currentPath, "\\admin\\$key\\") !== false) {
            $currentModule = $key;
            $currentModuleName = $info['name'];
            // 查找当前二级菜单
            foreach ($info['children'] as $child) {
                if (strpos($currentPath, $child['url']) !== false) {
                    $currentChildName = $child['name'];
                    $currentChildUrl = $child['url'];
                    break;
                }
            }
            break;
        }
    }
}

// dashboard 特殊处理
if (strpos($currentPath, '/admin/dashboard.php') !== false || strpos($currentPath, '\\admin\\dashboard.php') !== false) {
    $currentModule = 'dashboard';
    $currentModuleName = '数据大屏';
}
?>
<header class="top-header">
    <div class="header-left">
        <button id="menuToggleBtn" class="menu-toggle" title="切换菜单">☰</button>
        <div class="breadcrumb">
            <a href="/admin/dashboard.php" class="breadcrumb-link">首页</a>
            <span class="separator">/</span>
            <?php if ($currentModule): ?>
                <?php 
                // 一级菜单链接
                $moduleUrl = '';
                if ($currentModule === 'dashboard') {
                    $moduleUrl = '/admin/dashboard.php';
                } else {
                    $menuModules[$currentModule]['children'] === null 
                        ? $moduleUrl = '/admin/' . $currentModule . '.php'
                        : $moduleUrl = $menuModules[$currentModule]['children'][0]['url'];
                }
                ?>
                <a href="<?= $moduleUrl ?>" class="breadcrumb-link"><?= $currentModuleName ?></a>
                <?php if ($currentChildName): ?>
                    <span class="separator">/</span>
                    <span class="breadcrumb-current"><?= $currentChildName ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="header-right">
        <!-- 搜索框 -->
        <div class="header-search">
            <img src="/images/search_icon.png" alt="搜索" class="search-icon-img" />
            <input type="text" class="search-input" placeholder="搜索文件、日程、通知...." />
        </div>
        
        <div class="header-action<?= $unreadNotifCount > 0 ? ' notification-action' : '' ?>">
            <span class="icon">🔔</span>
            <?php if ($unreadNotifCount > 0): ?>
            <span class="badge"><?= $unreadNotifCount ?></span>
            <?php endif; ?>
        </div>
        
        <div class="header-action help-action" style="display: none;">
            <span class="icon">❓</span>
        </div>
        
        <div class="user-menu">
            <div class="user-avatar">👤</div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($admin['name']) ?></div>
                <div class="user-role">管理员</div>
            </div>
            <div class="dropdown">
                <a href="profile.php">个人中心</a>
                <a href="settings.php">系统设置</a>
                <div class="divider"></div>
                <a href="logout.php" class="danger">退出登录</a>
            </div>
        </div>
    </div>
</header>

<style>
.top-header {
    position: fixed;
    top: 12px;
    left: 256px;  /* 12px 间距 + 232px 侧边栏 + 12px 间距 */
    right: 12px;
    background: transparent;
    padding: 12px 32px;
    height: 56px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 12px;
    box-shadow: 0;
    z-index: 1000;
    transition: background 0.3s ease, box-shadow 0.3s ease, left 0.3s ease;
}

.top-header.scrolled {
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* 侧边栏收起时的 header 位置 */
body.sidebar-collapsed .top-header {
    left: 84px;  /* 12px 间距 + 60px 侧边栏 + 12px 间距 */
}

/* 内容区域 padding-top，防止被 header 遮挡 */
.main-content {
    padding-top: 80px;
}

.content-wrapper {
    /* 移除 padding-top，由 main-content 处理 */
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.menu-toggle {
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    border-radius: 8px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.menu-toggle:hover {
    background: #e6e6e6;
}

.menu-toggle:hover {
    background: #1890ff;
    color: white;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #000000;
    font-weight: 600;
}

.breadcrumb .separator {
    color: #000000;
    opacity: 0.5;
}

.breadcrumb-link {
    color: #000000;
    text-decoration: none;
    transition: color 0.3s;
    font-weight: 600;
}

.breadcrumb-link:hover {
    color: #1890ff;
}

.breadcrumb-current {
    color: #000000;
    font-weight: 700;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* 搜索框样式 */
.header-search {
    position: relative;
    display: flex;
    align-items: center;
    background: #f5f5f5;
    border-radius: 8px;
    height: 36px;
    padding: 0 12px;
    transition: all 0.3s;
}

.header-search:focus-within {
    background: white;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.search-icon-img {
    width: 18px;
    height: 18px;
    margin-right: 8px;
    opacity: 0.6;
    pointer-events: none;
}

.search-input {
    width: 200px;
    height: 36px;
    padding: 0;
    border: none;
    background: transparent;
    font-size: 13px;
    outline: none;
    color: #262626;
}

.search-input::placeholder {
    color: #8c8c8c;
}

.header-action {
    position: relative;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.header-action:hover {
    background: #e6e6e6;
}

.header-action .icon {
    font-size: 20px;
}

/* 铃铛摇动动画 */
@keyframes bell-shake {
    0%, 100% { transform: rotate(0deg); }
    10% { transform: rotate(15deg); }
    20% { transform: rotate(-15deg); }
    30% { transform: rotate(15deg); }
    40% { transform: rotate(-15deg); }
    50% { transform: rotate(0deg); }
}

.notification-action .icon {
    animation: bell-shake 1.5s ease-in-out infinite;
}

.header-action .badge {
    position: absolute;
    top: 4px;
    right: 4px;
    min-width: 18px;
    height: 18px;
    background: #ff4d4f;
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

.user-menu {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.user-menu:hover {
    background: #f5f5f5;
}

.user-menu:hover .dropdown {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.user-avatar {
    width: 36px;
    height: 36px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-name {
    font-size: 13px;
    font-weight: 700;
    color: #000000;
}

.user-role {
    font-size: 11px;
    font-weight: 600;
    color: #000000;
    opacity: 0.7;
}

.dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 180px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    padding: 8px;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s;
    z-index: 1000;
}

.dropdown a {
    display: block;
    padding: 10px 16px;
    color: #262626;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.dropdown a:hover {
    background: #f5f5f5;
}

.dropdown .divider {
    height: 1px;
    background: #f0f0f0;
    margin: 8px 0;
}

.dropdown .danger {
    color: #ff4d4f;
}

.dropdown .danger:hover {
    background: #fff2f0;
}

@media (max-width: 768px) {
    .user-info {
        display: none;
    }
}
</style>

<script>
(function() {
    console.log('🔶 header.php 脚本开始执行');
    
    // 侧边栏切换功能
    window.toggleSidebar = function() {
        console.log('🔵 toggleSidebar 被调用');
        const sidebar = document.getElementById('sidebar');
        const body = document.body;
        
        if (!sidebar) {
            console.error('❌ sidebar 不存在');
            return;
        }
        
        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
        
        window.dispatchEvent(new Event('resize'));
        console.log('✅ 侧边栏已' + (isCollapsed ? '收起' : '展开'));
    };
    
    // 立即绑定按钮事件
    function bind() {
        const btn = document.getElementById('menuToggleBtn');
        console.log('🔍 查找按钮:', btn);
        
        if (!btn) {
            console.log('⏳ 按钮不存在，等待 50ms 后重试');
            setTimeout(bind, 50);
            return;
        }
        
        // 克隆节点移除旧事件监听器
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', function(e) {
            console.log('🔴 按钮点击事件触发');
            e.preventDefault();
            e.stopPropagation();
            window.toggleSidebar();
        });
        
        console.log('✅ 侧边栏按钮事件已绑定');
    }
    
    bind();
})();
</script>

<!-- 顶部状态栏滚动效果 -->
<script>
(function() {
    const header = document.querySelector('.top-header');
    if (!header) return;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // 在页面顶部时（scrollTop <= 0），背景透明
        if (scrollTop <= 0) {
            header.classList.remove('scrolled');
        } 
        // 向下滚动时，背景变为白色
        else {
            header.classList.add('scrolled');
        }
    }, { passive: true });
})();
</script>
