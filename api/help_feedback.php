<?php
/**
 * 帮助与反馈配置 API
 * 为小程序提供配置数据
 */

header('Content-Type: application/json; charset=utf-8');
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

$action = $_GET['action'] ?? 'get_all';

switch ($action) {
    case 'get_all':
        get_all_config($pdo);
        break;
    
    case 'get_company_info':
        get_company_info($pdo);
        break;
    
    case 'get_faq':
        get_faq($pdo);
        break;
    
    default:
        echo json_encode([
            'code' => 400,
            'message' => '未知操作'
        ]);
}

/**
 * 获取所有配置
 */
function get_all_config($pdo) {
    $result = [
        'company_phone' => '',
        'company_email' => '',
        'company_intro' => '',
        'faq_list' => []
    ];
    
    // 获取公司信息
    $stmt = $pdo->query("SELECT config_key, config_value FROM help_feedback_config WHERE config_key IN ('company_phone', 'company_email', 'company_intro') AND status = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['config_key']] = $row['config_value'];
    }
    
    // 获取 FAQ 列表
    $stmt = $pdo->query("SELECT config_value FROM help_feedback_config WHERE config_key LIKE 'faq_%' AND status = 1 ORDER BY sort ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data = json_decode($row['config_value'], true);
        if ($data) {
            $result['faq_list'][] = $data;
        }
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $result
    ]);
}

/**
 * 获取公司信息
 */
function get_company_info($pdo) {
    $result = [];
    
    $stmt = $pdo->query("SELECT config_key, config_value FROM help_feedback_config WHERE config_key IN ('company_phone', 'company_email', 'company_intro') AND status = 1");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['config_key']] = $row['config_value'];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $result
    ]);
}

/**
 * 获取 FAQ 列表
 */
function get_faq($pdo) {
    $faq_list = [];
    
    $stmt = $pdo->query("SELECT config_value FROM help_feedback_config WHERE config_key LIKE 'faq_%' AND status = 1 ORDER BY sort ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data = json_decode($row['config_value'], true);
        // 如果 JSON 解析失败，尝试修复引号问题
        if (!$data) {
            $fixed = str_replace(['""', '""'], ['"', '"'], $row['config_value']);
            $data = json_decode($fixed, true);
        }
        if ($data) {
            $faq_list[] = $data;
        }
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $faq_list
    ]);
}
