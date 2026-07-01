<?php
/**
 * 支付设置
 */

while (ob_get_level()) { ob_end_clean(); }

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$defaultConfig = [
    'wechat_pay_enabled' => '1',
    'wechat_mch_id' => '',
    'wechat_api_v3_key' => '',
    'wechat_app_id' => '',
    'wechat_app_secret' => '',
    'alipay_enabled' => '0',
    'alipay_app_id' => '',
    'alipay_public_key' => '',
    'alipay_private_key' => '',
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
                'wechat_pay_enabled', 'wechat_mch_id', 'wechat_api_v3_key',
                'wechat_app_id', 'wechat_app_secret',
                'alipay_enabled', 'alipay_app_id', 'alipay_public_key', 'alipay_private_key',
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $val = $_POST[$f];
                    // 开关字段标准化为 0/1
                    if (in_array($f, ['wechat_pay_enabled', 'alipay_enabled'])) {
                        $val = ($val === '1' || $val === 'on' || $val === 'true') ? '1' : '0';
                    }
                    $config->updateConfig($f, trim((string)$val));
                }
            }
            // 关闭开关时确保存 0
            if (!isset($_POST['wechat_pay_enabled'])) $config->updateConfig('wechat_pay_enabled', '0');
            if (!isset($_POST['alipay_enabled'])) $config->updateConfig('alipay_enabled', '0');
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '支付设置保存成功'], JSON_UNESCAPED_UNICODE);
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
$pageTitle = '支付设置';
$config = SystemConfig::getInstance();

$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) { $configs[$row['config_key']] = $row['config_value']; }
} catch (Exception $e) { $configs = []; }

foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') { $configs[$k] = $v; }
}

