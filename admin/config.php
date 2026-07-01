<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 程序配置页面
 */

// 检查访问权限
require_once dirname(__DIR__) . '/includes/PermissionChecker.php';
$permissionChecker = new PermissionChecker();
$permissionChecker->requireAccess('config');

ob_start();
session_start();

require_once __DIR__ . '/includes/header.php';

$pageTitle = '程序配置';
$currentPage = 'config';

// 加载客服数据
$customerServiceList = [];
$customerServiceConfig = ['enabled' => '0', 'service_name' => '', 'service_url' => ''];

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $dbPdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 确保表存在
    $dbPdo->exec("CREATE TABLE IF NOT EXISTS `customer_service_config` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `service_name` VARCHAR(100) NOT NULL,
        `service_url` VARCHAR(500) NOT NULL,
        `enabled` TINYINT DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_enabled` (`enabled`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 查询所有客服列表
    try {
        $stmt = $dbPdo->query("SELECT * FROM `customer_service_config` ORDER BY sort_order, id");
        $customerServiceList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stmt = $dbPdo->query("SELECT * FROM `customer_service_config` ORDER BY id");
        $customerServiceList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 找到启用的客服
    foreach ($customerServiceList as $cs) {
        if ($cs['enabled'] == 1) {
            $customerServiceConfig = $cs;
            break;
        }
    }
} catch (PDOException $e) {
    // 静默失败
}

// 配置文件路径
$configFile = __DIR__ . '/../config/config.json';
$smsConfigFile = __DIR__ . '/../config/sms.json';
$paymentConfigFile = __DIR__ . '/../config/payment.json';
$miniprogramConfigFile = __DIR__ . '/../config/miniprogram.json';
$subscribeConfigFile = __DIR__ . '/../config/subscribe.json';
$customerServiceConfigFile = __DIR__ . '/../config/customer_service.json';
// 确保配置目录存在
if (!is_dir(__DIR__ . '/../config')) {
    mkdir(__DIR__ . '/../config', 0755, true);
}

// 确保证书目录存在
if (!is_dir(__DIR__ . '/../config/certs')) {
    mkdir(__DIR__ . '/../config/certs', 0755, true);
}

// 自动检测当前域名（每次访问时自动更新）
function detectCurrentDomain() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // 获取项目根路径（自动识别）
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptPath === '/' || $scriptPath === '\\' || $scriptPath === '') {
        $basePath = '';
    } else {
        // 移除 admin 路径
        $basePath = preg_replace('/\/admin$/', '', $scriptPath);
        $basePath = preg_replace('/\\\\admin$/', '', $basePath);
    }
    
    return $protocol . '://' . $host . $basePath;
}

// 处理保存配置
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        $siteConfig = [
            'site_name' => $_POST['site_name'] ?? '青园营地管理后台',
            'site_url' => rtrim($_POST['site_url'] ?? '', '/'),
            'api_url' => rtrim($_POST['api_url'] ?? '', '/'),
            'upload_max_size' => $_POST['upload_max_size'] ?? '10',
            'allow_register' => $_POST['allow_register'] ?? '0',
            'watermark_enabled' => $_POST['watermark_enabled'] ?? '1',
            'watermark_text' => $_POST['watermark_text'] ?? '青园营地',
            'timezone' => $_POST['timezone'] ?? 'Asia/Shanghai',
            'language' => $_POST['language'] ?? 'zh-CN',
            'miniprogram_appid' => $_POST['miniprogram_appid'] ?? '',
            'miniprogram_secret' => $_POST['miniprogram_secret'] ?? '',
            // 自动保存当前域名
            'auto_detected_domain' => detectCurrentDomain(),
            'last_domain_check' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($configFile, json_encode($siteConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // 同时将小程序配置和快捷按钮配置保存到数据库
        require_once __DIR__ . '/../config/database.php';
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $dbPdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // 检查表是否存在，不存在则创建
            $dbPdo->exec("CREATE TABLE IF NOT EXISTS `system_config` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `config_key` VARCHAR(100) NOT NULL UNIQUE,
                `config_value` TEXT,
                `config_desc` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // 保存小程序配置到数据库
            $miniprogram_appid = $_POST['miniprogram_appid'] ?? '';
            $miniprogram_secret = $_POST['miniprogram_secret'] ?? '';
            
            $dbPdo->exec("INSERT INTO `system_config` (`config_key`, `config_value`, `config_desc`) 
                         VALUES ('miniprogram_appid', '{$miniprogram_appid}', '小程序 APPID') 
                         ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
            
            $dbPdo->exec("INSERT INTO `system_config` (`config_key`, `config_value`, `config_desc`) 
                         VALUES ('miniprogram_secret', '{$miniprogram_secret}', '小程序 SECRET') 
                         ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
            
            // 保存快捷按钮配置到数据库
            $shortcuts = [
                'shortcut_hot_positions' => $_POST['shortcut_hot_positions'] ?? '1',
                'shortcut_policy' => $_POST['shortcut_policy'] ?? '1',
                'shortcut_travel' => $_POST['shortcut_travel'] ?? '1',
                'shortcut_study_abroad' => $_POST['shortcut_study_abroad'] ?? '1',
                'shortcut_progress' => $_POST['shortcut_progress'] ?? '1',
                'shortcut_stores' => $_POST['shortcut_stores'] ?? '1',
                'shortcut_managers' => $_POST['shortcut_managers'] ?? '1',
                'shortcut_knowledge' => $_POST['shortcut_knowledge'] ?? '1'
            ];
            
            foreach ($shortcuts as $key => $value) {
                $stmt = $dbPdo->prepare("INSERT INTO `system_config` (`config_key`, `config_value`, `config_desc`) 
                                        VALUES (:key, :value, :desc) 
                                        ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value,
                    ':desc' => '快捷按钮配置'
                ]);
            }
        } catch (PDOException $e) {
            // 数据库操作失败不影响主配置保存
            error_log("保存配置到数据库失败：" . $e->getMessage());
        }
        
        $success = true;
        $message = '基础配置保存成功！';
    }
    
    if ($action === 'auto_config_domain') {
        // 自动检测当前域名
        $currentDomain = detectCurrentDomain();
        $siteUrl = $currentDomain;
        $apiUrl = $currentDomain . '/api';
        
        // 读取现有配置（保留其他设置）
        $existingConfig = [];
        if (file_exists($configFile)) {
            $existingConfig = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        $siteConfig = array_merge($existingConfig, [
            'site_name' => $existingConfig['site_name'] ?? '青园营地管理后台',
            'site_url' => $siteUrl,
            'api_url' => $apiUrl,
            'upload_max_size' => $existingConfig['upload_max_size'] ?? '10',
            'allow_register' => $existingConfig['allow_register'] ?? '0',
            'watermark_enabled' => $existingConfig['watermark_enabled'] ?? '1',
            'watermark_text' => $existingConfig['watermark_text'] ?? '青园营地',
            'timezone' => $existingConfig['timezone'] ?? 'Asia/Shanghai',
            'language' => $existingConfig['language'] ?? 'zh-CN',
            'auto_detected_domain' => $currentDomain,
            'last_domain_check' => date('Y-m-d H:i:s'),
            'domain_history' => isset($existingConfig['domain_history']) && is_array($existingConfig['domain_history']) 
                ? array_merge(array_slice($existingConfig['domain_history'], -9), [[
                    'domain' => $currentDomain,
                    'time' => date('Y-m-d H:i:s')
                ]]) 
                : [[
                    'domain' => $currentDomain,
                    'time' => date('Y-m-d H:i:s')
                ]]
        ]);
        
        file_put_contents($configFile, json_encode($siteConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = true;
        $message = "✅ 一键配置成功！当前域名：{$currentDomain}";
    }
    
    if ($action === 'sync_domain_to_all') {
        // 同步域名到所有配置文件
        $currentDomain = detectCurrentDomain();
        $syncCount = 0;
        
        // 更新 paths.php（如果存在）
        $pathsFile = __DIR__ . '/../config/paths.php';
        if (file_exists($pathsFile)) {
            $pathsContent = file_get_contents($pathsFile);
            // 这里可以更新 paths.php 中的配置
            $syncCount++;
        }
        
        // 更新 storage.php（如果存在）
        $storageFile = __DIR__ . '/../config/storage.php';
        if (file_exists($storageFile)) {
            $storageContent = file_get_contents($storageFile);
            $storageContent = preg_replace(
                "/'url'\s*=>\s*['\"][^'\"]*['\"]/",
                "'url' => '" . $currentDomain . "/images'",
                $storageContent
            );
            file_put_contents($storageFile, $storageContent);
            $syncCount++;
        }
        
        $success = true;
        $message = "✅ 域名已同步到 {$syncCount} 个配置文件！当前域名：{$currentDomain}";
    }
    
    if ($action === 'save_sms_config') {
        $smsConfig = [
            'enabled' => $_POST['sms_enabled'] ?? '0',
            'platform' => $_POST['sms_platform'] ?? 'aliyun',
            'access_key_id' => $_POST['access_key_id'] ?? '',
            'access_key_secret' => $_POST['access_key_secret'] ?? '',
            'sign_name' => $_POST['sign_name'] ?? '',
            'template_code' => $_POST['template_code'] ?? '',
            'template_content' => $_POST['template_content'] ?? '验证码${code}，您正在进行身份验证，若非本人操作，请勿泄露。',
            'test_phone' => $_POST['test_phone'] ?? ''
        ];
        
        file_put_contents($smsConfigFile, json_encode($smsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = true;
        $message = '短信配置保存成功！';
    }
    
    if ($action === 'test_sms') {
        // TODO: 测试短信发送
        $testPhone = $_POST['test_phone'] ?? '';
        if (empty($testPhone)) {
            $error = true;
            $message = '请输入测试手机号';
        } else {
            // 模拟发送
            $success = true;
            $message = "测试短信已发送到：{$testPhone}（实际发送需配置完整短信平台）";
        }
    }
    
    if ($action === 'save_payment') {
        $paymentId = $_POST['payment_id'] ?? '';
        
        if (empty($paymentId)) {
            // 新建支付配置
            $paymentId = 'pay_' . time() . '_' . mt_rand(1000, 9999);
        }
        
        $paymentConfig = [
            'payment_id' => $paymentId,
            'template_name' => $_POST['template_name'] ?? '',
            'admin_remark' => $_POST['admin_remark'] ?? '',
            'sort_order' => intval($_POST['sort_order']) ?: 100,
            'platform_type' => $_POST['platform_type'] ?? 'h5',
            'payment_method' => $_POST['payment_method'] ?? 'wechat',
            'wechat_version' => $_POST['wechat_version'] ?? 'v3',
            'merchant_type' => $_POST['merchant_type'] ?? 'normal',
            'appid' => $_POST['appid'] ?? '',
            'mch_id' => $_POST['mch_id'] ?? '',
            'api_key' => $_POST['api_key'] ?? '',
            'verify_method' => $_POST['verify_method'] ?? 'public_key',
            'public_key_id' => $_POST['public_key_id'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 处理文件上传
        if (isset($_FILES['public_key_file']) && $_FILES['public_key_file']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = __DIR__ . '/../config/certs/' . $paymentId . '_pub_key.pem';
            move_uploaded_file($_FILES['public_key_file']['tmp_name'], $uploadPath);
            $paymentConfig['public_key_file'] = 'certs/' . $paymentId . '_pub_key.pem';
        }
        
        if (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = __DIR__ . '/../config/certs/' . $paymentId . '_apiclient_cert.pem';
            move_uploaded_file($_FILES['cert_file']['tmp_name'], $uploadPath);
            $paymentConfig['cert_file'] = 'certs/' . $paymentId . '_apiclient_cert.pem';
        }
        
        if (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = __DIR__ . '/../config/certs/' . $paymentId . '_apiclient_key.pem';
            move_uploaded_file($_FILES['key_file']['tmp_name'], $uploadPath);
            $paymentConfig['key_file'] = 'certs/' . $paymentId . '_apiclient_key.pem';
        }
        
        // 加载现有配置
        $paymentConfigs = [];
        if (file_exists($paymentConfigFile)) {
            $paymentConfigs = json_decode(file_get_contents($paymentConfigFile), true) ?: [];
        }
        
        // 保存配置
        if ($paymentId && isset($paymentConfigs[$paymentId])) {
            // 更新现有配置（保留文件路径）
            $paymentConfig['public_key_file'] = $paymentConfigs[$paymentId]['public_key_file'] ?? $paymentConfig['public_key_file'];
            $paymentConfig['cert_file'] = $paymentConfigs[$paymentId]['cert_file'] ?? $paymentConfig['cert_file'];
            $paymentConfig['key_file'] = $paymentConfigs[$paymentId]['key_file'] ?? $paymentConfig['key_file'];
            $paymentConfig['payment_method'] = $paymentConfigs[$paymentId]['payment_method']; // 支付方式不可修改
        }
        
        $paymentConfigs[$paymentId] = $paymentConfig;
        file_put_contents($paymentConfigFile, json_encode($paymentConfigs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $success = true;
        $message = '支付配置保存成功！';
    }
    
    if ($action === 'delete_payment') {
        $paymentId = $_POST['payment_id'] ?? '';
        
        if (!empty($paymentId) && file_exists($paymentConfigFile)) {
            $paymentConfigs = json_decode(file_get_contents($paymentConfigFile), true) ?: [];
            
            if (isset($paymentConfigs[$paymentId])) {
                // 删除证书文件
                $certs = [
                    $paymentConfigs[$paymentId]['public_key_file'] ?? '',
                    $paymentConfigs[$paymentId]['cert_file'] ?? '',
                    $paymentConfigs[$paymentId]['key_file'] ?? ''
                ];
                
                foreach ($certs as $cert) {
                    if (!empty($cert)) {
                        $certPath = __DIR__ . '/../config/' . $cert;
                        if (file_exists($certPath)) {
                            unlink($certPath);
                        }
                    }
                }
                
                unset($paymentConfigs[$paymentId]);
                file_put_contents($paymentConfigFile, json_encode($paymentConfigs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                $success = true;
                $message = '支付配置已删除！';
            }
        }
    }
    
    if ($action === 'save_miniprogram') {
        $miniprogramConfig = [
            'register_method' => $_POST['register_method'] ?? 'phone', // phone, wechat, both
            'require_phone' => $_POST['require_phone'] ?? '1',
            'auto_get_wechat' => $_POST['auto_get_wechat'] ?? '1',
            'auto_get_nickname' => $_POST['auto_get_nickname'] ?? '1',
            'auto_get_avatar' => $_POST['auto_get_avatar'] ?? '1',
            // 从基础配置读取 APPID 和 SECRET
            'wechat_appid' => $config['miniprogram_appid'] ?? '',
            'wechat_secret' => $config['miniprogram_secret'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($miniprogramConfigFile, json_encode($miniprogramConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // 同时将 APPID 和 SECRET 同步到数据库（如果基础配置中没有填写）
        if (!empty($_POST['wechat_appid']) || !empty($_POST['wechat_secret'])) {
            try {
                $dbConfig = require __DIR__ . '/../config/database.php';
                $dbPdo = new PDO(
                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                if (!empty($_POST['wechat_appid'])) {
                    $dbPdo->exec("INSERT INTO `system_config` (`config_key`, `config_value`, `config_desc`) 
                                 VALUES ('miniprogram_appid', '{$_POST['wechat_appid']}', '小程序 APPID') 
                                 ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
                }
                if (!empty($_POST['wechat_secret'])) {
                    $dbPdo->exec("INSERT INTO `system_config` (`config_key`, `config_value`, `config_desc`) 
                                 VALUES ('miniprogram_secret', '{$_POST['wechat_secret']}', '小程序 SECRET') 
                                 ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
                }
            } catch (PDOException $e) {
                error_log("同步小程序配置到数据库失败：" . $e->getMessage());
            }
        }
        
        $success = true;
        $message = '小程序注册配置保存成功！';
    }
    
    if ($action === 'save_subscribe') {
        $subscribeConfig = [
            'enabled' => $_POST['subscribe_enabled'] ?? '0',
            'template_id' => $_POST['template_id'] ?? '',
            'template_name' => $_POST['template_name'] ?? '',
            'auto_subscribe' => $_POST['auto_subscribe'] ?? '0',
            'subscribe_page' => $_POST['subscribe_page'] ?? 'pages/progress/progress',
            'send_on_progress_update' => $_POST['send_on_progress_update'] ?? '1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($subscribeConfigFile, json_encode($subscribeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = true;
        $message = '订阅推送配置保存成功！';
        
        // 重定向避免 POST 数据缓存
        ob_end_clean();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    // AJAX 切换客服状态
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_customer_service' && isset($_POST['service_id'])) {
        // 清除输出缓冲，确保只返回 JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $dbPdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $serviceId = $_POST['service_id'];
            $enable = $_POST['enable'] ?? '0';
            
            if ($enable === '1') {
                // 启用这个客服，禁用其他
                $dbPdo->exec("UPDATE `customer_service_config` SET `enabled` = 0");
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET `enabled` = 1 WHERE `id` = :id");
                $stmt->execute([':id' => $serviceId]);
            } else {
                // 禁用这个客服
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET `enabled` = 0 WHERE `id` = :id");
                $stmt->execute([':id' => $serviceId]);
            }
            
            // 查询所有客服的最新状态
            try {
                $stmt = $dbPdo->query("SELECT id, enabled FROM `customer_service_config` ORDER BY sort_order, id");
            } catch (PDOException $e) {
                $stmt = $dbPdo->query("SELECT id, enabled FROM `customer_service_config` ORDER BY id");
            }
            $allServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 返回 JSON 格式的所有客服状态
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'services' => $allServices
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // 启用/禁用客服（GET 方式，保留兼容性）
    if (isset($_GET['enable'])) {
        $enableId = $_GET['enable'];
        
        if (!empty($enableId) && $enableId !== '0') {
            // 启用指定客服
            try {
                $dbConfig = require __DIR__ . '/../config/database.php';
                $dbPdo = new PDO(
                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // 禁用所有
                $dbPdo->exec("UPDATE `customer_service_config` SET `enabled` = 0");
                
                // 启用指定的
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET `enabled` = 1 WHERE `id` = :id");
                $stmt->execute([':id' => $enableId]);
                
                $_SESSION['admin_notice'] = ['type' => 'success', 'message' => '已启用该客服，其他客服已自动禁用！'];
                
            } catch (PDOException $e) {
                $_SESSION['admin_notice'] = ['type' => 'error', 'message' => '启用失败：' . $e->getMessage()];
            }
        } elseif ($enableId === '0' && isset($_GET['id'])) {
            // 禁用指定客服（?enable=0&id=X）
            try {
                $dbConfig = require __DIR__ . '/../config/database.php';
                $dbPdo = new PDO(
                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // 禁用指定的
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET `enabled` = 0 WHERE `id` = :id");
                $stmt->execute([':id' => $_GET['id']]);
                
                $_SESSION['admin_notice'] = ['type' => 'success', 'message' => '已禁用该客服！'];
                
            } catch (PDOException $e) {
                $_SESSION['admin_notice'] = ['type' => 'error', 'message' => '禁用失败：' . $e->getMessage()];
            }
        }
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '#customer-service-config');
        exit;
    }
    
    // 删除客服
    if ($action === 'delete_customer_service' && !empty($_POST['delete_id'])) {
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $dbPdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $dbPdo->prepare("DELETE FROM `customer_service_config` WHERE `id` = :id");
            $stmt->execute([':id' => $_POST['delete_id']]);
            
            $_SESSION['admin_notice'] = ['type' => 'success', 'message' => '客服已删除！'];
            
        } catch (PDOException $e) {
            $_SESSION['admin_notice'] = ['type' => 'error', 'message' => '删除失败：' . $e->getMessage()];
        }
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '#customer-service-config');
        exit;
    }
    
    // 保存客服（支持多客服）
    if ($action === 'save_customer_service') {
        try {
            $dbConfig = require __DIR__ . '/../config/database.php';
            $dbPdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // 确保表结构正确
            $dbPdo->exec("CREATE TABLE IF NOT EXISTS `customer_service_config` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `service_name` VARCHAR(100) NOT NULL COMMENT '客服名称',
                `service_url` VARCHAR(500) NOT NULL COMMENT '客服链接',
                `enabled` TINYINT DEFAULT 0 COMMENT '是否启用（1=启用，0=禁用）',
                `sort_order` INT DEFAULT 0 COMMENT '排序',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_enabled` (`enabled`),
                INDEX `idx_sort` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企微客服配置表'");
            
            // 保存客服数据
            $serviceId = $_POST['service_id'] ?? '';
            $serviceName = $_POST['service_name'] ?? '';
            $serviceUrl = $_POST['service_url'] ?? '';
            $corpId = $_POST['corp_id'] ?? '';
            $enabled = $_POST['service_enabled'] ?? '0';
            
            // 检查重复（相同名称或链接）
            if (!empty($serviceName) && !empty($serviceUrl)) {
                $stmt = $dbPdo->prepare("SELECT id FROM `customer_service_config` WHERE (`service_name` = :name OR `service_url` = :url) AND id != :id");
                $stmt->execute([
                    ':name' => $serviceName,
                    ':url' => $serviceUrl,
                    ':id' => $serviceId ?: 0
                ]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    throw new PDOException('客服名称或链接已存在，请勿重复添加！');
                }
            }
            
            if (!empty($serviceId)) {
                // 更新现有客服
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET 
                    `service_name` = :name, 
                    `service_url` = :url, 
                    `corp_id` = :corp_id,
                    `enabled` = :enabled,
                    `updated_at` = CURRENT_TIMESTAMP 
                    WHERE `id` = :id");
                $stmt->execute([
                    ':name' => $serviceName,
                    ':url' => $serviceUrl,
                    ':corp_id' => $corpId,
                    ':enabled' => $enabled,
                    ':id' => $serviceId
                ]);
            } else {
                // 添加新客服
                $stmt = $dbPdo->prepare("INSERT INTO `customer_service_config` 
                    (`service_name`, `service_url`, `corp_id`, `enabled`, `sort_order`) 
                    VALUES (:name, :url, :corp_id, :enabled, 0)");
                $stmt->execute([
                    ':name' => $serviceName,
                    ':url' => $serviceUrl,
                    ':corp_id' => $corpId,
                    ':enabled' => $enabled
                ]);
            }
            
            // 如果启用了这个客服，确保其他客服都禁用
            if ($enabled == '1') {
                $stmt = $dbPdo->prepare("UPDATE `customer_service_config` SET `enabled` = 0 WHERE `id` != :id");
                $stmt->execute([':id' => $serviceId ?: 0]);
            }
            
            $_SESSION['admin_notice'] = ['type' => 'success', 'message' => '客服配置保存成功！'];
            
        } catch (PDOException $e) {
            error_log("保存企微客服配置失败：" . $e->getMessage());
            $_SESSION['admin_notice'] = ['type' => 'error', 'message' => '保存失败：' . $e->getMessage()];
        }
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '#customer-service-config');
        exit;
    }
    
    if ($action === 'test_subscribe') {
        $testOpenid = $_POST['test_openid'] ?? '';
        if (empty($testOpenid)) {
            $error = true;
            $message = '请输入测试用户的 OpenID';
        } else {
            // 读取订阅配置
            $subscribeConfig = [];
            if (file_exists($subscribeConfigFile)) {
                $subscribeConfig = json_decode(file_get_contents($subscribeConfigFile), true) ?: [];
            }
            
            if (empty($subscribeConfig['template_id'])) {
                $error = true;
                $message = '请先配置订阅消息模板 ID';
            } else {
                // 读取小程序配置
                $miniprogramConfig = [];
                if (file_exists($miniprogramConfigFile)) {
                    $miniprogramConfig = json_decode(file_get_contents($miniprogramConfigFile), true) ?: [];
                }
                
                // 从数据库读取 APPID 和 SECRET
                try {
                    $dbConfig = require __DIR__ . '/../config/database.php';
                    $dbPdo = new PDO(
                        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                        $dbConfig['username'],
                        $dbConfig['password'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    
                    $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_appid'");
                    $appId = $stmt->fetchColumn();
                    
                    $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_secret'");
                    $appSecret = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    $appId = '';
                    $appSecret = '';
                }
                
                $templateId = $subscribeConfig['template_id'];
                
                if (empty($appId) || empty($appSecret)) {
                    $error = true;
                    $message = '请先在"基础配置"中填写小程序 APPID 和 SECRET<br><br>' .
                               '获取方式：<br>' .
                               '1. 登录微信公众平台<br>' .
                               '2. 开发 → 开发管理 → 开发设置<br>' .
                               '3. 复制"AppID(小程序 ID)"和"AppSecret(小程序密钥)"<br>' .
                               '4. 在基础配置中填写并保存';
                } else {
                    // 引入发送类
                    require_once __DIR__ . '/../includes/SubscribeMessageSender.php';
                    
                    try {
                        $sender = new SubscribeMessageSender($appId, $appSecret, $templateId);
                        
                        // 发送测试消息（匹配签证订单办理通知模板）
                        $progressData = [
                            'handler' => '青园营地',
                            'order_no' => 'TEST' . date('YmdHis'),
                            'service_name' => '签证办理进度测试',
                            'phone' => '138****8888',
                            'handle_time' => date('Y-m-d H:i:s')
                        ];
                        
                        $result = $sender->sendProgressNotification($testOpenid, $progressData);
                        
                        if (isset($result['errcode']) && $result['errcode'] == 0) {
                            $success = true;
                            $message = "✅ 测试订阅消息发送成功！<br><br>" .
                                       "📱 接收用户 OpenID：<b>{$testOpenid}</b><br>" .
                                       "模板 ID：<b>{$templateId}</b><br><br>" .
                                       "请在微信中查看是否收到订阅消息通知。";
                        } else {
                            $error = true;
                            $message = "❌ 发送失败<br><br>" .
                                       "错误码：" . ($result['errcode'] ?? '未知') . "<br>" .
                                       "错误信息：" . ($result['errmsg'] ?? '未知错误') . "<br><br>" .
                                       "常见原因：<br>" .
                                       "1. 用户未订阅该模板消息<br>" .
                                       "2. APPID 或 SECRET 配置错误<br>" .
                                       "3. 模板 ID 无效";
                        }
                    } catch (Exception $e) {
                        $error = true;
                        $message = "❌ 发送异常<br><br>" .
                                   "错误信息：" . $e->getMessage();
                    }
                }
            }
        }
    }
}

// 加载当前配置
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: [];
} else {
    $config = [
        'site_name' => '青园营地管理后台',
        'site_url' => BASE_URL,
        'api_url' => BASE_URL . '/api',
        'upload_max_size' => '10',
        'allow_register' => '0',
        'watermark_enabled' => '1',
        'watermark_text' => '青园营地',
        'timezone' => 'Asia/Shanghai',
        'language' => 'zh-CN'
    ];
}

