<?php
class PasswordHelper {
    public static function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
    public static function generate($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
