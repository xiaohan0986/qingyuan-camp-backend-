<?php
/**
 * 配送设置
 */

while (ob_get_level()) { ob_end_clean(); }

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

$defaultConfig = [
    'delivery_methods' => 'express,selffetch,nodelivery,merchant',
    'express_companies' => 'sf,zt,yt,yz,ems,other',
    'default_delivery_method' => 'express',
];

$deliveryMethods = [
    'express' => ['icon' => '📦', 'name' => '快递配送', 'desc' => '通过快递公司发货'],
    'selffetch' => ['icon' => '🏬', 'name' => '到店自提', 'desc' => '用户到指定门店自提'],
    'nodelivery' => ['icon' => '🎁', 'name' => '无需配送', 'desc' => '虚拟商品或服务'],
    'merchant' => ['icon' => '🚚', 'name' => '商家配送', 'desc' => '商家自有配送团队'],
];

$expressCompanies = [
    'sf' => ['icon' => '🚚', 'name' => '顺丰速运', 'desc' => '次日达，覆盖全国'],
    'zt' => ['icon' => '📮', 'name' => '中通快递', 'desc' => '性价比高'],
    'yt' => ['icon' => '📦', 'name' => '圆通速递', 'desc' => '覆盖广'],
    'yz' => ['icon' => '📬', 'name' => '韵达速递', 'desc' => '电商首选'],
    'ems' => ['icon' => '✉️', 'name' => 'EMS', 'desc' => '中国邮政速递'],
    'other' => ['icon' => '🛵', 'name' => '其他', 'desc' => '其他快递公司'],
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
            
            // 配送方式（多选 → 用逗号拼成字符串）
            $methods = isset($_POST['delivery_methods']) && is_array($_POST['delivery_methods'])
                ? array_map('trim', $_POST['delivery_methods']) : [];
            $methods = array_filter($methods, function($v) use ($deliveryMethods) {
                return isset($deliveryMethods[$v]);
            });
            if (empty($methods)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '至少选择一种配送方式'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 快递公司（多选 → 用逗号拼成字符串）
            $companies = isset($_POST['express_companies']) && is_array($_POST['express_companies'])
                ? array_map('trim', $_POST['express_companies']) : [];
            $companies = array_filter($companies, function($v) use ($expressCompanies) {
                return isset($expressCompanies[$v]);
            });
            
            $defaultMethod = $_POST['default_delivery_method'] ?? '';
            if (!isset($deliveryMethods[$defaultMethod]) || !in_array($defaultMethod, $methods)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '默认配送方式必须在已选择的配送方式中'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $config->updateConfig('delivery_methods', implode(',', $methods));
            $config->updateConfig('express_companies', implode(',', $companies));
            $config->updateConfig('default_delivery_method', $defaultMethod);
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '配送设置保存成功'], JSON_UNESCAPED_UNICODE);
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
$pageTitle = '配送设置';
$config = SystemConfig::getInstance();

$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) { $configs[$row['config_key']] = $row['config_value']; }
} catch (Exception $e) { $configs = []; }

foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') { $configs[$k] = $v; }
}

