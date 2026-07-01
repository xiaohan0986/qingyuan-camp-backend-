<?php
/**
 * 门店管理 API
 */
// 关闭 HTML 错误输出，确保返回纯 JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// 检查登录状态（小程序端允许未登录访问）
// 注释掉登录检查，允许公开访问门店列表
// if (!isset($_SESSION['user_id']) && !isset($_SESSION['role_id'])) {
//     http_response_code(401);
//     echo json_encode(['code' => 401, 'message' => '未登录'], JSON_UNESCAPED_UNICODE);
//     exit;
// }

// 直接加载数据库配置
require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/database.php';

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
    $pdo->exec("SET CHARACTER SET utf8mb4");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => '数据库连接失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

// 检查 stores 表是否存在
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'stores'");
    if ($stmt->rowCount() == 0) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => 'stores 表不存在，请先创建表'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => '数据库错误'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'list':
        handle_list($pdo);
        break;
    case 'detail':
        handle_detail($pdo);
        break;
    case 'cities':
        handle_cities($pdo);
        break;
    case 'create':
        handle_create($pdo);
        break;
    case 'update':
        handle_update($pdo);
        break;
    case 'delete':
        handle_delete($pdo);
        break;
    case 'delete_image':
        handle_delete_image($pdo);
        break;
    case 'batch_delete':
        handle_batch_delete($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => '未知操作'], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取所有城市列表
 */
function handle_cities($pdo) {
    try {
        // 查询所有不重复的城市
        $sql = "SELECT DISTINCT city FROM stores WHERE city IS NOT NULL AND city != '' ORDER BY city ASC";
        $stmt = $pdo->query($sql);
        $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 过滤空值并添加"全部"选项
        $cities = array_filter($cities, function($city) {
            return !empty($city) && $city !== 'all';
        });
        array_unshift($cities, 'all');
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'cities' => $cities
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '查询失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 获取门店列表
 */
function handle_list($pdo) {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $page_size = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
        $offset = ($page - 1) * $page_size;
        
        $keyword = $_GET['keyword'] ?? '';
        $country = $_GET['country'] ?? '';
        $city = $_GET['city'] ?? '';
        
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = '(name LIKE ? OR address LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        if ($country) {
            $where[] = 'country = ?';
            $params[] = $country;
        }
        
        if ($city) {
            // 支持地级市前缀匹配（如"南京市"匹配"南京 - 六合"）
            // 移除"市"后缀进行匹配
            $cityPrefix = str_replace('市', '', $city);
            $where[] = 'city LIKE ?';
            $params[] = "{$cityPrefix}%";
            
            // 调试日志
            error_log("City filter: {$city} -> prefix: {$cityPrefix} -> pattern: {$cityPrefix}%");
        }
        
        $where_sql = implode(' AND ', $where);
        
        // 获取总数
        $count_sql = "SELECT COUNT(*) as count FROM stores WHERE {$where_sql}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch()['count'];
        
        // 获取数据 - 关联查询在招岗位数量
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM positions p WHERE p.store_id = s.id AND p.status = 1) as position_count
                FROM stores s
                WHERE {$where_sql} 
                ORDER BY s.sort ASC, s.id DESC 
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$page_size, $offset]));
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 处理数据，确保字段存在并转换 avatar 为完整 URL
        $baseUrl = 'https://www.gofong.com';
        foreach ($list as &$item) {
            // 转换 avatar 为完整 URL
            if (!empty($item['avatar'])) {
                // 如果已经是完整 URL，保持不变
                if (strpos($item['avatar'], 'http') !== 0) {
                    // 如果是服务器路径（以 /www 开头），转换为 URL
                    if (strpos($item['avatar'], '/www') === 0) {
                        $item['avatar'] = $baseUrl . substr($item['avatar'], strpos($item['avatar'], '/uploads'));
                    } else if (strpos($item['avatar'], '/') === 0) {
                        // 如果是相对路径，添加域名
                        $item['avatar'] = $baseUrl . $item['avatar'];
                    }
                }
            } else {
                $item['avatar'] = $baseUrl . '/images/store_avatar_1.png';
            }
            
            // 确保必要字段存在
            if (!isset($item['en_name']) || empty($item['en_name'])) {
                $item['en_name'] = $item['name'] ?? '';
            }
            // 确保 country 和 city 字段存在
            if (!isset($item['country'])) {
                $item['country'] = '';
            }
            if (!isset($item['city'])) {
                $item['city'] = '';
            }
            if (!isset($item['tags'])) {
                $item['tags'] = '';
            }
            if (!isset($item['verified'])) {
                $item['verified'] = ($item['status'] == 1) ? 1 : 0;
            }
            if (!isset($item['distance'])) {
                $item['distance'] = '344m';
            }
        }
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'page_size' => $page_size,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $page_size)
                ]
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '查询失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 获取门店详情
 */