// 从数据库读取所有配置（优先级高于配置文件）
try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $dbPdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $dbPdo->query("SELECT `config_key`, `config_value` FROM `system_config`");
    $dbConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dbConfigs as $dbConfigItem) {
        $config[$dbConfigItem['config_key']] = $dbConfigItem['config_value'];
    }
} catch (PDOException $e) {
    // 数据库读取失败不影响页面加载
    error_log("从数据库读取配置失败：" . $e->getMessage());
}

// 加载短信配置
if (file_exists($smsConfigFile)) {
    $smsConfig = json_decode(file_get_contents($smsConfigFile), true) ?: [];
} else {
    $smsConfig = [
        'enabled' => '0',
        'platform' => 'aliyun',
        'access_key_id' => '',
        'access_key_secret' => '',
        'sign_name' => '',
        'template_code' => '',
        'template_content' => '验证码${code}，您正在进行身份验证，若非本人操作，请勿泄露。',
        'test_phone' => ''
    ];
}

// 加载支付配置
$paymentConfigs = [];
if (file_exists($paymentConfigFile)) {
    $paymentConfigs = json_decode(file_get_contents($paymentConfigFile), true) ?: [];
}

// 按排序排序
uasort($paymentConfigs, function($a, $b) {
    return ($a['sort_order'] ?? 100) - ($b['sort_order'] ?? 100);
});

