<?php
/**
 * 商品管理列表页面
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '商品管理';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$code = trim($_GET['code'] ?? '');
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? ''; // 空：全部，0:已下架，1:出售中，2:已售罄

// 构建查询条件
$where = ['1=1'];
$params = [];
$paramTypes = [];

if ($keyword) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

if ($code) {
    $where[] = 'p.code = ?';
    $params[] = $code;
}

if ($category) {
    $where[] = 'p.category_id = ?';
    $params[] = $category;
}

if ($status !== '') {
    if ($status == '0') {
        $where[] = 'p.status = ?';
        $params[] = 0;
    } elseif ($status == '1') {
        $where[] = 'p.status = ?';
        $params[] = 1;
    } elseif ($status == '2') {
        $where[] = 'p.stock = ?';
        $params[] = 0;
    }
}

$whereStr = implode(' AND ', $where);

// 获取商品分类列表
$categorySql = "SELECT id, name FROM product_categories ORDER BY sort DESC, id DESC";
$categories = $db->fetchAll($categorySql);

// 获取总数
$totalSql = "SELECT COUNT(*) as count FROM products p WHERE {$whereStr}";
$totalResult = $db->fetchOne($totalSql, $params);
$total = $totalResult['count'] ?? 0;
$totalPages = ceil($total / $pageSize);

// 获取商品列表
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.id 
        WHERE {$whereStr} 
        ORDER BY p.sort DESC, p.created_at DESC 
        LIMIT {$pageSize} OFFSET {$offset}";
$products = $db->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理 - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
        .content-wrapper {
            flex: 1;
            padding: 24px;
        }
        
        /* 搜索面板 */
        .search-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }
        
        .form-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-item label {
            font-size: 13px;
            color: #8c8c8c;
            font-weight: 500;
        }
        
        .form-item input,
        .form-item select {
            padding: 8px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            min-width: 160px;
            transition: all 0.3s;
        }
        
        .form-item input:focus,
        .form-item select:focus {
            border-color: #1890ff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
        }
        
        /* 快捷筛选 */
        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .filter-tabs label {
            font-size: 14px;
            color: #8c8c8c;
            font-weight: 500;
        }
        
        .filter-btn {
            padding: 6px 16px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            border-color: #1890ff;
            color: #1890ff;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            color: white;
            border-color: transparent;
        }
        
        /* 数据表格 */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #fafafa;
            font-weight: 600;
            color: #262626;
            font-size: 14px;
            white-space: nowrap;
        }
        
        td {
            font-size: 14px;
            color: #595959;
        }
        
        tr:hover {
            background: #fafafa;
        }
        
        /* 商品图片 */
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }
        
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .product-name {
            font-weight: 600;
            color: #262626;
        }
        
        .product-category {
            font-size: 12px;
            color: #8c8c8c;
        }
        
        /* 价格 */
        .price {
            color: #ff4d4f;
            font-weight: 600;
            font-size: 15px;
        }
        
        /* 状态标签 */
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-success {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }
        
        .badge-danger {
            background: #fff1f0;
            color: #ff4d4f;
            border: 1px solid #ffa39e;
        }
        
        .badge-warning {
            background: #fffbe6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }
        
        /* 拨动开关 */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 22px;
            background: #ccc;
            border-radius: 11px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle-switch.active {
            background: linear-gradient(135deg, #52c41a, #95de64);
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
        
        .toggle-switch.active::after {
            transform: translateX(22px);
        }
        
        /* 操作按钮 */
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 4px 10px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn-edit {
            background: #e6f7ff;
            color: #1890ff;
            border: 1px solid #91d5ff;
        }
        
        .action-btn-edit:hover {
            background: #1890ff;
            color: white;
        }
        
        .action-btn-delete {
            background: #fff1f0;
            color: #ff4d4f;
            border: 1px solid #ffa39e;
        }
        
        .action-btn-delete:hover {
            background: #ff4d4f;
            color: white;
        }
        
        /* 分页 */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            text-decoration: none;
            color: #262626;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #1890ff;
            color: white;
            border-color: #1890ff;
        }
        
        .pagination .active {
            background: #1890ff;
            color: white;
            border-color: #1890ff;
            font-weight: 600;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* 批量操作栏 */
        .batch-toolbar {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        
        .batch-toolbar.show {
            display: flex;
        }
        
        .batch-actions {
            display: flex;
            gap: 12px;
        }
        
        /* 工具栏按钮 */
        .toolbar-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        /* 按钮样式 - 蓝色渐变 */
        .btn-primary {
            background: linear-gradient(135deg, #1890ff, #40a9ff);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #096dd9, #1890ff);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #52c41a, #73d13d);
            color: white;
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #389e0d, #52c41a);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(82, 196, 26, 0.4);
        }
        
        .btn-outline {
            background: white;
            border: 1px solid #d9d9d9;
            color: #262626;
        }
        
        .btn-outline:hover {
            border-color: #1890ff;
            color: #1890ff;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff4d4f, #ff7875);
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #f5222d, #ff4d4f);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 77, 79, 0.4);
        }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- 搜索面板 -->
            <div class="search-panel">
                <form method="GET" class="search-form">
                    <div class="form-item">
                        <label>商品名称</label>
                        <input type="text" name="keyword" placeholder="请输入商品名称" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                    
                    <div class="form-item">
                        <label>商品编码</label>
                        <input type="text" name="code" placeholder="请输入商品编码" value="<?= htmlspecialchars($code) ?>">
                    </div>
                    
                    <div class="form-item">
                        <label>商品分类</label>
                        <select name="category">
                            <option value="">全部分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-item" style="flex-direction: row; gap: 8px;">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <button type="button" class="btn btn-outline" onclick="resetSearch()">重置</button>
                    </div>
                    
                    <div class="form-item" style="flex-direction: row; gap: 8px; margin-left: auto;">
                        <a href="product_edit.php" class="btn btn-success">✚ 新增商品</a>
                        <button type="button" class="btn btn-outline" onclick="batchImport()">批量导入</button>
                        <button type="button" class="btn btn-outline" onclick="batchExport()">批量导出</button>
                    </div>
                </form>
            </div>
            
            <!-- 快捷筛选 -->
            <div class="filter-tabs">
                <label>快捷筛选：</label>
                <button class="filter-btn <?= $status === '' ? 'active' : '' ?>" onclick="filterByStatus('')">全部</button>
                <button class="filter-btn <?= $status === '1' ? 'active' : '' ?>" onclick="filterByStatus('1')">出售中</button>
                <button class="filter-btn <?= $status === '0' ? 'active' : '' ?>" onclick="filterByStatus('0')">已下架</button>
                <button class="filter-btn <?= $status === '2' ? 'active' : '' ?>" onclick="filterByStatus('2')">已售罄</button>
            </div>
            
            <!-- 批量操作栏 -->
            <div class="batch-toolbar" id="batchToolbar">
                <span>已选择 <strong id="selectedCount">0</strong> 个商品</span>
                <div class="batch-actions">
                    <button class="btn btn-primary" onclick="batchSetStatus(1)">批量上架</button>
                    <button class="btn btn-danger" onclick="batchSetStatus(0)">批量下架</button>
                    <button class="btn btn-danger" onclick="batchDelete()">批量删除</button>
                </div>
            </div>
            
            <!-- 数据表格 -->
            <div class="data-table">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                                </th>
                                <th width="80">ID</th>
                                <th width="280">商品信息</th>
                                <th width="120">价格</th>
                                <th width="100">总销量</th>
                                <th width="100">库存总量</th>
                                <th width="100">上下架</th>
                                <th width="100">状态</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #8c8c8c;">
                                    暂无商品数据
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $images = json_decode($product['images'] ?? '[]', true);
                                    $firstImage = $images[0] ?? '';
                                    $isOnSale = $product['status'] == 1;
                                    $isSoldOut = $product['stock'] == 0;
                                    ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="product-checkbox" value="<?= $product['id'] ?>" onchange="updateBatchToolbar()">
                                    </td>
                                    <td><?= $product['id'] ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($firstImage): ?>
                                                <img src="<?= htmlspecialchars($firstImage) ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📦</div>
                                            <?php endif; ?>
                                            <div class="product-info">
                                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                                <?php if ($product['category_name']): ?>
                                                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price">¥<?= number_format($product['price'], 2) ?></div>
                                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                            <div style="font-size: 12px; color: #8c8c8c; text-decoration: line-through;">¥<?= number_format($product['original_price'], 2) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $product['sales'] ?? 0 ?></td>
                                    <td>
                                        <span style="color: <?= ($product['stock'] ?? 0) == 0 ? '#ff4d4f' : '#52c41a' ?>; font-weight: 600;">
                                            <?= $product['stock'] ?? 0 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="toggle-switch <?= $isOnSale ? 'active' : '' ?>" 
                                             onclick="toggleStatus(<?= $product['id'] ?>, <?= $isOnSale ? 0 : 1 ?>)">
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isSoldOut): ?>
                                            <span class="badge badge-warning">已售罄</span>
                                        <?php elseif ($isOnSale): ?>
                                            <span class="badge badge-success">出售中</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">已下架</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="product_edit.php?id=<?= $product['id'] ?>" class="action-btn action-btn-edit">编辑</a>
                                            <a href="javascript:void(0)" class="action-btn action-btn-delete" onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')">删除</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 分页 -->
            <?php 
            // 调试信息（可删除）
            // echo "<!-- 总数：$total, 每页：$pageSize, 总页数：$totalPages -->";
            ?>
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?<?= buildQueryString(['page' => 1]) ?>" class="<?= $page == 1 ? 'active' : '' ?>">首页</a>
                
                <?php if ($page > 1): ?>
                    <a href="?<?= buildQueryString(['page' => $page - 1]) ?>">上一页</a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                    if ($i == $page) continue; // 跳过当前页（已经在最前面显示）
                ?>
                    <a href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= buildQueryString(['page' => $page + 1]) ?>">下一页</a>
                <?php endif; ?>
                
                <a href="?<?= buildQueryString(['page' => $totalPages]) ?>" class="<?= $page == $totalPages ? 'active' : '' ?>">末页</a>
            </div>
            <?php else: ?>
            <!-- 只有一页时也显示分页，显示当前页 -->
            <div class="pagination">
                <span class="active">1</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
        // 重置搜索
        function resetSearch() {
            window.location.href = 'index.php';
        }
        
        // 快捷筛选
        function filterByStatus(status) {
            const params = new URLSearchParams(window.location.search);
            if (status === '') {
                params.delete('status');
            } else {
                params.set('status', status);
            }
            params.set('page', '1');
            window.location.href = '?' + params.toString();
        }
        
        // 切换上下架
        function toggleStatus(productId, newStatus) {
            if (!confirm('确定要' + (newStatus == 1 ? '上架' : '下架') + '该商品吗？')) {
                return;
            }
            
            fetch('product_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_status&id=' + productId + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('操作失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请稍后重试');
            });
        }
        
        // 删除商品
        function deleteProduct(id, name) {
            if (!confirm('确定要删除商品"' + name + '"吗？此操作不可恢复！')) {
                return;
            }
            
            fetch('product_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('删除失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请稍后重试');
            });
        }
        
        // 全选/取消全选
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBatchToolbar();
        }
        
        // 更新批量操作栏
        function updateBatchToolbar() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const count = checkboxes.length;
            const toolbar = document.getElementById('batchToolbar');
            const selectedCount = document.getElementById('selectedCount');
            
            selectedCount.textContent = count;
            
            if (count > 0) {
                toolbar.classList.add('show');
            } else {
                toolbar.classList.remove('show');
            }
        }
        
        // 批量上架/下架
        function batchSetStatus(status) {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请先选择商品');
                return;
            }
            
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const action = status == 1 ? '上架' : '下架';
            
            if (!confirm('确定要将选中的 ' + ids.length + ' 个商品' + action + '吗？')) {
                return;
            }
            
            fetch('product_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=batch_status&ids=' + ids.join(',') + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('操作失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请稍后重试');
            });
        }
        
        // 批量删除
        function batchDelete() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请先选择商品');
                return;
            }
            
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (!confirm('确定要删除选中的 ' + ids.length + ' 个商品吗？此操作不可恢复！')) {
                return;
            }
            
            fetch('product_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=batch_delete&ids=' + ids.join(',')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('删除失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误，请稍后重试');
            });
        }
        
        // 批量导入
        function batchImport() {
            alert('批量导入功能开发中...\n支持 Excel/CSV 格式导入');
        }
        
        // 批量导出
        function batchExport() {
            alert('批量导出功能开发中...\n将导出当前筛选条件下的所有商品');
        }
        
        // 构建查询字符串
        function buildQueryString(params) {
            const urlParams = new URLSearchParams(window.location.search);
            for (const key in params) {
                if (params[key] === '' || params[key] === null) {
                    urlParams.delete(key);
                } else {
                    urlParams.set(key, params[key]);
                }
            }
            return urlParams.toString();
        }
    </script>
</body>
</html>

<?php
// 辅助函数：构建查询字符串
function buildQueryString($params) {
    $urlParams = $_GET;
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($urlParams[$key]);
        } else {
            $urlParams[$key] = $value;
        }
    }
    return http_build_query($urlParams);
}
