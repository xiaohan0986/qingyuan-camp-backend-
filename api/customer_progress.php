<?php
/**
 * 客户办理进度 API
 * 供小程序端查询客户办理进度
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 关闭 HTML 错误输出，避免破坏 JSON
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 数据库配置
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

// 获取请求参数
$action = $_GET['action'] ?? 'list';
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

switch ($action) {
    case 'list':
        // 获取用户的办理进度列表
        getProgressList($pdo, $phone, $user_id);
        break;
    
    case 'detail':
    case 'get_progress_detail':
        // 获取具体办理进度详情
        getProgressDetail($pdo, $customer_id);
        break;
    
    case 'upload_material':
        // 上传补充材料
        uploadMaterial($pdo);
        break;
    
    default:
        echo json_encode([
            'code' => 400,
            'message' => '未知操作'
        ]);
}

/**
 * 获取用户的办理进度列表
 */
function getProgressList($pdo, $phone, $user_id = 0) {
    // 优先使用手机号查询，如果没有手机号则使用 user_id
    if (empty($phone) && $user_id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '请提供手机号或用户 ID'
        ]);
        return;
    }
    
    // 查询客户信息
    if (!empty($phone)) {
        // 根据手机号查询（只查询 customers 表，不关联 progress 表）
        $stmt = $pdo->prepare("
            SELECT c.*
            FROM customers c
            WHERE c.phone = :phone
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
    } else {
        // 根据 user_id 查询（只查询 customers 表，不关联 progress 表）
        $stmt = $pdo->prepare("
            SELECT c.*
            FROM customers c
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
            LIMIT 10
        ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customers)) {
        echo json_encode([
            'code' => 0,
            'message' => 'success',
            'data' => []
        ]);
        return;
    }
    
    $list = [];
    foreach ($customers as $customer) {
        $list[] = [
            'customer_id' => $customer['id'],
            'name' => $customer['name'],
            'phone' => $customer['phone'],
            'visa_type' => $customer['visa_type'] ?? '',
            'country' => $customer['country'] ?? '',
            'current_stage' => 'submitted',  // 默认值
            'current_stage_name' => '已提交',  // 默认值
            'progress_color' => '#007aff',  // 默认值
            'description' => '',  // 默认值
            'updated_at' => $customer['created_at'] ?? ''
        ];
    }
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => $list
    ]);
}

/**
 * 获取办理进度详情
 */