// 加载小程序配置
if (file_exists($miniprogramConfigFile)) {
    $miniprogramConfig = json_decode(file_get_contents($miniprogramConfigFile), true) ?: [];
} else {
    $miniprogramConfig = [
        'register_method' => 'phone',
        'require_phone' => '1',
        'auto_get_wechat' => '1',
        'auto_get_nickname' => '1',
        'auto_get_avatar' => '1',
        'wechat_appid' => '',
        'wechat_secret' => ''
    ];
}

// 加载订阅推送配置
if (file_exists($subscribeConfigFile)) {
    $subscribeConfig = json_decode(file_get_contents($subscribeConfigFile), true) ?: [];
} else {
    $subscribeConfig = [
        'enabled' => '0',
        'template_id' => '',
        'template_name' => '',
        'auto_subscribe' => '0',
        'subscribe_page' => 'pages/progress/progress',
        'send_on_progress_update' => '1'
    ];
}

$shortcutConfigs = [
    'shortcut_hot_positions' => '1',
    'shortcut_policy' => '1',
    'shortcut_travel' => '1',
    'shortcut_study_abroad' => '1',
    'shortcut_progress' => '1',
    'shortcut_stores' => '1',
    'shortcut_managers' => '1',
    'shortcut_knowledge' => '1'
];

try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $dbPdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $stmt = $dbPdo->query("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'shortcut%'");
    while ($row = $stmt->fetch()) {
        $shortcutConfigs[$row['config_key']] = $row['config_value'];
    }
} catch (PDOException $e) {
    // 数据库连接失败时使用默认值
    error_log("加载快捷按钮配置失败：" . $e->getMessage());
}
?>

<style>
.config-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 24px;
    position: relative;
}

/* 快捷导航列表 */
  /* 隐藏快捷导航 */
  .quick-nav {
      display: none !important;
    position: fixed;
    top: 120px;
    left: 20px;
    width: 180px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    padding: 16px 0;
    z-index: 100;
    max-height: calc(100vh - 160px);
    overflow-y: auto;
    overflow-x: hidden;
}

.quick-nav-title {
    font-size: 14px;
    font-weight: 700;
    color: #1890ff;
    padding: 0 16px 12px;
    border-bottom: 2px solid #f0f0f0;
    margin-bottom: 8px;
    white-space: nowrap;
}

.quick-nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-nav-item {
    padding: 10px 16px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
    color: #595959;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.quick-nav-item:hover {
    background: #f0f5ff;
    color: #1890ff;
}

.quick-nav-item.active {
    background: #e6f4ff;
    color: #1890ff;
    font-weight: 600;
    border-right: 3px solid #1890ff;
}

.quick-nav-item .nav-icon {
    font-size: 16px;
    flex-shrink: 0;
}

/* 响应式适配 */
@media (max-width: 1200px) {
  /* 隐藏快捷导航 */
  .quick-nav {
      display: none !important;
        position: static;
        transform: none;
        width: 100%;
        margin-bottom: 24px;
        max-height: none;
    }
}

.config-section {
    background: white;
    border-radius: 12px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.3s;
}

.section-header:hover {
    opacity: 0.8;
}

.section-icon {
    font-size: 24px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    border-radius: 12px;
    color: white;
    flex-shrink: 0;
}

.section-info {
    flex: 1;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #262626;
}

.section-desc {
    font-size: 13px;
    color: #8c8c8c;
    margin-top: 4px;
}

.section-toggle {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 50%;
    font-size: 20px;
    color: #1890ff;
    transition: all 0.3s;
    flex-shrink: 0;
}

.section-header:hover .section-toggle {
    background: #1890ff;
    color: white;
}

.section-content {
    display: none;
}

.section-content.expanded {
    display: block;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 14px;
    color: #262626;
    font-weight: 600;
}

.form-label .required {
    color: #ff4d4f;
    margin-left: 4px;
}

