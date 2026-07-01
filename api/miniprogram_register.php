<?php
/**
 * 小程序注册 API
 * 支持手机号注册和微信号快捷登录
 * 自动获取微信号、昵称、头像
 * 注册必须绑定手机号
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载配置
$configFile = __DIR__ . '/../config/miniprogram.json';
if (!file_exists($configFile)) {
    echo json_encode(['code' => 500, 'message' => '小程序注册配置未设置，请先在后台配置']);
    exit;
}

$config = json_decode(file_get_contents($configFile), true) ?: [];

// 获取请求参数
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$code = $_POST['code'] ?? ''; // 微信登录 code
$phone = $_POST['phone'] ?? '';
$smsCode = $_POST['sms_code'] ?? '';
$encryptedData = $_POST['encrypted_data'] ?? '';
$iv = $_POST['iv'] ?? '';
$nickname = $_POST['nickname'] ?? '';
$avatar = $_POST['avatar'] ?? '';
$gender = $_POST['gender'] ?? 0;

// 验证注册方式
$registerMethod = $config['register_method'] ?? 'phone';
$requirePhone = $config['require_phone'] === '1';
$autoGetWechat = $config['auto_get_wechat'] === '1';
$autoGetNickname = $config['auto_get_nickname'] === '1';
$autoGetAvatar = $config['auto_get_avatar'] === '1';

// 处理不同动作
switch ($action) {
    case 'get_config':
        // 获取注册配置
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'register_method' => $registerMethod,
                'require_phone' => $requirePhone,
                'auto_get_wechat' => $autoGetWechat,
                'auto_get_nickname' => $autoGetNickname,
                'auto_get_avatar' => $autoGetAvatar,
                'supported_methods' => $registerMethod === 'both' ? ['phone', 'wechat'] : [$registerMethod]
            ]
        ]);
        break;
        
    case 'wechat_login':
        // 微信快捷登录
        if ($registerMethod === 'phone') {
            echo json_encode([
                'code' => 400,
                'message' => '当前仅支持手机号注册'
            ]);
            exit;
        }
        
        if (empty($code)) {
            echo json_encode([
                'code' => 400,
                'message' => '请提供微信登录 code'
            ]);
            exit;
        }
        
        // 调用微信 API 获取 openid
        $result = getWechatOpenid($code, $config);
        
        if ($result['code'] !== 200) {
            echo json_encode($result);
            exit;
        }
        
        $openid = $result['data']['openid'];
        $sessionKey = $result['data']['session_key'];
        
        // 解密用户信息
        $userInfo = [];
        if (!empty($encryptedData) && !empty($iv)) {
            $userInfo = decryptWechatUserInfo($encryptedData, $iv, $sessionKey);
        }
        
        // 检查用户是否已存在
        $user = findUserByOpenid($openid);
        
        if ($user) {
            // 用户已存在，返回登录态
            echo json_encode([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'user_id' => $user['user_id'],
                    'token' => generateToken($user['user_id']),
                    'is_new' => false,
                    'user_info' => [
                        'nickname' => $user['nickname'] ?? ($userInfo['nickName'] ?? ''),
                        'avatar' => $user['avatar'] ?? ($userInfo['avatarUrl'] ?? ''),
                        'phone' => $user['phone'] ?? ''
                    ]
                ]
            ]);
        } else {
            // 新用户，需要注册
            // 如果必须绑定手机号，返回需要绑定手机的提示
            if ($requirePhone) {
                echo json_encode([
                    'code' => 200,
                    'message' => '需要绑定手机号',
                    'data' => [
                        'openid' => $openid,
                        'session_key' => $sessionKey,
                        'is_new' => true,
                        'require_phone' => true,
                        'user_info' => [
                            'nickname' => $userInfo['nickName'] ?? '',
                            'avatar' => $userInfo['avatarUrl'] ?? '',
                            'gender' => $userInfo['gender'] ?? 0
                        ]
                    ]
                ]);
            } else {
                // 直接注册
                $userId = createNewUser([
                    'openid' => $openid,
                    'nickname' => $userInfo['nickName'] ?? '',
                    'avatar' => $userInfo['avatarUrl'] ?? '',
                    'gender' => $userInfo['gender'] ?? 0
                ]);
                
                echo json_encode([
                    'code' => 200,
                    'message' => '注册成功',
                    'data' => [
                        'user_id' => $userId,
                        'token' => generateToken($userId),
                        'is_new' => true,
                        'user_info' => [
                            'nickname' => $userInfo['nickName'] ?? '',
                            'avatar' => $userInfo['avatarUrl'] ?? '',
                            'phone' => ''
                        ]
                    ]
                ]);
            }
        }
        break;
        
    case 'register_by_phone':
        // 手机号注册
        if ($registerMethod === 'wechat') {
            echo json_encode([
                'code' => 400,
                'message' => '当前仅支持微信号快捷登录'
            ]);
            exit;
        }
        
        if (empty($phone)) {
            echo json_encode([
                'code' => 400,
                'message' => '请提供手机号'
            ]);
            exit;
        }
        
        if (empty($smsCode)) {
            echo json_encode([
                'code' => 400,
                'message' => '请提供短信验证码'
            ]);
            exit;
        }
        
        // 验证短信验证码
        $verifyResult = verifySmsCode($phone, $smsCode);
        if (!$verifyResult) {
            echo json_encode([
                'code' => 400,
                'message' => '短信验证码错误'
            ]);
            exit;
        }
        
        // 检查用户是否已存在
        $user = findUserByPhone($phone);
        
        if ($user) {
            // 用户已存在，返回登录态
            echo json_encode([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'user_id' => $user['user_id'],
                    'token' => generateToken($user['user_id']),
                    'is_new' => false
                ]
            ]);
        } else {
            // 新用户注册
            $userId = createNewUser([
                'phone' => $phone,
                'nickname' => $nickname ?? '用户' . substr($phone, -4),
                'avatar' => $avatar ?? ''
            ]);
            
            echo json_encode([
                'code' => 200,
                'message' => '注册成功',
                'data' => [
                    'user_id' => $userId,
                    'token' => generateToken($userId),
                    'is_new' => true
                ]
            ]);
        }
        break;
        
    case 'bind_phone':
        // 绑定手机号（微信登录后绑定）
        if (empty($phone) || empty($smsCode)) {
            echo json_encode([
                'code' => 400,
                'message' => '请提供手机号和验证码'
            ]);
            exit;
        }
        
        $openid = $_POST['openid'] ?? '';
        if (empty($openid)) {
            echo json_encode([
                'code' => 400,
                'message' => '请提供 openid'
            ]);
            exit;
        }
        
        // 验证短信验证码
        $verifyResult = verifySmsCode($phone, $smsCode);
        if (!$verifyResult) {
            echo json_encode([
                'code' => 400,
                'message' => '短信验证码错误'
            ]);
            exit;
        }
        
        // 检查手机号是否已被绑定
        $existUser = findUserByPhone($phone);
        if ($existUser) {
            echo json_encode([
                'code' => 400,
                'message' => '该手机号已被绑定'
            ]);
            exit;
        }
        
        // 绑定手机号
        $result = bindPhoneToUser($openid, $phone);
        
        if ($result) {
            echo json_encode([
                'code' => 200,
                'message' => '绑定成功',
                'data' => [
                    'user_id' => $result['user_id'],
                    'token' => generateToken($result['user_id']),
                    'phone' => $phone
                ]
            ]);
        } else {
            echo json_encode([
                'code' => 500,
                'message' => '绑定失败'
            ]);
        }
        break;
        
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作类型']);
        break;
}

/**
 * 获取微信 openid
 */
