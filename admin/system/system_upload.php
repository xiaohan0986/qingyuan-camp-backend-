<?php
/**
 * 上传设置
 */

while (ob_get_level()) { ob_end_clean(); }

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$defaultConfig = [
    'upload_storage' => 'local',
    'upload_allowed_types' => 'jpg,jpeg,png,gif,webp,mp4,pdf',
    'upload_max_size' => '10',
    'oss_access_key_id' => '',
    'oss_access_key_secret' => '',
    'oss_bucket' => '',
    'oss_endpoint' => '',
    'oss_upload_dir' => 'shopauba',
    'dx_storage_url' => '',
    'dx_storage_token' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    Auth::initSession();
    if (!isset($_SESSION['admin_id'])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未登录或登录已过期'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        if ($_POST['action'] === 'save') {
            $config = SystemConfig::getInstance();
            $fields = [
                'upload_storage', 'upload_allowed_types', 'upload_max_size',
                'oss_access_key_id', 'oss_access_key_secret', 'oss_bucket', 'oss_endpoint', 'oss_upload_dir',
                'dx_storage_url', 'dx_storage_token',
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $config->updateConfig($f, trim((string)$_POST[$f]));
                }
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '上传设置保存成功'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'reset') {
            $config = SystemConfig::getInstance();
            foreach ($defaultConfig as $k => $v) { $config->updateConfig($k, $v); }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '已恢复默认配置', 'data' => $defaultConfig], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未知操作'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '上传设置';
$config = SystemConfig::getInstance();

$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) { $configs[$row['config_key']] = $row['config_value']; }
} catch (Exception $e) { $configs = []; }

foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') { $configs[$k] = $v; }
}

