<?php
/**
 * 请求封装
 * 统一获取请求参数、支持 GET/POST/JSON 输入
 */
class Request
{
    private static ?self $instance = null;

    /** @var array 合并后的请求参数 */
    private array $params = [];
    
    /** @var array 路由参数 */
    private array $routeParams = [];
    
    /** @var array 请求头 */
    private array $headers = [];

    private function __construct()
    {
        // 合并 GET + POST
        $this->params = array_merge($_GET, $_POST);
        
        // 处理 JSON 请求体
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                $this->params = array_merge($this->params, $json);
            }
        }
        
        // 提取请求头
        if (function_exists('getallheaders')) {
            $this->headers = getallheaders() ?: [];
        } else {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerKey = str_replace('_', '-', substr($key, 5));
                    $this->headers[$headerKey] = $value;
                }
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取请求方法
     */
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * 是否 GET 请求
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * 是否 POST 请求
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * 是否 PUT 请求
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * 是否 DELETE 请求
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * 获取请求路径（去掉查询字符串和基础路径）
     */
    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        return $uri ?: '/';
    }

    /**
     * 获取单个参数
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * 获取所有参数
     */
    public function all(): array
    {
        return $this->params;
    }

    /**
     * 只获取指定参数列表
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->params[$key] ?? null;
        }
        return $result;
    }

    /**
     * 排除某些参数
     */
    public function except(array $keys): array
    {
        $result = $this->params;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        return $result;
    }

    /**
     * 判断参数是否存在
     */
    public function has(string $key): bool
    {
        return isset($this->params[$key]);
    }

    /**
     * 获取整数值
     */
    public function int(string $key, int $default = 0): int
    {
        $val = $this->params[$key] ?? null;
        return $val !== null && $val !== '' ? (int)$val : $default;
    }

    /**
     * 获取浮点值
     */
    public function float(string $key, float $default = 0.0): float
    {
        $val = $this->params[$key] ?? null;
        return $val !== null && $val !== '' ? (float)$val : $default;
    }

    /**
     * 获取字符串值（自动 trim）
     */
    public function string(string $key, string $default = ''): string
    {
        $val = $this->params[$key] ?? null;
        return $val !== null ? trim((string)$val) : $default;
    }

    /**
     * 获取布尔值
     */
    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->params[$key] ?? null;
        if ($val === null || $val === '') return $default;
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 获取数组值
     */
    public function array(string $key, array $default = []): array
    {
        $val = $this->params[$key] ?? null;
        if (is_array($val)) return $val;
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) return $decoded;
        }
        return $default;
    }

    /**
     * 设置路由参数
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
        $this->params = array_merge($this->params, $params);
    }

    /**
     * 获取路由参数
     */
    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * 获取请求头
     */
    public function header(string $key, string $default = ''): string
    {
        $key = strtolower($key);
        foreach ($this->headers as $hk => $hv) {
            if (strtolower($hk) === $key) {
                return $hv;
            }
        }
        return $default;
    }

    /**
     * 获取 Bearer Token
     */
    public function bearerToken(): string
    {
        $auth = $this->header('Authorization');
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * 获取客户端 IP
     */
    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * 获取 User-Agent
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * 获取上传文件
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * 获取分页参数
     */
    public function getPage(): array
    {
        $page = max(1, $this->int('page', 1));
        $pageSize = min(100, max(1, $this->int('pageSize', 20)));
        return [$page, $pageSize];
    }
}
