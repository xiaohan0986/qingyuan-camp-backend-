<?php
/**
 * 短信通知设置
 */

while (ob_get_level()) { ob_end_clean(); }

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$defaultConfig = [
    'sms_provider' => 'aliyun',
    'sms_access_key_id' => '',
    'sms_access_key_secret' => '',
    'sms_sign_name' => '青园营地',
    'sms_template_register' => '',
    'sms_template_login' => '',
    'sms_template_notify' => '',
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
                'sms_provider', 'sms_access_key_id', 'sms_access_key_secret',
                'sms_sign_name', 'sms_template_register', 'sms_template_login', 'sms_template_notify',
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $config->updateConfig($f, trim((string)$_POST[$f]));
                }
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '短信通知设置保存成功'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'test') {
            $phone = trim($_POST['phone'] ?? '');
            if (empty($phone)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '请填写测试手机号'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // 占位：真实实现需调用短信 API；这里做格式校验 + 配置校验
            if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '手机号格式不正确'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $config = SystemConfig::getInstance();
            $provider = $config->get('sms_provider');
            $signName = $config->get('sms_sign_name');
            if (empty($provider) || empty($signName)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '请先保存短信配置'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => '测试请求已受理（演示模式，未实际发送短信）'
            ], JSON_UNESCAPED_UNICODE);
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
$pageTitle = '短信通知设置';
$config = SystemConfig::getInstance();

$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) { $configs[$row['config_key']] = $row['config_value']; }
} catch (Exception $e) { $configs = []; }

foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') { $configs[$k] = $v; }
}

