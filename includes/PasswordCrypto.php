<?php
/**
 * 简单的加密工具类（用于密码存储）
 * 使用 OpenSSL 进行可逆加密
 */
class PasswordCrypto {
    private static $cipher = 'aes-128-cbc';
    private static $key = 'QianWuTong2026!!'; // 16 字节密钥
    private static $iv = 'QianWuTongKey!@#'; // 16 字节 IV
    
    /**
     * 加密密码
     * @param string $password 明文密码
     * @return string 加密后的密码（base64 编码）
     */
    public static function encrypt($password) {
        $encrypted = openssl_encrypt(
            $password,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );
        return base64_encode($encrypted);
    }
    
    /**
     * 解密密码
     * @param string $encrypted 加密的密码（base64 编码）
     * @return string|false 解密后的明文密码，失败返回 false
     */
    public static function decrypt($encrypted) {
        $decrypted = openssl_decrypt(
            base64_decode($encrypted),
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );
        return $decrypted;
    }
}
