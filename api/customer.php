<?php
/**
 * 客户管理 API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// 加载项目引导文件（定义路径常量）
require_once __DIR__ . '/../bootstrap.php';

// 加载数据库配置并创建连接
$config = require_once CONFIG_PATH . 'database.php';

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
    // 设置字符集
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// 检查登录状态和账号是否被删除（仅针对销售人员）
if (isset($_SESSION['login_type']) && $_SESSION['login_type'] === 'salesman') {
    try {
        $stmt = $pdo->prepare('SELECT id, status FROM salesmen WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $salesman = $stmt->fetch();
        
        if (!$salesman) {
            session_destroy();
            echo json_encode(['code' => 403, 'message' => '账号已被删除，请重新登录', 'need_logout' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($salesman['status'] !== '在职') {
            session_destroy();
            echo json_encode(['code' => 403, 'message' => '账号已禁用，请联系管理员', 'need_logout' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (PDOException $e) {
        // 数据库错误，继续执行
    }
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            getList($pdo);
            break;
        case 'detail':
            getDetail($pdo);
            break;
        case 'create':
            create($pdo);
            break;
        case 'update':
            update($pdo);
            break;
        case 'set_status':
            setStatus($pdo);
            break;
        case 'update_status':
            updateStatus($pdo);
            break;
        case 'delete':
            delete($pdo);
            break;
        case 'positions':
            getPositions($pdo);
            break;
        case 'sales_users':
            getSalesUsers($pdo);
            break;
        case 'salesmen_list':
            getSalesmenList($pdo);
            break;
        case 'progress':
            getProgress($pdo);
            break;
        case 'update_profile':
            updateProfile($pdo);
            break;
        case 'documents':
            getDocuments($pdo);
            break;
        case 'add_progress':
            addProgress($pdo);
            break;
        case 'get_progress':
            getProgress($pdo);
            break;
        case 'get_documents':
            getDocuments($pdo);
            break;
        case 'upload_document':
            uploadDocument($pdo);
            break;
        case 'delete_document':
            deleteDocument($pdo);
            break;
        case 'delete_progress':
            deleteProgress($pdo);
            break;
        default:
            echo json_encode(['code' => 400, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取客户列表
 */