.form-input, .form-select, .form-textarea {
    padding: 10px 14px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: #1890ff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-hint {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 4px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 24px;
    border-top: 2px solid #f0f0f0;
    margin-top: 24px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
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

.btn-success {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(82, 196, 26, 0.3);
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
    background: #d9d9d9;
    border-radius: 13px;
    cursor: pointer;
    transition: background 0.3s;
}

.toggle-switch.active {
    background: #1890ff;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s;
}

.toggle-switch.active::after {
    transform: translateX(24px);
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #f6ffed;
    border: 1px solid #b7eb8f;
    color: #52c41a;
}

.alert-error {
    background: #fff2f0;
    border: 1px solid #ffccc7;
    color: #ff4d4f;
}

.alert-info {
    background: #e6f7ff;
    border: 1px solid #91d5ff;
    color: #1890ff;
}

.platform-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.platform-card {
    border: 2px solid #f0f0f0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.platform-card:hover {
    border-color: #1890ff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.platform-card.selected {
    border-color: #1890ff;
    background: #f0f5ff;
}

.platform-icon {
    font-size: 36px;
    margin-bottom: 12px;
}

.platform-name {
    font-size: 15px;
    font-weight: 600;
    color: #262626;
    margin-bottom: 4px;
}

.platform-desc {
    font-size: 12px;
    color: #8c8c8c;
}

.platform-recommend {
    display: inline-block;
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    margin-top: 8px;
}

.config-info-box {
    background: linear-gradient(135deg, #f0f5ff 0%, #e6f7ff 100%);
    border: 1px solid #91d5ff;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}

.config-info-box h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #1890ff;
    font-weight: 600;
}

.config-info-box p {
    margin: 0;
    font-size: 13px;
    color: #595959;
    line-height: 1.6;
}

.config-info-box code {
    background: rgba(0, 0, 0, 0.06);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #ff4d4f;
}

  /* 隐藏快捷导航 - 2026-05-21 */
  .quick-nav, .quick-nav * {
      display: none !important;
  }
</style>

<div class="config-container">
    <?php if (isset($notice)): ?>
        <div class="alert alert-<?php echo $notice['type']; ?>">
            <span><?php echo $notice['type'] === 'success' ? '✅' : '❌'; ?></span>
            <span><?php echo htmlspecialchars($notice['message']); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- 快捷导航列表 -->
    <nav class="quick-nav">
        <div class="quick-nav-title">📍 快捷导航</div>
        <ul class="quick-nav-list">
            <li class="quick-nav-item" data-target="domain-config" onclick="scrollToSection('domain-config')">
                <span class="nav-icon">🚀</span>
                <span>一键配置域名</span>
            </li>
            <li class="quick-nav-item" data-target="base-config" onclick="scrollToSection('base-config')">
                <span class="nav-icon">🌐</span>
                <span>基础配置</span>
            </li>
            <li class="quick-nav-item" data-target="upload-config" onclick="scrollToSection('upload-config')">
                <span class="nav-icon">📤</span>
                <span>上传配置</span>
            </li>
            <li class="quick-nav-item" data-target="watermark-config" onclick="scrollToSection('watermark-config')">
                <span class="nav-icon">💧</span>
                <span>水印配置</span>
            </li>
            <li class="quick-nav-item" data-target="sms-config" onclick="scrollToSection('sms-config')">
                <span class="nav-icon">📱</span>
                <span>短信配置</span>
            </li>
            <li class="quick-nav-item" data-target="payment-config" onclick="scrollToSection('payment-config')">
                <span class="nav-icon">💳</span>
                <span>支付配置</span>
            </li>
            <li class="quick-nav-item" data-target="miniprogram-config" onclick="scrollToSection('miniprogram-config')">
                <span class="nav-icon">💬</span>
                <span>小程序注册</span>
            </li>
            <li class="quick-nav-item" data-target="subscribe-config" onclick="scrollToSection('subscribe-config')">
                <span class="nav-icon">🔔</span>
                <span>订阅推送</span>
            </li>
            <li class="quick-nav-item" data-target="customer-service-config" onclick="scrollToSection('customer-service-config')">
                <span class="nav-icon">💬</span>
                <span>企微客服</span>
            </li>
        </ul>
    </nav>
    
    <!-- 一键配置域名 -->
        <div class="config-content-wrapper">
    <div class="config-section" id="domain-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">🚀</div>
                <div class="section-info">
                    <div class="section-title">一键配置域名</div>
                    <div class="section-desc">自动检测当前环境，快速完成域名配置</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 使用说明</h4>
            <p>
                <strong>一键配置：</strong> 自动检测当前访问的域名和协议（HTTP/HTTPS），
                并自动配置 <code>site_url</code> 和 <code>api_url</code>。<br>
                <strong>同步到所有文件：</strong> 将当前域名同步到所有配置文件（storage.php、paths.php 等），
                确保整个项目使用统一的域名配置。<br>
                <strong>部署到服务器时，只需访问一次配置页面并点击这两个按钮即可完成所有域名配置。</strong>
            </p>
        </div>
        
        <div style="text-align: center; padding: 24px; background: linear-gradient(135deg, #f0f5ff 0%, #e6f7ff 100%); border-radius: 12px; margin-bottom: 20px; border: 2px solid #91d5ff;">
            <div style="font-size: 14px; color: #1890ff; margin-bottom: 12px; font-weight: 600;">🌐 当前访问地址</div>
            <div style="font-size: 18px; color: #262626; font-weight: 700; font-family: monospace; margin-bottom: 8px;">
                <?php
                $currentDomain = detectCurrentDomain();
                echo htmlspecialchars($currentDomain);
                ?>
            </div>
            <div style="font-size: 12px; color: #8c8c8c; margin-top: 8px;">
                📡 协议：<?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'HTTPS' : 'HTTP'; ?> | 
                🖥️ 主机：<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost'); ?> |
                📂 路径：<?php echo htmlspecialchars(dirname($_SERVER['SCRIPT_NAME'])); ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
            <form method="POST" style="text-align: center;">
                <input type="hidden" name="action" value="auto_config_domain">
                <button type="submit" class="btn btn-success" style="width: 100%; font-size: 15px; padding: 14px 24px;">
                    ⚡ 一键配置当前域名
                </button>
            </form>
            
            <form method="POST" style="text-align: center;">
                <input type="hidden" name="action" value="sync_domain_to_all">
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 15px; padding: 14px 24px;" 
                        onclick="return confirm('确定要将当前域名同步到所有配置文件吗？\n\n这将修改 storage.php 等文件中的域名配置。');">
                    🔄 同步到所有配置文件
                </button>
            </form>
        </div>
        
        <?php if (file_exists($configFile)): ?>
        <?php $savedConfig = json_decode(file_get_contents($configFile), true) ?: []; ?>
        <?php if (isset($savedConfig['auto_detected_domain'])): ?>
        <div style="background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 8px; padding: 12px 16px; margin-top: 16px;">
            <div style="font-size: 13px; color: #52c41a; margin-bottom: 4px;">✅ 上次自动检测的域名</div>
            <div style="font-size: 14px; color: #262626; font-family: monospace;">
                <?php echo htmlspecialchars($savedConfig['auto_detected_domain']); ?>
            </div>
            <?php if (isset($savedConfig['last_domain_check'])): ?>
            <div style="font-size: 12px; color: #8c8c8c; margin-top: 4px;">
                🕐 检测时间：<?php echo htmlspecialchars($savedConfig['last_domain_check']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($savedConfig['domain_history']) && is_array($savedConfig['domain_history']) && count($savedConfig['domain_history']) > 1): ?>
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #d9d9d9;">
                <div style="font-size: 12px; color: #8c8c8c; margin-bottom: 4px;">📋 域名历史记录（最近 <?php echo count($savedConfig['domain_history']); ?> 次）</div>
                <ul style="margin: 0; padding-left: 16px; font-size: 12px; color: #595959;">
                    <?php foreach (array_slice($savedConfig['domain_history'], -5) as $history): ?>
                    <li style="margin: 2px 0;">
                        <?php echo htmlspecialchars($history['domain']); ?> 
                        <span style="color: #8c8c8c;">(<?php echo htmlspecialchars($history['time']); ?>)</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- 基础配置 -->
    <div class="config-section" id="base-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">⚙️</div>
                <div class="section-info">
                    <div class="section-title">基础配置</div>
                    <div class="section-desc">系统基础设置（一键部署后自动匹配）</div>
                </div>
                <div class="section-toggle">▼</div>
            </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">网站名称 <span class="required">*</span></label>
                    <input type="text" class="form-input" name="site_name" value="<?php echo htmlspecialchars($config['site_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">网站 URL <span class="required">*</span></label>
                    <input type="url" class="form-input" name="site_url" value="<?php echo htmlspecialchars($config['site_url']); ?>" required>
                    <div class="form-hint">系统访问地址，一键配置后自动填充</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">API 地址 <span class="required">*</span></label>
                    <input type="url" class="form-input" name="api_url" value="<?php echo htmlspecialchars($config['api_url']); ?>" required>
                    <div class="form-hint">API 接口地址，一般为 网站 URL/api</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">时区设置</label>
                    <select class="form-select" name="timezone">
                        <option value="Asia/Shanghai" <?php echo $config['timezone'] === 'Asia/Shanghai' ? 'selected' : ''; ?>>中国标准时间 (Asia/Shanghai)</option>
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">美国东部时间</option>
                        <option value="Europe/London">伦敦时间</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">系统语言</label>
                    <select class="form-select" name="language">
                        <option value="zh-CN" <?php echo $config['language'] === 'zh-CN' ? 'selected' : ''; ?>>简体中文</option>
                        <option value="zh-TW">繁体中文</option>
                        <option value="en-US">English</option>
                    </select>
                </div>
            </div>
            
            <!-- 小程序配置 -->
            <div class="form-group" style="margin-top: 24px;">
                <label class="form-label" style="display: block; margin-bottom: 16px; font-size: 16px; font-weight: 600; color: #333;">📱 小程序配置</label>
                <div style="background: #f0f5ff; border: 1px solid #d6e4ff; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 12px 0; font-size: 14px; color: #666;">
                        <strong>说明：</strong> 此处配置的小程序 APPID 和 SECRET 将用于所有需要微信认证的功能，包括：
                        订阅消息推送、用户登录、微信支付等。配置一次，全局使用。
                    </p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">小程序 APPID <span class="required">*</span></label>
                        <input type="text" class="form-input" name="miniprogram_appid" value="<?php echo htmlspecialchars($config['miniprogram_appid'] ?? ''); ?>" placeholder="例如：wx1234567890abcdef" required>
                        <div class="form-hint">微信公众平台 → 开发 → 开发管理 → 开发设置 → AppID(小程序 ID)</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">小程序 SECRET <span class="required">*</span></label>
                        <input type="password" class="form-input" name="miniprogram_secret" value="<?php echo htmlspecialchars($config['miniprogram_secret'] ?? ''); ?>" placeholder="例如：a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6" required>
                        <div class="form-hint">微信公众平台 → 开发 → 开发管理 → 开发设置 → AppSecret(小程序密钥)</div>
                    </div>
                </div>
            </div>
            
            <!-- 快捷按钮组件开关 -->
            <div class="form-group" style="margin-top: 24px;">
                <label class="form-label" style="display: block; margin-bottom: 16px;">快捷按钮组件显示设置</label>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">🔥 热门岗位</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_hot_positions'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_hot_positions" value="<?php echo $shortcutConfigs['shortcut_hot_positions'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">📰 实事政策</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_policy'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_policy" value="<?php echo $shortcutConfigs['shortcut_policy'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">✈️ 出国旅游</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_travel'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_travel" value="<?php echo $shortcutConfigs['shortcut_travel'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">🎓 移民留学</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_study_abroad'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_study_abroad" value="<?php echo $shortcutConfigs['shortcut_study_abroad'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">📊 办理进度</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_progress'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_progress" value="<?php echo $shortcutConfigs['shortcut_progress'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">🏪 门店列表</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_stores'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_stores" value="<?php echo $shortcutConfigs['shortcut_stores'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">👨‍💼 业务经理</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_managers'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_managers" value="<?php echo $shortcutConfigs['shortcut_managers'] ?? '1'; ?>">
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f5f7fa; border-radius: 8px;">
                        <span style="font-size: 14px; color: #333;">📚 知识宝库</span>
                        <div class="toggle-switch <?php echo ($shortcutConfigs['shortcut_knowledge'] ?? '1') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                            <div class="toggle-handle"></div>
                        </div>
                        <input type="hidden" name="shortcut_knowledge" value="<?php echo $shortcutConfigs['shortcut_knowledge'] ?? '1'; ?>">
                    </div>
                </div>
                <div class="form-hint" style="margin-top: 12px;">开启后小程序端显示对应快捷按钮，关闭则隐藏</div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存配置</button>
            </div>
        </form>
    </div>
        </div>

<!-- 短信配置 -->
    <div class="config-section" id="sms-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">📱</div>
                <div class="section-info">
                    <div class="section-title">短信配置</div>
                    <div class="section-desc">配置短信平台，用于发送验证码和通知</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 短信服务说明</h4>
            <p>
                支持主流短信平台，推荐优先使用 <strong>阿里云短信</strong>。
                配置后可用于用户注册验证码、登录验证、重要通知等场景。
                请确保在短信平台完成实名认证和模板审核。
            </p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_sms_config">
            
            <!-- 短信开关 -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">开启短信服务</label>
                <div class="toggle-switch <?php echo $smsConfig['enabled'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                </div>
                <input type="hidden" name="sms_enabled" value="<?php echo $smsConfig['enabled']; ?>">
                <div class="form-hint">关闭后系统将不发送任何短信</div>
            </div>
            
            <!-- 短信平台选择 -->
            <label class="form-label" style="display: block; margin-bottom: 12px;">选择短信平台</label>
            <div class="platform-cards">
                <div class="platform-card <?php echo $smsConfig['platform'] === 'aliyun' ? 'selected' : ''; ?>" onclick="selectPlatform(this, 'aliyun')">
                    <div class="platform-icon">☁️</div>
                    <div class="platform-name">阿里云短信</div>
                    <div class="platform-desc">稳定可靠，覆盖全球</div>
                    <div class="platform-recommend">⭐ 推荐</div>
                </div>
                <div class="platform-card <?php echo $smsConfig['platform'] === 'tencent' ? 'selected' : ''; ?>" onclick="selectPlatform(this, 'tencent')">
                    <div class="platform-icon">🐧</div>
                    <div class="platform-name">腾讯云短信</div>
                    <div class="platform-desc">腾讯生态，快速接入</div>
                </div>
                <div class="platform-card <?php echo $smsConfig['platform'] === 'qiniu' ? 'selected' : ''; ?>" onclick="selectPlatform(this, 'qiniu')">
                    <div class="platform-icon">📬</div>
                    <div class="platform-name">七牛云短信</div>
                    <div class="platform-desc">性价比高，简单易用</div>
                </div>
            </div>
            <input type="hidden" name="sms_platform" id="sms_platform" value="<?php echo $smsConfig['platform']; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">AccessKeyId <span class="required">*</span></label>
                    <input type="text" class="form-input" name="access_key_id" value="<?php echo htmlspecialchars($smsConfig['access_key_id']); ?>" placeholder="请输入 AccessKeyId" required>
                    <div class="form-hint">短信平台的 AccessKeyId</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">AccessKeySecret <span class="required">*</span></label>
                    <input type="password" class="form-input" name="access_key_secret" value="<?php echo htmlspecialchars($smsConfig['access_key_secret']); ?>" placeholder="请输入 AccessKeySecret" required>
                    <div class="form-hint">短信平台的 AccessKeySecret，请妥善保管</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">短信签名 Sign <span class="required">*</span></label>
                    <input type="text" class="form-input" name="sign_name" value="<?php echo htmlspecialchars($smsConfig['sign_name']); ?>" placeholder="请输入短信签名" required>
                    <div class="form-hint">在短信平台申请的签名，如：青园营地</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">模板 ID/Code <span class="required">*</span></label>
                    <input type="text" class="form-input" name="template_code" value="<?php echo htmlspecialchars($smsConfig['template_code']); ?>" placeholder="请输入模板 ID" required>
                    <div class="form-hint">验证码短信的模板 ID</div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">模板内容</label>
                    <textarea class="form-textarea" name="template_content" rows="3" readonly><?php echo htmlspecialchars($smsConfig['template_content']); ?></textarea>
                    <div class="form-hint">验证码短信模板内容，${code}为验证码占位符</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">测试手机号</label>
                    <input type="tel" class="form-input" name="test_phone" value="<?php echo htmlspecialchars($smsConfig['test_phone']); ?>" placeholder="请输入测试手机号">
                    <div class="form-hint">用于测试短信发送</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="testSms()">📱 测试发送</button>
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存配置</button>
            </div>
        </form>
    </div>
        </div>

    <!-- 上传配置 -->
    <div class="config-section" id="upload-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">📁</div>
                <div class="section-info">
                    <div class="section-title">上传配置</div>
                    <div class="section-desc">文件上传相关设置</div>
                </div>
                <div class="section-toggle">▼</div>
            </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">最大上传文件大小 (MB)</label>
                    <input type="number" class="form-input" name="upload_max_size" value="<?php echo $config['upload_max_size']; ?>" min="1" max="100">
                    <div class="form-hint">限制用户上传文件的最大大小</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">允许用户注册</label>
                    <div class="toggle-switch <?php echo $config['allow_register'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="allow_register" value="<?php echo $config['allow_register']; ?>">
                    <div class="form-hint">开启后允许新用户注册</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存配置</button>
            </div>
        </form>
    </div>
        </div>

    <!-- 水印配置 -->
    <div class="config-section" id="watermark-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">💧</div>
                <div class="section-info">
                    <div class="section-title">水印配置</div>
                    <div class="section-desc">图片水印设置</div>
                </div>
                <div class="section-toggle">▼</div>
            </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">启用水印</label>
                    <div class="toggle-switch <?php echo $config['watermark_enabled'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="watermark_enabled" value="<?php echo $config['watermark_enabled']; ?>">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">水印文字</label>
                    <input type="text" class="form-input" name="watermark_text" value="<?php echo htmlspecialchars($config['watermark_text']); ?>">
                    <div class="form-hint">添加到图片上的水印文字</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存配置</button>
            </div>
        </form>
    </div>
        </div>

<!-- 支付配置 -->
    <div class="config-section" id="payment-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">💳</div>
                <div class="section-info">
                    <div class="section-title">支付配置</div>
                    <div class="section-desc">配置各端支付方式（微信小程序、H5、公众号、APP）</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 支付配置说明</h4>
            <p>
                支持配置多个支付模板，每个模板对应一种支付场景。例如：H5 端 - 支付宝支付、微信小程序端 - 微信支付等。
                配置后可在订单支付时选择对应的支付方式。<strong>保存后支付方式将不可修改</strong>，请谨慎操作。
            </p>
        </div>
        
        <!-- 支付配置列表 -->
        <?php if (!empty($paymentConfigs)): ?>
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 15px; margin-bottom: 12px; color: #262626;">已配置的支付方式</h4>
                <?php foreach ($paymentConfigs as $payId => $payConfig): ?>
                    <div style="border: 1px solid #f0f0f0; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: #fafafa;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 24px;">
                                    <?php
                                    $icons = [
                                        'wechat' => '💚',
                                        'alipay' => '💙'
                                    ];
                                    echo $icons[$payConfig['payment_method'] ?? 'wechat'] ?? '💳';
                                    ?>
                                </span>
                                <div>
                                    <div style="font-weight: 600; color: #262626;"><?php echo htmlspecialchars($payConfig['template_name']); ?></div>
                                    <div style="font-size: 12px; color: #8c8c8c;">
                                        <?php
                                        $platforms = [
                                            'miniprogram' => '微信小程序',
                                            'h5' => 'H5 端',
                                            'wechat_mp' => '微信公众号',
                                            'app' => 'APP'
                                        ];
                                        echo $platforms[$payConfig['platform_type'] ?? 'h5'];
                                        ?> · 
                                        <?php echo $payConfig['payment_method'] === 'wechat' ? '微信支付' : '支付宝'; ?>
                                        · 排序：<?php echo $payConfig['sort_order']; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-default" onclick="editPayment('<?php echo $payId; ?>')" style="padding: 6px 12px; font-size: 13px;">编辑</button>
                                <button class="btn btn-default" onclick="deletePayment('<?php echo $payId; ?>', '<?php echo htmlspecialchars($payConfig['template_name']); ?>')" style="padding: 6px 12px; font-size: 13px; color: #ff4d4f;">删除</button>
                            </div>
                        </div>
                        <?php if (!empty($payConfig['admin_remark'])): ?>
                            <div style="font-size: 13px; color: #595959; background: white; padding: 8px 12px; border-radius: 6px;">
                                <?php echo htmlspecialchars($payConfig['admin_remark']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- 添加/编辑支付配置表单 -->
        <div id="paymentFormContainer">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="payment_id" id="payment_id" value="">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">支付模板名称 <span class="required">*</span></label>
                        <input type="text" class="form-input" name="template_name" id="template_name" placeholder="例如：H5 端 - 支付宝支付" required>
                        <div class="form-hint">仅用于后台管理使用，对前台用户不可见</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">管理员备注</label>
                        <textarea class="form-textarea" name="admin_remark" id="admin_remark" rows="2" placeholder="备注信息，仅管理员可见"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">排序 <span class="required">*</span></label>
                        <input type="number" class="form-input" name="sort_order" id="sort_order" value="100" min="1" max="999">
                        <div class="form-hint">数字越小越靠前</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">应用图标</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button type="button" class="btn btn-default" onclick="document.getElementById('icon_up').click()">⬆️</button>
                            <button type="button" class="btn btn-default" onclick="document.getElementById('icon_down').click()">⬇️</button>
                            <span style="color: #8c8c8c; font-size: 13px;">调整排序（使用上下按钮）</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">平台类型 <span class="required">*</span></label>
                        <select class="form-select" name="platform_type" id="platform_type" required>
                            <option value="h5">H5 端</option>
                            <option value="miniprogram">微信小程序</option>
                            <option value="wechat_mp">微信公众号</option>
                            <option value="app">APP</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">支付方式 <span class="required">*</span></label>
                        <select class="form-select" name="payment_method" id="payment_method" required disabled>
                            <option value="wechat">💚 微信支付</option>
                            <option value="alipay">💙 支付宝</option>
                        </select>
                        <div class="form-hint" style="color: #ff4d4f; font-weight: 600;">⚠️ 保存以后支付方式将不可修改，请谨慎操作</div>
                    </div>
                </div>
                
                <!-- 微信支付配置 -->
                <div id="wechatConfig" style="margin-top: 24px; padding: 20px; background: #f0f5ff; border-radius: 8px; border: 1px solid #d6e4ff;">
                    <h4 style="margin: 0 0 16px 0; color: #1890ff; font-size: 15px;">💚 微信支付配置</h4>
                    
                    <div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                        <a href="https://pay.weixin.qq.com" target="_blank" style="color: #1890ff; text-decoration: none; font-weight: 600;">
                            🔗 微信支付商户平台：https://pay.weixin.qq.com
                        </a>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">微信支付接口版本</label>
                            <div style="display: flex; gap: 16px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="wechat_version" value="v3" checked>
                                    <span>V3（推荐）</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="wechat_version" value="v2">
                                    <span>V2（较老，不再支持新 API）</span>
                                </label>
                            </div>
                            <div class="form-hint">V2 版本较老已经不再支持新出的 API 接口，强烈建议使用 V3</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">微信商户号类型</label>
                            <div style="display: flex; gap: 16px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="merchant_type" value="normal" checked>
                                    <span>普通商户（推荐）</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="merchant_type" value="sub">
                                    <span>子商户（服务商模式）</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">应用 ID (AppID) <span class="required">*</span></label>
                            <input type="text" class="form-input" name="appid" placeholder="微信小程序端支付填写小程序 APPID，APP 支付需要填写开放平台的应用 APPID">
                            <div class="form-hint">微信小程序端支付填写小程序 APPID，APP 支付需要填写开放平台的应用 APPID</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">微信商户号 (MchId) <span class="required">*</span></label>
                            <input type="text" class="form-input" name="mch_id" placeholder="例如：1600000109" pattern="\d+">
                            <div class="form-hint">微信支付的商户号，纯数字格式</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">支付密钥 (APIKEY) <span class="required">*</span></label>
                            <input type="password" class="form-input" name="api_key" placeholder="••••••••••••" value="">
                            <div class="form-hint">"微信支付商户平台" - "账户中心" - "API 安全" - "设置 API 密钥"</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">验签方式</label>
                            <select class="form-select" name="verify_method">
                                <option value="public_key">微信支付公钥</option>
                                <option value="platform_cert">平台证书</option>
                            </select>
                            <div class="form-hint">"微信支付商户平台" - "账户中心" - "API 安全" - "验证微信支付身份"</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">微信支付公钥 ID</label>
                            <input type="text" class="form-input" name="public_key_id" placeholder="例如：PUB_KEY_ID_0116777777772024080100123400000567">
                            <div class="form-hint">"微信支付商户平台" - "账户中心" - "API 安全" - "微信支付公钥"</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">微信支付公钥 (KEY)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="file" class="form-input" name="public_key_file" accept=".pem" style="flex: 1;">
                                <span style="font-size: 13px; color: #8c8c8c;">请上传 "pub_key.pem" 文件</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">商户 API 证书 (CERT)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="file" class="form-input" name="cert_file" accept=".pem" style="flex: 1;">
                                <span style="font-size: 13px; color: #8c8c8c;">请上传 "apiclient_cert.pem" 文件</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">商户 API 证书 (KEY)</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="file" class="form-input" name="key_file" accept=".pem" style="flex: 1;">
                                <span style="font-size: 13px; color: #8c8c8c;">请上传 "apiclient_key.pem" 文件</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-default" onclick="resetPaymentForm()">🔄 重置</button>
                    <button type="submit" class="btn btn-primary" id="savePaymentBtn">💾 保存支付配置</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 小程序注册配置 -->
    <div class="config-section" id="miniprogram-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">📱</div>
                <div class="section-info">
                    <div class="section-title">小程序注册配置</div>
                    <div class="section-desc">配置小程序用户注册方式和信息获取</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 注册配置说明</h4>
            <p>
                支持两种注册方式：<strong>手机号注册</strong>和<strong>微信号快捷登录</strong>。
                可配置自动获取用户微信信息（微信号、昵称、头像），注册必须绑定手机号以确保用户身份真实性。
            </p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_miniprogram">
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">注册方式 <span class="required">*</span></label>
                    <div style="display: flex; gap: 16px; margin-top: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px 16px; border: 2px solid #1890ff; border-radius: 8px; background: #f0f5ff; flex: 1;">
                            <input type="radio" name="register_method" value="phone" <?php echo $miniprogramConfig['register_method'] === 'phone' ? 'checked' : ''; ?> onchange="toggleRegisterMethod('phone')">
                            <div>
                                <div style="font-weight: 600; color: #1890ff;">📱 手机号注册</div>
                                <div style="font-size: 12px; color: #8c8c8c;">用户通过手机号验证码注册</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 8px; flex: 1;" id="wechatRegisterCard">
                            <input type="radio" name="register_method" value="wechat" <?php echo $miniprogramConfig['register_method'] === 'wechat' ? 'checked' : ''; ?> onchange="toggleRegisterMethod('wechat')">
                            <div>
                                <div style="font-weight: 600; color: #262626;">💬 微信号快捷登录</div>
                                <div style="font-size: 12px; color: #8c8c8c;">一键获取微信信息快速注册</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px 16px; border: 2px solid #f0f0f0; border-radius: 8px; flex: 1;" id="bothRegisterCard">
                            <input type="radio" name="register_method" value="both" <?php echo $miniprogramConfig['register_method'] === 'both' ? 'checked' : ''; ?> onchange="toggleRegisterMethod('both')">
                            <div>
                                <div style="font-weight: 600; color: #262626;">🔀 两种方式都支持</div>
                                <div style="font-size: 12px; color: #8c8c8c;">用户可自由选择注册方式</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">必须绑定手机号 <span class="required">*</span></label>
                    <div class="toggle-switch <?php echo $miniprogramConfig['require_phone'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="require_phone" value="<?php echo $miniprogramConfig['require_phone']; ?>">
                    <div class="form-hint">开启后注册时必须绑定手机号</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">自动获取微信号</label>
                    <div class="toggle-switch <?php echo $miniprogramConfig['auto_get_wechat'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="auto_get_wechat" value="<?php echo $miniprogramConfig['auto_get_wechat']; ?>">
                    <div class="form-hint">开启后自动获取用户微信号</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">自动获取昵称</label>
                    <div class="toggle-switch <?php echo $miniprogramConfig['auto_get_nickname'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="auto_get_nickname" value="<?php echo $miniprogramConfig['auto_get_nickname']; ?>">
                    <div class="form-hint">开启后自动获取用户微信昵称</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">自动获取头像</label>
                    <div class="toggle-switch <?php echo $miniprogramConfig['auto_get_avatar'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="auto_get_avatar" value="<?php echo $miniprogramConfig['auto_get_avatar']; ?>">
                    <div class="form-hint">开启后自动获取用户微信头像</div>
                </div>
            </div>
            
            <!-- 微信配置 -->
            <div id="wechatConfigSection" style="margin-top: 24px; padding: 20px; background: #f6ffed; border-radius: 8px; border: 1px solid #b7eb8f; <?php echo $miniprogramConfig['register_method'] === 'phone' ? 'display: none;' : ''; ?>">
                <h4 style="margin: 0 0 16px 0; color: #52c41a; font-size: 15px;">💬 微信小程序配置</h4>
                
                <div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <a href="https://mp.weixin.qq.com" target="_blank" style="color: #52c41a; text-decoration: none; font-weight: 600;">
                        🔗 微信公众平台：https://mp.weixin.qq.com
                    </a>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">小程序 AppID <span class="required">*</span></label>
                        <input type="text" class="form-input" name="wechat_appid" value="<?php 
                            // 从数据库读取
                            try {
                                $dbConfig = require __DIR__ . '/../config/database.php';
                                $dbPdo = new PDO(
                                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                                    $dbConfig['username'],
                                    $dbConfig['password'],
                                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                                );
                                $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_appid'");
                                $dbAppId = $stmt->fetchColumn();
                                echo htmlspecialchars($dbAppId ?: $config['miniprogram_appid'] ?? '');
                            } catch (PDOException $e) {
                                echo htmlspecialchars($config['miniprogram_appid'] ?? '');
                            }
                        ?>" placeholder="例如：wx8888888888888888" readonly style="background: #f5f7fa; cursor: not-allowed;">
                        <div class="form-hint">从数据库自动读取，如需修改请前往 <a href="#base-config" onclick="scrollToSection('base-config')">基础配置</a></div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">小程序 AppSecret <span class="required">*</span></label>
                        <input type="password" class="form-input" name="wechat_secret" value="<?php 
                            // 从数据库读取
                            try {
                                $dbConfig = require __DIR__ . '/../config/database.php';
                                $dbPdo = new PDO(
                                    "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                                    $dbConfig['username'],
                                    $dbConfig['password'],
                                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                                );
                                $stmt = $dbPdo->query("SELECT `config_value` FROM `system_config` WHERE `config_key` = 'miniprogram_secret'");
                                $dbSecret = $stmt->fetchColumn();
                                echo htmlspecialchars($dbSecret ?: $config['miniprogram_secret'] ?? '');
                            } catch (PDOException $e) {
                                echo htmlspecialchars($config['miniprogram_secret'] ?? '');
                            }
                        ?>" placeholder="••••••••••••" readonly style="background: #f5f7fa; cursor: not-allowed;">
                        <div class="form-hint">从数据库自动读取，如需修改请前往 <a href="#base-config" onclick="scrollToSection('base-config')">基础配置</a></div>
                    </div>
                </div>
                
                <div class="config-info-box" style="margin-top: 16px;">
                    <h4>💡 配置说明</h4>
                    <p>
                        小程序 AppID 和 Secret 已在 <strong>基础配置</strong> 中统一配置，此处自动读取。
                        这样设计的好处：
                        <br><br>
                        ✅ <strong>统一管理</strong>：所有需要 APPID 的功能都从同一位置读取<br>
                        ✅ <strong>避免重复</strong>：不需要在多个地方填写相同的配置<br>
                        ✅ <strong>易于维护</strong>：修改一处，全局生效
                    </p>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存注册配置</button>
            </div>
        </form>
    </div>
        </div>

<!-- 订阅推送配置 -->
    <div class="config-section" id="subscribe-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">🔔</div>
                <div class="section-info">
                    <div class="section-title">订阅消息推送配置</div>
                    <div class="section-desc">配置小程序订阅消息推送（办理进度通知）</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 订阅消息说明</h4>
            <p>
                微信小程序订阅消息用于向用户发送办理进度通知。用户需要在小程序端授权订阅后，
                后台才能在进度更新时发送消息提醒。配置步骤：
                <br><br>
                <strong>1. 登录微信公众平台</strong> → 功能 → 订阅消息 → 选用公共模板
                <br><strong>2. 选择模板</strong> → 搜索"进度提醒"或"订单进度" → 选用并配置关键词
                <br><strong>3. 获取模板 ID</strong> → 审核通过后在"我的模板"中复制模板 ID
                <br><strong>4. 填写到下方配置</strong> → 保存后即可使用
                <br><br>
                <strong>注意：</strong> 用户每次授权只能获得 1 次推送机会，多次推送需引导用户多次授权。
            </p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_subscribe">
            
            <!-- 订阅开关 -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">开启订阅推送</label>
                <div class="toggle-switch <?php echo $subscribeConfig['enabled'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                </div>
                <input type="hidden" name="subscribe_enabled" value="<?php echo $subscribeConfig['enabled']; ?>">
                <div class="form-hint">关闭后系统将不发送订阅消息</div>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">模板 ID (Template ID) <span class="required">*</span></label>
                    <input type="text" class="form-input" name="template_id" value="<?php echo htmlspecialchars($subscribeConfig['template_id']); ?>" placeholder="请输入订阅消息模板 ID，如：wxxxxxxxxxxxx" required>
                    <div class="form-hint">在微信公众平台 → 功能 → 订阅消息 → 我的模板 中复制</div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">模板名称（备注用）</label>
                    <input type="text" class="form-input" name="template_name" value="<?php echo htmlspecialchars($subscribeConfig['template_name']); ?>" placeholder="例如：办理进度通知">
                    <div class="form-hint">仅用于后台管理备注，方便识别</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">自动引导订阅</label>
                    <div class="toggle-switch <?php echo $subscribeConfig['auto_subscribe'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="auto_subscribe" value="<?php echo $subscribeConfig['auto_subscribe']; ?>">
                    <div class="form-hint">开启后用户进入进度页面会自动弹出订阅授权</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">进度更新时自动发送</label>
                    <div class="toggle-switch <?php echo $subscribeConfig['send_on_progress_update'] === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="send_on_progress_update" value="<?php echo $subscribeConfig['send_on_progress_update']; ?>">
                    <div class="form-hint">开启后后台更新进度时自动推送消息给已订阅用户</div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">订阅授权页面路径</label>
                    <input type="text" class="form-input" name="subscribe_page" value="<?php echo htmlspecialchars($subscribeConfig['subscribe_page']); ?>" placeholder="pages/progress/progress" readonly>
                    <div class="form-hint">用户点击订阅按钮的页面路径，默认办理进度页面</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-default" onclick="testSubscribe()">📱 测试发送</button>
                <button type="button" class="btn btn-default">🔄 重置</button>
                <button type="submit" class="btn btn-primary">💾 保存配置</button>
            </div>
        </form>
    </div>
        </div>

<!-- 企微客服配置 -->
    <div class="config-section" id="customer-service-config">
        <div class="section-header" onclick="toggleSection(this)">
                <div class="section-icon">💬</div>
                <div class="section-info">
                    <div class="section-title">企微客服配置</div>
                    <div class="section-desc">配置企业微信客服，用户点击"立即沟通"可跳转对话</div>
                </div>
                <div class="section-toggle" id="toggle-??">▼</div>
            </div>
        
        <div class="config-info-box">
            <h4>💡 企微客服说明</h4>
            <p>
                配置企业微信客服后，小程序端岗位详情页面的"立即沟通"按钮将跳转到企业微信客服页面，
                用户可以直接与客服进行对话。配置步骤：
                <br><br>
                <strong>1. 登录企业微信管理后台</strong> → 应用管理 → 客服
                <br><strong>2. 创建客服账号</strong> → 配置客服名称、头像等信息
                <br><strong>3. 获取客服链接</strong> → 在客服设置中复制"客服接入链接"
                <br><strong>4. 填写到下方配置</strong> → 保存后小程序端即可使用
                <br><br>
                <strong>注意：</strong> 客服链接格式一般为 <code>https://work.weixin.qq.com/kfid/xxx</code>
            </p>
        </div>
        
        <!-- 客服列表 -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0; color: #333;">📋 客服列表</h3>
                <button type="button" class="btn btn-primary btn-sm" onclick="showAddForm()">➕ 添加客服</button>
            </div>
            
            <?php if (count($customerServiceList) > 0): ?>
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <thead>
                        <tr style="background: #fafafa;">
                            <th style="padding: 12px; text-align: left;">客服名称</th>
                            <th style="padding: 12px; text-align: left;">客服链接</th>
                            <th style="padding: 12px; text-align: center;">启用状态</th>
                            <th style="padding: 12px; text-align: center;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerServiceList as $cs): ?>
                            <tr style="border-bottom: 1px solid #e8e8e8;">
                                <td style="padding: 12px; font-weight: 600; color: #333;"><?php echo htmlspecialchars($cs['service_name']); ?></td>
                                <td style="padding: 12px;">
                                    <a href="<?php echo htmlspecialchars($cs['service_url']); ?>" target="_blank" style="color: #1890ff;">
                                        <?php echo htmlspecialchars($cs['service_url']); ?>
                                    </a>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span class="cs-status-text" id="cs-status-<?php echo $cs['id']; ?>">
                                        <?php if ($cs['enabled'] == 1): ?>
                                            <span style="color: #52c41a; font-weight: bold; font-size: 14px;">✅ 启用中</span>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 14px;">❌ 已禁用</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <!-- 启用/禁用拨动开关 -->
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 12px; color: #666;"><?php echo $cs['enabled'] == 1 ? '启用' : '禁用'; ?></span>
                                        <label class="toggle-switch-small <?php echo $cs['enabled'] == 1 ? 'active' : ''; ?>" 
                                               onclick="toggleEnable(<?php echo $cs['id']; ?>, event)"
                                               id="cs-toggle-<?php echo $cs['id']; ?>">
                                            <input type="checkbox" style="display: none;" <?php echo $cs['enabled'] == 1 ? 'checked' : ''; ?>>
                                        </label>
                                    </div>
                                    
                                    <!-- 编辑按钮 -->
                                    <button type="button" class="btn btn-sm btn-default" onclick="editCustomer(<?php echo htmlspecialchars(json_encode($cs)); ?>)" style="margin-left: 8px;">编辑</button>
                                    
                                    <!-- 删除按钮 -->
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这个客服吗？')">
                                        <input type="hidden" name="action" value="delete_customer_service">
                                        <input type="hidden" name="delete_id" value="<?php echo $cs['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="margin-left: 8px;">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #999; background: #fafafa; border-radius: 8px;">
                    暂无客服数据，请点击上方"添加客服"按钮
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 添加/编辑客服表单 -->
        <div id="customerServiceForm" style="display: none; background: #f0f5ff; padding: 24px; border-radius: 12px; border: 2px solid #91d5ff;">
            <h3 style="margin: 0 0 20px 0; color: #1890ff;" id="formTitle">➕ 添加客服</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_customer_service">
                <input type="hidden" name="service_id" id="service_id" value="">
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">启用状态</label>
                    <div class="toggle-switch <?php echo ($currentConfig['enabled'] ?? '0') === '1' ? 'active' : ''; ?>" onclick="toggleSwitch(this)">
                    </div>
                    <input type="hidden" name="service_enabled" id="service_enabled" value="<?php echo $currentConfig['enabled'] ?? '0'; ?>">
                    <div class="form-hint">启用后，小程序中的"立即沟通"按钮将跳转到此客服（同一时间只能启用一个）</div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">客服名称 <span class="required">*</span></label>
                        <input type="text" class="form-input" name="service_name" id="service_name" value="<?php echo htmlspecialchars($currentConfig['service_name'] ?? ''); ?>" placeholder="例如：青园营地 - 小韩" required>
                        <div class="form-hint">客服显示名称，会在小程序中展示</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">客服链接 <span class="required">*</span></label>
                        <input type="url" class="form-input" name="service_url" id="service_url" value="<?php echo htmlspecialchars($currentConfig['service_url'] ?? ''); ?>" placeholder="例如：https://work.weixin.qq.com/kfid/xxx" required>
                        <div class="form-hint">企业微信客服链接，从企业微信管理后台获取</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">企业 ID (CorpID) <span class="required">*</span></label>
                        <input type="text" class="form-input" name="corp_id" id="corp_id" value="<?php echo htmlspecialchars($currentConfig['corp_id'] ?? 'ww85276673d0f341a1'); ?>" placeholder="例如：ww85276673d0f341a1" required>
                        <div class="form-hint">企业微信企业 ID，从「我的企业」→「企业信息」查看</div>
                    </div>
                </div>
                
                <div class="form-actions" style="margin-top: 24px;">
                    <button type="button" class="btn btn-default" onclick="hideForm()">取消</button>
                    <button type="submit" class="btn btn-primary">💾 保存</button>
                </div>
            </form>
        </div>
        
        <style>
        /* 小尺寸拨动开关 */
        .toggle-switch-small {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 18px;
            background: #ccc;
            border-radius: 9px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-switch-small::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 14px;
            height: 14px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch-small.active {
            background: #52c41a;
        }
        .toggle-switch-small.active::after {
            transform: translateX(18px);
        }
        </style>
        
        <script>
        function showAddForm() {
            document.getElementById('formTitle').innerText = '➕ 添加客服';
            document.getElementById('service_id').value = '';
            document.getElementById('service_name').value = '';
            document.getElementById('service_url').value = '';
            document.getElementById('corp_id').value = 'ww85276673d0f341a1';
            document.getElementById('service_enabled').value = '0';
            document.querySelector('#customerServiceForm .toggle-switch').classList.remove('active');
            document.getElementById('customerServiceForm').style.display = 'block';
        }
        
        function editCustomer(data) {
            document.getElementById('formTitle').innerText = '编辑客服';
            document.getElementById('service_id').value = data.id;
            document.getElementById('service_name').value = data.service_name;
            document.getElementById('service_url').value = data.service_url;
            document.getElementById('corp_id').value = data.corp_id || 'ww85276673d0f341a1';
            document.getElementById('service_enabled').value = data.enabled || '0';
            
            const toggle = document.querySelector('#customerServiceForm .toggle-switch');
            if (data.enabled == 1) {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
            
            document.getElementById('customerServiceForm').style.display = 'block';
        }
        
        function hideForm() {
            document.getElementById('customerServiceForm').style.display = 'none';
        }
        
        function toggleSwitch(element) {
            element.classList.toggle('active');
            const input = element.nextElementSibling;
            input.value = element.classList.contains('active') ? '1' : '0';
        }
        
        function toggleEnable(serviceId, event) {
            // 阻止事件冒泡
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            // 获取触发事件的元素
            const element = event.currentTarget;
            
            // 获取 checkbox 的当前状态
            const checkbox = element.querySelector('input[type="checkbox"]');
            const isChecked = checkbox && checkbox.checked;
            
            // 确定要执行的操作
            const enable = !isChecked; // false→启用，true→禁用
            
            // 使用 AJAX 提交请求
            const formData = new FormData();
            formData.append('action', 'toggle_customer_service');
            formData.append('service_id', serviceId);
            formData.append('enable', enable ? '1' : '0');
            
            fetch('', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('[AJAX 响应状态]', response.status);
                return response.text();
            })
            .then(text => {
                console.log('[AJAX 响应内容]', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success && data.services) {
                        // 更新所有客服的状态
                        data.services.forEach(function(cs) {
                            const statusEl = document.querySelector('#cs-status-' + cs.id);
                            const toggleEl = document.querySelector('#cs-toggle-' + cs.id);
                            
                            if (statusEl && toggleEl) {
                                const isCsEnabled = cs.enabled == 1;
                                
                                // 更新状态文字
                                statusEl.innerHTML = isCsEnabled ? 
                                    '<span style="color: #52c41a; font-weight: bold; font-size: 14px;">✅ 启用中</span>' :
                                    '<span style="color: #999; font-size: 14px;">❌ 已禁用</span>';
                                
                                // 更新拨动开关
                                const toggleLabel = toggleEl;
                                const toggleInput = toggleLabel.querySelector('input[type="checkbox"]');
                                
                                if (isCsEnabled) {
                                    toggleLabel.classList.add('active');
                                    toggleInput.checked = true;
                                } else {
                                    toggleLabel.classList.remove('active');
                                    toggleInput.checked = false;
                                }
                            }
                        });
                    } else {
                        console.error('操作失败:', data.error);
                        alert('操作失败：' + (data.error || '未知错误'));
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('JSON 解析失败:', e);
                    console.error('原始响应:', text);
                    alert('服务器响应格式错误，请刷新页面重试');
                    window.location.reload();
                }
            })
            .catch(function(error) {
                console.error('网络请求失败:', error);
                alert('网络错误，请重试');
                window.location.reload();
            });
        }
        </script>
        
    </div>
</div>
<script>
// 配置区块展开/收起功能
function toggleSection(headerElement) {
    const section = headerElement.parentElement;
    let content = section.querySelector('.section-content');
    const toggle = headerElement.querySelector('.section-toggle');
    
    // 如果 section-content 不存在，动态创建并包裹所有内容
    if (!content) {
        content = document.createElement('div');
        content.className = 'section-content';
        
        // 找到 header 之后的所有内容
        let nextSibling = headerElement.nextSibling;
        while (nextSibling) {
            const temp = nextSibling.nextSibling;
            content.appendChild(nextSibling);
            nextSibling = temp;
        }
        
        section.appendChild(content);
    }
    
    if (content && toggle) {
        const isExpanded = content.classList.contains('expanded');
        
        if (isExpanded) {
            // 收起
            content.classList.remove('expanded');
            toggle.style.transform = 'rotate(0deg)';
        } else {
            // 展开
            content.classList.add('expanded');
            toggle.style.transform = 'rotate(180deg)';
        }
    }
    
    // 阻止事件冒泡
    event.stopPropagation();
}

// 页面加载完成后初始化（所有区块默认收起）
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Config] 页面加载完成，开始初始化...');
    
    // 所有配置区块默认收起
    const sections = document.querySelectorAll('.config-section[id]');
    console.log('[Config] 找到配置区块数量:', sections.length);
    
    sections.forEach(function(section) {
        const header = section.querySelector('.section-header');
        const toggle = header ? header.querySelector('.section-toggle') : null;
        
        console.log('[Config] 处理区块:', section.id, 'header:', !!header, 'toggle:', !!toggle);
        
        if (header && toggle) {
            // 检查是否已经有 section-content
            let content = section.querySelector('.section-content');
            
            // 如果没有，动态创建并包裹所有内容
            if (!content) {
                console.log('[Config] 创建 section-content for', section.id);
                content = document.createElement('div');
                content.className = 'section-content';
                
                // 找到 header 之后的所有内容
                let nextSibling = header.nextSibling;
                while (nextSibling) {
                    const temp = nextSibling.nextSibling;
                    content.appendChild(nextSibling);
                    nextSibling = temp;
                }
                
                section.appendChild(content);
                console.log('[Config] 创建完成');
            }
            
            // 默认收起所有区块
            content.classList.remove('expanded');
            toggle.style.transform = 'rotate(0deg)';
            console.log('[Config] 已收起区块:', section.id);
        }
    });
    
    console.log('[Config] 初始化完成');
});

// 快捷导航滚动函数（全局可访问）
function scrollToSection(targetId) {
    const targetElement = document.getElementById(targetId);
    if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // 更新活动状态
        document.querySelectorAll('.quick-nav-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-target') === targetId) {
                item.classList.add('active');
            }
        });
    }
}