$wechatEnabled = $configs['wechat_pay_enabled'] === '1';
$alipayEnabled = $configs['alipay_enabled'] === '1';
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
        .payment-card { padding: 24px; border-radius: 12px; border: 2px solid #e8e8e8; margin-bottom: 24px; background: #fafafa; transition: all 0.3s; }
        .payment-card.active { border-color: #1890ff; background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .payment-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0; }
        .payment-card-header.active-divider { border-bottom-color: rgba(102, 126, 234, 0.2); }
        .payment-info { display: flex; align-items: center; gap: 12px; }
        .payment-icon { font-size: 32px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .payment-card.wechat .payment-icon { background: linear-gradient(135deg, #07c160 0%, #00a854 100%); color: white; }
        .payment-card.alipay .payment-icon { background: linear-gradient(135deg, #1677ff 0%, #0958d9 100%); color: white; }
        .payment-name { font-size: 16px; font-weight: 600; color: #262626; }
        .payment-desc { font-size: 12px; color: #8c8c8c; margin-top: 2px; }
        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #d9d9d9; transition: 0.3s; border-radius: 28px; }
        .toggle-slider::before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch input:checked + .toggle-slider { background: linear-gradient(135deg, #1890ff, #40a9ff); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(24px); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; color: #262626; font-weight: 500; margin-bottom: 8px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group textarea { width: 100%; padding: 11px 14px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: white; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group textarea { min-height: 100px; resize: vertical; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 6px; line-height: 1.5; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
        .disabled-cover { position: relative; }
        .disabled-cover.disabled::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,0.7); border-radius: 8px; cursor: not-allowed; z-index: 1; }
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
                    <div class="section-icon">💳</div>
                    <div class="section-info">
                        <div class="section-title">支付设置</div>
                        <div class="section-desc">配置微信支付、支付宝等支付渠道的商户信息</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 支付密钥是核心敏感信息，请妥善保管。建议使用子商户号或受限 API 密钥，不要泄露主账号密钥。</div>
                
                <form id="paymentForm">
                    <!-- 微信支付 -->
                    <div class="payment-card wechat <?= $wechatEnabled ? 'active' : '' ?>" id="wechatCard">
                        <div class="payment-card-header <?= $wechatEnabled ? 'active-divider' : '' ?>">
                            <div class="payment-info">
                                <div class="payment-icon">💚</div>
                                <div>
                                    <div class="payment-name">微信支付</div>
                                    <div class="payment-desc">支持 JSAPI / H5 / 小程序支付</div>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="wechat_pay_enabled" id="wechat_pay_enabled" value="1" <?= $wechatEnabled ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="disabled-cover <?= $wechatEnabled ? '' : 'disabled' ?>" id="wechatBody">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>微信商户号</label>
                                    <input type="text" name="wechat_mch_id" id="wechat_mch_id" value="<?= htmlspecialchars($configs['wechat_mch_id']) ?>" placeholder="如 1234567890">
                                </div>
                                <div class="form-group">
                                    <label>APIv3 密钥</label>
                                    <input type="password" name="wechat_api_v3_key" id="wechat_api_v3_key" value="<?= htmlspecialchars($configs['wechat_api_v3_key']) ?>" placeholder="32 位 APIv3 密钥">
                                    <div class="hint">在微信支付商户平台 → API 安全 → APIv3 密钥中设置</div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>小程序 AppID</label>
                                    <input type="text" name="wechat_app_id" id="wechat_app_id" value="<?= htmlspecialchars($configs['wechat_app_id']) ?>" placeholder="小程序 AppID">
                                </div>
                                <div class="form-group">
                                    <label>小程序 AppSecret</label>
                                    <input type="password" name="wechat_app_secret" id="wechat_app_secret" value="<?= htmlspecialchars($configs['wechat_app_secret']) ?>" placeholder="小程序 AppSecret">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 支付宝 -->
                    <div class="payment-card alipay <?= $alipayEnabled ? 'active' : '' ?>" id="alipayCard">
                        <div class="payment-card-header <?= $alipayEnabled ? 'active-divider' : '' ?>">
                            <div class="payment-info">
                                <div class="payment-icon">💙</div>
                                <div>
                                    <div class="payment-name">支付宝</div>
                                    <div class="payment-desc">支持手机网站支付 / 当面付</div>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="alipay_enabled" id="alipay_enabled" value="1" <?= $alipayEnabled ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="disabled-cover <?= $alipayEnabled ? '' : 'disabled' ?>" id="alipayBody">
                            <div class="form-group">
                                <label>支付宝 AppID</label>
                                <input type="text" name="alipay_app_id" id="alipay_app_id" value="<?= htmlspecialchars($configs['alipay_app_id']) ?>" placeholder="支付宝应用 AppID">
                                <div class="hint">在支付宝开放平台 → 我的应用 中获取</div>
                            </div>
                            <div class="form-group">
                                <label>支付宝公钥</label>
                                <textarea name="alipay_public_key" id="alipay_public_key" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----"><?= htmlspecialchars($configs['alipay_public_key']) ?></textarea>
                                <div class="hint">用于验证支付宝回调签名，可在支付宝开放平台查看</div>
                            </div>
                            <div class="form-group">
                                <label>应用私钥</label>
                                <textarea name="alipay_private_key" id="alipay_private_key" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----"><?= htmlspecialchars($configs['alipay_private_key']) ?></textarea>
                                <div class="hint">用于请求签名，请妥善保管，勿泄露</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <div class="alert alert-warning" style="margin-top: 24px;">
                    <strong>⚠️ 安全提示：</strong>支付密钥一旦泄露可能被恶意使用，建议定期更换；同时在生产环境使用 HTTPS 回调地址。
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
    
    function syncToggleUI(name) {
        const cb = document.getElementById(name);
        const card = document.getElementById(name.replace('_enabled', 'Card'));
        const body = document.getElementById(name.replace('_enabled', 'Body'));
        if (cb.checked) {
            card.classList.add('active');
            card.querySelector('.payment-card-header').classList.add('active-divider');
            body.classList.remove('disabled');
        } else {
            card.classList.remove('active');
            card.querySelector('.payment-card-header').classList.remove('active-divider');
            body.classList.add('disabled');
        }
    }
    
    ['wechat_pay_enabled', 'alipay_enabled'].forEach(name => {
        const cb = document.getElementById(name);
        if (cb) cb.addEventListener('change', () => syncToggleUI(name));
    });
    
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
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
        if (!confirm('确定要恢复默认配置吗？所有密钥将被清空！')) return;
        const fd = new FormData(); fd.append('action', 'reset');
        fetch('', { method: 'POST', body: new URLSearchParams(fd) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.data) {
                    Object.keys(data.data).forEach(k => {
                        const el = document.getElementById(k);
                        if (!el) return;
                        if (el.type === 'checkbox') {
                            el.checked = data.data[k] === '1';
                            syncToggleUI(k);
                        } else el.value = data.data[k];
                    });
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    
    // 初始化开关状态
    syncToggleUI('wechat_pay_enabled');
    syncToggleUI('alipay_enabled');
    </script>
</body>
</html>