<?php
function env($key, $default = null) {
    static $env = null;
    if ($env === null) {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
        } else {
            $env = [];
        }
    }
    return $env[$key] ?? $default;
}
