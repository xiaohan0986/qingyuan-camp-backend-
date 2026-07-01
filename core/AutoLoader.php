<?php
/**
 * 简易自动加载器
 * 遵循 PSR-4 风格，映射命名空间到目录
 * 
 * 约定：
 *   core\        → core/
 *   app\         → app/
 * 
 * 无需 Composer，纯 PHP 实现
 */
class AutoLoader
{
    /** @var array 命名空间前缀 → 目录映射 */
    private static array $prefixes = [];
    
    /** @var array 类名 → 文件路径缓存 */
    private static array $classMap = [];

    /**
     * 注册命名空间
     */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$prefixes[$prefix] = $baseDir;
    }

    /**
     * 注册自动加载
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * 加载类
     */
    public static function loadClass(string $class): void
    {
        // 检查缓存
        if (isset(self::$classMap[$class])) {
            $file = self::$classMap[$class];
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }

        // 遍历命名空间映射
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (strpos($class, $prefix) === 0) {
                // 去掉前缀，转换剩余部分为路径
                $relativeClass = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    self::$classMap[$class] = $file;
                    require_once $file;
                    return;
                }
            }
        }
    }

    /**
     * 注册全局函数自动加载（app/common.php 等）
     */
    public static function loadFunctions(string $file): void
    {
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
