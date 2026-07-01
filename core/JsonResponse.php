<?php
/**
 * 统一 JSON 响应
 * 
 * 用法：
 *   JsonResponse::success($data, '操作成功');
 *   JsonResponse::fail('参数错误', 400);
 *   JsonResponse::success($data)->code(201);
 * 
 * 响应格式：
 *   { "code": 200, "message": "success", "data": {...} }
 */
class JsonResponse
{
    private int $code = 200;
    private string $message = 'success';
    private mixed $data = null;
    private array $extra = [];

    /**
     * 成功响应
     */
    public static function success(mixed $data = null, string $message = 'success'): self
    {
        $instance = new self();
        $instance->code = 200;
        $instance->message = $message;
        $instance->data = $data;
        return $instance;
    }

    /**
     * 失败响应
     */
    public static function fail(string $message = 'fail', int $code = 400, mixed $data = null): self
    {
        $instance = new self();
        $instance->code = $code;
        $instance->message = $message;
        $instance->data = $data;
        return $instance;
    }

    /**
     * 错误响应（服务端错误）
     */
    public static function error(string $message = 'server error', int $code = 500): self
    {
        return self::fail($message, $code);
    }

    /**
     * 未授权
     */
    public static function unauthorized(string $message = '未登录或 Token 已过期'): self
    {
        return self::fail($message, 401);
    }

    /**
     * 无权限
     */
    public static function forbidden(string $message = '无权限访问'): self
    {
        return self::fail($message, 403);
    }

    /**
     * 未找到
     */
    public static function notFound(string $message = '资源不存在'): self
    {
        return self::fail($message, 404);
    }

    /**
     * 参数验证失败
     */
    public static function validateFail(string $message, array $errors = []): self
    {
        $instance = self::fail($message, 422);
        if (!empty($errors)) {
            $instance->extra['errors'] = $errors;
        }
        return $instance;
    }

    /**
     * 设置状态码
     */
    public function code(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置消息
     */
    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 附加额外字段
     */
    public function with(string $key, mixed $value): self
    {
        $this->extra[$key] = $value;
        return $this;
    }

    /**
     * 分页数据
     */
    public static function page(array $list, int $total, int $page, int $pageSize): self
    {
        return self::success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => (int)ceil($total / $pageSize),
        ]);
    }

    /**
     * 发送 JSON 响应并终止
     */
    public function send(): never
    {
        // 清除之前的输出
        if (ob_get_level()) ob_clean();
        
        http_response_code($this->code >= 100 && $this->code < 600 ? $this->code : 200);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $body = [
            'code' => $this->code,
            'message' => $this->message,
        ];
        
        if ($this->data !== null) {
            $body['data'] = $this->data;
        }
        
        if (!empty($this->extra)) {
            $body = array_merge($body, $this->extra);
        }

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * 转为数组（不终止）
     */
    public function toArray(): array
    {
        $body = [
            'code' => $this->code,
            'message' => $this->message,
        ];
        if ($this->data !== null) {
            $body['data'] = $this->data;
        }
        return array_merge($body, $this->extra);
    }
}
