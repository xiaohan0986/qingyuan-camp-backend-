<?php
/**
 * 短信发送 API
 * 支持阿里云、腾讯云、七牛云短信平台
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载配置
$configFile = __DIR__ . '/../config/sms.json';
if (!file_exists($configFile)) {
    echo json_encode(['code' => 500, 'message' => '短信配置未设置，请先配置短信平台']);
    exit;
}

$smsConfig = json_decode(file_get_contents($configFile), true);

// 检查是否开启短信服务
if ($smsConfig['enabled'] !== '1') {
    echo json_encode(['code' => 500, 'message' => '短信服务未开启']);
    exit;
}

// 获取请求参数
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$phone = $_POST['phone'] ?? $_GET['phone'] ?? '';
$code = $_POST['code'] ?? '';

// 验证手机号
if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode(['code' => 400, 'message' => '请输入正确的手机号']);
    exit;
}

// 处理不同动作
switch ($action) {
    case 'send_code':
        // 发送验证码
        if (empty($code)) {
            $code = sprintf('%06d', mt_rand(100000, 999999));
        }
        
        // 存储验证码到 session/cache（这里用简单示例）
        session_start();
        $_SESSION['sms_code_' . $phone] = $code;
        $_SESSION['sms_code_time_' . $phone] = time();
        
        // 发送短信
        $result = sendSms($phone, $code, $smsConfig);
        
        if ($result['success']) {
            echo json_encode([
                'code' => 200,
                'message' => '验证码发送成功',
                'data' => [
                    'expire_in' => 300 // 5 分钟有效期
                ]
            ]);
        } else {
            echo json_encode([
                'code' => 500,
                'message' => '验证码发送失败：' . $result['message']
            ]);
        }
        break;
        
    case 'verify_code':
        // 验证验证码
        session_start();
        $savedCode = $_SESSION['sms_code_' . $phone] ?? '';
        $savedTime = $_SESSION['sms_code_time_' . $phone] ?? 0;
        
        if (empty($savedCode)) {
            echo json_encode(['code' => 400, 'message' => '验证码已过期，请重新获取']);
            exit;
        }
        
        // 检查是否过期（5 分钟）
        if (time() - $savedTime > 300) {
            unset($_SESSION['sms_code_' . $phone]);
            unset($_SESSION['sms_code_time_' . $phone]);
            echo json_encode(['code' => 400, 'message' => '验证码已过期，请重新获取']);
            exit;
        }
        
        // 验证验证码
        if ($code === $savedCode) {
            // 验证成功后删除验证码
            unset($_SESSION['sms_code_' . $phone]);
            unset($_SESSION['sms_code_time_' . $phone]);
            
            echo json_encode([
                'code' => 200,
                'message' => '验证码验证成功'
            ]);
        } else {
            echo json_encode([
                'code' => 400,
                'message' => '验证码错误'
            ]);
        }
        break;
        
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作类型']);
        break;
}

/**
 * 发送短信
 * @param string $phone 手机号
 * @param string $code 验证码
 * @param array $config 短信配置
 * @return array ['success' => bool, 'message' => string]
 */
function sendSms($phone, $code, $config) {
    $platform = $config['platform'] ?? 'aliyun';
    
    switch ($platform) {
        case 'aliyun':
            return sendSmsAliyun($phone, $code, $config);
        case 'tencent':
            return sendSmsTencent($phone, $code, $config);
        case 'qiniu':
            return sendSmsQiniu($phone, $code, $config);
        default:
            return ['success' => false, 'message' => '不支持的短信平台'];
    }
}

/**
 * 阿里云短信发送
 */
function sendSmsAliyun($phone, $code, $config) {
    // 检查配置是否完整
    if (empty($config['access_key_id']) || empty($config['access_key_secret']) || 
        empty($config['sign_name']) || empty($config['template_code'])) {
        return ['success' => false, 'message' => '阿里云短信配置不完整'];
    }
    
    // 这里是阿里云短信 API 调用示例
    // 实际使用时需要安装阿里云 SDK: composer require aliyuncs/dfs-sdk-php
    /*
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $accessKeyId = $config['access_key_id'];
    $accessKeySecret = $config['access_key_secret'];
    $signName = $config['sign_name'];
    $templateCode = $config['template_code'];
    
    // 使用阿里云 SDK 发送
    // ... SDK 调用代码 ...
    
    return ['success' => true, 'message' => '发送成功'];
    */
    
    // 演示模式：记录日志
    $logFile = __DIR__ . '/../logs/sms_send.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] 阿里云短信 | 手机：%s | 验证码：%s | 签名：%s | 模板：%s\n",
        date('Y-m-d H:i:s'),
        $phone,
        $code,
        $config['sign_name'],
        $config['template_code']
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // 开发环境直接返回成功
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        return ['success' => true, 'message' => '本地环境，短信已记录到日志'];
    }
    
    return ['success' => false, 'message' => '生产环境需要配置阿里云 SDK 和真实凭证'];
}

/**
 * 腾讯云短信发送
 */
function sendSmsTencent($phone, $code, $config) {
    if (empty($config['access_key_id']) || empty($config['access_key_secret']) || 
        empty($config['sign_name']) || empty($config['template_code'])) {
        return ['success' => false, 'message' => '腾讯云短信配置不完整'];
    }
    
    // 腾讯云 SMS API 调用示例
    // 需要安装腾讯云 SDK: composer require tencentcloud/sms
    
    $logFile = __DIR__ . '/../logs/sms_send.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] 腾讯云短信 | 手机：%s | 验证码：%s | 签名：%s | 模板：%s\n",
        date('Y-m-d H:i:s'),
        $phone,
        $code,
        $config['sign_name'],
        $config['template_code']
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        return ['success' => true, 'message' => '本地环境，短信已记录到日志'];
    }
    
    return ['success' => false, 'message' => '生产环境需要配置腾讯云 SDK 和真实凭证'];
}

/**
 * 七牛云短信发送
 */
function sendSmsQiniu($phone, $code, $config) {
    if (empty($config['access_key_id']) || empty($config['access_key_secret']) || 
        empty($config['sign_name']) || empty($config['template_code'])) {
        return ['success' => false, 'message' => '七牛云短信配置不完整'];
    }
    
    // 七牛云 SMS API 调用示例
    // 需要安装七牛云 SDK: composer require qiniu/php-sdk
    
    $logFile = __DIR__ . '/../logs/sms_send.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] 七牛云短信 | 手机：%s | 验证码：%s | 签名：%s | 模板：%s\n",
        date('Y-m-d H:i:s'),
        $phone,
        $code,
        $config['sign_name'],
        $config['template_code']
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        return ['success' => true, 'message' => '本地环境，短信已记录到日志'];
    }
    
    return ['success' => false, 'message' => '生产环境需要配置七牛云 SDK 和真实凭证'];
}
