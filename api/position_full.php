<?php
/**
 * 岗位管理 API
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
} catch (PDOException $e) {
    echo json_encode(['code' => 500, 'message' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
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
        case 'toggle_status':
            toggleStatus($pdo);
            break;
        case 'toggle_recommend':
            toggleRecommend($pdo);
            break;
        case 'set_status':
            setStatus($pdo);
            break;
        case 'delete':
            delete($pdo);
            break;
        case 'countries':
            getCountries($pdo);
            break;
        default:
            echo json_encode(['code' => 400, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取岗位列表（支持排序）
 */
function getList($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, (int)($_GET['page_size'] ?? 20));
    $offset = ($page - 1) * $pageSize;
    
    // 获取排序参数
    $orderBy = $_GET['order_by'] ?? 'id';
    $order = strtoupper($_GET['order'] ?? 'DESC');
    
    // 白名单验证，防止 SQL 注入
    $allowedOrderBy = ['id', 'view_count', 'created_at', 'salary_min', 'salary_max'];
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'id';
    }
    if ($order !== 'ASC' && $order !== 'DESC') {
        $order = 'DESC';
    }
    
    // 简单查询，暂不支持筛选
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM positions");
    $stmt->execute();
    $total = (int)$stmt->fetch()['total'];
    
    $offset = (int)$offset;
    $pageSize = (int)$pageSize;
    $stmt = $pdo->prepare("SELECT * FROM positions ORDER BY {$orderBy} {$order} LIMIT {$offset}, {$pageSize}");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => [
            'list' => $list,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取岗位详情
 */
function getDetail($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    // 使用 LEFT JOIN 关联 salesmen 表获取推荐经理信息
    $stmt = $pdo->prepare("
        SELECT p.*, 
               s.name as salesman_name, 
               s.avatar as salesman_avatar, 
               s.phone as salesman_phone,
               s.wechat as salesman_wechat
        FROM positions p
        LEFT JOIN salesmen s ON p.recommend_salesman_id = s.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode(['code' => 404, 'message' => '岗位不存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 添加推荐经理信息（如果关联了业务员）
    if (!empty($data['recommend_salesman_id']) && !empty($data['salesman_name'])) {
        $data['recommend_manager'] = [
            'id' => $data['recommend_salesman_id'],
            'name' => $data['salesman_name'],
            'avatar' => $data['salesman_avatar'] ?? '',
            'phone' => $data['salesman_phone'] ?? '',
            'wechat' => $data['salesman_wechat'] ?? ''
        ];
    } else {
        $data['recommend_manager'] = null;
    }
    
    // 移除关联查询产生的额外字段，保持 API 干净
    unset($data['salesman_name'], $data['salesman_avatar'], $data['salesman_phone'], $data['salesman_wechat']);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 创建岗位
 */
function create($pdo) {
    // 处理所需材料（JSON 格式）
    $requiredMaterials = [];
    if (!empty($_POST['required_materials'])) {
        $materialsText = $_POST['required_materials'];
        $materialsText = trim($materialsText, "[]\"'"); // 去除可能的引号和括号
        if (!empty($materialsText)) {
            $requiredMaterials = array_filter(array_map('trim', explode(',', $materialsText)));
        }
    }
    
    // 处理附件文件（JSON 格式）
    $attachmentFiles = [];
    if (!empty($_POST['attachment_files'])) {
        try {
            $attachmentFiles = json_decode($_POST['attachment_files'], true);
            if (!is_array($attachmentFiles)) {
                $attachmentFiles = [];
            }
        } catch (Exception $e) {
            $attachmentFiles = [];
        }
    }
    
    // 数组键名 不带冒号（根据数据库表结构）
    $data = [
        'title' => $_POST['title'] ?? '',
        'category' => $_POST['category'] ?? '',
        'country' => $_POST['country'] ?? '',
        'city' => $_POST['city'] ?? '',
        'industry' => $_POST['industry'] ?? '',
        'visa_type' => $_POST['visa_type'] ?? '',
        'education_required' => $_POST['education_required'] ?? '',
        'major_required' => $_POST['major_required'] ?? '',
        'age_min' => (int)($_POST['age_min'] ?? 0),
        'age_max' => (int)($_POST['age_max'] ?? 0),
        'languages' => $_POST['languages'] ?? '',
        'skills' => $_POST['skills'] ?? '',
        'salary_range' => $_POST['salary_range'] ?? '',
        'description' => $_POST['description'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'benefits' => $_POST['benefits'] ?? '',
        'tags' => $_POST['tags'] ?? '',
        'required_materials' => json_encode($requiredMaterials, JSON_UNESCAPED_UNICODE),
        'attachment_files' => json_encode($attachmentFiles, JSON_UNESCAPED_UNICODE),
        'recommend_salesman_id' => !empty($_POST['recommend_salesman_id']) ? (int)$_POST['recommend_salesman_id'] : null,
        'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'status' => (int)($_POST['status'] ?? 1),
        'created_by' => $_SESSION['user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 验证必填字段
    if (empty($data['title']) || empty($data['country'])) {
        echo json_encode(['code' => 400, 'message' => '岗位名称和国家为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO positions 
        (title, category, country, city, industry, visa_type, education_required, major_required, age_min, age_max, 
         languages, skills, salary_range, description, requirements, benefits, tags, 
         required_materials, attachment_files, recommend_salesman_id,
         latitude, longitude, status, created_by, created_at, updated_at)
        VALUES 
        (:title, :category, :country, :city, :industry, :visa_type, :education_required, :major_required, :age_min, :age_max,
         :languages, :skills, :salary_range, :description, :requirements, :benefits, :tags, 
         :required_materials, :attachment_files, :recommend_salesman_id,
         :latitude, :longitude, :status, :created_by, :created_at, :updated_at)
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
 * 更新岗位
 */
function update($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '岗位 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 处理所需材料
    $requiredMaterials = [];
    if (!empty($_POST['required_materials'])) {
        $materialsText = $_POST['required_materials'];
        $materialsText = trim($materialsText, "[]\"'");
        if (!empty($materialsText)) {
            $requiredMaterials = array_filter(array_map('trim', explode(',', $materialsText)));
        }
    }
    
    // 处理附件
    $attachmentFiles = [];
    if (!empty($_POST['attachment_files'])) {
        try {
            $attachmentFiles = json_decode($_POST['attachment_files'], true);
            if (!is_array($attachmentFiles)) $attachmentFiles = [];
        } catch (Exception $e) {
            $attachmentFiles = [];
        }
    }
    
    // ↓↓↓↓↓ 【关键】参数和 SQL 完全一一对应（根据数据库表结构）
    $data = [
        'id' => $id,
        'title' => $_POST['title'] ?? '',
        'category' => $_POST['category'] ?? '',
        'country' => $_POST['country'] ?? '',
        'city' => $_POST['city'] ?? '',
        'industry' => $_POST['industry'] ?? '',
        'visa_type' => $_POST['visa_type'] ?? '',
        'education_required' => $_POST['education_required'] ?? '',
        'major_required' => $_POST['major_required'] ?? '',
        'age_min' => (int)($_POST['age_min'] ?? 0),
        'age_max' => (int)($_POST['age_max'] ?? 0),
        'languages' => $_POST['languages'] ?? '',
        'skills' => $_POST['skills'] ?? '',
        'salary_range' => $_POST['salary_range'] ?? '',
        'description' => $_POST['description'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'benefits' => $_POST['benefits'] ?? '',
        'tags' => $_POST['tags'] ?? '',
        'required_materials' => json_encode($requiredMaterials, JSON_UNESCAPED_UNICODE),
        'attachment_files' => json_encode($attachmentFiles, JSON_UNESCAPED_UNICODE),
        'recommend_salesman_id' => !empty($_POST['recommend_salesman_id']) ? (int)$_POST['recommend_salesman_id'] : null,
        'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'status' => (int)($_POST['status'] ?? 1),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if (empty($data['title']) || empty($data['country'])) {
        echo json_encode(['code' => 400, 'message' => '岗位名称和国家为必填'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // ↓↓↓↓↓【SQL 严格匹配参数】一个不多一个不少
    $stmt = $pdo->prepare("
        UPDATE positions SET
            title = :title,
            category = :category,
            country = :country,
            city = :city,
            industry = :industry,
            visa_type = :visa_type,
            education_required = :education_required,
            major_required = :major_required,
            age_min = :age_min,
            age_max = :age_max,
            languages = :languages,
            skills = :skills,
            salary_range = :salary_range,
            description = :description,
            requirements = :requirements,
            benefits = :benefits,
            tags = :tags,
            required_materials = :required_materials,
            attachment_files = :attachment_files,
            recommend_salesman_id = :recommend_salesman_id,
            latitude = :latitude,
            longitude = :longitude,
            status = :status,
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

/**
 * 切换岗位状态
 */
function toggleStatus($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '岗位 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE positions SET status = 1 - status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 设置岗位状态
 */
function setStatus($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 1);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '岗位 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE positions SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id, ':status' => $status]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 删除岗位
 */
function delete($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '岗位 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM positions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'code' => 200,
        'message' => '删除成功',
        'data' => ['id' => $id]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取国家列表
 */
function getCountries($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT country FROM positions WHERE country IS NOT NULL AND country != '' ORDER BY country");
    $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $countries
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 切换推荐状态
 */
function toggleRecommend($pdo) {
    $id = (int)($_POST['id'] ?? 0);
    $isRecommend = (int)($_POST['is_recommend'] ?? 0);
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '岗位 ID 不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查 is_recommend 字段是否存在，不存在则添加
    try {
        $stmt = $pdo->prepare("ALTER TABLE positions ADD COLUMN IF NOT EXISTS is_recommend TINYINT(1) DEFAULT 0 COMMENT '是否推荐'");
        $stmt->execute();
    } catch (Exception $e) {
        // 字段可能已存在，忽略错误
    }
    
    $stmt = $pdo->prepare("UPDATE positions SET is_recommend = :is_recommend, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $id, ':is_recommend' => $isRecommend]);
    
    echo json_encode([
        'code' => 200,
        'message' => '操作成功',
        'data' => ['id' => $id, 'is_recommend' => $isRecommend]
    ], JSON_UNESCAPED_UNICODE);
}
