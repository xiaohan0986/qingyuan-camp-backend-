<?php
/**
 * 客服设置
 */

while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$defaultConfig = [
    'service_phone' => '400-888-8888',
    'service_wechat' => 'qingyuancamp',
    'service_work_time' => '周一至周日 09:00-21:00',
    'service_qrcode' => '',
    'service_description' => '您好，欢迎来到青园营地。如有任何问题，请通过以下方式联系我们，我们将在工作时间为您解答。',
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
            $fields = ['service_phone', 'service_wechat', 'service_work_time', 'service_qrcode', 'service_description'];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $config->updateConfig($f, trim((string)$_POST[$f]));
                }
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '客服设置保存成功'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'reset') {
            $config = SystemConfig::getInstance();
            foreach ($defaultConfig as $k => $v) {
                $config->updateConfig($k, $v);
            }
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
$pageTitle = '客服设置';
$config = SystemConfig::getInstance();

$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) { $configs[$row['config_key']] = $row['config_value']; }
} catch (Exception $e) { $configs = []; }

foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') { $configs[$k] = $v; }
}
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
        .form-group input[type="text"], .form-group textarea { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; line-height: 1.6; }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 8px; line-height: 1.5; }
        .qrcode-uploader { display: flex; align-items: center; gap: 16px; }
        .qrcode-preview { width: 120px; height: 120px; border: 2px dashed #d9d9d9; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fafafa; overflow: hidden; flex-shrink: 0; }
        .qrcode-preview img { width: 100%; height: 100%; object-fit: contain; }
        .qrcode-preview .placeholder { font-size: 32px; color: #bfbfbf; }
        .qrcode-actions { flex: 1; }
        .qrcode-actions .upload-input { width: 100%; padding: 10px 12px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; background: #fafafa; }
        .btn { padding: 12px 24px; font-size: 14px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-default { background: #f5f5f5; color: #262626; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #fafafa; }
        .btn-mini { padding: 8px 14px; font-size: 13px; }
        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .alert-info { background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 100%); color: #0050b3; border: 1px solid #bae7ff; }
        .alert-success { background: linear-gradient(135deg, #f6ffed 0%, #f9f0ff 100%); color: #237804; border: 1px solid #b7eb8f; }
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
                    <div class="section-icon">💬</div>
                    <div class="section-info">
                        <div class="section-title">客服设置</div>
                        <div class="section-desc">配置客服联系方式、工作时间、二维码等信息，用户端将自动展示</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 这里配置的信息会展示在用户端的"联系客服"页面，修改保存后立即生效。</div>
                
                <form id="serviceForm">
                    <h3 style="font-size: 15px; margin-bottom: 20px; color: #262626; font-weight: 600;">📞 联系方式</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>客服电话</label>
                            <input type="text" name="service_phone" id="service_phone" value="<?= htmlspecialchars($configs['service_phone']) ?>" placeholder="如 400-888-8888">
                            <div class="hint">用户拨打咨询的客服电话号码</div>
                        </div>
                        <div class="form-group">
                            <label>客服微信号</label>
                            <input type="text" name="service_wechat" id="service_wechat" value="<?= htmlspecialchars($configs['service_wechat']) ?>" placeholder="如 qingyuancamp">
                            <div class="hint">用户可添加的企业微信号</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>客服工作时间</label>
                        <input type="text" name="service_work_time" id="service_work_time" value="<?= htmlspecialchars($configs['service_work_time']) ?>" placeholder="如 周一至周日 09:00-21:00">
                        <div class="hint">展示客服在线服务的时间段</div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">🖼️ 客服二维码</h3>
                    <div class="form-group">
                        <label>二维码图片 URL</label>
                        <div class="qrcode-uploader">
                            <div class="qrcode-preview" id="qrcodePreview">
                                <?php if (!empty($configs['service_qrcode'])): ?>
                                    <img src="<?= htmlspecialchars($configs['service_qrcode']) ?>" alt="客服二维码" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="placeholder" style="display:none;">🖼️</div>
                                <?php else: ?>
                                    <div class="placeholder">🖼️</div>
                                <?php endif; ?>
                            </div>
                            <div class="qrcode-actions">
                                <input type="text" name="service_qrcode" id="service_qrcode" class="upload-input" value="<?= htmlspecialchars($configs['service_qrcode']) ?>" placeholder="请填写二维码图片的 URL 地址（http/https）">
                                <div style="margin-top: 8px; display: flex; gap: 8px;">
                                    <button type="button" class="btn btn-default btn-mini" onclick="document.getElementById('qrFile').click()">上传图片</button>
                                    <button type="button" class="btn btn-default btn-mini" onclick="clearQrcode()">清除</button>
                                </div>
                                <input type="file" id="qrFile" accept="image/*" style="display:none;" onchange="uploadQrcode(this)">
                            </div>
                        </div>
                        <div class="hint">支持 JPG/PNG 格式，建议尺寸 300×300，最大 2MB。也可以直接填写图片 URL。</div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">服务说明</h3>
                    <div class="form-group">
                        <label>服务说明文案</label>
                        <textarea name="service_description" id="service_description" placeholder="请输入客服服务说明文案..."><?= htmlspecialchars($configs['service_description']) ?></textarea>
                        <div class="hint">展示在客服页面的说明文字，支持换行</div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <div class="alert alert-success" style="margin-top: 24px;">
                    <strong>📌 提示：</strong>二维码图片可上传到本系统素材库后填入 URL，也可以使用第三方图床地址。
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
    
    function uploadQrcode(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            showMessage('❌ 图片大小不能超过 2MB', 'error');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            const preview = document.getElementById('qrcodePreview');
            preview.innerHTML = '<img src="' + dataUrl + '" alt="客服二维码">';
            document.getElementById('service_qrcode').value = dataUrl;
            showMessage('✅ 图片已读取，保存后生效', 'success');
        };
        reader.readAsDataURL(file);
    }
    
    function clearQrcode() {
        document.getElementById('qrcodePreview').innerHTML = '<div class="placeholder">🖼️</div>';
        document.getElementById('service_qrcode').value = '';
        showMessage('已清除', 'info');
    }
    
    document.getElementById('serviceForm').addEventListener('submit', function(e) {
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
                        if (el) el.value = data.data[k];
                    });
                    clearQrcode();
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    </script>
</body>
</html>