function getProgressDetail($pdo, $customer_id) {
    if ($customer_id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '缺少客户 ID'
        ]);
        return;
    }
    
    // 查询客户信息
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->bindValue(':id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo json_encode([
            'code' => 404,
            'message' => '客户不存在'
        ]);
        return;
    }
    
    // 查询当前进度（从 customer_progress 表获取最新一条）
    // 使用 try-catch 处理可能的字段不存在错误
    try {
        $stmt = $pdo->prepare("SELECT * FROM customer_progress WHERE customer_id = :id ORDER BY created_at DESC LIMIT 1");
        $stmt->bindValue(':id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 如果表不存在或字段不存在，返回空进度
        error_log("查询进度失败：" . $e->getMessage());
        $progress = null;
    }
    
    // 查询时间线：优先使用 progress_timeline 表，如果没有则从 customer_progress 生成
    $stmt = $pdo->prepare("SELECT * FROM progress_timeline WHERE customer_id = :id ORDER BY created_at ASC");
    $stmt->bindValue(':id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化时间线
    $timelineList = [];
    if (!empty($timeline)) {
        // 使用 progress_timeline 表的数据
        foreach ($timeline as $item) {
            $timelineList[] = [
                'stage' => $item['stage'],
                'stage_name' => $item['stage_name'] ?? $item['stage'],
                'status' => (int)($item['status'] ?? 1),
                'description' => $item['description'] ?? '',
                'completed_at' => $item['completed_at'] ?? $item['created_at']
            ];
        }
    } else {
        // 从 customer_progress 生成完整的 timeline（所有记录）
        $stmt = $pdo->prepare("SELECT * FROM customer_progress WHERE customer_id = :id ORDER BY created_at ASC");
        $stmt->bindValue(':id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $allProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allProgress as $item) {
            // 解析材料标签
            $tags = [];
            if (!empty($item['materials'])) {
                $materials = json_decode($item['materials'], true);
                if (is_array($materials)) {
                    $tags = $materials;
                }
            }
            
            $timelineList[] = [
                'stage' => 'submitted',
                'stage_name' => '已提交',
                'status' => 1,
                'description' => $item['progress_text'] ?? '',
                'completed_at' => $item['created_at'] ?? '',
                'tags' => $tags  // 添加 tags 字段
            ];
        }
    }
    
    // 解析材料标签
    $tags = [];
    if (!empty($progress['materials'])) {
        $materials = json_decode($progress['materials'], true);
        if (is_array($materials)) {
            $tags = $materials;
        }
    }
    
    // 解析材料文件
    $materials_files = [];
    if (!empty($progress['materials_files'])) {
        $materials_files = json_decode($progress['materials_files'], true);
        if (!is_array($materials_files)) {
            $materials_files = [];
        }
    }
    
    // 如果 progress_text 为空，使用默认值
    $progressText = !empty($progress['progress_text']) ? $progress['progress_text'] : '已提交';
    $progressTime = !empty($progress['created_at']) ? $progress['created_at'] : '';
    
    echo json_encode([
        'code' => 0,
        'message' => 'success',
        'data' => [
            'customer' => [
                'id' => $customer['id'],
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'visa_type' => $customer['visa_type'] ?? '',
                'country' => $customer['country'] ?? '',
                'submit_date' => date('Y-m-d', strtotime($customer['created_at']))
            ],
            'currentProgress' => [
                'stage' => 'submitted',
                'name' => $progressText,
                'color' => '#007aff',
                'description' => $progressText,
                'updated_at' => $progressTime,
                'tags' => $tags,
                'materials_status' => !empty($progress['materials_status']) ? json_decode($progress['materials_status'], true) : [],
                'materials_files' => $materials_files  // 添加文件 URL
            ],
            'timeline' => $timelineList
        ]
    ]);
}

/**
 * 上传补充材料
 */
function uploadMaterial($pdo) {
    // 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'code' => 400,
            'message' => '请求方法错误'
        ]);
        return;
    }
    
    // 获取参数
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $progress_key = isset($_POST['progress_key']) ? trim($_POST['progress_key']) : '';
    $material_name = isset($_POST['material_name']) ? trim($_POST['material_name']) : '补充材料';
    
    if ($customer_id <= 0) {
        echo json_encode([
            'code' => 400,
            'message' => '客户 ID 无效'
        ]);
        return;
    }
    
    // 检查文件上传
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'code' => 400,
            'message' => '文件上传失败'
        ]);
        return;
    }
    
    $file = $_FILES['file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // 验证文件类型
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($file_ext, $allowed_exts)) {
        echo json_encode([
            'code' => 400,
            'message' => '不支持的文件类型，仅支持 jpg、png、gif、pdf'
        ]);
        return;
    }
    
    // 验证文件大小（5MB）
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'code' => 400,
            'message' => '文件大小不能超过 5MB'
        ]);
        return;
    }
    
    // 生成文件名
    $upload_dir = __DIR__ . '/../uploads/materials/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid('material_') . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    $file_url = 'https://www.gofong.com/' . 'uploads/materials/' . $filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode([
            'code' => 500,
            'message' => '文件保存失败'
        ]);
        return;
    }
    
    // 获取最新的 progress 记录
    $stmt = $pdo->prepare("SELECT * FROM customer_progress WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT 1");
    $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 获取客户信息
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :customer_id");
    $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo json_encode([
            'code' => 404,
            'message' => '客户不存在'
        ]);
        return;
    }
    
    if (!$progress) {
        // 如果没有进度记录，创建一条
        $stmt = $pdo->prepare("
            INSERT INTO customer_progress (customer_id, progress_text, materials, materials_status, materials_uploaded_at, materials_files, created_at)
            VALUES (:customer_id, '已提交', :materials, :materials_status, :materials_uploaded_at, :materials_files, NOW())
        ");
        
        // 初始化材料状态
        $materialsArray = [$material_name];
        $materialsStatus = [$material_name => 'uploaded'];
        $materialsUploadedAt = [$material_name => date('Y-m-d H:i:s')];
        $materialsFiles = [$material_name => $file_url];
        
        $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindValue(':progress_text', '已提交', PDO::PARAM_STR);
        $stmt->bindValue(':materials', json_encode($materialsArray, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_status', json_encode($materialsStatus, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_uploaded_at', json_encode($materialsUploadedAt, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_files', json_encode($materialsFiles, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->execute();
        $progress_id = $pdo->lastInsertId();
    } else {
        $progress_id = $progress['id'];
        
        // 更新 materials 字段
        $materials = [];
        if (!empty($progress['materials'])) {
            $materials = json_decode($progress['materials'], true);
            if (!is_array($materials)) {
                $materials = [];
            }
        }
        
        // 添加新材料标签（如果不存在）
        if (!in_array($material_name, $materials)) {
            $materials[] = $material_name;
        }
        
        // 更新 materials_status
        $materials_status = [];
        if (!empty($progress['materials_status'])) {
            $materials_status = json_decode($progress['materials_status'], true);
            if (!is_array($materials_status)) {
                $materials_status = [];
            }
        }
        $materials_status[$material_name] = 'uploaded';
        
        // 更新 materials_uploaded_at
        $materials_uploaded_at = [];
        if (!empty($progress['materials_uploaded_at'])) {
            $materials_uploaded_at = json_decode($progress['materials_uploaded_at'], true);
            if (!is_array($materials_uploaded_at)) {
                $materials_uploaded_at = [];
            }
        }
        $materials_uploaded_at[$material_name] = date('Y-m-d H:i:s');
        
        // 更新 materials_files（添加文件路径）
        $materials_files = [];
        if (!empty($progress['materials_files'])) {
            $materials_files = json_decode($progress['materials_files'], true);
            if (!is_array($materials_files)) {
                $materials_files = [];
            }
        }
        $materials_files[$material_name] = $file_url;
        
        // 更新数据库
        $stmt = $pdo->prepare("
            UPDATE customer_progress 
            SET materials = :materials,
                materials_status = :materials_status,
                materials_uploaded_at = :materials_uploaded_at,
                materials_files = :materials_files
            WHERE id = :id
        ");
        $stmt->bindValue(':materials', json_encode($materials, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_status', json_encode($materials_status, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_uploaded_at', json_encode($materials_uploaded_at, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':materials_files', json_encode($materials_files, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $stmt->bindValue(':id', $progress_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    echo json_encode([
        'code' => 0,
        'message' => '上传成功',
        'data' => [
            'file_url' => $file_url,
            'material_name' => $material_name,
            'upload_time' => date('Y-m-d H:i:s')
        ]
    ]);
}
