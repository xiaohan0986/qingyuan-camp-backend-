<?php
/**
 * 其他配置 - 域名配置 + OSS 配置
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

// 处理 POST 请求（在 Auth 检查之前）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 开始新的缓冲，确保捕获所有输出
    ob_start();
    
    // 检查登录状态
    Auth::initSession();
    if (!isset($_SESSION['admin_id'])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未登录或登录已过期'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $config = SystemConfig::getInstance();
        
        if ($_POST['action'] === 'auto_detect') {
            $result = $config->autoDetectDomain();
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'save_config') {
            $domain = trim($_POST['domain'] ?? '');
            $protocol = $_POST['protocol'] ?? 'http';
            $siteName = trim($_POST['site_name'] ?? '');
            
            if (empty($domain)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => '域名不能为空'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $config->updateConfig('site_domain', $domain);
            $config->updateConfig('site_protocol', $protocol);
            $config->updateConfig('site_name', $siteName);
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '配置保存成功'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'save_oss') {
            $ossEnabled = isset($_POST['oss_enabled']) ? 1 : 0;
            $ossEndpoint = trim($_POST['oss_endpoint'] ?? '');
            $ossBucket = trim($_POST['oss_bucket'] ?? '');
            $ossAccessKeyId = trim($_POST['oss_access_key_id'] ?? '');
            $ossAccessKeySecret = trim($_POST['oss_access_key_secret'] ?? '');
            $ossCname = trim($_POST['oss_cname'] ?? '');
            $ossUploadDir = trim($_POST['oss_upload_dir'] ?? '');
            
            $config->updateConfig('oss_enabled', $ossEnabled);
            $config->updateConfig('oss_endpoint', $ossEndpoint);
            $config->updateConfig('oss_bucket', $ossBucket);
            $config->updateConfig('oss_access_key_id', $ossAccessKeyId);
            $config->updateConfig('oss_access_key_secret', $ossAccessKeySecret);
            $config->updateConfig('oss_cname', $ossCname);
            $config->updateConfig('oss_upload_dir', $ossUploadDir);
            
            $testResult = null;
            if ($ossEnabled && !empty($ossAccessKeyId) && !empty($ossAccessKeySecret)) {
                try {
                    require_once __DIR__ . '/../../config/oss.php';
                    require_once __DIR__ . '/../../includes/SimpleOSSClient.php';
                    // 使用单例模式获取实例
                    $testClient = SimpleOSSClient::getInstance();
                    $testResult = $testClient->isAvailable() ? 'success' : 'error';
                } catch (Exception $e) {
                    $testResult = 'error';
                }
            }
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => '对象存储配置保存成功',
                'test_result' => $testResult
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未知操作'], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '服务器错误'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// GET 请求 - 检查登录并显示页面
Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '其他配置';
$config = SystemConfig::getInstance();
$systemStatus = $config->getSystemStatus();
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
        .section-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #f5f5f5; cursor: pointer; transition: all 0.3s ease; }
        .section-header:hover { opacity: 0.9; transform: translateX(4px); }
        .section-icon { font-size: 28px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%); border-radius: 14px; color: white; flex-shrink: 0; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .section-info { flex: 1; }
        .section-title { font-size: 18px; font-weight: 600; color: #262626; margin-bottom: 4px; }
        .section-desc { font-size: 13px; color: #8c8c8c; line-height: 1.5; }
        .section-toggle { font-size: 18px; color: #bfbfbf; transition: transform 0.3s ease; width: 32px; text-align: center; }
        .section-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
        .section-content.expanded { max-height: 5000px; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .status-card { background: linear-gradient(135deg, #fafafa 0%, #fff 100%); border-radius: 12px; padding: 20px; border: 1px solid #f0f0f0; transition: all 0.3s ease; }
        .status-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .status-card .label { font-size: 13px; color: #8c8c8c; margin-bottom: 10px; font-weight: 500; }
        .status-card .value { font-size: 16px; color: #262626; font-weight: 600; word-break: break-all; margin-bottom: 12px; }
        .status-card .status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; }
        .status-card .status.normal { background: #f6ffed; color: #52c41a; }
        .status-card .status.error { background: #fff2f0; color: #ff4d4f; }
        .status-card .status.online { background: #e6f7ff; color: #1890ff; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-size: 14px; color: #262626; font-weight: 500; margin-bottom: 10px; }
        .form-group input[type="text"], .form-group input[type="password"], .form-group select { width: 100%; padding: 12px 16px; font-size: 14px; border: 1px solid #d9d9d9; border-radius: 8px; transition: all 0.3s; background: #fafafa; }
        .form-group input:focus, .form-group select:focus { border-color: #1890ff; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1); }
        .form-group .hint { font-size: 12px; color: #8c8c8c; margin-top: 8px; line-height: 1.5; }
        .btn { padding: 12px 24px; font-size: 14px; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%); color: white; box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(24, 144, 255, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-default { background: #f5f5f5; color: #262626; border: 1px solid #d9d9d9; }
        .btn-default:hover { background: #fafafa; border-color: #d9d9d9; }
        .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.6; }
        .alert-info { background: linear-gradient(135deg, #e6f7ff 0%, #f0f5ff 100%); color: #0050b3; border: 1px solid #bae7ff; }
        .alert-success { background: linear-gradient(135deg, #f6ffed 0%, #f9f0ff 100%); color: #237804; border: 1px solid #b7eb8f; }
        .alert-warning { background: linear-gradient(135deg, #fffbe6 0%, #fff7e6 100%); color: #ad6800; border: 1px solid #ffe58f; }
        .path-list { background: #fafafa; border-radius: 10px; padding: 16px; margin-top: 16px; }
        .path-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .path-item:last-child { border-bottom: none; }
        .path-label { font-size: 13px; color: #595959; font-weight: 500; }
        .path-value { font-size: 13px; color: #1890ff; font-family: 'Courier New', monospace; }
        input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #1890ff; }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- 域名配置 -->
            <div class="config-section" id="section-domain">
                <div class="section-header" onclick="toggleSection('section-domain')">
                    <div class="section-icon">⚙️</div>
                    <div class="section-info">
                        <div class="section-title">域名配置</div>
                        <div class="section-desc">自动识别当前域名、服务器、数据库运行状态</div>
                    </div>
                    <div class="section-toggle" id="toggle-section-domain">▼</div>
                </div>
                
                <div class="section-content expanded" id="content-section-domain">
                    <div class="alert alert-info">💡 系统会自动识别当前访问的域名、服务器和数据库状态，您可以手动修改配置。</div>
                    
                    <h3 style="font-size: 16px; margin-bottom: 16px; color: #262626;">📊 系统状态</h3>
                    <div class="status-grid">
                        <div class="status-card">
                            <div class="label">当前域名</div>
                            <div class="value" id="display_domain"><?= htmlspecialchars($systemStatus['domain']) ?></div>
                            <div class="status <?= $systemStatus['domain'] ? 'normal' : 'error' ?>">
                                <span><?= $systemStatus['domain'] ? '✓' : '✗' ?></span>
                                <span><?= $systemStatus['domain'] ? '已识别' : '未识别' ?></span>
                            </div>
                        </div>
                        <div class="status-card">
                            <div class="label">协议类型</div>
                            <div class="value"><?= strtoupper($systemStatus['protocol']) ?></div>
                            <div class="status <?= $systemStatus['protocol'] === 'https' ? 'normal' : 'online' ?>">
                                <span><?= $systemStatus['protocol'] === 'https' ? '🔒' : '🌐' ?></span>
                                <span><?= $systemStatus['protocol'] === 'https' ? '安全连接' : '普通连接' ?></span>
                            </div>
                        </div>
                        <div class="status-card">
                            <div class="label">服务器 IP</div>
                            <div class="value"><?= htmlspecialchars($systemStatus['server_ip']) ?></div>
                            <div class="status normal"><span>✓</span><span>正常运行</span></div>
                        </div>
                        <div class="status-card">
                            <div class="label">数据库状态</div>
                            <div class="value"><?= $systemStatus['db_status'] === 'normal' ? '正常' : '异常' ?></div>
                            <div class="status <?= $systemStatus['db_status'] === 'normal' ? 'normal' : 'error' ?>">
                                <span><?= $systemStatus['db_status'] === 'normal' ? '✓' : '✗' ?></span>
                                <span><?= $systemStatus['db_status'] === 'normal' ? '连接正常' : '连接异常' ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 16px; margin-bottom: 16px; color: #262626;">⚙️ 域名配置</h3>
                    <form id="configForm">
                        <div class="form-group">
                            <label>网站名称</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($systemStatus['site_name']) ?>" placeholder="请输入网站名称">
                        </div>
                        <div class="form-group">
                            <label>访问协议</label>
                            <select name="protocol" id="protocol">
                                <option value="http" <?= $systemStatus['protocol'] === 'http' ? 'selected' : '' ?>>HTTP</option>
                                <option value="https" <?= $systemStatus['protocol'] === 'https' ? 'selected' : '' ?>>HTTPS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>网站域名</label>
                            <div class="input-with-addon">
                                <input type="text" name="domain" id="domain" value="<?= htmlspecialchars($systemStatus['domain']) ?>" placeholder="例如：shop.auba.cn">
                                <button type="button" class="btn btn-default" onclick="autoDetect()">🔍 自动识别</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">💾 保存配置</button>
                            <button type="button" class="btn btn-default" onclick="resetConfig()">🔄 重置</button>
                        </div>
                    </form>
                    
                    <h3 style="font-size: 16px; margin-bottom: 16px; color: #262626; margin-top: 32px;">📁 全局路径预览</h3>
                    <div class="alert alert-success">✅ 以下路径将自动应用到全站，涉及路径、域名的地方都会调用配置中的域名。</div>
                    <div class="path-list">
                        <div class="path-item">
                            <span class="path-label">网站首页</span>
                            <span class="path-value" id="path_site"><?= htmlspecialchars($config->getFullDomain()) ?></span>
                        </div>
                        <div class="path-item">
                            <span class="path-label">后台地址</span>
                            <span class="path-value" id="path_admin"><?= htmlspecialchars($config->getAdminPath()) ?></span>
                        </div>
                        <div class="path-item">
                            <span class="path-label">上传目录</span>
                            <span class="path-value" id="path_upload"><?= htmlspecialchars($config->getUploadPath()) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 对象存储配置 -->
            <div class="config-section" id="section-oss">
                <div class="section-header" onclick="toggleSection('section-oss')">
                    <div class="section-icon">☁️</div>
                    <div class="section-info">
                        <div class="section-title">对象存储配置（阿里云 OSS）</div>
                        <div class="section-desc">配置阿里云 OSS 对象存储，实现图片、视频等文件的云端存储和 CDN 加速</div>
                    </div>
                    <div class="section-toggle" id="toggle-section-oss">▼</div>
                </div>
                
                <div class="section-content <?= ($systemStatus['oss_enabled'] ?? 0) ? 'expanded' : '' ?>" id="content-section-oss">
                    <div class="alert alert-info">💡 启用对象存储后，商品图片、视频等文件将自动上传到阿里云 OSS，减轻服务器压力，支持 CDN 加速。</div>
                    
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; margin-bottom: 16px; color: #262626; font-weight: 600;">📊 OSS 状态</h3>
                        <div class="status-grid">
                            <div class="status-card">
                                <div class="label">启用状态</div>
                                <div class="value"><?= ($systemStatus['oss_enabled'] ?? 0) ? '已启用' : '未启用' ?></div>
                                <div class="status <?= ($systemStatus['oss_enabled'] ?? 0) ? 'normal' : 'error' ?>">
                                    <span><?= ($systemStatus['oss_enabled'] ?? 0) ? '✓' : '✗' ?></span>
                                    <span><?= ($systemStatus['oss_enabled'] ?? 0) ? '已启用' : '未启用' ?></span>
                                </div>
                            </div>
                            <div class="status-card">
                                <div class="label">Bucket</div>
                                <div class="value"><?= htmlspecialchars($systemStatus['oss_bucket'] ?? '未配置') ?></div>
                                <div class="status <?= !empty($systemStatus['oss_bucket']) ? 'normal' : 'error' ?>">
                                    <span><?= !empty($systemStatus['oss_bucket']) ? '✓' : '✗' ?></span>
                                    <span><?= !empty($systemStatus['oss_bucket']) ? '已配置' : '未配置' ?></span>
                                </div>
                            </div>
                            <div class="status-card">
                                <div class="label">Endpoint</div>
                                <div class="value"><?= htmlspecialchars($systemStatus['oss_endpoint'] ?? '未配置') ?></div>
                                <div class="status <?= !empty($systemStatus['oss_endpoint']) ? 'normal' : 'error' ?>">
                                    <span><?= !empty($systemStatus['oss_endpoint']) ? '✓' : '✗' ?></span>
                                    <span><?= !empty($systemStatus['oss_endpoint']) ? '已配置' : '未配置' ?></span>
                                </div>
                            </div>
                            <div class="status-card">
                                <div class="label">CDN 加速</div>
                                <div class="value"><?= !empty($systemStatus['oss_cname']) ? '已启用' : '未启用' ?></div>
                                <div class="status <?= !empty($systemStatus['oss_cname']) ? 'online' : 'error' ?>">
                                    <span><?= !empty($systemStatus['oss_cname']) ? '🚀' : '✗' ?></span>
                                    <span><?= !empty($systemStatus['oss_cname']) ? '已配置' : '未配置' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #f5f5f5;">
                        <h3 style="font-size: 16px; margin-bottom: 20px; color: #262626; font-weight: 600;">⚙️ OSS 配置</h3>
                        <form id="ossForm">
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="oss_enabled" id="oss_enabled" <?= ($systemStatus['oss_enabled'] ?? 0) ? 'checked' : '' ?>>
                                    <span>启用对象存储</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>OSS Endpoint <span style="color: #8c8c8c; font-weight: normal;">（如 oss-cn-hangzhou.aliyuncs.com）</span></label>
                                <input type="text" name="oss_endpoint" id="oss_endpoint" value="<?= htmlspecialchars($systemStatus['oss_endpoint'] ?? '') ?>" placeholder="根据你的区域选择，如 oss-cn-hangzhou.aliyuncs.com">
                                <div class="hint">常见 Endpoint：oss-cn-hangzhou.aliyuncs.com（杭州）、oss-cn-shanghai.aliyuncs.com（上海）、oss-cn-beijing.aliyuncs.com（北京）</div>
                            </div>
                            <div class="form-group">
                                <label>Bucket 名称</label>
                                <input type="text" name="oss_bucket" id="oss_bucket" value="<?= htmlspecialchars($systemStatus['oss_bucket'] ?? '') ?>" placeholder="你的 Bucket 名称">
                            </div>
                            <div class="form-group">
                                <label>AccessKey ID</label>
                                <input type="text" name="oss_access_key_id" id="oss_access_key_id" value="<?= htmlspecialchars($systemStatus['oss_access_key_id'] ?? '') ?>" placeholder="你的 AccessKey ID">
                                <div class="hint">建议使用 RAM 子账号的 AccessKey，不要使用主账号</div>
                            </div>
                            <div class="form-group">
                                <label>AccessKey Secret</label>
                                <input type="password" name="oss_access_key_secret" id="oss_access_key_secret" value="<?= htmlspecialchars($systemStatus['oss_access_key_secret'] ?? '') ?>" placeholder="你的 AccessKey Secret">
                                <div class="hint">AccessKey Secret 是敏感信息，请妥善保管</div>
                            </div>
                            <div class="form-group">
                                <label>CDN 加速域名（可选）</label>
                                <input type="text" name="oss_cname" id="oss_cname" value="<?= htmlspecialchars($systemStatus['oss_cname'] ?? '') ?>" placeholder="如 https://cdn.yourdomain.com">
                                <div class="hint">如果配置了 CDN 域名，返回的 URL 将使用此域名</div>
                            </div>
                            <div class="form-group">
                                <label>上传目录前缀（可选）</label>
                                <input type="text" name="oss_upload_dir" id="oss_upload_dir" value="<?= htmlspecialchars($systemStatus['oss_upload_dir'] ?? 'shopauba') ?>" placeholder="如 shopauba">
                                <div class="hint">OSS 中的根目录，避免和其他项目混在一起</div>
                            </div>
                            <div class="form-group" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">💾 保存配置</button>
                                <button type="button" class="btn btn-default" onclick="testOssConnection()">🔍 测试连接</button>
                                <button type="button" class="btn btn-default" onclick="resetOssConfig()">🔄 重置</button>
                            </div>
                        </form>
                        
                        <div id="ossTestResult" style="display: none; margin-top: 20px;"></div>
                        
                        <div class="alert alert-warning" style="margin-top: 32px;">
                            <strong>配置指南：</strong><br>
                            1. 登录 <a href="https://oss.console.aliyun.com/" target="_blank">阿里云 OSS 控制台</a><br>
                            2. 创建 Bucket 或选择现有 Bucket<br>
                            3. 在"基础设置"中获取 Endpoint<br>
                            4. 在"访问密钥"中创建 AccessKey（建议使用 RAM 子账号）<br>
                            5. 填写以上配置信息，点击"保存配置"<br>
                            6. 点击"测试连接"验证配置是否正确
                        </div>
                    </div>
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
        alert.style.minWidth = '200px';
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
    
    function toggleSection(sectionId) {
        const content = document.getElementById('content-' + sectionId);
        const toggle = document.getElementById('toggle-' + sectionId);
        if (content.classList.contains('expanded')) {
            content.classList.remove('expanded');
            toggle.textContent = '▶';
            localStorage.setItem(sectionId + '_collapsed', '1');
        } else {
            content.classList.add('expanded');
            toggle.textContent = '▼';
            localStorage.setItem(sectionId + '_collapsed', '0');
        }
    }
    
    function autoDetect() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = '🔍 识别中...';
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=auto_detect'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('domain').value = data.data.domain;
                document.getElementById('protocol').value = data.data.protocol;
                document.getElementById('display_domain').textContent = data.data.domain;
                showMessage('✅ 自动识别成功！', 'success');
            } else {
                showMessage('❌ 识别失败', 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '🔍 自动识别';
        });
    }
    
    document.getElementById('configForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_config');
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                location.reload();
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    });
    
    function resetConfig() {
        if (confirm('确定要重置配置吗？')) {
            document.getElementById('configForm').reset();
            showMessage('🔄 配置已重置', 'info');
        }
    }
    
    document.getElementById('ossForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'save_oss');
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = '💾 保存中...';
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    if (data.test_result === 'success') {
                        showOssTestResult('✅ 连接测试成功！OSS 配置可用。', 'success');
                    } else if (data.test_result === 'error') {
                        showOssTestResult('⚠️ 配置已保存，但连接测试失败。', 'warning');
                    }
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            } catch (parseErr) {
                showMessage('❌ 非 JSON 响应：<br><pre style="font-size:12px;">' + text.substring(0, 300) + '</pre>', 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = '💾 保存配置';
        });
    });
    
    function testOssConnection() {
        const formData = new FormData(document.getElementById('ossForm'));
        formData.append('action', 'save_oss');
        const testBtn = event.target;
        testBtn.disabled = true;
        testBtn.textContent = '🔍 测试中...';
        fetch('', { method: 'POST', body: new URLSearchParams(formData) })
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    if (data.test_result === 'success') {
                        showOssTestResult('✅ 连接测试成功！OSS 配置可用。', 'success');
                    } else if (data.test_result === 'error') {
                        showOssTestResult('❌ 连接测试失败。请检查配置。', 'error');
                    } else {
                        showOssTestResult('⚠️ OSS 未启用或配置不完整。', 'warning');
                    }
                } else {
                    showOssTestResult('❌ 测试失败：' + data.message, 'error');
                }
            } catch (parseErr) {
                showOssTestResult('❌ 非 JSON 响应：<br><pre style="font-size:12px;">' + text.substring(0, 300) + '</pre>', 'error');
            }
        })
        .catch(err => showOssTestResult('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            testBtn.disabled = false;
            testBtn.textContent = '🔍 测试连接';
        });
    }
    
    function showOssTestResult(message, type) {
        const resultDiv = document.getElementById('ossTestResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'alert alert-' + type;
        resultDiv.innerHTML = message;
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function resetOssConfig() {
        if (confirm('确定要重置 OSS 配置吗？')) {
            document.getElementById('ossForm').reset();
            document.getElementById('ossTestResult').style.display = 'none';
            showMessage('🔄 配置已重置', 'info');
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const domainCollapsed = localStorage.getItem('section-domain_collapsed') === '1';
        if (domainCollapsed) toggleSection('section-domain');
        const ossCollapsed = localStorage.getItem('section-oss_collapsed') === '1';
        if (ossCollapsed) toggleSection('section-oss');
    });
    </script>
</body>
</html>
