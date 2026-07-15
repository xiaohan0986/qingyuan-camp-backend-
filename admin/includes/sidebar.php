<?php
/**
 * 侧边栏导航 - 支持展开/收起 + 下拉菜单
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = $_SERVER['PHP_SELF'];

// 一级菜单配置
$menuModules = [
    'dashboard' => ['name' => '数据大屏', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>', 'category' => 'core', 'children' => null],
    'product' => [
        'name' => '商品管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '商品列表', 'url' => '/admin/product/index.php'],
            ['name' => '商品分类', 'url' => '/admin/product/product_category.php'],
            ['name' => '商品标签', 'url' => '/admin/product/product_tag.php'],
        ]
    ],
    'order' => [
        'name' => '订单管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '订单列表', 'url' => '/admin/order/index.php'],
            ['name' => '售后管理', 'url' => '/admin/order/order_after.php'],
            ['name' => '发货管理', 'url' => '/admin/order/order_delivery.php'],
        ]
    ],
    'member' => [
        'name' => '用户管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'category' => 'business',
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
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '文章列表', 'url' => '/admin/content/index.php'],
            ['name' => '文章分类', 'url' => '/admin/content/article_category.php'],
        ]
    ],
    'marketing' => [
        'name' => '营销管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '优惠券管理', 'url' => '/admin/marketing/coupon_manage.php'],
            ['name' => '秒杀管理', 'url' => '/admin/marketing/seckill_manage.php'],
            ['name' => '优惠发放', 'url' => '/admin/marketing/index.php'],
            ['name' => '领券记录', 'url' => '/admin/marketing/marketing_coupon_log.php'],
            ['name' => '开屏推广', 'url' => '/admin/marketing/marketing_ad.php'],
            ['name' => '活动列表', 'url' => '/admin/marketing/marketing_activity.php'],
        ]
    ],
    'block' => [
        'name' => '板块管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '回收板块', 'url' => '/admin/block/index.php'],
            ['name' => '校园墙', 'url' => '/admin/block/block_wall.php'],
            ['name' => '校务通', 'url' => '/admin/block/block_affairs.php'],
            ['name' => '失物认领', 'url' => '/admin/block/block_lost.php'],
        ]
    ],
    'shop' => [
        'name' => '店铺管理',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'category' => 'business',
        'children' => [
            ['name' => '门店列表', 'url' => '/admin/shop/index.php'],
            ['name' => '店员管理', 'url' => '/admin/shop/shop_staff.php'],
            ['name' => '核销记录', 'url' => '/admin/shop/shop_verify.php'],
        ]
    ],
    'app' => [
        'name' => '应用中心',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>',
        'category' => 'extend',
        'children' => [
            ['name' => '数据统计', 'url' => '/admin/app/index.php'],
            ['name' => '合伙用户', 'url' => '/admin/app/app_partner.php'],
            ['name' => '提现申请', 'url' => '/admin/app/app_withdraw.php'],
            ['name' => '退出申请', 'url' => '/admin/app/app_quit.php'],
        ]
    ],
    'system' => [
        'name' => '系统设置',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'category' => 'system',
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

// 获取当前页面对应的一级菜单 key（仅用于展开二级菜单，不高亮一级菜单）
$currentParentKey = '';
foreach ($menuModules as $key => $item) {
    if ($item['children']) {
        if (strpos($currentPath, "/admin/$key/") !== false || strpos($currentPath, "\\admin\\$key\\") !== false) {
            $currentParentKey = $key;
            break;
        }
    }
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <picture>
                <source srcset="/admin/images/logo.webp" type="image/webp">
                <img src="/admin/images/logo.png" alt="青园营地" style="width:40px;height:40px;border-radius:8px;" decoding="async">
            </picture>
        </div>
        <div class="sidebar-title-group">
            <h2>青园营地</h2>
            <p class="sidebar-subtitle">助力校园线上发展</p>
        </div>
    </div>
    
    <nav class="sidebar-menu">
        <?php
        foreach ($menuModules as $module => $info):
            
            $hasChildren = !empty($info['children']);
            $isActive = $currentParentKey === $module;
            ?>
            
            <?php if ($hasChildren): ?>
                <div class="sidebar-menu-item has-children" 
                     data-module="<?= $module ?>"
                     title="<?= $info['name'] ?>"
                     onclick="handleMenuClick('<?= $module ?>')">
                    <span class="icon"><?= $info['icon'] ?></span>
                    <span class="text"><?= $info['name'] ?></span>
                    <span class="arrow"></span>
                </div>
                
                <!-- 正常二级菜单 -->
                <div class="sidebar-submenu" id="submenu-<?= $module ?>" style="display: <?= $isActive ? 'block' : 'none' ?>;">
                    <?php foreach ($info['children'] as $child): ?>
                        <a href="<?= $child['url'] ?>" onclick="event.stopPropagation()" class="sidebar-submenu-item <?php 
                            $currentUrl = $_SERVER['PHP_SELF'];
                            $childUrl = $child['url'];
                            echo $currentUrl === $childUrl ? 'active' : ''; 
                        ?>" title="<?= $child['name'] ?>">
                            <span class="text"><?= $child['name'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <a href="/admin/<?= $module ?>.php" class="sidebar-menu-item <?php echo $currentPage === $module ? 'active' : ''; ?>" 
                   title="<?= $info['name'] ?>">
                    <span class="icon"><?= $info['icon'] ?></span>
                    <span class="text"><?= $info['name'] ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    
    <!-- 下拉菜单（侧边栏收起时显示） -->
    <div id="dropdownMenu" class="dropdown-menu" style="display: none;"></div>
<script src="/admin/assets/js/form-enhancer.min.js?v=7"></script>
<?php
// 为所有页面注入 CSRF Token（需在页面包含 header 前调用）
require_once __DIR__ . '/../../includes/CsrfHelper.php';
$csrfToken = CsrfHelper::generate();
?>
</aside>
<!-- CSRF 防护 AJAX 支持 -->
<script>
// CSRF Token 注入到全局 AJAX 请求
(function() {
    var token = <?= json_encode($csrfToken) ?>;
    
    // XMLHttpRequest 自动注入 CSRF Token
    var origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this._method = (arguments[1] || '').toLowerCase();
        origOpen.apply(this, arguments);
    };
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(data) {
        if (this._method && this._method.indexOf('post') >= 0) {
            this.setRequestHeader('X-CSRF-Token', token);
        }
        origSend.apply(this, arguments);
    };
    
    // fetch API 自动注入 CSRF Token
    var origFetch = window.fetch;
    window.fetch = function(url, options) {
        options = options || {};
        if (options.method && options.method.toUpperCase() === 'POST') {
            options.headers = options.headers || {};
            options.headers['X-CSRF-Token'] = token;
        }
        return origFetch.call(this, url, options);
    };
})();
</script>

<style>
.sidebar {
    position: fixed;
    left: 12px;
    top: 12px;
    bottom: 12px;
    width: 232px !important;
    background: #ffffff;
    padding: 24px 12px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    transition: width 0.3s ease, padding 0.3s ease !important;
    border-radius: 16px;
    box-shadow: 4px 0 20px rgba(0,0,0,0.08), 0 0 16px rgba(0,0,0,0.04);
}

.sidebar.collapsed {
    width: 60px !important;
    padding: 12px 0;
}

/* 收起状态 - 文字和箭头从右向左渐隐 */
.sidebar.collapsed .sidebar-menu-item .text {
    opacity: 0;
    max-width: 0 !important;
    flex: 0 0 0;
    overflow: hidden;
    transition: opacity 0.3s ease, max-width 0.3s ease;
}

