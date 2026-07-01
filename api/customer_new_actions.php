<?php
/**
 * 客户管理 API - 新增接口
 */

// 添加进度
function addProgress($pdo) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $progress_text = $_POST['progress_text'] ?? '';
    
    if (!$customer_id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (empty($progress_text)) {
        echo json_encode(['code' => 400, 'message' => '进度内容不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_progress 
        (customer_id, progress_text, created_by)
        VALUES 
        (:customer_id, :progress_text, :created_by)
    ");
    
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':progress_text' => $progress_text,
        ':created_by' => $_SESSION['user_id'] ?? 0
    ]);
    
    echo json_encode([
        'code' => 200,
        'message' => '进度已添加',
        'data' => ['id' => $pdo->lastInsertId()]
    ], JSON_UNESCAPED_UNICODE);
}

// 获取进度历史
function getProgress($pdo) {
    $customer_id = (int)($_GET['customer_id'] ?? 0);
    
    if (!$customer_id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_progress 
        WHERE customer_id = :customer_id 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([':customer_id' => $customer_id]);
    $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'data' => $progress
    ], JSON_UNESCAPED_UNICODE);
}

// 获取资料列表
function getDocuments($pdo) {
    $customer_id = (int)($_GET['customer_id'] ?? 0);
    
    if (!$customer_id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_documents 
        WHERE customer_id = :customer_id 
        ORDER BY doc_type, uploaded_at DESC
    ");
    
    $stmt->execute([':customer_id' => $customer_id]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'data' => $docs
    ], JSON_UNESCAPED_UNICODE);
}

// 上传资料
function uploadDocument($pdo) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $doc_type = $_POST['doc_type'] ?? '';
    
    if (!$customer_id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (empty($doc_type)) {
        echo json_encode(['code' => 400, 'message' => '资料类型不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查文件
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['code' => 400, 'message' => '请选择要上传的文件'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $file = $_FILES['file'];
    
    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['code' => 400, 'message' => '只支持 JPG、PNG、GIF 和 PDF 格式'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证文件大小（10MB）
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['code' => 400, 'message' => '文件大小不能超过 10MB'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 创建上传目录
    $uploadDir = __DIR__ . '/../memory/documents/customer_' . $customer_id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 生成文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $doc_type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['code' => 500, 'message' => '文件上传失败'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 保存到数据库
    $stmt = $pdo->prepare("
        INSERT INTO customer_documents 
        (customer_id, doc_type, file_path, file_name, file_size, uploaded_by)
        VALUES 
        (:customer_id, :doc_type, :file_path, :file_name, :file_size, :uploaded_by)
    ");
    
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':doc_type' => $doc_type,
        ':file_path' => 'memory/documents/customer_' . $customer_id . '/' . $fileName,
        ':file_name' => $file['name'],
        ':file_size' => $file['size'],
        ':uploaded_by' => $_SESSION['user_id'] ?? 0
    ]);
    
    echo json_encode([
        'code' => 200,
        'message' => '文件上传成功',
        'data' => ['id' => $pdo->lastInsertId()]
    ], JSON_UNESCAPED_UNICODE);
}

// 删除资料
function deleteDocument($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '资料 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 获取文件信息
    $stmt = $pdo->prepare("SELECT * FROM customer_documents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doc) {
        echo json_encode(['code' => 404, 'message' => '资料不存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 删除文件
    $filePath = __DIR__ . '/../' . $doc['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // 删除数据库记录
    $stmt = $pdo->prepare("DELETE FROM customer_documents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '文件已删除'
    ], JSON_UNESCAPED_UNICODE);
}
