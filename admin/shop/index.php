<?php
/**
 * 门店列表管理
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '门店列表';

// 处理 AJAX 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 列表数据
        if ($_POST['action'] === 'list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $level = trim($_POST['level'] ?? '');
            $star = trim($_POST['star'] ?? '');
            $status = $_POST['status'] ?? '';
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = intval($_POST['pageSize'] ?? 20);

            $where = ['1=1'];
            $params = [];

            if ($keyword !== '') {
                $where[] = 'name LIKE ?';
                $params[] = "%{$keyword}%";
            }
            if ($level !== '') {
                $where[] = 'level = ?';
                $params[] = $level;
            }
            if ($star !== '') {
                $starVal = floatval($star);
                if ($starVal >= 5) {
                    $where[] = 'star_rating >= 4.8';
                } elseif ($starVal >= 4) {
                    $where[] = 'star_rating >= 4.0 AND star_rating < 4.8';
                } elseif ($starVal >= 3) {
                    $where[] = 'star_rating >= 3.0 AND star_rating < 4.0';
                } elseif ($starVal >= 2) {
                    $where[] = 'star_rating >= 2.0 AND star_rating < 3.0';
                } else {
                    $where[] = 'star_rating >= 1.0 AND star_rating < 2.0';
                }
            }
            if ($status !== '' && $status !== null) {
                $where[] = 'status = ?';
                $params[] = intval($status);
            }

            $whereSql = implode(' AND ', $where);
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM stores WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = $db->fetchAll("SELECT * FROM stores WHERE {$whereSql} ORDER BY sort DESC, id DESC LIMIT {$pageSize} OFFSET {$offset}", $params);

            // 统计店员/核销
            foreach ($rows as &$row) {
                $staff = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_staff WHERE store_id = ?", [$row['id']]);
                $verify = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_verify_log WHERE store_id = ?", [$row['id']]);
                $row['staff_count'] = intval($staff['c'] ?? 0);
                $row['verify_count'] = intval($verify['c'] ?? 0);
            }
            unset($row);

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
            exit;
        }

        // 单条详情
        if ($_POST['action'] === 'detail') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM stores WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '门店不存在']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }

        // 门店订单列表
        if ($_POST['action'] === 'store_orders_list') {
            $sid = intval($_POST['store_id'] ?? 0);
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM orders WHERE store_id = ?", [$sid]);
            $total = intval($countRow['total'] ?? 0);

            $rows = [];
            if ($total > 0) {
                $rows = $db->fetchAll(
                    "SELECT o.id, o.order_no, o.member_id, o.total_amount, o.pay_amount, o.discount_amount,
                            o.status, o.remark, o.created_at,
                            COALESCE(m.nickname, concat('用户#', o.member_id)) AS member_name,
                            m.avatar AS member_avatar
                     FROM orders o
                     LEFT JOIN members m ON o.member_id = m.id
                     WHERE o.store_id = ?
                     ORDER BY o.created_at DESC
                     LIMIT {$pageSize} OFFSET {$offset}",
                    [$sid]
                );
            }

            // 获取订单商品
            if (!empty($rows)) {
                $orderIds = array_column($rows, 'id');
                $ids_str = implode(',', $orderIds);
                $items = $db->fetchAll(
                    "SELECT order_id, product_name, product_image, price, quantity, total
                     FROM order_items
                     WHERE order_id IN ({$ids_str})
                     ORDER BY id"
                );
                $itemsByOrder = [];
                foreach ($items as $item) {
                    $itemsByOrder[$item['order_id']][] = $item;
                }
                foreach ($rows as &$row) {
                    $row['items'] = $itemsByOrder[$row['id']] ?? [];
                }
                unset($row);
            }

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
            exit;
        }

        // 发货
        if ($_POST['action'] === 'ship_order') {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '订单ID不能为空']);
                exit;
            }
            $row = $db->fetchOne("SELECT id, status FROM orders WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '订单不存在']);
                exit;
            }
            if ($row['status'] !== 'paid' && $row['status'] !== 'pending_ship') {
                echo json_encode(['success' => false, 'message' => '当前状态不可发货']);
                exit;
            }
            $now = date('Y-m-d H:i:s');
            $db->update('orders', ['status' => 'shipped', 'ship_time' => $now, 'updated_at' => $now], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '发货成功']);
            exit;
        }

        // 门店售后列表
        if ($_POST['action'] === 'store_after_list') {
            $sid = intval($_POST['store_id'] ?? 0);
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM order_after WHERE store_id = ?", [$sid]);
            $total = intval($countRow['total'] ?? 0);

            $rows = [];
            if ($total > 0) {
                $rows = $db->fetchAll(
                    "SELECT id, after_no, order_no, goods_name, goods_image, goods_spec, goods_price,
                            goods_quantity, pay_amount, refund_amount, buyer_name, buyer_phone,
                            after_type, after_status, reason, description, apply_time
                     FROM order_after
                     WHERE store_id = ?
                     ORDER BY apply_time DESC
                     LIMIT {$pageSize} OFFSET {$offset}",
                    [$sid]
                );
            }

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]);
            exit;
        }

        // 售后处理
        if ($_POST['action'] === 'process_after_sale') {
            $id = intval($_POST['id'] ?? 0);
            $action = $_POST['process_action'] ?? '';
            $reason = trim($_POST['handle_reason'] ?? '');
            if (!$id) {
                echo json_encode(['success' => false, 'message' => '售后ID不能为空']);
                exit;
            }
            if ($action === 'reject' && $reason === '') {
                echo json_encode(['success' => false, 'message' => '拒绝时请输入拒绝理由']);
                exit;
            }
            if (!in_array($action, ['handle', 'reject'])) {
                echo json_encode(['success' => false, 'message' => '处理类型无效']);
                exit;
            }
            $row = $db->fetchOne("SELECT id, after_status FROM order_after WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '售后单不存在']);
                exit;
            }
            if ($row['after_status'] !== 'processing') {
                echo json_encode(['success' => false, 'message' => '当前状态不可处理']);
                exit;
            }
            $newStatus = $action === 'handle' ? 'handled' : 'rejected';
            $now = date('Y-m-d H:i:s');
            $updateData = ['after_status' => $newStatus, 'handle_time' => $now, 'updated_at' => $now];
            if ($reason !== '') {
                $updateData['handle_reason'] = $reason;
            }
            $db->update('order_after', $updateData, 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => $action === 'handle' ? '售后处理完成' : '已拒绝退款']);
            exit;
        }


        // 门店评价列表
        if ($_POST['action'] === 'comments') {
            $sid = intval($_POST['store_id'] ?? 0);
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = 20;
            $offset = ($page - 1) * $pageSize;

            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM store_comments WHERE store_id = ?", [$sid]);
            $total = intval($countRow['total'] ?? 0);

            $rows = [];
            if ($total > 0) {
                $rows = $db->fetchAll(
                    "SELECT id, user_name, user_avatar, rating, content, images, created_at
                     FROM store_comments
                     WHERE store_id = ?
                     ORDER BY created_at DESC
                     LIMIT {$pageSize} OFFSET {$offset}",
                    [$sid]
                );
            }

            // 统计评分分布
            $ratingStats = $db->fetchAll(
                "SELECT rating, COUNT(*) AS cnt FROM store_comments WHERE store_id = ? GROUP BY rating ORDER BY rating DESC",
                [$sid]
            );

            echo json_encode([
                'success' => true,
                'data' => $rows,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'rating_stats' => $ratingStats
            ]);
            exit;
        }


        // 新增
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $phone2 = trim($_POST['phone2'] ?? '');
            $avatar = trim($_POST['avatar'] ?? '');
            $businessHours = trim($_POST['business_hours'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $latitude = $_POST['latitude'] ?? '';
            $longitude = $_POST['longitude'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            $sort = intval($_POST['sort'] ?? 0);

            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '门店名称不能为空']);
                exit;
            }

            $tags = trim($_POST['tags'] ?? '');
            $images = trim($_POST['images'] ?? '[]');
            $id = $db->insert('stores', [
                'name' => $name,
                'phone' => $phone,
                'phone2' => $phone2,
                'avatar' => $avatar,
                'images' => $images,
                'tags' => $tags,
                'business_hours' => $businessHours,
                'country' => $country,
                'city' => $city,
                'address' => $address,
                'latitude' => $latitude === '' ? null : floatval($latitude),
                'longitude' => $longitude === '' ? null : floatval($longitude),
                'description' => $description,
                'status' => $status,
                'sort' => $sort,
                'suspended' => intval($_POST['suspended'] ?? 0),
                'suspended_reason' => trim($_POST['suspended_reason'] ?? ''),
                'suspended_at' => intval($_POST['suspended'] ?? 0) === 1 ? date('Y-m-d H:i:s') : null,
                'suspended_until' => '',
                'view_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['success' => true, 'message' => '门店添加成功', 'id' => $id]);
            exit;
        }

        // 编辑
        if ($_POST['action'] === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id FROM stores WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '门店不存在']);
                exit;
            }

            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '门店名称不能为空']);
                exit;
            }

            $country = trim($_POST['country'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $latitude = $_POST['latitude'] ?? '';
            $longitude = $_POST['longitude'] ?? '';

            // 关停历史处理
            $newSuspended = intval($_POST['suspended'] ?? 0);
            if ($newSuspended !== intval($row['suspended'] ?? 0)) {
                $history = [];
                $oldHistory = $row['suspension_history'] ?? '[]';
                if ($oldHistory) { try { $history = json_decode($oldHistory, true) ?: []; } catch(\Exception $e) { $history = []; } }
                if ($newSuspended == 1) {
                    // 新增关停记录
                    $history[] = [
                        'suspended_at' => date('Y-m-d H:i:s'),
                        'reason' => trim($_POST['suspended_reason'] ?? ''),
                        'operator' => ($admin['name'] ?? $admin['username'] ?? 'admin'),
                    ];
                } else {
                    // 恢复营业：更新最后一条记录
                    $lastIdx = count($history) - 1;
                    if ($lastIdx >= 0) {
                        $history[$lastIdx]['restored_at'] = date('Y-m-d H:i:s');
                        $history[$lastIdx]['restore_operator'] = $admin['name'] ?? $admin['username'] ?? 'admin';
                    }
                }
                $_POST['suspension_history'] = json_encode($history, JSON_UNESCAPED_UNICODE);
            }

            $db->update('stores', [
                'name' => $name,
                'phone' => trim($_POST['phone'] ?? ''),
                'phone2' => trim($_POST['phone2'] ?? ''),
                'avatar' => trim($_POST['avatar'] ?? ''),
                'tags' => trim($_POST['tags'] ?? ''),
                'business_hours' => trim($_POST['business_hours'] ?? ''),
                'country' => $country,
                'city' => $city,
                'address' => trim($_POST['address'] ?? ''),
                'latitude' => $latitude === '' ? null : floatval($latitude),
                'longitude' => $longitude === '' ? null : floatval($longitude),
                'description' => trim($_POST['description'] ?? ''),
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'suspended' => $newSuspended,
                'suspended_reason' => trim($_POST['suspended_reason'] ?? ''),
                'suspended_at' => $newSuspended && !intval($row['suspended'] ?? 0) ? date('Y-m-d H:i:s') : ($newSuspended == 0 ? null : $row['suspended_at']),
                'suspended_until' => trim($_POST['suspended_until'] ?? '') ?: null,
                'suspension_history' => trim($_POST['suspension_history'] ?? $row['suspension_history'] ?? ''),
                'badge' => trim($_POST['badge'] ?? ''),
                'images' => trim($_POST['images'] ?? '[]'),
                'license_images' => trim($_POST['license_images'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => '门店更新成功']);
            exit;
        }

        // 关停/恢复店铺
        if ($_POST['action'] === 'suspend_store') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM stores WHERE id = ?", [$id]);
            if (!$row) { echo json_encode(['success' => false, 'message' => '门店不存在']); exit; }

            $action = $_POST['suspend_action'] ?? 'suspend'; // suspend / restore

            if ($action === 'suspend') {
                $reason = trim($_POST['reason'] ?? '');
                if (!$reason) { echo json_encode(['success' => false, 'message' => '请填写关停理由']); exit; }
                $now = date('Y-m-d H:i:s');
                $until = trim($_POST['until'] ?? '');

                // 历史记录
                $history = [];
                if ($row['suspension_history']) {
                    try { $history = json_decode($row['suspension_history'], true) ?: []; } catch(Exception $e) { $history = []; }
                }
                $history[] = [
                    'suspended_at' => $now,
                    'reason' => $reason,
                    'operator' => ($admin['name'] ?? $admin['username'] ?? 'admin'),
                ];

                $db->update('stores', [
                    'suspended' => 1,
                    'status' => 0,
                    'suspended_at' => $now,
                    'suspended_reason' => $reason,
                    'suspended_until' => $until ?: null,
                    'suspension_history' => json_encode($history, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => '门店已关停']);
            } else {
                // 恢复
                $now = date('Y-m-d H:i:s');
                $history = [];
                if ($row['suspension_history']) {
                    try { $history = json_decode($row['suspension_history'], true) ?: []; } catch(Exception $e) { $history = []; }
                }
                $lastIdx = count($history) - 1;
                if ($lastIdx >= 0) {
                    $history[$lastIdx]['restored_at'] = $now;
                    $history[$lastIdx]['restore_operator'] = $admin['name'] ?? $admin['username'] ?? 'admin';
                }

                $db->update('stores', [
                    'suspended' => 0,
                    'status' => 1,
                    'suspended_at' => null,
                    'suspended_reason' => '',
                    'suspended_until' => null,
                    'suspension_history' => json_encode($history, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => '门店已恢复营业']);
            }
            exit;
        }

        // 切换状态
        if ($_POST['action'] === 'toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $db->update('stores', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '状态更新成功']);
            exit;
        }

        // 更新排序
        if ($_POST['action'] === 'update_sort') {
            $id = intval($_POST['id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $db->update('stores', ['sort' => $sort], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '排序更新成功']);
            exit;
        }

        // 删除
        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);

            // 检查是否有店员
            $staffCount = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_staff WHERE store_id = ?", [$id]);
            if (intval($staffCount['c'] ?? 0) > 0) {
                echo json_encode(['success' => false, 'message' => '该门店下存在店员，请先删除店员']);
                exit;
            }
            // 检查是否有核销记录
            $verifyCount = $db->fetchOne("SELECT COUNT(*) AS c FROM shop_verify_log WHERE store_id = ?", [$id]);
            if (intval($verifyCount['c'] ?? 0) > 0) {
                echo json_encode(['success' => false, 'message' => '该门店存在核销记录，无法删除']);
                exit;
            }

            $db->delete('stores', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '门店删除成功']);
            exit;
        }

        // 批量删除
        if ($_POST['action'] === 'batch_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要删除的门店']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM stores WHERE id IN ({$placeholders})", $ids);
            echo json_encode(['success' => true, 'message' => '批量删除成功']);
            exit;
        }

        // 批量设置状态
        if ($_POST['action'] === 'batch_status') {
            $ids = $_POST['ids'] ?? [];
            $status = intval($_POST['status'] ?? 0);
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择要操作的门店']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], $ids);
            $db->query("UPDATE stores SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})", $params);
            echo json_encode(['success' => true, 'message' => '批量操作成功']);
            exit;
        }

        // 筛选下拉数据
        if ($_POST['action'] === 'options') {
                        echo json_encode([
                'success' => true,
                'countries' => array_column($countries, 'country'),
                'cities' => array_column($cities, 'city'),
            ]);
            exit;
        }

        // 门店商品列表
        if ($_POST['action'] === 'store_products_list') {
            $storeId = intval($_POST['store_id'] ?? 0);

            $products = $db->fetchAll(
                "SELECT p.id, p.name, p.price, p.cost_price, p.stock, p.sales, p.status, p.images,
                        c.name as category_name,
                        s.name as store_name
                 FROM products p
                 INNER JOIN store_products sp ON sp.product_id = p.id AND sp.store_id = ?
                 LEFT JOIN product_categories c ON p.category_id = c.id
                 LEFT JOIN stores s ON s.id = sp.store_id
                 ORDER BY sp.id DESC",
                [$storeId]
            );
            // 解析图片 URL（与 admin/product/index.php 一致）
            require_once __DIR__ . '/../../config/oss.php';
            require_once __DIR__ . '/../../includes/OSSClient.php';
            $ossReady = defined('OSS_ENABLED') && OSS_ENABLED;
            foreach ($products as &$p) {
                $imgs = json_decode($p['images'] ?? '[]', true);
                $rawImg = $imgs[0] ?? '';
                if ($rawImg) {
                    if (strpos($rawImg, 'http') === 0 || strpos($rawImg, '/') === 0) {
                        $p['image'] = $rawImg;
                    } elseif ($ossReady) {
                        try {
                            $oss = OSSClient::getInstance();
                            $p['image'] = $oss->getFileUrl($rawImg);
                        } catch (Exception $e) {
                            $p['image'] = '';
                        }
                    } else {
                        // OSS 未开启：取文件名，拼接本地上传目录
                        $filename = basename($rawImg);
                        $p['image'] = '/uploads/products/' . $filename;
                    }
                } else {
                    $p['image'] = '';
                }
                unset($p['images']);
            }
            unset($p);
            echo json_encode(['success' => true, 'data' => $products]);
            exit;
        }

        // 门店分类列表
        if ($_POST['action'] === 'store_categories_list') {
            $rows = $db->fetchAll("SELECT * FROM product_categories ORDER BY sort ASC, id ASC");
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        // 门店分类新增
        if ($_POST['action'] === 'store_category_add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
                exit;
            }
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $icon = trim($_POST['icon'] ?? '');
            $id = $db->insert('product_categories', ['name' => $name, 'parent_id' => $parent_id, 'sort' => $sort, 'status' => $status, 'icon' => $icon, 'created_at' => date('Y-m-d H:i:s')]);
            echo json_encode(['success' => true, 'message' => '分类添加成功', 'id' => $id]);
            exit;
        }

        // 门店分类删除
        if ($_POST['action'] === 'store_category_delete') {
            $id = intval($_POST['id'] ?? 0);
            $db->delete('product_categories', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '分类删除成功']);
            exit;
        }

        // 门店分类更新
        if ($_POST['action'] === 'store_category_update') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $sort = intval($_POST['sort'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => '分类名称不能为空']);
                exit;
            }
            $db->update('product_categories', [
                'name' => $name, 'parent_id' => $parent_id,
                'sort' => $sort, 'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '分类更新成功']);
            exit;
        }

        // 搜索可添加商品
        if ($_POST['action'] === 'store_products_search') {
            $storeId = intval($_POST['store_id'] ?? 0);
            $keyword = trim($_POST['keyword'] ?? '');
            $where = ['1=1'];
            $params = [];
            if ($keyword !== '') {
                $where[] = 'p.name LIKE ?';
                $params[] = "%{$keyword}%";
            }
            if ($storeId) {
                $where[] = 'p.id NOT IN (SELECT product_id FROM store_products WHERE store_id = ?)';
                $params[] = $storeId;
            }
            $whereSql = implode(' AND ', $where);
            $rows = $db->fetchAll(
                "SELECT p.id, p.name, p.price, p.stock, p.status, p.images
                 FROM products p WHERE {$whereSql} ORDER BY p.id DESC LIMIT 50",
                $params
            );
            foreach ($rows as &$p) {
                $p['image'] = $p['images'] ? (json_decode($p['images'], true)[0] ?? '') : '';
                unset($p['images']);
            }
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        // 添加门店商品关联
        if ($_POST['action'] === 'store_products_add') {
            $storeId = intval($_POST['store_id'] ?? 0);
            $productId = intval($_POST['product_id'] ?? 0);
            if (!$storeId || !$productId) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            try {
                $db->query("INSERT INTO store_products (store_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id", [$storeId, $productId]);
                echo json_encode(['success' => true, 'message' => '关联成功']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => '关联失败：' . $e->getMessage()]);
            }
            exit;
        }

        // 移除门店商品关联
        if ($_POST['action'] === 'store_products_remove') {
            $storeId = intval($_POST['store_id'] ?? 0);
            $productId = intval($_POST['product_id'] ?? 0);
            $db->query("DELETE FROM store_products WHERE store_id = ? AND product_id = ?", [$storeId, $productId]);
            echo json_encode(['success' => true, 'message' => '已移除']);
            exit;
        }

        // ========== 店铺等级评定 ==========
        if ($_POST['action'] === 'calc_level') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM stores WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '门店不存在']);
                exit;
            }

            // --- 计算五维评分 ---
            // 1. 经营规模（30分）
            $monthlySales = intval($row['monthly_sales'] ?? 0);
            if ($monthlySales >= 500) $score1 = 30;
            elseif ($monthlySales >= 300) $score1 = 25;
            elseif ($monthlySales >= 100) $score1 = 18;
            elseif ($monthlySales >= 30) $score1 = 10;
            else $score1 = 5;

            // 2. 用户口碑（25分）
            $rating = floatval($row['star_rating'] ?? 0);
            if ($rating >= 4.8) $score2 = 25;
            elseif ($rating >= 4.7) $score2 = 22;
            elseif ($rating >= 4.5) $score2 = 18;
            elseif ($rating >= 4.2) $score2 = 14;
            elseif ($rating >= 3.5) $score2 = 8;
            else $score2 = 3;

            // 3. 履约服务（20分）- 按默认良好计分
            // TODO: 接入订单数据后细算
            $score3 = 15;

            // 4. 投诉合规（15分）- 按默认良好计分
            // TODO: 接入售后/投诉数据后细算
            $score4 = 12;

            // 5. 用户复购（10分）- 按默认良好计分
            // TODO: 接入复购率数据后细算
            if ($monthlySales >= 200) $score5 = 8;
            elseif ($monthlySales >= 50) $score5 = 5;
            else $score5 = 2;

            // 总分
            $total = $score1 + $score2 + $score3 + $score4 + $score5;

            // --- 定级 ---
            $levelName = '';
            $levelBadge = '';
            if ($total >= 95) { $levelName = '5级 · 头部品牌'; $levelBadge = 'head'; }
            elseif ($total >= 90) { $levelName = '4级 · 标杆店铺'; $levelBadge = 'benchmark'; }
            elseif ($total >= 75) { $levelName = '3级 · 优质经营'; $levelBadge = 'premium'; }
            elseif ($total >= 60) { $levelName = '2级 · 合规经营'; $levelBadge = 'standard'; }
            else { $levelName = '1级 · 新手入驻'; $levelBadge = 'basic'; }

            $detail = json_encode([
                'total' => $total,
                'level_name' => $levelName,
                'level_badge' => $levelBadge,
                'dimensions' => [
                    ['name' => '经营规模', 'score' => $score1, 'full' => 30, 'desc' => '月售' . $monthlySales . '单'],
                    ['name' => '用户口碑', 'score' => $score2, 'full' => 25, 'desc' => '评分' . number_format($rating, 1)],
                    ['name' => '履约服务', 'score' => $score3, 'full' => 20, 'desc' => '接单及时·出餐稳定'],
                    ['name' => '投诉合规', 'score' => $score4, 'full' => 15, 'desc' => '客诉情况良好'],
                    ['name' => '用户复购', 'score' => $score5, 'full' => 10, 'desc' => '复购趋势稳定'],
                ]
            ]);

            // 保存到数据库
            $now = date('Y-m-d H:i:s');
            $db->update('stores', [
                'level' => $levelName,
                'level_score' => $total,
                'level_detail' => $detail,
                'level_updated_at' => $now,
                'updated_at' => $now,
            ], 'id = ?', [$id]);

            echo json_encode([
                'success' => true,
                'message' => '等级评定完成',
                'data' => [
                    'level' => $levelName,
                    'score' => $total,
                    'detail' => json_decode($detail, true),
                ]
            ]);
            exit;
        }

        // 批量评定等级
        if ($_POST['action'] === 'batch_calc_level') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => '请选择门店']);
                exit;
            }
            $ids = array_map('intval', $ids);
            $count = 0;
            foreach ($ids as $sid) {
                $row = $db->fetchOne("SELECT * FROM stores WHERE id = ?", [$sid]);
                if (!$row) continue;
                // 复用计算逻辑
                $monthlySales = intval($row['monthly_sales'] ?? 0);
                $rating = floatval($row['star_rating'] ?? 0);
                if ($monthlySales >= 500) $s1 = 30;
                elseif ($monthlySales >= 300) $s1 = 25;
                elseif ($monthlySales >= 100) $s1 = 18;
                elseif ($monthlySales >= 30) $s1 = 10;
                else $s1 = 5;
                if ($rating >= 4.8) $s2 = 25;
                elseif ($rating >= 4.7) $s2 = 22;
                elseif ($rating >= 4.5) $s2 = 18;
                elseif ($rating >= 4.2) $s2 = 14;
                elseif ($rating >= 3.5) $s2 = 8;
                else $s2 = 3;
                $s3 = 15; $s4 = 12;
                $s5 = ($monthlySales >= 200) ? 8 : (($monthlySales >= 50) ? 5 : 2);
                $total = $s1 + $s2 + $s3 + $s4 + $s5;
                if ($total >= 95) $name = '5级 · 头部品牌';
                elseif ($total >= 90) $name = '4级 · 标杆店铺';
                elseif ($total >= 75) $name = '3级 · 优质经营';
                elseif ($total >= 60) $name = '2级 · 合规经营';
                else $name = '1级 · 新手入驻';
                $now = date('Y-m-d H:i:s');
                $detail = json_encode(['total' => $total, 'level_name' => $name, 'dimensions' => []]);
                $db->update('stores', ['level' => $name, 'level_score' => $total, 'level_detail' => $detail, 'level_updated_at' => $now, 'updated_at' => $now], 'id = ?', [$sid]);
                $count++;
            }
            echo json_encode(['success' => true, 'message' => "已完成 $count 个门店的等级评定"]);
            exit;
        }

        // ========== 店铺星级评定 ==========
        if ($_POST['action'] === 'calc_star') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM stores WHERE id = ?", [$id]);
            if (!$row) { echo json_encode(['success' => false, 'message' => '门店不存在']); exit; }

            $monthlySales = intval($row['monthly_sales'] ?? 0);
            $rating = floatval($row['star_rating'] ?? 0);

            // 五大维度评分计算（模拟，TODO：接入真实评价数据）
            // 1. 餐品口味与品质（40%）
            $d1 = $rating >= 4.5 ? 9.5 : ($rating >= 4.0 ? 8.0 : ($rating >= 3.0 ? 6.5 : ($rating >= 2.0 ? 4.0 : 2.0)));
            // 2. 餐品包装与卫生（20%）
            $d2 = $rating >= 4.5 ? 9.0 : ($rating >= 4.0 ? 7.5 : ($rating >= 3.0 ? 6.0 : ($rating >= 2.0 ? 4.0 : 2.0)));
            // 3. 配送履约与时效（20%）
            $d3 = $monthlySales >= 200 ? 8.5 : ($monthlySales >= 50 ? 7.0 : 6.0);
            // 4. 商家服务与订单准确率（15%）
            $d4 = $rating >= 4.5 ? 9.0 : ($rating >= 4.0 ? 7.5 : ($rating >= 3.0 ? 6.0 : 4.0));
            // 5. 性价比匹配度（5%）
            $d5 = $rating >= 4.5 ? 8.5 : ($rating >= 4.0 ? 7.0 : ($rating >= 3.0 ? 5.5 : 3.5));

            // 综合星级 = 加权平均（满分10分制，再映射到5分制）
            $weightedAvg = $d1 * 0.40 + $d2 * 0.20 + $d3 * 0.20 + $d4 * 0.15 + $d5 * 0.05;
            $finalStar = round($weightedAvg / 2, 1); // 10分→5分
            if ($finalStar > 5.0) $finalStar = 5.0;
            if ($finalStar < 1.0) $finalStar = 1.0;

            // 映射星级区间对应的体验等级描述
            $starLevel = '';
            if ($finalStar >= 4.8) $starLevel = '非常满意（最优体验）';
            elseif ($finalStar >= 4.0) $starLevel = '满意（良好体验）';
            elseif ($finalStar >= 3.0) $starLevel = '一般（合格体验）';
            elseif ($finalStar >= 2.0) $starLevel = '不满意（较差体验）';
            else $starLevel = '非常不满意（极差体验）';

            $detail = json_encode([
                'final' => $finalStar,
                'star_level' => $starLevel,
                'dimensions' => [
                    ['name' => '餐品口味与品质', 'weight' => '40%', 'score' => $d1, 'full' => 10],
                    ['name' => '餐品包装与卫生', 'weight' => '20%', 'score' => $d2, 'full' => 10],
                    ['name' => '配送履约与时效', 'weight' => '20%', 'score' => $d3, 'full' => 10],
                    ['name' => '商家服务与订单准确率', 'weight' => '15%', 'score' => $d4, 'full' => 10],
                    ['name' => '性价比匹配度', 'weight' => '5%', 'score' => $d5, 'full' => 10],
                ]
            ]);

            $now = date('Y-m-d H:i:s');
            $db->update('stores', [
                'star_rating' => $finalStar,
                'star_detail' => $detail,
                'updated_at' => $now,
            ], 'id = ?', [$id]);

            echo json_encode([
                'success' => true,
                'message' => "星级评定完成：{$finalStar}分（{$starLevel}）",
                'data' => ['star_rating' => $finalStar, 'star_detail' => json_decode($detail, true)]
            ]);
            exit;
        }

        // ========== 门店标签 ==========
        if ($_POST['action'] === 'store_tags_list') {
            $rows = $db->fetchAll("SELECT * FROM store_tags WHERE is_active = 1 ORDER BY sort ASC, id ASC");
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        // ========== 门店徽章 ==========
        if ($_POST['action'] === 'store_badges_list') {
            $rows = $db->fetchAll("SELECT * FROM store_badges WHERE is_active = 1 ORDER BY sort ASC, id ASC");
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        // ========== 商品 CRUD ==========

        // 新增商品
        if ($_POST['action'] === 'product_create') {
            $storeId = intval($_POST['store_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '商品名称不能为空']);
                exit;
            }

            $data = [
                'name' => $name,
                'type' => intval($_POST['type'] ?? 1),
                'category_id' => intval($_POST['category_id'] ?? 0) ?: null,
                'product_code' => trim($_POST['product_code'] ?? ''),
                'images' => trim($_POST['images'] ?? ''),
                'freight_template_id' => intval($_POST['freight_template_id'] ?? 0),
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'spec_type' => intval($_POST['spec_type'] ?? 1),
                'spec_data' => trim($_POST['spec_data'] ?? '[]'),
                'price' => floatval($_POST['price'] ?? 0),
                'member_price' => floatval($_POST['member_price'] ?? 0),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'stock' => intval($_POST['stock'] ?? 0),
                'weight' => floatval($_POST['weight'] ?? 0),
                'stock_method' => intval($_POST['stock_method'] ?? 1),
                'limit_buy' => intval($_POST['limit_buy'] ?? 0),
                'limit_buy_num' => intval($_POST['limit_buy_num'] ?? 0),
                'content' => trim($_POST['content'] ?? ''),
                'video_url' => trim($_POST['video_url'] ?? ''),
                'video_cover' => trim($_POST['video_cover'] ?? ''),
                'selling_points' => trim($_POST['selling_points'] ?? ''),
                'services' => trim($_POST['services'] ?? ''),
                'initial_sales' => intval($_POST['initial_sales'] ?? 0),
                'member_discount' => intval($_POST['member_discount'] ?? 0),
                'points_gift' => intval($_POST['points_gift'] ?? 0),
                'points_deduct' => intval($_POST['points_deduct'] ?? 0),
                'points_deduct_type' => trim($_POST['points_deduct_type'] ?? ''),
                'commission_type' => trim($_POST['commission_type'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $productId = $db->insert('products', $data);

            // 关联到门店
            if ($storeId && $productId) {
                $db->query("INSERT IGNORE INTO store_products (store_id, product_id) VALUES (?, ?)", [$storeId, $productId]);
            }

            echo json_encode(['success' => true, 'message' => '商品创建成功', 'product_id' => $productId]);
            exit;
        }

        // 编辑商品
        if ($_POST['action'] === 'product_update') {
            $productId = intval($_POST['product_id'] ?? 0);
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => '缺少商品ID']);
                exit;
            }
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '商品名称不能为空']);
                exit;
            }

            $data = [
                'name' => $name,
                'type' => intval($_POST['type'] ?? 1),
                'category_id' => intval($_POST['category_id'] ?? 0) ?: null,
                'product_code' => trim($_POST['product_code'] ?? ''),
                'images' => trim($_POST['images'] ?? ''),
                'freight_template_id' => intval($_POST['freight_template_id'] ?? 0),
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'spec_type' => intval($_POST['spec_type'] ?? 1),
                'spec_data' => trim($_POST['spec_data'] ?? '[]'),
                'price' => floatval($_POST['price'] ?? 0),
                'member_price' => floatval($_POST['member_price'] ?? 0),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'stock' => intval($_POST['stock'] ?? 0),
                'weight' => floatval($_POST['weight'] ?? 0),
                'stock_method' => intval($_POST['stock_method'] ?? 1),
                'limit_buy' => intval($_POST['limit_buy'] ?? 0),
                'limit_buy_num' => intval($_POST['limit_buy_num'] ?? 0),
                'content' => trim($_POST['content'] ?? ''),
                'video_url' => trim($_POST['video_url'] ?? ''),
                'video_cover' => trim($_POST['video_cover'] ?? ''),
                'selling_points' => trim($_POST['selling_points'] ?? ''),
                'services' => trim($_POST['services'] ?? ''),
                'initial_sales' => intval($_POST['initial_sales'] ?? 0),
                'member_discount' => intval($_POST['member_discount'] ?? 0),
                'points_gift' => intval($_POST['points_gift'] ?? 0),
                'points_deduct' => intval($_POST['points_deduct'] ?? 0),
                'points_deduct_type' => trim($_POST['points_deduct_type'] ?? ''),
                'commission_type' => trim($_POST['commission_type'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $db->update('products', $data, 'id = ?', [$productId]);
            echo json_encode(['success' => true, 'message' => '商品更新成功']);
            exit;
        }

        // 商品上下架切换
        if ($_POST['action'] === 'product_toggle_status') {
            $productId = intval($_POST['product_id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $db->update('products', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$productId]);
            echo json_encode(['success' => true, 'message' => '状态已更新']);
            exit;
        }

        // 获取单个商品信息（含图片/视频 URL 解析）
        if ($_POST['action'] === 'product_get') {
            $productId = intval($_POST['product_id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
            if ($row) {
                // 解析图片 URL
                $imgs = json_decode($row['images'] ?? '[]', true);
                $resolvedImgs = [];
                require_once __DIR__ . '/../../config/oss.php';
                require_once __DIR__ . '/../../includes/OSSClient.php';
                $ossReady = defined('OSS_ENABLED') && OSS_ENABLED;
                foreach ($imgs as $img) {
                    if (strpos($img, 'http') === 0 || strpos($img, '/') === 0) {
                        $resolvedImgs[] = $img;
                    } elseif ($ossReady) {
                        try { $resolvedImgs[] = OSSClient::getInstance()->getFileUrl($img); }
                        catch (Exception $e) { $resolvedImgs[] = '/uploads/products/' . basename($img); }
                    } else {
                        $resolvedImgs[] = '/uploads/products/' . basename($img);
                    }
                }
                $row['images'] = json_encode($resolvedImgs);
                // 解析视频 URL
                if ($row['video_url'] && strpos($row['video_url'], 'http') !== 0 && strpos($row['video_url'], '/') !== 0) {
                    if ($ossReady) {
                        try { $row['video_url'] = OSSClient::getInstance()->getFileUrl($row['video_url']); }
                        catch (Exception $e) { $row['video_url'] = '/uploads/videos/' . basename($row['video_url']); }
                    } else {
                        $row['video_url'] = '/uploads/videos/' . basename($row['video_url']);
                    }
                }
                // 解析视频封面
                if ($row['video_cover'] && strpos($row['video_cover'], 'http') !== 0 && strpos($row['video_cover'], '/') !== 0) {
                    if ($ossReady) {
                        try { $row['video_cover'] = OSSClient::getInstance()->getFileUrl($row['video_cover']); }
                        catch (Exception $e) { $row['video_cover'] = '/uploads/products/' . basename($row['video_cover']); }
                    } else {
                        $row['video_cover'] = '/uploads/products/' . basename($row['video_cover']);
                    }
                }
            }
            echo json_encode(['success' => true, 'data' => $row ?: null]);
            exit;
        }

        // 删除规格 OSS 图片
        if ($_POST['action'] === 'product_delete_oss_images') {
            $images = json_decode($_POST['images'] ?? '[]', true);
            $deleted = 0;
            if (!empty($images)) {
                require_once __DIR__ . '/../../config/oss.php';
                require_once __DIR__ . '/../../includes/OSSClient.php';
                require_once __DIR__ . '/../../includes/SimpleOSSClient.php';
                $ossClient = OSSClient::getInstance();
                foreach ($images as $img) {
                    if (strpos($img, 'http') !== 0) continue;
                    $parsed = parse_url($img);
                    $path = ltrim($parsed['path'] ?? '', '/');
                    $uploadDir = defined('OSS_UPLOAD_DIR') ? OSS_UPLOAD_DIR : '';
                    if ($uploadDir && strpos($path, $uploadDir . '/') === 0) {
                        $path = substr($path, strlen($uploadDir) + 1);
                    }
                    try {
                        $ossClient->deleteObject($path);
                        $deleted++;
                    } catch (Exception $e) {}
                }
            }
            echo json_encode(['success' => true, 'message' => "已清理 {$deleted} 张规格图片"]);
            exit;
        }

        // 商品分类列表
        if ($_POST['action'] === 'product_categories') {
            $rows = $db->fetchAll("SELECT id, name FROM product_categories WHERE status = 1 ORDER BY sort DESC, id ASC");
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
        }

        // ========== 活动管理 - 优惠券 CRUD ==========

        // 优惠券列表
        if ($_POST['action'] === 'coupon_list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = min(intval($_POST['pageSize'] ?? 20), 100);
            $offset = ($page - 1) * $pageSize;

            $where = ['1=1'];
            $params = [];
            if ($keyword !== '') {
                $where[] = 'name LIKE ?';
                $params[] = "%{$keyword}%";
            }

            $whereSql = implode(' AND ', $where);
            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM coupons WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = [];
            if ($total > 0) {
                $rows = $db->fetchAll("SELECT * FROM coupons WHERE {$whereSql} ORDER BY sort DESC, id DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
            }

            echo json_encode(['success' => true, 'data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
            exit;
        }

        // 优惠券详情
        if ($_POST['action'] === 'coupon_detail') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM coupons WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => $row ?: null]);
            exit;
        }

        // 新增优惠券
        if ($_POST['action'] === 'coupon_add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '优惠券名称不能为空']);
                exit;
            }

            $data = [
                'name' => $name,
                'description' => trim($_POST['description'] ?? ''),
                'type' => trim($_POST['type'] ?? 'discount'),
                'value' => floatval($_POST['value'] ?? 0),
                'min_amount' => floatval($_POST['min_amount'] ?? 0),
                'max_amount' => !empty($_POST['max_amount']) ? floatval($_POST['max_amount']) : null,
                'total' => intval($_POST['total'] ?? 0),
                'per_limit' => intval($_POST['per_limit'] ?? 1),
                'start_time' => trim($_POST['start_time'] ?? '') ?: null,
                'end_time' => trim($_POST['end_time'] ?? '') ?: null,
                'receive_start' => trim($_POST['receive_start'] ?? '') ?: null,
                'receive_end' => trim($_POST['receive_end'] ?? '') ?: null,
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'use_range' => trim($_POST['use_range'] ?? 'all'),
                'instructions' => trim($_POST['instructions'] ?? ''),
                'is_show' => intval($_POST['is_show'] ?? 1),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = $db->insert('coupons', $data);
            echo json_encode(['success' => true, 'message' => '优惠券添加成功', 'id' => $id]);
            exit;
        }

        // 编辑优惠券
        if ($_POST['action'] === 'coupon_edit') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id FROM coupons WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '优惠券不存在']);
                exit;
            }

            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '优惠券名称不能为空']);
                exit;
            }

            $db->update('coupons', [
                'name' => $name,
                'description' => trim($_POST['description'] ?? ''),
                'type' => trim($_POST['type'] ?? 'discount'),
                'value' => floatval($_POST['value'] ?? 0),
                'min_amount' => floatval($_POST['min_amount'] ?? 0),
                'max_amount' => !empty($_POST['max_amount']) ? floatval($_POST['max_amount']) : null,
                'total' => intval($_POST['total'] ?? 0),
                'per_limit' => intval($_POST['per_limit'] ?? 1),
                'start_time' => trim($_POST['start_time'] ?? '') ?: null,
                'end_time' => trim($_POST['end_time'] ?? '') ?: null,
                'receive_start' => trim($_POST['receive_start'] ?? '') ?: null,
                'receive_end' => trim($_POST['receive_end'] ?? '') ?: null,
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'use_range' => trim($_POST['use_range'] ?? 'all'),
                'instructions' => trim($_POST['instructions'] ?? ''),
                'is_show' => intval($_POST['is_show'] ?? 1),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => '优惠券更新成功']);
            exit;
        }

        // 删除优惠券
        if ($_POST['action'] === 'coupon_delete') {
            $id = intval($_POST['id'] ?? 0);
            $db->delete('coupons', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '优惠券已删除']);
            exit;
        }

        // 切换优惠券状态
        if ($_POST['action'] === 'coupon_toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $db->update('coupons', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '状态已更新']);
            exit;
        }

        // ========== 秒杀 CRUD ==========

        // 秒杀列表
        if ($_POST['action'] === 'seckill_list') {
            $keyword = trim($_POST['keyword'] ?? '');
            $page = max(1, intval($_POST['page'] ?? 1));
            $pageSize = min(intval($_POST['pageSize'] ?? 20), 100);
            $offset = ($page - 1) * $pageSize;

            $where = ['1=1'];
            $params = [];
            if ($keyword !== '') {
                $where[] = 'name LIKE ?';
                $params[] = "%{$keyword}%";
            }

            $whereSql = implode(' AND ', $where);
            $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM seckill_activities WHERE {$whereSql}", $params);
            $total = intval($countRow['total'] ?? 0);

            $rows = [];
            if ($total > 0) {
                $rows = $db->fetchAll("SELECT * FROM seckill_activities WHERE {$whereSql} ORDER BY sort DESC, id DESC LIMIT {$pageSize} OFFSET {$offset}", $params);
            }

            echo json_encode(['success' => true, 'data' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize]);
            exit;
        }

        // 秒杀详情
        if ($_POST['action'] === 'seckill_detail') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT * FROM seckill_activities WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'data' => $row ?: null]);
            exit;
        }

        // 新增秒杀
        if ($_POST['action'] === 'seckill_add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '活动名称不能为空']);
                exit;
            }

            $data = [
                'name' => $name,
                'product_id' => intval($_POST['product_id'] ?? 0),
                'image' => trim($_POST['image'] ?? ''),
                'seckill_price' => floatval($_POST['seckill_price'] ?? 0),
                'original_price' => floatval($_POST['original_price'] ?? 0),
                'stock' => intval($_POST['stock'] ?? 0),
                'limit_buy' => intval($_POST['limit_buy'] ?? 1),
                'start_time' => trim($_POST['start_time'] ?? '') ?: null,
                'end_time' => trim($_POST['end_time'] ?? '') ?: null,
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = $db->insert('seckill_activities', $data);
            echo json_encode(['success' => true, 'message' => '秒杀活动添加成功', 'id' => $id]);
            exit;
        }

        // 编辑秒杀
        if ($_POST['action'] === 'seckill_edit') {
            $id = intval($_POST['id'] ?? 0);
            $row = $db->fetchOne("SELECT id FROM seckill_activities WHERE id = ?", [$id]);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '秒杀活动不存在']);
                exit;
            }

            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                echo json_encode(['success' => false, 'message' => '活动名称不能为空']);
                exit;
            }

            $db->update('seckill_activities', [
                'name' => $name,
                'product_id' => intval($_POST['product_id'] ?? 0),
                'image' => trim($_POST['image'] ?? ''),
                'seckill_price' => floatval($_POST['seckill_price'] ?? 0),
                'original_price' => floatval($_POST['original_price'] ?? 0),
                'stock' => intval($_POST['stock'] ?? 0),
                'limit_buy' => intval($_POST['limit_buy'] ?? 1),
                'start_time' => trim($_POST['start_time'] ?? '') ?: null,
                'end_time' => trim($_POST['end_time'] ?? '') ?: null,
                'status' => intval($_POST['status'] ?? 1),
                'sort' => intval($_POST['sort'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => '秒杀活动更新成功']);
            exit;
        }

        // 删除秒杀
        if ($_POST['action'] === 'seckill_delete') {
            $id = intval($_POST['id'] ?? 0);
            $db->delete('seckill_activities', 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '秒杀活动已删除']);
            exit;
        }

        // 切换秒杀状态
        if ($_POST['action'] === 'seckill_toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            $status = intval($_POST['status'] ?? 0);
            $db->update('seckill_activities', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
            echo json_encode(['success' => true, 'message' => '状态已更新']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => '未知操作']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        exit;
    }
}

// 初始加载时的国家/城市下拉
$countries = $db->fetchAll("SELECT DISTINCT country FROM stores WHERE country IS NOT NULL AND country <> '' ORDER BY country");
$cities = $db->fetchAll("SELECT DISTINCT city FROM stores WHERE city IS NOT NULL AND city <> '' ORDER BY city");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <!-- 高德地图 JSAPI v2.0：Key 请在 https://console.amap.com/dev/key/app 申请 -->
    <style>
        .spec-group{border:1px solid #eee;border-radius:8px;margin-bottom:12px;overflow:hidden;}
        .spec-group-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f8f9fa;font-weight:600;}
        .spec-group-body{padding:8px 12px;}
        .spec-item{display:flex;align-items:center;gap:6px;padding:8px 0;border-bottom:1px dashed #f0f0f0;}
        .spec-item:last-child{border-bottom:none;}
        .spec-item input{padding:6px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;outline:none;width:95px;}
        .spec-item input.img{width:140px;}
        .spec-item input.name{width:110px;}
        .spec-item input:focus{border-color:#409eff;}
        .spec-margin{width:70px;text-align:center;font-size:13px;font-weight:600;}
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: nowrap;
            gap: 12px;
        }

        .toolbar-actions {
            display: flex;
            gap: 12px;
            flex-wrap: nowrap;
        }

        .search-panel { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .search-form { display: flex; gap: 16px; flex-wrap: nowrap; align-items: flex-end; }
        .form-item { display: flex; flex-direction: column; gap: 6px; min-width: 140px; }
        .form-item label { font-size: 13px; color: #8c8c8c; font-weight: 600; }
        .form-item input, .form-item select { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; }
        .form-item input:focus, .form-item select:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,0.1); }


        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff4d4f, #ff7875);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #52c41a, #95de64);
            color: white;
        }
        .btn-default {
            background: #f5f5f5;
            color: #595959;
        }
        .btn-default:hover {
            background: #e6e6e6;
        }

        .batch-toolbar {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
            flex-wrap: nowrap;
            gap: 12px;
        }
        .batch-toolbar.show { display: flex; }

        .toggle-switch {
            position: relative;
            width: 36px;
            height: 22px;
            background: #d9d9d9;
            border-radius: 11px;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }
        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle-switch.on { background: linear-gradient(135deg, #52c41a, #95de64); }
        .toggle-switch.on::after { transform: translateX(22px); }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: #f6ffed; color: #237804; }
        .badge-danger { background: #fff2f0; color: #cf1322; }
        .badge-primary { background: #e6f7ff; color: #096dd9; }

        .sort-input {
            width: 70px;
            padding: 4px 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-align: center;
            font-size: 13px;
        }
        .sort-input:focus { outline: none; border-color: var(--primary-color); }

        .action-btn {
            padding: 4px 10px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s;
        }
        .action-btn.edit { color: var(--primary-color); }
        .action-btn.edit:hover { background: #f0f5ff; }
        .action-btn.delete { color: #ff4d4f; }
        .action-btn.delete:hover { background: #fff2f0; }
        .action-btn.view { color: #13c2c2; }
        .action-btn.view:hover { background: #e6fffb; }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 32px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15);
        }
        .modal-header {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .modal-body { margin-bottom: 24px; }
        /* 抽屉 TAB 切换 */
        .drawer-tabs {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
            padding: 0 24px;
            flex-shrink: 0;
            overflow-x: auto;
        }
        .drawer-tab {
            padding: 14px 18px;
            font-size: 13px;
            color: #8c8c8c;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            font-weight: 500;
        }
        .drawer-tab:hover { color: #262626; }
        .drawer-tab.active {
            color: #1890ff;
            border-bottom-color: #1890ff;
            font-weight: 600;
        }
        .tab-section { display: none; }
        .tab-section.active { display: block; }
        .image-upload-area {
            border: 2px dashed #d9d9d9;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            color: #8c8c8c;
            transition: all 0.3s;
            background: #fafafa;
        }
        .image-upload-area:hover {
            border-color: #1890ff;
            background: #f0f5ff;
            color: #1890ff;
        }


        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group label .required { color: #ff4d4f; }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }
        .empty-state .icon { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state .text { font-size: 14px; }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: nowrap;
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 40px;
            padding: 12px 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .pagination .pag-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 34px;
            min-width: 34px;
            padding: 0 12px;
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 17px;
            text-decoration: none;
            color: #595959;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-sizing: border-box;
            user-select: none;
        }
        .pagination .pag-btn:hover { border-color: #1890ff; color: #1890ff; background: #f0f8ff; }
        .pagination .pag-btn.active { background: #1890ff; color: white; border-color: #1890ff; font-weight: 600; }
        .pagination .pag-btn.active:hover { background: #40a9ff; border-color: #40a9ff; }
        .pagination .pag-btn.disabled { color: #d9d9d9; border-color: #f0f0f0; cursor: default; background: #fafafa; }
        .pagination .pag-btn.disabled:hover { border-color: #f0f0f0; color: #d9d9d9; background: #fafafa; }
       .pagination .pag-page-size {
           height: 34px;
            width: auto;
            min-width: 80px;
            padding: 0 24px 0 10px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238c8c8c'/%3E%3C/svg%3E") no-repeat right 8px center;
            border: 1px solid #e8e8e8;
            border-radius: 17px;
            font-size: 13px;
            outline: none;
            cursor: pointer;
            color: #595959;
            background: white;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .pagination .pag-page-size:hover { border-color: #1890ff; }
        .pagination .pag-page-size:focus { border-color: #1890ff; box-shadow: 0 0 0 2px rgba(24,144,255,0.1); }
        .pagination .pag-info {
            color: #8c8c8c;
            font-size: 13px;
            white-space: nowrap;
        }
        .pagination .pag-sep {
            color: #e8e8e8;
            font-size: 14px;
            user-select: none;
        }
        .checkbox { width: 16px; height: 16px; cursor: pointer; }

        /* 地图选点组件 */
        #mapContainer { z-index:1; width:100%; height:400px; }
        #mapModal .modal-content { animation: slideIn 0.3s ease; }
        /* 图片上传器（复用 product_edit 样式） */
        .image-uploader {
            border: 2px dashed #d9d9d9;
            border-radius: 12px;
            width: 150px;
            height: 150px;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }
        .image-uploader:hover {
            border-color: #1890ff;
            background: #f0f5ff;
        }
        .image-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .image-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #f0f0f0;
            transition: all 0.3s;
            cursor: grab;
        }
        .image-item:hover {
            border-color: #1890ff;
            box-shadow: 0 4px 12px rgba(24, 144, 255, 0.2);
        }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-item .remove {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            background: rgba(255, 77, 79, 0.95);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .image-item .remove:hover {
            background: #ff4d4f;
            transform: scale(1.1);
        }
        .image-item.dragging {
            opacity: 0.4;
            border: 2px dashed #1890ff;
            cursor: grabbing;
        }
        .image-item:first-child::after {
            content: "首页展示图";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(24, 144, 255, 0.85);
            color: #fff;
            font-size: 12px;
            text-align: center;
            padding: 3px 0;
            z-index: 1;
            pointer-events: none;
        }
        .search-result-item {
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.2s;
        }
        .search-result-item:hover { background: #f0f5ff; color: #1890ff; }
        /* Z30075 门店照片组件样式 */
        .piu-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
            min-height: 80px;
        }
        .piu-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #d9d9d9;
            flex-shrink: 0;
            background: #fff;
        }
        .piu-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .piu-remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            z-index: 3;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(0,0,0,0.55);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .piu-remove-btn:hover {
            background: rgba(255,77,79,0.9);
        }
        .piu-remove-btn::before {
            content: '×';
            font-size: 15px;
        }
        .piu-uploader {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            border: 2px dashed #d9d9d9;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #fafafa;
            transition: all 0.25s ease;
            user-select: none;
        }
        .piu-uploader:hover {
            border-color: #1890ff;
            background: #f0f5ff;
        }
        .piu-uploader__icon { font-size: 32px; margin-bottom: 6px; line-height: 1; }
        .piu-uploader__text { font-size: 12px; font-weight: 500; color: #262626; }
        .piu-uploader__hint { font-size: 10px; color: #8c8c8c; margin-top: 3px; }
        .piu-hidden-input { display: none; }

        .cell-name { font-weight: 600; color: var(--text-primary); }
        .cell-loc { color: var(--text-secondary); font-size: 12px; }
        .cell-coord { color: #8c8c8c; font-size: 12px; font-family: 'SF Mono', Monaco, monospace; }
        .data-table table td { max-width:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-align:center; }
        .data-table table td.td-left { text-align:left; }
        .data-table table th { text-align:center; position:relative; }
        .data-table table th:not(:last-child)::after { content:''; position:absolute; top:8px; right:0; width:1px; height:calc(100% - 16px); background:#e0e0e0; }
        .col-resize-handle { position:absolute; top:0; right:-3px; width:6px; height:100%; cursor:col-resize; z-index:10; }

        .stat-line {
            font-size: 12px;
            color: var(--text-secondary);
            margin-right: 12px;
        }
        .stat-line strong { color: var(--primary-color); font-weight: 600; }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 240px;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 14px;
            animation: slideIn 0.3s ease;
        }
        .alert-success { background: #f6ffed; color: #237804; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffccc7; }
        .alert-info { background: #e6f7ff; color: #096dd9; border: 1px solid #91d5ff; }

        /* 抽屉 */
        .page-drawer {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 2000;
        }
        .page-drawer.show { display: flex; justify-content: flex-end; }
        .page-drawer .drawer-mask {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .page-drawer .drawer-content {
            position: relative;
            width: 1300px;
            max-width: 95%;
            height: 100%;
            background: white;
            box-shadow: -4px 0 24px rgba(0,0,0,0.15);
            animation: slideIn 0.3s;
            display: flex;
            flex-direction: column;
        }
        .page-drawer .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
        }
        .page-drawer .drawer-header h3 { margin: 0; font-size: 18px; color: #262626; }
        .page-drawer .drawer-close {
            width: 32px; height: 32px; border: none; background: transparent;
            font-size: 24px; color: #8c8c8c; cursor: pointer; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .page-drawer .drawer-close:hover { background: #f5f5f5; color: #262626; }
        .page-drawer .drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
        .page-drawer .drawer-footer {
            padding: 16px 24px; border-top: 1px solid #f0f0f0;
            display: flex; justify-content: flex-end; gap: 12px;
        }
        .page-drawer.closing .drawer-content { animation: slideOut 0.3s forwards; }
        .page-drawer.closing .drawer-mask { animation: fadeOut 0.3s forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(100%); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../includes/header.php'; ?>

        <div class="content-wrapper">
            <!-- 工具栏 -->
            <div class="toolbar">
                <h1 style="font-size: 24px; color: var(--text-primary); margin: 0;">🏪 <?= $pageTitle ?></h1>
                <div class="toolbar-actions">
                    <button class="btn btn-primary" onclick="openAddDrawer()">
                        <span>➕</span> 新增门店
                    </button>
                </div>
            </div>

            <!-- 搜索面板 -->
            <div class="search-panel">
                <form class="search-form" onsubmit="loadList(1);return false">
                    <div class="form-item">
                        <label>关键词</label>
                        <input type="text" id="searchKeyword" placeholder="门店名称">
                    </div>
                    <div class="form-item">
                        <label>等级</label>
                        <select id="searchLevel">
                            <option value="">全部</option>
                            <option value="5级 · 头部品牌">5级 · 头部品牌</option>
                            <option value="4级 · 标杆店铺">4级 · 标杆店铺</option>
                            <option value="3级 · 优质经营">3级 · 优质经营</option>
                            <option value="2级 · 合规经营">2级 · 合规经营</option>
                            <option value="1级 · 新手入驻">1级 · 新手入驻</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>星级</label>
                        <select id="searchStar">
                            <option value="">全部</option>
                            <option value="5">5星 (4.8~5.0)</option>
                            <option value="4">4星 (4.0~4.7)</option>
                            <option value="3">3星 (3.0~3.9)</option>
                            <option value="2">2星 (2.0~2.9)</option>
                            <option value="1">1星 (1.0~1.9)</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>状态</label>
                        <select id="searchStatus">
                            <option value="">全部</option>
                            <option value="1">营业中</option>
                            <option value="0">已停业</option>
                        </select>
                    </div>
                    <div class="form-item" style="flex-direction:row;gap:8px;">
                        <button type="submit" class="btn btn-primary">🔍 搜索</button>
                        <button type="button" class="btn btn-default" onclick="resetSearch()">重置</button>
                    </div>
                </form>
            </div>



            <!-- 批量操作栏 -->
            <div class="batch-toolbar" id="batchToolbar">
                <div>已选择 <strong id="selectedCount">0</strong> 个门店</div>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="batchSetStatus(1)">✅ 批量启用</button>
                    <button class="btn btn-default" onclick="batchSetStatus(0)">⏸️ 批量停业</button>
                    <button class="btn btn-primary" onclick="batchCalcLevel()">📊 批量评定等级</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>

            <!-- 数据表格 -->
            <div class="data-table" id="tableContainer">
                <div class="empty-state">
                    <div class="icon">⏳</div>
                    <div class="text">数据加载中…</div>
                </div>
            </div>

            <!-- 分页 -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 新增/编辑抽屉（TAB 版） -->
    <div id="formDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeFormDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3 id="formTitle">门店管理</h3>
                <button class="drawer-close" onclick="closeFormDrawer()">x</button>
            </div>
            <div class="drawer-tabs" id="formDrawerTabs">
                <span class="drawer-tab active" data-tab="info">门店信息</span>
                <span class="drawer-tab" data-tab="products">商品管理</span>
                <span class="drawer-tab" data-tab="profile">门店档案</span>
                <span class="drawer-tab" data-tab="orders">门店订单</span>
                <span class="drawer-tab" data-tab="after">门店售后</span>
                <span class="drawer-tab" data-tab="reviews">门店评价</span>
                <span class="drawer-tab" data-tab="benefits">活动管理</span>
            </div>
            <div class="drawer-body">
                <!-- TAB1: 门店信息 -->
                <div class="tab-section active" data-tab="info">
                    <form id="storeForm">
                        <input type="hidden" name="id" id="formId">

                        <!-- 门店信息卡 -->
                        <div style="background:white;border:1px solid #f0f0f0;border-radius:12px;padding:20px;margin-bottom:20px;">
                            <div style="font-size:15px;font-weight:600;color:#262626;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f5f5f5;">📋 门店信息 <span id="infoCreatedAt" style="font-size:12px;font-weight:400;color:#8c8c8c;float:right;line-height:1.8;"></span></div>
                            <div style="display:flex;gap:16px;align-items:center;">
                                <!-- 头像 -->
                                <div style="position:relative;flex-shrink:0;">
                                    <div id="formAvatarPreview" style="width:72px;height:72px;border-radius:10px;overflow:hidden;background:#f5f5f5;border:2px solid #f0f0f0;cursor:pointer;" onclick="viewAvatar()" title="点击查看大图">
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:32px;">🏪</div>
                                    </div>
                                    <div onclick="document.getElementById('avatarFileInput').click()" style="position:absolute;bottom:-4px;left:4px;width:24px;height:24px;border-radius:50%;background:#1890ff;color:white;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;box-shadow:0 2px 6px rgba(24,144,255,0.4);border:2px solid white;" title="修改头像">✏️</div>
                                    <div onclick="removeAvatar()" style="position:absolute;bottom:-4px;right:4px;width:24px;height:24px;border-radius:50%;background:#ff4d4f;color:white;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;box-shadow:0 2px 6px rgba(255,77,79,0.4);border:2px solid white;" title="删除头像">🗑️</div>
                                    <input type="file" id="avatarFileInput" accept="image/*" style="display:none" onchange="handleAvatarUpload(event)">
                                    <input type="hidden" id="formAvatar" value="">
                                </div>
                                <!-- 右侧字段 -->
                                <div style="flex:1;display:flex;flex-direction:column;gap:10px;">
                                    <div>
                                        <label style="display:block;font-size:13px;color:#8c8c8c;font-weight:600;margin-bottom:4px;">门店名称 <span style="color:#ff4d4f;">*</span></label>
                                        <input type="text" id="formName" required placeholder="如：青园营地·莫干山店" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                    </div>
                                    <div style="display:flex;gap:10px;">
                                        <div style="flex:1;">
                                            <label style="display:block;font-size:13px;color:#8c8c8c;font-weight:600;margin-bottom:4px;">联系电话1</label>
                                            <input type="text" id="formPhone" placeholder="如：0572-88888888" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                        </div>
                                        <div style="flex:1;">
                                            <label style="display:block;font-size:13px;color:#8c8c8c;font-weight:600;margin-bottom:4px;">联系电话2</label>
                                            <input type="text" id="formPhone2" placeholder="备用电话" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- 只读信息区 -->
                            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f5f5f5;">
                                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">⭐</span>
                                        <span id="infoStarRating" style="font-size:14px;color:#8c8c8c;" title="点击查看星级评分细则">暂无评分</span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">📦</span>
                                        <span style="font-size:13px;color:#8c8c8c;">月售</span>
                                        <strong id="infoMonthlySales" style="font-size:14px;color:#262626;">0</strong>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">🏅</span>
                                        <span style="font-size:13px;color:#8c8c8c;">等级</span>
                                        <strong id="infoLevel" style="font-size:14px;color:#262626;">-</strong>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">🏆</span>
                                        <span style="font-size:13px;color:#8c8c8c;">徽章</span>
                                        <strong id="infoBadge" style="font-size:14px;color:#262626;">-</strong>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">🚚</span>
                                        <span style="font-size:13px;color:#8c8c8c;">配送</span>
                                        <strong id="infoDeliveryTime" style="font-size:14px;color:#262626;">--</strong>
                                        <span style="font-size:12px;color:#8c8c8c;">分钟</span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">🎫</span>
                                        <span style="font-size:13px;color:#8c8c8c;">优惠券</span>
                                        <strong id="infoCouponCount" style="font-size:14px;color:#262626;">0</strong>
                                        <span style="font-size:12px;color:#8c8c8c;">张</span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;">
                                        <span style="font-size:18px;">🕐</span>
                                        <span style="font-size:13px;color:#8c8c8c;">营业</span>
                                        <strong id="infoBusinessHours" style="font-size:14px;color:#262626;">-</strong>
                                    </div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#fafafa;border-radius:8px;margin-top:10px;">
                                    <span style="font-size:18px;">🔖</span>
                                    <span style="font-size:13px;color:#8c8c8c;">标签</span>
                                    <span id="infoTags" style="font-size:13px;color:#8c8c8c;">暂无标签</span>
                                </div>
                                <div style="text-align:right;margin-top:8px;display:flex;gap:8px;justify-content:flex-end;">
                                    <button type="button" class="btn btn-default" onclick="calcStar()" style="font-size:12px;padding:4px 12px;">⭐ 刷新星级评分</button>
                                    <button type="button" class="btn btn-default" onclick="calcLevel()" style="font-size:12px;padding:4px 12px;">🏅 刷新等级评定</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>详细地址</label>
                            <div style="display:flex;gap:8px;">
                                <input type="text" name="address" id="formAddress" placeholder="街道、门牌号" style="flex:1;">
                                <button type="button" class="btn btn-primary" onclick="openMapPicker()" style="white-space:nowrap;flex-shrink:0;padding:8px 14px;">🗺️ 地图选择</button>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group"><label>排序（数字越大越靠前）</label><input type="number" name="sort" id="formSort" value="0"></div>
                            <div class="form-group"><label>状态</label><select name="status" id="formStatus"><option value="1">营业中</option><option value="0">已停业</option></select></div>
                        </div>
                        <input type="hidden" id="formLat" value="">
                        <input type="hidden" id="formLng" value="">
                        <input type="hidden" id="formCountry" value="中国">
                        <input type="hidden" id="formCity" value="">
                        <div class="form-group"><label>门店描述</label><textarea name="description" id="formDesc" placeholder="简单介绍门店特色、配套、风景等"></textarea></div>

                        <!-- 门店关停管理 -->
                        <div style="background:white;border:1px solid #f0f0f0;border-radius:12px;padding:20px;margin-bottom:20px;">
                            <div style="font-size:15px;font-weight:600;color:#262626;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f5f5f5;">🚫 关停管理 <span id="suspendHistoryHint" style="font-size:12px;font-weight:400;color:#ff4d4f;cursor:pointer;float:right;line-height:1.8;display:none;" onclick="showSuspendHistory()">⚠️ 该门店有违规记录，点击查看</span></div>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                                <span style="font-size:13px;color:#595959;font-weight:600;">当前状态：</span>
                                <span id="storeStatusDisplay" style="display:inline-block;padding:3px 12px;border-radius:4px;font-size:13px;font-weight:600;background:#f6ffed;color:#237804;">营业中</span>
                                <span id="storeSuspendInfo" style="font-size:12px;color:#8c8c8c;"></span>
                            </div>
                            <div id="suspendFormArea" style="display:none;">
                                <div class="form-group">
                                    <label>关停理由</label>
                                    <textarea id="formSuspendReason" rows="3" placeholder="请说明关停原因，如：违规经营、食品安全问题、资质异常等" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
                                </div>
                                <div style="display:flex;gap:12px;">
                                    <div class="form-group" style="flex:1;">
                                        <label>关停类型</label>
                                        <select id="formSuspendType" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;">
                                            <option value="permanent">永久关停</option>
                                            <option value="temporary">临时关停（指定时长）</option>
                                            <option value="7days">关停 7 天</option>
                                            <option value="15days">关停 15 天</option>
                                            <option value="30days">关停 30 天</option>
                                            <option value="90days">关停 90 天</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex:1;" id="suspendUntilGroup" style="display:none;">
                                        <label>手动指定截止时间</label>
                                        <input type="datetime-local" id="formSuspendUntil" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                    </div>
                                </div>
                            </div>
                            <div id="suspendedInfoArea" style="display:none;">
                                <div style="padding:12px;background:#fff2f0;border-radius:8px;margin-bottom:12px;">
                                    <div style="font-size:13px;color:#cf1322;font-weight:600;margin-bottom:6px;">🔴 门店已关停</div>
                                    <div style="font-size:12px;color:#595959;line-height:1.8;">
                                        <strong>关停理由：</strong><span id="displaySuspendReason">-</span><br>
                                        <strong>关停时间：</strong><span id="displaySuspendAt">-</span><br>
                                        <strong>截止时间：</strong><span id="displaySuspendUntil">-</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;margin-top:8px;">
                                <button type="button" id="btnSuspendStore" class="btn btn-danger" onclick="showSuspendForm()" style="font-size:13px;padding:8px 16px;">🚫 关停门店</button>
                                <button type="button" id="btnRestoreStore" class="btn btn-success" onclick="restoreStore()" style="font-size:13px;padding:8px 16px;display:none;">✅ 恢复营业</button>
                            </div>
                        </div>
                        <input type="hidden" id="formSuspended" value="0">
                        <input type="hidden" id="formSuspendUntilHidden" value="">
                    </form>
                </div>
                                <!-- TAB2: 商品管理 -->
                <div class="tab-section" data-tab="products">
                    <div style="display:flex;gap:6px;margin-bottom:16px;border-bottom:1px solid #f0f0f0;padding-bottom:8px;">
                        <span class="prod-sub-tab active" data-subtab="products" onclick="switchProdTab('products')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#1890ff;color:#fff;transition:all 0.2s;">商品列表</span>
                        <span class="prod-sub-tab" data-subtab="categories" onclick="switchProdTab('categories')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">分类列表</span>
                    </div>
                    <div class="table-toolbar" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <span style="font-size:14px;color:#595959;" id="storeProductCount">该门店关联的商品</span>
                        <button class="btn btn-primary" id="prodTabActionBtn" onclick="openProductEditor()">+ 添加商品</button>
                    </div>
                    <div class="data-table" id="storeProductsTable">
                        <div class="empty-state"><div class="icon">🛒</div><div class="text">暂无关联商品</div></div>
                    </div>
                    <div class="data-table" id="storeCategoryTable" style="display:none;">
                        <div class="empty-state"><div class="icon">📂</div><div class="text">暂无分类数据</div></div>
                    </div>
                    <!-- 添加分类弹窗 -->
                    <div id="addCategoryModal" class="modal" style="z-index:2500;">
                        <div class="modal-content" style="max-width:520px;">
                            <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                                <span>新增分类</span>
                                <button class="drawer-close" onclick="closeCategoryModal()" style="width:28px;height:28px;border:none;background:transparent;font-size:20px;color:#8c8c8c;cursor:pointer;">×</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group"><label>分类名称 <span style="color:#ff4d4f;">*</span></label><input type="text" id="catName" placeholder="请输入分类名称" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;"></div>
                                <div class="form-group"><label>上级分类</label><select id="catParent" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;"><option value="0">无（顶级分类）</option></select></div>
                                <div class="form-group">
                                    <label>分类图片</label>
                                    <div onclick="document.getElementById('catImageInput').click()" style="width:120px;height:120px;border:2px dashed #d9d9d9;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;background:#fafafa;transition:all 0.3s;" onmouseover="this.style.borderColor='#1890ff'" onmouseout="this.style.borderColor='#d9d9d9'">
                                        <div id="catIconPreview" style="font-size:36px;margin-bottom:6px;">🖼️</div>
                                        <div style="font-size:12px;color:#8c8c8c;">点击上传图片</div>
                                    </div>
                                    <input type="file" id="catImageInput" accept="image/*" style="display:none;" onchange="handleCategoryImageUpload(event)">
                                    <input type="hidden" id="catIcon" value="">
                                </div>
                                <div class="form-row-2">
                                    <div class="form-group"><label>排序</label><input type="number" id="catSort" value="0" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;"></div>
                                    <div class="form-group"><label>状态</label><select id="catStatus" style="width:100%;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;"><option value="1">启用</option><option value="0">禁用</option></select></div>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;">
                                <button class="btn btn-default" onclick="closeCategoryModal()">取消</button>
                                <button class="btn btn-primary" onclick="submitCategory()">确定添加</button>
                            </div>
                        </div>
                   </div>
                </div>
<!-- TAB3: 门店档案 -->
                <div class="tab-section" data-tab="profile">
                    <div class="form-group"><label>门店照片</label><div id="storePhotoUploader" class="piu-wrapper"></div><input type="hidden" id="formStoreImages" value="[]"></div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>门店标签（点击选择）</label>
                            <div id="storeTagSelector" style="display:flex;flex-wrap:wrap;gap:6px;padding:8px 0;">
                                <span style="color:#8c8c8c;font-size:13px;">加载中...</span>
                            </div>
                            <div style="font-size:12px;color:#8c8c8c;margin-top:4px;">点击标签选择/取消，选中的标签将在门店信息卡显示</div>
                        </div>
                        <div class="form-group">
                            <label>营业时间</label>
                            <div style="display:flex;gap:10px;align-items:center;">
                                <div style="flex:1;">
                                    <span style="font-size:12px;color:#8c8c8c;display:block;margin-bottom:2px;">开门</span>
                                    <input type="time" id="formOpenTime" value="08:00" style="width:100%;padding:8px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                </div>
                                <span style="font-size:16px;color:#bfbfbf;margin-top:14px;">—</span>
                                <div style="flex:1;">
                                    <span style="font-size:12px;color:#8c8c8c;display:block;margin-bottom:2px;">关门</span>
                                    <input type="time" id="formCloseTime" value="22:00" style="width:100%;padding:8px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;box-sizing:border-box;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>商家经营牌照（支持多张）</label>
                            <div id="licenseImagesContainer" style="display:flex;flex-wrap:nowrap;gap:8px;margin-bottom:8px;"></div>
                            <div style="display:flex;gap:8px;">
                                <input type="file" id="licenseFileInput" accept="image/*" style="display:none" multiple onchange="handleLicenseUpload(event)">
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('licenseFileInput').click()" style="font-size:12px;padding:6px 12px;">📷 选择图片</button>
                            </div>
                            <input type="hidden" id="formLicenseImages" value="[]">
                            <div style="font-size:12px;color:#8c8c8c;margin-top:4px;">支持 JPG/PNG，单张不超过 5MB</div>
                        </div>
                        <div class="form-group">
                            <label>商家徽章（点击选择）</label>
                            <div id="storeBadgeSelector" style="display:flex;flex-wrap:wrap;gap:6px;padding:8px 0;">
                                <span style="color:#8c8c8c;font-size:13px;">加载中...</span>
                            </div>
                            <input type="hidden" id="formBadge" value="">
                            <div style="font-size:12px;color:#8c8c8c;margin-top:4px;">点击徽章选择</div>
                        </div>
                    </div>
                </div>
                <!-- TAB4: 门店订单 -->
                <div class="tab-section" data-tab="orders">
                    <div id="storeOrdersStats" style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:nowrap;">
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#f6ffed,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">总订单</div>
                            <div id="orderTotalCount" style="font-size:24px;font-weight:700;color:#52c41a;">0</div>
                        </div>
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#fff7e6,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">总金额</div>
                            <div id="orderTotalAmount" style="font-size:24px;font-weight:700;color:#fa8c16;">¥0</div>
                        </div>
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#f0f5ff,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">已成交</div>
                            <div id="orderCompletedCount" style="font-size:24px;font-weight:700;color:#1890ff;">0</div>
                        </div>
                    </div>
                    <!-- 订单筛选 Tab -->
                    <div id="ordersFilterTabs" style="margin-bottom:14px;display:flex;gap:6px;border-bottom:1px solid #f0f0f0;padding-bottom:10px;flex-wrap:nowrap;">
                        <span class="order-filter-tab active" data-filter="all" onclick="filterStoreOrders(this, 'all')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#1890ff;color:#fff;transition:all 0.2s;">全部</span>
                        <span class="order-filter-tab" data-filter="pending" onclick="filterStoreOrders(this, 'pending')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">待付款</span>
                        <span class="order-filter-tab" data-filter="paid" onclick="filterStoreOrders(this, 'paid')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">待发货</span>
                        <span class="order-filter-tab" data-filter="shipped" onclick="filterStoreOrders(this, 'shipped')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">已发货</span>
                        <span class="order-filter-tab" data-filter="completed" onclick="filterStoreOrders(this, 'completed')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">已完成</span>
                    </div>
                    <div id="storeOrdersList">
                        <div class="empty-state"><div class="icon">📦</div><div class="text">暂无门店订单</div></div>
                    </div>
                </div>
                <!-- TAB5: 门店售后 -->
                <div class="tab-section" data-tab="after">
                    <div id="storeAfterStats" style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:nowrap;">
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#fff7e6,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">总售后</div>
                            <div id="afterTotalCount" style="font-size:24px;font-weight:700;color:#fa8c16;">0</div>
                        </div>
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#e6f7ff,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">处理中</div>
                            <div id="afterProcessingCount" style="font-size:24px;font-weight:700;color:#1890ff;">0</div>
                        </div>
                        <div style="flex:1;min-width:120px;padding:12px;background:linear-gradient(135deg,#fff1f0,#fff);border-radius:12px;border:1px solid #f0f0f0;text-align:center;">
                            <div style="font-size:12px;color:#8c8c8c;">退款总额</div>
                            <div id="afterRefundTotal" style="font-size:24px;font-weight:700;color:#cf1322;">¥0</div>
                        </div>
                    </div>
                    <div id="storeAfterList">
                        <div class="empty-state"><div class="icon">🔧</div><div class="text">暂无售后记录</div></div>
                    </div>
                </div>
                <!-- TAB6: 门店评价 -->
                <div class="tab-section" data-tab="reviews">
                    <!-- 评价统计 -->
                    <div id="reviewsStats" style="margin-bottom:16px;display:flex;gap:12px;flex-wrap:nowrap;">
                        <div style="flex:1;min-width:200px;padding:16px;background:linear-gradient(135deg,#fafafa,#fff);border-radius:12px;border:1px solid #f0f0f0;">
                            <div style="font-size:13px;color:#8c8c8c;margin-bottom:8px;">综合评分</div>
                            <div style="display:flex;align-items:baseline;gap:8px;">
                                <span id="avgRatingDisplay" style="font-size:28px;font-weight:700;color:#faad14;">-</span>
                                <span id="avgRatingStars" style="font-size:16px;color:#faad14;"></span>
                                <span id="totalReviewsCount" style="font-size:13px;color:#8c8c8c;">0条评价</span>
                            </div>
                        </div>
                        <div style="flex:2;min-width:300px;padding:12px 16px;background:linear-gradient(135deg,#fafafa,#fff);border-radius:12px;border:1px solid #f0f0f0;">
                            <div id="ratingBarChart" style="display:flex;flex-direction:column;gap:4px;"></div>
                        </div>
                    </div>
                    <!-- 评价列表 -->
                    <!-- 评价筛选 Tab -->
                    <div id="reviewsFilterTabs" style="margin-bottom:14px;display:flex;gap:6px;border-bottom:1px solid #f0f0f0;padding-bottom:10px;">
                        <span class="review-filter-tab active" data-filter="all" onclick="filterReviews(this, 'all')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#1890ff;color:#fff;transition:all 0.2s;">全部</span>
                        <span class="review-filter-tab" data-filter="good" onclick="filterReviews(this, 'good')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">好评 (<span id="goodCount">0</span>)</span>
                        <span class="review-filter-tab" data-filter="normal" onclick="filterReviews(this, 'normal')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">一般 (<span id="normalCount">0</span>)</span>
                        <span class="review-filter-tab" data-filter="bad" onclick="filterReviews(this, 'bad')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">差评 (<span id="badCount">0</span>)</span>
                    </div>
                    <div id="storeCommentsList">
                        <div class="empty-state"><div class="icon">⭐</div><div class="text">暂无评价数据</div></div>
                    </div>
                </div>
                <!-- TAB7: 活动管理 -->
                <div class="tab-section" data-tab="benefits">
                    <!-- 子 Tab 切换 -->
                    <div style="display:flex;gap:6px;border-bottom:1px solid #f0f0f0;padding-bottom:10px;margin-bottom:16px;">
                        <span class="benefit-sub-tab active" data-subtab="coupon" onclick="switchBenefitTab('coupon')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#1890ff;color:#fff;transition:all 0.2s;">🎫 优惠券</span>
                        <span class="benefit-sub-tab" data-subtab="seckill" onclick="switchBenefitTab('seckill')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">⚡ 秒杀</span>
                        <span class="benefit-sub-tab" data-subtab="presale" onclick="switchBenefitTab('presale')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">📅 预售</span>
                        <span class="benefit-sub-tab" data-subtab="help" onclick="switchBenefitTab('help')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">🤝 助力</span>
                        <span class="benefit-sub-tab" data-subtab="group" onclick="switchBenefitTab('group')" style="padding:6px 16px;border-radius:20px;font-size:13px;cursor:pointer;background:#f5f5f5;color:#595959;transition:all 0.2s;">👥 拼团</span>
                    </div>

                    <!-- 子1: 优惠券 -->
                    <div class="benefit-section" data-subtab="coupon">
                        <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:15px;font-weight:600;color:#262626;">🎫 优惠券管理</div>
                            <button class="btn btn-primary" onclick="openCouponEditor()">+ 新增优惠券</button>
                        </div>
                        <div class="data-table" id="couponTable">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>名称</th>
                                        <th>类型</th>
                                        <th>价值</th>
                                        <th>最低消费</th>
                                        <th>库存</th>
                                        <th>已领/已用</th>
                                        <th>有效期</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="couponList">
                                    <tr><td colspan="10" style="text-align:center;color:#8c8c8c;padding:40px;">加载中...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="couponPagination" style="display:none;"></div>
                    </div>

                    <!-- 子2: 秒杀 -->
                    <div class="benefit-section" data-subtab="seckill" style="display:none;">
                        <div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                            <div style="font-size:15px;font-weight:600;color:#262626;">⚡ 秒杀活动</div>
                            <button class="btn btn-primary" onclick="openSeckillEditor()">+ 新增秒杀</button>
                        </div>
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>活动名称</th>
                                        <th>秒杀价</th>
                                        <th>原价</th>
                                        <th>库存/已售</th>
                                        <th>限购</th>
                                        <th>开始时间</th>
                                        <th>结束时间</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="seckillList">
                                    <tr><td colspan="10" style="text-align:center;color:#8c8c8c;padding:40px;">加载中...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="seckillPagination" style="display:none;"></div>
                    </div>

                    <!-- 子3: 预售 -->
                    <div class="benefit-section" data-subtab="presale" style="display:none;">
                        <div class="empty-state"><div class="icon">📅</div><div class="text">预售功能开发中...</div></div>
                    </div>

                    <!-- 子4: 助力 -->
                    <div class="benefit-section" data-subtab="help" style="display:none;">
                        <div class="empty-state"><div class="icon">🤝</div><div class="text">助力功能开发中...</div></div>
                    </div>

                    <!-- 子5: 拼团 -->
                    <div class="benefit-section" data-subtab="group" style="display:none;">
                        <div class="empty-state"><div class="icon">👥</div><div class="text">拼团功能开发中...</div></div>
                    </div>
                </div>
            </div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closeFormDrawer()">取消</button>
                <button class="btn btn-primary" id="formSubmitBtn" onclick="submitForm()">确定保存</button>
            </div>
        </div>
    </div>

    <!-- 商品编辑抽屉（第二层，宽度与门店抽屉一致） -->
    <div id="productEditorDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeProductEditor()"></div>
        <div class="drawer-content" style="width:1300px;">
            <div class="drawer-header">
                <h3 id="productEditorTitle">添加商品</h3>
                <div style="display:flex;gap:12px;align-items:center;">
                    <button class="btn btn-default" onclick="openProductSearch()" style="font-size:13px;">📂 从已有商品选择</button>
                    <button class="drawer-close" onclick="closeProductEditor()">x</button>
                </div>
            </div>
            <div class="drawer-tabs" id="productEditorTabs">
                <span class="drawer-tab active" data-tab="p-basic">基本信息</span>
                <span class="drawer-tab" data-tab="p-spec">规格/库存</span>
                <span class="drawer-tab" data-tab="p-detail">商品详情</span>
                <span class="drawer-tab" data-tab="p-more">更多设置</span>
            </div>
            <div class="drawer-body" style="padding:24px 32px;">
                <form id="productForm">
                    <input type="hidden" name="store_id" id="pStoreId">
                    <input type="hidden" name="product_id" id="pId">
                    <input type="hidden" name="images" id="pImages">

                    <!-- TAB: 基本信息 -->
                    <div class="tab-section active" data-tab="p-basic">
                        <div class="form-group">
                            <label>商品类型</label>
                            <select name="type" id="pType" style="width:200px;">
                                <option value="1">实物商品</option>
                                <option value="2">虚拟商品</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>商品名称 <span class="required">*</span></label>
                            <input type="text" name="name" id="pName" required placeholder="请输入商品名称">
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>商品分类</label>
                                <select name="category_id" id="pCategory">
                                    <option value="">请选择分类</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>商品编码</label>
                                <input type="text" name="product_code" id="pCode" placeholder="如：SP20260625001">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>商品图片（最少 1 张，最多 10 张）</label>
                            <div class="image-list" id="imageList"></div>
                            <div class="image-uploader" onclick="document.getElementById('imageInput').click()">
                                <div style="font-size:32px;margin-bottom:8px;">📷</div>
                                <div style="font-size:12px;font-weight:500;color:#262626;">点击上传</div>
                                <div style="font-size:10px;color:#8c8c8c;margin-top:4px;">JPG/PNG 5MB</div>
                            </div>
                            <input type="file" id="imageInput" multiple accept="image/*" style="display:none;" onchange="handleImageUpload(event)">
                            <input type="hidden" name="images" id="imagesInput" value='[]'>
                            <div class="hint" id="imageCount" style="font-size:12px;color:#8c8c8c;margin-top:8px;">已选择 0 张图片（最少 1 张，最多 10 张）</div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>运费模板</label>
                                <select name="freight_template_id" id="pFreight">
                                    <option value="0">默认运费</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>商品排序</label>
                                <input type="number" name="sort" id="pSort" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>商品状态</label>
                            <select name="status" id="pStatus" style="width:200px;">
                                <option value="1">上架</option>
                                <option value="0">下架</option>
                            </select>
                        </div>
                    </div>

                    <!-- TAB: 规格/库存 -->
                    <div class="tab-section" data-tab="p-spec">
                        <div class="form-group">
                            <label>规格类型</label>
                            <div style="display:flex;gap:20px;padding:8px 0;">
                                <label style="font-weight:400;cursor:pointer;">
                                    <input type="radio" name="spec_type" value="1" checked onchange="toggleSpecType(1)"> 单规格
                                </label>
                                <label style="font-weight:400;cursor:pointer;">
                                    <input type="radio" name="spec_type" value="2" onchange="toggleSpecType(2)"> 多规格
                                </label>
                            </div>
                        </div>
                        <div id="singleSpecBlock">
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>商品价格 <span class="required">*</span></label>
                                    <input type="number" name="price" id="pPrice" step="0.01" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label>会员价</label>
                                    <input type="number" name="member_price" id="pMemberPrice" step="0.01" value="0">
                                </div>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>成本价</label>
                                    <input type="number" name="cost_price" id="pCostPrice" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>毛利率  <span style="font-size:12px;color:#8c8c8c;" id="pMarginDisplay">-</span></label>
                                    <input type="text" id="pMarginReadonly" readonly style="background:#f5f5f5;color:#8c8c8c;" value="输入价格后自动计算">
                                </div>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>库存量</label>
                                    <input type="number" name="stock" id="pStock" value="0" min="0">
                                </div>
                                <div class="form-group">
                                    <label>商品重量（kg）</label>
                                    <input type="number" name="weight" id="pWeight" step="0.001" value="0">
                                </div>
                            </div>
                        </div>
                        <div id="multiSpecBlock" style="display:none;">
                            <!-- 多规格列表 -->
                            <div class="btn btn-primary" onclick="addSpecGroup()" style="margin-bottom:16px;display:inline-block;cursor:pointer;">+ 新增规格分类</div>
                            <div id="specGroupContainer"></div>
                            <input type="hidden" name="spec_data" id="specDataInput" value='[]'>
                        </div>
                        <div style="border-top:1px solid #f0f0f0;margin-top:16px;padding-top:16px;">
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>库存计算方式</label>
                                    <select name="stock_method" id="pStockMethod">
                                        <option value="1">下单减库存</option>
                                        <option value="2">付款减库存</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>商品限购</label>
                                    <div style="display:flex;gap:12px;align-items:center;padding:6px 0;">
                                        <label style="font-weight:400;cursor:pointer;"><input type="radio" name="limit_buy" value="0" checked onchange="toggleLimitBuy(false)"> 关闭</label>
                                        <label style="font-weight:400;cursor:pointer;"><input type="radio" name="limit_buy" value="1" onchange="toggleLimitBuy(true)"> 开启</label>
                                        <input type="number" name="limit_buy_num" id="pLimitNum" value="0" min="0" disabled style="width:80px;padding:6px 8px;border:1px solid #d9d9d9;border-radius:6px;font-size:13px;" placeholder="限购数量">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: 商品详情 -->
                    <div class="tab-section" data-tab="p-detail">
                        <div class="form-group">
                            <label>商品详情</label>
                            <textarea name="content" id="pContent" style="min-height:350px;" placeholder="请输入商品详情（支持 HTML）"></textarea>
                        </div>
                    </div>

                    <!-- TAB: 更多设置 -->
                    <div class="tab-section" data-tab="p-more">
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>主图视频</label>
                                <div class="image-uploader" id="pVideoUploader" onclick="document.getElementById('pVideoInput').click()" style="width:140px;height:140px;">
                                    <div style="font-size:32px;margin-bottom:8px;">🎬</div>
                                    <div style="font-size:11px;font-weight:500;color:#262626;">点击上传视频</div>
                                    <div style="font-size:9px;color:#8c8c8c;margin-top:4px;">16:9 8-45 秒</div>
                                </div>
                                <input type="file" id="pVideoInput" accept="video/*" style="display:none;" onchange="handlePVideoUpload(event)">
                                <div class="hint" id="pVideoPreview">建议视频宽高比 16:9，建议时长 8-45 秒</div>
                                <input type="hidden" name="video_url" id="pVideoUrl" value="">
                            </div>
                            <div class="form-group">
                                <label>视频封面图</label>
                                <div class="image-uploader" id="pCoverUploader" onclick="document.getElementById('pCoverInput').click()" style="width:140px;height:140px;">
                                    <div style="font-size:32px;margin-bottom:8px;">🖼️</div>
                                    <div style="font-size:11px;font-weight:500;color:#262626;">点击上传封面</div>
                                    <div style="font-size:9px;color:#8c8c8c;margin-top:4px;">JPG/PNG 5MB</div>
                                </div>
                                <input type="file" id="pCoverInput" accept="image/*" style="display:none;" onchange="handlePCoverUpload(event)">
                                <div class="image-list" id="pCoverList" style="margin-top:12px;"></div>
                                <input type="hidden" name="video_cover" id="pVideoCover" value="">
                            </div>
                        </div>
                        <div class="form-group"><label>商品卖点</label><textarea name="selling_points" id="pSellingPoints" placeholder="一行一个卖点" style="min-height:80px;"></textarea></div>
                        <div class="form-group"><label>服务承诺</label><textarea name="services" id="pServices" placeholder="一行一个承诺项" style="min-height:80px;"></textarea></div>
                        <div class="form-row-2">
                            <div class="form-group"><label>初始销量</label><input type="number" name="initial_sales" id="pInitialSales" value="0" min="0"></div>
                            <div class="form-group"><label>是否参与打折活动</label><select name="member_discount" id="pMemberDiscount"><option value="0">不参与</option><option value="1">参与</option></select></div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group"><label>积分赠送设置</label><input type="number" name="points_gift" id="pPointsGift" value="0" min="0" placeholder="赠送积分数量"></div>
                            <div class="form-group"><label>积分抵扣</label><select name="points_deduct" id="pPointsDeduct"><option value="0">关闭</option><option value="1">开启</option></select></div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group"><label>积分抵扣设置</label><select name="points_deduct_type" id="pPointsDeductType"><option value="">无</option><option value="fixed">固定抵扣</option><option value="percent">按比例抵扣</option></select></div>
                            <div class="form-group"><label>分销佣金</label><input type="text" name="commission_type" id="pCommission" placeholder="如：10% 或具体金额"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closeProductEditor()">取消</button>
                <button class="btn btn-primary" id="productSubmitBtn" onclick="submitProductForm()">确定保存</button>
            </div>
        </div>
    </div>

    <!-- 已有商品搜索弹窗 -->
    <div id="productSearchModal" class="modal" style="z-index:2500;">
        <div class="modal-content" style="max-width:700px;">
            <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                <span>选择已有商品</span>
                <button class="drawer-close" onclick="closeProductSearch()" style="width:28px;height:28px;border:none;background:transparent;font-size:20px;color:#8c8c8c;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display:flex;gap:12px;margin-bottom:16px;">
                    <input type="text" id="productSearchInput" placeholder="搜索商品名称" style="flex:1;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;">
                    <button class="btn btn-primary" onclick="searchProducts()">🔍 搜索</button>
                </div>
                <div class="data-table" id="productSearchResults">
                    <div class="empty-state"><div class="icon">🔍</div><div class="text">输入关键词搜索已有商品</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 地图选择模态框 -->
    <div id="mapModal" class="modal" style="z-index:3000;">
        <div class="modal-content" style="max-width:800px;padding:0;overflow:hidden;">
            <div style="padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;color:#262626;">🗺️ 地图选点</h3>
                <button class="drawer-close" onclick="closeMapPicker()" style="width:28px;height:28px;border:none;background:transparent;font-size:20px;color:#8c8c8c;cursor:pointer;border-radius:4px;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <div style="padding:16px 24px 12px;display:flex;gap:8px;">
                <input type="text" id="mapSearchInput" placeholder="输入地址搜索" style="flex:1;padding:8px 12px;border:1px solid #d9d9d9;border-radius:6px;font-size:14px;">
                <button class="btn btn-primary" onclick="searchAddress()" style="white-space:nowrap;">🔍 搜索</button>
            </div>
            <div id="mapContainer" style="height:400px;margin:0 24px 12px;border-radius:8px;border:1px solid #e8e8e8;"></div>
            <div style="padding:12px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-size:13px;color:#8c8c8c;">
                    点击地图选择地点，或搜索地址后点击结果
                    <span id="mapCoordDisplay" style="display:none;margin-left:12px;color:#262626;font-weight:600;"></span>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-default" onclick="closeMapPicker()">取消</button>
                    <button class="btn btn-primary" onclick="confirmMapPick()">✅ 确认选择</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 详情抽屉 -->
    <div id="detailDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeDetailDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3>门店详情</h3>
                <button class="drawer-close" onclick="closeDetailDrawer()">x</button>
            </div>
            <div class="drawer-body" id="detailBody"></div>
            <div class="drawer-footer">
                <button class="btn btn-default" onclick="closeDetailDrawer()">关闭</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="../assets/css/table-component.css?v=<?= time() ?>">
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script src="../assets/js/table-component.min.js?v=<?= time() ?>"></script>
    <script>
    // ========== 高德地图配置 ==========
    // 请替换为你的高德地图 Key：https://console.amap.com/dev/key/app
    var AMAP_KEY = '5f3decaa001f5009142ed8e29f8e6699';
    window._AMapSecurityConfig = { securityJsCode: '4062a22b990bc5a8df8bad95761bce4e' };
    (function() {
        var s = document.createElement('script');
        s.src = 'https://webapi.amap.com/maps?v=2.0&key=' + AMAP_KEY + '&plugin=AMap.Geocoder,AMap.PlaceSearch';
        s.async = true;
        document.head.appendChild(s);
    })();
    </script>
    <script>
    let currentPage = 1;
    let totalPage = 1;
    let pageSize = 20;
    let editingId = 0;

    // 加载列表
    function changePageSize(newSize) {
        pageSize = parseInt(newSize);
        loadList(1);
    }

    function loadList(page) {
        currentPage = page || 1;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', currentPage);
        params.append('pageSize', pageSize);
        var kwEl = document.getElementById('searchKeyword');
        params.append('keyword', kwEl ? kwEl.value.trim() : '');
        var lvEl = document.getElementById('searchLevel');
        params.append('level', lvEl ? lvEl.value : '');
        var stEl = document.getElementById('searchStar');
        params.append('star', stEl ? stEl.value : '');
        params.append('status', document.getElementById('searchStatus').value);

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(function(res) {
            if (res.redirected) {
                showMessage('⏱️ 登录已过期，请刷新页面重新登录', 'error');
                return null;
            }
            if (!res.ok) {
                showMessage('❌ 服务器错误: ' + res.status, 'error');
                return null;
            }
            return res.json();
        })
        .then(function(data) {
            if (!data) return;
            if (data.success) {
                renderTable(data.data);
                renderPagination(data.total, data.page, data.pageSize);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(function(err) {
            showMessage('❌ 加载失败：' + err.message, 'error');
        });
    }

    // 渲染表格
    function renderTable(rows) {
        const container = document.getElementById('tableContainer');
        if (!rows || rows.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="icon">🏪</div>
                    <div class="text">暂无门店数据，点击"新增门店"开始添加</div>
                </div>`;
            return;
        }
        let html = `
            <table>
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                        <th width="70">ID</th>
                        <th>门店信息</th>
                        <th width="130">等级</th>
                        <th width="130">联系电话</th>
                        <th width="180">位置</th>
                        <th width="90">数据</th>
                        <th width="80">状态</th>
                        <th width="80">排序</th>
                        <th width="180">操作</th>
                    </tr>
                </thead>
                <tbody>`;
        rows.forEach(r => {

            const statusBadge = r.status == 1
                ? '<span class="badge badge-success">营业中</span>'
                : '<span class="badge badge-danger">已停业</span>';
            html += `
                <tr data-id="${r.id}">
                    <td><input type="checkbox" class="checkbox item-checkbox" value="${r.id}"></td>
                    <td>#${r.id}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:6px;overflow:hidden;flex-shrink:0;background:#f5f5f5;border:1px solid #f0f0f0;">
                                ${r.avatar ? 
                                    `<img src="${escapeHtml(r.avatar)}" alt="" style="width:100%;height:100%;object-fit:cover;">` : 
                                    '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px;">🏪</div>'
                                }
                            </div>
                            <div>
                                <div class="cell-name">${escapeHtml(r.name)}</div>
                                <div class="cell-loc">${escapeHtml(r.country)} · ${escapeHtml(r.city)}</div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;" onclick="showLevelDetailById(${r.id})" title="点击查看评分细则">
                        ${r.level_score 
                            ? `<span style="font-size:12px;font-weight:600;cursor:pointer;">${escapeHtml(r.level || '-')}</span><br><span style="display:inline-block;padding:1px 6px;border-radius:3px;font-size:11px;margin-top:2px;background:${r.level_score >= 90 ? '#f6ffed' : (r.level_score >= 75 ? '#e6f7ff' : (r.level_score >= 60 ? '#fffbe6' : '#fff2f0'))};color:${r.level_score >= 90 ? '#237804' : (r.level_score >= 75 ? '#096dd9' : (r.level_score >= 60 ? '#ad8b00' : '#cf1322'))};">${r.level_score}分</span>`
                            : '<span style="color:#d9d9d9;font-size:12px;cursor:pointer;">未评定</span>'
                        }
                    </td>
                    <td>${escapeHtml(r.phone || '-')}</td>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${escapeHtml(r.address || '-')}">
                        <div class="cell-loc">${escapeHtml(r.address || '-')}</div>
                    </td>
                    <td>
                        <div class="stat-line">👥 <strong>${r.staff_count}</strong> 店员</div>
                        <div class="stat-line">✅ <strong>${r.verify_count}</strong> 核销</div>
                    </td>
                    <td>
                        <div class="toggle-switch ${r.status == 1 ? 'on' : ''}"
                             onclick="toggleStatus(${r.id}, ${r.status == 1 ? 0 : 1})"
                             title="点击切换"></div>
                    </td>
                    <td>
                        <input type="number" class="sort-input" value="${r.sort || 0}"
                               onchange="updateSort(${r.id}, this.value)">
                    </td>
                    <td>
                        <button class="action-btn view" onclick="viewDetail(${r.id})">详情</button>
                        <button class="action-btn edit" onclick="openEditDrawer(${r.id})">编辑</button>
                        <button class="action-btn delete" onclick="deleteItem(${r.id})">删除</button>
                    </td>
                </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
        // 初始化表格组件（列宽拖动等）
        if (typeof TableComponent !== 'undefined') {
            var tbl = container.querySelector('table');
            if (tbl) TableComponent.init(tbl);
        }
    }

    // 渲染分页
    function renderPagination(total, page, pageSize) {
        const container = document.getElementById('pagination');
        totalPage = Math.max(1, Math.ceil(total / pageSize));

        var html = '';

        // Info: 显示 1-20 条  共 22 条
        var fromItem = (page - 1) * pageSize + 1;
        var toItem = Math.min(page * pageSize, total);
        html += '<span class="pag-info">显示 ' + fromItem + '-' + toItem + ' 条</span>';
        html += '<span class="pag-sep">|</span>';
        html += '<span class="pag-info">共 ' + total + ' 条</span>';
        html += '<span class="pag-sep">|</span>';

        // Page size select: 每页 [10 条]
        html += '<span class="pag-info">每页</span>';
        html += '<select onchange="changePageSize(this.value)" class="pag-page-size">';
        [10, 50, 100].forEach(function(s) {
            html += '<option value="' + s + '"' + (s === pageSize ? ' selected' : '') + '>' + s + ' 条</option>';
        });
        html += '</select>';

        // Navigation
        if (totalPage > 1) {
            html += '<span class="pag-sep">|</span>';

            if (page > 1)
                html += '<a href="javascript:;" onclick="loadList(' + (page - 1) + ')" class="pag-btn">上一页</a>';
            else
                html += '<span class="pag-btn disabled">上一页</span>';

            var start = Math.max(1, page - 2);
            var end = Math.min(totalPage, page + 2);
            if (start > 1) html += '<a href="javascript:;" onclick="loadList(1)" class="pag-btn">1</a>';
            if (start > 2) html += '<span class="pag-btn disabled">\u2026</span>';
            for (var i = start; i <= end; i++) {
                if (i === page) html += '<a class="pag-btn active">' + i + '</a>';
                else html += '<a href="javascript:;" onclick="loadList(' + i + ')" class="pag-btn">' + i + '</a>';
            }
            if (end < totalPage - 1) html += '<span class="pag-btn disabled">\u2026</span>';
            if (end < totalPage) html += '<a href="javascript:;" onclick="loadList(' + totalPage + ')" class="pag-btn">' + totalPage + '</a>';

            if (page < totalPage)
                html += '<a href="javascript:;" onclick="loadList(' + (page + 1) + ')" class="pag-btn">下一页</a>';
            else
                html += '<span class="pag-btn disabled">下一页</span>';
        }

        container.innerHTML = html;
    }    function resetSearch() {
        document.getElementById('searchKeyword').value = '';
        var lvEl = document.getElementById('searchLevel'); if (lvEl) lvEl.value = '';
        var stEl = document.getElementById('searchStar'); if (stEl) stEl.value = '';
        document.getElementById('searchStatus').value = '';
        loadList(1);
    }



    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
        updateBatchToolbar();
    }

    function updateBatchToolbar() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        const toolbar = document.getElementById('batchToolbar');
        document.getElementById('selectedCount').textContent = checked.length;
        toolbar.classList.toggle('show', checked.length > 0);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => parseInt(cb.value));
    }

    function toggleStatus(id, status) {
        const action = status ? '启用' : '停业';
        if (!confirm('确定要' + action + '该门店吗？')) return;
        postAction('toggle_status', {id, status}, '状态更新成功');
    }

    function updateSort(id, sort) {
        postAction('update_sort', {id, sort}, '排序更新成功');
    }

    function deleteItem(id) {
        if (!confirm('确定要删除该门店吗？删除后不可恢复！')) return;
        postAction('delete', {id}, '门店删除成功', () => loadList(currentPage));
    }

    function batchDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择门店', 'error');
        if (!confirm(`确定要删除选中的 ${ids.length} 个门店吗？`)) return;
        postAction('batch_delete', {ids}, '批量删除成功', () => loadList(1));
    }

    function batchCalcLevel() {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择门店', 'error');
        if (!confirm(`确定评定选中的 ${ids.length} 个门店等级？`)) return;
        showLoading('批量评定中...');
        const params = new URLSearchParams();
        params.append('action', 'batch_calc_level');
        ids.forEach(id => params.append('ids[]', id));
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    loadList(currentPage);
                } else {
                    showMessage('❌ ' + (data.message || '操作失败'), 'error');
                }
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    function batchSetStatus(status) {
        const ids = getSelectedIds();
        if (ids.length === 0) return showMessage('请先选择门店', 'error');
        const action = status ? '启用' : '停业';
        if (!confirm(`确定要${action}选中的 ${ids.length} 个门店吗？`)) return;
        postAction('batch_status', {ids, status}, '批量操作成功', () => loadList(currentPage));
    }

    function postAction(action, extra, successMsg, callback) {
        const params = new URLSearchParams();
        params.append('action', action);
        Object.keys(extra).forEach(k => {
            const v = extra[k];
            if (Array.isArray(v)) v.forEach(x => params.append(k + '[]', x));
            else params.append(k, v);
        });
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + (data.message || successMsg), 'success');
                if (callback) callback();
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'));
    }

    function resetReadonlyInfo() {
        document.getElementById('infoStarRating').textContent = '暂无评分';
        document.getElementById('infoMonthlySales').textContent = '0';
        document.getElementById('infoCreatedAt').textContent = '';
        document.getElementById('infoLevel').innerHTML = '-';
        document.getElementById('infoBadge').textContent = '-';
        document.getElementById('infoCouponCount').textContent = '0';
        document.getElementById('infoBusinessHours').textContent = '-';
        document.getElementById('infoTags').textContent = '暂无标签';
    }

    function fillReadonlyInfo(r) {
        // 星级评分（可点击查看细则）
        const rating = parseFloat(r.star_rating) || 0;
        const starEl = document.getElementById('infoStarRating');
        if (rating > 0) {
            const full = Math.floor(rating);
            const half = rating - full >= 0.5;
            const stars = '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(Math.max(0, 5 - full - (half ? 1 : 0)));
            starEl.innerHTML = `<span style="color:#faad14;cursor:pointer;" onclick="showStarDetail()">${stars}</span> <span style="color:#1890ff;font-size:12px;cursor:pointer;text-decoration:underline;" onclick="showStarDetail()">${rating.toFixed(1)}</span>`;
        } else {
            starEl.innerHTML = '<span style="cursor:pointer;color:#8c8c8c;" onclick="showStarDetail()">暂无评分</span>';
        }
        // 入驻时间
        var createdEl = document.getElementById('infoCreatedAt');
        if (r.created_at) {
            createdEl.textContent = '🕐 入驻时间：' + new Date(r.created_at).toLocaleDateString('zh-CN');
        } else {
            createdEl.textContent = '';
        }
        // 月售
        document.getElementById('infoMonthlySales').textContent = r.monthly_sales || '0';
        // 配送时间
        document.getElementById('infoDeliveryTime').textContent = r.delivery_time ? r.delivery_time + ' 分钟' : '--';
        // 等级（可点击查看细则）
        const levelEl = document.getElementById('infoLevel');
        if (r.level && r.level_score) {
            levelEl.innerHTML = `<span style="cursor:pointer;color:#1890ff;text-decoration:underline;" onclick="showLevelDetail()">${escapeHtml(r.level)}</span> <span style="font-size:12px;color:#8c8c8c;">(${r.level_score}分)</span>`;
        } else {
            levelEl.innerHTML = '<span style="cursor:pointer;color:#8c8c8c;" onclick="showLevelDetail()">未评定</span>';
        }
        // 徽章（多选）
        const badgeEl = document.getElementById('infoBadge');
        if (r.badge) {
            var badges = [];
            try { badges = JSON.parse(r.badge); } catch(e) { badges = r.badge ? [r.badge] : []; }
            if (badges.length > 0) {
                badgeEl.innerHTML = badges.map(function(b) {
                    // 从 store_badges 找图标，简单处理
                    return '<span style="display:inline-block;padding:2px 8px;background:#f6ffed;color:#237804;border-radius:4px;font-size:12px;margin-right:4px;">' + escapeHtml(b) + '</span>';
                }).join('');
            } else {
                badgeEl.textContent = '-';
            }
        } else {
            badgeEl.textContent = '-';
        }
        // 优惠券
        document.getElementById('infoCouponCount').textContent = r.coupon_count || '0';
        // 营业时间
        document.getElementById('infoBusinessHours').textContent = r.business_hours || '-';
        // 标签
        const tagsEl = document.getElementById('infoTags');
        if (r.tags) {
            let tags = [];
            try { tags = JSON.parse(r.tags); } catch(e) { tags = r.tags.split(',').map(t=>t.trim()).filter(t=>t); }
            if (tags.length > 0) {
                tagsEl.innerHTML = tags.map(t => `<span style="display:inline-block;padding:2px 8px;background:#e6f7ff;color:#1890ff;border-radius:4px;font-size:12px;margin-right:4px;">${escapeHtml(t)}</span>`).join('');
            } else {
                tagsEl.textContent = '暂无标签';
            }
        } else {
            tagsEl.textContent = '暂无标签';
        }
    }

    /* ===== 头像本地预览 & 保存时上传 ===== */
    let pendingAvatarFile = null;        // 待上传的文件对象
    let oldAvatarUrl = '';               // 旧头像 URL（编辑时记录，用于保存时删除旧文件）

    function extractOssKey(avatarUrl) {
        // 从 OSS URL 中提取文件 key
        // http://bucket.endpoint/shopauba/products/xxx.png → shopauba/products/xxx.png
        if (!avatarUrl) return '';
        try {
            const u = new URL(avatarUrl);
            return u.pathname.replace(/^\//, '');
        } catch(e) {
            return avatarUrl;
        }
    }

    function deleteOssFile(fileKey) {
        return new Promise((resolve, reject) => {
            if (!fileKey) return resolve();
            const fd = new FormData();
            fd.append('file_key', fileKey);
            fetch('../api/file_api.php?action=delete_file', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(r => { console.log('[删除旧头像]', r); resolve(r); })
                .catch(err => { console.error('[删除旧头像失败]', err); resolve(); });
        });
    }

    /* ===== 关停管理 ===== */
    function loadSuspendInfo(r) {
        var isSuspended = parseInt(r.suspended || 0) === 1;
        document.getElementById('formSuspended').value = r.suspended || '0';

        // 违规记录提示
        var historyEl = document.getElementById('suspendHistoryHint');
        // 有关停记录或历史，都显示违规提示（恢复后也保留）
        if (r.suspension_history || (r.suspended == 1)) {
            historyEl.style.display = 'inline';
        } else {
            historyEl.style.display = 'none';
        }

        if (isSuspended) {
            document.getElementById('storeStatusDisplay').textContent = '已关停';
            document.getElementById('storeStatusDisplay').style.cssText = 'display:inline-block;padding:3px 12px;border-radius:4px;font-size:13px;font-weight:600;background:#fff2f0;color:#cf1322;';
            document.getElementById('btnSuspendStore').style.display = 'none';
            document.getElementById('btnRestoreStore').style.display = 'inline-block';
            document.getElementById('suspendFormArea').style.display = 'none';
            document.getElementById('suspendedInfoArea').style.display = 'block';
            document.getElementById('formStatus').value = '0';

            document.getElementById('displaySuspendReason').textContent = r.suspended_reason || '-';
            document.getElementById('displaySuspendAt').textContent = r.suspended_at || '-';
            if (r.suspended_until) {
                document.getElementById('displaySuspendUntil').textContent = r.suspended_until;
                document.getElementById('storeSuspendInfo').textContent = '｜自动恢复时间：' + r.suspended_until;
            } else {
                document.getElementById('displaySuspendUntil').textContent = '永久';
                document.getElementById('storeSuspendInfo').textContent = '｜永久关停';
            }
        } else {
            document.getElementById('storeStatusDisplay').textContent = '营业中';
            document.getElementById('storeStatusDisplay').style.cssText = 'display:inline-block;padding:3px 12px;border-radius:4px;font-size:13px;font-weight:600;background:#f6ffed;color:#237804;';
            document.getElementById('btnSuspendStore').style.display = 'inline-block';
            document.getElementById('btnRestoreStore').style.display = 'none';
            document.getElementById('suspendFormArea').style.display = 'none';
            document.getElementById('suspendedInfoArea').style.display = 'none';
            document.getElementById('storeSuspendInfo').textContent = '';
        }
    }

    function showSuspendForm() {
        document.getElementById('suspendFormArea').style.display = 'block';
        document.getElementById('btnSuspendStore').textContent = '🚫 确认关停';
        document.getElementById('btnSuspendStore').onclick = confirmSuspend;
    }

    function confirmSuspend() {
        var reason = document.getElementById('formSuspendReason').value.trim();
        if (!reason) { showMessage('请填写关停理由', 'error'); return; }
        if (!confirm('确定关停该门店？关停后门店将对外不可见。')) return;

        var suspendType = document.getElementById('formSuspendType').value;
        var untilDate = '';

        if (suspendType === 'permanent') {
            untilDate = '';
        } else if (suspendType === 'temporary') {
            untilDate = document.getElementById('formSuspendUntil').value;
            if (!untilDate) { showMessage('请选择关停截止时间', 'error'); return; }
        } else {
            var days = parseInt(suspendType.replace('days', ''));
            var d = new Date();
            d.setDate(d.getDate() + days);
            untilDate = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' 23:59:59';
        }

        var storeId = editingId;
        if (!storeId) { showMessage('请先打开门店', 'error'); return; }

        var params = new URLSearchParams();
        params.append('action', 'suspend_store');
        params.append('suspend_action', 'suspend');
        params.append('id', storeId);
        params.append('reason', reason);
        params.append('until', untilDate);

        showLoading('关停中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                hideLoading();
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    document.getElementById('formSuspended').value = '1';
                    document.getElementById('formStatus').value = '0';
                    document.getElementById('formSuspendReason').value = reason;
                    document.getElementById('formSuspendUntilHidden').value = untilDate;
                    document.getElementById('btnSuspendStore').textContent = '🚫 关停门店';
                    document.getElementById('btnSuspendStore').onclick = showSuspendForm;
                    document.getElementById('suspendFormArea').style.display = 'none';
                    // 刷新关停显示
                    openEditDrawer(storeId);
                } else {
                    showMessage('❌ ' + (data.message || '关停失败'), 'error');
                }
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    function showSuspendHistory() {
        var storeId = editingId;
        if (!storeId) return;
        var params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', storeId);
        showLoading('加载中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                hideLoading();
                if (!data.success) { showMessage('加载失败', 'error'); return; }
                var r = data.data;
                var html = '<div style="padding:10px 0;"><div style="font-size:16px;font-weight:600;color:#262626;margin-bottom:16px;">📋 违规处罚记录</div>';

                // 当前关停信息
                if (r.suspended == 1) {
                    html += '<div style="padding:12px;background:#fff2f0;border-radius:8px;margin-bottom:12px;">';
                    html += '<div style="font-size:13px;color:#cf1322;font-weight:600;margin-bottom:4px;">🔴 当前处于关停状态</div>';
                    html += '<div style="font-size:12px;color:#595959;line-height:1.8;">';
                    html += '理由：' + escapeHtml(r.suspended_reason || '-') + '<br>';
                    html += '关停时间：' + (r.suspended_at || '-');
                    if (r.suspended_until) html += '<br>截止时间：' + r.suspended_until;
                    html += '</div></div>';
                }

                // 历史记录
                var history = [];
                try { history = JSON.parse(r.suspension_history || '[]'); } catch(e) {}
                if (history.length > 0) {
                    html += '<div style="font-size:13px;font-weight:600;color:#262626;margin-bottom:8px;">📜 历史违规记录（' + history.length + '条）</div>';
                    history.forEach(function(h) {
                        html += '<div style="padding:10px;background:#fafafa;border-radius:8px;margin-bottom:8px;border-left:3px solid #ff4d4f;">';
                        html += '<div style="font-size:12px;color:#595959;line-height:1.8;">';
                        html += '<strong>关停时间：</strong>' + escapeHtml(h.suspended_at || '-') + '<br>';
                        if (h.restored_at) html += '<strong>恢复时间：</strong>' + escapeHtml(h.restored_at) + '<br>';
                        html += '<strong>关停理由：</strong>' + escapeHtml(h.reason || '-') + '<br>';
                        if (h.operator) html += '<strong>操作人：</strong>' + escapeHtml(h.operator);
                        html += '</div></div>';
                    });
                } else {
                    html += '<div style="color:#8c8c8c;font-size:13px;padding:20px;text-align:center;">暂无历史违规记录</div>';
                }
                html += '</div>';

                var overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
                overlay.onclick = function(e) { if (e.target === overlay) overlay.remove(); };
                overlay.className = 'modal-overlay';
                overlay.innerHTML = '<div style="background:white;border-radius:16px;padding:28px;width:520px;max-width:90vw;max-height:85vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.15);position:relative;">' +
                    '<div onclick="this.closest(\'.modal-overlay\').remove()" style="position:absolute;top:12px;right:16px;font-size:24px;cursor:pointer;color:#8c8c8c;line-height:1;">×</div>' +
                    html + '</div>';
                document.body.appendChild(overlay);
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    function restoreStore() {
        if (!confirm('确定恢复该门店营业？')) return;
        var storeId = editingId;
        if (!storeId) { showMessage('请先打开门店', 'error'); return; }

        var params = new URLSearchParams();
        params.append('action', 'suspend_store');
        params.append('suspend_action', 'restore');
        params.append('id', storeId);

        showLoading('恢复中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                hideLoading();
                if (data.success) {
                    showMessage('✅ ' + data.message, 'success');
                    openEditDrawer(storeId);
                } else {
                    showMessage('❌ ' + (data.message || '恢复失败'), 'error');
                }
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }
    /* ===== ====== ===== */

    function uploadAvatarFile(file) {
        return new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('image', file);
            fetch('../product/product_upload.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(r => {
                    if (!r.success) { reject(new Error(r.message || '上传失败')); return; }
                    const url = r.preview_url || r.url || r.file_key;
                    resolve(url);
                })
                .catch(err => reject(err));
        });
    }

    function viewAvatar() {
        const url = document.getElementById('formAvatar').value;
        console.log('[查看头像] formAvatar 值:', url);
        if (!url) {
            showMessage('暂无头像可查看', 'info');
            return;
        }
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:pointer;';
        overlay.onclick = () => overlay.remove();
        overlay.innerHTML = `<img src="${url}" style="max-width:80vw;max-height:80vh;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,0.5);">`;
        document.body.appendChild(overlay);
    }

    function handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        const validTypes = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!validTypes.includes(file.type)) { showMessage('只支持 JPG/PNG/GIF/WebP 格式', 'error'); return; }
        if (file.size > 5 * 1024 * 1024) { showMessage('图片大小不能超过 5MB', 'error'); return; }

        // 本地预览（base64），不上传到 OSS
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('formAvatarPreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);

        // 暂存文件，保存时统一上传
        pendingAvatarFile = file;
        event.target.value = '';
        showMessage('✅ 已选择新头像，保存时自动上传', 'success');
    }

    function removeAvatar() {
        if (!confirm('确定要删除门店头像吗？此操作将在保存后生效。')) return;
        const preview = document.getElementById('formAvatarPreview');
        preview.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;">🏪</div>';
        pendingAvatarFile = null;
        document.getElementById('formAvatar').value = '';
        showMessage('头像已移除，保存后生效', 'info');
    }

    async function handleAvatarOnSave() {
        const curAvatar = document.getElementById('formAvatar').value;
        
        // 有旧头像需要删除？
        if (editingId && oldAvatarUrl && oldAvatarUrl !== curAvatar) {
            const oldKey = extractOssKey(oldAvatarUrl);
            if (oldKey) await deleteOssFile(oldKey);
        }

        // 有新文件待上传？
        if (pendingAvatarFile) {
            showLoading('上传头像中...');
            try {
                const url = await uploadAvatarFile(pendingAvatarFile);
                document.getElementById('formAvatar').value = url;
                pendingAvatarFile = null;
                hideLoading();
            } catch(err) {
                hideLoading();
                throw err;
            }
        }
    }
    /* ===== ====== ===== */

    function openAddDrawer() {
        editingId = 0;
        document.getElementById('formTitle').textContent = '新增门店';
        document.getElementById('storeForm').reset();
        document.getElementById('formId').value = '';
        document.getElementById('formStatus').value = '1';
        document.getElementById('formSort').value = '0';
        document.getElementById('formSuspended').value = '0';
        document.getElementById('formSuspendReason').value = '';
        document.getElementById('suspendFormArea').style.display = 'none';
        document.getElementById('suspendedInfoArea').style.display = 'none';
        document.getElementById('btnSuspendStore').style.display = 'inline-block';
        document.getElementById('btnRestoreStore').style.display = 'none';
        document.getElementById('storeStatusDisplay').textContent = '营业中';
        document.getElementById('storeStatusDisplay').style.cssText = 'display:inline-block;padding:3px 12px;border-radius:4px;font-size:13px;font-weight:600;background:#f6ffed;color:#237804;';
        document.getElementById('storeSuspendInfo').textContent = '';
        document.getElementById('formOpenTime').value = '08:00';
        document.getElementById('formCloseTime').value = '22:00';
        // 重置标签
        document.getElementById('storeTagSelector').innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载中...</span>';
        document.getElementById('storeBadgeSelector').innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载中...</span>';
        document.getElementById('formBadge').value = '';
        licenseImages = [];
        pendingLicenseFiles = [];
        renderLicenseImages();
        // 重置门店照片
        storeImages = [];
        pendingStoreImages = [];
        initStorePhotoUploader();
        document.getElementById('formStoreImages').value = '[]';
        // 重置头像预览
        document.getElementById('formAvatarPreview').innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;">🏪</div>';
        document.getElementById('formAvatar').value = '';
        pendingAvatarFile = null;
        oldAvatarUrl = '';
        // 重置只读信息
        resetReadonlyInfo();
        document.getElementById('formDrawer').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function openEditDrawer(id) {
        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', id);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const r = data.data;
                editingId = r.id;
                document.getElementById('formTitle').textContent = '编辑门店 #' + r.id;
                document.getElementById('formId').value = r.id;
                document.getElementById('formName').value = r.name || '';
                // 加载头像
                const avatarPreview = document.getElementById('formAvatarPreview');
                console.log('[加载门店] avatar 原始值:', r.avatar);
                // 解析 avatar URL（可能是 file_key 相对路径，也可能是完整 URL）
                let avatarUrl = r.avatar || '';
                if (avatarUrl && !avatarUrl.startsWith('http') && !avatarUrl.startsWith('/')) {
                    // 相对路径 file_key，加上域名前缀才能访问
                    avatarUrl = window.location.protocol + '//' + window.location.host + '/uploads/' + avatarUrl;
                    if (!r.avatar.startsWith('uploads/') && !r.avatar.startsWith('products/')) {
                        avatarUrl = r.avatar; // 无法判断，保持原样
                    }
                }
                if (avatarUrl) {
                    avatarPreview.innerHTML = `<img src="${avatarUrl}" alt="" style="width:100%;height:100%;object-fit:cover;">`;
                    const avtImg = avatarPreview.querySelector('img');
                    if (avtImg) avtImg.onerror = function(){ avatarPreview.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;">🏪</div>'; };
                    document.getElementById('formAvatar').value = r.avatar;
                    oldAvatarUrl = r.avatar;
                } else {
                    avatarPreview.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;">🏪</div>';
                    document.getElementById('formAvatar').value = '';
                }
                document.getElementById('formAddress').value = r.address || '';
                document.getElementById('formCountry').value = r.country || '中国';
                document.getElementById('formCity').value = r.city || '';
                document.getElementById('formPhone').value = r.phone || '';
                document.getElementById('formPhone2').value = r.phone2 || '';
                document.getElementById('formLat').value = r.latitude || '';
                document.getElementById('formLng').value = r.longitude || '';
                document.getElementById('formDesc').value = r.description || '';
                document.getElementById('formStatus').value = r.status;
                document.getElementById('formSort').value = r.sort || 0;
                // 关停信息
                loadSuspendInfo(r);
                // 营业时间
                var times = (r.business_hours || '').split('-');
                document.getElementById('formOpenTime').value = times[0] ? times[0].trim() : '08:00';
                document.getElementById('formCloseTime').value = times[1] ? times[1].trim() : '22:00';
                // 填充只读信息
                fillReadonlyInfo(r);
                document.getElementById('formDrawer').classList.add('show');
                document.body.style.overflow = 'hidden';
                loadStoreProducts(r.id);
                loadStoreCategories(r.id);
                loadStoreOrders(r.id);
                loadStoreAfter(r.id);
                loadStoreComments(r.id);
                loadStoreTags(r.id);
                loadStoreBadges(r.id);
                // 经营牌照图片
                pendingLicenseFiles = [];
                if (r.license_images) {
                    try { licenseImages = JSON.parse(r.license_images); } catch(e) { licenseImages = []; }
                } else { licenseImages = []; }
                renderLicenseImages();
                // 加载门店照片
                initStorePhotoUploader();
                storeImages = [];
                if (r.images) {
                    try { storeImages = JSON.parse(r.images); } catch(e) { storeImages = []; }
                }
                if (!Array.isArray(storeImages)) storeImages = [];
                document.getElementById('formStoreImages').value = JSON.stringify(storeImages);
                renderStoreImages();
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    var selectedTags = [];

    function loadStoreTags(storeId) {
        const container = document.getElementById('storeTagSelector');
        if (!container) return;
        container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载中...</span>';

        // 先获取门店已选标签
        const detailParams = new URLSearchParams();
        detailParams.append('action', 'detail');
        detailParams.append('id', storeId);

        Promise.all([
            // 加载所有可用标签
            new Promise(function(resolve) {
                const p = new URLSearchParams();
                p.append('action', 'store_tags_list');
                fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p.toString() })
                    .then(r => r.json()).then(resolve).catch(function() { resolve({success: false}); });
            }),
            // 获取门店当前标签
            new Promise(function(resolve) {
                fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: detailParams.toString() })
                    .then(r => r.json()).then(resolve).catch(function() { resolve({success: false}); });
            })
        ]).then(function(results) {
            const tagData = results[0];
            const detailData = results[1];
            if (!tagData.success) { container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载失败</span>'; return; }

            // 解析当前门店标签
            selectedTags = [];
            if (detailData.success && detailData.data && detailData.data.tags) {
                try {
                    const t = JSON.parse(detailData.data.tags);
                    if (Array.isArray(t)) selectedTags = t;
                } catch(e) {
                    selectedTags = detailData.data.tags.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s; });
                }
            }

            renderTagSelector(tagData.data || []);
        });
    }

    function renderTagSelector(allTags) {
        const container = document.getElementById('storeTagSelector');
        if (!container) return;
        if (!allTags.length) { container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">暂无可用标签</span>'; return; }

        var html = '';
        allTags.forEach(function(tag) {
            var checked = selectedTags.indexOf(tag.name) !== -1;
            html += '<span onclick="toggleStoreTag(\'' + tag.name.replace(/'/g, "\\'") + '\')" style="display:inline-block;padding:5px 12px;border-radius:6px;font-size:13px;cursor:pointer;transition:all 0.2s;user-select:none;' +
                (checked
                    ? 'background:#1890ff;color:white;border:1px solid #1890ff;'
                    : 'background:white;color:#595959;border:1px solid #d9d9d9;') +
                '"' + (checked ? ' data-selected="1"' : '') + '>' + escapeHtml(tag.name) + '</span>';
        });
        container.innerHTML = html;
    }

    function toggleStoreTag(tagName) {
        var idx = selectedTags.indexOf(tagName);
        if (idx === -1) {
            selectedTags.push(tagName);
        } else {
            selectedTags.splice(idx, 1);
        }
        // 重新渲染所有标签
        const params = new URLSearchParams();
        params.append('action', 'store_tags_list');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) renderTagSelector(data.data || []);
            });
    }

    /* ===== 徽章选择（多选，同标签逻辑） ===== */
    var selectedBadges = [];

    function loadStoreBadges(storeId) {
        const container = document.getElementById('storeBadgeSelector');
        if (!container) return;
        container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载中...</span>';

        const detailParams = new URLSearchParams();
        detailParams.append('action', 'detail');
        detailParams.append('id', storeId);

        Promise.all([
            new Promise(function(resolve) {
                const p = new URLSearchParams();
                p.append('action', 'store_badges_list');
                fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p.toString() })
                    .then(r => r.json()).then(resolve).catch(function() { resolve({success:false}); });
            }),
            new Promise(function(resolve) {
                fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: detailParams.toString() })
                    .then(r => r.json()).then(resolve).catch(function() { resolve({success:false}); });
            })
        ]).then(function(results) {
            const badgeData = results[0];
            const detailData = results[1];
            if (!badgeData.success) { container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">加载失败</span>'; return; }

            selectedBadges = [];
            if (detailData.success && detailData.data && detailData.data.badge) {
                try {
                    var t = JSON.parse(detailData.data.badge);
                    if (Array.isArray(t)) selectedBadges = t;
                } catch(e) {
                    if (detailData.data.badge) selectedBadges = [detailData.data.badge];
                }
            }
            renderBadgeSelector(badgeData.data || []);
        });
    }

    function renderBadgeSelector(allBadges) {
        const container = document.getElementById('storeBadgeSelector');
        if (!container) return;
        if (!allBadges.length) { container.innerHTML = '<span style="color:#8c8c8c;font-size:13px;">暂无可用徽章</span>'; return; }
        var html = '';
        allBadges.forEach(function(b) {
            var checked = selectedBadges.indexOf(b.name) !== -1;
            html += '<span onclick="toggleStoreBadge(\'' + b.name.replace(/'/g, "\\'") + '\')" style="display:inline-block;padding:5px 12px;border-radius:6px;font-size:13px;cursor:pointer;transition:all 0.2s;user-select:none;' +
                (checked ? 'background:#1890ff;color:white;border:1px solid #1890ff;' : 'background:white;color:#595959;border:1px solid #d9d9d9;') +
                '"' + (checked ? ' data-selected="1"' : '') + '>' + escapeHtml(b.icon) + ' ' + escapeHtml(b.name) + '</span>';
        });
        container.innerHTML = html;
        document.getElementById('formBadge').value = JSON.stringify(selectedBadges);
    }

    function toggleStoreBadge(name) {
        var idx = selectedBadges.indexOf(name);
        if (idx === -1) { selectedBadges.push(name); }
        else { selectedBadges.splice(idx, 1); }
        const p = new URLSearchParams();
        p.append('action', 'store_badges_list');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p.toString() })
            .then(r => r.json())
            .then(function(data) {
                if (data.success) renderBadgeSelector(data.data || []);
            });
    }
    /* ===== ====== ===== */

    /* ===== 经营牌照（选图→预览→保存时上传） ===== */
    var licenseImages = [];       // 已上传/已保存的 URL
    var pendingLicenseFiles = []; // 待上传的文件(preview+file对象)

    function renderLicenseImages() {
        var container = document.getElementById('licenseImagesContainer');
        if (!container) return;
        var html = '';
        // 已保存的 URL 图片
        licenseImages.forEach(function(url, idx) {
            html += '<div style="position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;border:1px solid #f0f0f0;">' +
                '<img src="' + url + '" style="width:100%;height:100%;object-fit:cover;">' +
                '<div onclick="removeLicenseImage(' + idx + ')" style="position:absolute;top:2px;right:2px;width:20px;height:20px;border-radius:50%;background:rgba(255,77,79,0.9);color:white;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;">×</div></div>';
        });
        // 待上传的本地预览
        pendingLicenseFiles.forEach(function(item, idx) {
            html += '<div style="position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;border:1px solid #1890ff;">' +
                '<img src="' + item.preview + '" style="width:100%;height:100%;object-fit:cover;">' +
                '<span style="position:absolute;top:2px;left:2px;font-size:10px;background:#1890ff;color:white;padding:1px 5px;border-radius:3px;">待上传</span>' +
                '<div onclick="removePendingLicenseImage(' + idx + ')" style="position:absolute;top:2px;right:2px;width:20px;height:20px;border-radius:50%;background:rgba(255,77,79,0.9);color:white;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;">×</div></div>';
        });
        if (!html) { html = '<span style="color:#d9d9d9;font-size:13px;">暂无经营牌照图片</span>'; }
        container.innerHTML = html;
    }

    function removeLicenseImage(idx) {
        licenseImages.splice(idx, 1);
        renderLicenseImages();
    }

    function removePendingLicenseImage(idx) {
        URL.revokeObjectURL(pendingLicenseFiles[idx].preview);
        pendingLicenseFiles.splice(idx, 1);
        renderLicenseImages();
    }

    function handleLicenseUpload(event) {
        var files = Array.from(event.target.files);
        if (!files.length) return;
        var total = licenseImages.length + pendingLicenseFiles.length + files.length;
        if (total > 10) {
            showMessage('最多 10 张，已有 ' + (licenseImages.length + pendingLicenseFiles.length) + ' 张', 'error');
            event.target.value = ''; return;
        }
        files.forEach(function(file) {
            var preview = URL.createObjectURL(file);
            pendingLicenseFiles.push({file: file, preview: preview, name: file.name});
        });
        renderLicenseImages();
        event.target.value = '';
    }

    async function handleLicenseOnSave() {
        if (!pendingLicenseFiles.length) return;
        showLoading('上传经营牌照...');
        for (var i = 0; i < pendingLicenseFiles.length; i++) {
            var item = pendingLicenseFiles[i];
            var fd = new FormData();
            fd.append('image', item.file);
            try {
                var res = await fetch('../product/product_upload.php', { method: 'POST', body: fd });
                var result = await res.json();
                if (result.success) {
                    var url = result.preview_url || result.url || result.file_key;
                    licenseImages.push(url);
                }
            } catch(e) {}
            URL.revokeObjectURL(item.preview);
        }
        pendingLicenseFiles = [];
        renderLicenseImages();
        hideLoading();
    }

    // =============== 门店照片上传（Z30075 风格） ===============
    var storeImages = [];
    var pendingStoreImages = [];

    function initStorePhotoUploader() {
        var container = document.getElementById('storePhotoUploader');
        if (!container) return;
        container.innerHTML =
            '<div class="piu-grid" id="storePhotoGrid">' +
              '<div class="piu-uploader" id="storePhotoUploadBtn">' +
                '<div class="piu-uploader__icon">📷</div>' +
                '<div class="piu-uploader__text">点击上传</div>' +
                '<div class="piu-uploader__hint">JPG/PNG 5MB</div>' +
              '</div>' +
            '</div>' +
            '<input type="file" id="storePhotoInput" class="piu-hidden-input" multiple accept="image/*">' +
            '<div style="font-size:12px;color:#8c8c8c;margin-top:8px;" id="storePhotoCount">已选择 0 张图片</div>';

        document.getElementById('storePhotoUploadBtn').onclick = function() {
            document.getElementById('storePhotoInput').click();
        };
        document.getElementById('storePhotoInput').onchange = function(e) {
            handleStoreImageUpload(e);
        };
    }

    function renderStoreImages() {
        var grid = document.getElementById('storePhotoGrid');
        if (!grid) return;
        grid.querySelectorAll('.store-photo-item').forEach(function(el) { el.remove(); });
        storeImages.forEach(function(url, idx) {
            var src = (url.startsWith('http') || url.startsWith('/')) ? url : '/uploads/products/' + url.split('/').pop();
            var html = '<div class="store-photo-item piu-item" style="width:120px;height:120px;">' +
                '<img src="' + src.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;object-fit:cover;">' +
                '<button type="button" class="piu-remove-btn" onclick="removeStoreImage(' + idx + ')"></button></div>';
            var uploader = grid.querySelector('.piu-uploader');
            if (uploader) uploader.insertAdjacentHTML('beforebegin', html);
        });
        pendingStoreImages.forEach(function(item, idx) {
            var html = '<div class="store-photo-item piu-item" style="width:120px;height:120px;position:relative;">' +
                '<img src="' + item.blobUrl.replace(/"/g, '&quot;') + '" style="width:100%;height:100%;object-fit:cover;">' +
                '<span style="position:absolute;top:4px;left:4px;background:#fa8c16;color:white;font-size:10px;padding:1px 5px;border-radius:3px;z-index:2;">待上传</span>' +
                '<button type="button" class="piu-remove-btn" onclick="removePendingStoreImage(' + idx + ')"></button></div>';
            var uploader = grid.querySelector('.piu-uploader');
            if (uploader) uploader.insertAdjacentHTML('beforebegin', html);
        });
        updateStorePhotoCount();
        updateStorePhotoUploaderVisibility();
    }

    function updateStorePhotoCount() {
        var el = document.getElementById('storePhotoCount');
        if (el) el.textContent = '已选择 ' + (storeImages.length + pendingStoreImages.length) + ' 张图片';
    }

    function updateStorePhotoUploaderVisibility() {
        var total = storeImages.length + pendingStoreImages.length;
        var uploader = document.getElementById('storePhotoUploadBtn');
        if (uploader) uploader.style.display = (total >= 10) ? 'none' : 'flex';
    }

    function removeStoreImage(index) {
        storeImages.splice(index, 1);
        renderStoreImages();
        document.getElementById('formStoreImages').value = JSON.stringify(storeImages);
    }

    function removePendingStoreImage(index) {
        URL.revokeObjectURL(pendingStoreImages[index].blobUrl);
        pendingStoreImages.splice(index, 1);
        renderStoreImages();
    }

    function handleStoreImageUpload(event) {
        var files = Array.from(event.target.files);
        if (!files.length) return;
        var total = storeImages.length + pendingStoreImages.length + files.length;
        if (total > 10) {
            showMessage('最多 10 张，已有 ' + (storeImages.length + pendingStoreImages.length) + ' 张', 'error');
            event.target.value = ''; return;
        }
        files.forEach(function(file) {
            if (!file.type.match(/image\//)) return;
            if (file.size > 5 * 1024 * 1024) {
                showMessage('图片 "' + file.name + '" 大小不能超过 5MB', 'error');
                return;
            }
            var blobUrl = URL.createObjectURL(file);
            pendingStoreImages.push({file: file, blobUrl: blobUrl, name: file.name});
        });
        renderStoreImages();
        event.target.value = '';
    }

    async function uploadStoreImagesOnSave() {
        if (!pendingStoreImages.length) return;
        showLoading('上传门店照片...');
        for (var i = 0; i < pendingStoreImages.length; i++) {
            var item = pendingStoreImages[i];
            var fd = new FormData();
            fd.append('image', item.file);
            try {
                var res = await fetch('../product/product_upload.php', { method: 'POST', body: fd });
                var result = await res.json();
                if (result.success) {
                    var url = result.preview_url || result.url || result.file_key;
                    storeImages.push(url);
                }
            } catch(e) {}
            URL.revokeObjectURL(item.blobUrl);
        }
        pendingStoreImages = [];
        renderStoreImages();
        hideLoading();
    }
    /* ===== ====== ===== */

    function loadStoreProducts(storeId) {
        var container = document.getElementById('storeProductsTable');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><div class="icon">⏳</div><div class="text">加载中…</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'store_products_list');
        params.append('store_id', storeId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) { container.innerHTML = '<div class="empty-state"><div class="icon">❌</div><div class="text">' + data.message + '</div></div>'; return; }
                var items = data.data || [];
                document.getElementById('storeProductCount').textContent = '门店商品（' + items.length + '个）';
                if (items.length === 0) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">🛒</div><div class="text">该门店暂无关联商品，点击上方「+ 添加商品」新增</div></div>';
                    return;
                }
                var html = '<table><thead><tr>' +
                    '<th width="280">商品信息</th>' +
                    '<th width="120">价格</th>' +
                    '<th width="100">总销量</th>' +
                    '<th width="100">库存总量</th>' +
                    '<th width="140">所属门店</th>' +
                    '<th width="100">上下架</th>' +
                    '<th width="100">状态</th>' +
                    '<th width="150">操作</th>' +
                    '</tr></thead><tbody>';
                items.forEach(function(p) {
                    var isOnSale = p.status == 1;
                    var isSoldOut = p.stock == 0;
                    var imgHtml = p.image
                        ? '<img src="' + p.image + '" style="width:60px;height:60px;border-radius:8px;object-fit:cover;">'
                        : '<div style="width:60px;height:60px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:24px;">📦</div>';
                    var statusHtml;
                    if (isSoldOut) { statusHtml = '<span class="badge" style="background:#fff7e6;color:#d46b08;">已售罄</span>'; }
                    else if (isOnSale) { statusHtml = '<span class="badge badge-success">出售中</span>'; }
                    else { statusHtml = '<span class="badge badge-danger">已下架</span>'; }
                    html += '<tr>' +
                        '<td><div style="display:flex;align-items:center;gap:12px;">' + imgHtml + '<div>' +
                        '<div style="font-weight:600;color:#262626;">' + escapeHtml(p.name) + '</div>' +
                        '<div style="font-size:12px;color:#8c8c8c;margin-top:2px;">' + escapeHtml(p.category_name || '') + '</div></div></div></td>' +
                        '<td><div style="font-weight:600;color:#262626;">¥' + parseFloat(p.price).toFixed(2) + '</div></td>' +
                        '<td>' + (p.sales || 0) + '</td>' +
                        '<td><span style="color:' + (p.stock == 0 ? '#ff4d4f' : '#52c41a') + ';font-weight:600;">' + (p.stock || 0) + '</span></td>' +
                        '<td><div style="font-size:12px;color:#595959;">' + escapeHtml(p.store_name || '-') + '</div></td>' +
                        '<td><div class="toggle-switch ' + (isOnSale ? 'on' : '') + '" onclick="toggleProductStatus(' + p.id + ', ' + (isOnSale ? 0 : 1) + ',' + storeId + ')"></div></td>' +
                        '<td>' + statusHtml + '</td>' +
                        '<td>' +
                        '<button class="btn btn-danger" style="padding:4px 10px;font-size:12px;margin-right:4px;" onclick="removeStoreProduct(' + storeId + ', ' + p.id + ')">移除</button>' +
                        '<button class="btn btn-default" style="padding:4px 10px;font-size:12px;" onclick="openProductEditor(' + p.id + ')">编辑</button>' +
                        '</td></tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">❌</div><div class="text">加载失败：' + err.message + '</div></div>';
            });
    }



    // =============== 门店评价 ===============


    // =============== 门店订单 ===============
    var allStoreOrdersData = [];
    var currentOrderFilter = 'all';

    function loadStoreOrders(storeId) {
        var container = document.getElementById('storeOrdersList');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><div class="icon">\u23f3</div><div class="text">加载中\u2026</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'store_orders_list');
        params.append('store_id', storeId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">' + data.message + '</div></div>';
                    return;
                }
                allStoreOrdersData = data.data || [];
                renderOrderStats(allStoreOrdersData);
                renderOrdersByFilter(currentOrderFilter, allStoreOrdersData);
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">\u52a0\u8f7d\u5931\u8d25\uff1a' + err.message + '</div></div>';
            });
    }

    function processAfterSale(afterId, action) {
        var actionLabel = action === 'handle' ? '\u5904\u7406\u5b8c\u6210' : '\u62d2\u7edd\u9000\u6b3e';
        var reason = '';
        if (action === 'reject') {
            reason = prompt('\u8bf7\u8f93\u5165\u62d2\u7edd\u7406\u7531\uff1a');
            if (reason === null) return; // cancelled
            reason = reason.trim();
            if (reason === '') {
                showMessage('\u8bf7\u8f93\u5165\u62d2\u7edd\u7406\u7531', 'error');
                return;
            }
        } else {
            if (!confirm('\u786e\u5b9a\u8981\u5c06\u8be5\u552e\u540e\u5355\u6807\u8bb0\u4e3a\u201c' + actionLabel + '\u201d\u5417\uff1f')) return;
        }
        var params = new URLSearchParams();
        params.append('action', 'process_after_sale');
        params.append('id', afterId);
        params.append('process_action', action);
        if (reason) params.append('handle_reason', reason);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    var storeId = parseInt(document.getElementById('formId').value);
                    if (storeId) loadStoreAfter(storeId);
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }

    function filterStoreOrders(el, filter) {
        currentOrderFilter = filter;
        document.querySelectorAll('.order-filter-tab').forEach(function(t) {
            t.style.background = '#f5f5f5';
            t.style.color = '#595959';
        });
        el.style.background = '#1890ff';
        el.style.color = '#fff';
        renderOrdersByFilter(filter, allStoreOrdersData);
    }

    function renderOrderStats(orders) {
        var total = orders.length;
        var totalAmt = 0, completed = 0;
        var pending = 0, pendingShip = 0, shipped = 0;
        orders.forEach(function(o) {
            totalAmt += parseFloat(o.pay_amount || 0);
            if (o.status === 'completed') completed++;
            if (o.status === 'pending') pending++;
            if (o.status === 'pending_ship' || o.status === 'paid') pendingShip++;
            if (o.status === 'shipped') shipped++;
        });
        document.getElementById('orderTotalCount').textContent = total;
        document.getElementById('orderTotalAmount').textContent = '\u00a5' + totalAmt.toFixed(2);
        document.getElementById('orderCompletedCount').textContent = completed;
        // Update tab counts
        var tabs = document.querySelectorAll('.order-filter-tab');
        if (tabs.length >= 5) {
            tabs[0].innerHTML = '\u5168\u90e8 (<span>' + total + '</span>)';
            tabs[1].innerHTML = '\u5f85\u4ed8\u6b3e (<span>' + pending + '</span>)';
            tabs[2].innerHTML = '\u5f85\u53d1\u8d27 (<span>' + pendingShip + '</span>)';
            tabs[3].innerHTML = '\u5df2\u53d1\u8d27 (<span>' + shipped + '</span>)';
            tabs[4].innerHTML = '\u5df2\u5b8c\u6210 (<span>' + completed + '</span>)';
        }
    }

    var expandedOrderId = null;

    var expandedOrderId = null;

    function renderOrdersByFilter(filter, orders) {
        var container = document.getElementById('storeOrdersList');
        if (!container) return;
        var filtered = orders.filter(function(o) {
            if (filter === 'all') return true;
            if (filter === 'pending') return o.status === 'pending';
            if (filter === 'paid') return o.status === 'paid' || o.status === 'pending_ship';
            if (filter === 'shipped') return o.status === 'shipped';
            if (filter === 'completed') return o.status === 'completed';
            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="icon">\ud83d\udce6</div><div class="text">\u6682\u65e0\u8be5\u72b6\u6001\u8ba2\u5355</div></div>';
            return;
        }

        var statusLabels = {
            'pending': '<span class="badge" style="background:#fff7e6;color:#d46b08;">\u5f85\u4ed8\u6b3e</span>',
            'paid': '<span class="badge" style="background:#f6ffed;color:#52c41a;">\u5df2\u4ed8\u6b3e</span>',
            'pending_ship': '<span class="badge" style="background:#e6f7ff;color:#1890ff;">\u5f85\u53d1\u8d27</span>',
            'shipped': '<span class="badge" style="background:#f9f0ff;color:#722ed1;">\u5df2\u53d1\u8d27</span>',
            'completed': '<span class="badge badge-success">\u5df2\u5b8c\u6210</span>',
            'cancelled': '<span class="badge badge-danger">\u5df2\u53d6\u6d88</span>'
        };

        var colHeaders = '<div style="background:white;border:1px solid #e8e8e8;border-bottom:none;overflow:hidden;"><div style="padding:8px 16px;display:flex;align-items:center;gap:12px;background:#fafafa;border-bottom:1px solid #f0f0f0;font-size:12px;color:#8c8c8c;font-weight:600;"><div style="width:6px;flex-shrink:0;"></div><div style="width:170px;flex-shrink:0;">\u8ba2\u5355\u53f7</div><div style="width:90px;flex-shrink:0;">\u5ba2\u6237</div><div style="width:80px;flex-shrink:0;">\u72b6\u6001</div><div style="width:50px;flex-shrink:0;text-align:center;">\u6570\u91cf</div><div style="width:100px;flex-shrink:0;text-align:right;">\u91d1\u989d</div><div style="flex:1;text-align:right;">\u65f6\u95f4</div><div style="width:16px;flex-shrink:0;"></div></div></div>';
        var cards = [];

        filtered.forEach(function(o) {
            var statusHtml = statusLabels[o.status] || '<span class="badge">' + (o.status || '\u672a\u77e5') + '</span>';
            var createdAt = o.created_at ? o.created_at.substr(0, 16) : '-';
            var isExpanded = (expandedOrderId === o.id);
            var itemCount = (o.items && o.items.length > 0) ? o.items.length : 0;
            var canShip = (o.status === 'paid' || o.status === 'pending_ship');

            // Build status bar color
            var barColor = '#1890ff';
            if (o.status === 'completed') barColor = '#52c41a';
            else if (o.status === 'pending') barColor = '#faad14';
            else if (o.status === 'shipped') barColor = '#722ed1';
            else if (o.status === 'cancelled') barColor = '#ff4d4f';

            // Build collapsed row
            var itemsPreview = '';
            if (o.items && o.items.length > 0) {
                var names = [];
                o.items.forEach(function(item) { names.push(item.product_name); });
                itemsPreview = names.join(', ');
                if (itemsPreview.length > 40) itemsPreview = itemsPreview.substr(0, 38) + '\u2026';
            }

            var headerHtml = '<div style="display:flex;align-items:center;gap:12px;flex-wrap:nowrap;padding:10px 16px;cursor:pointer;" onclick="toggleOrderDetail(' + o.id + ')">' +
                '<div style="width:6px;height:32px;border-radius:3px;flex-shrink:0;background:' + barColor + ';"></div>' +
                '<div style="width:170px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><span style="font-size:13px;font-weight:600;color:#262626;">' + escapeHtml(o.order_no || '-') + '</span></div>' +
                '<div style="width:90px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><span style="font-size:13px;color:#595959;">' + escapeHtml(o.member_name || '\u672a\u77e5') + '</span></div>' +
                '<div style="width:80px;flex-shrink:0;text-align:center;">' + statusHtml + '</div>' +
                '<div style="width:50px;flex-shrink:0;text-align:center;"><span style="font-size:12px;color:#8c8c8c;">' + itemCount + '\u4ef6</span></div>' +
                '<div style="width:100px;flex-shrink:0;text-align:right;"><span style="font-size:14px;font-weight:700;color:#cf1322;">\u00a5' + parseFloat(o.pay_amount || 0).toFixed(2) + '</span></div>' +
                '<div style="flex:1;text-align:right;"><span style="font-size:12px;color:#bfbfbf;">' + createdAt + '</span></div>' +
                '<div style="width:16px;flex-shrink:0;text-align:center;transition:transform 0.2s;' + (isExpanded ? 'transform:rotate(180deg);' : '') + '">\u25bc</div>' +
                '</div>';

            // Build expanded detail section
            var detailHtml = '';
            if (isExpanded) {
                var itemsDetail = '';
                if (o.items && o.items.length > 0) {
                    o.items.forEach(function(item) {
                        var imgHtml = item.product_image
                            ? '<img src="' + item.product_image + '" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">'
                            : '<div style="width:40px;height:40px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#8c8c8c;">\ud83c\udf92</div>';
                        itemsDetail += '<div style="display:flex;align-items:center;gap:8px;padding:6px 0;">' +
                            imgHtml +
                            '<div style="flex:1;min-width:0;"><span style="font-size:13px;color:#262626;">' + escapeHtml(item.product_name) + '</span>' +
                            '<span style="font-size:12px;color:#bfbfbf;margin-left:8px;">\u00a5' + parseFloat(item.price).toFixed(2) + ' x ' + item.quantity + '</span></div>' +
                            '<span style="font-size:13px;font-weight:600;color:#262626;">\u00a5' + parseFloat(item.total).toFixed(2) + '</span></div>';
                    });
                }

                var shipBtn = canShip
                    ? '<button class="btn btn-primary" style="padding:6px 16px;font-size:13px;" onclick="event.stopPropagation();shipOrder(' + o.id + ')">\ud83d\ude9a \u53d1\u8d27</button>'
                    : '';

                detailHtml = '<div style="padding:10px 16px;border-top:1px solid #f0f0f0;background:#fafafa;">' +
                    itemsDetail +
                    '<div style="display:flex;justify-content:space-between;align-items:center;padding-top:6px;border-top:1px dashed #e8e8e8;">' +
                    '<div><span style="font-size:12px;color:#8c8c8c;">\u4f1a\u5458\uff1a</span><span style="font-size:13px;color:#262626;">' + escapeHtml(o.member_name || '\u672a\u77e5') + '</span>' +
                    (o.discount_amount > 0 ? '<span style="font-size:12px;color:#8c8c8c;margin-left:12px;">\u4f18\u60e0\uff1a-\u00a5' + parseFloat(o.discount_amount).toFixed(2) + '</span>' : '') +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:10px;"><span style="font-size:13px;color:#cf1322;font-weight:600;">\u00a5' + parseFloat(o.pay_amount || 0).toFixed(2) + '</span>' + shipBtn + '</div></div></div>';
            }

            var cardHtml = '<div style="background:white;border:1px solid #e8e8e8;margin-bottom:8px;overflow:hidden;' + (isExpanded ? 'box-shadow:0 2px 12px rgba(0,0,0,0.08);' : 'box-shadow:0 1px 4px rgba(0,0,0,0.04);') + '">' +
                headerHtml + detailHtml + '</div>';
            cards.push(cardHtml);
        });

        container.innerHTML = colHeaders + cards.join('');
    }

    function toggleOrderDetail(orderId) {
        if (expandedOrderId === orderId) {
            expandedOrderId = null;
        } else {
            expandedOrderId = orderId;
        }
        renderOrdersByFilter(currentOrderFilter, allStoreOrdersData);
    }

    // =============== 门店售后 ===============
    function loadStoreAfter(storeId) {
        var container = document.getElementById('storeAfterList');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><div class="icon">\u23f3</div><div class="text">加载中\u2026</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'store_after_list');
        params.append('store_id', storeId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">' + data.message + '</div></div>';
                    return;
                }
                var items = data.data || [];
                document.getElementById('afterTotalCount').textContent = data.total || 0;

                var processing = 0, refundTotal = 0;
                items.forEach(function(a) {
                    if (a.after_status === 'processing') processing++;
                    refundTotal += parseFloat(a.refund_amount || 0);
                });
                document.getElementById('afterProcessingCount').textContent = processing;
                document.getElementById('afterRefundTotal').textContent = '\u00a5' + refundTotal.toFixed(2);

                if (items.length === 0) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">\ud83d\udd27</div><div class="text">\u6682\u65e0\u552e\u540e\u8bb0\u5f55</div></div>';
                    return;
                }

                var statusLabels = {
                    'processing': '<span class="badge" style="background:#e6f7ff;color:#1890ff;">\u5904\u7406\u4e2d</span>',
                    'handled': '<span class="badge badge-success">\u5df2\u5904\u7406</span>',
                    'rejected': '<span class="badge badge-danger">\u5df2\u62d2\u7edd</span>',
                    'cancelled': '<span class="badge" style="background:#f5f5f5;color:#8c8c8c;">\u5df2\u53d6\u6d88</span>'
                };
                var typeLabels = {
                    'return_refund': '\u9000\u8d27\u9000\u6b3e',
                    'refund_only': '\u4ec5\u9000\u6b3e',
                    'exchange': '\u6362\u8d27'
                };

                var html = '';
                items.forEach(function(a) {
                    var statusHtml = statusLabels[a.after_status] || a.after_status;
                    var typeLabel = typeLabels[a.after_type] || a.after_type;
                    var applyTime = a.apply_time ? a.apply_time.substr(0, 16) : '-';

                    html += '<div style="background:white;border-radius:12px;border:1px solid #f0f0f0;margin-bottom:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.04);">' +
                        '<div style="padding:10px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f5f5f5;">' +
                        '<div style="display:flex;align-items:center;gap:10px;">' +
                        '<span style="font-size:13px;font-weight:600;color:#262626;">' + escapeHtml(a.after_no || '-') + '</span>' +
                        '<span style="font-size:12px;color:#8c8c8c;">\u8ba2\u5355\uff1a' + escapeHtml(a.order_no || '-') + '</span></div>' +
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                        '<span class="badge" style="background:#f5f5f5;color:#595959;">' + typeLabel + '</span>' +
                        statusHtml +
                        '<span style="font-size:12px;color:#bfbfbf;">' + applyTime + '</span></div></div>' +

                        '<div style="padding:10px 16px;display:flex;align-items:center;gap:10px;">' +
                        '<div style="width:44px;height:44px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#8c8c8c;flex-shrink:0;">\ud83c\udf92</div>' +
                        '<div style="flex:1;min-width:0;"><div style="font-size:13px;font-weight:500;color:#262626;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(a.goods_name) + '</div>' +
                        '<div style="font-size:12px;color:#8c8c8c;">\u00a5' + parseFloat(a.goods_price || 0).toFixed(2) + ' x ' + (a.goods_quantity || 0) + '</div></div>' +
                        '<div style="text-align:right;"><div style="font-size:12px;color:#8c8c8c;">\u9000\u6b3e</div>' +
                        '<div style="font-size:14px;font-weight:700;color:#cf1322;">\u00a5' + parseFloat(a.refund_amount || 0).toFixed(2) + '</div></div></div>' +

                        '<div style="padding:8px 16px;background:#fafafa;border-top:1px solid #f5f5f5;display:flex;justify-content:space-between;align-items:center;">' +
                        '<div style="font-size:12px;color:#8c8c8c;max-width:50%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escapeHtml(a.reason || '') + '">' +
                        '<span style="color:#595959;">\u539f\u56e0\uff1a</span>' + escapeHtml(a.reason || '\u65e0') + '</div>' +
                        (a.after_status === 'processing'
                            ? '<div style="display:flex;gap:6px;flex-shrink:0;"><button class="btn btn-success" style="padding:4px 12px;font-size:12px;" onclick="event.stopPropagation();processAfterSale(' + a.id + ', \'handle\')">\u2705 \u5904\u7406\u5b8c\u6210</button><button class="btn btn-danger" style="padding:4px 12px;font-size:12px;" onclick="event.stopPropagation();processAfterSale(' + a.id + ', \'reject\')">\u274c \u62d2\u7edd</button></div>'
                            : a.after_status === 'rejected'
                                ? '<div style="font-size:12px;color:#ff4d4f;flex-shrink:0;text-align:right;" title="' + escapeHtml(a.handle_reason || '') + '"><div>\u2716 \u5df2\u62d2\u7edd</div><div style="color:#8c8c8c;">' + escapeHtml(a.handle_reason || '') + '</div></div>'
                                : a.after_status === 'handled'
                                    ? '<div style="font-size:12px;color:#52c41a;flex-shrink:0;">\u2714 \u5df2\u5904\u7406' + (a.handle_time ? ' ' + a.handle_time.substr(0,10) : '') + '</div>'
                                    : '<div style="font-size:12px;color:#8c8c8c;flex-shrink:0;">' + escapeHtml(a.buyer_name || '-') + ' | ' + escapeHtml(a.buyer_phone || '-') + '</div>') +
                        '</div></div>';
                });
                container.innerHTML = html;
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">\u52a0\u8f7d\u5931\u8d25\uff1a' + err.message + '</div></div>';
            });
    }

    function shipOrder(orderId) {
        if (!confirm('\u786e\u5b9a\u8981\u5c06\u8be5\u8ba2\u5355\u6807\u8bb0\u4e3a\u5df2\u53d1\u8d27\u5417\uff1f')) return;
        var params = new URLSearchParams();
        params.append('action', 'ship_order');
        params.append('id', orderId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    expandedOrderId = null;
                    renderOrdersByFilter(currentOrderFilter, allStoreOrdersData);
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }
    var allCommentsData = [];
    var currentFilter = 'all';

    function loadStoreComments(storeId) {
        var container = document.getElementById('storeCommentsList');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><div class="icon">\u23f3</div><div class="text">加载中\u2026</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'comments');
        params.append('store_id', storeId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">' + data.message + '</div></div>';
                    return;
                }
                renderRatingStats(data.rating_stats, data.total);
                allCommentsData = data.data || [];
                // 计算好评/一般/差评数量
                var good = 0, normal = 0, bad = 0;
                allCommentsData.forEach(function(c) {
                    if (c.rating >= 4) good++;
                    else if (c.rating == 3) normal++;
                    else bad++;
                });
                document.getElementById('goodCount').textContent = good;
                document.getElementById('normalCount').textContent = normal;
                document.getElementById('badCount').textContent = bad;
                // 按当前筛选渲染
                renderCommentsByFilter(currentFilter);
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">\u52a0\u8f7d\u5931\u8d25\uff1a' + err.message + '</div></div>';
            });
    }

    function filterReviews(el, filter) {
        currentFilter = filter;
        // 切换 tab 样式
        document.querySelectorAll('.review-filter-tab').forEach(function(t) {
            t.style.background = '#f5f5f5';
            t.style.color = '#595959';
        });
        el.style.background = '#1890ff';
        el.style.color = '#fff';
        renderCommentsByFilter(filter);
    }

    function renderCommentsByFilter(filter) {
        var container = document.getElementById('storeCommentsList');
        if (!container) return;
        var items = allCommentsData.filter(function(c) {
            if (filter === 'all') return true;
            if (filter === 'good') return c.rating >= 4;
            if (filter === 'normal') return c.rating == 3;
            if (filter === 'bad') return c.rating <= 2;
            return true;
        });
        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="icon">\u2b50</div><div class="text">\u6682\u65e0\u8bc4\u4ef7\u6570\u636e</div></div>';
            return;
        }
        var html = '';
        items.forEach(function(c) {
            var stars = '';
            for (var s = 0; s < 5; s++) {
                stars += s < c.rating ? '\u2b50' : '\u2606';
            }
            var avatarHtml = c.user_avatar
                ? '<img src="' + c.user_avatar + '" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">'
                : '<div style="width:36px;height:36px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:16px;">\ud83d\udc64</div>';
            var imagesHtml = '';
            if (c.images) {
                try {
                    var imgs = JSON.parse(c.images);
                    if (imgs.length > 0) {
                        imagesHtml = '<div style="display:flex;gap:8px;margin-top:8px;flex-wrap:nowrap;">';
                        imgs.forEach(function(img) {
                            imagesHtml += '<div style="width:64px;height:64px;border-radius:8px;overflow:hidden;border:1px solid #f0f0f0;"><img src="' + img + '" style="width:100%;height:100%;object-fit:cover;"></div>';
                        });
                        imagesHtml += '</div>';
                    }
                } catch(e) {}
            }
            // 格式化的绝对时间
            var dateStr = c.created_at || '';
            var formattedDate = '';
            if (dateStr) {
                var d = new Date(dateStr.replace(/-/g, '/'));
                var y = d.getFullYear();
                var m = (d.getMonth()+1).toString().padStart(2,'0');
                var day = d.getDate().toString().padStart(2,'0');
                var h = d.getHours().toString().padStart(2,'0');
                var min = d.getMinutes().toString().padStart(2,'0');
                formattedDate = y + '-' + m + '-' + day + ' ' + h + ':' + min;
            }
            var relativeTime = formatTime(dateStr);
            html += '<div style="padding:16px;border-bottom:1px solid #f5f5f5;">' +
                '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">' +
                avatarHtml +
                '<div style="flex:1;"><div style="font-weight:600;font-size:14px;color:#262626;">' + escapeHtml(c.user_name || '\u533f\u540d\u7528\u6237') + '</div>' +
                '<div style="display:flex;align-items:center;gap:8px;margin-top:2px;">' +
                '<span style="font-size:13px;color:#faad14;">' + stars + '</span>' +
                '<span style="font-size:12px;color:#bfbfbf;">' + relativeTime + '</span>' +
                '<span style="font-size:12px;color:#d9d9d9;">|</span>' +
                '<span style="font-size:12px;color:#8c8c8c;" title="' + formattedDate + '">' + formattedDate + '</span></div></div></div>' +
                '<div style="font-size:14px;color:#595959;line-height:1.6;">' + escapeHtml(c.content) + '</div>' +
                imagesHtml +
                '</div>';
        });
        container.innerHTML = html;
    }


    function renderRatingStats(ratingStats, total) {
        document.getElementById('totalReviewsCount').textContent = total + '条评价';
        if (!ratingStats || ratingStats.length === 0) return;

        var totalScore = 0, count = 0;
        ratingStats.forEach(function(s) {
            totalScore += parseInt(s.rating) * parseInt(s.cnt);
            count += parseInt(s.cnt);
        });
        if (count === 0) return;
        var avg = (totalScore / count).toFixed(1);
        document.getElementById('avgRatingDisplay').textContent = avg;

        var starsHtml = '';
        for (var s = 0; s < 5; s++) {
            starsHtml += s < Math.round(avg) ? '⭐' : '☆';
        }
        document.getElementById('avgRatingStars').innerHTML = starsHtml;

        var barChart = document.getElementById('ratingBarChart');
        var barHtml = '';
        for (var r = 5; r >= 1; r--) {
            var found = false;
            var cnt = 0;
            ratingStats.forEach(function(s) {
                if (parseInt(s.rating) === r) { cnt = parseInt(s.cnt); found = true; }
            });
            var pct = count > 0 ? (cnt / count * 100).toFixed(0) : 0;
            barHtml += '<div style="display:flex;align-items:center;gap:8px;font-size:12px;">' +
                '<span style="width:36px;color:#8c8c8c;text-align:right;">' + r + '星</span>' +
                '<div style="flex:1;height:14px;background:#f5f5f5;border-radius:7px;overflow:hidden;position:relative;">' +
                '<div style="width:' + pct + '%;height:100%;background:linear-gradient(90deg,#faad14,#fadb14);border-radius:7px;transition:width 0.3s;"></div></div>' +
                '<span style="width:40px;color:#8c8c8c;">' + cnt + '条</span></div>';
        }
        barChart.innerHTML = barHtml;
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(/-/g, '/'));
        var now = new Date();
        var diff = (now - d) / 1000;
        if (diff < 60) return '刚刚';
        if (diff < 3600) return Math.floor(diff/60) + '分钟前';
        if (diff < 86400) return Math.floor(diff/3600) + '小时前';
        if (diff < 2592000) return Math.floor(diff/86400) + '天前';
        var m = (d.getMonth()+1).toString().padStart(2,'0');
        var day = d.getDate().toString().padStart(2,'0');
        return d.getFullYear() + '-' + m + '-' + day;
    }
    function toggleProductStatus(productId, newStatus, storeId) {
        if (!confirm('确定要' + (newStatus == 1 ? '上架' : '下架') + '该商品吗？')) return;
        var params = new URLSearchParams();
        params.append('action', 'product_toggle_status');
        params.append('product_id', productId);
        params.append('status', newStatus);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) { loadStoreProducts(storeId); showMessage('✅ ' + d.message, 'success'); }
                else { showMessage('❌ ' + d.message, 'error'); }
            });
    }

    // =============== 门店分类 ===============
    var allCategories = [];

    function loadStoreCategories(storeId) {
        var container = document.getElementById('storeCategoryTable');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><div class="icon">\u23f3</div><div class="text">\u52a0\u8f7d\u4e2d\u2026</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'store_categories_list');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">' + data.message + '</div></div>';
                    return;
                }
                allCategories = data.data || [];
                renderCategoryList(allCategories);
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">\u274c</div><div class="text">\u52a0\u8f7d\u5931\u8d25\uff1a' + err.message + '</div></div>';
            });
    }

    function renderCategoryList(cats) {
        var container = document.getElementById('storeCategoryTable');
        if (!container || cats.length === 0) {
            if (container) container.innerHTML = '<div class="empty-state"><div class="icon">\uD83D\uDCC2</div><div class="text">\u6682\u65E0\u5206\u7C7B\u6570\u636E</div></div>';
            return;
        }
        // \u8ba1\u7b97\u6bcf\u4e2a\u7236\u5206\u7c7b\u662f\u5426\u6709\u5b50\u7ea7
        var hasChild = {};
        cats.forEach(function(c) { if (c.parent_id > 0) hasChild[c.parent_id] = true; });
        var html = '<table style="font-size:13px;"><thead><tr>' +
            '<th width="50">ID</th>' +
            '<th>\u5206\u7c7b\u540d\u79f0</th>' +
            '<th width="70">\u5546\u54c1\u6570</th>' +
            '<th width="70">\u6392\u5e8f</th>' +
            '<th width="70">\u72b6\u6001</th>' +
            '<th width="140">\u64cd\u4f5c</th>' +
            '</tr></thead><tbody>';

        cats.forEach(function(c) {
            if (c.parent_id == 0) {
                html += renderCategoryRow(c, 0, cats, hasChild);
            }
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function renderCategoryRow(cat, depth, allCats, hasChild) {
        var indent = depth * 24;
        var isParent = depth === 0;
        // \u5206\u7c7b\u540d\u79f0: \u7236\u7ea7\u663e\u793a\u6298\u53e0\u4e09\u89d2\u548c\u7c97\u4f53
        var nameHtml = '';
        if (isParent) {
            var showToggle = hasChild && hasChild[cat.id] ? 'style="cursor:pointer;display:inline-block;width:14px;text-align:center;margin-right:2px;color:#8c8c8c;font-size:11px;"' : 'style="display:inline-block;width:14px;text-align:center;margin-right:2px;visibility:hidden;"';
            nameHtml = '<span class="toggle-child" data-parent="' + cat.id + '" ' + showToggle + ' onclick="toggleChildCategories(' + cat.id + ')">\u25bc</span> <strong>' + escapeHtml(cat.name) + '</strong>';
        } else {
            nameHtml = '<span style="display:inline-block;width:14px;margin-right:2px;color:#bfbfbf;font-size:11px;">\u2517</span> <span style="color:#595959;">' + escapeHtml(cat.name) + '</span>';
        }
        var nameCell = '<div style="padding-left:' + indent + 'px;">' + nameHtml + '</div>';

        // \u72b6\u6001: toggle\u5f00\u5173
        var statusHtml = '<div class="toggle-switch' + (cat.status == 1 ? ' on' : '') + '" onclick="toggleCategoryStatus(' + cat.id + ',' + (cat.status == 1 ? 0 : 1) + ')" title="' + (cat.status == 1 ? '\u70b9\u51fb\u7981\u7528' : '\u70b9\u51fb\u542f\u7528') + '"></div>';

        // \u6392\u5e8f: \u53ef\u7f16\u8f91\u8f93\u5165\u6846
        var sortHtml = '<input type="number" class="sort-input" value="' + (cat.sort || 0) + '" onchange="updateCategorySort(' + cat.id + ', this.value)" style="width:50px;padding:2px 6px;border:1px solid #d9d9d9;border-radius:4px;text-align:center;font-size:12px;">';

        // \u5546\u54c1\u6570
        var prodCount = cat.product_count || 0;

        var html = '<tr data-id="' + cat.id + '" class="' + (depth > 0 ? 'child-row-' + cat.parent_id : 'parent-row') + '"' + (depth > 0 ? ' style="background:#f5f9ff;"' : '') + '>' +
            '<td style="font-size:12px;color:#8c8c8c;">#' + cat.id + '</td>' +
            '<td style="text-align:left;">' + nameCell + '</td>' +
            '<td style="text-align:center;"><span style="background:#e6f7ff;color:#1890ff;padding:1px 8px;border-radius:8px;font-size:12px;">' + prodCount + '</span></td>' +
            '<td>' + sortHtml + '</td>' +
            '<td>' + statusHtml + '</td>' +
            '<td style="white-space:nowrap;">' +
            '<button class="action-btn edit" onclick="editCategoryName(' + cat.id + ')" style="padding:3px 8px;font-size:12px;color:#1890ff;border:none;background:transparent;cursor:pointer;border-radius:4px;">\u7f16\u8f91</button>' +
            '<button class="action-btn delete" onclick="deleteCategory(' + cat.id + ')" style="padding:3px 8px;font-size:12px;color:#ff4d4f;border:none;background:transparent;cursor:pointer;border-radius:4px;">\u5220\u9664</button>' +
            '</td></tr>';

        // \u663e\u793a\u5b50\u5206\u7c7b
        allCats.forEach(function(sub) {
            if (sub.parent_id == cat.id) {
                html += renderCategoryRow(sub, depth + 1, allCats, hasChild);
            }
        });
        return html;
    }


    // 拘叠/展开子分类
    function toggleChildCategories(parentId) {
        var rows = document.querySelectorAll('.child-row-' + parentId);
        if (!rows.length) return;
        var isHidden = rows[0].style.display === 'none';
        rows.forEach(function(r) { r.style.display = isHidden ? '' : 'none'; });
        var btn = document.querySelector('.parent-row[data-id="' + parentId + '"] .toggle-child');
        if (btn) btn.textContent = isHidden ? '\u25bc' : '\u25b6';
    }

    // 更新分类排序
    function updateCategorySort(id, sort) {
        var params = new URLSearchParams();
        params.append('action', 'store_category_update');
        params.append('id', id);
        params.append('name', '');
        params.append('parent_id', 0);
        params.append('sort', sort);
        params.append('status', 1);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) { showMessage('\u2705 ' + d.message, 'success'); }
                else { showMessage('\u274c ' + d.message, 'error'); }
            });
    }

    // 编辑分类名称
    function editCategoryName(id) {
        var newName = prompt('\u8bf7\u8f93\u5165\u65b0\u7684\u5206\u7c7b\u540d\u79f0：');
        if (!newName || newName.trim() === '') return;
        var params = new URLSearchParams();
        params.append('action', 'store_category_update');
        params.append('id', id);
        params.append('name', newName.trim());
        params.append('parent_id', 0);
        params.append('sort', 0);
        params.append('status', 1);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    loadStoreCategories();
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }

    function toggleCategoryStatus(id, newStatus) {
        var params = new URLSearchParams();
        params.append('action', 'store_category_update');
        params.append('id', id);
        params.append('name', '');
        params.append('parent_id', 0);
        params.append('sort', 0);
        params.append('status', newStatus);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    loadStoreCategories();
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }

    function openCategoryEditor() {
        // \u52a0\u8f7d\u4e0a\u7ea7\u5206\u7c7b\u4e0b\u62c9
        var parentSel = document.getElementById('catParent');
        parentSel.innerHTML = '<option value="0">\u65e0\uff08\u9876\u7ea7\u5206\u7c7b\uff09</option>';
        allCategories.forEach(function(c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            parentSel.appendChild(opt);
        });
        document.getElementById('catName').value = '';
        document.getElementById('catSort').value = '0';
        document.getElementById('catStatus').value = '1';
        categoryIconData = '';
        document.getElementById('catIcon').value = '';
        document.getElementById('catIconPreview').innerHTML = '🖼️';
        document.getElementById('catImageInput').value = '';
        document.getElementById('addCategoryModal').classList.add('show');
    }

    function closeCategoryModal() {
        document.getElementById('addCategoryModal').classList.remove('show');
    }

    function submitCategory() {
        var name = document.getElementById('catName').value.trim();
        if (!name) { showMessage('\u8bf7\u8f93\u5165\u5206\u7c7b\u540d\u79f0', 'error'); return; }
        var parent_id = document.getElementById('catParent').value;
        var sort = document.getElementById('catSort').value;
        var status = document.getElementById('catStatus').value;
        var params = new URLSearchParams();
        params.append('action', 'store_category_add');
        params.append('name', name);
        params.append('parent_id', parent_id);
        params.append('sort', sort);
        params.append('status', status);
        params.append('icon', document.getElementById('catIcon').value);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    closeCategoryModal();
                    loadStoreCategories();
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }

    function deleteCategory(id) {
        if (!confirm('\u786e\u5b9a\u8981\u5220\u9664\u8be5\u5206\u7c7b\u5417\uff1f')) return;
        var params = new URLSearchParams();
        params.append('action', 'store_category_delete');
        params.append('id', id);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    showMessage('\u2705 ' + d.message, 'success');
                    loadStoreCategories();
                } else {
                    showMessage('\u274c ' + d.message, 'error');
                }
            });
    }

    function switchProdTab(tab) {
        document.querySelectorAll('.prod-sub-tab').forEach(function(t) {
            t.style.background = '#f5f5f5';
            t.style.color = '#595959';
        });
        var activeTab = document.querySelector('.prod-sub-tab[data-subtab="' + tab + '"]');
        if (activeTab) {
            activeTab.style.background = '#1890ff';
            activeTab.style.color = '#fff';
        }
        if (tab === 'products') {
            document.getElementById('storeProductsTable').style.display = '';
            document.getElementById('storeCategoryTable').style.display = 'none';
            document.getElementById('storeProductCount').textContent = '该门店关联的商品';
            document.getElementById('prodTabActionBtn').textContent = '+ 添加商品';
            document.getElementById('prodTabActionBtn').onclick = function() { openProductEditor(); };
        } else {
            document.getElementById('storeProductsTable').style.display = 'none';
            document.getElementById('storeCategoryTable').style.display = '';
            document.getElementById('storeProductCount').textContent = '商品分类列表';
            document.getElementById('prodTabActionBtn').textContent = '+ 添加分类';
            document.getElementById('prodTabActionBtn').onclick = function() { openCategoryEditor(); };
        }
    }

    // =============== 商品编辑器 ===============
    var productEditorStoreId = 0;
    var pPendingVideo = null;
    var pPendingCover = null;

    function openProductEditor(productId) {
        productEditorStoreId = parseInt(document.getElementById('formId').value);
        if (!productEditorStoreId) { alert('请先保存门店信息'); return; }
        document.getElementById('pStoreId').value = productEditorStoreId;

        if (productId) {
            // 编辑已有商品
            document.getElementById('productEditorTitle').textContent = '编辑商品 #' + productId;
            loadProductData(productId);
        } else {
            // 新增商品
            document.getElementById('productEditorTitle').textContent = '添加商品';
            resetProductForm();
        }
        document.getElementById('productEditorDrawer').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeProductEditor() {
        var d = document.getElementById('productEditorDrawer');
        d.classList.add('closing');
        setTimeout(function() { d.classList.remove('show', 'closing'); document.body.style.overflow = ''; }, 300);
    }

    function resetProductForm() {
        pImages = [];
        pPendingImages = [];
        document.getElementById('pId').value = '';
        document.getElementById('pName').value = '';
        document.getElementById('pType').value = '1';
        document.getElementById('pCategory').value = '';
        document.getElementById('pCode').value = '';
        document.getElementById('pImages').value = '';
        document.getElementById('pFreight').value = '0';
        document.getElementById('pStatus').value = '1';
        document.getElementById('pSort').value = '0';
        document.getElementById('pPrice').value = '0';
        document.getElementById('pMemberPrice').value = '0';
        document.getElementById('pCostPrice').value = '0';
        document.getElementById('pStock').value = '0';
        document.getElementById('pWeight').value = '0';
        document.getElementById('pStockMethod').value = '1';
        document.querySelectorAll('input[name="spec_type"]').forEach(function(r) { r.checked = r.value === '1'; });
        document.querySelectorAll('input[name="limit_buy"]').forEach(function(r) { r.checked = r.value === '0'; });
        document.getElementById('pLimitNum').value = '0';
        document.getElementById('pLimitNum').disabled = true;
        document.getElementById('pContent').value = '';
        pPendingVideo = null;
        pPendingCover = null;
        document.getElementById('pVideoUrl').value = '';
        document.getElementById('pVideoCover').value = '';
        document.getElementById('pVideoPreview').innerHTML = '建议视频宽高比 16:9，建议时长 8-45 秒';
        document.getElementById('pVideoUploader').style.display = 'flex';
        document.getElementById('pCoverList').innerHTML = '';
        document.getElementById('pCoverUploader').style.display = 'flex';
        document.getElementById('pSellingPoints').value = '';
        document.getElementById('pServices').value = '';
        document.getElementById('pInitialSales').value = '0';
        document.getElementById('pMemberDiscount').value = '0';
        document.getElementById('pPointsGift').value = '0';
        document.getElementById('pPointsDeduct').value = '0';
        document.getElementById('pPointsDeductType').value = '';
        document.getElementById('pCommission').value = '';
        document.getElementById('pMarginReadonly').value = '输入价格后自动计算';
        toggleSpecType(1);
        updateImageCount();
        renderImages();
    }

    function loadProductData(productId) {
        var params = new URLSearchParams();
        params.append('action', 'product_get');
        params.append('product_id', productId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var p = data.data;
                    try { pImages = JSON.parse(p.images || '[]'); } catch(e) { pImages = []; }
                    pPendingImages = [];
                    document.getElementById('pId').value = p.id;
                    document.getElementById('pName').value = p.name || '';
                    document.getElementById('pType').value = p.type || 1;
                    document.getElementById('pCategory').value = p.category_id || '';
                    document.getElementById('pCode').value = p.product_code || '';
                    document.getElementById('pImages').value = p.images || '';
                    document.getElementById('pFreight').value = p.freight_template_id || 0;
                    document.getElementById('pStatus').value = p.status;
                    document.getElementById('pSort').value = p.sort || 0;
                    document.getElementById('pPrice').value = p.price || 0;
                    document.getElementById('pMemberPrice').value = p.member_price || 0;
                    document.getElementById('pCostPrice').value = p.cost_price || 0;
                    document.getElementById('pStock').value = p.stock || 0;
                    document.getElementById('pWeight').value = p.weight || 0;
                    document.getElementById('pStockMethod').value = p.stock_method || 1;
                    if (p.spec_data) {
                        document.getElementById('specDataInput').value = typeof p.spec_data === 'string' ? p.spec_data : JSON.stringify(p.spec_data);
                        // 保存原始 spec_data 用于对比删除
                        window._oldSpecData = document.getElementById('specDataInput').value;
                    } else {
                        document.getElementById('specDataInput').value = '[]';
                        window._oldSpecData = '[]';
                    }
                    document.getElementById('pLimitNum').value = p.limit_buy_num || 0;
                    document.getElementById('pContent').value = p.content || '';
                    document.getElementById('pVideoUrl').value = p.video_url || '';
                    document.getElementById('pVideoCover').value = p.video_cover || '';
                    document.getElementById('pSellingPoints').value = p.selling_points || '';
                    document.getElementById('pServices').value = p.services || '';
                    document.getElementById('pInitialSales').value = p.initial_sales || 0;
                    document.getElementById('pMemberDiscount').value = p.member_discount || 0;
                    document.getElementById('pPointsGift').value = p.points_gift || 0;
                    document.getElementById('pPointsDeduct').value = p.points_deduct || 0;
                    document.getElementById('pPointsDeductType').value = p.points_deduct_type || '';
                    document.getElementById('pCommission').value = p.commission_type || '';
                    // 加载视频
                    if (p.video_url) {
                        var vSrc = p.video_url;
                        document.getElementById('pVideoUrl').value = vSrc;
                        var vName = vSrc.split('/').pop();
                        document.getElementById('pVideoPreview').innerHTML =
                            '<div style="margin-top:12px;">' +
                            '<div class="image-item" style="width:140px;height:140px;">' +
                            '<video src="' + vSrc + '" controls style="width:100%;height:100%;object-fit:cover;border-radius:12px;background:#000;"></video>' +
                            '</div>' +
                            '<div style="margin-top:8px;font-size:12px;"><span style="color:#52c41a;">✓ 视频已上传：</span>' + vName +
                            ' <a href="javascript:;" onclick="removePVideo();" style="color:#ff4d4f;margin-left:12px;">删除</a></div>' +
                            '</div>';
                        document.getElementById('pVideoUploader').style.display = 'none';
                    }
                    // 加载封面
                    if (p.video_cover) {
                        var cSrc = p.video_cover;
                        document.getElementById('pVideoCover').value = cSrc;
                        renderPCover(cSrc, false);
                    }
                    // 单选按钮
                    var limitBuy = parseInt(p.limit_buy || 0);
                    document.querySelectorAll('input[name="limit_buy"]').forEach(function(r) { r.checked = parseInt(r.value) === limitBuy; });
                    toggleLimitBuy(limitBuy === 1);
                    var specType = parseInt(p.spec_type || 1);
                    document.querySelectorAll('input[name="spec_type"]').forEach(function(r) { r.checked = parseInt(r.value) === specType; });
                    toggleSpecType(specType);
                    calcMargin();
                    updateImageCount();
                    renderImages();
                }
            });
    }

    function toggleSpecType(val) {
        var single = document.getElementById('singleSpecBlock');
        var multi = document.getElementById('multiSpecBlock');
        if (val === 1) {
            single.style.display = 'block';
            multi.style.display = 'none';
        } else {
            single.style.display = 'none';
            multi.style.display = 'block';
            renderSpecGroups();
        }
    }

    /* ===== 多规格构建器 ===== */
    var specData = [];

    function getSpecData() {
        try { return JSON.parse(document.getElementById('specDataInput').value || '[]'); }
        catch(e) { return []; }
    }
    function setSpecData(data) {
        document.getElementById('specDataInput').value = JSON.stringify(data);
    }

    
    async function previewSpecImg(input) {
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        var item = input.closest('.spec-item');
        if (!item) return;
        // 先显示本地预览
        var imgContainer = item.querySelector('div[onclick*="click()"]');
        if (imgContainer) imgContainer.innerHTML = '<img src="' + URL.createObjectURL(file) + '" style="width:100%;height:100%;object-fit:cover;">';
        // 上传到 OSS
        var formData = new FormData();
        formData.append('image', file);
        try {
            var response = await fetch('../product/product_upload.php', { method: 'POST', body: formData });
            var result = await response.json();
            if (result.success) {
                var imageUrl = result.preview_url || result.url || result.file_key || '';
                var hidden = item.querySelector('.spec-img-val');
                if (hidden) { hidden.value = imageUrl; }
                syncSpecData();
            } else {
                showMessage('规格图片上传失败: ' + (result.message || '未知错误'), 'error');
                // 恢复为 '+' 占位
                if (imgContainer) imgContainer.innerHTML = '<span style="font-size:22px;color:#d9d9d9;">+</span>';
            }
        } catch (error) {
            showMessage('规格图片上传失败: ' + (error.message || error), 'error');
            if (imgContainer) imgContainer.innerHTML = '<span style="font-size:22px;color:#d9d9d9;">+</span>';
        }
    }
