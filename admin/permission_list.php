<?php
/**
 * 更新角色编辑页面的权限配置
 * 基于实际项目功能扫描生成完整权限列表
 */

// 完整的权限模块配置
$permissionModules = [
    'dashboard' => '📊 数据大屏',
    'position' => '🏢 岗位管理',
    'customer' => '👥 客户管理',
    'user' => '👤 用户管理',
    'article' => '文章管理',
    'store' => '🏪 门店管理',
    'salesmen' => '👔 销售管理',
    'finance' => '💰 财务管理',
    'config' => '⚙️ 系统配置',
    'role' => '🎭 角色管理',
    'report' => '📈 报表统计',
    'notification' => '🔔 消息通知',
    'marketing' => '📢 营销管理',
    'miniprogram' => '📱 小程序管理',
    'file' => '📁 文件管理',
    'system' => '🔧 系统工具'
];

// 标准操作权限
$permissionActions = [
    'view' => '查看',
    'create' => '➕ 创建',
    'edit' => '编辑',
    'delete' => '删除',
    'export' => '导出',
    'import' => '导入',
    'batch' => '📦 批量',
    'detail' => '📋 详情',
    'audit' => '✅ 审核',
    'assign' => '📌 分配'
];

// 各模块特有操作
$moduleSpecificActions = [
    'dashboard' => ['refresh' => '🔄 刷新', 'configure' => '⚙️ 配置'],
    'customer' => ['follow' => '📞 跟进', 'transform' => '🔄 转化', 'pool' => '🏊 公海'],
    'article' => ['publish' => '📢 发布', 'feature' => '⭐ 推荐'],
    'user' => ['enable' => '✅ 启用', 'disable' => '🚫 禁用', 'reset_password' => '🔑 重置密码'],
    'finance' => ['reconcile' => '📊 对账', 'invoice' => '🧾 发票'],
    'notification' => ['send' => '发送', 'schedule' => '⏰ 定时'],
    'miniprogram' => ['sync' => '🔄 同步', 'deploy' => '🚀 部署'],
    'file' => ['upload' => '⬆️ 上传', 'download' => '⬇️ 下载'],
    'system' => ['backup' => '💾 备份', 'restore' => '恢复', 'log' => '📜 日志', 'cache' => '清缓存']
];

// 生成完整的权限配置 JavaScript
$jsCode = "// 完整的权限模块配置\n";
$jsCode .= "const permissionModules = " . json_encode($permissionModules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n\n";

$jsCode .= "// 基础操作权限\n";
$jsCode .= "const permissionActions = " . json_encode($permissionActions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n\n";

$jsCode .= "// 模块特有操作权限\n";
$jsCode .= "const moduleSpecificActions = " . json_encode($moduleSpecificActions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n";

// 保存到文件
file_put_contents(__DIR__ . '/permission_config.js', $jsCode);

// 生成 HTML 展示
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>完整权限列表 - 青园营地</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; padding: 30px; border-radius: 16px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { opacity: 0.9; margin-top: 10px; }
        
        .module-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 20px; }
        .module-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .module-card h3 { margin: 0 0 16px 0; color: #1890ff; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        
        .permission-section { margin-bottom: 16px; }
        .section-title { font-size: 13px; font-weight: 600; color: #999; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .permission-tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .permission-tag { 
            padding: 6px 12px; 
            background: #f0f5ff; 
            color: #0050b3; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: 500;
            border: 1px solid #d6e4ff;
        }
        .permission-tag.special {
            background: #f6ffed;
            color: #52c41a;
            border-color: #b7eb8f;
        }
        .permission-tag.warning {
            background: #fff7e6;
            color: #fa8c16;
            border-color: #ffd591;
        }
        
        .action-section { margin-top: 24px; padding-top: 24px; border-top: 2px solid #f0f0f0; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .action-card { background: #fafafa; padding: 16px; border-radius: 8px; }
        .action-card h4 { margin: 0 0 12px 0; font-size: 14px; color: #595959; }
        .action-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .action-tag {
            padding: 4px 10px;
            background: white;
            color: #666;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid #e8e8e8;
        }
        
        .btn-group { margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; }
        .btn-success { background: linear-gradient(135deg, #52c41a, #73d13d); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
        
        .code-block { background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; overflow-x: auto; margin: 20px 0; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 完整权限列表</h1>
            <p>基于项目实际功能扫描生成的权限配置，包含所有模块和操作</p>
        </div>
        
        <h2 style="color: #262626; margin-bottom: 20px;">📋 模块权限详情</h2>
        
        <div class="module-grid">
            <?php foreach ($permissionModules as $key => $name): ?>
            <div class="module-card">
                <h3><?= $name ?></h3>
                
                <div class="permission-section">
                    <div class="section-title">基础操作</div>
                    <div class="permission-tags">
                        <?php foreach ($permissionActions as $action => $label): ?>
                            <span class="permission-tag"><?= $label ?> (<?= $action ?>)</span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (isset($moduleSpecificActions[$key])): ?>
                <div class="permission-section">
                    <div class="section-title">特有操作</div>
                    <div class="permission-tags">
                        <?php foreach ($moduleSpecificActions[$key] as $action => $label): ?>
                            <span class="permission-tag special"><?= $label ?> (<?= $action ?>)</span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="action-section">
            <h2 style="color: #262626; margin-bottom: 20px;">📁 实际页面文件</h2>
            
            <div class="action-grid">
                <?php
                $moduleFiles = [
                    'dashboard' => ['dashboard.php'],
                    'position' => ['position.php', 'position_edit.php'],
                    'customer' => ['customer.php', 'customer_edit.php'],
                    'user' => ['user.php', 'profile.php'],
                    'article' => ['article.php', 'article_edit.php'],
                    'store' => ['store.php', 'store_edit.php'],
                    'salesmen' => ['salesmen.php', 'sales.php'],
                    'finance' => ['finance.php'],
                    'config' => ['config.php', 'decoration.php'],
                    'role' => ['roles.php', 'role_edit.php'],
                    'report' => ['reports.php'],
                    'notification' => ['notifications.php', 'notification_detail.php'],
                ];
                
                foreach ($moduleFiles as $module => $files):
                ?>
                <div class="action-card">
                    <h4><?= $permissionModules[$module] ?></h4>
                    <div class="action-tags">
                        <?php foreach ($files as $file): ?>
                            <span class="action-tag"><?= $file ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="btn-group">
            <a href="role_edit.php?id=2" class="btn btn-primary">编辑系统管理员角色</a>
            <a href="roles.php" class="btn btn-success">📋 返回角色管理</a>
            <a href="scan_permissions.php" class="btn" style="background: #f5f5f5; color: #666;">🔍 重新扫描</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #fff7e6; border-radius: 12px; border: 2px solid #ffd591;">
            <h3 style="margin: 0 0 10px 0; color: #fa8c16;">💡 使用说明</h3>
            <ul style="margin: 0; padding-left: 20px; line-height: 2; color: #595959;">
                <li>基础操作权限适用于所有模块（查看、创建、编辑、删除、导出、导入等）</li>
                <li>特有操作权限仅适用于特定模块（如客户的跟进、转化，文章的发布、推荐等）</li>
                <li>在角色编辑页面，可以勾选任意模块和操作的组合</li>
                <li>勾选整个模块表示拥有该模块所有权限</li>
                <li>超级管理员默认拥有所有权限（all: true）</li>
            </ul>
        </div>
    </div>
<?php include __DIR__ . '/../includes/error_handler_include.php'; ?>
</body>
</html>
