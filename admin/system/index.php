<?php
/**
 * 交易设置
 */

// 清空所有输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 引入文件
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/SystemConfig.php';

// 默认配置
$defaultConfig = [
    'default_shipping_fee' => '10.00',
    'free_shipping_threshold' => '99.00',
    'order_timeout_minutes' => '30',
    'auto_receive_days' => '15',
    'after_sale_days' => '7',
    'min_withdraw_amount' => '10.00',
];

// 处理 POST 请求
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
                'default_shipping_fee', 'free_shipping_threshold',
                'order_timeout_minutes', 'auto_receive_days',
                'after_sale_days', 'min_withdraw_amount',
            ];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $config->updateConfig($f, trim((string)$_POST[$f]));
                }
            }
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '交易设置保存成功'], JSON_UNESCAPED_UNICODE);
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
$pageTitle = '交易设置';
$config = SystemConfig::getInstance();
$systemStatus = $config->getSystemStatus();

// 读取当前配置
$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($rows as $row) {
        $configs[$row['config_key']] = $row['config_value'];
    }
} catch (Exception $e) {
    $configs = [];
}

// 合并默认值（保证表单始终有值）
foreach ($defaultConfig as $k => $v) {
    if (!isset($configs[$k]) || $configs[$k] === '') {
        $configs[$k] = $v;
    }
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
        .form-group label .required { color: #ff4d4f; margin-right: 4px; }
        .form-group input[type="text"], .form-group input[type="number"] { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; }
        .form-group input:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1); }
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
                    <div class="section-icon">💰</div>
                    <div class="section-info">
                        <div class="section-title">交易设置</div>
                        <div class="section-desc">配置订单交易相关的默认参数，包括运费、超时、自动收货等</div>
                    </div>
                </div>
                
                <div class="alert alert-info">💡 这里配置的是系统级别的交易默认值，下单、支付、退款等流程会自动应用这些规则。</div>
                
                <form id="tradeForm">
                    <h3 style="font-size: 15px; margin-bottom: 20px; color: #262626; font-weight: 600;">📦 运费设置</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>默认运费（元）</label>
                            <input type="number" name="default_shipping_fee" id="default_shipping_fee" step="0.01" min="0" value="<?= htmlspecialchars($configs['default_shipping_fee']) ?>" placeholder="10.00">
                            <div class="hint">订单未达免邮门槛时收取的默认运费</div>
                        </div>
                        <div class="form-group">
                            <label>免运费门槛（元）</label>
                            <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" step="0.01" min="0" value="<?= htmlspecialchars($configs['free_shipping_threshold']) ?>" placeholder="99.00">
                            <div class="hint">订单金额达到此值时免运费，0 表示不免运费</div>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">⏰ 订单时效</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>订单超时（分钟）</label>
                            <input type="number" name="order_timeout_minutes" id="order_timeout_minutes" min="1" value="<?= htmlspecialchars($configs['order_timeout_minutes']) ?>" placeholder="30">
                            <div class="hint">订单创建后多少分钟内未支付自动取消</div>
                        </div>
                        <div class="form-group">
                            <label>自动收货天数</label>
                            <input type="number" name="auto_receive_days" id="auto_receive_days" min="1" value="<?= htmlspecialchars($configs['auto_receive_days']) ?>" placeholder="15">
                            <div class="hint">发货后多少天用户未确认收货，系统自动确认收货</div>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 15px; margin: 32px 0 20px; color: #262626; font-weight: 600;">🛡️ 售后与提现</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>售后申请期限（天）</label>
                            <input type="number" name="after_sale_days" id="after_sale_days" min="0" value="<?= htmlspecialchars($configs['after_sale_days']) ?>" placeholder="7">
                            <div class="hint">订单完成后多少天内允许发起退款/售后申请</div>
                        </div>
                        <div class="form-group">
                            <label>最小提现金额（元）</label>
                            <input type="number" name="min_withdraw_amount" id="min_withdraw_amount" step="0.01" min="0" value="<?= htmlspecialchars($configs['min_withdraw_amount']) ?>" placeholder="10.00">
                            <div class="hint">用户申请提现的最低金额门槛</div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <button type="submit" class="btn btn-primary" id="saveBtn">💾 保存配置</button>
                        <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 恢复默认</button>
                    </div>
                </form>
                
                <div class="alert alert-success" style="margin-top: 24px;">
                    <strong>📌 提示：</strong>修改后点击保存即可立即生效，下次下单时自动使用新规则。
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
    
    document.getElementById('tradeForm').addEventListener('submit', function(e) {
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
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            } catch (err) {
                showMessage('❌ 响应解析失败', 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function resetConfig() {
        if (!confirm('确定要恢复默认配置吗？当前值将被覆盖。')) return;
        const formData = new FormData();
        formData.append('action', 'reset');
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.data) {
                    Object.keys(data.data).forEach(k => {
                        const el = document.getElementById(k);
                        if (el) el.value = data.data[k];
                    });
                }
                showMessage('🔄 已恢复默认配置', 'success');
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }
    </script>
</body>
</html>