$currentProvider = $configs['sms_provider'];
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
        .form-group input[type="text"], .form-group input[type="password"], .form-group select { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; }
        .form-group input:focus, .form-group select:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 8px; line-height: 1.5; }
        .radio-group { display: flex; gap: 12px; flex-wrap: wrap; }
        .radio-card { position: relative; flex: 1; min-width: 200px; padding: 16px; border: 2px solid #e8e8e8; border-radius: 10px; cursor: pointer; transition: all 0.25s; background: #fafafa; }
        .radio-card:hover { border-color: #1890ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .radio-card input[type="radio"] { position: absolute; opacity: 0; }
        .radio-card .radio-icon { font-size: 28px; margin-bottom: 8px; }
        .radio-card .radio-label { font-size: 14px; font-weight: 600; color: #262626; margin-bottom: 4px; }
        .radio-card .radio-desc { font-size: 12px; color: #8c8c8c; line-height: 1.5; }
        .radio-card.checked { border-color: #1890ff; background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .radio-card.checked::after { content: '✓'; position: absolute; top: 10px; right: 10px; width: 22px; height: 22px; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: bold; }
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
        .test-panel { margin-top: 16px; padding: 20px; background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); border-radius: 10px; border: 1px dashed #1890ff; }
        .test-panel-title { font-size: 14px; font-weight: 600; color: #262626; margin-bottom: 12px; }
        .test-row { display: flex; gap: 10px; align-items: stretch; }
        .test-row input { flex: 1; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; background: white; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="config-section">
                <div class="section-header">
                    <div class="section-icon">📱</div>
                    <div class="section-info">
                        <div class="section-title">短信通知设置</div>
                        <div class="section-desc">配置短信平台、模板 ID、签名等参数</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 配置正确的短信平台凭证后，用户注册、登录、订单通知等场景会自动调用短信服务。</div>
                
                <form id="smsForm">
                    <h3 style="font-size: 15px; margin-bottom: 16px; color: #262626; font-weight: 600;">📡 短信平台</h3>
                    <div class="form-group">
                        <div class="radio-group" id="providerGroup">
                            <label class="radio-card <?= $currentProvider === 'aliyun' ? 'checked' : '' ?>" data-value="aliyun">
                                <input type="radio" name="sms_provider" value="aliyun" <?= $currentProvider === 'aliyun' ? 'checked' : '' ?>>
                                <div class="radio-icon">🌐</div>
                                <div class="radio-label">阿里云短信</div>
                                <div class="radio-desc">阿里云通信（原阿里大于）短信服务</div>
                            </label>
                            <label class="radio-card <?= $currentProvider === 'tencent' ? 'checked' : '' ?>" data-value="tencent">
                                <input type="radio" name="sms_provider" value="tencent" <?= $currentProvider === 'tencent' ? 'checked' : '' ?>>
                                <div class="radio-icon">🐧</div>
                                <div class="radio-label">腾讯云短信</div>
                                <div class="radio-desc">腾讯云 SMS 短信服务</div>
                            </label>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">🔑 凭证信息</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>AccessKey ID / SecretId</label>
                            <input type="text" name="sms_access_key_id" id="sms_access_key_id" value="<?= htmlspecialchars($configs['sms_access_key_id']) ?>" placeholder="平台 API 密钥 ID">
                        </div>
                        <div class="form-group">
                            <label>AccessKey Secret / SecretKey</label>
                            <input type="password" name="sms_access_key_secret" id="sms_access_key_secret" value="<?= htmlspecialchars($configs['sms_access_key_secret']) ?>" placeholder="平台 API 密钥 Secret">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>短信签名</label>
                        <input type="text" name="sms_sign_name" id="sms_sign_name" value="<?= htmlspecialchars($configs['sms_sign_name']) ?>" placeholder="如 青园营地">
                        <div class="hint">短信签名需在平台审核通过后才能使用，一般 2-3 个汉字或字母</div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">📋 模板 ID</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>注册验证码模板 ID</label>
                            <input type="text" name="sms_template_register" id="sms_template_register" value="<?= htmlspecialchars($configs['sms_template_register']) ?>" placeholder="如 SMS_123456789">
                            <div class="hint">用户注册时发送的验证码模板</div>
                        </div>
                        <div class="form-group">
                            <label>登录验证码模板 ID</label>
                            <input type="text" name="sms_template_login" id="sms_template_login" value="<?= htmlspecialchars($configs['sms_template_login']) ?>" placeholder="如 SMS_123456789">
                            <div class="hint">用户登录时发送的验证码模板</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>通用通知模板 ID</label>
                        <input type="text" name="sms_template_notify" id="sms_template_notify" value="<?= htmlspecialchars($configs['sms_template_notify']) ?>" placeholder="如 SMS_123456789">
                        <div class="hint">订单状态、活动通知等通用场景</div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <!-- 测试发送面板 -->
                <div class="test-panel" style="margin-top: 24px;">
                    <div class="test-panel-title">🧪 测试发送</div>
                    <div class="test-row">
                        <input type="text" id="testPhone" placeholder="请输入测试手机号（11位）" maxlength="11">
                        <button type="button" class="btn btn-primary" id="testBtn" onclick="testSend()">发送测试</button>
                    </div>
                    <div style="font-size: 12px; color: #8c8c8c; margin-top: 8px;">演示模式下仅校验配置完整性，不会真实发送短信。</div>
                </div>
                
                <div class="alert alert-warning" style="margin-top: 24px;">
                    <strong>⚠️ 提示：</strong>短信签名和模板都需要在对应云厂商短信平台审核通过后才能使用，建议先在测试环境完成调试再上线。
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
    
    function updateProviderUI() {
        const checked = document.querySelector('input[name="sms_provider"]:checked');
        const value = checked ? checked.value : 'aliyun';
        document.querySelectorAll('#providerGroup .radio-card').forEach(c => c.classList.remove('checked'));
        const card = document.querySelector('#providerGroup .radio-card[data-value="' + value + '"]');
        if (card) card.classList.add('checked');
    }
    
    document.querySelectorAll('input[name="sms_provider"]').forEach(r => r.addEventListener('change', updateProviderUI));
    document.querySelectorAll('#providerGroup .radio-card').forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; updateProviderUI(); }
        });
    });
    
    document.getElementById('smsForm').addEventListener('submit', function(e) {
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
    
    function testSend() {
        const phone = document.getElementById('testPhone').value.trim();
        if (!phone) { showMessage('❌ 请输入手机号', 'error'); return; }
        if (!/^1[3-9]\d{9}$/.test(phone)) { showMessage('❌ 手机号格式不正确', 'error'); return; }
        const btn = document.getElementById('testBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳ 发送中...';
        const fd = new FormData(); fd.append('action', 'test'); fd.append('phone', phone);
        fetch('', { method: 'POST', body: new URLSearchParams(fd) })
        .then(res => res.json())
        .then(data => {
            if (data.success) showMessage('✅ ' + data.message, 'success');
            else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
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
                    updateProviderUI();
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    
    updateProviderUI();
    </script>
</body>
</html>