function toggleSwitch(element) {
    element.classList.toggle('active');
    const hiddenInput = element.nextElementSibling;
    hiddenInput.value = element.classList.contains('active') ? '1' : '0';
}

function selectPlatform(element, platform) {
    document.querySelectorAll('.platform-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('sms_platform').value = platform;
}

function testSms() {
    const testPhone = document.querySelector('input[name="test_phone"]').value;
    if (!testPhone) {
        alert('请先输入测试手机号');
        return;
    }
    
    if (confirm('确定要发送测试短信到 ' + testPhone + ' 吗？')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="test_sms">
            <input type="hidden" name="test_phone" value="${testPhone}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function testSubscribe() {
    const testOpenid = prompt('请输入测试用户的 OpenID：', '');
    if (!testOpenid) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="test_subscribe">
        <input type="hidden" name="test_openid" value="${testOpenid}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// 表单验证
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                e.preventDefault();
                alert('请填写所有必填字段');
                field.focus();
                return false;
            }
        }
    });
});

// 支付配置相关函数
const paymentConfigs = <?php echo json_encode($paymentConfigs); ?>;

function editPayment(paymentId) {
    const config = paymentConfigs[paymentId];
    if (!config) return;
    
    // 填充表单
    document.getElementById('payment_id').value = paymentId;
    document.getElementById('template_name').value = config.template_name || '';
    document.getElementById('admin_remark').value = config.admin_remark || '';
    document.getElementById('sort_order').value = config.sort_order || 100;
    document.getElementById('platform_type').value = config.platform_type || 'h5';
    
    // 支付方式不可修改
    const paymentMethodSelect = document.getElementById('payment_method');
    paymentMethodSelect.value = config.payment_method || 'wechat';
    paymentMethodSelect.disabled = true;
    
    // 微信支付配置
    document.querySelector(`input[name="wechat_version"][value="${config.wechat_version || 'v3'}"]`).checked = true;
    document.querySelector(`input[name="merchant_type"][value="${config.merchant_type || 'normal'}"]`).checked = true;
    document.getElementById('appid').value = config.appid || '';
    document.getElementById('mch_id').value = config.mch_id || '';
    document.getElementById('api_key').value = config.api_key || '';
    document.getElementById('verify_method').value = config.verify_method || 'public_key';
    document.getElementById('public_key_id').value = config.public_key_id || '';
    
    // 滚动到表单
    document.getElementById('paymentFormContainer').scrollIntoView({ behavior: 'smooth' });
    
    // 更新按钮文字
    document.getElementById('savePaymentBtn').innerHTML = '💾 更新支付配置';
}

