<?php
require_once __DIR__ . '/../config/paths.php';

/**
 * 小程序配置 API
 * 返回小程序端所需的各种配置信息
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 数据库配置
require_once __DIR__ . '/../config/database.php';

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败',
        'error' => $e->getMessage()
    ]);
    exit;
}

// 获取系统配置
function getSystemConfig($pdo) {
    $config = [];
    try {
        $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['config_key']] = $row['config_value'];
        }
    } catch (PDOException $e) {
        // 如果表不存在，返回默认配置（全部开启）
        $config = [
            'shortcut_hot_positions' => '1',
            'shortcut_policy' => '1',
            'shortcut_travel' => '1',
            'shortcut_study_abroad' => '1',
            'shortcut_progress' => '1',
            'shortcut_stores' => '1',
            'shortcut_managers' => '1',
            'shortcut_knowledge' => '1'
        ];
    }
    return $config;
}

// 更新系统配置
function updateSystemConfig($pdo, $key, $value) {
    try {
        // 检查配置是否存在
        $stmt = $pdo->prepare("SELECT id FROM system_config WHERE config_key = :key");
        $stmt->execute([':key' => $key]);
        
        if ($stmt->fetch()) {
            // 更新现有配置
            $stmt = $pdo->prepare("UPDATE system_config SET config_value = :value WHERE config_key = :key");
            $stmt->execute([':key' => $key, ':value' => $value]);
        } else {
            // 插入新配置
            $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value, config_desc) VALUES (:key, :value, :desc)");
            $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':desc' => '系统配置'
            ]);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// 处理请求
$action = $_GET['action'] ?? 'get_config';

switch ($action) {
    case 'get_config':
        // 获取所有配置
        $config = getSystemConfig($pdo);
        echo json_encode([
            'code' => 200,
            'data' => $config
        ]);
        break;
    
    case 'get_shortcuts':
        // 只获取快捷按钮配置
        $config = getSystemConfig($pdo);
        $shortcuts = [
            'hotPositions' => ($config['shortcut_hot_positions'] ?? '1') === '1',
            'policy' => ($config['shortcut_policy'] ?? '1') === '1',
            'travel' => ($config['shortcut_travel'] ?? '1') === '1',
            'studyAbroad' => ($config['shortcut_study_abroad'] ?? '1') === '1',
            'progress' => ($config['shortcut_progress'] ?? '1') === '1',
            'stores' => ($config['shortcut_stores'] ?? '1') === '1',
            'managers' => ($config['shortcut_managers'] ?? '1') === '1',
            'knowledge' => ($config['shortcut_knowledge'] ?? '1') === '1'
        ];
        echo json_encode([
            'code' => 200,
            'data' => $shortcuts
        ]);
        break;
    
    case 'update_config':
        // 更新配置（POST 请求）
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['code' => 400, 'message' => '请求方法错误']);
            break;
        }
        
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        
        if (empty($key)) {
            echo json_encode(['code' => 400, 'message' => '缺少配置键']);
            break;
        }
        
        $success = updateSystemConfig($pdo, $key, $value);
        echo json_encode([
            'code' => $success ? 200 : 500,
            'message' => $success ? '配置更新成功' : '配置更新失败'
        ]);
        break;
    
    case 'update_shortcuts':
        // 批量更新快捷按钮配置（POST 请求）
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['code' => 400, 'message' => '请求方法错误']);
            break;
        }
        
        $shortcuts = [
            'shortcut_hot_positions' => $_POST['shortcut_hot_positions'] ?? '1',
            'shortcut_policy' => $_POST['shortcut_policy'] ?? '1',
            'shortcut_travel' => $_POST['shortcut_travel'] ?? '1',
            'shortcut_study_abroad' => $_POST['shortcut_study_abroad'] ?? '1',
            'shortcut_progress' => $_POST['shortcut_progress'] ?? '1',
            'shortcut_stores' => $_POST['shortcut_stores'] ?? '1',
            'shortcut_managers' => $_POST['shortcut_managers'] ?? '1',
            'shortcut_knowledge' => $_POST['shortcut_knowledge'] ?? '1'
        ];
        
        $allSuccess = true;
        foreach ($shortcuts as $key => $value) {
            if (!updateSystemConfig($pdo, $key, $value)) {
                $allSuccess = false;
            }
        }
        
        echo json_encode([
            'code' => $allSuccess ? 200 : 500,
            'message' => $allSuccess ? '快捷按钮配置更新成功' : '部分配置更新失败'
        ]);
        break;
    
    default:
        echo json_encode(['code' => 400, 'message' => '未知操作']);
}
