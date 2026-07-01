<?php
/**
 * 支付 API 接口
 * 支持微信小程序、H5、微信公众号、APP 四种支付方式
 * 支持微信支付和支付宝
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载配置
$configFile = __DIR__ . '/../config/payment.json';
if (!file_exists($configFile)) {
    echo json_encode(['code' => 500, 'message' => '支付配置未设置，请先在后台配置支付方式']);
    exit;
}

$paymentConfigs = json_decode(file_get_contents($configFile), true) ?: [];

// 获取请求参数
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$paymentId = $_POST['payment_id'] ?? $_GET['payment_id'] ?? '';
$orderNo = $_POST['order_no'] ?? '';
$amount = $_POST['amount'] ?? 0;
$phone = $_POST['phone'] ?? '';
$openId = $_POST['open_id'] ?? '';

// 验证支付配置
if (!empty($paymentId) && !isset($paymentConfigs[$paymentId])) {
    echo json_encode(['code' => 400, 'message' => '无效的支付配置 ID']);
    exit;
}

// 处理不同动作
switch ($action) {
    case 'list':
        // 获取支付列表
        $platformType = $_GET['platform_type'] ?? '';
        $list = [];
        
        foreach ($paymentConfigs as $payId => $config) {
            if (empty($platformType) || $config['platform_type'] === $platformType) {
                $list[$payId] = [
                    'payment_id' => $config['payment_id'],
                    'template_name' => $config['template_name'],
                    'platform_type' => $config['platform_type'],
                    'payment_method' => $config['payment_method'],
                    'sort_order' => $config['sort_order']
                ];
            }
        }
        
        // 按排序排序
        usort($list, function($a, $b) {
            return $a['sort_order'] - $b['sort_order'];
        });
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $list
        ]);
        break;
        
    case 'get':
        // 获取支付配置详情（不返回敏感信息）
        if (empty($paymentId)) {
            echo json_encode(['code' => 400, 'message' => '请提供支付配置 ID']);
            exit;
        }
        
        $config = $paymentConfigs[$paymentId];
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'payment_id' => $config['payment_id'],
                'template_name' => $config['template_name'],
                'platform_type' => $config['platform_type'],
                'payment_method' => $config['payment_method'],
                'wechat_version' => $config['wechat_version'] ?? 'v3',
                'appid' => $config['appid'] ?? '',
                'mch_id' => $config['mch_id'] ?? ''
            ]
        ]);
        break;
        
    case 'create_order':
        // 创建支付订单
        if (empty($paymentId)) {
            echo json_encode(['code' => 400, 'message' => '请提供支付配置 ID']);
            exit;
        }
        
        if (empty($orderNo)) {
            echo json_encode(['code' => 400, 'message' => '请提供订单号']);
            exit;
        }
        
        if (empty($amount) || $amount <= 0) {
            echo json_encode(['code' => 400, 'message' => '订单金额必须大于 0']);
            exit;
        }
        
        $config = $paymentConfigs[$paymentId];
        
        // 根据支付方式和平台调用不同的支付接口
        if ($config['payment_method'] === 'wechat') {
            $result = createWechatOrder($config, $orderNo, $amount, $openId);
        } else {
            $result = createAlipayOrder($config, $orderNo, $amount);
        }
        
        echo json_encode($result);
        break;
        
    case 'query_order':
        // 查询订单状态
        if (empty($orderNo)) {
            echo json_encode(['code' => 400, 'message' => '请提供订单号']);
            exit;
        }
        
        // TODO: 实现订单查询逻辑
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'order_no' => $orderNo,
                'status' => 'PENDING', // PENDING, SUCCESS, FAILED
                'amount' => $amount
            ]
        ]);
        break;
        
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作类型']);
        break;
}

/**
 * 创建微信支付订单
 */
function createWechatOrder($config, $orderNo, $amount, $openId = '') {
    $version = $config['wechat_version'] ?? 'v3';
    $platformType = $config['platform_type'] ?? 'h5';
    
    // 根据版本和平台类型调用不同的接口
    if ($version === 'v3') {
        return createWechatV3Order($config, $orderNo, $amount, $openId, $platformType);
    } else {
        return createWechatV2Order($config, $orderNo, $amount, $openId, $platformType);
    }
}

/**
 * 微信支付 V3 下单
 */
