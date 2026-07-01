<?php
/**
 * 顶部导航栏 - Tailwind CSS 风格
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

$pageTitle = $pageTitle ?? '管理后台';
$adminName = $admin['name'] ?? '管理员';
?>
<header class="h-16 bg-white shadow-lg shadow-black/5 flex items-center justify-between px-4 md:px-6 z-20 sticky top-0">
    <div class="flex items-center gap-4">
        <button onclick="toggleSidebar()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-all" title="切换侧边栏">
            <i class="fa fa-bars text-gray-600"></i>
        </button>
        
        <div class="relative w-64 hidden sm:block">
            <input type="text" placeholder="搜索功能..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all">
            <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        
        <div class="breadcrumb hidden md:flex items-center gap-2 text-sm text-gray-500">
            <span>首页</span>
            <span class="text-gray-300">/</span>
            <span class="text-gray-800 font-medium"><?= $pageTitle ?></span>
        </div>
    </div>
    
    <div class="flex items-center gap-3">
        <!-- 通知 -->
        <button class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 relative transition-all">
            <i class="fa fa-bell-o text-gray-600"></i>
            <span class="absolute top-2 right-2 w-2 h-2 bg-danger rounded-full"></span>
        </button>
        
        <!-- 帮助 -->
        <button class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition-all hidden sm:flex">
            <i class="fa fa-question-circle text-gray-600"></i>
        </button>
        
        <!-- 管理员菜单 -->
        <div class="relative group">
            <div class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg hover:bg-gray-50 transition-all">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold flex-shrink-0">
                    <?= mb_substr($adminName, 0, 1) ?>
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($adminName) ?></div>
                    <div class="text-xs text-gray-500"><?= $admin['role'] ?? '管理员' ?></div>
                </div>
                <i class="fa fa-caret-down text-gray-400 hidden sm:block"></i>
            </div>
            
            <!-- 下拉菜单 -->
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg shadow-black/10 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0">
                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <i class="fa fa-user w-5"></i> 个人中心
                </a>
                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <i class="fa fa-cog w-5"></i> 系统设置
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="logout.php" class="block px-4 py-2 text-sm text-danger hover:bg-red-50">
                    <i class="fa fa-sign-out w-5"></i> 退出登录
                </a>
            </div>
        </div>
    </div>
</header>

<script>
// 全局函数已在 sidebar.php 中定义
</script>
