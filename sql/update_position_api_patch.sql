-- =====================================================
-- 修改 position.php 的 update() 函数
-- 添加 recommend_salesman_id 字段支持
-- =====================================================

-- 在 $data 数组中添加（第 302 行之后）：
-- 'recommend_salesman_id' => !empty($_POST['recommend_salesman_id']) ? (int)$_POST['recommend_salesman_id'] : null,

-- 在 UPDATE SQL 中添加（第 335 行之后）：
-- recommend_salesman_id = :recommend_salesman_id,

-- 完整修改后的 update() 函数应该包含：
/*
    $data = [
        'id' => $id,
        // ... 其他字段
        'attachment_files' => json_encode($attachmentFiles, JSON_UNESCAPED_UNICODE),
        'recommend_salesman_id' => !empty($_POST['recommend_salesman_id']) ? (int)$_POST['recommend_salesman_id'] : null,
        'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        // ... 其他字段
    ];
    
    $stmt = $pdo->prepare("
        UPDATE positions SET
            // ... 其他字段
            attachment_files = :attachment_files,
            recommend_salesman_id = :recommend_salesman_id,
            latitude = :latitude,
            // ... 其他字段
        WHERE id = :id
    ");
*/