function renderSpecGroups() {
        var data = getSpecData();
        var container = document.getElementById('specGroupContainer');
        if (!container) return;
        if (data.length === 0) {
            container.innerHTML = '<div style="padding:20px;text-align:center;color:#8c8c8c;background:#fafafa;border-radius:8px;border:1px dashed #d9d9d9;">暂无规格分类，点击上方「新增规格分类」添加</div>';
            return;
        }
        var html = '';
        for (var g = 0; g < data.length; g++) {
            var group = data[g];
            html += '<div class="spec-group">';
            html += '<div class="spec-group-head"><span>规格：' + escapeHtml(group.name) + '</span><div style="display:flex;gap:6px;"><button class="btn btn-sm btn-success" onclick="addSpecItem(this)" style="padding:4px 10px;font-size:12px;border:none;border-radius:4px;color:#fff;background:#67c23a;cursor:pointer;">新增规格</button><button class="btn btn-sm btn-danger" onclick="delSpecGroup(this)" style="padding:4px 10px;font-size:12px;border:none;border-radius:4px;color:#fff;background:#f56c6c;cursor:pointer;">删除</button></div></div>';
            html += '<div class="spec-group-body">';
            var items = group.items || [];
            if (items.length === 0) {
                html += '<div style="padding:12px;text-align:center;color:#bfbfbf;font-size:13px;">暂无规格项，点击「新增规格」添加</div>';
            } else {
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var margin = (item.price && item.cost_price) ? ((parseFloat(item.price) - parseFloat(item.cost_price)) / parseFloat(item.price) * 100).toFixed(1) : '';
                                        html += '<div class="spec-item" style="display:flex;gap:6px;align-items:center;padding:10px 0;border-bottom:1px dashed #f0f0f0;">';
                    html += '<div style="display:flex;flex-direction:column;align-items:center;gap:3px;width:70px;flex-shrink:0;">';
                    html += '<span style="font-size:10px;color:#999;">图片</span>';
                    html += '<div style="width:60px;height:60px;border:1px dashed #d9d9d9;border-radius:6px;overflow:hidden;cursor:pointer;display:flex;align-items:center;justify-content:center;background:#fafafa;" onclick="this.parentNode.querySelector(\'input[type=file]\').click()">';
                    html += (item.image ? '<img src="' + escapeHtml(item.image) + '" style="width:100%;height:100%;object-fit:cover;">' : '<span style="font-size:22px;color:#d9d9d9;">+</span>');
                    html += '</div>';
                    html += '<input type="file" style="display:none" accept="image/*" onchange="previewSpecImg(this)">';
                    html += '<input type="hidden" class="spec-img-val" value="' + escapeHtml(item.image || '') + '">';
                    html += '</div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">规格名称</span><input type="text" class="name" value="' + escapeHtml(item.name || '') + '" placeholder="红色" onchange="syncSpecData()" style="width:90px;"></div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">价格</span><input type="number" value="' + (item.price ?? '') + '" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">会员价</span><input type="number" value="' + (item.member_price ?? '') + '" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">成本价</span><input type="number" value="' + (item.cost_price ?? '') + '" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>';
                    html += '<div style="text-align:center"><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">毛利率</span><div class="spec-margin" style="padding:6px 0;font-size:13px;font-weight:600;' + (margin ? 'color:' + (parseFloat(margin) < 0 ? '#f56c6c' : '#67c23a') : '') + '">' + (margin ? margin + '%' : '-') + '</div></div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">库存（-1无限）</span><input type="number" value="' + (item.stock ?? '-1') + '" min="-1" placeholder="-1" onchange="syncSpecData()" style="width:70px;"></div>';
                    html += '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">重量/kg</span><input type="number" value="' + (item.weight ?? '0.5') + '" step="0.001" placeholder="0.5" onchange="syncSpecData()" style="width:70px;"></div>';
                    html += '<button class="btn btn-sm btn-danger" onclick="delSpecItem(this)" style="padding:4px 8px;font-size:12px;border:none;border-radius:4px;color:#fff;background:#f56c6c;cursor:pointer;margin-top:18px;">删除</button>';
                    html += '</div>';
                }
            }
            html += '</div></div>';
        }
        container.innerHTML = html;
    }

    function syncSpecData() {
        var container = document.getElementById('specGroupContainer');
        if (!container) return;
        var groups = container.querySelectorAll('.spec-group');
        var data = [];
        for (var g = 0; g < groups.length; g++) {
            var head = groups[g].querySelector('.spec-group-head span');
            if (!head) continue;
            var name = head.textContent.replace('规格：', '');
            var items = [];
            var itemEls = groups[g].querySelectorAll('.spec-item');
            for (var i = 0; i < itemEls.length; i++) {
                var inputs = itemEls[i].querySelectorAll('input');
                // DOM input order per .spec-item:
                //   [0] file upload, [1] hidden spec-img-val, [2] text name,
                //   [3] price, [4] member_price, [5] cost_price, [6] stock, [7] weight
                if (inputs.length < 8) continue;
                items.push({
                    image: itemEls[i].querySelector('.spec-img-val') ? itemEls[i].querySelector('.spec-img-val').value : (itemEls[i].querySelector('input[type=hidden]') ? itemEls[i].querySelector('input[type=hidden]').value : ''),

                    name: inputs[2].value,
                    price: parseFloat(inputs[3].value) || 0,
                    member_price: parseFloat(inputs[4].value) || 0,
                    cost_price: parseFloat(inputs[5].value) || 0,
                    stock: parseInt(inputs[6].value) || 0,
                    weight: parseFloat(inputs[7].value) || 0
                });
            }
            data.push({ name: name, items: items });
        }
        setSpecData(data);
        // 刷新毛利率显示
        renderSpecGroups();
    }

    function addSpecGroup() {
        var name = prompt('请输入规格分类名称（如：颜色、尺寸、版本）：');
        if (!name || name.trim() === '') return;
        var data = getSpecData();
        data.push({ name: name.trim(), items: [] });
        setSpecData(data);
        renderSpecGroups();
    }

    function delSpecGroup(el) {
        if (!confirm('确定删除此规格分类？')) return;
        var groupEl = el.closest('.spec-group');
        if (groupEl) groupEl.remove();
        syncSpecData();
    }

    function addSpecItem(el) {
        var group = el.closest('.spec-group');
        if (!group) return;
        var body = group.querySelector('.spec-group-body');
        if (!body) return;
        // 移除'暂无规格项'占位文本
        var emptyMsg = body.querySelector('div[style*="text-align:center"]');
        if (emptyMsg) emptyMsg.remove();
        var item = document.createElement('div');
        item.className = 'spec-item';
        item.innerHTML = '<div style="display:flex;align-items:center;gap:6px;padding:10px 0;border-bottom:1px dashed #f0f0f0;">' +
            '<div style="display:flex;flex-direction:column;align-items:center;gap:3px;width:70px;flex-shrink:0;">' +
            '<span style="font-size:10px;color:#999;">图片</span>' +
            '<div style="width:60px;height:60px;border:1px dashed #d9d9d9;border-radius:6px;overflow:hidden;cursor:pointer;display:flex;align-items:center;justify-content:center;background:#fafafa;" onclick="this.parentNode.querySelector(\'input[type=file]\').click()">' +
            '<span style="font-size:22px;color:#d9d9d9;">+</span></div>' +
            '<input type="file" style="display:none" accept="image/*" onchange="previewSpecImg(this)">' +
            '<input type="hidden" class="spec-img-val" value="">' +
            '</div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">规格名称</span><input type="text" class="name" placeholder="红色" onchange="syncSpecData()" style="width:90px;"></div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">价格</span><input type="number" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">会员价</span><input type="number" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">成本价</span><input type="number" step="0.01" placeholder="" onchange="syncSpecData()" style="width:80px;"></div>' +
            '<div style="text-align:center"><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">毛利率</span><div class="spec-margin" style="padding:6px 0;font-size:13px;font-weight:600;">-</div></div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">库存（-1无限）</span><input type="number" min="-1" placeholder="-1" value="-1" onchange="syncSpecData()" style="width:70px;"></div>' +
            '<div><span style="font-size:10px;color:#999;display:block;margin-bottom:2px;">重量/kg</span><input type="number" step="0.001" placeholder="0.5" value="0.5" onchange="syncSpecData()" style="width:70px;"></div>' +
            '<button class="btn btn-sm btn-danger" onclick="delSpecItem(this)" style="padding:4px 8px;font-size:12px;border:none;border-radius:4px;color:#fff;background:#f56c6c;cursor:pointer;margin-top:18px;">删除</button>' +
            '</div>'
        body.appendChild(item);
        syncSpecData();
    }

    function delSpecItem(el) {
        el.closest('.spec-item').remove();
        syncSpecData();
    }

    /* 提交时收集规格数据 */
    window.collectSpecData = function() {
        var specType = document.querySelector('input[name="spec_type"]:checked');
        if (specType && specType.value === '2') {
            // 多规格，已通过 hidden input 保存
        }
    };

    function toggleLimitBuy(open) {
        document.getElementById('pLimitNum').disabled = !open;
    }

    // 自动计算毛利率
    document.addEventListener('input', function(e) {
        if (e.target.id === 'pPrice' || e.target.id === 'pCostPrice') {
            calcMargin();
        }
    });

    function calcMargin() {
        var price = parseFloat(document.getElementById('pPrice').value) || 0;
        var cost = parseFloat(document.getElementById('pCostPrice').value) || 0;
        var el = document.getElementById('pMarginReadonly');
        var display = document.getElementById('pMarginDisplay');
        if (price > 0) {
            var margin = ((price - cost) / price * 100).toFixed(1);
            el.value = margin + '%';
            el.style.color = margin >= 0 ? '#52c41a' : '#ff4d4f';
            display.textContent = '毛利率: ' + margin + '%';
        } else {
            el.value = '输入价格后自动计算';
            el.style.color = '#8c8c8c';
            display.textContent = '-';
        }
    }

    // =============== 商品图片上传 ===============
    function updateImageCount() {
        var total = pImages.length + pPendingImages.length;
        var pending = pPendingImages.length > 0 ? '（' + pPendingImages.length + ' 张待上传）' : '';
        var el = document.getElementById('imageCount');
        if (el) el.textContent = '已选择 ' + total + ' 张图片' + pending + '（最少 1 张，最多 10 张）';
    }

    function renderImages() {
        var container = document.getElementById('imageList');
        if (!container) return;
        container.innerHTML = '';
        pImages.forEach(function(img) {
            var div = document.createElement('div');
            div.className = 'image-item';
            div.draggable = true;
            div.dataset.type = 'stored';
            div.dataset.url = img;
            var imgEl = document.createElement('img');
            imgEl.src = (img.startsWith('http') || img.startsWith('/')) ? img : '/uploads/products/' + img.split('/').pop();
            imgEl.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            div.appendChild(imgEl);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'remove';
            btn.textContent = '×';
            div.appendChild(btn);
            container.appendChild(div);
        });
        pPendingImages.forEach(function(item) {
            var div = document.createElement('div');
            div.className = 'image-item';
            div.draggable = true;
            div.dataset.type = 'pending';
            div.dataset.blob = item.blobUrl;
            div.style.position = 'relative';
            var imgEl = document.createElement('img');
            imgEl.src = item.blobUrl;
            imgEl.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            div.appendChild(imgEl);
            var badge = document.createElement('span');
            badge.style.cssText = 'position:absolute;top:4px;left:4px;background:#fa8c16;color:white;font-size:10px;padding:1px 5px;border-radius:3px;';
            badge.textContent = '待上传';
            div.appendChild(badge);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'remove';
            btn.textContent = '×';
            div.appendChild(btn);
            container.appendChild(div);
        });
        document.getElementById('imagesInput').value = JSON.stringify(pImages);
    }

    function handleImageUpload(event) {
        var files = Array.from(event.target.files);
        if (!files.length) return;
        var totalCount = pImages.length + pPendingImages.length + files.length;
        if (totalCount > 10) {
            showMessage('最多只能上传 10 张图片，当前已有 ' + (pImages.length + pPendingImages.length) + ' 张', 'error');
            return;
        }
        files.forEach(function(file) {
            var blobUrl = URL.createObjectURL(file);
            pPendingImages.push({ file: file, blobUrl: blobUrl, name: file.name });
        });
        updateImageCount();
        renderImages();
        event.target.value = '';
    }

    async function uploadImageFiles(files) {
        var urls = [];
        for (var i = 0; i < files.length; i++) {
            var item = files[i];
            var formData = new FormData();
            formData.append('image', item.file);
            try {
                var response = await fetch('../product/product_upload.php', { method: 'POST', body: formData });
                var result = await response.json();
                if (result.success) {
                    var imageUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
                    urls.push(imageUrl);
                    URL.revokeObjectURL(item.blobUrl);
                } else {
                    throw new Error(result.message || '上传失败');
                }
            } catch (error) {
                showMessage('图片上传失败: ' + (error.message || error), 'error');
                throw error;
            }
        }
        return urls;
    }

    // =============== 拖拽排序 ===============
    var _dragItem = null;

    function syncArrayFromDom() {
        var container = document.getElementById('imageList');
        if (!container) return;
        var items = Array.from(container.children);
        var newStored = [];
        var newPending = [];
        items.forEach(function(item) {
            if (item.dataset.type === 'stored') {
                newStored.push(item.dataset.url);
            } else if (item.dataset.type === 'pending') {
                var blob = item.dataset.blob;
                var found = pPendingImages.find(function(p) { return p.blobUrl === blob; });
                if (found) newPending.push(found);
            }
        });
        pImages = newStored;
        pPendingImages = newPending;
        document.getElementById('imagesInput').value = JSON.stringify(pImages);
    }

    // 使用事件委托处理图片删除，兼容拖拽后索引变化
    document.addEventListener('click', function _imageRemoveHandler(e) {
        var btn = e.target.closest('#imageList .remove');
        if (!btn) return;
        var item = btn.closest('.image-item');
        if (!item) return;
        e.preventDefault();
        var container = document.getElementById('imageList');
        if (!container) return;
        var allStored = container.querySelectorAll('.image-item[data-type="stored"]');
        var allPending = container.querySelectorAll('.image-item[data-type="pending"]');
        if (item.dataset.type === 'stored') {
            var idx = Array.from(allStored).indexOf(item);
            if (idx !== -1) {
                pImages.splice(idx, 1);
            }
        } else if (item.dataset.type === 'pending') {
            var idx = Array.from(allPending).indexOf(item);
            if (idx !== -1) {
                URL.revokeObjectURL(pPendingImages[idx].blobUrl);
                pPendingImages.splice(idx, 1);
            }
        }
        updateImageCount();
        renderImages();
    });

    // 拖拽事件：初始化
    document.getElementById('imageList').addEventListener('dragstart', function(e) {
        var item = e.target.closest('.image-item');
        if (!item) return;
        _dragItem = item;
        item.classList.add('dragging');
    });

    document.getElementById('imageList').addEventListener('dragend', function() {
        if (_dragItem) {
            _dragItem.classList.remove('dragging');
            _dragItem = null;
        }
    });

    document.getElementById('imageList').addEventListener('dragover', function(e) {
        e.preventDefault();
        var targetItem = e.target.closest('.image-item:not(.dragging)');
        if (!targetItem || targetItem === _dragItem) return;
        var rect = targetItem.getBoundingClientRect();
        var isLeftHalf = e.clientX < rect.left + rect.width / 2;
        if (isLeftHalf) {
            this.insertBefore(_dragItem, targetItem);
        } else {
            this.insertBefore(_dragItem, targetItem.nextSibling);
        }
        // 拖动中实时同步数据
        syncArrayFromDom();
    });

    // =============== 视频/封面上传 ===============
    function handlePVideoUpload(event) {
        var file = event.target.files[0];
        if (!file) return;
        var validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        if (validTypes.indexOf(file.type) === -1) { showMessage('请上传 MP4、WebM 或 OGG 格式的视频', 'error'); return; }
        if (file.size > 100 * 1024 * 1024) { showMessage('视频大小不能超过 100MB', 'error'); return; }
        var blobUrl = URL.createObjectURL(file);
        pPendingVideo = { file: file, blobUrl: blobUrl, name: file.name };
        document.getElementById('pVideoUrl').value = '';
        document.getElementById('pVideoPreview').innerHTML =
            '<div style="margin-top:12px;">' +
            '<div class="image-item" style="width:140px;height:140px;">' +
            '<video src="' + blobUrl + '" controls style="width:100%;height:100%;object-fit:cover;border-radius:12px;background:#000;"></video>' +
            '</div>' +
            '<div style="margin-top:8px;font-size:12px;"><span style="color:#fa8c16;">⏳ 待上传：</span>' + file.name +
            ' <a href="javascript:;" onclick="removePVideo();" style="color:#ff4d4f;margin-left:12px;">删除</a></div>' +
            '</div>';
        document.getElementById('pVideoUploader').style.display = 'none';
        event.target.value = '';
    }

    function removePVideo() {
        if (pPendingVideo) {
            URL.revokeObjectURL(pPendingVideo.blobUrl);
            pPendingVideo = null;
        }
        document.getElementById('pVideoUrl').value = '';
        document.getElementById('pVideoPreview').innerHTML = '建议视频宽高比 16:9，建议时长 8-45 秒';
        document.getElementById('pVideoInput').value = '';
        document.getElementById('pVideoUploader').style.display = 'flex';
    }

    async function uploadPVideoFile() {
        if (!pPendingVideo) return document.getElementById('pVideoUrl').value;
        var formData = new FormData();
        formData.append('video', pPendingVideo.file);
        var response = await fetch('../product/product_upload.php', { method: 'POST', body: formData });
        var result = await response.json();
        if (result.success) {
            var vUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
            URL.revokeObjectURL(pPendingVideo.blobUrl);
            pPendingVideo = null;
            return vUrl;
        }
        throw new Error(result.message || '视频上传失败');
    }

    function handlePCoverUpload(event) {
        var file = event.target.files[0];
        if (!file) return;
        var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (validTypes.indexOf(file.type) === -1) { showMessage('请上传 JPG/PNG/GIF/WebP 格式的封面图片', 'error'); return; }
        if (file.size > 5 * 1024 * 1024) { showMessage('封面图片大小不能超过 5MB', 'error'); return; }
        var blobUrl = URL.createObjectURL(file);
        pPendingCover = { file: file, blobUrl: blobUrl, name: file.name };
        document.getElementById('pVideoCover').value = '';
        renderPCover(blobUrl, true);
        event.target.value = '';
    }

    function renderPCover(coverUrl, isPending) {
        var badge = isPending ? '<span style="position:absolute;top:4px;left:4px;background:#fa8c16;color:white;font-size:10px;padding:1px 5px;border-radius:3px;">待上传</span>' : '';
        document.getElementById('pCoverList').innerHTML =
            '<div class="image-item" style="width:140px;height:140px;position:relative;">' + badge +
            '<img src="' + coverUrl + '" style="width:100%;height:100%;object-fit:cover;">' +
            '<button type="button" class="remove" onclick="removePCover()">×</button></div>';
        document.getElementById('pCoverUploader').style.display = 'none';
    }

    function removePCover() {
        if (pPendingCover) {
            URL.revokeObjectURL(pPendingCover.blobUrl);
            pPendingCover = null;
        }
        document.getElementById('pVideoCover').value = '';
        document.getElementById('pCoverList').innerHTML = '';
        document.getElementById('pCoverInput').value = '';
        document.getElementById('pCoverUploader').style.display = 'flex';
    }

    async function uploadPCoverFile() {
        if (!pPendingCover) return document.getElementById('pVideoCover').value;
        var formData = new FormData();
        formData.append('image', pPendingCover.file);
        var response = await fetch('../product/product_upload.php', { method: 'POST', body: formData });
        var result = await response.json();
        if (result.success) {
            var cUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
            URL.revokeObjectURL(pPendingCover.blobUrl);
            pPendingCover = null;
            return cUrl;
        }
        throw new Error(result.message || '封面上传失败');
    }

    // =============== 提交保存（含图片上传） ===============
    async function submitProductForm() {
        var name = document.getElementById('pName').value.trim();
        if (!name) { showMessage('商品名称不能为空', 'error'); return; }

        var totalImages = pImages.length + pPendingImages.length;
        if (totalImages < 1) {
            showMessage('请至少上传 1 张商品图片', 'error');
            return;
        }

        var btn = document.getElementById('productSubmitBtn');
        btn.disabled = true;
        btn.textContent = '上传中…';

        try {
            // 先上传待传图片
            if (pPendingImages.length > 0) {
                btn.textContent = '上传图片(' + pPendingImages.length + ')…';
                var urls = await uploadImageFiles(pPendingImages);
                pImages = pImages.concat(urls);
                pPendingImages = [];
                document.getElementById('imagesInput').value = JSON.stringify(pImages);
            }

            // 上传视频
            if (pPendingVideo) {
                btn.textContent = '上传视频…';
                var vUrl = await uploadPVideoFile();
                document.getElementById('pVideoUrl').value = vUrl;
            }

            // 上传封面
            if (pPendingCover) {
                btn.textContent = '上传封面…';
                var cUrl = await uploadPCoverFile();
                document.getElementById('pVideoCover').value = cUrl;
            }

            btn.textContent = '保存中…';
            var params = new URLSearchParams();
            var productId = document.getElementById('pId').value;
            params.append('action', productId ? 'product_update' : 'product_create');
            if (productId) params.append('product_id', productId);

            params.append('store_id', document.getElementById('pStoreId').value);
            params.append('type', document.getElementById('pType').value);
            params.append('name', name);
            params.append('category_id', document.getElementById('pCategory').value);
            params.append('product_code', document.getElementById('pCode').value.trim());
            params.append('images', JSON.stringify(pImages));
            params.append('freight_template_id', document.getElementById('pFreight').value);
            params.append('status', document.getElementById('pStatus').value);
            params.append('sort', document.getElementById('pSort').value);
            params.append('spec_type', document.querySelector('input[name="spec_type"]:checked').value);
            params.append('spec_data', document.getElementById('specDataInput').value);
            params.append('price', document.getElementById('pPrice').value);
            params.append('member_price', document.getElementById('pMemberPrice').value);
            params.append('cost_price', document.getElementById('pCostPrice').value);
            params.append('stock', document.getElementById('pStock').value);
            params.append('weight', document.getElementById('pWeight').value);
            params.append('stock_method', document.getElementById('pStockMethod').value);
            params.append('limit_buy', document.querySelector('input[name="limit_buy"]:checked').value);
            params.append('limit_buy_num', document.getElementById('pLimitNum').value);
            params.append('content', document.getElementById('pContent').value);
            params.append('video_url', document.getElementById('pVideoUrl').value.trim());
            params.append('video_cover', document.getElementById('pVideoCover').value.trim());
            params.append('selling_points', document.getElementById('pSellingPoints').value);
            params.append('services', document.getElementById('pServices').value);
            params.append('initial_sales', document.getElementById('pInitialSales').value);
            params.append('member_discount', document.getElementById('pMemberDiscount').value);
            params.append('points_gift', document.getElementById('pPointsGift').value);
            params.append('points_deduct', document.getElementById('pPointsDeduct').value);
            params.append('points_deduct_type', document.getElementById('pPointsDeductType').value);
            params.append('commission_type', document.getElementById('pCommission').value.trim());

            // 删除已移除的规格图片
            (function() {
                var oldSpec = [];
                try { oldSpec = JSON.parse(window._oldSpecData || '[]'); } catch(e) {}
                var newSpec = [];
                try { newSpec = JSON.parse(document.getElementById('specDataInput').value || '[]'); } catch(e) {}
                var oldImgs = [];
                oldSpec.forEach(function(g) { (g.items || []).forEach(function(it) { if (it.image && it.image.indexOf('http') === 0) oldImgs.push(it.image); }); });
                var newImgs = [];
                newSpec.forEach(function(g) { (g.items || []).forEach(function(it) { if (it.image && it.image.indexOf('http') === 0) newImgs.push(it.image); }); });
                var deleted = oldImgs.filter(function(img) { return newImgs.indexOf(img) === -1; });
                if (deleted.length > 0) {
                    var dp = new URLSearchParams();
                    dp.append('action', 'product_delete_oss_images');
                    dp.append('images', JSON.stringify(deleted));
                    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: dp.toString() }).catch(function(){});
                }
            })();

            var response = await fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() });
            var result = await response.json();
            if (result.success) {
                // 保存成功后更新旧数据引用
                window._oldSpecData = document.getElementById('specDataInput').value;
                showMessage('✅ ' + result.message, 'success');
                closeProductEditor();
                loadStoreProducts(productEditorStoreId);
            } else {
                showMessage('❌ ' + result.message, 'error');
            }
        } catch (err) {
            showMessage('❌ 操作失败：' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = '确定保存';
        }
    }

    // =============== 已有商品搜索 ===============
    function openProductSearch() {
        document.getElementById('productSearchInput').value = '';
        document.getElementById('productSearchResults').innerHTML = '<div class="empty-state"><div class="icon">🔍</div><div class="text">输入关键词搜索已有商品</div></div>';
        document.getElementById('productSearchModal').classList.add('show');
    }

    function closeProductSearch() {
        document.getElementById('productSearchModal').classList.remove('show');
    }

    function searchProducts() {
        var keyword = document.getElementById('productSearchInput').value.trim();
        var storeId = productEditorStoreId || parseInt(document.getElementById('formId').value);
        if (!storeId) { showMessage('请先保存门店信息', 'error'); return; }
        var container = document.getElementById('productSearchResults');
        container.innerHTML = '<div class="empty-state"><div class="icon">⏳</div><div class="text">搜索中…</div></div>';
        var params = new URLSearchParams();
        params.append('action', 'store_products_search');
        params.append('store_id', storeId);
        params.append('keyword', keyword);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.data || data.data.length === 0) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">📭</div><div class="text">没有找到可添加的商品</div></div>';
                    return;
                }
                var html = '<table><thead><tr><th>商品名称</th><th>价格</th><th>库存</th><th>状态</th><th width="80">操作</th></tr></thead><tbody>';
                data.data.forEach(function(p) {
                    html += '<tr><td>#' + p.id + ' ' + escapeHtml(p.name) + '</td><td>¥' + p.price + '</td><td>' + p.stock + '</td><td>' + (p.status == 1 ? '<span class="badge badge-success">上架</span>' : '<span class="badge badge-danger">下架</span>') + '</td>';
                    html += '<td><button class="btn btn-primary" style="padding:4px 10px;font-size:12px;" onclick="addStoreProduct(' + storeId + ', ' + p.id + ')">添加</button></td></tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            })
            .catch(function(err) {
                container.innerHTML = '<div class="empty-state"><div class="icon">❌</div><div class="text">搜索失败：' + err.message + '</div></div>';
            });
    }

    function addStoreProduct(storeId, productId) {
        var params = new URLSearchParams();
        params.append('action', 'store_products_add');
        params.append('store_id', storeId);
        params.append('product_id', productId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showMessage('✅ 商品已关联到门店', 'success');
                    loadStoreProducts(storeId);
                    // 如果搜索弹窗开着则关闭
                    var sm = document.getElementById('productSearchModal');
                    if (sm && sm.classList.contains('show')) closeProductSearch();
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            })
            .catch(function(err) {
                showMessage('❌ 关联失败：' + err.message, 'error');
            });
    }

    function removeStoreProduct(storeId, productId) {
        if (!confirm('确定移除该商品关联吗？')) return;
        var params = new URLSearchParams();
        params.append('action', 'store_products_remove');
        params.append('store_id', storeId);
        params.append('product_id', productId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showMessage('✅ 已移除', 'success');
                    loadStoreProducts(storeId);
                } else {
                    showMessage('❌ ' + data.message, 'error');
                }
            })
            .catch(function(err) {
                showMessage('❌ 移除失败：' + err.message, 'error');
            });
    }

    function closeFormDrawer() {
        var d = document.getElementById('formDrawer');
        d.classList.add('closing');
        setTimeout(function() { d.classList.remove('show', 'closing'); document.body.style.overflow = ''; }, 300);
    }

    async function submitForm() {
        const name = document.getElementById('formName').value.trim();
        if (!name) {
            return showMessage('门店名称不能为空', 'error');
        }

        const btn = document.getElementById('formSubmitBtn');
        btn.disabled = true;
        btn.textContent = '保存中…';

        // 处理头像 + 经营牌照 + 门店照片
        try {
            await handleAvatarOnSave();
            await handleLicenseOnSave();
            await uploadStoreImagesOnSave();
        } catch(err) {
            showMessage('❌ 文件处理失败：' + (err.message || err), 'error');
            btn.disabled = false;
            btn.textContent = '确定保存';
            return;
        }

        const params = new URLSearchParams();
        params.append('action', editingId ? 'edit' : 'add');
        if (editingId) params.append('id', editingId);
        params.append('name', name);
        params.append('phone', document.getElementById('formPhone').value.trim());
        params.append('phone2', document.getElementById('formPhone2').value.trim());
        params.append('avatar', document.getElementById('formAvatar').value.trim());
        params.append('tags', JSON.stringify(selectedTags));
        params.append('country', document.getElementById('formCountry').value.trim());
        params.append('city', document.getElementById('formCity').value.trim());
        params.append('address', document.getElementById('formAddress').value.trim());
        params.append('latitude', document.getElementById('formLat').value.trim());
        params.append('longitude', document.getElementById('formLng').value.trim());
        params.append('description', document.getElementById('formDesc').value.trim());
        params.append('status', document.getElementById('formStatus').value);
        params.append('sort', document.getElementById('formSort').value);
        params.append('suspended', document.getElementById('formSuspended').value);
        params.append('suspended_reason', document.getElementById('formSuspendReason').value);
        params.append('suspended_until', document.getElementById('formSuspendUntilHidden').value);
        params.append('business_hours', document.getElementById('formOpenTime').value + '-' + document.getElementById('formCloseTime').value);
        params.append('badge', document.getElementById('formBadge').value);
        params.append('images', JSON.stringify(storeImages));
        params.append('license_images', JSON.stringify(licenseImages));

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✅ ' + data.message, 'success');
                closeFormDrawer();
                loadList(editingId ? currentPage : 1);
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        })
        .catch(err => showMessage('❌ 请求失败：' + err.message, 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '确定保存';
        });
    }

    function viewDetail(id) {
        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', id);
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const r = data.data;
                document.getElementById('detailBody').innerHTML = `
                    <div class="form-group"><label>门店名称</label><div>${escapeHtml(r.name)}</div></div>
                    <div class="form-row-2">
                        <div class="form-group"><label>国家</label><div>${escapeHtml(r.country)}</div></div>
                        <div class="form-group"><label>城市</label><div>${escapeHtml(r.city)}</div></div>
                    </div>
                    <div class="form-group"><label>详细地址</label><div>${escapeHtml(r.address || '-')}</div></div>
                    <div class="form-row-2">
                        <div class="form-group"><label>联系电话</label><div>${escapeHtml(r.phone || '-')}</div></div>
                        <div class="form-group"><label>浏览次数</label><div>${r.view_count || 0}</div></div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group"><label>纬度</label><div class="cell-coord">${r.latitude || '-'}</div></div>
                        <div class="form-group"><label>经度</label><div class="cell-coord">${r.longitude || '-'}</div></div>
                    </div>
                    <div class="form-group"><label>门店描述</label><div>${escapeHtml(r.description || '-')}</div></div>
                    <div class="form-row-2">
                        <div class="form-group"><label>状态</label><div>${r.status == 1 ? '<span class="badge badge-success">营业中</span>' : '<span class="badge badge-danger">已停业</span>'}</div></div>
                        <div class="form-group"><label>排序</label><div>${r.sort || 0}</div></div>
                    </div>
                    <div class="form-group"><label>创建时间</label><div>${r.created_at || '-'}</div></div>
                `;
                document.getElementById('detailDrawer').classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                showMessage('❌ ' + data.message, 'error');
            }
        });
    }

    function closeDetailDrawer() {
        var d = document.getElementById('detailDrawer');
        d.classList.add('closing');
        setTimeout(function() { d.classList.remove('show', 'closing'); document.body.style.overflow = ''; }, 300);
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function showLoading(msg) {
        const el = document.createElement('div');
        el.id = 'loadingToast';
        el.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.75);color:white;padding:16px 28px;border-radius:10px;font-size:15px;z-index:10000;';
        el.textContent = msg || '加载中…';
        document.body.appendChild(el);
    }
    function hideLoading() {
        const el = document.getElementById('loadingToast');
        if (el) el.remove();
    }

    function showMessage(msg, type) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (type || 'info');
        alert.textContent = msg;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    // =============== 高德地图选点 ===============
    var mapInstance = null;
    var mapMarker = null;
    var selectedLat = '';
    var selectedLng = '';
    var selectedAddress = '';
    var mapReady = false;

    // 等待高德地图 SDK 加载完成
    function waitAMap(callback) {
        if (typeof AMap !== 'undefined') {
            callback();
        } else {
            setTimeout(function() { waitAMap(callback); }, 200);
        }
    }

    function openMapPicker() {
        document.getElementById('mapModal').classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(function() {
            waitAMap(function() {
                if (!mapInstance) {
                    initAMap();
                }
                // 如果已有坐标，跳到该位置
                var lat = document.getElementById('formLat').value.trim();
                var lng = document.getElementById('formLng').value.trim();
                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    centerMapAt(parseFloat(lng), parseFloat(lat), 16);
                } else {
                    // 尝试定位到当前位置
                    tryGetLocation();
                }
            });
        }, 400);
    }

    function tryGetLocation() {
        if (!navigator.geolocation) {
            centerMapAt(119.9, 30.6, 12);
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                centerMapAt(pos.coords.longitude, pos.coords.latitude, 16);
            },
            function() {
                // 定位失败，用默认中心
                centerMapAt(119.9, 30.6, 12);
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 }
        );
    }

    function centerMapAt(lng, lat, zoom) {
        var pos = [parseFloat(lng), parseFloat(lat)];
        mapInstance.setCenter(pos);
        mapInstance.setZoom(zoom);
        if (mapMarker) {
            mapMarker.setPosition(pos);
        } else {
            mapMarker = new AMap.Marker({
                position: pos,
                draggable: true,
                map: mapInstance
            });
            mapMarker.on('dragend', onAMapMarkerDragEnd);
        }
        selectedLat = lat.toFixed ? lat.toFixed(6) : lat;
        selectedLng = lng.toFixed ? lng.toFixed(6) : lng;
        updateCoordDisplay(selectedLat, selectedLng);
        amapReverseGeocode(selectedLng, selectedLat);
    }

    function closeMapPicker() {
        document.getElementById('mapModal').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('mapSearchInput').value = '';
        var results = document.getElementById('mapSearchResults');
        if (results) results.remove();
    }

    function initAMap() {
        mapInstance = new AMap.Map('mapContainer', {
            center: [119.9, 30.6],
            zoom: 12,
            resizeEnable: true,
            mapStyle: 'amap://styles/light'
        });

        mapInstance.on('click', function(e) {
            var lng = e.lnglat.getLng().toFixed(6);
            var lat = e.lnglat.getLat().toFixed(6);
            var pos = [parseFloat(lng), parseFloat(lat)];

            if (mapMarker) {
                mapMarker.setPosition(pos);
            } else {
                mapMarker = new AMap.Marker({
                    position: pos,
                    draggable: true,
                    map: mapInstance
                });
                mapMarker.on('dragend', onAMapMarkerDragEnd);
            }

            selectedLat = lat;
            selectedLng = lng;
            updateCoordDisplay(lat, lng);
            amapReverseGeocode(lng, lat);
        });

        // 地图加载完成后标记就绪
        mapInstance.on('complete', function() {
            mapReady = true;
        });
    }

    function onAMapMarkerDragEnd(e) {
        var pos = e.target.getPosition();
        var lat = pos.getLat().toFixed(6);
        var lng = pos.getLng().toFixed(6);
        selectedLat = lat;
        selectedLng = lng;
        updateCoordDisplay(lat, lng);
        amapReverseGeocode(lng, lat);
    }

    function updateCoordDisplay(lat, lng) {
        var el = document.getElementById('mapCoordDisplay');
        el.style.display = 'inline';
        el.textContent = '📍 ' + lat + ', ' + lng;
    }

    // 高德逆地理编码
    function amapReverseGeocode(lng, lat) {
        AMap.plugin('AMap.Geocoder', function() {
            var geocoder = new AMap.Geocoder();
            geocoder.getAddress([parseFloat(lng), parseFloat(lat)], function(status, result) {
                if (status === 'complete' && result.info === 'OK') {
                    selectedAddress = result.regeocode.formattedAddress || '';
                    // 自动识别城市
                    var addr = result.regeocode.addressComponent;
                    if (addr) {
                        document.getElementById('formCity').value = (addr.province || '') + (addr.city && addr.city !== addr.province ? '·' + addr.city : '') + (addr.district ? '·' + addr.district : '');
                        document.getElementById('formCountry').value = '中国';
                    }
                }
            });
        });
    }

    // 高德 POI 搜索
    function searchAddress() {
        var keyword = document.getElementById('mapSearchInput').value.trim();
        if (!keyword) return;

        AMap.plugin('AMap.PlaceSearch', function() {
            var placeSearch = new AMap.PlaceSearch({ pageSize: 10, pageIndex: 1 });
            placeSearch.search(keyword, function(status, result) {
                var old = document.getElementById('mapSearchResults');
                if (old) old.remove();

                if (status !== 'complete' || !result.poiList || result.poiList.pois.length === 0) {
                    showMessage('未找到地点', 'error');
                    return;
                }

                var pois = result.poiList.pois;
                var container = document.createElement('div');
                container.id = 'mapSearchResults';
                container.style.cssText = 'margin:0 24px 12px;max-height:180px;overflow-y:auto;background:#fff;border:1px solid #e8e8e8;border-radius:6px;padding:4px;';

                pois.forEach(function(poi) {
                    var div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.textContent = poi.name + ' - ' + (poi.address || poi.pname + poi.cityname);
                    div.addEventListener('click', function() {
                        var lat = poi.location.getLat().toFixed(6);
                        var lng = poi.location.getLng().toFixed(6);
                        var pos = [parseFloat(lng), parseFloat(lat)];

                        selectedLat = lat;
                        selectedLng = lng;
                        selectedAddress = poi.name + ', ' + (poi.address || '');
                    // 自动识别城市
                    document.getElementById('formCity').value = (poi.pname || '') + (poi.cityname && poi.cityname !== poi.pname ? '·' + poi.cityname : '');
                    document.getElementById('formCountry').value = '中国';

                        if (mapMarker) {
                            mapMarker.setPosition(pos);
                        } else {
                            mapMarker = new AMap.Marker({
                                position: pos,
                                draggable: true,
                                map: mapInstance
                            });
                            mapMarker.on('dragend', onAMapMarkerDragEnd);
                        }

                        mapInstance.setCenter(pos);
                        mapInstance.setZoom(16);
                        updateCoordDisplay(lat, lng);
                        document.getElementById('mapSearchInput').value = '';
                        container.remove();
                    });
                    container.appendChild(div);
                });

                document.getElementById('mapModal').querySelector('.modal-content').appendChild(container);
            });
        });
    }

    function confirmMapPick() {
        if (!selectedLat || !selectedLng) {
            showMessage('请先在地图上选择地点', 'error');
            return;
        }
        document.getElementById('formLat').value = selectedLat;
        document.getElementById('formLng').value = selectedLng;
        if (selectedAddress) {
            document.getElementById('formAddress').value = selectedAddress;
        }
        // 如果城市未自动识别，从地址提取
        if (!document.getElementById('formCity').value && selectedAddress) {
            var m = selectedAddress.match(/(\S+?[省市])(\S*?[市区])/);
            if (m) document.getElementById('formCity').value = m[1] + '·' + m[2];
        }
        closeMapPicker();
        showMessage('✅ 地点已选择', 'success');
    }

    // 模态框点击外部关闭
    document.getElementById('formDrawer').addEventListener('click', function(e) {
        if (e.target === this) closeFormDrawer();
    });
    document.getElementById('detailDrawer').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });

    document.getElementById('productEditorDrawer').addEventListener('click', function(e) {
        if (e.target === this) closeProductEditor();
    });
    document.getElementById('mapModal').addEventListener('click', function(e) {
        if (e.target === this) closeMapPicker();
    });
    document.getElementById('productSearchModal').addEventListener('click', function(e) {
        if (e.target === this) closeProductSearch();
    });
    document.getElementById('productSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') searchProducts();
    });

    // 抽屉 TAB 切换
    // 门店抽屉 TAB 切换
    document.getElementById('formDrawerTabs').addEventListener('click', function(e) {
        var tab = e.target.closest('.drawer-tab');
        if (!tab) return;
        var name = tab.getAttribute('data-tab');
        document.querySelectorAll('#formDrawerTabs .drawer-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('#formDrawer .tab-section').forEach(function(s) { s.classList.remove('active'); });
        tab.classList.add('active');
        var section = document.querySelector('#formDrawer .tab-section[data-tab="' + name + '"]');
        if (section) section.classList.add('active');
    });

    // 商品编辑器 TAB 切换
    var pEditorTabs = document.getElementById('productEditorTabs');
    if (pEditorTabs) {
        pEditorTabs.addEventListener('click', function(e) {
            var tab = e.target.closest('.drawer-tab');
            if (!tab) return;
            var name = tab.getAttribute('data-tab');
            document.querySelectorAll('#productEditorTabs .drawer-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('#productEditorDrawer .tab-section').forEach(function(s) { s.classList.remove('active'); });
            tab.classList.add('active');
            var section = document.querySelector('#productEditorDrawer .tab-section[data-tab="' + name + '"]');
            if (section) section.classList.add('active');
        });
    }

    // 绑定回车搜索
    var kwInput = document.getElementById('searchKeyword');
    if (kwInput) kwInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') loadList(1);
    });

    // 加载商品分类
    function loadProductCategories() {
        var params = new URLSearchParams();
        params.append('action', 'product_categories');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var sel = document.getElementById('pCategory');
                    data.data.forEach(function(c) {
                        var opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        sel.appendChild(opt);
                    });
                }
            })
            .catch(function() {});
    }
    loadProductCategories();

    // 初始加载
    loadList(1);

    /* ===== 等级评定弹窗 ===== */
    function showLevelDetail() {
        showLevelDetailById(editingId);
    }

    function showLevelDetailById(storeId) {
        if (!storeId) { showMessage('请选择门店', 'info'); return; }

        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', storeId);
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showMessage('加载失败', 'error'); return; }
                const r = data.data;
                let detail = null;
                try { detail = r.level_detail ? JSON.parse(r.level_detail) : null; } catch(e) {}
                
                let html = '<div style="padding:10px 0;">';
                html += '<div style="text-align:center;margin-bottom:20px;">';
                html += '<div style="font-size:32px;margin-bottom:4px;">' + (detail ? levelIcon(detail.level_badge) : '🏪') + '</div>';
                html += '<div style="font-size:20px;font-weight:700;color:#262626;">' + escapeHtml(r.level || '未评定') + '</div>';
                html += '<div style="font-size:14px;color:#8c8c8c;">综合评分：<strong style="color:#1890ff;font-size:18px;">' + (r.level_score || 0) + '</strong> / 100</div>';
                if (r.level_updated_at) html += '<div style="font-size:12px;color:#bbb;margin-top:4px;">评定时间：' + r.level_updated_at + '</div>';
                html += '</div>';

                if (detail && detail.dimensions) {
                    html += '<div style="border-top:1px solid #f0f0f0;padding-top:16px;">';
                    html += '<div style="font-size:14px;font-weight:600;color:#262626;margin-bottom:12px;">📊 五维评分明细</div>';
                    detail.dimensions.forEach(function(d) {
                        var pct = Math.round(d.score / d.full * 100);
                        var barColor = pct >= 80 ? '#52c41a' : (pct >= 60 ? '#1890ff' : (pct >= 40 ? '#faad14' : '#ff4d4f'));
                        html += '<div style="margin-bottom:14px;">';
                        html += '<div style="display:flex;justify-content:space-between;font-size:13px;">';
                        html += '<span style="color:#262626;font-weight:500;">' + escapeHtml(d.name) + '</span>';
                        html += '<span>' + d.score + '/' + d.full + ' <span style="color:' + barColor + ';font-weight:600;">(' + pct + '%)</span></span>';
                        html += '</div>';
                        html += '<div style="height:8px;background:#f0f0f0;border-radius:4px;margin-top:4px;overflow:hidden;">';
                        html += '<div style="height:100%;width:' + pct + '%;background:' + barColor + ';border-radius:4px;transition:width 0.3s;"></div>';
                        html += '</div>';
                        html += '<div style="font-size:12px;color:#8c8c8c;margin-top:2px;">' + escapeHtml(d.desc || '') + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                html += '</div>';

                // 弹窗
                const overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
                overlay.className = 'modal-overlay';
                overlay.onclick = function(e) { if (e.target === overlay) overlay.remove(); };
                overlay.innerHTML = '<div style="background:white;border-radius:16px;padding:28px;width:440px;max-width:90vw;max-height:85vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.15);position:relative;">' +
                    '<div onclick="this.closest(\'.modal-overlay\').remove()" style="position:absolute;top:12px;right:16px;font-size:24px;cursor:pointer;color:#8c8c8c;line-height:1;">×</div>' +
                    html + '</div>';
                document.body.appendChild(overlay);
            })
            .catch(function(err) { showMessage('❌ 加载失败：' + err.message, 'error'); });
    }

    function levelIcon(badge) {
        switch(badge) {
            case 'head': return '👑';
            case 'benchmark': return '🏆';
            case 'premium': return '🥇';
            case 'standard': return '🥈';
            case 'basic': return '🌱';
            default: return '🏪';
        }
    }

    function calcLevel() {
        const storeId = editingId;
        if (!storeId) { showMessage('请先打开门店', 'info'); return; }
        if (!confirm('确定重新评定该门店等级？')) return;
        
        const params = new URLSearchParams();
        params.append('action', 'calc_level');
        params.append('id', storeId);
        showLoading('评定中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showMessage('✅ 等级评定完成', 'success');
                    openEditDrawer(storeId);
                } else {
                    showMessage('❌ ' + (data.message || '评定失败'), 'error');
                }
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    /* ===== 星级评分 ===== */
    function showStarDetail() {
        const storeId = editingId;
        if (!storeId) { showMessage('请先打开门店', 'info'); return; }

        const params = new URLSearchParams();
        params.append('action', 'detail');
        params.append('id', storeId);
        showLoading('加载中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (!data.success) { showMessage('加载失败', 'error'); return; }
                const r = data.data;
                let detail = null;
                try { detail = r.star_detail ? JSON.parse(r.star_detail) : null; } catch(e) {}

                let html = '<div style="padding:10px 0;">';
                // 头部：综合星级
                html += '<div style="text-align:center;margin-bottom:20px;">';
                const rating = parseFloat(r.star_rating) || 0;
                if (rating > 0) {
                    const full = Math.floor(rating);
                    const half = rating - full >= 0.5;
                    const stars = '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(Math.max(0, 5 - full - (half ? 1 : 0)));
                    html += '<div style="font-size:40px;color:#faad14;margin-bottom:4px;">' + stars + '</div>';
                    html += '<div style="font-size:28px;font-weight:700;color:#262626;">' + rating.toFixed(1) + ' / 5.0</div>';
                    if (detail && detail.star_level) html += '<div style="font-size:14px;color:#8c8c8c;margin-top:4px;">' + escapeHtml(detail.star_level) + '</div>';
                } else {
                    html += '<div style="font-size:40px;color:#d9d9d9;">☆</div>';
                    html += '<div style="font-size:20px;color:#8c8c8c;">暂无评分</div>';
                }
                html += '</div>';

                // 五维评分明细
                if (detail && detail.dimensions) {
                    html += '<div style="border-top:1px solid #f0f0f0;padding-top:16px;">';
                    html += '<div style="font-size:14px;font-weight:600;color:#262626;margin-bottom:12px;">📊 五维加权评分（满分10分）</div>';
                    detail.dimensions.forEach(function(d) {
                        var pct = Math.round(d.score / d.full * 100);
                        var barColor = pct >= 80 ? '#52c41a' : (pct >= 60 ? '#1890ff' : (pct >= 40 ? '#faad14' : '#ff4d4f'));
                        html += '<div style="margin-bottom:14px;">';
                        html += '<div style="display:flex;justify-content:space-between;font-size:13px;">';
                        html += '<span style="color:#262626;font-weight:500;">' + escapeHtml(d.name) + ' <span style="color:#8c8c8c;font-weight:400;font-size:12px;">(' + d.weight + ')</span></span>';
                        html += '<span>' + d.score + '/' + d.full + ' <span style="color:' + barColor + ';font-weight:600;">(' + pct + '%)</span></span>';
                        html += '</div>';
                        html += '<div style="height:8px;background:#f0f0f0;border-radius:4px;margin-top:4px;overflow:hidden;">';
                        html += '<div style="height:100%;width:' + pct + '%;background:' + barColor + ';border-radius:4px;transition:width 0.3s;"></div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                // 星级标准说明
                html += '<div style="border-top:1px solid #f0f0f0;padding-top:16px;">';
                html += '<div style="font-size:13px;font-weight:600;color:#262626;margin-bottom:8px;">📖 星级判定标准</div>';
                html += '<div style="font-size:12px;color:#8c8c8c;line-height:1.8;">';
                html += '5⭐ <strong>4.8~5.0</strong> 非常满意（最优体验）<br>';
                html += '4⭐ <strong>4.0~4.7</strong> 满意（良好体验）<br>';
                html += '3⭐ <strong>3.0~3.9</strong> 一般（合格体验）<br>';
                html += '2⭐ <strong>2.0~2.9</strong> 不满意（较差体验）<br>';
                html += '1⭐ <strong>1.0~1.9</strong> 非常不满意（极差体验）';
                html += '</div></div>';

                

                // 弹窗
                const overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
                overlay.className = 'modal-overlay';
                overlay.onclick = function(e) { if (e.target === overlay) overlay.remove(); };
                overlay.innerHTML = '<div style="background:white;border-radius:16px;padding:28px;width:480px;max-width:90vw;max-height:85vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.15);position:relative;">' +
                    '<div onclick="this.closest(\'.modal-overlay\').remove()" style="position:absolute;top:12px;right:16px;font-size:24px;cursor:pointer;color:#8c8c8c;line-height:1;">×</div>' +
                    html + '</div>';
                document.body.appendChild(overlay);
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 加载失败：' + err.message, 'error'); });
    }

    function calcStar() {
        const storeId = editingId;
        if (!storeId) { showMessage('请先打开门店', 'info'); return; }
        if (!confirm('确定重新评定该门店星级？')) return;

        const params = new URLSearchParams();
        params.append('action', 'calc_star');
        params.append('id', storeId);
        showLoading('评定中...');
        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params.toString() })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showMessage('✅ ' + (data.message || '星级评定完成'), 'success');
                    openEditDrawer(storeId);
                } else {
                    showMessage('❌ ' + (data.message || '评定失败'), 'error');
                }
            })
            .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }
    /* ===== ====== ===== */
    /* ===== 活动管理 - 优惠券 ===== */
    /* ===== ====== ===== */

    let couponPage = 1;
    let couponTotal = 0;
    let couponPageSize = 20;

    // 打开门店抽屉时加载优惠券
    function loadCoupons(page) {
        couponPage = page || 1;
        var container = document.getElementById('couponList');
        if (!container) return;
        container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</td></tr>';

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: 'action=coupon_list&page=' + couponPage + '&pageSize=' + couponPageSize
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#ff4d4f;">加载失败：' + (data.message || '') + '</td></tr>';
                return;
            }
            var rows = data.data || [];
            couponTotal = data.total || 0;
            if (rows.length === 0) {
                container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#8c8c8c;">暂无优惠券数据</td></tr>';
                document.getElementById('couponPagination').style.display = 'none';
                return;
            }
            var html = '';
            for (var i = 0; i < rows.length; i++) {
                var c = rows[i];
                var typeLabel = c.type === 'discount' ? '折扣' : (c.type === 'cash' ? '代金券' : (c.type === 'shipping' ? '包邮' : c.type));
                var valueStr = c.type === 'discount' ? (c.value + '折') : '¥' + parseFloat(c.value).toFixed(2);
                var statusLabel = c.status == 1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>';
                var timeStr = (c.start_time || '不限') + ' ~ ' + (c.end_time || '不限');
                var remain = parseInt(c.total) - parseInt(c.received || 0);
                html += '<tr>' +
                    '<td>' + c.id + '</td>' +
                    '<td style="text-align:left;"><strong>' + c.name + '</strong>' + (c.description ? '<br><span style="font-size:11px;color:#8c8c8c;">' + c.description + '</span>' : '') + '</td>' +
                    '<td>' + typeLabel + '</td>' +
                    '<td style="font-weight:600;color:#f5222d;">' + valueStr + '</td>' +
                    '<td>' + (c.min_amount > 0 ? '¥' + parseFloat(c.min_amount).toFixed(2) : '无') + '</td>' +
                    '<td>' + (c.total > 0 ? remain + '/' + c.total : '不限') + '</td>' +
                    '<td>' + parseInt(c.received || 0) + '/' + parseInt(c.used || 0) + '</td>' +
                    '<td style="font-size:12px;">' + timeStr + '</td>' +
                    '<td>' + statusLabel + '</td>' +
                    '<td>' +
                        '<button class="action-btn edit" onclick="openCouponEditor(' + c.id + ')">编辑</button>' +
                        '<button class="action-btn delete" onclick="deleteCoupon(' + c.id + ')">删除</button>' +
                    '</td>' +
                    '</tr>';
            }
            container.innerHTML = html;

            // 分页
            var totalPages = Math.ceil(couponTotal / couponPageSize);
            if (totalPages <= 1) {
                document.getElementById('couponPagination').style.display = 'none';
            } else {
                document.getElementById('couponPagination').style.display = 'flex';
                var phtml = '<span class="pag-info">共 ' + couponTotal + ' 条</span>';
                if (couponPage > 1) phtml += '<span class="pag-btn" onclick="loadCoupons(' + (couponPage - 1) + ')">‹ 上一页</span>';
                for (var p = Math.max(1, couponPage - 2); p <= Math.min(totalPages, couponPage + 2); p++) {
                    phtml += '<span class="pag-btn' + (p === couponPage ? ' active' : '') + '" onclick="loadCoupons(' + p + ')">' + p + '</span>';
                }
                if (couponPage < totalPages) phtml += '<span class="pag-btn" onclick="loadCoupons(' + (couponPage + 1) + ')">下一页 ›</span>';
                document.getElementById('couponPagination').innerHTML = phtml;
            }
        })
        .catch(function(err) {
            container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#ff4d4f;">请求失败</td></tr>';
        });
    }

    // 打开优惠券编辑窗口
    function openCouponEditor(id) {
        var title = id ? '编辑优惠券' : '新增优惠券';
        var html = '<div class="modal-header">' + title + '</div>' +
            '<div class="modal-body">' +
            '<div class="form-group"><label>优惠券名称 <span class="required">*</span></label><input type="text" id="couponName" placeholder="如：满100减20"></div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>类型</label><select id="couponType"><option value="cash">代金券</option><option value="discount">折扣券</option><option value="shipping">包邮券</option></select></div>' +
                '<div class="form-group"><label>面值/折扣</label><input type="number" id="couponValue" step="0.01" placeholder="代金券填金额，折扣券填折扣数"></div>' +
            '</div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>最低消费</label><input type="number" id="couponMinAmount" step="0.01" value="0" placeholder="0 表示无限制"></div>' +
                '<div class="form-group"><label>最高抵扣</label><input type="number" id="couponMaxAmount" step="0.01" value="" placeholder="不填表示不限制"></div>' +
            '</div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>总库存</label><input type="number" id="couponTotal" value="100" placeholder="0 表示不限"></div>' +
                '<div class="form-group"><label>每人限领</label><input type="number" id="couponPerLimit" value="1" placeholder="每人限领数量"></div>' +
            '</div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>开始时间</label><input type="datetime-local" id="couponStartTime"></div>' +
                '<div class="form-group"><label>结束时间</label><input type="datetime-local" id="couponEndTime"></div>' +
            '</div>' +
            '<div class="form-group"><label>使用说明</label><textarea id="couponInstructions" rows="2" placeholder="可选，使用说明"></textarea></div>' +
            '<div class="form-group"><label>状态</label><select id="couponStatus"><option value="1">启用</option><option value="0">禁用</option></select></div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button class="btn btn-default" onclick="closeCouponModal()">取消</button>' +
            '<button class="btn btn-primary" onclick="saveCoupon(' + (id || 0) + ')">保存</button>' +
            '</div>';

        var modal = document.createElement('div');
        modal.className = 'modal show';
        modal.id = 'couponModal';
        modal.innerHTML = '<div class="modal-content" style="max-width:550px;">' + html + '</div>';
        document.body.appendChild(modal);

        if (id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
                body: 'action=coupon_detail&id=' + id
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var c = data.data;
                    document.getElementById('couponName').value = c.name || '';
                    document.getElementById('couponType').value = c.type || 'cash';
                    document.getElementById('couponValue').value = c.value || 0;
                    document.getElementById('couponMinAmount').value = c.min_amount || 0;
                    document.getElementById('couponMaxAmount').value = c.max_amount || '';
                    document.getElementById('couponTotal').value = c.total || 0;
                    document.getElementById('couponPerLimit').value = c.per_limit || 1;
                    document.getElementById('couponInstructions').value = c.instructions || '';
                    document.getElementById('couponStatus').value = c.status || 1;
                    if (c.start_time) document.getElementById('couponStartTime').value = c.start_time.substring(0, 16);
                    if (c.end_time) document.getElementById('couponEndTime').value = c.end_time.substring(0, 16);
                }
            });
        }
    }

    function closeCouponModal() {
        var el = document.getElementById('couponModal');
        if (el) { el.remove(); }
    }

    function saveCoupon(id) {
        var name = document.getElementById('couponName').value.trim();
        if (!name) { showMessage('请输入优惠券名称', 'error'); return; }

        var data = {
            action: id ? 'coupon_edit' : 'coupon_add',
            id: id,
            name: name,
            type: document.getElementById('couponType').value,
            value: parseFloat(document.getElementById('couponValue').value) || 0,
            min_amount: parseFloat(document.getElementById('couponMinAmount').value) || 0,
            max_amount: parseFloat(document.getElementById('couponMaxAmount').value) || '',
            total: parseInt(document.getElementById('couponTotal').value) || 0,
            per_limit: parseInt(document.getElementById('couponPerLimit').value) || 1,
            start_time: document.getElementById('couponStartTime').value || '',
            end_time: document.getElementById('couponEndTime').value || '',
            instructions: document.getElementById('couponInstructions').value.trim(),
            status: parseInt(document.getElementById('couponStatus').value) || 1
        };

        var params = Object.keys(data).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
        }).join('&');

        showLoading();
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: params
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            hideLoading();
            if (res.success) {
                showMessage(res.message || '操作成功', 'success');
                closeCouponModal();
                loadCoupons(couponPage);
            } else {
                showMessage('❌ ' + (res.message || '操作失败'), 'error');
            }
        })
        .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    function deleteCoupon(id) {
        if (!confirm('确定要删除该优惠券吗？')) return;
        showLoading();
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: 'action=coupon_delete&id=' + id
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            hideLoading();
            if (res.success) {
                showMessage('删除成功', 'success');
                loadCoupons(couponPage);
            } else {
                showMessage('❌ ' + (res.message || '删除失败'), 'error');
            }
        })
        .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    /* 在打开门店抽屉时，切换到福利tab自动加载优惠券 */
    /* 在 switchDrawerTab 中触发加载 */
    var origShowTab = window.showTab || function(){};
    /* 活动管理子Tab切换 */
    function switchBenefitTab(subtab) {
        // 切换tab样式
        var tabs = document.querySelectorAll('.benefit-sub-tab');
        for (var i = 0; i < tabs.length; i++) {
            var t = tabs[i];
            if (t.getAttribute('data-subtab') === subtab) {
                t.style.background = '#1890ff';
                t.style.color = '#fff';
            } else {
                t.style.background = '#f5f5f5';
                t.style.color = '#595959';
            }
        }
        // 切换内容区
        var sections = document.querySelectorAll('.benefit-section');
        for (var j = 0; j < sections.length; j++) {
            sections[j].style.display = sections[j].getAttribute('data-subtab') === subtab ? 'block' : 'none';
        }
        // 加载对应数据
        if (subtab === 'coupon') loadCoupons(1);
        else if (subtab === 'seckill') loadSeckills(1);
    }

    /* 监听活动管理主Tab点击 - 默认加载优惠券 */
    var tabHandlers = window.tabHandlers || {};
    window.tabHandlers = tabHandlers;
    tabHandlers.benefits = function() { switchBenefitTab('coupon'); };

    document.addEventListener('click', function(e) {
        var tab = e.target.closest && e.target.closest('.drawer-tab[data-tab="benefits"]');
        if (tab) {
            setTimeout(function() { switchBenefitTab('coupon'); }, 100);
        }
    });

    /* ===== ====== ===== */
    /* ===== 秒杀 ===== */
    /* ===== ====== ===== */

    var seckillPage = 1, seckillTotal = 0, seckillPageSize = 20;

    function loadSeckills(page) {
        seckillPage = page || 1;
        var container = document.getElementById('seckillList');
        if (!container) return;
        container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#8c8c8c;">加载中...</td></tr>';

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: 'action=seckill_list&page=' + seckillPage + '&pageSize=' + seckillPageSize
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#ff4d4f;">加载失败</td></tr>';
                return;
            }
            var rows = data.data || [];
            seckillTotal = data.total || 0;
            if (rows.length === 0) {
                container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#8c8c8c;">暂无秒杀活动</td></tr>';
                document.getElementById('seckillPagination').style.display = 'none';
                return;
            }
            var html = '';
            for (var i = 0; i < rows.length; i++) {
                var s = rows[i];
                var statusLabel = s.status == 1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>';
                html += '<tr>' +
                    '<td>' + s.id + '</td>' +
                    '<td style="text-align:left;"><strong>' + escapeHtml(s.name) + '</strong>' + (s.description ? '<br><span style="font-size:11px;color:#8c8c8c;">' + escapeHtml(s.description) + '</span>' : '') + '</td>' +
                    '<td style="font-weight:600;color:#f5222d;">¥' + parseFloat(s.seckill_price).toFixed(2) + '</td>' +
                    '<td>¥' + parseFloat(s.original_price).toFixed(2) + '</td>' +
                    '<td>' + (parseInt(s.stock) - parseInt(s.sold || 0)) + '/' + s.stock + '</td>' +
                    '<td>' + (s.limit_buy || 1) + '件</td>' +
                    '<td style="font-size:12px;">' + (s.start_time || '-') + '</td>' +
                    '<td style="font-size:12px;">' + (s.end_time || '-') + '</td>' +
                    '<td>' + statusLabel + '</td>' +
                    '<td>' +
                        '<button class="action-btn edit" onclick="openSeckillEditor(' + s.id + ')">编辑</button>' +
                        '<button class="action-btn delete" onclick="deleteSeckill(' + s.id + ')">删除</button>' +
                    '</td>' +
                    '</tr>';
            }
            container.innerHTML = html;

            var totalPages = Math.ceil(seckillTotal / seckillPageSize);
            if (totalPages <= 1) {
                document.getElementById('seckillPagination').style.display = 'none';
            } else {
                document.getElementById('seckillPagination').style.display = 'flex';
                var phtml = '<span class="pag-info">共 ' + seckillTotal + ' 条</span>';
                if (seckillPage > 1) phtml += '<span class="pag-btn" onclick="loadSeckills(' + (seckillPage - 1) + ')">‹ 上一页</span>';
                for (var p = Math.max(1, seckillPage - 2); p <= Math.min(totalPages, seckillPage + 2); p++) {
                    phtml += '<span class="pag-btn' + (p === seckillPage ? ' active' : '') + '" onclick="loadSeckills(' + p + ')">' + p + '</span>';
                }
                if (seckillPage < totalPages) phtml += '<span class="pag-btn" onclick="loadSeckills(' + (seckillPage + 1) + ')">下一页 ›</span>';
                document.getElementById('seckillPagination').innerHTML = phtml;
            }
        })
        .catch(function(err) {
            container.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:40px;color:#ff4d4f;">请求失败</td></tr>';
        });
    }

    function openSeckillEditor(id) {
        var title = id ? '编辑秒杀活动' : '新增秒杀活动';
        var html = '<div class="modal-header">' + title + '</div>' +
            '<div class="modal-body">' +
            '<div class="form-group"><label>活动名称 <span class="required">*</span></label><input type="text" id="skName" placeholder="如：暑期特惠秒杀"></div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>秒杀价</label><input type="number" id="skPrice" step="0.01" value="0" placeholder="秒杀价格"></div>' +
                '<div class="form-group"><label>原价</label><input type="number" id="skOriginal" step="0.01" value="0" placeholder="商品原价"></div>' +
            '</div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>库存</label><input type="number" id="skStock" value="100" placeholder="秒杀库存"></div>' +
                '<div class="form-group"><label>每人限购</label><input type="number" id="skLimit" value="1" placeholder="每人限购数量"></div>' +
            '</div>' +
            '<div class="form-row-2">' +
                '<div class="form-group"><label>开始时间</label><input type="datetime-local" id="skStartTime"></div>' +
                '<div class="form-group"><label>结束时间</label><input type="datetime-local" id="skEndTime"></div>' +
            '</div>' +
            '<div class="form-group"><label>活动说明</label><textarea id="skDesc" rows="2" placeholder="可选"></textarea></div>' +
            '<div class="form-group"><label>状态</label><select id="skStatus"><option value="1">启用</option><option value="0">禁用</option></select></div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button class="btn btn-default" onclick="closeSeckillModal()">取消</button>' +
            '<button class="btn btn-primary" onclick="saveSeckill(' + (id || 0) + ')">保存</button>' +
            '</div>';

        var modal = document.createElement('div');
        modal.className = 'modal show';
        modal.id = 'seckillModal';
        modal.innerHTML = '<div class="modal-content" style="max-width:550px;">' + html + '</div>';
        document.body.appendChild(modal);

        if (id) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
                body: 'action=seckill_detail&id=' + id
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var s = data.data;
                    document.getElementById('skName').value = s.name || '';
                    document.getElementById('skPrice').value = s.seckill_price || 0;
                    document.getElementById('skOriginal').value = s.original_price || 0;
                    document.getElementById('skStock').value = s.stock || 0;
                    document.getElementById('skLimit').value = s.limit_buy || 1;
                    document.getElementById('skStatus').value = s.status || 1;
                    document.getElementById('skDesc').value = s.description || '';
                    if (s.start_time) document.getElementById('skStartTime').value = s.start_time.substring(0, 16);
                    if (s.end_time) document.getElementById('skEndTime').value = s.end_time.substring(0, 16);
                }
            });
        }
    }

    function closeSeckillModal() {
        var el = document.getElementById('seckillModal');
        if (el) el.remove();
    }

    function saveSeckill(id) {
        var name = document.getElementById('skName').value.trim();
        if (!name) { showMessage('请输入活动名称', 'error'); return; }

        var data = {
            action: id ? 'seckill_edit' : 'seckill_add',
            id: id,
            name: name,
            seckill_price: parseFloat(document.getElementById('skPrice').value) || 0,
            original_price: parseFloat(document.getElementById('skOriginal').value) || 0,
            stock: parseInt(document.getElementById('skStock').value) || 0,
            limit_buy: parseInt(document.getElementById('skLimit').value) || 1,
            start_time: document.getElementById('skStartTime').value || '',
            end_time: document.getElementById('skEndTime').value || '',
            description: document.getElementById('skDesc').value.trim(),
            status: parseInt(document.getElementById('skStatus').value) || 1
        };

        var params = Object.keys(data).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
        }).join('&');

        showLoading();
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: params
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            hideLoading();
            if (res.success) {
                showMessage(res.message || '操作成功', 'success');
                closeSeckillModal();
                loadSeckills(seckillPage);
            } else {
                showMessage('❌ ' + (res.message || '操作失败'), 'error');
            }
        })
        .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }

    function deleteSeckill(id) {
        if (!confirm('确定要删除该秒杀活动吗？')) return;
        showLoading();
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'},
            body: 'action=seckill_delete&id=' + id
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            hideLoading();
            if (res.success) {
                showMessage('删除成功', 'success');
                loadSeckills(seckillPage);
            } else {
                showMessage('❌ ' + (res.message || '删除失败'), 'error');
            }
        })
        .catch(function(err) { hideLoading(); showMessage('❌ 请求失败：' + err.message, 'error'); });
    }
    /* ===== ====== ===== */
    /* ===== ====== ===== */
    </script>
</body>
</html>
