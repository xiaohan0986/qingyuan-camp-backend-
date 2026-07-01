<?php
/**
 * 财务管理 API
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/Database.php';

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    switch ($action) {
        case 'list':
            // 获取财务记录列表
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $pageSize = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
            $offset = ($page - 1) * $pageSize;
            
            $where = ['1=1'];
            $params = [];
            
            // 关键词筛选
            if (!empty($_GET['keyword'])) {
                $where[] = '(title LIKE :keyword OR ref_no LIKE :keyword OR remark LIKE :keyword)';
                $params[':keyword'] = '%' . $_GET['keyword'] . '%';
            }
            
            // 类型筛选
            if (!empty($_GET['type'])) {
                $where[] = 'type = :type';
                $params[':type'] = $_GET['type'];
            }
            
            // 状态筛选
            if (!empty($_GET['status'])) {
                $where[] = 'status = :status';
                $params[':status'] = $_GET['status'];
            }
            
            // 日期范围筛选
            if (!empty($_GET['date_start'])) {
                $where[] = 'date >= :date_start';
                $params[':date_start'] = $_GET['date_start'];
            }
            if (!empty($_GET['date_end'])) {
                $where[] = 'date <= :date_end';
                $params[':date_end'] = $_GET['date_end'];
            }
            
            $whereClause = implode(' AND ', $where);
            
            // 查询总数
            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM finance_records WHERE {$whereClause}");
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($total / $pageSize);
            
            // 查询数据
            $stmt = $pdo->prepare("SELECT * FROM finance_records 
                                   WHERE {$whereClause} 
                                   ORDER BY date DESC, id DESC 
                                   LIMIT :offset, :limit");
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $records,
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => $totalPages
            ]);
            break;
            
        case 'get':
            // 获取单条记录
            if (empty($_GET['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM finance_records WHERE id = :id");
            $stmt->execute([':id' => $_GET['id']]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('记录不存在');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $record
            ]);
            break;
            
        case 'create':
            // 创建记录
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['type'])) {
                throw new Exception('缺少类型参数');
            }
            if (!isset($data['amount'])) {
                throw new Exception('缺少金额参数');
            }
            if (empty($data['title'])) {
                throw new Exception('缺少摘要参数');
            }
            if (empty($data['date'])) {
                throw new Exception('缺少日期参数');
            }
            if (empty($data['status'])) {
                throw new Exception('缺少状态参数');
            }
            
            $stmt = $pdo->prepare("INSERT INTO finance_records 
                                   (type, amount, title, ref_no, date, status, remark, created_by) 
                                   VALUES 
                                   (:type, :amount, :title, :ref_no, :date, :status, :remark, :created_by)");
            
            $stmt->execute([
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':title' => $data['title'],
                ':ref_no' => $data['ref_no'] ?? null,
                ':date' => $data['date'],
                ':status' => $data['status'],
                ':remark' => $data['remark'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '创建成功'
            ]);
            break;
            
        case 'update':
            // 更新记录
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            if (empty($data['type'])) {
                throw new Exception('缺少类型参数');
            }
            if (!isset($data['amount'])) {
                throw new Exception('缺少金额参数');
            }
            if (empty($data['title'])) {
                throw new Exception('缺少摘要参数');
            }
            if (empty($data['date'])) {
                throw new Exception('缺少日期参数');
            }
            if (empty($data['status'])) {
                throw new Exception('缺少状态参数');
            }
            
            $stmt = $pdo->prepare("UPDATE finance_records SET
                                   type = :type,
                                   amount = :amount,
                                   title = :title,
                                   ref_no = :ref_no,
                                   date = :date,
                                   status = :status,
                                   remark = :remark,
                                   updated_by = :updated_by
                                   WHERE id = :id");
            
            $stmt->execute([
                ':id' => $data['id'],
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':title' => $data['title'],
                ':ref_no' => $data['ref_no'] ?? null,
                ':date' => $data['date'],
                ':status' => $data['status'],
                ':remark' => $data['remark'] ?? null,
                ':updated_by' => $_SESSION['user_id'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => '更新成功'
            ]);
            break;
            
        case 'delete':
            // 删除记录
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('缺少 ID 参数');
            }
            
            $stmt = $pdo->prepare("DELETE FROM finance_records WHERE id = :id");
            $stmt->execute([':id' => $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => '删除成功'
            ]);
            break;
            
        case 'stats':
            // 获取统计数据
            $stats = [];
            
            // 总收入
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM finance_records WHERE type = 'income'");
            $stats['total_income'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 总支出
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM finance_records WHERE type = 'expense'");
            $stats['total_expense'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 结余
            $stats['balance'] = $stats['total_income'] - $stats['total_expense'];
            
            // 待收款（收入类未收款）
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM finance_records WHERE type = 'income' AND status = 'unpaid'");
            $stats['receivable'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 待付款（支出类未付款）
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM finance_records WHERE type = 'expense' AND status = 'unpaid'");
            $stats['payable'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            // 本月统计
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM finance_records WHERE type = 'income' AND date >= :start AND date < :end");
            $stmt->execute([':start' => $currentMonth, ':end' => $nextMonth]);
            $stats['month_income'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM finance_records WHERE type = 'expense' AND date >= :start AND date < :end");
            $stmt->execute([':start' => $currentMonth, ':end' => $nextMonth]);
            $stats['month_expense'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            $stats['month_balance'] = $stats['month_income'] - $stats['month_expense'];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('无效的 action 参数');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