function getList($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, (int)($_GET['page_size'] ?? 20));
    $offset = ($page - 1) * $pageSize;
    
    $keyword = $_GET['keyword'] ?? '';
    $country = $_GET['country'] ?? '';
    $status = $_GET['status'] ?? '';
    
    // 检查登录用户类型
    $loginType = $_SESSION['login_type'] ?? 'user';
    $userId = $_SESSION['user_id'] ?? 0;
    
    $where = ['1=1'];
    $params = [];
    
    // 销售人员只能看到分配给自己的客户
    if ($loginType === 'salesman') {
        $where[] = 'c.owner_id = :owner_id';
        $params[':owner_id'] = $userId;
    }
    
    if ($keyword) {
        $where[] = '(c.name LIKE :keyword OR c.phone LIKE :keyword OR c.email LIKE :keyword)';
        $params[':keyword'] = "%{$keyword}%";
    }
    if ($country) {
        $where[] = 'c.country = :country';
        $params[':country'] = $country;
    }
    if ($status !== '') {
        $where[] = 'c.status = :status';
        $params[':status'] = (int)$status;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 获取总数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereClause}");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 获取数据（关联查询获取负责人名称）
    $stmt = $pdo->prepare("
        SELECT c.*, 
               s.name as salesman_name,
               u.username as user_name, 
               u.nickname as user_nickname,
               COALESCE(s.name, u.nickname, u.username) as owner_name
        FROM customers c
        LEFT JOIN salesmen s ON c.owner_id = s.id
        LEFT JOIN users u ON c.owner_id = u.id
        WHERE {$whereClause}
        ORDER BY c.id DESC
        LIMIT :offset, :limit
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'list' => $list,
            'pagination' => [
                'total' => (int)$total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取客户详情
 */
function getDetail($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    // 检查登录用户类型
    $loginType = $_SESSION['login_type'] ?? 'user';
    $userId = $_SESSION['user_id'] ?? 0;
    
    // 销售人员只能查看分配给自己的客户
    if ($loginType === 'salesman') {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   s.name as salesman_name,
                   u.username as user_name, 
                   u.nickname as user_nickname,
                   COALESCE(s.name, u.nickname, u.username) as owner_name
            FROM customers c
            LEFT JOIN salesmen s ON c.owner_id = s.id
            LEFT JOIN users u ON c.owner_id = u.id
            WHERE c.id = :id AND c.owner_id = :owner_id
        ");
        $stmt->execute([':id' => $id, ':owner_id' => $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   s.name as salesman_name,
                   u.username as user_name, 
                   u.nickname as user_nickname,
                   COALESCE(s.name, u.nickname, u.username) as owner_name
            FROM customers c
            LEFT JOIN salesmen s ON c.owner_id = s.id
            LEFT JOIN users u ON c.owner_id = u.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
    }
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode(['code' => 404, 'message' => '客户不存在或无权查看'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 创建客户
 */
function create($pdo) {
    $name = $_POST['name'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    // 验证必填字段
    if (empty($name)) {
        echo json_encode(['code' => 400, 'message' => '客户姓名为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (!$user_id) {
        echo json_encode(['code' => 400, 'message' => '必须从用户管理中选择已有用户'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证用户是否存在于平台 users 表或 mini_program_users 表中
    $platformConfig = require_once CONFIG_PATH . 'database_platform.php';
    try {
        $platformPdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $platformConfig['host'],
                $platformConfig['port'],
                $platformConfig['database'],
                $platformConfig['charset']
            ),
            $platformConfig['username'],
            $platformConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $platformPdo->exec("SET NAMES utf8mb4");
        
        // 同时检查 users 表和 mini_program_users 表，支持小程序用户
        $stmt = $platformPdo->prepare("
            SELECT id FROM users WHERE id = :id AND status = 1
            UNION
            SELECT id FROM mini_program_users WHERE id = :id AND status = 1
        ");
        $stmt->execute([':id' => $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['code' => 400, 'message' => '选择的用户不存在或已禁用'], JSON_UNESCAPED_UNICODE);
            return;
        }
    } catch (PDOException $e) {
        echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 自动设置 owner_id：如果是销售人员，自动设置为自己的 ID
    $loginType = $_SESSION['login_type'] ?? 'user';
    $userId = $_SESSION['user_id'] ?? 0;
    $ownerId = (int)($_POST['owner_id'] ?? 0);
    
    // 如果销售人员创建客户，强制设置 owner_id 为自己
    if ($loginType === 'salesman' && $userId > 0) {
        $ownerId = $userId;
    }
    
    $data = [
        'name' => $name,
        'user_id' => $user_id,
        'age' => (int)($_POST['age'] ?? 0),
        'gender' => (int)($_POST['gender'] ?? 0),
        'phone' => $_POST['phone'] ?? '',
        'education' => $_POST['education'] ?? '',
        'email' => $_POST['email'] ?? '',
        'wechat' => $_POST['wechat'] ?? '',
        'country' => $_POST['country'] ?? '',
        'position_id' => (int)($_POST['position_id'] ?? 0),
        'visa_type' => $_POST['visa_type'] ?? '',
        'source' => $_POST['source'] ?? '',
        'sales_user_id' => (int)($_POST['sales_user_id'] ?? 0),
        'owner_id' => $ownerId,
        'status' => (int)($_POST['status'] ?? 0),
        'marital_status' => $_POST['marital_status'] ?? '',
        'children_status' => $_POST['children_status'] ?? '',
        'work_status' => $_POST['work_status'] ?? '',
        'flow_status' => $_POST['flow_status'] ?? '',
        'social_security_status' => $_POST['social_security_status'] ?? '',
        'skills' => $_POST['skills'] ?? '',
        'hometown' => $_POST['hometown'] ?? '',
        'vehicle' => $_POST['vehicle'] ?? '',
        'property' => $_POST['property'] ?? '',
        'financial_status' => $_POST['financial_status'] ?? '',
        'ethnicity' => $_POST['ethnicity'] ?? '',
        'school' => $_POST['school'] ?? '',
        'major' => $_POST['major'] ?? '',
        'expected_salary' => $_POST['expected_salary'] ?? '',
        'expected_city' => $_POST['expected_city'] ?? '',
        'graduate_year' => $_POST['graduate_year'] ?? '',
        'work_experience' => $_POST['work_experience'] ?? '',
        'birthday' => !empty($_POST['birthday']) ? $_POST['birthday'] : null,
        'remark' => $_POST['remark'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO customers 
        (name, user_id, age, gender, phone, education, email, wechat, country, position_id, visa_type, source, sales_user_id, owner_id, status, marital_status, children_status, work_status, flow_status, social_security_status, skills, hometown, vehicle, property, financial_status, ethnicity, school, major, expected_salary, expected_city, graduate_year, work_experience, birthday, remark, created_at, updated_at)
        VALUES 
        (:name, :user_id, :age, :gender, :phone, :education, :email, :wechat, :country, :position_id, :visa_type, :source, :sales_user_id, :owner_id, :status, :marital_status, :children_status, :work_status, :flow_status, :social_security_status, :skills, :hometown, :vehicle, :property, :financial_status, :ethnicity, :school, :major, :expected_salary, :expected_city, :graduate_year, :work_experience, :birthday, :remark, :created_at, :updated_at)
    ");
    
    $stmt->execute($data);
    $customerId = $pdo->lastInsertId();
    
    // 保存资料文件
    if (isset($_POST['documents']) && !empty($_POST['documents'])) {
        $documents = json_decode($_POST['documents'], true);
        if (is_array($documents)) {
            $stmt = $pdo->prepare("INSERT INTO customer_documents (customer_id, doc_type, file_path, file_name, file_size, uploaded_by) VALUES (:customer_id, :doc_type, :file_path, :file_name, :file_size, :uploaded_by)");
            foreach ($documents as $doc) {
                $stmt->execute([
                    ':customer_id' => $customerId,
                    ':doc_type' => $doc['type'] ?? '',
                    ':file_path' => $doc['path'] ?? '',
                    ':file_name' => $doc['name'] ?? '',
                    ':file_size' => $doc['size'] ?? 0,
                    ':uploaded_by' => $user_id
                ]);
            }
        }
    }
    
    // 保存办理进度
    if (isset($_POST['progress']) && !empty($_POST['progress'])) {
        $progressRecords = json_decode($_POST['progress'], true);
        if (is_array($progressRecords)) {
            $stmt = $pdo->prepare("INSERT INTO customer_progress (customer_id, progress_text, created_by) VALUES (:customer_id, :progress_text, :created_by)");
            foreach ($progressRecords as $record) {
                $stmt->execute([
                    ':customer_id' => $customerId,
                    ':progress_text' => $record['text'] ?? '',
                    ':created_by' => $user_id
                ]);
            }
        }
    }
    
    echo json_encode([
        'code' => 200,
        'message' => '创建成功',
        'data' => ['id' => $customerId]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 更新客户
 */
function update($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $name = $_POST['name'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    // 验证必填字段
    if (empty($name)) {
        echo json_encode(['code' => 400, 'message' => '客户姓名为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (!$user_id) {
        echo json_encode(['code' => 400, 'message' => '必须从用户管理中选择已有用户'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查登录用户类型和权限
    $loginType = $_SESSION['login_type'] ?? 'user';
    $userId = $_SESSION['user_id'] ?? 0;
    
    // 获取当前的 owner_id（用于检测是否转移客户）
    $stmt = $pdo->prepare("SELECT owner_id FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $oldOwnerId = (int)$stmt->fetchColumn();
    
    // 如果是销售人员，检查是否只能更新自己的客户
    if ($loginType === 'salesman') {
        // 如果不是自己的客户，拒绝更新
        if ($oldOwnerId != $userId) {
            echo json_encode(['code' => 403, 'message' => '无权更新此客户！该客户不属于您。'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 强制保持 owner_id 为自己，防止被篡改
        $_POST['owner_id'] = $userId;
    }
    
    // 验证用户是否存在于平台 users 表或 mini_program_users 表中
    $platformConfig = require_once CONFIG_PATH . 'database_platform.php';
    try {
        $platformPdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $platformConfig['host'],
                $platformConfig['port'],
                $platformConfig['database'],
                $platformConfig['charset']
            ),
            $platformConfig['username'],
            $platformConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $platformPdo->exec("SET NAMES utf8mb4");
        
        // 同时检查 users 表和 mini_program_users 表，支持小程序用户
        $stmt = $platformPdo->prepare("
            SELECT id FROM users WHERE id = :id AND status = 1
            UNION
            SELECT id FROM mini_program_users WHERE id = :id AND status = 1
        ");
        $stmt->execute([':id' => $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['code' => 400, 'message' => '选择的用户不存在或已禁用'], JSON_UNESCAPED_UNICODE);
            return;
        }
    } catch (PDOException $e) {
        echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $data = [
        'id' => $id,
        'name' => $name,
        'user_id' => $user_id,
        'age' => (int)($_POST['age'] ?? 0),
        'gender' => (int)($_POST['gender'] ?? 0),
        'phone' => $_POST['phone'] ?? '',
        'education' => $_POST['education'] ?? '',
        'email' => $_POST['email'] ?? '',
        'wechat' => $_POST['wechat'] ?? '',
        'country' => $_POST['country'] ?? '',
        'position_id' => (int)($_POST['position_id'] ?? 0),
        'visa_type' => $_POST['visa_type'] ?? '',
        'source' => $_POST['source'] ?? '',
        'sales_user_id' => (int)($_POST['sales_user_id'] ?? 0),
        'owner_id' => (int)($_POST['owner_id'] ?? 0),
        'status' => (int)($_POST['status'] ?? 0),
        'marital_status' => $_POST['marital_status'] ?? '',
        'children_status' => $_POST['children_status'] ?? '',
        'work_status' => $_POST['work_status'] ?? '',
        'flow_status' => $_POST['flow_status'] ?? '',
        'social_security_status' => $_POST['social_security_status'] ?? '',
        'skills' => $_POST['skills'] ?? '',
        'hometown' => $_POST['hometown'] ?? '',
        'vehicle' => $_POST['vehicle'] ?? '',
        'property' => $_POST['property'] ?? '',
        'financial_status' => $_POST['financial_status'] ?? '',
        'ethnicity' => $_POST['ethnicity'] ?? '',
        'school' => $_POST['school'] ?? '',
        'major' => $_POST['major'] ?? '',
        'expected_salary' => $_POST['expected_salary'] ?? '',
        'expected_city' => $_POST['expected_city'] ?? '',
        'graduate_year' => $_POST['graduate_year'] ?? '',
        'work_experience' => $_POST['work_experience'] ?? '',
        'birthday' => !empty($_POST['birthday']) ? $_POST['birthday'] : null,
        'remark' => $_POST['remark'] ?? '',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        UPDATE customers SET
            name = :name,
            user_id = :user_id,
            age = :age,
            gender = :gender,
            phone = :phone,
            education = :education,
            email = :email,
            wechat = :wechat,
            country = :country,
            position_id = :position_id,
            visa_type = :visa_type,
            source = :source,
            sales_user_id = :sales_user_id,
            owner_id = :owner_id,
            status = :status,
            marital_status = :marital_status,
            children_status = :children_status,
            work_status = :work_status,
            flow_status = :flow_status,
            social_security_status = :social_security_status,
            skills = :skills,
            hometown = :hometown,
            vehicle = :vehicle,
            property = :property,
            financial_status = :financial_status,
            ethnicity = :ethnicity,
            school = :school,
            major = :major,
            expected_salary = :expected_salary,
            expected_city = :expected_city,
            graduate_year = :graduate_year,
            work_experience = :work_experience,
            birthday = :birthday,
            remark = :remark,
            updated_at = :updated_at
        WHERE id = :id
    ");
    
    $stmt->execute($data);
    
    // 处理已存在的资料文件（编辑时保留的文件）+ 新上传的文件
    // 不再先删除所有资料，而是智能处理：保留 existing_documents，新文件通过 upload_document 添加
    // 始终处理删除逻辑，即使 existing_documents 为空（表示用户删除了所有文件）
    if (isset($_POST['existing_documents'])) {
        $existingDocs = json_decode($_POST['existing_documents'], true);
        if (is_array($existingDocs)) {
            // 获取当前所有资料
            $stmt = $pdo->prepare("SELECT id, doc_type, file_path FROM customer_documents WHERE customer_id = :customer_id");
            $stmt->execute([':customer_id' => $id]);
            $currentDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 找出需要删除的资料（不在 existing_documents 中的）
            $existingDocPaths = array_map(function($doc) { return $doc['file_path']; }, $existingDocs);
            foreach ($currentDocs as $currentDoc) {
                if (!in_array($currentDoc['file_path'], $existingDocPaths)) {
                    // 这个资料不在保留列表中，删除它
                    $deleteStmt = $pdo->prepare("DELETE FROM customer_documents WHERE id = :id");
                    $deleteStmt->execute([':id' => $currentDoc['id']]);
                    
                    // 同时删除服务器上的文件
                    if (!empty($currentDoc['file_path'])) {
                        $filePath = ROOT_PATH . 'qianwutong/' . $currentDoc['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            }
        }
    }
    
    // 保存办理进度
    if (isset($_POST['progress']) && !empty($_POST['progress'])) {
        $progressRecords = json_decode($_POST['progress'], true);
        if (is_array($progressRecords)) {
            $stmt = $pdo->prepare("INSERT INTO customer_progress (customer_id, progress_text, created_by) VALUES (:customer_id, :progress_text, :created_by)");
            foreach ($progressRecords as $record) {
                $stmt->execute([
                    ':customer_id' => $id,
                    ':progress_text' => $record['text'] ?? '',
                    ':created_by' => $user_id
                ]);
            }
        }
    }
    
    // 检测客户是否被转移（owner_id 变更）
    $newOwnerId = (int)($_POST['owner_id'] ?? 0);
    if ($newOwnerId > 0 && $newOwnerId != $oldOwnerId) {
        // 客户被转移，发送通知给新的负责人
        try {
            require_once __DIR__ . '/../includes/NotificationHelper.php';
            
            // 获取原负责人姓名
            $fromUserName = '';
            if ($oldOwnerId > 0) {
                // 先尝试从 salesmen 表获取
                $stmt = $pdo->prepare("SELECT name FROM salesmen WHERE id = :id");
                $stmt->execute([':id' => $oldOwnerId]);
                $oldOwner = $stmt->fetch();
                
                if (!$oldOwner) {
                    // 再从 users 表获取
                    $stmt = $pdo->prepare("SELECT nickname, username FROM users WHERE id = :id");
                    $stmt->execute([':id' => $oldOwnerId]);
                    $oldUser = $stmt->fetch();
                    if ($oldUser) {
                        $fromUserName = $oldUser['nickname'] ?: $oldUser['username'];
                    }
                } else {
                    $fromUserName = $oldOwner['name'];
                }
            }
            
            // 获取新负责人信息（包含等级）
            $stmt = $pdo->prepare("SELECT name, level FROM salesmen WHERE id = :id");
            $stmt->execute([':id' => $newOwnerId]);
            $newOwner = $stmt->fetch();
            
            if ($newOwner) {
                // 发送通知给新的销售人员（包含等级和姓名）
                NotificationHelper::notifyCustomerTransfer(
                    $newOwnerId,
                    $name,
                    $id,
                    $fromUserName,
                    $newOwner['name'],
                    $newOwner['level'] ?? ''
                );
            }
        } catch (Exception $e) {
            // 通知失败不影响主流程，记录日志
            error_log("[Customer Transfer] 发送通知失败：" . $e->getMessage());
        }
    }
    
    echo json_encode([
        'code' => 200,
        'message' => '更新成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 设置客户状态
 */
function setStatus($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE customers SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id, ':status' => $status]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 更新客户状态（批量）
 */
function updateStatus($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE customers SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id, ':status' => $status]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 删除客户
 */
function delete($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '删除成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 小程序 - 更新用户个人资料
 */
function updateProfile($pdo) {
    // 获取请求数据（支持多种格式）
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 如果 JSON 解析失败，尝试 $_POST
    if (!is_array($data) || empty($data)) {
        $data = $_POST;
    }
    
    // 如果还是空，返回错误
    if (empty($data)) {
        echo json_encode([
            'code' => 400,
            'message' => '未收到任何数据',
            'debug' => [
                'raw_input' => substr($input, 0, 500),
                '_POST' => $_POST,
                '_GET' => $_GET
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 从小程序获取用户标识（优先使用 wechat_openid）
    $wechatOpenid = $data['wechat_openid'] ?? '';
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    
    // 调试日志 - 记录到文件
    $debugLog = [
        'function' => 'updateProfile',
        'wechat_openid' => $wechatOpenid,
        'user_id' => $userId,
        'received_data' => $data,
        'raw_input' => file_get_contents('php://input'),
        'time' => date('Y-m-d H:i:s')
    ];
    file_put_contents(__DIR__ . '/../111ceshi/update_profile_debug.log', json_encode($debugLog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    // 通过 openid 或 user_id 验证用户身份
    $currentUserId = 0;
    if ($wechatOpenid) {
        $stmt = $pdo->prepare("SELECT id FROM mini_program_users WHERE wechat_openid = :openid");
        $stmt->execute([':openid' => $wechatOpenid]);
        $currentUserId = (int)$stmt->fetchColumn();
        error_log("[updateProfile] 通过 openid 查询到用户 ID: $currentUserId");
    } elseif ($userId) {
        // 验证 user_id 是否存在
        $stmt = $pdo->prepare("SELECT id FROM mini_program_users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $currentUserId = (int)$stmt->fetchColumn();
        error_log("[updateProfile] 通过 user_id 查询到用户 ID: $currentUserId");
    }
    
    if (!$currentUserId) {
        // 返回详细调试信息
        echo json_encode([
            'code' => 401, 
            'message' => '请先登录',
            'debug' => [
                'wechat_openid' => $wechatOpenid,
                'user_id' => $userId,
                'user_id_type' => gettype($data['user_id'] ?? null),
                'user_id_isset' => isset($data['user_id']) ? 'yes' : 'no',
                'received_data_keys' => array_keys($data),
                'full_data' => $data
            ]
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 获取提交的数据
    $avatar = $data['avatar'] ?? '';
    $nickname = $data['nickname'] ?? '';
    $realName = $data['real_name'] ?? '';
    $gender = $data['gender'] ?? -1;
    $specialty = $data['specialty'] ?? '';
    $age = $data['age'] ?? null;
    $education = $data['education'] ?? '';
    $intendedCountries = $data['intended_countries'] ?? null;
    $positions = $data['positions'] ?? null;
    
    // 验证必填项
    if (empty($realName)) {
        echo json_encode(['code' => 400, 'message' => '真实姓名不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if ($gender === -1 || $gender === '') {
        echo json_encode(['code' => 400, 'message' => '请选择性别'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 更新数据库
    $stmt = $pdo->prepare("
        UPDATE mini_program_users 
        SET 
            avatar = :avatar,
            nickname = :nickname,
            real_name = :real_name,
            gender = :gender,
            specialty = :specialty,
            age = :age,
            education = :education,
            intended_countries = :intended_countries,
            positions = :positions,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->bindValue(':avatar', $avatar);
    $stmt->bindValue(':nickname', $nickname);
    $stmt->bindValue(':real_name', $realName);
    $stmt->bindValue(':gender', $gender);
    $stmt->bindValue(':specialty', $specialty);
    $stmt->bindValue(':age', $age);
    $stmt->bindValue(':education', $education);
    $stmt->bindValue(':intended_countries', $intendedCountries);
    $stmt->bindValue(':positions', $positions);
    $stmt->bindValue(':id', $currentUserId, PDO::PARAM_INT);
    
    $stmt->execute();
    
    echo json_encode([
        'code' => 200,
        'message' => '保存成功',
        'data' => ['id' => $currentUserId]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取岗位列表（用于下拉选择）
 */
function getPositions($pdo) {
    $country = $_GET['country'] ?? '';
    
    if ($country) {
        // 根据国家筛选岗位
        $stmt = $pdo->prepare("SELECT id, title FROM positions WHERE status = 1 AND country = :country ORDER BY id DESC");
        $stmt->execute([':country' => $country]);
    } else {
        // 获取全部岗位
        $stmt = $pdo->query("SELECT id, title FROM positions WHERE status = 1 ORDER BY id DESC");
    }
    
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $positions
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取销售用户列表（用于下拉选择）
 */
function getSalesUsers($pdo) {
    $stmt = $pdo->query("SELECT id, username, nickname FROM users WHERE status = 1 ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $users
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取销售人员列表（用于负责人选择）
 */
function getSalesmenList($pdo) {
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.phone, s.email, r.role_name
        FROM salesmen s
        LEFT JOIN roles r ON s.role_id = r.id
        WHERE s.status = '在职'
        ORDER BY s.id DESC
    ");
    $salesmen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $salesmen
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 客户管理 API - 新增接口
 */

// 添加进度
function addProgress($pdo) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $progress_text = $_POST['progress_text'] ?? '';
    $materials = $_POST['materials'] ?? null;  // JSON 字符串或 null
    
    if (!$customer_id) {
        echo json_encode(['code' => 400, 'message' => '客户 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (empty($progress_text)) {
        echo json_encode(['code' => 400, 'message' => '进度内容不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 初始化材料状态（如果有材料）
    $materialsStatus = null;
    $materialsUploadedAt = null;
    if (!empty($materials) && $materials !== 'null') {
        // 解析材料数组，初始化所有材料状态为 pending
        $materialsArray = json_decode($materials, true);
        if (is_array($materialsArray) && count($materialsArray) > 0) {
            $statusObj = [];
            foreach ($materialsArray as $material) {
                $statusObj[$material] = 'pending';  // 初始状态为未上传
            }
            $materialsStatus = json_encode($statusObj, JSON_UNESCAPED_UNICODE);
            $materialsUploadedAt = json_encode([], JSON_UNESCAPED_UNICODE);  // 空的时间对象
        }
    }
    
    // 保存进度到数据库（包含材料字段）
    try {
        $stmt = $pdo->prepare("
            INSERT INTO customer_progress 
            (customer_id, progress_text, materials, materials_status, materials_uploaded_at, created_by)
            VALUES 
            (:customer_id, :progress_text, :materials, :materials_status, :materials_uploaded_at, :created_by)
        ");
        
        $stmt->execute([
            ':customer_id' => $customer_id,
            ':progress_text' => $progress_text,
            ':materials' => $materials,
            ':materials_status' => $materialsStatus,
            ':materials_uploaded_at' => $materialsUploadedAt,
            ':created_by' => $_SESSION['user_id'] ?? 0
        ]);
    } catch (PDOException $e) {
        error_log("添加进度失败：" . $e->getMessage());
        echo json_encode([
            'code' => 500,
            'message' => '数据库错误：' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 发送订阅消息通知
    try {
        // 获取客户信息（通过手机号查询 OpenID，自动匹配）
        $stmt = $pdo->prepare("SELECT c.name, c.phone, c.user_id, 
                               (SELECT openid FROM users WHERE phone = c.phone LIMIT 1) as openid,
                               (SELECT nickname FROM users WHERE phone = c.phone LIMIT 1) as nickname
                               FROM customers c 
                               WHERE c.id = :customer_id");
        $stmt->execute([':customer_id' => $customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 如果有 OpenID，发送订阅消息
        if ($customer && !empty($customer['openid'])) {
            // 从数据库读取小程序配置
            $configStmt = $pdo->prepare("SELECT `config_key`, `config_value` FROM `system_config` 
                                        WHERE `config_key` IN ('miniprogram_appid', 'miniprogram_secret', 'subscribe_template_id')");
            $configStmt->execute();
            $configs = $configStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $appId = $configs['miniprogram_appid'] ?? '';
            $appSecret = $configs['miniprogram_secret'] ?? '';
            $templateId = $configs['subscribe_template_id'] ?? '';
            
            // 如果配置完整，发送消息
            if ($appId && $appSecret && $templateId) {
                require_once __DIR__ . '/../includes/SubscribeMessageSender.php';
                
                $sender = new SubscribeMessageSender($appId, $appSecret, $templateId);
                
                // 构造消息数据
                $materialsText = '';
                if (!empty($materials)) {
                    $materialsArray = json_decode($materials, true);
                    if (is_array($materialsArray) && count($materialsArray) > 0) {
                        $materialsText = ' 请补充材料：' . implode('、', $materialsArray);
                    }
                }
                
                $progressData = [
                    'handler' => $customer['name'] ?: '青园营地',
                    'order_no' => 'CUST' . str_pad($customer_id, 6, '0', STR_PAD_LEFT),
                    'service_name' => $progress_text . $materialsText,
                    'phone' => $customer['phone'] ?? '暂无',
                    'handle_time' => date('Y-m-d H:i:s')
                ];
                
                $sender->sendProgressNotification($customer['openid'], $progressData);
            }
        }
    } catch (Exception $e) {
        error_log('发送订阅消息失败：' . $e->getMessage());
        // 不中断主流程，仅记录日志
    }
    
    echo json_encode([
        'code' => 200,
        'message' => '进度已添加',
        'data' => ['id' => $pdo->lastInsertId()]
    ], JSON_UNESCAPED_UNICODE);
}

// 获取进度历史
function getProgress($pdo) {
    $customer_id = (int)($_GET['customer_id'] ?? $_GET['id'] ?? 0);
    
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

// 删除进度
function deleteProgress($pdo) {
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
    
    // 根据客户 ID 和进度文本删除（因为前端没有传递进度 ID）
    $stmt = $pdo->prepare("DELETE FROM customer_progress WHERE customer_id = :customer_id AND progress_text = :progress_text");
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':progress_text' => $progress_text
    ]);
    
    echo json_encode([
        'code' => 200,
        'message' => '进度已删除'
    ], JSON_UNESCAPED_UNICODE);
}

// 获取资料列表
function getDocuments($pdo) {
    $customer_id = (int)($_GET['customer_id'] ?? $_GET['id'] ?? 0);
    
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
    
    if (empty($doc_type)) {
        echo json_encode(['code' => 400, 'message' => '资料类型不能为空'], JSON_UNESCAPED_UNICODE);
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

