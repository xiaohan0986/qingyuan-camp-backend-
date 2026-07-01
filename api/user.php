<?php
/**
 * 用户管理 API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载数据库
require_once __DIR__ . '/../bootstrap.php';
$config = require_once CONFIG_PATH . 'database_platform.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'], $config['port'], $config['database'], $config['charset']),
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");
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
        case 'delete':
            delete($pdo);
            break;
        case 'set_status':
            setStatus($pdo);
            break;
        case 'batch_delete':
            batchDelete($pdo);
            break;
        case 'batch_set_status':
            batchSetStatus($pdo);
            break;
        case 'search':
            search($pdo);
            break;
        default:
            echo json_encode(['code' => 400, 'message' => '无效的操作'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'message' => '服务器错误：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取用户列表
 */
function getList($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(100, (int)($_GET['page_size'] ?? 20));
    $offset = ($page - 1) * $pageSize;
    
    $keyword = $_GET['keyword'] ?? '';
    $status = $_GET['status'] ?? '';
    $gender = $_GET['gender'] ?? '';
    
    $where = ['1=1'];
    $params = [];
    
    if ($keyword) {
        $where[] = '(username LIKE :keyword OR nickname LIKE :keyword OR phone LIKE :keyword OR wechat_id LIKE :keyword)';
        $params[':keyword'] = "%{$keyword}%";
    }
    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = (int)$status;
    }
    if ($gender !== '') {
        $where[] = 'gender = :gender';
        $params[':gender'] = (int)$gender;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // 获取总数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mini_program_users WHERE {$whereClause}");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 获取数据
    $stmt = $pdo->prepare("
        SELECT id, username, wechat_id, phone, password, nickname, real_name, avatar, gender, age, education, occupation, role, status, member_id, last_login_at, last_login_ip, created_at, updated_at
        FROM mini_program_users 
        WHERE {$whereClause}
        ORDER BY id DESC
        LIMIT :offset, :limit
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 转换字段名
    foreach ($list as &$item) {
        $item['role_id'] = $item['role'];
    }
    
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
 * 获取用户详情
 */
function getDetail($pdo) {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT * FROM mini_program_users WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['code' => 404, 'message' => '用户不存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $user['role_id'] = $user['role'];
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $user
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 生成唯一的 6 位会员 ID
 */
function generateUniqueMemberId($pdo) {
    $maxAttempts = 100;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // 生成 6 位随机数字
        $memberId = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // 检查是否已存在
        $stmt = $pdo->prepare("SELECT id FROM mini_program_users WHERE member_id = :member_id");
        $stmt->bindValue(':member_id', $memberId);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return $memberId;
        }
        
        $attempt++;
    }
    
    throw new Exception("无法生成唯一的 member_id，已尝试 {$maxAttempts} 次");
}

/**
 * 创建用户
 */
function create($pdo) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $wechat_id = $_POST['wechat_id'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $real_name = $_POST['real_name'] ?? '';
    $gender = $_POST['gender'] ?? -1;
    $age = $_POST['age'] ?? null;
    $education = $_POST['education'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $status = $_POST['status'] ?? 1;
    $role_id = $_POST['role_id'] ?? 3;
    $avatar = $_POST['avatar'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    
    if (!$username || !$password) {
        echo json_encode(['code' => 400, 'message' => '用户名和密码不能为空'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查用户名是否存在
    $stmt = $pdo->prepare("SELECT id FROM mini_program_users WHERE username = :username");
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    if ($stmt->fetch()) {
        echo json_encode(['code' => 400, 'message' => '用户名已存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 生成唯一的 6 位会员 ID
    try {
        $memberId = generateUniqueMemberId($pdo);
    } catch (Exception $e) {
        echo json_encode(['code' => 500, 'message' => '无法生成会员 ID: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO mini_program_users (username, wechat_id, phone, password, nickname, real_name, gender, age, education, occupation, role, status, member_id, avatar, specialty, created_at, updated_at) 
        VALUES (:username, :wechat_id, :phone, :password, :nickname, :real_name, :gender, :age, :education, :occupation, :role, :status, :member_id, :avatar, :specialty, NOW(), NOW())
    ");
    
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':wechat_id', $wechat_id);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':password', $hashedPassword);
    $stmt->bindValue(':nickname', $nickname);
    $stmt->bindValue(':real_name', $real_name);
    $stmt->bindValue(':gender', (int)$gender, PDO::PARAM_INT);
    $stmt->bindValue(':age', $age ?: null);
    $stmt->bindValue(':education', $education);
    $stmt->bindValue(':occupation', $occupation);
    $stmt->bindValue(':role', (int)$role_id, PDO::PARAM_INT);
    $stmt->bindValue(':status', (int)$status, PDO::PARAM_INT);
    $stmt->bindValue(':member_id', $memberId);
    $stmt->bindValue(':avatar', $avatar);
    $stmt->bindValue(':specialty', $specialty);
    
    $stmt->execute();
    
    echo json_encode([
        'code' => 200,
        'message' => '创建成功',
        'data' => [
            'id' => $pdo->lastInsertId(),
            'member_id' => $memberId
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 更新用户
 */
function update($pdo) {
    $id = $_POST['id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $wechat_id = $_POST['wechat_id'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $real_name = $_POST['real_name'] ?? '';
    $gender = $_POST['gender'] ?? -1;
    $age = $_POST['age'] ?? null;
    $education = $_POST['education'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $status = $_POST['status'] ?? 1;
    $role_id = $_POST['role_id'] ?? 3;
    $avatar = $_POST['avatar'] ?? '';
    $skills = $_POST['skills'] ?? null;
    $intended_countries = $_POST['intended_countries'] ?? null;
    $specialty = $_POST['specialty'] ?? '';
    
    if (!$id || !$username) {
        echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // 检查用户名是否被其他用户使用
    $stmt = $pdo->prepare("SELECT id FROM mini_program_users WHERE username = :username AND id != :id");
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetch()) {
        echo json_encode(['code' => 400, 'message' => '用户名已存在'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    if ($password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE mini_program_users 
            SET username = :username, wechat_id = :wechat_id, password = :password, nickname = :nickname, phone = :phone, real_name = :real_name, gender = :gender, age = :age, education = :education, occupation = :occupation, role = :role, status = :status, avatar = :avatar, skills = :skills, intended_countries = :intended_countries, specialty = :specialty, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->bindValue(':password', $hashedPassword);
    } else {
        $stmt = $pdo->prepare("
            UPDATE mini_program_users 
            SET username = :username, wechat_id = :wechat_id, nickname = :nickname, phone = :phone, real_name = :real_name, gender = :gender, age = :age, education = :education, occupation = :occupation, role = :role, status = :status, avatar = :avatar, skills = :skills, intended_countries = :intended_countries, specialty = :specialty, updated_at = NOW()
            WHERE id = :id
        ");
    }
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':wechat_id', $wechat_id);
    $stmt->bindValue(':nickname', $nickname);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':real_name', $real_name);
    $stmt->bindValue(':gender', (int)$gender, PDO::PARAM_INT);
    $stmt->bindValue(':age', $age ?: null);
    $stmt->bindValue(':education', $education);
    $stmt->bindValue(':occupation', $occupation);
    $stmt->bindValue(':role', (int)$role_id, PDO::PARAM_INT);
    $stmt->bindValue(':status', (int)$status, PDO::PARAM_INT);
    $stmt->bindValue(':avatar', $avatar);
    $stmt->bindValue(':skills', $skills);
    $stmt->bindValue(':intended_countries', $intended_countries);
    $stmt->bindValue(':specialty', $specialty);
    
    $stmt->execute();
    
    echo json_encode(['code' => 200, 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 删除用户
 */
function delete($pdo) {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM mini_program_users WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 设置用户状态
 */
function setStatus($pdo) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 1;
    
    if (!$id) {
        echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE mini_program_users SET status = :status, updated_at = NOW() WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':status', (int)$status, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['code' => 200, 'message' => '操作成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 批量删除用户
 */
function batchDelete($pdo) {
    $idsStr = $_POST['ids'] ?? '';
    
    if (!$idsStr) {
        echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $ids = explode(',', $idsStr);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("DELETE FROM mini_program_users WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 批量设置状态
 */
function batchSetStatus($pdo) {
    $idsStr = $_POST['ids'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (!$idsStr) {
        echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $ids = explode(',', $idsStr);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("UPDATE mini_program_users SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
    array_unshift($ids, (int)$status);
    $stmt->execute($ids);
    
    echo json_encode(['code' => 200, 'message' => '操作成功'], JSON_UNESCAPED_UNICODE);
}

/**
 * 搜索用户
 */
function search($pdo) {
    $keyword = $_GET['keyword'] ?? '';
    
    if (!$keyword) {
        echo json_encode(['code' => 400, 'message' => '请输入搜索关键词'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM mini_program_users 
        WHERE username LIKE :keyword OR nickname LIKE :keyword OR phone LIKE :keyword OR wechat_id LIKE :keyword OR member_id LIKE :keyword OR id LIKE :keyword
        LIMIT 20
    ");
    $stmt->bindValue(':keyword', "%{$keyword}%");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $list
    ], JSON_UNESCAPED_UNICODE);
}