function createWechatV3Order($config, $orderNo, $amount, $openId, $platformType) {
    // 检查必要配置
    if (empty($config['appid']) || empty($config['mch_id']) || empty($config['api_key'])) {
        return [
            'code' => 500,
            'message' => '微信支付配置不完整，请检查 AppID、商户号和 API 密钥'
        ];
    }
    
    // 根据平台类型选择下单方式
    switch ($platformType) {
        case 'miniprogram':
            // 小程序支付
            if (empty($openId)) {
                return [
                    'code' => 400,
                    'message' => '小程序支付需要提供 OpenID'
                ];
            }
            
            // TODO: 调用微信小程序支付 API
            /*
            $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';
            $data = [
                'appid' => $config['appid'],
                'mchid' => $config['mch_id'],
                'description' => '商品支付',
                'out_trade_no' => $orderNo,
                'notify_url' => 'https://your-domain.com/api/payment_notify.php',
                'amount' => [
                    'total' => intval($amount * 100), // 转换为分
                    'currency' => 'CNY'
                ],
                'payer' => [
                    'openid' => $openId
                ]
            ];
            
            // 使用 API v3 签名和请求
            // ...
            */
            
            return [
                'code' => 200,
                'message' => '订单创建成功（演示模式）',
                'data' => [
                    'appId' => $config['appid'],
                    'timeStamp' => time(),
                    'nonceStr' => bin2hex(random_bytes(16)),
                    'package' => 'prepay_id=xxx',
                    'signType' => 'RSA',
                    'paySign' => 'xxx'
                ]
            ];
            
        case 'h5':
            // H5 支付
            // TODO: 调用微信 H5 支付 API
            return [
                'code' => 200,
                'message' => '订单创建成功（演示模式）',
                'data' => [
                    'mweb_url' => 'https://wx.tenpay.com/cgi-bin/mmpayweb-bin/checkmweb?prepay_id=xxx'
                ]
            ];
            
        case 'wechat_mp':
            // 公众号支付
            if (empty($openId)) {
                return [
                    'code' => 400,
                    'message' => '公众号支付需要提供 OpenID'
                ];
            }
            
            // TODO: 调用微信公众号支付 API
            return [
                'code' => 200,
                'message' => '订单创建成功（演示模式）',
                'data' => [
                    'appId' => $config['appid'],
                    'timeStamp' => time(),
                    'nonceStr' => bin2hex(random_bytes(16)),
                    'package' => 'prepay_id=xxx',
                    'signType' => 'MD5',
                    'paySign' => 'xxx'
                ]
            ];
            
        case 'app':
            // APP 支付
            // TODO: 调用微信 APP 支付 API
            return [
                'code' => 200,
                'message' => '订单创建成功（演示模式）',
                'data' => [
                    'appid' => $config['appid'],
                    'partnerid' => $config['mch_id'],
                    'prepayid' => 'xxx',
                    'package' => 'Sign=WXPay',
                    'noncestr' => bin2hex(random_bytes(16)),
                    'timestamp' => time(),
                    'sign' => 'xxx'
                ]
            ];
            
        default:
            return [
                'code' => 400,
                'message' => '不支持的平台类型'
            ];
    }
}

/**
 * 微信支付 V2 下单（兼容旧版）
 */
function createWechatV2Order($config, $orderNo, $amount, $openId, $platformType) {
    // V2 版本已不再推荐使用
    return [
        'code' => 500,
        'message' => '微信支付 V2 版本已不再支持，请使用 V3 版本'
    ];
}

/**
 * 创建支付宝订单
 */
function createAlipayOrder($config, $orderNo, $amount) {
    // 检查必要配置
    if (empty($config['appid']) || empty($config['mch_id']) || empty($config['api_key'])) {
        return [
            'code' => 500,
            'message' => '支付宝配置不完整'
        ];
    }
    
    // TODO: 调用支付宝 API
    /*
    require_once __DIR__ . '/../vendor/alipay-sdk-php/alipay.php';
    
    $alipay = new Alipay([
        'app_id' => $config['appid'],
        'merchant_private_key' => $config['api_key'],
        'alipay_public_key' => $config['alipay_public_key'] ?? ''
    ]);
    
    // 根据平台类型选择支付方式
    switch ($config['platform_type']) {
        case 'h5':
            // 手机网站支付
            $result = $alipay->wap($orderNo, $amount, '商品标题');
            break;
        case 'app':
            // APP 支付
            $result = $alipay->app($orderNo, $amount, '商品标题');
            break;
        default:
            // 默认电脑网站支付
            $result = $alipay->web($orderNo, $amount, '商品标题');
            break;
    }
    */
    
    return [
        'code' => 200,
        'message' => '订单创建成功（演示模式）',
        'data' => [
            'order_no' => $orderNo,
            'amount' => $amount,
            'pay_url' => 'https://openapi.alipay.com/gateway.do?xxx'
        ]
    ];
}
