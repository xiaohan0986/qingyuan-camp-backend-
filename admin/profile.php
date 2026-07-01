<?php
/**
 * 个人中心页面
 */
require_once __DIR__ . '/includes/header.php';

// 从数据库实时查询用户信息，而不是从 session 读取
$userId = $_SESSION['user_id'] ?? 0;
$currentUser = null;

try {
    $config = require_once __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        ),
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec("SET NAMES utf8mb4");
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $currentUser = [
            'id' => $userData['id'],
            'username' => $userData['username'],
            'nickname' => $userData['nickname'] ?? $userData['username'],
            'role' => $userData['role'],
            'email' => $userData['email'] ?? '',
            'phone' => $userData['phone'] ?? '',
            'avatar' => $userData['avatar'] ?? '',
            'created_at' => $userData['created_at'],
            'last_login' => $userData['last_login_at'] ?? date('Y-m-d H:i:s')
        ];
    } else {
        // 如果数据库找不到，降级使用 session 数据
        $currentUser = [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nickname' => $_SESSION['nickname'] ?? $_SESSION['username'],
            'role' => $_SESSION['role'],
            'email' => $_SESSION['email'] ?? '',
            'phone' => $_SESSION['phone'] ?? '',
            'avatar' => $_SESSION['avatar'] ?? '',
            'created_at' => $_SESSION['created_at'] ?? '',
            'last_login' => $_SESSION['last_login'] ?? date('Y-m-d H:i:s')
        ];
    }
} catch (Exception $e) {
    // 数据库连接失败，降级使用 session 数据
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nickname' => $_SESSION['nickname'] ?? $_SESSION['username'],
        'role' => $_SESSION['role'],
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'avatar' => $_SESSION['avatar'] ?? '',
        'created_at' => $_SESSION['created_at'] ?? '',
        'last_login' => $_SESSION['last_login'] ?? date('Y-m-d H:i:s')
    ];
}

$pageTitle = '个人中心';
$currentPage = 'profile';
?>

<style>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-header {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    border-radius: 16px;
    padding: 40px;
    color: white;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    margin-bottom: 20px;
    border: 4px solid rgba(255, 255, 255, 0.5);
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.profile-username {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 16px;
}

.profile-role {
    display: inline-block;
    padding: 6px 16px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.profile-content {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-label {
    font-size: 13px;
    color: #8c8c8c;
    font-weight: 600;
}

.info-value {
    font-size: 15px;
    color: #262626;
    font-weight: 600;
    padding: 12px 16px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}

.action-buttons {
    display: flex;
    gap: 12px;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.3);
}

.btn-default {
    background: #f5f5f5;
    color: #262626;
}

.btn-default:hover {
    background: #e8e8e8;
}

.stat-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1890ff;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 13px;
    color: #595959;
    font-weight: 600;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="profile-container">
    <!-- 头部信息 -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo mb_substr($currentUser['nickname'], 0, 1); ?>
        </div>
        <div class="profile-name"><?php echo htmlspecialchars($currentUser['nickname']); ?></div>
        <div class="profile-username">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
        <div class="profile-role">
            <?php
            $roleNames = [1 => '超级管理员', 2 => '管理员', 3 => '普通用户'];
            echo $roleNames[$currentUser['role']] ?? '未知角色';
            ?>
        </div>
    </div>
    
    <!-- 主要内容 -->
    <div class="profile-content">
        <div class="section-title">📋 个人信息</div>
        
        <!-- 统计卡片 -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-value">ID: <?php echo $currentUser['id']; ?></div>
                <div class="stat-label">用户 ID</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo date('Y-m-d', strtotime($currentUser['last_login'])); ?></div>
                <div class="stat-label">最后登录</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo date('Y-m', strtotime($currentUser['created_at'])); ?></div>
                <div class="stat-label">注册时间</div>
            </div>
        </div>
        
        <!-- 详细信息 -->
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">👤 用户名</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">昵称</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['nickname']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">📧 邮箱</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['email']) ?: '未设置'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">📱 手机号</div>
                <div class="info-value"><?php echo htmlspecialchars($currentUser['phone']) ?: '未设置'; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">🔐 角色</div>
                <div class="info-value">
                    <?php
                    $roleNames = [1 => '超级管理员', 2 => '管理员', 3 => '普通用户'];
                    echo $roleNames[$currentUser['role']] ?? '未知角色';
                    ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">📅 注册时间</div>
                <div class="info-value">
                    <?php echo $currentUser['created_at'] ? date('Y-m-d H:i', strtotime($currentUser['created_at'])) : '未知'; ?>
                </div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="editProfile()">
                编辑资料
            </button>
            <button class="btn btn-default" onclick="changePassword()">
                🔑 修改密码
            </button>
            <a href="dashboard.php" class="btn btn-default">
                🏠 返回首页
            </a>
        </div>
    </div>
</div>

<script>

}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- 修改密码弹窗 -->
<div id="changePasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 32px; width: 90%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
        <div style="font-size: 20px; font-weight: 700; margin-bottom: 24px; color: #262626; display: flex; justify-content: space-between; align-items: center;">
            <span>🔑 修改密码</span>
            <span onclick="closeChangePasswordModal()" style="cursor: pointer; font-size: 24px; color: #8c8c8c; line-height: 1;">&times;</span>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #262626; font-size: 14px;">原密码</label>
            <input type="password" id="oldPassword" placeholder="请输入原密码" style="width: 100%; padding: 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;" />
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #262626; font-size: 14px;">新密码</label>
            <input type="password" id="newPassword" placeholder="请输入新密码（至少 6 位）" style="width: 100%; padding: 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;" />
        </div>
        
        <div style="margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #262626; font-size: 14px;">确认新密码</label>
            <input type="password" id="confirmPassword" placeholder="请再次输入新密码" style="width: 100%; padding: 12px; border: 2px solid #f0f0f0; border-radius: 8px; font-size: 14px; box-sizing: border-box;" />
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button onclick="submitChangePassword()" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">确认修改</button>
            <button onclick="closeChangePasswordModal()" style="flex: 1; padding: 12px; background: #f5f5f5; color: #262626; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">取消</button>
        </div>
    </div>
</div>

<script>
function editProfile() {
    alert('编辑资料功能开发中...');
}

function changePassword() {
    showChangePasswordModal();
}

function showChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
    document.getElementById('oldPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
}

function submitChangePassword() {
    const oldPassword = document.getElementById('oldPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!oldPassword) {
        alert('请输入原密码');
        return;
    }
    if (!newPassword) {
        alert('请输入新密码');
        return;
    }
    if (newPassword !== confirmPassword) {
        alert('两次输入的新密码不一致');
        return;
    }
    if (newPassword.length < 6) {
        alert('密码长度不能少于 6 位');
        return;
    }
    
    fetch('../api/profile.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            old_password: oldPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.code === 200) {
            alert(res.message);
            closeChangePasswordModal();
            window.location.href = 'logout.php';
        } else {
            alert('修改失败：' + res.message);
        }
    })
    .catch(err => {
        alert('网络错误，请稍后重试');
    });
}

// 关闭弹窗：点击背景
document.getElementById('changePasswordModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangePasswordModal();
    }
});

// 按 ESC 关闭
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeChangePasswordModal();
    }
});
</script>