.sidebar.collapsed .arrow {
    opacity: 0;
    width: 0;
    flex: 0 0 0;
    overflow: hidden;
    transition: opacity 0.3s ease, width 0.3s ease;
}

/* 图标始终可见，无动画 */
.sidebar-menu-item .icon,
.sidebar.collapsed .sidebar-menu-item .icon {
    opacity: 1 !important;
    transition: none !important;
    transform: none !important;
}

.sidebar.collapsed .sidebar-submenu {
    display: none !important;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
    padding: 0 0 24px;
    gap: 0;
}

.sidebar.collapsed .sidebar-title-group {
    display: none;
}

.sidebar-title-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
}

.sidebar-subtitle {
    margin: 0;
    font-size: 11px;
    color: #8c8c8c;
    white-space: nowrap;
}

.sidebar-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 0 12px 24px;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 24px;
    overflow: hidden;
    min-height: 60px;
    flex-shrink: 0;
}

.sidebar-header h2 {
    color: #262626;
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
}

/* 展开时标题渐显动画 */
.sidebar:not(.collapsed) .sidebar-header h2 {
    opacity: 1;
    max-width: 200px;
    transition: opacity 0.3s ease, max-width 0.3s ease;
}

/* 一级菜单项 */
.sidebar-menu-item {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    padding: 12px;
    height: 44px;
    box-sizing: border-box;
    color: #595959;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 4px;
    cursor: pointer;
    overflow: hidden;
}