function getWechatOpenid($code, $config) {
    $appid = $config['wechat_appid'] ?? '';
    $secret = $config['wechat_secret'] ?? '';
    
    if (empty($appid) || empty($secret)) {
        return [
            'code' => 500,
            'message' => '微信配置不完整，请先配置 AppID 和 AppSecret'
        ];
    }
    
    $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['errcode'])) {
        return [
            'code' => 500,
            'message' => '微信登录失败：' . ($data['errmsg'] ?? '未知错误')
        ];
    }
    
    return [
        'code' => 200,
        'message' => 'success',
        'data' => [
            'openid' => $data['openid'],
            'session_key' => $data['session_key']
        ]
    ];
}

/**
 * 解密微信用户信息
 */
function decryptWechatUserInfo($encryptedData, $iv, $sessionKey) {
    // 需要安装 openssl 扩展
    if (!function_exists('openssl_decrypt')) {
        return [];
    }
    
    $aesKey = base64_decode($sessionKey);
    $iv = base64_decode($iv);
    $encryptedData = base64_decode($encryptedData);
    
    $decrypted = openssl_decrypt(
        $encryptedData,
        'AES-128-CBC',
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($decrypted === false) {
        return [];
    }
    
    $userInfo = json_decode($decrypted, true);
    return $userInfo ?: [];
}

/**
 * 查找用户（通过 openid）
 */
function findUserByOpenid($openid) {
    // TODO: 实现数据库查询
    // 示例代码：
    // $db = getDatabase();
    // $stmt = $db->prepare("SELECT * FROM users WHERE openid = ?");
    // $stmt->execute([$openid]);
    // return $stmt->fetch(PDO::FETCH_ASSOC);
    return null;
}

/**
 * 查找用户（通过手机号）
 */
function findUserByPhone($phone) {
    // TODO: 实现数据库查询
    return null;
}

/**
 * 创建新用户
 */
function createNewUser($data) {
    // TODO: 实现数据库插入
    $userId = 'user_' . time() . '_' . mt_rand(1000, 9999);
    
    // 示例：
    // $db = getDatabase();
    // $stmt = $db->prepare("INSERT INTO users (openid, phone, nickname, avatar, gender, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    // $stmt->execute([
    //     $data['openid'] ?? null,
    //     $data['phone'] ?? null,
    //     $data['nickname'] ?? '',
    //     $data['avatar'] ?? '',
    //     $data['gender'] ?? 0,
    //     date('Y-m-d H:i:s')
    // ]);
    // return $db->lastInsertId();
    
    return $userId;
}

/**
 * 生成用户 token
 */
function generateToken($userId) {
    // TODO: 实现 token 生成逻辑
    return bin2hash($userId . '_' . time() . '_' . mt_rand(1000, 9999));
}

/**
 * 验证短信验证码
 */
function verifySmsCode($phone, $code) {
    // TODO: 实现验证码验证逻辑
    // 可以调用 api/sms.php 中的 verify_code 接口
    return true; // 演示模式直接返回成功
}

/**
 * 绑定手机号到用户
 */
function bindPhoneToUser($openid, $phone) {
    // TODO: 实现数据库更新
    // $db = getDatabase();
    // $stmt = $db->prepare("UPDATE users SET phone = ? WHERE openid = ?");
    // $stmt->execute([$phone, $openid]);
    // return findUserByOpenid($openid);
    return ['user_id' => 'user_' . time()];
}

/**
 * 生成 hash
 */
function bin2hash($data) {
    return hash('sha256', $data);
}
