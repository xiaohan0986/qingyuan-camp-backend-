<?php
/**
 * 小程序端上传材料 API
 * 接收小程序上传的材料图片，保存到服务器并更新状态
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 数据库配置
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
} catch (PDOException $e) {
    echo json_encode([
        'code' => 500,
        'message' => '数据库连接失败',
        'error' => $e->getMessage()
    ]);
    exit;
}

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 400, 'message' => '请求方法错误']);
    exit;
}

// 获取参数
$customer_id = (int)($_POST['customer_id'] ?? 0);
$material_type = $_POST['material_type'] ?? '';  // 材料类型，如"无犯罪证明"
$progress_id = (int)($_POST['progress_id'] ?? 0);  // 关联的进度 ID

// 验证参数
if (!$customer_id) {
    echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空']);
    exit;
}

if (empty($material_type)) {
    echo json_encode(['code' => 400, 'message' => '材料类型不能为空']);
    exit;
}

// 检查文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 中 upload_max_filesize 限制',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单 MAX_FILE_SIZE 限制',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => 'PHP 扩展阻止了文件上传'
    ];
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMsg = $errorMessages[$errorCode] ?? '未知错误';
    echo json_encode(['code' => 400, 'message' => '文件上传失败：' . $errorMsg]);
    exit;
}

$file = $_FILES['file'];

// 验证文件类型（只允许图片）
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['code' => 400, 'message' => '只允许上传图片文件 (JPG/PNG/GIF/WEBP)']);
    exit;
}

// 验证文件大小（最大 5MB）
$maxSize = 5 * 1024 * 1024;  // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['code' => 400, 'message' => '文件大小不能超过 5MB']);
    exit;
}

// 检查该材料已上传次数（最多 2 次）
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM customer_documents 
    WHERE customer_id = :customer_id 
    AND material_type = :material_type
    AND status != 'deleted'
");
$stmt->execute([
    ':customer_id' => $customer_id,
    ':material_type' => $material_type
]);
$result = $stmt->fetch();
if ($result['count'] >= 2) {
    echo json_encode(['code' => 400, 'message' => '该材料最多上传 2 次，已达到上限']);
    exit;
}

// 创建保存目录
$uploadDir = __DIR__ . '/../uploads/materials/' . $customer_id . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成文件名
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$filePath = $uploadDir . $fileName;

// 移动上传的文件
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['code' => 500, 'message' => '文件保存失败']);
    exit;
}

// 相对路径（用于数据库存储）
$relativePath = 'uploads/materials/' . $customer_id . '/' . $fileName;

// 获取上传用户 ID（小程序用户）
// 从小程序的登录 token 中获取，这里暂时从 POST 获取
$uploaded_by = (int)($_POST['user_id'] ?? 0);

// 插入数据库
$stmt = $pdo->prepare("
    INSERT INTO customer_documents 
    (customer_id, doc_type, material_type, progress_id, file_path, file_name, file_size, uploaded_by, status, created_at)
    VALUES 
    (:customer_id, :doc_type, :material_type, :progress_id, :file_path, :file_name, :file_size, :uploaded_by, :status, NOW())
");

$stmt->execute([
    ':customer_id' => $customer_id,
    ':doc_type' => 'material',  // 固定为 material
    ':material_type' => $material_type,
    ':progress_id' => $progress_id > 0 ? $progress_id : null,
    ':file_path' => $relativePath,
    ':file_name' => $file['name'],
    ':file_size' => $file['size'],
    ':uploaded_by' => $uploaded_by,
    ':status' => 'uploaded'  // 待审核状态
]);

$document_id = $pdo->lastInsertId();

// 更新 customer_progress 表的材料状态
if ($progress_id > 0) {
    // 获取当前的 materials_status
    $stmt = $pdo->prepare("SELECT materials_status, materials_uploaded_at FROM customer_progress WHERE id = :id");
    $stmt->execute([':id' => $progress_id]);
    $progress = $stmt->fetch();
    
    if ($progress) {
        $materialsStatus = !empty($progress['materials_status']) ? json_decode($progress['materials_status'], true) : [];
        $materialsUploadedAt = !empty($progress['materials_uploaded_at']) ? json_decode($progress['materials_uploaded_at'], true) : [];
        
        // 更新该材料的状态为 uploaded
        if (isset($materialsStatus[$material_type])) {
            $materialsStatus[$material_type] = 'uploaded';
        }
        
        // 记录上传时间
        $materialsUploadedAt[$material_type] = date('Y-m-d H:i:s');
        
        // 更新数据库
        $updateStmt = $pdo->prepare("
            UPDATE customer_progress 
            SET materials_status = :status, materials_uploaded_at = :uploaded_at 
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':status' => json_encode($materialsStatus, JSON_UNESCAPED_UNICODE),
            ':uploaded_at' => json_encode($materialsUploadedAt, JSON_UNESCAPED_UNICODE),
            ':id' => $progress_id
        ]);
    }
}

// 创建站内通知（通知负责该客户的管理员）
try {
    // 获取客户的 owner_id
    $stmt = $pdo->prepare("SELECT owner_id, name FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch();
    
    if ($customer && $customer['owner_id'] > 0) {
        $notifyStmt = $pdo->prepare("
            INSERT INTO admin_notifications 
            (admin_id, title, content, type, related_type, related_id, created_at)
            VALUES 
            (:admin_id, :title, :content, :type, :related_type, :related_id, NOW())
        ");
        
        $notifyStmt->execute([
            ':admin_id' => $customer['owner_id'],
            ':title' => '📋 客户已上传材料',
            ':content' => "客户 {$customer['name']} 已上传材料：{$material_type}，请及时审核。",
            ':type' => 'material_upload',
            ':related_type' => 'customer',
            ':related_id' => $customer_id
        ]);
    }
} catch (PDOException $e) {
    // 通知表可能不存在，记录错误但不影响主流程
    error_log('创建通知失败：' . $e->getMessage());
}

// 返回成功响应
echo json_encode([
    'code' => 200,
    'message' => '材料上传成功',
    'data' => [
        'id' => $document_id,
        'file_path' => $relativePath,
        'file_url' => 'https://www.gofong.com/' . $relativePath,
        'material_type' => $material_type,
        'uploaded_at' => date('Y-m-d H:i:s')
    ]
], JSON_UNESCAPED_UNICODE);