/* 展开时图标固定在左侧 */
.sidebar-menu-item .icon {
    width: 20px;
    height: 20px;
    color: #595959;
    flex-shrink: 0;
    flex-grow: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 0;
}

/* 收起时菜单项 - 图标居中 */
.sidebar.collapsed .sidebar-menu-item {
    gap: 0 !important;
    padding: 12px 0 !important;
    box-sizing: border-box;
    justify-content: center !important;
    align-items: center !important;
}

/* 收起时图标居中 */
.sidebar.collapsed .sidebar-menu-item .icon {
    margin: 0 !important;
}

.sidebar-menu-item:hover {
    background: #f5f5f5;
    color: #262626;
}

/* 一级菜单 active 状态 - 最高优先级 */
.sidebar-menu-item.active,
.sidebar-menu-item.active.has-children,
.sidebar-menu-item.has-children.active {
    background: linear-gradient(90deg, #1890ff, #91d5ff) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3) !important;
}

/* 收起时高亮项 - 图标居中，蓝色背景 */
.sidebar.collapsed .sidebar-menu-item.active {
    padding: 12px 0 !important;
    margin: 0 8px !important;
    width: auto !important;
    justify-content: center !important;
    background: linear-gradient(90deg, #1890ff, #91d5ff) !important;
    color: white !important;
    border-radius: 8px !important;
}

.sidebar-menu-item .icon svg {
    width: 100%;
    height: 100%;
}

.sidebar-menu-item:hover .icon {
    color: #1890ff;
}

.sidebar-menu-item.active .icon {
    color: white;
}

.sidebar-menu-item .text {
    font-size: 14px;
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    max-width: 200px;
    transition: max-width 0.3s ease, opacity 0.3s ease;
}

/* 展开时菜单文字渐显动画 */
.sidebar:not(.collapsed) .sidebar-menu-item .text {
    opacity: 1;
    max-width: 200px;
    transition: opacity 0.3s ease, max-width 0.3s ease;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* 文字动画速度统一 */
.sidebar:not(.collapsed) .sidebar-header h2,
.sidebar:not(.collapsed) .sidebar-menu-item .text {
    animation-duration: 0.3s;
}

.sidebar-menu-item .arrow {
    width: 12px;
   height: 12px;
   
   filter: brightness(0) saturate(100%) invert(37%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(95%) contrast(93%);
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='9 18 15 12 9 6'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center/contain;
    transition: transform 0.3s ease, opacity 0.3s ease, width 0.3s ease;
    flex-shrink: 0;
    opacity: 1;
}

/* 展开时箭头旋转 */
.sidebar-menu-item.active .arrow {
    transform: rotate(-90deg);
    filter: brightness(0) invert(1);
}

/* 二级菜单容器 */
.sidebar-submenu {
    padding-left: 12px;
    overflow: hidden;
    transition: background 0.3s, color 0.3s;
}

/* 二级菜单项 */
.sidebar-submenu-item {
    display: block;
    padding: 10px 12px 10px 36px;
    height: 40px;
    box-sizing: border-box;
    color: #8c8c8c;
    text-decoration: none;
    font-size: 13px;
    border-radius: 8px;
    margin-bottom: 2px;
    transition: background 0.3s, color 0.3s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-submenu-item:hover {
    background: #f5f5f5;
    color: #262626;
}

.sidebar-submenu-item.active {
    background: linear-gradient(90deg, #1890ff, #91d5ff);
    color: white;
    font-weight: 600;
}

/* 下拉菜单（收起状态） */
.dropdown-menu {
    position: fixed;
    left: 76px;
    top: 12px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    padding: 6px;
    min-width: 120px;
    max-width: 140px;
    max-height: calc(100vh - 24px);
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1001;
    box-sizing: border-box;
}

.dropdown-menu::-webkit-scrollbar {
    width: 4px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #d9d9d9;
    border-radius: 2px;
}

.dropdown-menu::-webkit-scrollbar-thumb:hover {
    background: #bfbfbf;
}

.dropdown-menu-item {
    display: block;
    padding: 8px 12px;
    height: 36px;
    box-sizing: border-box;
    color: #595959;
    text-decoration: none;
    font-size: 13px;
    border-radius: 6px;
    margin-bottom: 2px;
    transition: all 0.2s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dropdown-menu-item:hover {
    background: #f5f5f5;
    color: #262626;
}

.dropdown-menu-item.active {
    background: linear-gradient(90deg, #1890ff, #91d5ff);
    color: white;
}

/* 隐藏滚动条 */
.sidebar::-webkit-scrollbar {
    width: 0;
    display: none;
}

.sidebar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
}
</style>

<script>
// 初始化侧边栏状态
(function() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        setTimeout(arguments.callee, 50);
        return;
    }
    
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
})();

// 菜单点击处理
function handleMenuClick(module) {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = sidebar.classList.contains('collapsed');
    
    if (isCollapsed) {
        // 收起状态：显示下拉菜单
        showDropdown(module);
    } else {
        // 展开状态：切换二级菜单
        toggleSubMenu(module);
    }
}

// 切换二级菜单
function toggleSubMenu(module) {
    const submenu = document.getElementById('submenu-' + module);
    const menuItem = document.querySelector('.sidebar-menu-item[data-module="' + module + '"]');
    
    if (!submenu || !menuItem) return;
    
    // 关闭其他菜单
    document.querySelectorAll('.sidebar-submenu').forEach(function(el) {
        if (el.id !== 'submenu-' + module) {
            el.style.display = 'none';
        }
    });
    document.querySelectorAll('.sidebar-menu-item.has-children').forEach(function(el) {
        if (el.dataset.module !== module) {
            el.classList.remove('active');
        }
    });
    
    // 切换当前菜单
    const isVisible = submenu.style.display === 'block';
    submenu.style.display = isVisible ? 'none' : 'block';
    menuItem.classList.toggle('active', !isVisible);
}

// 显示下拉菜单
function showDropdown(module) {
    const dropdown = document.getElementById('dropdownMenu');
    const menuItem = document.querySelector('.sidebar-menu-item[data-module="' + module + '"]');
    
    if (!dropdown || !menuItem) return;
    
    // 先构建下拉菜单内容
    const menuModules = {
        'product': [
            {name: '商品列表', url: '/admin/product/index.php'},
            {name: '商品分类', url: '/admin/product/product_category.php'},
            {name: '商品标签', url: '/admin/product/product_tag.php'}
        ],
        'order': [
            {name: '订单列表', url: '/admin/order/index.php'},
            {name: '售后管理', url: '/admin/order/order_after.php'},
            {name: '发货管理', url: '/admin/order/order_delivery.php'}
        ],
        'member': [
            {name: '用户列表', url: '/admin/member/index.php'},
            {name: '用户积分', url: '/admin/member/member_points.php'},
            {name: '用户规则', url: '/admin/member/member_rules.php'},
            {name: '用户充值', url: '/admin/member/member_recharge.php'},
            {name: '充值记录', url: '/admin/member/member_recharge_log.php'},
            {name: '消费明细', url: '/admin/member/member_consumption.php'}
        ],
        'content': [
            {name: '文章列表', url: '/admin/content/index.php'},
            {name: '文章分类', url: '/admin/content/article_category.php'}
        ],
        'marketing': [
            {name: '优惠发放', url: '/admin/marketing/index.php'},
            {name: '领券记录', url: '/admin/marketing/marketing_coupon_log.php'},
            {name: '开屏推广', url: '/admin/marketing/marketing_ad.php'},
            {name: '活动列表', url: '/admin/marketing/marketing_activity.php'}
        ],
        'block': [
            {name: '回收板块', url: '/admin/block/index.php'},
            {name: '校园墙', url: '/admin/block/block_wall.php'},
            {name: '校务通', url: '/admin/block/block_affairs.php'},
            {name: '失物认领', url: '/admin/block/block_lost.php'}
        ],
        'shop': [
            {name: '门店列表', url: '/admin/shop/index.php'},
            {name: '店员管理', url: '/admin/shop/shop_staff.php'},
            {name: '核销记录', url: '/admin/shop/shop_verify.php'}
        ],
        'app': [
            {name: '数据统计', url: '/admin/app/index.php'},
            {name: '合伙用户', url: '/admin/app/app_partner.php'},
            {name: '提现申请', url: '/admin/app/app_withdraw.php'},
            {name: '退出申请', url: '/admin/app/app_quit.php'}
        ],
        'system': [
            {name: '交易设置', url: '/admin/system/index.php'},
            {name: '客服设置', url: '/admin/system/system_service.php'},
            {name: '上传设置', url: '/admin/system/system_upload.php'},
            {name: '短信通知', url: '/admin/system/system_sms.php'},
            {name: '配送设置', url: '/admin/system/system_delivery.php'},
            {name: '支付设置', url: '/admin/system/system_payment.php'},
            {name: '其他配置', url: '/admin/system/system_other.php'}
        ]
    };
    
    const items = menuModules[module] || [];
    const currentPath = window.location.pathname;
    
    let html = '';
    items.forEach(function(item) {
        const isActive = currentPath === item.url;
        html += '<a href="' + item.url + '" class="dropdown-menu-item' + (isActive ? ' active' : '') + '">' + item.name + '</a>';
    });
    
    dropdown.innerHTML = html;
    
    // 内容构建完成后，计算并设置位置
    dropdown.style.display = 'block';
    const dropdownHeight = dropdown.offsetHeight;
    
    const rect = menuItem.getBoundingClientRect();
    const screenHeight = window.innerHeight;
    const dropdownBottom = rect.top + dropdownHeight;
    
    // 计算最佳位置
    let top;
    if (dropdownBottom > screenHeight - 12) {
        // 会超出底部，向上对齐
        top = screenHeight - dropdownHeight - 12;
        // 确保不会超出顶部
        if (top < 12) {
            top = 12;
        }
    } else {
        // 不会超出，正常显示
        top = rect.top;
    }
    
    dropdown.style.top = top + 'px';
    
    // 点击其他地方关闭
    setTimeout(function() {
        document.addEventListener('click', closeDropdown);
    }, 100);
}

function closeDropdown(e) {
    const dropdown = document.getElementById('dropdownMenu');
    const sidebar = document.getElementById('sidebar');
    
    if (dropdown && !sidebar.contains(e.target)) {
        dropdown.style.display = 'none';
        document.removeEventListener('click', closeDropdown);
    }
}
</script>
