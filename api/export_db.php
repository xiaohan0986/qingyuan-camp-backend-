<?php
/**
 * 导出本地数据库
 * 生成 SQL 文件和 JSON 数据包
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        ),
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    
    $exportData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => $config['database'],
        'tables' => []
    ];
    
    // 获取所有表
    $tables = ['users', 'customers', 'positions', 'articles', 'system_configs', 'platform_users'];
    
    foreach ($tables as $table) {
        try {
            // 获取表结构
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            
            // 获取表数据
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $exportData['tables'][$table] = [
                'structure' => $createTable['Create Table'] ?? '',
                'data' => $data,
                'count' => count($data)
            ];
        } catch (Exception $e) {
            $exportData['tables'][$table] = [
                'structure' => '',
                'data' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // 保存 JSON 文件
    $jsonFile = __DIR__ . '/database/export_' . date('Ymd_His') . '.json';
    file_put_contents($jsonFile, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // 生成 SQL 文件
    $sqlContent = "-- 青园营地数据库导出\n";
    $sqlContent .= "-- 导出时间：" . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- 数据库：" . $config['database'] . "\n\n";
    
    foreach ($exportData['tables'] as $tableName => $tableData) {
        if (!empty($tableData['structure'])) {
            $sqlContent .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sqlContent .= $tableData['structure'] . ";\n\n";
            
            if (!empty($tableData['data'])) {
                foreach ($tableData['data'] as $row) {
                    $columns = implode(', ', array_keys($row));
                    $values = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $sqlContent .= "INSERT INTO `$tableName` ($columns) VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            $sqlContent .= "\n";
        }
    }
    
    $sqlFile = __DIR__ . '/database/export_' . date('Ymd_His') . '.sql';
    file_put_contents($sqlFile, $sqlContent);
    
    json_success([
        'message' => '数据库导出成功',
        'json_file' => basename($jsonFile),
        'sql_file' => basename($sqlFile),
        'tables' => array_map(function($t) {
            return $t['count'];
        }, $exportData['tables'])
    ]);
    
} catch (Exception $e) {
    json_error('导出失败：' . $e->getMessage(), 500);
}

function json_success($data) {
    echo json_encode([
        'code' => 200,
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $code = 400) {
    echo json_encode([
        'code' => $code,
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