$currentStorage = $configs['upload_storage'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .config-section { background: white; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.04); }
        .section-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #f5f5f5; }
        .section-icon { font-size: 28px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); border-radius: 14px; color: white; flex-shrink: 0; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .section-info { flex: 1; }
        .section-title { font-size: 18px; font-weight: 600; color: #262626; margin-bottom: 4px; }
        .section-desc { font-size: 13px; color: #8c8c8c; line-height: 1.5; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 14px; color: #262626; font-weight: 500; margin-bottom: 10px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group input[type="number"], .form-group select { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; }
        .form-group input:focus, .form-group select:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 8px; line-height: 1.5; }
        .radio-group { display: flex; gap: 12px; flex-wrap: wrap; }
        .radio-card { position: relative; flex: 1; min-width: 180px; padding: 16px; border: 2px solid #e8e8e8; border-radius: 10px; cursor: pointer; transition: all 0.25s; background: #fafafa; }
        .radio-card:hover { border-color: #1890ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .radio-card input[type="radio"] { position: absolute; opacity: 0; }
        .radio-card .radio-icon { font-size: 28px; margin-bottom: 8px; }
        .radio-card .radio-label { font-size: 14px; font-weight: 600; color: #262626; margin-bottom: 4px; }
        .radio-card .radio-desc { font-size: 12px; color: #8c8c8c; line-height: 1.5; }
        .radio-card.checked { border-color: #1890ff; background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .radio-card.checked::after { content: '✓'; position: absolute; top: 10px; right: 10px; width: 22px; height: 22px; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: bold; }
        .storage-config { margin-top: 16px; padding: 20px; border-radius: 10px; background: #fafafa; border: 1px dashed #d9d9d9; transition: all 0.3s; }
        .storage-config.active { background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); border: 1px solid #1890ff; }
        .storage-config-title { font-size: 14px; font-weight: 600; color: #262626; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .btn { padding: 12px 24px; font-size: 14px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-default { background: #f5f5f5; color: #262626; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #fafafa; }
        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .alert-info { background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 100%); color: #0050b3; border: 1px solid #bae7ff; }
        .alert-success { background: linear-gradient(135deg, #f6ffed 0%, #f9f0ff 100%); color: #237804; border: 1px solid #b7eb8f; }
        .alert-warning { background: linear-gradient(135deg, #fffbe6 0%, #fff7e6 100%); color: #ad6800; border: 1px solid #ffe58f; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="config-section">
                <div class="section-header">
                    <div class="section-icon">📤</div>
                    <div class="section-info">
                        <div class="section-title">上传设置</div>
                        <div class="section-desc">配置文件存储方式、文件类型、大小限制等参数</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 选择不同的存储方式，下方会自动展示对应的配置项。本地存储无需配置，OSS / DX Storage 需要填写密钥信息。</div>
                
                <form id="uploadForm">
                    <h3 style="font-size: 15px; margin-bottom: 16px; color: #262626; font-weight: 600;">💾 存储方式</h3>
                    <div class="form-group">
                        <div class="radio-group" id="storageGroup">
                            <label class="radio-card <?= $currentStorage === 'local' ? 'checked' : '' ?>" data-value="local">
                                <input type="radio" name="upload_storage" value="local" <?= $currentStorage === 'local' ? 'checked' : '' ?>>
                                <div class="radio-icon">📁</div>
                                <div class="radio-label">本地存储</div>
                                <div class="radio-desc">文件保存在服务器本地磁盘，无需配置密钥</div>
                            </label>
                            <label class="radio-card <?= $currentStorage === 'oss' ? 'checked' : '' ?>" data-value="oss">
                                <input type="radio" name="upload_storage" value="oss" <?= $currentStorage === 'oss' ? 'checked' : '' ?>>
                                <div class="radio-icon">☁️</div>
                                <div class="radio-label">阿里云 OSS</div>
                                <div class="radio-desc">文件上传到阿里云对象存储，支持 CDN 加速</div>
                            </label>
                            <label class="radio-card <?= $currentStorage === 'dx' ? 'checked' : '' ?>" data-value="dx">
                                <input type="radio" name="upload_storage" value="dx" <?= $currentStorage === 'dx' ? 'checked' : '' ?>>
                                <div class="radio-icon">📦</div>
                                <div class="radio-label">DX Storage</div>
                                <div class="radio-desc">使用 DX Storage 自定义存储服务</div>
                            </label>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">⚙️ 通用设置</h3>
                    <div class="form-group">
                        <label>允许的文件类型</label>
                        <input type="text" name="upload_allowed_types" id="upload_allowed_types" value="<?= htmlspecialchars($configs['upload_allowed_types']) ?>" placeholder="jpg,jpeg,png,gif,webp,mp4,pdf">
                        <div class="hint">多个类型用英文逗号分隔，留空表示允许所有类型</div>
                    </div>
                    <div class="form-group">
                        <label>最大文件大小（MB）</label>
                        <input type="number" name="upload_max_size" id="upload_max_size" min="1" value="<?= htmlspecialchars($configs['upload_max_size']) ?>" placeholder="10">
                        <div class="hint">单个文件的最大大小限制，单位 MB</div>
                    </div>
                    
                    <!-- 阿里云 OSS 配置 -->
                    <div class="storage-config" id="ossConfig" style="<?= $currentStorage === 'oss' ? '' : 'display:none;' ?>">
                        <div class="storage-config-title">☁️ 阿里云 OSS 配置</div>
                        <div class="form-group">
                            <label>OSS Endpoint</label>
                            <input type="text" name="oss_endpoint" id="oss_endpoint" value="<?= htmlspecialchars($configs['oss_endpoint']) ?>" placeholder="如 oss-cn-hangzhou.aliyuncs.com">
                            <div class="hint">根据你的区域选择，如杭州：oss-cn-hangzhou.aliyuncs.com</div>
                        </div>
                        <div class="form-group">
                            <label>Bucket 名称</label>
                            <input type="text" name="oss_bucket" id="oss_bucket" value="<?= htmlspecialchars($configs['oss_bucket']) ?>" placeholder="你的 Bucket 名称">
                        </div>
                        <div class="form-group">
                            <label>AccessKey ID</label>
                            <input type="text" name="oss_access_key_id" id="oss_access_key_id" value="<?= htmlspecialchars($configs['oss_access_key_id']) ?>" placeholder="建议使用 RAM 子账号">
                        </div>
                        <div class="form-group">
                            <label>AccessKey Secret</label>
                            <input type="password" name="oss_access_key_secret" id="oss_access_key_secret" value="<?= htmlspecialchars($configs['oss_access_key_secret']) ?>" placeholder="AccessKey Secret（保密）">
                        </div>
                        <div class="form-group">
                            <label>上传目录前缀</label>
                            <input type="text" name="oss_upload_dir" id="oss_upload_dir" value="<?= htmlspecialchars($configs['oss_upload_dir']) ?>" placeholder="shopauba">
                            <div class="hint">OSS 中的根目录前缀</div>
                        </div>
                    </div>
                    
                    <!-- DX Storage 配置 -->
                    <div class="storage-config" id="dxConfig" style="<?= $currentStorage === 'dx' ? '' : 'display:none;' ?>">
                        <div class="storage-config-title">📦 DX Storage 配置</div>
                        <div class="form-group">
                            <label>DX Storage URL</label>
                            <input type="text" name="dx_storage_url" id="dx_storage_url" value="<?= htmlspecialchars($configs['dx_storage_url']) ?>" placeholder="如 https://storage.example.com">
                            <div class="hint">DX Storage 服务的基础 URL</div>
                        </div>
                        <div class="form-group">
                            <label>Access Token</label>
                            <input type="password" name="dx_storage_token" id="dx_storage_token" value="<?= htmlspecialchars($configs['dx_storage_token']) ?>" placeholder="DX Storage 的访问令牌">
                            <div class="hint">用于身份验证的访问令牌，请妥善保管</div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <div class="alert alert-warning" style="margin-top: 24px;">
                    <strong>⚠️ 安全提示：</strong>切换存储方式后，建议测试上传功能是否正常；AccessKey/Secret 是敏感信息，请妥善保管。
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.innerHTML = msg;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '240px';
        alert.style.maxWidth = '420px';
        alert.style.boxShadow = '0 4px 16px rgba(0,0,0,0.1)';
        document.body.appendChild(alert);
        setTimeout(() => { alert.style.opacity = '0'; alert.style.transition = 'opacity 0.3s'; setTimeout(() => alert.remove(), 300); }, 2800);
    }
    
    function updateStorageUI() {
        const checked = document.querySelector('input[name="upload_storage"]:checked');
        const value = checked ? checked.value : 'local';
        document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('checked'));
        const card = document.querySelector('.radio-card[data-value="' + value + '"]');
        if (card) card.classList.add('checked');
        document.getElementById('ossConfig').style.display = value === 'oss' ? 'block' : 'none';
        document.getElementById('dxConfig').style.display = value === 'dx' ? 'block' : 'none';
        if (value === 'oss') document.getElementById('ossConfig').classList.add('active');
        else document.getElementById('ossConfig').classList.remove('active');
        if (value === 'dx') document.getElementById('dxConfig').classList.add('active');
        else document.getElementById('dxConfig').classList.remove('active');
    }
    
    document.querySelectorAll('input[name="upload_storage"]').forEach(r => {
        r.addEventListener('change', updateStorageUI);
    });
    document.querySelectorAll('.radio-card').forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; updateStorageUI(); }
        });
    });
    
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save');
        const submitBtn = document.getElementById('saveBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ 保存中...';
        
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) showMessage('✅ ' + data.message, 'success');
                else showMessage('❌ ' + data.message, 'error');
            } catch (err) { showMessage('❌ 响应解析失败', 'error'); }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function resetConfig() {
        if (!confirm('确定要恢复默认配置吗？')) return;
        const fd = new FormData(); fd.append('action', 'reset');
        fetch('', { method: 'POST', body: new URLSearchParams(fd) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.data) {
                    Object.keys(data.data).forEach(k => {
                        const el = document.getElementById(k);
                        if (!el) return;
                        if (el.type === 'radio') {
                            document.querySelectorAll('input[name="' + k + '"]').forEach(r => r.checked = (r.value === data.data[k]));
                        } else el.value = data.data[k];
                    });
                    updateStorageUI();
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    
    updateStorageUI();
    </script>
</body>
</html>