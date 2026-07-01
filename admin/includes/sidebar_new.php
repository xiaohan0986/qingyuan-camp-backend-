<?php
/**
 * 侧边栏导航 - Tailwind CSS 风格
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$menuModules = [
    'dashboard' => ['name' => '数据大屏', 'icon' => 'fa-dashboard', 'category' => 'core'],
    'product' => ['name' => '商品管理', 'icon' => 'fa-shopping-bag', 'category' => 'business'],
    'order' => ['name' => '订单管理', 'icon' => 'fa-shopping-cart', 'category' => 'business'],
    'member' => ['name' => '会员管理', 'icon' => 'fa-users', 'category' => 'business'],
    'content' => ['name' => '内容管理', 'icon' => 'fa-file-text', 'category' => 'business'],
    'marketing' => ['name' => '营销管理', 'icon' => 'fa-bullhorn', 'category' => 'business'],
    'block' => ['name' => '板块管理', 'icon' => 'fa-th-large', 'category' => 'business'],
    'shop' => ['name' => '店铺管理', 'icon' => 'fa-store', 'category' => 'business'],
    'app' => ['name' => '应用中心', 'icon' => 'fa-puzzle-piece', 'category' => 'extend'],
    'system' => ['name' => '系统设置', 'icon' => 'fa-cog', 'category' => 'system'],
];

$menuCategories = [
    'core' => '📈 核心功能',
    'business' => '💼 业务管理',
    'extend' => '🔌 扩展功能',
    'system' => '🔐 系统管理'
];
?>
<aside id="sidebar" class="sidebar w-64 h-full bg-white shadow-lg shadow-black/5 fixed left-0 top-0 bottom-0 z-30 overflow-y-auto transition-all duration-300">
    <div class="p-4 border-b border-gray-100">
        <div class="sidebar-header flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <i class="fa fa-cubes text-xl"></i>
            </div>
            <h2 class="sidebar-text text-xl font-bold text-primary transition-all duration-300">青园营地</h2>
        </div>
    </div>
    
    <nav class="p-2 space-y-1 mt-2">
        <?php
        $lastCategory = '';
        foreach ($menuModules as $module => $info):
            if ($lastCategory !== $info['category']):
                if ($lastCategory !== ''):
                    echo '</div>';
                endif;
                $lastCategory = $info['category'];
                echo '<div class="sidebar-menu-title text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2 mt-4">' . $menuCategories[$lastCategory] . '</div>';
            endif;
            
            $isActive = $currentPage === $module;
            $activeClass = $isActive ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-gray-50';
            ?>
            
            <a href="<?= $module ?>.php" class="sidebar-item flex items-center gap-2 px-4 py-3 rounded-lg cursor-pointer transition-all duration-200 <?= $activeClass ?>">
                <i class="fa <?= $info['icon'] ?> w-5 text-center flex-shrink-0"></i>
                <span class="sidebar-text transition-all duration-300"><?= $info['name'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<style>
/* 侧边栏收起状态 */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .sidebar-text,
.sidebar.collapsed .sidebar-menu-title {
    opacity: 0;
    visibility: hidden;
    white-space: nowrap;
    width: 0;
    padding: 0;
    margin: 0;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
}

.sidebar.collapsed .sidebar-item {
    justify-content: center;
    padding: 12px 0;
}

.sidebar.collapsed .sidebar-item i {
    margin-right: 0;
}

/* 移动端响应式 */
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
// 侧边栏切换功能 - 全局函数，确保在 header 加载前就定义
window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    
    if (!sidebar) {
        console.error('侧边栏元素未找到');
        return;
    }
    
    // 切换收起/展开状态
    sidebar.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');
    
    // 保存到 localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
    
    // 触发窗口大小调整事件
    window.dispatchEvent(new Event('resize'));
    
    console.log('侧边栏已切换:', isCollapsed ? '收起' : '展开');
};

// 页面加载时恢复状态
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
    
    if (isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
    
    console.log('侧边栏初始化完成，当前状态:', isCollapsed ? '收起' : '展开');
});
</script>
