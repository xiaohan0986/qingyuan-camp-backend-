<?php
$currentPage = 'user';

// 获取用户 ID（如果有则是编辑模式）
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $userId > 0;
$pageTitle = $isEditMode ? '编辑用户' : '新增用户';

// 初始化数据库变量
$dbSkills = [];
$dbCountries = [];
$userSkills = [];
$userCountries = [];
$userSpecialty = '';
$favoritePositions = [];
$favoriteArticles = [];
$favoriteStores = [];
$loadError = '';

try {
    // 使用 Database 单例类获取连接（避免重复加载配置文件）
    require_once __DIR__ . '/../includes/Database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 查询技能标签表
    $stmt = $pdo->query("SELECT skill_name FROM skill_tags ORDER BY sort_order");
    $dbSkills = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 查询国家标签表
    $stmt = $pdo->query("SELECT country_name FROM country_tags ORDER BY sort_order");
    $dbCountries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 查询用户选中的标签和收藏（仅在编辑模式下）
    if ($isEditMode) {
        $stmt = $pdo->prepare("SELECT skills, intended_countries, specialty FROM mini_program_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userSkills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];
            $userCountries = !empty($user['intended_countries']) ? json_decode($user['intended_countries'], true) : [];
            $userSpecialty = $user['specialty'] ?? '';
            
            if (!is_array($userSkills)) $userSkills = [];
            if (!is_array($userCountries)) $userCountries = [];
        }
        
        // 查询用户收藏的岗位
        $stmt = $pdo->prepare("SELECT position_id, created_at FROM user_favorites WHERE user_id = ? AND type = 'position' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]);
        $favoritePositions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 查询用户收藏的文章
        $stmt = $pdo->prepare("SELECT article_id, created_at FROM user_favorites WHERE user_id = ? AND type = 'article' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]);
        $favoriteArticles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 查询用户关注的门店
        $stmt = $pdo->prepare("SELECT store_id, created_at FROM user_favorites WHERE user_id = ? AND type = 'store' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]);
        $favoriteStores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    if (empty($dbSkills)) {
        $loadError = '技能标签表为空';
    }
    if (empty($dbCountries)) {
        $loadError = '国家标签表为空';
    }
} catch (Exception $e) {
    $loadError = '数据库加载失败：' . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
        .main-container {
            padding: 24px;
        }

        .edit-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 24px;
        }

        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .edit-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .edit-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #262626;
        }

        .mode-tag {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .mode-tag.edit {
            background: linear-gradient(135deg, #e6f7ff, #bae6fd);
            color: #0050b3;
        }

        .mode-tag.new {
            background: linear-gradient(135deg, #f6ffed, #d9f7be);
            color: #237804;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 11px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .btn-default {
            background: #f0f0f0;
            color: #666;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1890ff, #096dd9);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f5222d, #ff4d4f);
            color: white;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            color: #262626;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 10px;
            color: #262626;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group label.required::after {
            content: '*';
            color: #ff4d4f;
            margin-left: 4px;
        }

        .form-group input,
        .form-group select {
            padding: 11px 14px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1890ff;
            box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
        }

        .form-group input[readonly] {
            background: #f5f5f5;
            color: #1890ff;
            font-weight: 600;
            cursor: not-allowed;
        }

        .password-setted {
            background: #f0f9ff;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #bae6fd;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .password-setted span {
            color: #0369a1;
            font-weight: 600;
        }

        .password-setted button {
            padding: 4px 12px;
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
        }

        .form-hint {
            color: #8c8c8c;
            font-size: 13px;
            margin-top: 6px;
            font-weight: normal;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            padding-top: 24px;
            border-top: 2px solid #f0f0f0;
        }

        .avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
            border: 2px dashed #d9d9d9;
            margin-bottom: 20px;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            border: 2px solid #f0f0f0;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            margin-bottom: 12px;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .avatar-preview .placeholder {
            font-size: 48px;
            color: #d9d9d9;
        }

        .avatar-buttons {
            display: flex;
            gap: 8px;
        }

        .avatar-upload-btn,
        .avatar-remove-btn {
            padding: 6px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .avatar-upload-btn {
            background: #1890ff;
            color: white;
        }

        .avatar-upload-btn:hover {
            background: #40a9ff;
        }

        .avatar-remove-btn {
            background: #fff2f0;
            color: #cf1322;
        }

        .avatar-remove-btn:hover {
            background: #ffccc7;
        }

        /* 头像环绕布局 */
        .avatar-wrapper {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px;
            background: #fafafa;
            border-radius: 12px;
            border: 2px dashed #d9d9d9;
            align-items: flex-start;
        }

        .avatar-main {
            flex: 0 0 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            border: 2px solid #f0f0f0;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .avatar-preview .placeholder {
            font-size: 48px;
            color: #d9d9d9;
        }

        .avatar-fields {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .avatar-fields .form-group {
            margin-bottom: 0;
        }

        /* 用户收藏样式 */
        .favorites-list {
            height: 200px;
            overflow-y: auto;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 12px;
            background: #fafafa;
        }

        .favorites-list::-webkit-scrollbar {
            width: 6px;
        }

        .favorites-list::-webkit-scrollbar-thumb {
            background: #d9d9d9;
            border-radius: 3px;
        }

        .favorites-list::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .favorites-empty {
            text-align: center;
            color: #999;
            padding: 60px 0;
            font-size: 14px;
        }

        .favorites-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 12px;
        }

        .favorites-item:last-child {
            border-bottom: none;
        }

        .favorites-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #262626;
            font-size: 13px;
        }

        .favorites-time {
            flex-shrink: 0;
            color: #999;
            font-size: 12px;
            white-space: nowrap;
        }

        @media (max-width: 1200px) {
            .form-row {
                grid-template-columns: repeat(2, 1fr);
            }
            .avatar-fields {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 768px) {
            .avatar-wrapper {
                flex-direction: column;
                align-items: center;
            }
            .avatar-main {
                flex: none;
                width: 100%;
            }
            .avatar-fields {
                grid-template-columns: 1fr !important;
                width: 100%;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* 标签选择器样式 */
        .tag-selector-container {
            margin-top: 8px;
        }
        
        .tag-input-wrapper {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .tag-input {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .tag-input:focus {
            outline: none;
            border-color: #1890ff;
            box-shadow: 0 0 0 4px rgba(24,144,255,0.1);
        }
        
        .tag-add-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #1890ff, #096dd9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tag-add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(24,144,255,0.3);
        }
        
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 16px;
            background: #fafafa;
            border-radius: 8px;
            border: 2px solid #f0f0f0;
            min-height: 60px;
        }
        
        /* 预设标签样式 */
        .tag-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: #f0f0f0;
            color: #666;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            user-select: none;
        }
        
        .tag-item:hover {
            border-color: #1890ff;
            color: #1890ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24,144,255,0.15);
        }
        
        /* 选中状态 */
        .tag-item.selected {
            background: linear-gradient(135deg, #1890ff, #096dd9);
            color: white;
            border-color: #1890ff;
            box-shadow: 0 4px 12px rgba(24,144,255,0.3);
        }
        
        .tag-item.selected:hover {
            background: linear-gradient(135deg, #40a9ff, #1890ff);
            transform: translateY(-2px);
        }
        
        /* 所有标签的删除按钮 */
        .tag-item {
            position: relative;
            padding-right: 32px;
        }
        
        .tag-item .tag-remove {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            color: #ff4d4f;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s;
        }
        
        .tag-item:hover .tag-remove {
            opacity: 1;
        }
        
        .tag-item .tag-remove:hover {
            background: #ff4d4f;
            color: white;
        }
        
        /* 自定义标签样式 */
        .tag-item.custom {
            background: linear-gradient(135deg, #e6f7ff, #bae6fd);
            color: #0050b3;
            border-color: #91d5ff;
        }
        
        /* 长按删除提示 */
        .tag-item.long-pressing {
            animation: tagShake 0.5s ease-in-out infinite;
            background: #fff2f0 !important;
            border-color: #ff4d4f !important;
            color: #ff4d4f !important;
        }
        
        @keyframes tagShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
            20% { opacity: 1; transform: translateX(-50%) translateY(0); }
            80% { opacity: 1; transform: translateX(-50%) translateY(0); }
            100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
        }
        
        .tag-delete-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff4d4f, #ff7875);
            border-radius: 0 0 16px 16px;
            transition: width 0.1s linear;
            pointer-events: none;
        }
        
        .tag-item.long-pressing .tag-delete-progress {
            width: 100%;
        }
        
        .tag-section-title {
            width: 100%;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin: 16px 0 8px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tag-hint {
            color: #8c8c8c;
            font-size: 12px;
            margin-top: 12px;
            padding-left: 4px;
        }
    </style>

    <div class="edit-container">
        <div class="edit-header">
            <div class="edit-title">
                <h1><?php echo $isEditMode ? '👤 编辑用户' : '➕ 新增用户'; ?></h1>
                <span class="mode-tag <?php echo $isEditMode ? 'edit' : 'new'; ?>">
                    <?php echo $isEditMode ? '编辑模式' : '新增模式'; ?>
                </span>
            </div>
            <div class="header-actions">
                <button class="btn btn-default" onclick="window.location.href='user.php'">
                    ← 返回列表
                </button>
                <?php if ($isEditMode): ?>
                <button class="btn btn-danger" onclick="deleteUser()">
                    删除用户
                </button>
                <?php endif; ?>
            </div>
        </div>

        <form id="userForm" onsubmit="return saveUser(event)" class="edit-form">
            <input type="hidden" id="userId" name="id" value="<?php echo $userId; ?>">
            
            <!-- 头像环绕布局区域 -->
            <div class="avatar-wrapper">
                <div class="avatar-main">
                    <div class="avatar-preview" id="avatarPreview">
                        <div class="placeholder">👤</div>
                    </div>
                    <div class="avatar-buttons">
                        <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatarUploadInput').click()">
                            📷 选择头像
                        </button>
                        <button type="button" class="avatar-remove-btn" id="removeAvatarBtn" onclick="removeAvatar()" style="display: none;">
                            删除
                        </button>
                    </div>
                    <input type="file" id="avatarUploadInput" accept="image/*" style="display: none;" onchange="handleAvatarUpload(event)">
                    <input type="hidden" id="userAvatar" value="">
                </div>
                
                <div class="avatar-fields" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="form-group">
                        <label>会员 ID</label>
                        <input type="text" id="memberIdDisplay" readonly title="系统自动生成，不可修改" style="background: #f5f5f5; color: #1890ff; font-weight: 600;">
                    </div>
                    <div class="form-group">
                        <label class="required">用户名</label>
                        <input type="text" id="username" name="username" required placeholder="请输入用户名">
                    </div>
                    <div class="form-group">
                        <label>昵称</label>
                        <input type="text" id="nickname" name="nickname" placeholder="请输入昵称">
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <div id="passwordSetted" style="display: none;">
                            <div class="password-setted" style="padding: 8px 12px; margin-bottom: 0;">
                                <span style="font-size: 13px;">✓ 已设置</span>
                                <button type="button" onclick="showPasswordInput()" style="padding: 2px 8px; font-size: 12px;">修改</button>
                            </div>
                        </div>
                        <input type="password" id="passwordInput" name="password" placeholder="留空不修改">
                        <span id="passwordHint" class="form-hint">留空则不修改密码</span>
                    </div>
                    <div class="form-group">
                        <label>📱 OpenID</label>
                        <input type="text" id="wechatId" name="wechat_id" placeholder="自动获取" readonly style="background-color: #fafafa; cursor: not-allowed; color: #8c8c8c;">
                        <span class="form-hint">💡 用户登录小程序后自动获取</span>
                    </div>
                    <div class="form-group">
                        <label>手机号</label>
                        <input type="text" id="phone" name="phone" placeholder="请输入手机号">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">📋 其他信息</div>
                
                <div class="form-row" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="form-group">
                        <label class="required">真实姓名</label>
                        <input type="text" id="real_name" name="real_name" placeholder="请输入真实姓名" required>
                    </div>
                    <div class="form-group">
                        <label>性别</label>
                        <select id="gender" name="gender">
                            <option value="-1">未选择</option>
                            <option value="0">男</option>
                            <option value="1">女</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>年龄</label>
                        <input type="number" id="age" name="age" placeholder="请输入年龄" min="1" max="150">
                    </div>
                </div>
                
                <div class="form-row" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="form-group">
                        <label>学历</label>
                        <select id="education" name="education">
                            <option value="">请选择</option>
                            <option value="初中及以下">初中及以下</option>
                            <option value="高中/中专">高中/中专</option>
                            <option value="大专">大专</option>
                            <option value="本科">本科</option>
                            <option value="硕士">硕士</option>
                            <option value="博士">博士</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>工作/职业</label>
                        <input type="text" id="occupation" name="occupation" placeholder="请输入工作/职业">
                    </div>
                    <div class="form-group">
                        <label>状态</label>
                        <select id="status" name="status">
                            <option value="1">启用</option>
                            <option value="0">禁用</option>
                        </select>
                    </div>
                </div>
                
                <!-- 特长（普通文本输入框） -->
                <div class="form-row" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label>特长（文本）</label>
                        <input type="text" id="specialty" name="specialty" value="<?php echo htmlspecialchars($userSpecialty, ENT_QUOTES, 'UTF-8'); ?>" placeholder="请输入特长，多个特长用逗号分隔" />
                        <div class="form-hint">💡 简单文本输入，多个特长可用逗号分隔</div>
                    </div>
                </div>
                
                <!-- 用户收藏 -->
                <?php if($isEditMode): ?>
                <div class="form-row" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="form-group">
                        <label>岗位收藏（<?php echo count($favoritePositions); ?>）</label>
                        <div class="favorites-list">
                            <?php if(empty($favoritePositions)): ?>
                            <div class="favorites-empty">暂无收藏</div>
                            <?php else: ?>
                            <?php foreach($favoritePositions as $fav): ?>
                            <?php
                                // 获取岗位标题
                                $stmt = $pdo->prepare("SELECT title FROM positions WHERE id = ?");
                                $stmt->execute([$fav['position_id']]);
                                $position = $stmt->fetch(PDO::FETCH_ASSOC);
                                $title = $position ? $position['title'] : '已删除';
                            ?>
                            <div class="favorites-item">
                                <span class="favorites-title"><?php echo htmlspecialchars($title); ?></span>
                                <span class="favorites-time"><?php echo date('m-d H:i', strtotime($fav['created_at'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>文章收藏（<?php echo count($favoriteArticles); ?>）</label>
                        <div class="favorites-list">
                            <?php if(empty($favoriteArticles)): ?>
                            <div class="favorites-empty">暂无收藏</div>
                            <?php else: ?>
                            <?php foreach($favoriteArticles as $fav): ?>
                            <?php
                                // 获取文章标题
                                $stmt = $pdo->prepare("SELECT title FROM articles WHERE id = ?");
                                $stmt->execute([$fav['article_id']]);
                                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                                $title = $article ? $article['title'] : '已删除';
                            ?>
                            <div class="favorites-item">
                                <span class="favorites-title"><?php echo htmlspecialchars($title); ?></span>
                                <span class="favorites-time"><?php echo date('m-d H:i', strtotime($fav['created_at'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>关注门店（<?php echo count($favoriteStores); ?>）</label>
                        <div class="favorites-list">
                            <?php if(empty($favoriteStores)): ?>
                            <div class="favorites-empty">暂无收藏</div>
                            <?php else: ?>
                            <?php foreach($favoriteStores as $fav): ?>
                            <?php
                                // 获取门店名称
                                $stmt = $pdo->prepare("SELECT name FROM stores WHERE id = ?");
                                $stmt->execute([$fav['store_id']]);
                                $store = $stmt->fetch(PDO::FETCH_ASSOC);
                                $title = $store ? $store['name'] : '已删除';
                            ?>
                            <div class="favorites-item">
                                <span class="favorites-title"><?php echo htmlspecialchars($title); ?></span>
                                <span class="favorites-time"><?php echo date('m-d H:i', strtotime($fav['created_at'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-row" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="form-group">
                        <label>特长（标签）</label>
                        <div class="tag-selector-container">
                            <div class="tag-input-wrapper">
                                <input type="text" id="skillsInput" class="tag-input" placeholder="输入自定义特长，按回车或点击添加" />
                                <button type="button" class="tag-add-btn" onclick="addSkill()">➕ 添加自定义</button>
                            </div>
                            <div id="skillsTagList" class="tag-list">
                                <!-- 动态生成的标签 -->
                            </div>
                            <input type="hidden" id="skills" name="skills" value="" />
                            <div class="tag-hint">💡 点击标签切换选中（蓝色=选中） | 悬浮点击×删除标签</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>意向国家</label>
                        <div class="tag-selector-container">
                            <div class="tag-input-wrapper">
                                <input type="text" id="countriesInput" class="tag-input" placeholder="输入自定义国家，按回车或点击添加" />
                                <button type="button" class="tag-add-btn" onclick="addCountry()">➕ 添加自定义</button>
                            </div>
                            <div id="countriesTagList" class="tag-list">
                                <!-- 动态生成的标签 -->
                            </div>
                            <input type="hidden" id="intended_countries" name="intended_countries" value="" />
                            <div class="tag-hint">💡 点击标签切换选中（蓝色=选中） | 悬浮点击×删除标签</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="window.location.href='user.php'">
                    取消
                </button>
                <button type="submit" class="btn btn-primary">
                    💾 保存
                </button>
            </div>
        </form>
    </div>

    <?php
    // 从数据库加载标签数据和用户选中的标签
    // 调试输出（生产环境可删除）
    ?>
    <div style="background: #fff3cd; padding: 15px; margin: 20px; border: 2px solid #ffc107;">
        <strong>🔍 PHP 加载的数据：</strong><br>
        技能标签数：<?php echo count($dbSkills); ?> 个<br>
        技能标签：<?php echo implode(', ', $dbSkills); ?><br>
        <br>
        国家标签数：<?php echo count($dbCountries); ?> 个<br>
        国家标签：<?php echo implode(', ', $dbCountries); ?><br>
        <br>
        <strong>用户选中的技能：</strong><?php echo implode(', ', $userSkills); ?><br>
        <strong>用户选中的国家：</strong><?php echo implode(', ', $userCountries); ?><br>
        <strong>用户特长（文本）：</strong><?php echo htmlspecialchars($userSpecialty ?? '空'); ?><br>
        <?php if($loadError): ?>
        <br><strong style="color: red;">错误：<?php echo $loadError; ?></strong>
        <?php endif; ?>
        <br>
        <strong>调试 - $userSpecialty 值：</strong><?php var_dump($userSpecialty); ?><br>
        <strong>调试 - 是否编辑模式：</strong><?php echo isset($_GET['id']) ? '是 (ID=' . $_GET['id'] . ')' : '否'; ?>
        <br><br>
        <button type="button" onclick="checkSpecialtyValue()">🔍 检查表单实际值</button>
        <div id="specialtyCheckResult" style="margin-top:10px;padding:10px;background:#e6f7ff;border:1px solid #91d5ff;"></div>
    </div>
    
    <script>
    function checkSpecialtyValue() {
        const input = document.getElementById('specialty');
        const result = document.getElementById('specialtyCheckResult');
        if (input) {
            result.innerHTML = `
                <strong>Input 元素存在</strong><br>
                value 属性：[${input.value}]<br>
                getAttribute('value'): [${input.getAttribute('value')}]<br>
                placeholder: [${input.placeholder}]<br>
                类型：${input.type}<br>
                是否禁用：${input.disabled}<br>
                是否只读：${input.readOnly}
            `;
        } else {
            result.innerHTML = '<strong style="color:red">❌ Input 元素不存在！</strong>';
        }
    }
    </script>
    
    <script>
    let userAvatarUrl = '';
    
    // 标签选择器数据
    let selectedSkills = <?php echo json_encode($userSkills, JSON_UNESCAPED_UNICODE); ?>;      // 已选中的特长（从数据库加载）
    let selectedCountries = <?php echo json_encode($userCountries, JSON_UNESCAPED_UNICODE); ?>;   // 已选中的国家（从数据库加载）
    let customSkills = [];        // 自定义特长
    let customCountries = [];     // 自定义国家
    
    // 预设选项（从数据库加载）
    const skillOptions = <?php echo json_encode($dbSkills, JSON_UNESCAPED_UNICODE); ?>;
    
    const countryOptions = <?php echo json_encode($dbCountries, JSON_UNESCAPED_UNICODE); ?>;

    // 显示头像
    function displayAvatar(avatarUrl) {
        const preview = document.getElementById('avatarPreview');
        
        if (avatarUrl && avatarUrl.trim() !== '') {
            let finalUrl = avatarUrl;
            
            if (avatarUrl.startsWith('http://') || avatarUrl.startsWith('https://')) {
                finalUrl = avatarUrl;
            } else if (avatarUrl.startsWith('/uploads/')) {
                // 使用动态路径，适应任何部署环境
                const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                const projectPath = basePath.substring(0, basePath.lastIndexOf('/'));
                finalUrl = window.location.protocol + '//' + window.location.host + projectPath + avatarUrl;
            }
            
            const img = document.createElement('img');
            img.src = finalUrl;
            img.alt = '用户头像';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.display = 'block';
            
            img.onload = function() {
                console.log('[displayAvatar] 图片加载成功:', finalUrl);
            };
            
            img.onerror = function() {
                console.error('[displayAvatar] 图片加载失败:', finalUrl);
                preview.innerHTML = '<div class="placeholder">👤</div>';
            };
            
            preview.innerHTML = '';
            preview.appendChild(img);
            document.getElementById('removeAvatarBtn').style.display = 'inline-block';
        } else {
            preview.innerHTML = '<div class="placeholder">👤</div>';
            document.getElementById('removeAvatarBtn').style.display = 'none';
        }
    }

    // 处理头像上传
    function handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('只支持 JPG、PNG、GIF 格式图片');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('图片大小不能超过 5MB');
            return;
        }
        
        const formData = new FormData();
        formData.append('avatar', file);
        
        // 使用相对路径，适应任何部署环境
        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const uploadUrl = window.location.protocol + '//' + window.location.host + basePath.substring(0, basePath.lastIndexOf('/')) + '/api/upload_avatar.php';
        
        fetch(uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const imageUrl = res.data.url;
                displayAvatar(imageUrl);
                document.getElementById('userAvatar').value = imageUrl;
                userAvatarUrl = imageUrl;
                alert('✅ 头像上传成功');
            } else {
                alert('❌ 上传失败：' + res.message);
            }
        })
        .catch(err => {
            console.error('上传失败:', err);
            alert('❌ 网络错误');
        });
        
        event.target.value = '';
    }

    // 删除头像
    function removeAvatar() {
        if (confirm('确定要删除用户头像吗？')) {
            document.getElementById('userAvatar').value = '';
            userAvatarUrl = '';
            displayAvatar('');
        }
    }
    
    // ========== 标签选择器函数 ==========
    
    // 从预设选项中删除特长
    async function deleteSkillFromOptions(skill) {
        if (confirm(`确定要从预设选项中删除"${skill}"吗？\n\n删除后，所有用户将无法再选择此选项，但已选择的用户不受影响。`)) {
            try {
                // 调用 API 从数据库删除（user_id 传 0 即可，因为是从预设表删除）
                const response = await fetch('../api/delete_skill_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `skill=${encodeURIComponent(skill)}`
                });
                
                const result = await response.json();
                
                if (result.code === 200) {
                    // 显示提示
                    showToast(`✅ 已删除预设标签：${skill}`);
                    
                    // 延迟刷新页面，让提示显示一会儿
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(`❌ 删除失败：${result.message || '未知错误'}`);
                }
            } catch (error) {
                console.error('删除标签失败', error);
                showToast(`❌ 网络错误：${error.message}`);
            }
        }
    }
    
    // 从预设选项中删除国家
    async function deleteCountryFromOptions(country) {
        if (confirm(`确定要从预设选项中删除"${country}"吗？\n\n删除后，所有用户将无法再选择此选项，但已选择的用户不受影响。`)) {
            try {
                // 调用 API 从数据库删除（user_id 传 0 即可，因为是从预设表删除）
                const response = await fetch('../api/delete_country_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `country=${encodeURIComponent(country)}`
                });
                
                const result = await response.json();
                
                if (result.code === 200) {
                    // 显示提示
                    showToast(`✅ 已删除预设标签：${country}`);
                    
                    // 延迟刷新页面，让提示显示一会儿
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(`❌ 删除失败：${result.message || '未知错误'}`);
                }
            } catch (error) {
                console.error('删除标签失败', error);
                showToast(`❌ 网络错误：${error.message}`);
            }
        }
    }
    
    // 显示提示消息
    function showToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 9999;
            animation: fadeInOut 2s ease-in-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 2000);
    }
    
    // 切换特长选中状态
    function toggleSkill(skill) {
        const index = selectedSkills.indexOf(skill);
        if (index > -1) {
            // 已选中，取消选中
            selectedSkills.splice(index, 1);
        } else {
            // 未选中，添加
            selectedSkills.push(skill);
        }
        renderSkillsTags();
        updateSkillsHidden();
    }
    
    // 添加自定义特长 - 添加到数据库
    async function addSkill() {
        const input = document.getElementById('skillsInput');
        const value = input.value.trim();
        
        if (!value) {
            alert('请输入特长名称');
            return;
        }
        
        // 检查是否已存在
        const allSkills = [...skillOptions, ...customSkills];
        if (allSkills.includes(value)) {
            alert('该特长已存在');
            return;
        }
        
        try {
            // 调用 API 添加到数据库
            const response = await fetch('../api/add_skill_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `skill=${encodeURIComponent(value)}`
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                alert('✅ 添加成功，页面将刷新');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('❌ 添加失败：' + result.message);
            }
        } catch (error) {
            alert('❌ 网络错误：' + error.message);
        }
    }
    
    // 删除自定义特长
    function removeCustomSkill(skill) {
        customSkills = customSkills.filter(s => s !== skill);
        selectedSkills = selectedSkills.filter(s => s !== skill);
        renderSkillsTags();
        updateSkillsHidden();
    }
    
    // 渲染特长标签
    function renderSkillsTags() {
        const container = document.getElementById('skillsTagList');
        
        // 预设标签 - 全部显示删除按钮
        let html = skillOptions.map(skill => {
            const isSelected = selectedSkills.includes(skill);
            return `<div class="tag-item ${isSelected ? 'selected' : ''}" 
                    onclick="toggleSkill('${skill}')">
                    ${skill}
                    <span class="tag-remove" onclick="event.stopPropagation(); deleteSkillFromOptions('${skill}')">×</span>
                    </div>`;
        }).join('');
        
        // 自定义标签
        if (customSkills.length > 0) {
            html += customSkills.map(skill => {
                const isSelected = selectedSkills.includes(skill);
                return `<div class="tag-item custom ${isSelected ? 'selected' : ''}" 
                        onclick="toggleSkill('${skill}')">
                        ${skill}
                        <span class="tag-remove" onclick="event.stopPropagation(); deleteSkillFromOptions('${skill}')">×</span>
                        </div>`;
            }).join('');
        }
        
        container.innerHTML = html;
    }
    
    // 更新隐藏字段
    function updateSkillsHidden() {
        document.getElementById('skills').value = JSON.stringify(selectedSkills);
    }
    
    // 切换国家选中状态
    function toggleCountry(country) {
        const index = selectedCountries.indexOf(country);
        if (index > -1) {
            // 已选中，取消选中
            selectedCountries.splice(index, 1);
        } else {
            // 未选中，添加
            selectedCountries.push(country);
        }
        renderCountriesTags();
        updateCountriesHidden();
    }
    
    // 添加自定义国家 - 添加到数据库
    async function addCountry() {
        const input = document.getElementById('countriesInput');
        const value = input.value.trim();
        
        if (!value) {
            alert('请输入国家名称');
            return;
        }
        
        // 检查是否已存在
        const allCountries = [...countryOptions, ...customCountries];
        if (allCountries.includes(value)) {
            alert('该国家已存在');
            return;
        }
        
        try {
            // 调用 API 添加到数据库
            const response = await fetch('../api/add_country_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `country=${encodeURIComponent(value)}`
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                alert('✅ 添加成功，页面将刷新');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('❌ 添加失败：' + result.message);
            }
        } catch (error) {
            alert('❌ 网络错误：' + error.message);
        }
    }
    
    // 删除自定义国家
    function removeCustomCountry(country) {
        customCountries = customCountries.filter(c => c !== country);
        selectedCountries = selectedCountries.filter(c => c !== country);
        renderCountriesTags();
        updateCountriesHidden();
    }
    
    // 渲染国家标签
    function renderCountriesTags() {
        const container = document.getElementById('countriesTagList');
        
        // 预设标签 - 全部显示删除按钮
        let html = countryOptions.map(country => {
            const isSelected = selectedCountries.includes(country);
            return `<div class="tag-item ${isSelected ? 'selected' : ''}" 
                    onclick="toggleCountry('${country}')">
                    ${country}
                    <span class="tag-remove" onclick="event.stopPropagation(); deleteCountryFromOptions('${country}')">×</span>
                    </div>`;
        }).join('');
        
        // 自定义标签
        if (customCountries.length > 0) {
            html += customCountries.map(country => {
                const isSelected = selectedCountries.includes(country);
                return `<div class="tag-item custom ${isSelected ? 'selected' : ''}" 
                        onclick="toggleCountry('${country}')">
                        ${country}
                        <span class="tag-remove" onclick="event.stopPropagation(); deleteCountryFromOptions('${country}')">×</span>
                        </div>`;
            }).join('');
        }
        
        container.innerHTML = html;
    }
    
    // 更新隐藏字段
    function updateCountriesHidden() {
        document.getElementById('intended_countries').value = JSON.stringify(selectedCountries);
    }
    
    // 回车键添加
    function handleSkillKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill();
        }
    }
    
    function handleCountryKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addCountry();
        }
    }

    // 显示密码输入框
    function showPasswordInput() {
        document.getElementById('passwordSetted').style.display = 'none';
        document.getElementById('passwordInput').style.display = 'block';
        document.getElementById('passwordInput').value = '';
        document.getElementById('passwordInput').required = true;
    }

    // 保存用户
    function saveUser(event) {
        event.preventDefault();
        
        // 确保隐藏字段已更新
        updateSkillsHidden();
        updateCountriesHidden();
        
        const formData = new FormData(event.target);
        formData.set('avatar', document.getElementById('userAvatar').value);
        
        const userId = document.getElementById('userId').value;
        const isEdit = userId && userId != '0';
        
        console.log('保存数据:', {
            skills: formData.get('skills'),
            intended_countries: formData.get('intended_countries')
        });
        
        fetch('../api/user.php?action=' + (isEdit ? 'update' : 'create'), {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                alert('✅ 保存成功！');
                window.location.href = 'user.php';
            } else {
                alert('❌ 保存失败：' + res.message);
            }
        })
        .catch(err => {
            console.error('保存失败:', err);
            alert('❌ 网络错误');
        });
        
        return false;
    }

    // 删除用户
    function deleteUser() {
        const userId = document.getElementById('userId').value;
        if (!userId) {
            alert('用户 ID 无效');
            return;
        }
        
        if (!confirm('⚠️ 确定要删除该用户吗？\n\n删除后无法恢复，请谨慎操作！')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', userId);
        
        fetch('../api/user.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200) {
                alert('✅ 删除成功！');
                window.location.href = 'user.php';
            } else {
                alert('❌ 删除失败：' + res.message);
            }
        })
        .catch(err => {
            console.error('删除失败:', err);
            alert('❌ 网络错误');
        });
    }

    // 加载用户数据（编辑模式）
    <?php if ($isEditMode): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const userId = <?php echo $userId; ?>;
        
        fetch('../api/user.php?action=detail&id=' + userId)
            .then(res => res.json())
            .then(res => {
                if (res.code !== 200) {
                    alert('获取用户信息失败：' + res.message);
                    return;
                }
                
                const data = res.data;
                
                // 填充表单
                document.getElementById('userId').value = data.id || '';
                document.getElementById('memberIdDisplay').value = data.member_id || '';
                document.getElementById('username').value = data.username || '';
                document.getElementById('nickname').value = data.nickname || '';
                document.getElementById('phone').value = data.phone || '';
                
                // 获取 OpenID（通过手机号查询 users 表，自动匹配）
                if (data.phone) {
                    fetch('../api/get_user_openid.php?phone=' + encodeURIComponent(data.phone))
                        .then(res => res.json())
                        .then(res => {
                            if (res.code === 200 && res.data && res.data.openid) {
                                document.getElementById('wechatId').value = res.data.openid;
                            } else {
                                document.getElementById('wechatId').value = '未获取到 OpenID';
                            }
                        })
                        .catch(err => {
                            console.error('获取 OpenID 失败:', err);
                            document.getElementById('wechatId').value = '获取失败';
                        });
                } else {
                    document.getElementById('wechatId').value = data.wechat_id || '';
                }
                document.getElementById('real_name').value = data.real_name || '';
                document.getElementById('gender').value = (data.gender !== undefined && data.gender !== null) ? data.gender : '-1';
                document.getElementById('age').value = data.age || '';
                document.getElementById('education').value = data.education || '';
                document.getElementById('occupation').value = data.occupation || '';
                document.getElementById('status').value = data.status || '1';
                document.getElementById('specialty').value = data.specialty || '';
                
                // 头像
                if (data.avatar) {
                    displayAvatar(data.avatar);
                    document.getElementById('userAvatar').value = data.avatar;
                    userAvatarUrl = data.avatar;
                }
                
                // 密码状态
                if (data.password_setted) {
                    document.getElementById('passwordSetted').style.display = 'block';
                    document.getElementById('passwordInput').style.display = 'none';
                    document.getElementById('passwordInput').required = false;
                } else {
                    document.getElementById('passwordSetted').style.display = 'none';
                    document.getElementById('passwordInput').style.display = 'block';
                    document.getElementById('passwordInput').required = true;
                }
                
                // 特长（JSON 数组）
                if (data.skills) {
                    try {
                        const skillsArray = JSON.parse(data.skills);
                        // 区分预设和自定义
                        selectedSkills = skillsArray;
                        customSkills = skillsArray.filter(s => !skillOptions.includes(s));
                        renderSkillsTags();
                        updateSkillsHidden();
                    } catch (e) {
                        console.error('解析特长数据失败', e);
                        selectedSkills = [];
                        customSkills = [];
                        renderSkillsTags();
                        updateSkillsHidden();
                    }
                } else {
                    selectedSkills = [];
                    customSkills = [];
                    renderSkillsTags();
                    updateSkillsHidden();
                }
                
                // 意向国家（JSON 数组）
                if (data.intended_countries) {
                    try {
                        const countriesArray = JSON.parse(data.intended_countries);
                        // 区分预设和自定义
                        selectedCountries = countriesArray;
                        customCountries = countriesArray.filter(c => !countryOptions.includes(c));
                        renderCountriesTags();
                        updateCountriesHidden();
                    } catch (e) {
                        console.error('解析意向国家数据失败', e);
                        selectedCountries = [];
                        customCountries = [];
                        renderCountriesTags();
                        updateCountriesHidden();
                    }
                } else {
                    selectedCountries = [];
                    customCountries = [];
                    renderCountriesTags();
                    updateCountriesHidden();
                }
            })
            .catch(err => {
                console.error('加载失败:', err);
                alert('加载用户信息失败');
            });
    });
    <?php else: ?>
    // 新建模式：会员 ID 显示为提示
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('memberIdDisplay').value = '保存后自动生成';
        document.getElementById('passwordInput').required = true;
        
        // 初始化标签选择器
        const skillsInput = document.getElementById('skillsInput');
        const countriesInput = document.getElementById('countriesInput');
        
        if (skillsInput) {
            skillsInput.addEventListener('keydown', handleSkillKeydown);
        }
        if (countriesInput) {
            countriesInput.addEventListener('keydown', handleCountryKeydown);
        }
        
        // 新建模式：初始化为空列表
        selectedSkills = [];
        customSkills = [];
        selectedCountries = [];
        customCountries = [];
        renderSkillsTags();
        updateSkillsHidden();
        renderCountriesTags();
        updateCountriesHidden();
    });
    <?php endif; ?>
    
    // 通用：页面加载完成后绑定事件
    document.addEventListener('DOMContentLoaded', function() {
        const skillsInput = document.getElementById('skillsInput');
        const countriesInput = document.getElementById('countriesInput');
        
        if (skillsInput && !skillsInput.hasAttribute('data-bound')) {
            skillsInput.addEventListener('keydown', handleSkillKeydown);
            skillsInput.setAttribute('data-bound', 'true');
        }
        if (countriesInput && !countriesInput.hasAttribute('data-bound')) {
            countriesInput.addEventListener('keydown', handleCountryKeydown);
            countriesInput.setAttribute('data-bound', 'true');
        }
    });
    </script>
    </div>

<?php require_once __DIR__ . '/includes/footer.php';