function handle_detail($pdo) {
    try {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare('SELECT * FROM stores WHERE id = ?');
        $stmt->execute([$id]);
        $store = $stmt->fetch();
        
        if (!$store) {
            http_response_code(404);
            echo json_encode(['code' => 404, 'message' => '门店不存在'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 转换 avatar 为完整 URL
        $baseUrl = 'https://www.gofong.com';
        if (!empty($store['avatar'])) {
            // 如果已经是完整 URL，保持不变
            if (strpos($store['avatar'], 'http') !== 0) {
                // 如果是服务器路径（以 /www 开头），转换为 URL
                if (strpos($store['avatar'], '/www') === 0) {
                    $store['avatar'] = $baseUrl . substr($store['avatar'], strpos($store['avatar'], '/uploads'));
                } else if (strpos($store['avatar'], '/') === 0) {
                    // 如果是相对路径，添加域名
                    $store['avatar'] = $baseUrl . $store['avatar'];
                }
            }
        } else {
            $store['avatar'] = $baseUrl . '/images/store_avatar_1.png';
        }
        
        // 解析 environment_images 字段（直接返回文件名数组）
        if (!empty($store['environment_images'])) {
            $images = json_decode($store['environment_images'], true);
            if (is_array($images)) {
                // 转换环境图片为完整 URL
                foreach ($images as &$img) {
                    if (strpos($img, 'http') !== 0) {
                        if (strpos($img, '/www') === 0) {
                            $img = $baseUrl . substr($img, strpos($img, '/uploads'));
                        } else if (strpos($img, '/') === 0) {
                            $img = $baseUrl . $img;
                        }
                    }
                }
                $store['environment_images'] = $images;
            } else {
                $store['environment_images'] = [];
            }
        } else {
            $store['environment_images'] = [];
        }
        
        echo json_encode(['code' => 200, 'message' => 'success', 'data' => $store], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '查询失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 创建门店
 */
function handle_create($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($input['name'] ?? '');
        $country = trim($input['country'] ?? '');
        $city = trim($input['city'] ?? '');
        
        if (!$name || !$country || !$city) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '门店名称、国家、城市不能为空'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 处理环境图片
        $environmentImages = [];
        if (!empty($input['environment_images']) && is_array($input['environment_images'])) {
            $uploadDir = __DIR__ . '/../uploads/store_environments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($input['environment_images'] as $index => $imageItem) {
                if ($index >= 20) break; // 最多 20 张
                
                // 新格式：对象格式 {type: 'new'|'existing', data: base64, filename: string}
                if (is_array($imageItem)) {
                    // 新上传的图片
                    if (($imageItem['type'] ?? '') === 'new' && !empty($imageItem['data'])) {
                        $imageData = $imageItem['data'];
                        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
                        $imageData = str_replace('data:image/png;base64,', '', $imageData);
                        $imageData = str_replace(' ', '+', $imageData);
                        $imageData = base64_decode($imageData);
                        
                        if ($imageData !== false) {
                            // 生成唯一文件名
                            $filename = 'store_' . time() . '_' . $index . '_' . uniqid() . '.jpg';
                            $filepath = $uploadDir . $filename;
                            file_put_contents($filepath, $imageData);
                            // 只保存文件名到数据库
                            $environmentImages[] = $filename;
                        }
                    }
                    // 已有的图片
                    else if (($imageItem['type'] ?? '') === 'existing' && !empty($imageItem['filename'])) {
                        $environmentImages[] = $imageItem['filename'];
                    }
                }
                // 兼容旧格式：直接是 base64 字符串
                else if (is_string($imageItem) && strpos($imageItem, 'data:image') === 0) {
                    $imageData = str_replace('data:image/jpeg;base64,', '', $imageItem);
                    $imageData = str_replace(' ', '+', $imageItem);
                    $imageData = base64_decode($imageData);
                    
                    if ($imageData !== false) {
                        $filename = 'store_' . time() . '_' . $index . '_' . uniqid() . '.jpg';
                        $filepath = $uploadDir . $filename;
                        file_put_contents($filepath, $imageData);
                        $environmentImages[] = $filename;
                    }
                }
            }
        }
        
        // 处理经纬度
        $latitude = !empty($input['latitude']) ? floatval($input['latitude']) : null;
        $longitude = !empty($input['longitude']) ? floatval($input['longitude']) : null;
        
        // 处理标签
        $tags = !empty($input['tags']) ? $input['tags'] : '';
        if (is_array($input['tags'])) {
            $tags = json_encode($input['tags'], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "INSERT INTO stores (name, manager, manager_phone, country, city, address, latitude, longitude, avatar, description, environment_images, tags, status, sort, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $input['manager'] ?? '',
            $input['manager_phone'] ?? '',
            $country,
            $city,
            $input['address'] ?? '',
            $latitude,
            $longitude,
            $input['avatar'] ?? '',
            $input['description'] ?? '',
            json_encode($environmentImages, JSON_UNESCAPED_UNICODE),
            $tags,
            (int)($input['status'] ?? 1),
            (int)($input['sort'] ?? 0)
        ]);
        
        $id = $pdo->lastInsertId();
        
        echo json_encode(['code' => 200, 'message' => '创建成功', 'data' => ['id' => $id]], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '创建失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 更新门店
 */
function handle_update($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)($input['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $name = trim($input['name'] ?? '');
        $country = trim($input['country'] ?? '');
        $city = trim($input['city'] ?? '');
        
        if (!$name || !$country || !$city) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '门店名称、国家、城市不能为空'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 处理环境图片
        $environmentImages = [];
        if (!empty($input['environment_images']) && is_array($input['environment_images'])) {
            $uploadDir = __DIR__ . '/../uploads/store_environments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($input['environment_images'] as $index => $imageItem) {
                if ($index >= 20) break; // 最多 20 张
                
                // 新格式：对象格式 {type: 'new'|'existing', data: base64, filename: string}
                if (is_array($imageItem)) {
                    // 新上传的图片
                    if (($imageItem['type'] ?? '') === 'new' && !empty($imageItem['data'])) {
                        $imageData = $imageItem['data'];
                        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
                        $imageData = str_replace('data:image/png;base64,', '', $imageData);
                        $imageData = str_replace(' ', '+', $imageData);
                        $imageData = base64_decode($imageData);
                        
                        if ($imageData !== false) {
                            // 生成唯一文件名
                            $filename = 'store_' . time() . '_' . $index . '_' . uniqid() . '.jpg';
                            $filepath = $uploadDir . $filename;
                            file_put_contents($filepath, $imageData);
                            // 只保存文件名到数据库
                            $environmentImages[] = $filename;
                        }
                    }
                    // 已有的图片
                    else if (($imageItem['type'] ?? '') === 'existing' && !empty($imageItem['filename'])) {
                        $environmentImages[] = $imageItem['filename'];
                    }
                }
                // 兼容旧格式：直接是 base64 字符串
                else if (is_string($imageItem) && strpos($imageItem, 'data:image') === 0) {
                    $imageData = str_replace('data:image/jpeg;base64,', '', $imageItem);
                    $imageData = str_replace(' ', '+', $imageItem);
                    $imageData = base64_decode($imageData);
                    
                    if ($imageData !== false) {
                        $filename = 'store_' . time() . '_' . $index . '_' . uniqid() . '.jpg';
                        $filepath = $uploadDir . $filename;
                        file_put_contents($filepath, $imageData);
                        $environmentImages[] = $filename;
                    }
                }
            }
        }
        
        // 处理经纬度
        $latitude = !empty($input['latitude']) ? floatval($input['latitude']) : null;
        $longitude = !empty($input['longitude']) ? floatval($input['longitude']) : null;
        
        // 处理标签
        $tags = !empty($input['tags']) ? $input['tags'] : '';
        if (is_array($input['tags'])) {
            $tags = json_encode($input['tags'], JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "UPDATE stores SET 
                name = ?, manager = ?, manager_phone = ?, country = ?, city = ?, 
                address = ?, latitude = ?, longitude = ?, 
                avatar = ?, description = ?, environment_images = ?, tags = ?, status = ?, sort = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name,
            $input['manager'] ?? '',
            $input['manager_phone'] ?? '',
            $country,
            $city,
            $input['address'] ?? '',
            $latitude,
            $longitude,
            $input['avatar'] ?? '',
            $input['description'] ?? '',
            json_encode($environmentImages, JSON_UNESCAPED_UNICODE),
            $tags,
            (int)($input['status'] ?? 1),
            (int)($input['sort'] ?? 0),
            $id
        ]);
        
        echo json_encode(['code' => 200, 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '更新失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 删除门店
 */
function handle_delete($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '缺少参数 id'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 检查是否有在招岗位
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM positions WHERE store_id = ? AND status = 1');
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '该门店下还有在招岗位，无法删除'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare('DELETE FROM stores WHERE id = ?');
        $stmt->execute([$id]);
        
        echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '删除失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 批量删除门店
 */
function handle_batch_delete($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        
        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '请选择要删除的门店'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 检查是否有在招岗位
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM positions WHERE store_id IN ($placeholders) AND status = 1");
        $stmt->execute($ids);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '部分门店下还有在招岗位，无法删除'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM stores WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '删除失败'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 删除门店图片（同时删除服务器文件）
 */
function handle_delete_image($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['code' => 405, 'message' => '请求方法错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $storeId = (int)($input['store_id'] ?? 0);
        $filename = trim($input['filename'] ?? '');
        
        if (!$storeId || !$filename) {
            http_response_code(400);
            echo json_encode(['code' => 400, 'message' => '参数错误'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 获取当前门店的图片列表
        $stmt = $pdo->prepare('SELECT environment_images FROM stores WHERE id = ?');
        $stmt->execute([$storeId]);
        $store = $stmt->fetch();
        
        if (!$store) {
            http_response_code(404);
            echo json_encode(['code' => 404, 'message' => '门店不存在'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 解析图片列表
        $images = json_decode($store['environment_images'], true);
        if (!is_array($images)) {
            $images = [];
        }
        
        // 查找并删除指定文件
        $uploadDir = __DIR__ . '/../uploads/store_environments/';
        $filepath = $uploadDir . $filename;
        
        if (file_exists($filepath)) {
            unlink($filepath); // 删除服务器文件
        }
        
        // 从数组中移除该文件名
        $images = array_filter($images, function($img) use ($filename) {
            return $img !== $filename;
        });
        $images = array_values($images); // 重新索引
        
        // 更新数据库
        $stmt = $pdo->prepare('UPDATE stores SET environment_images = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([json_encode($images, JSON_UNESCAPED_UNICODE), $storeId]);
        
        echo json_encode(['code' => 200, 'message' => '删除成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'message' => '删除失败'], JSON_UNESCAPED_UNICODE);
    }
}
