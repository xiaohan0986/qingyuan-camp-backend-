<?php
/**
 * 销售管理 API
 */

header('Content-Type: application/json; charset=utf-8');

// 加载项目引导文件
require_once __DIR__ . '/../bootstrap.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'OperationLogger.php';

// 检查登录状态
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['code' => 401, 'message' => '未登录']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = Database::getInstance();
$logger = new OperationLogger();

switch ($action) {
    case 'stats':
        getSalesStats($db);
        break;
    
    case 'list':
        getSalesList($db);
        break;
    
    case 'get':
        getSaleDetail($db);
        break;
    
    case 'create':
        createSale($db, $logger);
        break;
    
    case 'update':
        updateSale($db, $logger);
        break;
    
    case 'salesmen':
        getSalesmen($db);
        break;
    
    case 'export':
        exportSales($db);
        break;
    
    default:
        echo json_encode(['code' => 400, 'message' => '无效的操作']);
}

/**
 * 获取销售统计
 */
function getSalesStats($db) {
    try {
        // 总销售额和成交数
        $totalData = $db->fetch("SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(amount), 0) as total_amount
            FROM sales 
            WHERE status = '已成交'");
        
        // 本月销售额
        $currentMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        
        $monthData = $db->fetch("SELECT 
            COALESCE(SUM(amount), 0) as month_amount
            FROM sales 
            WHERE status = '已成交' 
            AND close_date >= ?", [$currentMonth]);
        
        // 上月销售额（用于计算环比）
        $lastMonthData = $db->fetch("SELECT 
            COALESCE(SUM(amount), 0) as last_month_amount
            FROM sales 
            WHERE status = '已成交' 
            AND close_date >= ? AND close_date < ?", [$lastMonth, $currentMonth]);
        
        // 计算环比
        $monthTrend = 0;
        if ($lastMonthData['last_month_amount'] > 0) {
            $monthTrend = (($monthData['month_amount'] - $lastMonthData['last_month_amount']) / $lastMonthData['last_month_amount']) * 100;
        }
        
        // 成交客户数
        $closedData = $db->fetch("SELECT COUNT(DISTINCT customer_id) as closed_customers FROM sales WHERE status = '已成交'");
        
        // 跟进中客户数 - 如果没有 customer_id 字段，使用 customer_phone
        $pendingData = $db->fetch("SELECT COUNT(DISTINCT customer_phone) as pending_customers FROM sales WHERE status = '跟进中'");
        
        // 转化率（需要总客户数）
        $totalCustomers = $db->fetch("SELECT COUNT(*) as total_customers FROM customers");
        
        $conversionRate = 0;
        if ($totalCustomers['total_customers'] > 0) {
            $conversionRate = ($closedData['closed_customers'] / $totalCustomers['total_customers']) * 100;
        }
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_sales' => (int)($totalData['total_sales'] ?? 0),
                'total_amount' => floatval($totalData['total_amount'] ?? 0),
                'month_amount' => floatval($monthData['month_amount'] ?? 0),
                'month_trend' => round($monthTrend, 2),
                'closed_customers' => (int)($closedData['closed_customers'] ?? 0),
                'pending_customers' => (int)($pendingData['pending_customers'] ?? 0),
                'conversion_rate' => round($conversionRate, 2)
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 获取销售列表
 */
function getSalesList($db) {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 20;
        $offset = ($page - 1) * $pageSize;
        
        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? '';
        $salesman = $_GET['salesman'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = '(customer_name LIKE ? OR customer_phone LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($status) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        
        if ($salesman) {
            $where[] = 'salesman_id = ?';
            $params[] = $salesman;
        }
        
        if ($startDate) {
            $where[] = 'close_date >= ?';
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $where[] = 'close_date <= ?';
            $params[] = $endDate;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM sales WHERE {$whereClause}";
        $total = $db->fetch($countSql, $params)['total'];
        
        // 获取列表
        $params[] = $pageSize;
        $params[] = $offset;
        
        $listSql = "SELECT s.*, u.nickname as salesman_name 
            FROM sales s 
            LEFT JOIN users u ON s.salesman_id = u.id 
            WHERE {$whereClause} 
            ORDER BY s.created_at DESC 
            LIMIT ? OFFSET ?";
        
        $list = $db->fetchAll($listSql, $params);
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => (int)$total,
                'page' => $page,
                'page_size' => $pageSize
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 获取销售详情
 */
function getSaleDetail($db) {
    try {
        $id = $_GET['id'] ?? 0;
        
        $data = $db->fetch("SELECT s.*, u.nickname as salesman_name 
            FROM sales s 
            LEFT JOIN users u ON s.salesman_id = u.id 
            WHERE s.id = ?", [$id]);
        
        if (!$data) {
            echo json_encode(['code' => 404, 'message' => '记录不存在']);
            return;
        }
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 创建销售记录
 */
function createSale($db, $logger) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $data = [
            'customer_name' => $input['customer_name'] ?? '',
            'customer_phone' => $input['customer_phone'] ?? '',
            'salesman_id' => $input['salesman_id'] ?? 0,
            'amount' => $input['amount'] ?? 0,
            'product_type' => $input['product_type'] ?? '',
            'status' => $input['status'] ?? '跟进中',
            'close_date' => $input['close_date'] ?? null,
            'remark' => $input['remark'] ?? ''
        ];
        
        // 验证必填项
        if (!$data['customer_name'] || !$data['customer_phone'] || !$data['salesman_id'] || !$data['amount']) {
            echo json_encode(['code' => 400, 'message' => '请填写必填项']);
            return;
        }
        
        $db->execute("INSERT INTO sales (customer_name, customer_phone, salesman_id, amount, product_type, status, close_date, remark, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())", 
            [$data['customer_name'], $data['customer_phone'], $data['salesman_id'], $data['amount'], $data['product_type'], $data['status'], $data['close_date'], $data['remark']]);
        
        $logger->log('创建销售', "创建销售记录：{$data['customer_name']}，金额：¥{$data['amount']}");
        
        echo json_encode(['code' => 200, 'message' => '创建成功']);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 更新销售记录
 */
function updateSale($db, $logger) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? 0;
        if (!$id) {
            echo json_encode(['code' => 400, 'message' => '缺少 ID']);
            return;
        }
        
        $data = [
            'customer_name' => $input['customer_name'] ?? '',
            'customer_phone' => $input['customer_phone'] ?? '',
            'salesman_id' => $input['salesman_id'] ?? 0,
            'amount' => $input['amount'] ?? 0,
            'product_type' => $input['product_type'] ?? '',
            'status' => $input['status'] ?? '跟进中',
            'close_date' => $input['close_date'] ?? null,
            'remark' => $input['remark'] ?? ''
        ];
        
        $db->execute("UPDATE sales SET 
            customer_name = ?, 
            customer_phone = ?, 
            salesman_id = ?, 
            amount = ?, 
            product_type = ?, 
            status = ?, 
            close_date = ?, 
            remark = ?, 
            updated_at = NOW() 
            WHERE id = ?", 
            [$data['customer_name'], $data['customer_phone'], $data['salesman_id'], $data['amount'], $data['product_type'], $data['status'], $data['close_date'], $data['remark'], $id]);
        
        $logger->log('更新销售', "更新销售记录 ID:{$id}，客户：{$data['customer_name']}");
        
        echo json_encode(['code' => 200, 'message' => '更新成功']);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 获取销售顾问列表
 */
function getSalesmen($db) {
    try {
        $list = $db->fetchAll("SELECT id, nickname, username FROM users WHERE role IN (1, 2) ORDER BY nickname");
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $list
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}

/**
 * 导出销售数据
 */
function exportSales($db) {
    try {
        $list = $db->fetchAll("SELECT s.*, u.nickname as salesman_name 
            FROM sales s 
            LEFT JOIN users u ON s.salesman_id = u.id 
            ORDER BY s.created_at DESC");
        
        // 设置 CSV 头
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sales_' . date('Y-m-d_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // 添加 BOM 以支持 Excel 打开中文
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // 表头
        fputcsv($output, ['ID', '客户姓名', '客户手机号', '销售顾问', '销售金额', '产品类型', '状态', '成交日期', '备注', '创建时间']);
        
        // 数据
        foreach ($list as $item) {
            fputcsv($output, [
                $item['id'],
                $item['customer_name'],
                $item['customer_phone'],
                $item['salesman_name'] ?? '',
                $item['amount'],
                $item['product_type'],
                $item['status'],
                $item['close_date'],
                $item['remark'],
                $item['created_at']
            ]);
        }
        
        fclose($output);
        
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()]);
    }
}
