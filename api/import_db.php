<?php
/**
 * 导入数据库
 * 从本地导出数据导入到当前数据库
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once INCLUDES_PATH . 'functions.php';

// 检查管理员权限
check_admin();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'import':
        handle_import();
        break;
    case 'export':
        handle_export();
        break;
    default:
        json_error('未知操作', 400);
}

/**
 * 处理导入
 */
function handle_import() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('请求方法错误', 405);
    }
    
    try {
        // 获取本地数据库配置
        $localConfig = [
            'host' => $_POST['local_host'] ?? 'localhost',
            'port' => $_POST['local_port'] ?? '3306',
            'database' => $_POST['local_database'] ?? 'qianwutong',
            'username' => $_POST['local_username'] ?? 'root',
            'password' => $_POST['local_password'] ?? '',
        ];
        
        // 连接本地数据库
        $localDsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $localConfig['host'],
            $localConfig['port'],
            $localConfig['database']
        );
        
        $localPdo = new PDO($localDsn, $localConfig['username'], $localConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // 连接当前服务器数据库
        $serverConfig = require CONFIG_PATH . 'database.php';
        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $serverConfig['host'],
            $serverConfig['port'],
            $serverConfig['database']
        );
        
        $serverPdo = new PDO($serverDsn, $serverConfig['username'], $serverConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $tables = ['users', 'customers', 'positions', 'articles', 'system_configs', 'platform_users'];
        $importStats = [];
        
        foreach ($tables as $table) {
            try {
                // 从本地获取数据
                $stmt = $localPdo->query("SELECT * FROM `$table`");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($data)) {
                    $importStats[$table] = ['status' => 'skip', 'message' => '无数据'];
                    continue;
                }
                
                // 清空服务器表
                $serverPdo->exec("TRUNCATE TABLE `$table`");
                
                // 插入数据
                $count = 0;
                foreach ($data as $row) {
                    $columns = implode(', ', array_keys($row));
                    $placeholders = implode(', ', array_fill(0, count($row), '?'));
                    $values = array_values($row);
                    
                    $stmt = $serverPdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
                    $stmt->execute($values);
                    $count++;
                }
                
                $importStats[$table] = ['status' => 'success', 'count' => $count];
                
            } catch (Exception $e) {
                $importStats[$table] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }
        
        json_success([
            'message' => '数据导入成功',
            'stats' => $importStats
        ]);
        
    } catch (Exception $e) {
        json_error('导入失败：' . $e->getMessage(), 500);
    }
}

/**
 * 处理导出
 */
function handle_export() {
    try {
        $config = require CONFIG_PATH . 'database.php';
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $tables = ['users', 'customers', 'positions', 'articles', 'system_configs', 'platform_users'];
        $exportData = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $exportData[$table] = $data;
            } catch (Exception $e) {
                $exportData[$table] = ['error' => $e->getMessage()];
            }
        }
        
        // 保存到临时文件
        $exportFile = UPLOAD_PATH . 'db_export_' . date('Ymd_His') . '.json';
        file_put_contents($exportFile, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        json_success([
            'message' => '数据导出成功',
            'file' => basename($exportFile),
            'tables' => array_map('count', $exportData)
        ]);
        
    } catch (Exception $e) {
        json_error('导出失败：' . $e->getMessage(), 500);
    }
}
