<?php
/**
 * 角色管理 API - 重组版
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/OperationLogger.php';
require_once __DIR__ . '/../includes/PermissionChecker.php';

session_start();

// 检查登录状态
$permissionChecker = new PermissionChecker();
if (!$permissionChecker->isSuperAdmin()) {
    echo json_encode(['code' => 403, 'message' => '权限不足，只有超级管理员可以管理角色']);
    exit;
}

$db = Database::getInstance();
$logger = new OperationLogger();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // 获取角色列表
            $sql = "SELECT r.* FROM roles r WHERE 1=1 ORDER BY r.sort_order ASC, r.id ASC";
            $stmt = $pdo->query($sql);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取每个角色的销售人员列表
            foreach ($roles as &$role) {
                // 解析权限
                if ($role['permissions']) {
                    $role['permissions'] = json_decode($role['permissions'], true);
                }
                
                // 获取该角色下的所有销售人员
                $salesmenSql = "SELECT id, name, phone FROM salesmen WHERE role_id = :role_id ORDER BY id ASC";
                $stmt = $pdo->prepare($salesmenSql);
                $stmt->execute([':role_id' => $role['id']]);
                $salesmen = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $role['salesmen'] = $salesmen;
            }
            
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'list' => $roles
                ]
            ]);
            break;
            
        case 'get':
            // 获取角色详情
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$role) {
                echo json_encode(['code' => 404, 'message' => '角色不存在']);
                exit;
            }
            
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => $role
            ]);
            break;
            
        case 'save':
            // 保存角色（新增或编辑）
            $id = $_POST['id'] ?? '';
            $roleName = trim($_POST['role_name'] ?? '');
            $roleKey = trim($_POST['role_key'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            $permissionsJson = $_POST['permissions'] ?? '{}';
            
            // 验证必填字段
            if (empty($roleName)) {
                echo json_encode(['code' => 400, 'message' => '角色名称不能为空']);
                exit;
            }
            
            if (empty($roleKey)) {
                echo json_encode(['code' => 400, 'message' => '角色标识不能为空']);
                exit;
            }
            
            // 验证权限 JSON
            $permissions = json_decode($permissionsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['code' => 400, 'message' => '权限格式错误']);
                exit;
            }
            
            $permissionsStr = json_encode($permissions, JSON_UNESCAPED_UNICODE);
            
            try {
                if (empty($id)) {
                    // 新增角色
                    $stmt = $pdo->prepare("
                        INSERT INTO roles (role_name, role_key, description, status, permissions, sort_order, created_at, updated_at)
                        VALUES (:role_name, :role_key, :description, :status, :permissions, :sort_order, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        ':role_name' => $roleName,
                        ':role_key' => $roleKey,
                        ':description' => $description,
                        ':status' => $status,
                        ':permissions' => $permissionsStr,
                        ':sort_order' => 100
                    ]);
                    
                    $roleId = $pdo->lastInsertId();
                    $logger->log('新增角色', "角色 ID: $roleId, 名称：$roleName, 标识：$roleKey");
                    
                    echo json_encode([
                        'code' => 200,
                        'message' => '新增成功',
                        'data' => ['id' => $roleId]
                    ]);
                } else {
                    // 编辑角色
                    $stmt = $pdo->prepare("
                        UPDATE roles 
                        SET role_name = :role_name,
                            role_key = :role_key,
                            description = :description,
                            status = :status,
                            permissions = :permissions,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    
                    $stmt->execute([
                        ':id' => $id,
                        ':role_name' => $roleName,
                        ':role_key' => $roleKey,
                        ':description' => $description,
                        ':status' => $status,
                        ':permissions' => $permissionsStr
                    ]);
                    
                    $logger->log('编辑角色', "角色 ID: $id, 名称：$roleName, 标识：$roleKey");
                    
                    echo json_encode([
                        'code' => 200,
                        'message' => '保存成功'
                    ]);
                }
            } catch (PDOException $e) {
                // 检查是否是唯一键冲突
                if ($e->getCode() == 23000) {
                    echo json_encode(['code' => 400, 'message' => '角色标识已存在，请使用其他标识']);
                } else {
                    echo json_encode(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
                }
                exit;
            }
            break;
            
        case 'delete':
            // 删除角色
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['code' => 400, 'message' => '角色 ID 无效']);
                exit;
            }
            
            // 禁止删除销售人员角色（ID=5）
            if ($id === 5) {
                echo json_encode([
                    'code' => 400,
                    'message' => "无法删除：销售人员是系统默认角色，不可删除"
                ]);
                exit;
            }
            
            // 检查是否有销售人员使用该角色
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM salesmen WHERE role_id = :role_id");
            $stmt->execute([':role_id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'code' => 400,
                    'message' => "无法删除：有 {$result['count']} 个销售人员正在使用该角色，请先修改他们的角色"
                ]);
                exit;
            }
            
            // 删除角色
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $logger->log('删除角色', "角色 ID: $id");
            
            echo json_encode([
                'code' => 200,
                'message' => '删除成功'
            ]);
            break;
            
        default:
            echo json_encode(['code' => 400, 'message' => '未知操作']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
}