$selectedMethods = array_filter(explode(',', $configs['delivery_methods']));
$selectedCompanies = array_filter(explode(',', $configs['express_companies']));
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
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 12px; }
        .check-card { position: relative; padding: 14px; border: 2px solid #e8e8e8; border-radius: 10px; cursor: pointer; transition: all 0.25s; background: #fafafa; }
        .check-card:hover { border-color: #1890ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .check-card input { position: absolute; opacity: 0; }
        .check-card .check-icon { font-size: 26px; margin-bottom: 6px; }
        .check-card .check-label { font-size: 14px; font-weight: 600; color: #262626; margin-bottom: 4px; }
        .check-card .check-desc { font-size: 12px; color: #8c8c8c; line-height: 1.4; }
        .check-card.checked { border-color: #1890ff; background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .check-card.checked::after { content: '✓'; position: absolute; top: 8px; right: 8px; width: 22px; height: 22px; background: linear-gradient(135deg, #1890ff, #40a9ff); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: bold; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 14px; color: #262626; font-weight: 500; margin-bottom: 10px; }
        .form-group select { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; }
        .form-group select:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 8px; line-height: 1.5; }
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
        .conditional-section { margin-top: 16px; padding: 20px; border-radius: 10px; background: #fafafa; border: 1px dashed #d9d9d9; transition: all 0.3s; }
        .conditional-section.active { background: linear-gradient(135deg, #f0f5ff 0%, #faf5ff 100%); border: 1px solid #1890ff; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="config-section">
                <div class="section-header">
                    <div class="section-icon">🚚</div>
                    <div class="section-info">
                        <div class="section-title">配送设置</div>
                        <div class="section-desc">配置支持的配送方式、快递公司及默认配送方式</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 用户下单时可选择您启用的配送方式。快递公司只有在选择了"快递配送"时才生效。</div>
                
                <form id="deliveryForm">
                    <h3 style="font-size: 15px; margin-bottom: 16px; color: #262626; font-weight: 600;">📦 配送方式（多选）</h3>
                    <div class="form-group">
                        <div class="checkbox-grid" id="methodsGrid">
                            <?php foreach ($deliveryMethods as $key => $info): ?>
                                <label class="check-card <?= in_array($key, $selectedMethods) ? 'checked' : '' ?>" data-value="<?= $key ?>">
                                    <input type="checkbox" name="delivery_methods[]" value="<?= $key ?>" <?= in_array($key, $selectedMethods) ? 'checked' : '' ?>>
                                    <div class="check-icon"><?= $info['icon'] ?></div>
                                    <div class="check-label"><?= $info['name'] ?></div>
                                    <div class="check-desc"><?= $info['desc'] ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint">至少选择一种配送方式</div>
                    </div>
                    
                    <div class="conditional-section" id="expressSection" style="<?= in_array('express', $selectedMethods) ? '' : 'display:none;' ?>">
                        <h3 style="font-size: 15px; margin-bottom: 16px; color: #262626; font-weight: 600;">🏢 快递公司（多选）</h3>
                        <div class="form-group" style="margin-bottom: 0;">
                            <div class="checkbox-grid" id="companiesGrid">
                                <?php foreach ($expressCompanies as $key => $info): ?>
                                    <label class="check-card <?= in_array($key, $selectedCompanies) ? 'checked' : '' ?>" data-value="<?= $key ?>">
                                        <input type="checkbox" name="express_companies[]" value="<?= $key ?>" <?= in_array($key, $selectedCompanies) ? 'checked' : '' ?>>
                                        <div class="check-icon"><?= $info['icon'] ?></div>
                                        <div class="check-label"><?= $info['name'] ?></div>
                                        <div class="check-desc"><?= $info['desc'] ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="hint">至少选择一家快递公司，未选择时商家发货时可手动指定</div>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 16px; color: #262626; font-weight: 600;">⭐ 默认配送方式</h3>
                    <div class="form-group">
                        <select name="default_delivery_method" id="default_delivery_method">
                            <?php foreach ($deliveryMethods as $key => $info): ?>
                                <option value="<?= $key ?>" <?= $configs['default_delivery_method'] === $key ? 'selected' : '' ?>><?= $info['icon'] ?> <?= $info['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">用户下单页面默认选中的配送方式</div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <div class="alert alert-warning" style="margin-top: 24px;">
                    <strong>📌 提示：</strong>启用"快递配送"后，必须至少配置一家快递公司；调整后用户端立即生效。
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
    
    function updateCardUI(card) {
        const cb = card.querySelector('input[type="checkbox"]');
        if (cb && cb.checked) card.classList.add('checked');
        else card.classList.remove('checked');
    }
    
    function refreshExpressSection() {
        const expressChecked = document.querySelector('input[name="delivery_methods[]"][value="express"]').checked;
        const section = document.getElementById('expressSection');
        if (expressChecked) {
            section.style.display = 'block';
            section.classList.add('active');
        } else {
            section.style.display = 'none';
            section.classList.remove('active');
        }
    }
    
    document.querySelectorAll('.check-card').forEach(card => {
        const cb = card.querySelector('input[type="checkbox"]');
        if (!cb) return;
        cb.addEventListener('change', function() {
            updateCardUI(card);
            if (this.name === 'delivery_methods[]') refreshExpressSection();
        });
        card.addEventListener('click', function(e) {
            // 防止重复触发（点击 input 自身）
            if (e.target.tagName === 'INPUT') return;
            cb.checked = !cb.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });
    
    document.getElementById('deliveryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save');
        const submitBtn = document.getElementById('saveBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ 保存中...';
        
        fetch('', { method: 'POST', body: formData })
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
                    // 多选字段恢复
                    const methods = (data.data.delivery_methods || '').split(',').filter(Boolean);
                    const companies = (data.data.express_companies || '').split(',').filter(Boolean);
                    document.querySelectorAll('input[name="delivery_methods[]"]').forEach(cb => {
                        cb.checked = methods.includes(cb.value);
                        updateCardUI(cb.closest('.check-card'));
                    });
                    document.querySelectorAll('input[name="express_companies[]"]').forEach(cb => {
                        cb.checked = companies.includes(cb.value);
                        updateCardUI(cb.closest('.check-card'));
                    });
                    document.getElementById('default_delivery_method').value = data.data.default_delivery_method;
                    refreshExpressSection();
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else showMessage('❌ ' + data.message, 'error');
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    
    refreshExpressSection();
    </script>
</body>
</html>