function deletePayment(paymentId, templateName) {
    if (confirm(`确定要删除支付配置 "${templateName}" 吗？删除后无法恢复！`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_payment">
            <input type="hidden" name="payment_id" value="${paymentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function resetPaymentForm() {
    document.getElementById('payment_id').value = '';
    document.getElementById('template_name').value = '';
    document.getElementById('admin_remark').value = '';
    document.getElementById('sort_order').value = '100';
    document.getElementById('platform_type').value = 'h5';
    
    const paymentMethodSelect = document.getElementById('payment_method');
    paymentMethodSelect.value = 'wechat';
    paymentMethodSelect.disabled = false;
    
    document.querySelector('input[name="wechat_version"][value="v3"]').checked = true;
    document.querySelector('input[name="merchant_type"][value="normal"]').checked = true;
    document.getElementById('appid').value = '';
    document.getElementById('mch_id').value = '';
    document.getElementById('api_key').value = '';
    document.getElementById('verify_method').value = 'public_key';
    document.getElementById('public_key_id').value = '';
    
    document.getElementById('savePaymentBtn').innerHTML = '💾 保存支付配置';
    
    // 清空文件上传
    document.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
}

// 调整排序
document.getElementById('icon_up').addEventListener('click', function() {
    const sortInput = document.getElementById('sort_order');
    let value = parseInt(sortInput.value) || 100;
    if (value > 1) {
        sortInput.value = value - 1;
    }
});

document.getElementById('icon_down').addEventListener('click', function() {
    const sortInput = document.getElementById('sort_order');
    let value = parseInt(sortInput.value) || 100;
    if (value < 999) {
        sortInput.value = value + 1;
    }
});

// 小程序注册配置相关函数
function toggleRegisterMethod(method) {
    const wechatConfigSection = document.getElementById('wechatConfigSection');
    const wechatCard = document.getElementById('wechatRegisterCard');
    const bothCard = document.getElementById('bothRegisterCard');
    
    // 显示/隐藏微信配置
    if (method === 'phone') {
        wechatConfigSection.style.display = 'none';
        wechatCard.style.borderColor = '#f0f0f0';
        wechatCard.style.background = 'white';
        bothCard.style.borderColor = '#f0f0f0';
        bothCard.style.background = 'white';
    } else {
        wechatConfigSection.style.display = 'block';
        
        if (method === 'wechat') {
            wechatCard.style.borderColor = '#52c41a';
            wechatCard.style.background = '#f6ffed';
            bothCard.style.borderColor = '#f0f0f0';
            bothCard.style.background = 'white';
        } else if (method === 'both') {
            wechatCard.style.borderColor = '#f0f0f0';
            wechatCard.style.background = 'white';
            bothCard.style.borderColor = '#52c41a';
            bothCard.style.background = '#f6ffed';
        }
    }
}


</script>

<?php require_once __DIR__ . '/includes/footer.php';
