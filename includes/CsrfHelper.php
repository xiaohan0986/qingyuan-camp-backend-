<?php
/**
 * CSRF 防护工具类
 * 实现 Token 生成、验证，防止跨站请求伪造攻击
 */
class CsrfHelper {
    
    /**
     * 生成并存储 CSRF Token
     * @return string
     */
    public static function generate() {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 获取当前 CSRF Token
     * @return string|null
     */
    public static function getToken() {
        self::initSession();
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * 验证 CSRF Token
     * @param string|null $token 前端提交的 token
     * @return bool
     */
    public static function verify($token) {
        self::initSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * 验证 POST 请求中的 CSRF Token
     * 若验证失败则终止请求并返回 JSON 错误
     */
    public static function checkPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::verify($token)) {
            http_response_code(403);
            echo json_encode([
                'code' => 403,
                'msg' => 'CSRF token 验证失败，请刷新页面后重试'
            ]);
            exit;
        }
    }
    
    /**
     * 输出隐藏的 CSRF Token 字段
     * 用于表单中
     * @return string
     */
    public static function hiddenField() {
        $token = self::generate();
        $escaped = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $escaped . '">';
    }
    
    /**
     * 输出 CSRF 元标签（供 JavaScript 读取）
     * @return string
     */
    public static function metaTag() {
        $token = self::generate();
        return '<meta name="csrf-token" content="' . $token . '">';
    }
    
    private static function initSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // 仅在未输出任何内容前启动会话，避免 warning
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
