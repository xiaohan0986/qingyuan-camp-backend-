<?php
/**
 * 简单的 JWT 实现
 * 用于小程序登录 token 生成和验证
 */
class JWT {
    private static $secret = null;

    private static function getSecret() {
        if (self::$secret === null) {
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile) && !function_exists('env')) {
                $env = parse_ini_file($envFile);
                self::$secret = $env['JWT_SECRET'] ?? '';
            } elseif (function_exists('env')) {
                self::$secret = env('JWT_SECRET', '');
            }
            if (empty(self::$secret)) {
                self::$secret = 'qianwutong_jwt_secret_key_2026_change_this_in_prod';
            }
        }
        return self::$secret;
    }
    
    /**
     * 生成 JWT token
     * 
     * @param array $payload 载荷数据（包含 user_id, openid 等）
     * @param int $expireDays 有效期（天），默认 30 天
     * @return string JWT token
     */
    public static function generate($payload, $expireDays = 30) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time(); // 签发时间
        $payload['exp'] = time() + ($expireDays * 24 * 60 * 60); // 过期时间
        
        // Base64Url 编码
        $base64Header = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE));
        $base64Payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        // 生成签名
        $signature = hash_hmac('SHA256', "$base64Header.$base64Payload", self::getSecret(), true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    /**
     * 验证并解析 JWT token
     * 
     * @param string $token JWT token
     * @return array ['valid' => bool, 'payload' => array|null, 'error' => string|null]
     */
    public static function verify($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['valid' => false, 'payload' => null, 'error' => 'Invalid token format'];
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // 验证签名
        $signature = hash_hmac('SHA256', "$base64Header.$base64Payload", self::getSecret(), true);
        $base64SignatureCalc = self::base64UrlEncode($signature);
        
        if (!self::secureCompare($base64Signature, $base64SignatureCalc)) {
            return ['valid' => false, 'payload' => null, 'error' => 'Invalid signature'];
        }
        
        // 解析载荷
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload) {
            return ['valid' => false, 'payload' => null, 'error' => 'Invalid payload'];
        }
        
        // 检查过期时间
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token expired'];
        }
        
        return ['valid' => true, 'payload' => $payload, 'error' => null];
    }
    
    /**
     * Base64Url 编码
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64Url 解码
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * 公开方法用于测试
     */
    public static function decodePayload($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        return json_decode(self::base64UrlDecode($parts[1]), true);
    }
    
    /**
     * 安全比较字符串（防止时序攻击）
     */
    private static function secureCompare($a, $b) {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
}
