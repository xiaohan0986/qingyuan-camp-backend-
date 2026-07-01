<?php
/**
 * system_other.php - 最简调试版
 * 只保留 save_oss 功能
 */

// 清空输出缓冲
while (ob_get_level()) {
    ob_end_clean();
}

// 引入文件
require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/config/config.php';
require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/includes/Database.php';
require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/includes/Auth.php';
require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/includes/SystemConfig.php';

// 处理 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_oss') {
    ob_start();
    
    // 检查登录
    Auth::initSession();
    if (!isset($_SESSION['admin_id'])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '未登录']);
        exit;
    }
    
    try {
        $config = SystemConfig::getInstance();
        
        // 保存配置
        $config->updateConfig('oss_enabled', isset($_POST['oss_enabled']) ? 1 : 0);
        $config->updateConfig('oss_endpoint', trim($_POST['oss_endpoint'] ?? ''));
        $config->updateConfig('oss_bucket', trim($_POST['oss_bucket'] ?? ''));
        $config->updateConfig('oss_access_key_id', trim($_POST['oss_access_key_id'] ?? ''));
        $config->updateConfig('oss_access_key_secret', trim($_POST['oss_access_key_secret'] ?? ''));
        $config->updateConfig('oss_cname', trim($_POST['oss_cname'] ?? ''));
        $config->updateConfig('oss_upload_dir', trim($_POST['oss_upload_dir'] ?? ''));
        
        // 测试 OSS
        $testResult = null;
        if (!empty($_POST['oss_access_key_id']) && !empty($_POST['oss_access_key_secret'])) {
            require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/config/oss.php';
            require_once 'D:/phpstudy_pro/WWW/shop.auba.cn/includes/SimpleOSSClient.php';
            $testClient = SimpleOSSClient::getInstance();
            $testResult = $testClient->isAvailable() ? 'success' : 'error';
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => '保存成功',
            'test_result' => $testResult
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '错误：' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// GET 请求
echo "<h1>最简测试版</h1>";
echo "<p>页面加载成功！</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='save_oss'>";
echo "<input type='text' name='oss_access_key_id' placeholder='Access Key ID' value='test'><br><br>";
echo "<input type='text' name='oss_access_key_secret' placeholder='Access Key Secret' value='test'><br><br>";
echo "<button type='submit'>测试保存</button>";
echo "</form>";
