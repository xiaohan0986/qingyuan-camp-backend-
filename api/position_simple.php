<?php
/**
 * 简化版 Position API - 不依赖 bootstrap
 */
header('Content-Type: application/json; charset=utf-8');

// 直接加载数据库配置
$config = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'qianwutong',
    'username' => 'qianwutong',
    'password' => 'hb098634',
    'charset' => 'utf8'
];

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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        getList($pdo);
        break;
    case 'detail':
        getDetail($pdo);
        break;
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作']);
}

function getList($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, (int)($_GET['page_size'] ?? 20));
    $offset = ($page - 1) * $pageSize;
    
    // 简单查询，不使用 WHERE 条件
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM positions");
    $stmt->execute();
    $total = (int)$stmt->fetch()['total'];
    
    $offset = (int)$offset;
    $pageSize = (int)$pageSize;
    $stmt = $pdo->prepare("SELECT * FROM positions ORDER BY id DESC LIMIT {$offset}, {$pageSize}");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'list' => $list,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getDetail($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode(['code' => 404, 'message' => '岗位不存在']);
        return;
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}
