<?php
/**
 * 轻量路由器
 * 
 * 支持 RESTful 风格路由：
 *   Router::get('/product/:id', 'ProductController@show');
 *   Router::post('/product', 'ProductController@store');
 *   Router::resource('/product', 'ProductController');
 * 
 * 路由分组：
 *   Router::group('/adminapi', function() {
 *       Router::get('/product', 'adminapi.Product@index');
 *   });
 */
class Router
{
    /** @var array 已注册路由 */
    private static array $routes = [];
    
    /** @var string 当前分组前缀 */
    private static string $groupPrefix = '';
    
    /** @var array 当前分组中间件 */
    private static array $groupMiddleware = [];

    // ========== HTTP 方法注册 ==========

    public static function get(string $uri, string $handler, array $middleware = []): void
    {
        self::addRoute('GET', $uri, $handler, $middleware);
    }

    public static function post(string $uri, string $handler, array $middleware = []): void
    {
        self::addRoute('POST', $uri, $handler, $middleware);
    }

    public static function put(string $uri, string $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $uri, $handler, $middleware);
    }

    public static function delete(string $uri, string $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $uri, $handler, $middleware);
    }

    public static function any(string $uri, string $handler, array $middleware = []): void
    {
        self::addRoute('ANY', $uri, $handler, $middleware);
    }

    /**
     * RESTful 资源路由
     * 自动生成 index / show / store / update / destroy
     */
    public static function resource(string $uri, string $controller, array $middleware = []): void
    {
        $uri = rtrim($uri, '/');
        self::get($uri, "{$controller}@index", $middleware);
        self::get("{$uri}/:id", "{$controller}@show", $middleware);
        self::post($uri, "{$controller}@store", $middleware);
        self::put("{$uri}/:id", "{$controller}@update", $middleware);
        self::delete("{$uri}/:id", "{$controller}@destroy", $middleware);
    }

    // ========== 路由分组 ==========

    /**
     * 路由分组
     */
    public static function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $oldPrefix = self::$groupPrefix;
        $oldMiddleware = self::$groupMiddleware;
        
        self::$groupPrefix .= rtrim($prefix, '/');
        self::$groupMiddleware = array_merge(self::$groupMiddleware, $middleware);
        
        $callback();
        
        self::$groupPrefix = $oldPrefix;
        self::$groupMiddleware = $oldMiddleware;
    }

    // ========== 注册与匹配 ==========

    private static function addRoute(string $method, string $uri, string $handler, array $middleware): void
    {
        $fullUri = self::$groupPrefix . '/' . trim($uri, '/');
        $fullUri = '/' . trim($fullUri, '/');
        $fullMiddleware = array_merge(self::$groupMiddleware, $middleware);

        // 将 :param 转为正则捕获组
        $pattern = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $fullUri);
        $pattern = '#^' . $pattern . '$#';

        self::$routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $fullMiddleware,
        ];
    }

    // ========== 调度 ==========

    /**
     * 匹配并执行路由
     */
    public static function dispatch(): void
    {
        $request = Request::getInstance();
        $path = $request->path();
        $method = $request->method();
        $allowedMethods = [];

        foreach (self::$routes as $route) {
            // 检查方法匹配
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                if (preg_match($route['pattern'], $path)) {
                    $allowedMethods[$route['method']] = true;
                }
                continue;
            }

            // 正则匹配
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            // 提取路由参数
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            $request->setRouteParams($params);

            // 执行中间件链
            $handler = $route['handler'];
            $middleware = $route['middleware'];
            
            if (!empty($middleware)) {
                self::runMiddleware($middleware, function() use ($handler, $params) {
                    self::executeHandler($handler, $params);
                });
            } else {
                self::executeHandler($handler, $params);
            }
            return;
        }

        // 未匹配到路由
        if (!empty($allowedMethods)) {
            JsonResponse::fail('Method Not Allowed', 405)->send();
        }
        JsonResponse::notFound('路由未找到: ' . $path)->send();
    }

    /**
     * 执行中间件链
     */
    private static function runMiddleware(array $middleware, callable $next): void
    {
        if (empty($middleware)) {
            $next();
            return;
        }

        $middlewareClass = array_shift($middleware);
        
        if (is_string($middlewareClass) && class_exists($middlewareClass)) {
            $instance = new $middlewareClass();
            $instance->handle(Request::getInstance(), function() use ($middleware, $next) {
                self::runMiddleware($middleware, $next);
            });
        } elseif (is_callable($middlewareClass)) {
            $middlewareClass(Request::getInstance(), function() use ($middleware, $next) {
                self::runMiddleware($middleware, $next);
            });
        } else {
            // 跳过无效中间件
            self::runMiddleware($middleware, $next);
        }
    }

    /**
     * 执行控制器方法
     * handler 格式: "ControllerClass@method" 或 "ControllerClass"
     */
    private static function executeHandler(string $handler, array $routeParams): void
    {
        if (strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
        } else {
            $class = $handler;
            $method = 'index';
        }

        if (!class_exists($class)) {
            JsonResponse::error("控制器不存在: {$class}")->send();
        }

        $controller = new $class();
        
        if (!method_exists($controller, $method)) {
            JsonResponse::error("方法不存在: {$class}@{$method}")->send();
        }

        // 调用方法，注入路由参数
        $reflection = new ReflectionMethod($controller, $method);
        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (isset($routeParams[$name])) {
                $args[] = $routeParams[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        $result = $reflection->invokeArgs($controller, $args);

        // 如果返回 JsonResponse，发送它
        if ($result instanceof JsonResponse) {
            $result->send();
        }
        
        // 如果是数组，包装为成功响应
        if (is_array($result)) {
            JsonResponse::success($result)->send();
        }
    }

    /**
     * 导入路由文件
     */
    public static function load(string $routeFile): void
    {
        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }

    /**
     * 获取所有已注册路由（调试用）
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}
