/**
 * 创建客户
 */
function create($pdo) {
    $name = $_POST['name'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    // 验证必填字段
    if (empty($name)) {
        echo json_encode(['code' => 400, 'message' => '客户名称为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (!$user_id) {
        echo json_encode(['code' => 400, 'message' => '必须从用户管理中选择已有用户'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证用户是否存在于平台 users 表中
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
        
        $stmt = $platformPdo->prepare("SELECT id FROM users WHERE id = :id AND status = 1");
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
        'name' => $name,
        'user_id' => $user_id,
        'age' => (int)($_POST['age'] ?? 0),
        'phone' => $_POST['phone'] ?? '',
        'education' => $_POST['education'] ?? '',
        'email' => $_POST['email'] ?? '',
        'wechat' => $_POST['wechat'] ?? '',
        'country' => $_POST['country'] ?? '',
        'position_id' => (int)($_POST['position_id'] ?? 0),
        'visa_type' => $_POST['visa_type'] ?? '',
        'source' => $_POST['source'] ?? '',
        'sales_user_id' => (int)($_POST['sales_user_id'] ?? 0),
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
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO customers 
        (name, user_id, age, phone, education, email, wechat, country, position_id, visa_type, source, sales_user_id, status, marital_status, children_status, work_status, flow_status, social_security_status, skills, hometown, vehicle, property, financial_status, created_at, updated_at)
        VALUES 
        (:name, :user_id, :age, :phone, :education, :email, :wechat, :country, :position_id, :visa_type, :source, :sales_user_id, :status, :marital_status, :children_status, :work_status, :flow_status, :social_security_status, :skills, :hometown, :vehicle, :property, :financial_status, :created_at, :updated_at)
    ");
    
    $stmt->execute($data);
    $id = $pdo->lastInsertId();
    
    echo json_encode([
        'code' => 200,
        'message' => '创建成功',
        'data' => ['id' => $id]
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
        echo json_encode(['code' => 400, 'message' => '客户名称为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if (!$user_id) {
        echo json_encode(['code' => 400, 'message' => '必须从用户管理中选择已有用户'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 验证用户是否存在于平台 users 表中
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
        
        $stmt = $platformPdo->prepare("SELECT id FROM users WHERE id = :id AND status = 1");
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
        'phone' => $_POST['phone'] ?? '',
        'education' => $_POST['education'] ?? '',
        'email' => $_POST['email'] ?? '',
        'wechat' => $_POST['wechat'] ?? '',
        'country' => $_POST['country'] ?? '',
        'position_id' => (int)($_POST['position_id'] ?? 0),
        'visa_type' => $_POST['visa_type'] ?? '',
        'source' => $_POST['source'] ?? '',
        'sales_user_id' => (int)($_POST['sales_user_id'] ?? 0),
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
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        UPDATE customers SET
            name = :name,
            user_id = :user_id,
            age = :age,
            phone = :phone,
            education = :education,
            email = :email,
            wechat = :wechat,
            country = :country,
            position_id = :position_id,
            visa_type = :visa_type,
            source = :source,
            sales_user_id = :sales_user_id,
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
            updated_at = :updated_at
        WHERE id = :id
    ");
    
    $stmt->execute($data);
    
    echo json_encode([
        'code' => 200,
        'message' => '更新成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}
