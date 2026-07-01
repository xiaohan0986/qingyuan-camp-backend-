<?php
/**
 * 销售人员管理 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/Database.php';

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'list':
            // 获取销售人员列表
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $pageSize = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
            $offset = ($page - 1) * $pageSize;
            
            $keyword = $_GET['keyword'] ?? '';
            $level = $_GET['level'] ?? '';
            $storeId = $_GET['store_id'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $where = ['1=1'];
            $params = [];
            
            if ($keyword) {
                $where[] = "(name LIKE :keyword OR phone LIKE :keyword OR wechat LIKE :keyword)";
                $params[':keyword'] = "%{$keyword}%";
            }
            
            if ($level) {
                $where[] = "level = :level";
                $params[':level'] = $level;
            }
            
            if ($storeId) {
                $where[] = "store_id = :store_id";
                $params[':store_id'] = $storeId;
            }
            
            if ($status) {
                $where[] = "s.status = :status";
                $params[':status'] = $status;
            }
            
            $whereClause = implode(' AND ', $where);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM salesmen s WHERE {$whereClause}";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 查询数据（使用子查询自动计算销售额、成交量、最后成交日期）
            $sql = "SELECT 
                s.*, 
                r.role_name,
                COALESCE((SELECT SUM(amount) FROM sales WHERE salesman_id = s.id AND status = '已成交'), 0) as sales_amount,
                COALESCE((SELECT COUNT(*) FROM sales WHERE salesman_id = s.id AND status = '已成交'), 0) as deal_count,
                (SELECT MAX(close_date) FROM sales WHERE salesman_id = s.id AND status = '已成交') as last_deal_date
                FROM salesmen s 
                LEFT JOIN roles r ON s.role_id = r.id 
                WHERE {$whereClause} 
                ORDER BY s.created_at DESC 
                LIMIT :offset, :limit";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $list,
                'total' => intval($total),
                'page' => $page,
                'page_size' => $pageSize
            ]);
            break;
            
        case 'get':
            // 获取销售人员详情
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                throw new Exception('缺少 ID 参数');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM salesmen WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                throw new Exception('销售人员不存在');
            }
            
            // 如果有 password_view，解密返回明文密码
            if (!empty($data['password_view'])) {
                require_once __DIR__ . '/../includes/PasswordHelper.php';
                $plainPassword = decryptPassword($data['password_view']);
                // 添加明文密码到返回数据（仅用于显示）
                $data['password_plain'] = $plainPassword;
            }
            
            // 自动计算销售额、成交量、最后成交日期（从 sales 表）
            $stmt = $pdo->prepare("SELECT 
                COALESCE(SUM(amount), 0) as total_sales,
                COUNT(*) as total_deals,
                MAX(close_date) as last_deal_date
                FROM sales 
                WHERE salesman_id = :salesman_id AND status = '已成交'");
            $stmt->execute([':salesman_id' => $id]);
            $salesStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data['sales_amount'] = floatval($salesStats['total_sales'] ?? 0);
            $data['deal_count'] = intval($salesStats['total_deals'] ?? 0);
            $data['last_deal_date'] = $salesStats['last_deal_date'] ?? '';
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'create':
            // 创建销售人员
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['phone'])) {
                throw new Exception('姓名和手机号为必填项');
            }
            
            // 检查手机号是否已存在
            $stmt = $pdo->prepare("SELECT id FROM salesmen WHERE phone = :phone");
            $stmt->execute([':phone' => $data['phone']]);
            if ($stmt->fetch()) {
                throw new Exception('该手机号已存在');
            }
            
            // 默认角色为销售人员（ID=5）
            $data['role_id'] = 5;
            
            // 处理密码
            if (!empty($data['password'])) {
                // bcrypt 加密（用于登录验证）
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                // AES 加密（用于管理员查看）
                require_once __DIR__ . '/../includes/PasswordHelper.php';
                $data['password_view'] = encryptPassword($data['password']);
            } else {
                unset($data['password']);
            }
        
        $sql = "INSERT INTO salesmen (name, avatar, phone, password, password_view, role_id, email, wechat, level, store_id, store_name, entry_date, sales_amount, deal_count, last_deal_date, status, sort_order, remark) 
                    VALUES (:name, :avatar, :phone, :password, :password_view, :role_id, :email, :wechat, :level, :store_id, :store_name, :entry_date, :sales_amount, :deal_count, :last_deal_date, :status, :sort_order, :remark)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':avatar' => $data['avatar'] ?? '',
                ':phone' => $data['phone'],
                ':password' => $data['password'] ?? null,
                ':password_view' => $data['password_view'] ?? null,
                ':role_id' => $data['role_id'] ?? null,
                ':email' => $data['email'] ?? '',
                ':wechat' => $data['wechat'] ?? '',
                ':level' => $data['level'] ?? '小白',
                ':store_id' => $data['store_id'] ?? null,
                ':store_name' => $data['store_name'] ?? '',
                ':entry_date' => empty($data['entry_date']) ? null : $data['entry_date'],
                ':sales_amount' => $data['sales_amount'] ?? 0,
                ':deal_count' => $data['deal_count'] ?? 0,
                ':last_deal_date' => empty($data['last_deal_date']) ? null : $data['last_deal_date'],
                ':status' => $data['status'] ?? '在职',
                ':sort_order' => $data['sort_order'] ?? 0,
                ':remark' => $data['remark'] ?? ''
            ]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => '创建成功',
                'id' => $newId
            ]);
            break;
            
        case 'update':
            // 更新销售人员
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            
            if (empty($data['name']) || empty($data['phone'])) {
                throw new Exception('姓名和手机号为必填项');
            }
            
            // 检查手机号是否被其他人使用
            $stmt = $pdo->prepare("SELECT id FROM salesmen WHERE phone = :phone AND id != :id");
            $stmt->execute([
                ':phone' => $data['phone'],
                ':id' => $data['id']
            ]);
            if ($stmt->fetch()) {
                throw new Exception('该手机号已被其他销售人员使用');
            }
            
            // 构建 UPDATE SQL（不包含销售额、成交量、最后成交日期，这些字段自动计算）
            $fields = [
                "name = :name",
                "avatar = :avatar",
                "phone = :phone",
                "email = :email",
                "wechat = :wechat",
                "level = :level",
                "store_id = :store_id",
                "store_name = :store_name",
                "entry_date = :entry_date",
                "status = :status",
                "sort_order = :sort_order",
                "remark = :remark",
                "role_id = :role_id"
            ];
            
            // 如果有密码则添加
            if (!empty($data['password'])) {
                $fields[] = "password = :password";
            }
            
            $sql = "UPDATE salesmen SET " . implode(', ', $fields) . " WHERE id = :id";
            
            // 构建参数数组
            $params = [
                ':id' => $data['id'],
                ':name' => $data['name'],
                ':avatar' => $data['avatar'] ?? '',
                ':phone' => $data['phone'],
                ':email' => $data['email'] ?? '',
                ':wechat' => $data['wechat'] ?? '',
                ':level' => $data['level'] ?? '小白',
                ':store_id' => $data['store_id'] ?? null,
                ':store_name' => $data['store_name'] ?? '',
                ':entry_date' => empty($data['entry_date']) ? null : $data['entry_date'],
                ':status' => $data['status'] ?? '在职',
                ':sort_order' => $data['sort_order'] ?? 0,
                ':remark' => $data['remark'] ?? '',
                ':role_id' => $data['role_id'] ?? null
            ];
            
            // 添加密码
            if (!empty($data['password'])) {
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => '更新成功'
            ]);
            break;
            
        case 'delete':
            // 删除销售人员
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            
            // 检查是否有客户关联
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE owner_id = :id1 OR sales_user_id = :id2");
            $checkStmt->execute([':id1' => $data['id'], ':id2' => $data['id']]);
            $customerCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($customerCount > 0) {
                throw new Exception("无法删除：该销售名下有 {$customerCount} 个客户，请先转移或删除客户");
            }
            
            $stmt = $pdo->prepare("DELETE FROM salesmen WHERE id = :id1");
            $stmt->execute([':id1' => $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => '删除成功'
            ]);
            break;
            
        case 'batch_delete':
            // 批量删除销售人员
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['ids']) || !is_array($data['ids'])) {
                throw new Exception('缺少 IDs 参数');
            }
            
            // 检查是否有客户关联
            $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
            $checkStmt = $pdo->prepare("SELECT DISTINCT owner_id FROM customers WHERE owner_id IN ({$placeholders})");
            $checkStmt->execute($data['ids']);
            $ownersWithCustomers = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($ownersWithCustomers)) {
                $ownersStr = implode(', ', $ownersWithCustomers);
                throw new Exception("无法删除：以下销售 ID 名下有客户，请先转移或删除客户：{$ownersStr}");
            }
            
            $stmt = $pdo->prepare("DELETE FROM salesmen WHERE id IN ({$placeholders})");
            $stmt->execute($data['ids']);
            
            echo json_encode([
                'success' => true,
                'message' => '批量删除成功',
                'deleted_count' => $stmt->rowCount()
            ]);
            break;
            
        case 'toggle_status':
            // 切换销售人员状态（禁用/启用）
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            
            if (empty($data['status']) || !in_array($data['status'], ['在职', '离职'])) {
                throw new Exception('状态参数无效');
            }
            
            // 检查销售是否存在
            $checkStmt = $pdo->prepare("SELECT id, name FROM salesmen WHERE id = ?");
            $checkStmt->execute([$data['id']]);
            $salesman = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$salesman) {
                throw new Exception('销售人员不存在');
            }
            
            // 如果有客户关联，禁止禁用
            if ($data['status'] === '离职') {
                $customerCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE owner_id = ? OR sales_user_id = ?");
                $customerCheckStmt->execute([$data['id'], $data['id']]);
                $customerCount = $customerCheckStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($customerCount > 0) {
                    throw new Exception("无法禁用：该销售名下有 {$customerCount} 个客户，请先转移或删除客户");
                }
            }
            
            $stmt = $pdo->prepare("UPDATE salesmen SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => '状态更新成功',
                'salesman_name' => $salesman['name'],
                'new_status' => $data['status']
            ]);
            break;
            
        case 'batch_update_status':
            // 批量更新状态
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['ids']) || !is_array($data['ids'])) {
                throw new Exception('缺少 IDs 参数');
            }
            
            if (empty($data['status'])) {
                throw new Exception('缺少状态参数');
            }
            
            $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE salesmen SET status = ? WHERE id IN ({$placeholders})");
            $params = array_merge([$data['status']], $data['ids']);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => '批量更新状态成功',
                'updated_count' => $stmt->rowCount()
            ]);
            break;
            
        case 'batch_update_level':
            // 批量更新等级
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['ids']) || !is_array($data['ids'])) {
                throw new Exception('缺少 IDs 参数');
            }
            
            if (empty($data['level'])) {
                throw new Exception('缺少等级参数');
            }
            
            $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE salesmen SET level = ? WHERE id IN ({$placeholders})");
            $params = array_merge([$data['level']], $data['ids']);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => '批量更新等级成功',
                'updated_count' => $stmt->rowCount()
            ]);
            break;
            
        case 'stats':
            // 获取统计数据
            $stats = [];
            
            // 总人数
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM salesmen");
            $stats['total_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 在职人数
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM salesmen WHERE status = '在职'");
            $stats['active_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 离职人数
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM salesmen WHERE status = '离职'");
            $stats['inactive_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 总销售额
            $stmt = $pdo->query("SELECT SUM(sales_amount) as total FROM salesmen");
            $stats['total_sales'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 总成交量
            $stmt = $pdo->query("SELECT SUM(deal_count) as total FROM salesmen");
            $stats['total_deals'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 按等级统计
            $stmt = $pdo->query("SELECT level, COUNT(*) as count FROM salesmen WHERE status = '在职' GROUP BY level");
            $levelStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats['level_distribution'] = $levelStats;
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'levels':
            // 获取等级选项
            $levels = ['小白', '初级', '中级', '高级', '金牌', '王牌'];
            echo json_encode([
                'success' => true,
                'data' => $levels
            ]);
            break;
            
        case 'stores':
            // 获取门店列表
            $stmt = $pdo->query("SELECT id, name FROM stores ORDER BY id");
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => $stores
            ]);
            break;
            
        default:
            throw new Exception('未知的操作');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
