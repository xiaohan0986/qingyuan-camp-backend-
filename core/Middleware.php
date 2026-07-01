<?php
/**
 * 中间件接口
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): void;
}

/**
 * API 认证中间件
 * 验证 Bearer Token (JWT)
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): void
    {
        $token = $request->bearerToken();
        
        if (empty($token)) {
            JsonResponse::unauthorized('请先登录')->send();
        }

        require_once INCLUDES_PATH . 'JWT.php';
        
        try {
            $payload = JWT::decode($token);
            if (!$payload || !isset($payload['user_id'])) {
                JsonResponse::unauthorized('Token 无效或已过期')->send();
            }
            // 将用户信息附加到请求
            $request->setRouteParams(array_merge(
                $request->all(),
                ['_auth_user' => $payload]
            ));
        } catch (\Exception $e) {
            JsonResponse::unauthorized('Token 验证失败: ' . $e->getMessage())->send();
        }

        $next();
    }
}

/**
 * CORS 中间件
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($request->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $next();
    }
}
