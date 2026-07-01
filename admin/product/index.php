<?php
/**
 * 商品管理列表页面
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../config/oss.php';
require_once __DIR__ . '/../../includes/OSSClient.php';
require_once __DIR__ . '/../../includes/SimpleOSSClient.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();

    // OSS 图片 URL 解析
    function productImgUrl($path) {
        // 防御性处理：$path 不是字符串时直接返回空
        if (!is_string($path)) {
            return '';
        }
        if (empty($path)) return '';
        if (strpos($path, 'http') === 0 || strpos($path, '/') === 0) return $path;
        try {
        $oss = OSSClient::getInstance();
        return $oss->getFileUrl($path);
    } catch (Exception $e) {
        return '';
    }
}

$pageTitle = '商品管理';

// 搜索参数
$keyword = trim($_GET['keyword'] ?? '');
$code = trim($_GET['code'] ?? '');
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? ''; // 空:全部,0:已下架,1:出售中,2:已售罄

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

// 获取商品列表
$sql = "SELECT p.*, c.name as category_name, GROUP_CONCAT(DISTINCT s.name SEPARATOR '、') as store_names
        FROM products p
        LEFT JOIN product_categories c ON p.category_id = c.id
        LEFT JOIN store_products sp ON sp.product_id = p.id
        LEFT JOIN stores s ON s.id = sp.store_id
        WHERE {$whereStr}
        GROUP BY p.id
        ORDER BY p.sort DESC, p.created_at DESC";
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
    

        .drawer-tabs {
            display: flex; gap: 4px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 8px;
        }
        .drawer-tabs .drawer-tab {
            padding: 6px 16px; border-radius: 20px;
            font-size: 13px; cursor: pointer;
            background: #f5f5f5; color: #595959;
            transition: all 0.2s;
        }
        .drawer-tabs .drawer-tab.active {
            background: #1890ff; color: #fff;
        }
        .drawer-tabs .drawer-tab:hover:not(.active) {
            background: #e8e8e8;
        }
        .tab-section { display: none; }
        .tab-section.active { display: block; }

        .page-drawer .drawer-body .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .page-drawer .drawer-body .form-row-3 .form-group { margin-bottom: 0; }


        /* ==== 商品编辑抽屉样式 ==== */
        .page-drawer {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
        }
        .page-drawer.show { display: flex; justify-content: flex-end; }
       .page-drawer .drawer-mask {
           position: absolute; inset: 0;
            background: rgba(0, 0, 0, 0.65);
           backdrop-filter: blur(8px);
           -webkit-backdrop-filter: blur(8px);
           animation: fadeIn 0.2s ease;
       }
        .page-drawer .drawer-content {
            position: relative;
            width: 900px;
            max-width: 90vw;
            height: 100%;
            background: #f5f5f5;
            display: flex;
            flex-direction: column;
            box-shadow: -4px 0 16px rgba(0,0,0,0.12);
            animation: slideIn 0.25s ease;
        }
        .page-drawer .drawer-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 24px;
            background: white;
            border-bottom: 1px solid #f0f0f0;
        }
        .page-drawer .drawer-header h3 { margin: 0; font-size: 16px; color: #262626; }
        .page-drawer .drawer-close {
            width: 32px; height: 32px;
            border: none; background: transparent;
            font-size: 18px; color: #8c8c8c;
            cursor: pointer; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .page-drawer .drawer-close:hover { background: #f5f5f5; color: #262626; }
        .page-drawer .drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
       .page-drawer .drawer-footer {
           padding: 12px 24px;
           background: white;
           border-top: 1px solid #f0f0f0;
           display: flex; justify-content: flex-end; gap: 10px;
       }
        .page-drawer .drawer-footer .btn {
            padding: 6px 16px;
            font-size: 13px;
            border-radius: 6px;
        }
        .page-drawer .drawer-body .form-group { margin-bottom: 16px; }
        .page-drawer .drawer-body .form-group label { display: block; font-size: 13px; color: #595959; font-weight: 500; margin-bottom: 6px; }
        .page-drawer .drawer-body .form-group input,
        .page-drawer .drawer-body .form-group select,
        .page-drawer .drawer-body .form-group textarea {
            width: 100%; padding: 8px 12px;
            border: 1px solid #d9d9d9; border-radius: 6px;
            font-size: 14px; box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .page-drawer .drawer-body input:focus,
        .page-drawer .drawer-body select:focus,
        .page-drawer .drawer-body textarea:focus {
            border-color: #1890ff !important;
            box-shadow: 0 0 0 2px rgba(24,144,255,0.1) !important;
        }
        .page-drawer .drawer-body input:hover,
        .page-drawer .drawer-body select:hover,
        .page-drawer .drawer-body textarea:hover {
            border-color: #40a9ff !important;
        }

            border-color: #1890ff; outline: none;
            box-shadow: 0 0 0 2px rgba(24,144,255,0.1);
        }
        .page-drawer .drawer-body .form-row-2 { display: flex; gap: 16px; }
        .page-drawer .drawer-body .form-row-2 .form-group { flex: 1; }
        .page-drawer .drawer-body .required { color: #ff4d4f; }
        .page-drawer .drawer-body .hint { font-size: 12px; color: #8c8c8c; margin-top: 4px; }
        .page-drawer .drawer-body .image-upload-area {
            width: 120px; height: 120px;
            border: 2px dashed #d9d9d9; border-radius: 10px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            cursor: pointer; background: #fafafa;
            transition: border-color 0.3s;
        }
        .page-drawer .drawer-body .image-upload-area:hover { border-color: #1890ff; }
        .page-drawer .drawer-body .image-preview {
            display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;
        }
        .page-drawer .drawer-body .image-preview .img-item {
            width: 80px; height: 80px;
            border-radius: 6px; overflow: hidden;
            position: relative; border: 1px solid #f0f0f0;
        }
        .page-drawer .drawer-body .image-preview .img-item img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .page-drawer .drawer-body .image-preview .img-item .remove {
            position: absolute; top: 2px; right: 2px;
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(0,0,0,0.5); color: white;
            border: none; cursor: pointer; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
       @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
       @keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(100%); } }
       .page-drawer.closing .drawer-content { animation: slideOut 0.3s forwards; }
       .page-drawer.closing .drawer-mask { animation: fadeOut 0.3s forwards; }

        .image-uploader {
            border: 2px dashed #d9d9d9;
            border-radius: 12px;
            width: 150px; height: 150px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            cursor: pointer; background: #fafafa;
            transition: all 0.3s;
        }
        .image-uploader:hover { border-color: #1890ff; background: #f0f5ff; }
        .image-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .image-item {
            position: relative; width: 150px; height: 150px;
            border-radius: 8px; overflow: hidden;
            border: 1px solid #f0f0f0; flex-shrink: 0;
        }
        .image-item img { width: 100%; height: 100%; object-fit: cover; }
        .image-item .remove {
            position: absolute; top: 2px; right: 2px;
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(0,0,0,0.6); color: white;
            border: none; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
        }
       .image-item .remove:hover { background: rgba(255,77,79,0.9); }
        .img-item {
            position: relative; width: 150px; height: 150px;
            border-radius: 8px; overflow: hidden;
            border: 1px solid #f0f0f0; flex-shrink: 0;
        }
        .img-item img { width: 100%; height: 100%; object-fit: cover; }
        .img-item .remove {
            position: absolute; top: 2px; right: 2px;
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(0,0,0,0.6); color: white;
            border: none; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .img-item .remove:hover { background: rgba(255,77,79,0.9); }

        /* 图片拖拽排序 + 封面图 + 点击预览 */
        .image-list .img-item, .image-list .image-item {
            cursor: grab; transition: transform 0.15s, box-shadow 0.15s;
        }
        .image-list .img-item:active, .image-list .image-item:active { cursor: grabbing; }
        .image-list .drag-over { transform: scale(1.05); box-shadow: 0 4px 12px rgba(24,144,255,0.3); }
        .image-list .dragging { opacity: 0.4; }
        .cover-badge {
            position: absolute; top: 4px; left: 4px;
            background: #1890ff; color: #fff;
            font-size: 10px; padding: 1px 6px;
            border-radius: 4px; z-index: 2;
            line-height: 1.6; pointer-events: none;
        }
        .img-preview-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.75);
            display: flex; align-items: center; justify-content: center;
            cursor: zoom-out;
        }
        .img-preview-overlay img {
            max-width: 85vw; max-height: 85vh;
            border-radius: 8px; box-shadow: 0 8px 40px rgba(0,0,0,0.4);
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
                        <a href="javascript:;" class="btn btn-success" onclick="openProductDrawer(0);return false">✚ 新增商品</a>
                        <button type="button" class="btn btn-outline" onclick="batchImport()">批量导入</button>
                        <button type="button" class="btn btn-outline" onclick="batchExport()">批量导出</button>
                    </div>
                </form>
            </div>

            <!-- 快捷筛选 -->
            <div class="filter-tabs">
                <label>快捷筛选:</label>
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
                                <th width="140">所属门店</th>
                                <th width="100">上下架</th>
                                <th width="100">状态</th>
                                <th width="180">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px; color: #8c8c8c;">
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
                                                <img src="<?= htmlspecialchars(productImgUrl($firstImage)) ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
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
                                        <div style="font-size:12px;color:#595959;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($product['store_names'] ?? '-') ?>">
                                            <?= htmlspecialchars($product['store_names'] ?? '-') ?>
                                        </div>
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
                                            <a href="javascript:;" class="action-btn action-btn-edit" onclick="openProductDrawer(<?= $product['id'] ?>);return false">编辑</a>
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
                </form>
            </div>
    </div>

    <script src="../assets/js/admin.min.js?v=<?= time() ?>"></script>
    <script>
    // ========== 商品编辑抽屉 ==========
    var pImagesData = [];
    var pPendingImages = [];

    function openProductDrawer(productId) {
        document.getElementById('pId').value = productId || 0;
        var storeInfo = document.getElementById('productStoreInfo');
        if (productId) {
            document.getElementById('productDrawerTitle').textContent = '编辑商品';
            if (storeInfo) storeInfo.textContent = '加载中...';
            loadProductData(productId);
        } else {
            document.getElementById('productDrawerTitle').textContent = '新增商品';
            if (storeInfo) storeInfo.textContent = '';
            resetProductForm();
        }
        document.getElementById('productDrawer').classList.add('show');
    }

    function closeProductDrawer() {
        // 释放 blob URL
        pPendingImages.forEach(function(item) {
            if (item.blobUrl) URL.revokeObjectURL(item.blobUrl);
        });
        var d = document.getElementById('productDrawer');
        d.classList.add('closing');
        setTimeout(function() {
            d.classList.remove('show', 'closing');
        }, 300);
    }

    function resetProductForm() {
        document.getElementById('productForm').reset();
        document.getElementById('pId').value = '0';
        document.getElementById('pSort').value = '0';
        document.getElementById('pStock').value = '0';
        document.getElementById('pStatus').value = '1';
        document.getElementById('pPrice').value = '';
        document.getElementById('imagesInput').value = '[]';
        pImagesData = [];
        pPendingImages = [];
        document.getElementById('imageList').innerHTML = '';
    }

    function loadProductData(id) {
        var params = new URLSearchParams();
        params.append('action', 'get_detail');
        params.append('id', id);
        fetch('product_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) { alert(data.message); return; }
            var p = data.data;
            document.getElementById('pName').value = p.name || '';
            document.getElementById('pCategory').value = p.category_id || '';
            document.getElementById('pCode').value = p.product_code || p.code || '';
            document.getElementById('pType').value = p.type || 1;
            document.getElementById('pPrice').value = p.price || '';
            document.getElementById('pMemberPrice').value = p.member_price || '';
            document.getElementById('pCostPrice').value = p.cost_price || '';
            document.getElementById('pStock').value = p.stock || 0;
            document.getElementById('pWeight').value = p.weight || 0;
            document.getElementById('pSort').value = p.sort || 0;
            document.getElementById('pStatus').value = p.status || 1;
            document.getElementById('pFreight').value = p.freight_template_id || 0;
            document.getElementById('pStockMethod').value = p.stock_method || 1;
            document.getElementById('pInitialSales').value = p.initial_sales || 0;
            document.getElementById('pMemberDiscount').value = p.member_discount || 0;
            document.getElementById('pSellingPoints').value = p.selling_points || '';
            document.getElementById('pServices').value = p.services || '';
            document.getElementById('pContent').value = p.content || '';
            // 显示绑定门店
            var storeEl = document.getElementById('productStoreInfo');
            if (storeEl) {
                if (p.bound_stores && p.bound_stores.length > 0) {
                    var names = p.bound_stores.map(function(s) { return s.name; }).join(' · ');
                    storeEl.innerHTML = '🏪 ' + names;
                } else {
                    storeEl.innerHTML = '🏪 未绑定门店';
                }
            }
            document.getElementById('pVideoUrl').value = p.video_url || '';
            document.getElementById('pVideoCover').value = p.video_cover || '';
            // Set limit_buy radio
            var limitVal = p.limit_buy || 0;
            document.querySelectorAll('input[name="limit_buy"]').forEach(function(rb) {
                rb.checked = parseInt(rb.value) === limitVal;
            });
            toggleLimitBuy(limitVal === 1);
            // Load images
            try {
                var imgs = p.images ? JSON.parse(p.images) : [];
                if (imgs.length > 0) {
                    pImagesData = imgs.map(function(url) {
                        if (url && !url.startsWith('http') && !url.startsWith('/') && !url.startsWith('data:')) return '/' + url;
                        return url;
                    });
                    originalSavedImages = pImagesData.slice();
                    renderProductImages();
                    updateCoverBadge();
                }
            } catch(e) {}
            document.getElementById('imagesInput').value = JSON.stringify(pImagesData);
        });
    }

    function handleProductImageUpload(event) {
        var file = event.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { alert('图片大小不能超过 5MB'); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            var dataUrl = e.target.result;
            pPendingImages.push(dataUrl);
            var html = '<div class="img-item"><img src="' + dataUrl + '"><button class="remove" onclick="removePendingProductImage(' + (pPendingImages.length - 1) + ')">×</button></div>';
            document.getElementById('imageList').insertAdjacentHTML('beforeend', html);
        };
        reader.readAsDataURL(file);
    }

    function removeProductImage(idx) {
        pImagesData.splice(idx, 1);
        document.getElementById('imagesInput').value = JSON.stringify(pImagesData);
        renderProductImages();
        updateCoverBadge();
    }

    function removePendingProductImage(idx) {
        pPendingImages.splice(idx, 1);
        renderProductImages();
        updateCoverBadge();
    }

    function renderItemHtml(img, idx, isPending) {
        var removeFn = isPending ? 'removePendingProductImage' : 'removeProductImage';
        var coverBadge = (idx === 0) ? '<span class="cover-badge">封面图</span>' : '';
        var imgUrl = (img && !img.startsWith('http') && !img.startsWith('/') && !img.startsWith('blob:')) ? '/' + img : img;
        // data: URL (如 base64 图片) 直接使用，不加 '/'
        if (img && img.startsWith('data:')) {
            imgUrl = img;
        }
        return '<div class="img-item" draggable="true" data-arr="' + (isPending ? 'pending' : 'saved') + '" data-arrindex="' + idx + '" onclick="showImagePreview(this)">' +
            coverBadge +
            '<img src="' + imgUrl + '">' +
            '<button class="remove" onclick="event.stopPropagation();' + removeFn + '(' + idx + ')">×</button></div>';
    }

    function renderProductImages() {
        var html = '';
        pImagesData.forEach(function(img, idx) {
            html += renderItemHtml(img, idx, false);
        });
        pPendingImages.forEach(function(img, idx) {
            html += renderItemHtml(img, idx, true);
        });
        document.getElementById('imageList').innerHTML = html;
    }

    // 拖拽排序
    function initDragDrop() {
        var list = document.getElementById('imageList');
        if (!list || list.dragInitialized) return;
        list.dragInitialized = true;
        var dragEl = null;

        list.addEventListener('dragstart', function(e) {
            dragEl = e.target.closest('.img-item');
            if (!dragEl) { e.preventDefault(); return; }
            dragEl.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', 'x');
        });
        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            list.querySelectorAll('.img-item').forEach(function(it) { it.classList.remove('drag-over'); });
            var hover = e.target.closest('.img-item');
            if (hover) hover.classList.add('drag-over');
        });
        list.addEventListener('drop', function(e) {
            e.preventDefault();
            list.querySelectorAll('.img-item').forEach(function(it) { it.classList.remove('drag-over', 'dragging'); });
            if (!dragEl) return;
            // Determine insertion point from DOM order
            var items = Array.from(list.querySelectorAll('.img-item'));
            var dropTarget = e.target.closest('.img-item');
            if (!dropTarget) {
                // Drop on the container - append at end
                var newSaved = [], newPending = [];
                items.forEach(function(el) {
                    var arr = el.getAttribute('data-arr');
                    var idx = parseInt(el.getAttribute('data-arrindex'));
                    if (arr === 'saved') newSaved.push(pImagesData[idx]);
                    else newPending.push(pPendingImages[idx]);
                });
                pImagesData = newSaved; pPendingImages = newPending;
            } else {
                // Reorder: rebuild from DOM
                var newSaved = [], newPending = [];
                items.forEach(function(el) {
                    var arr = el.getAttribute('data-arr');
                    var idx = parseInt(el.getAttribute('data-arrindex'));
                    if (arr === 'saved') newSaved.push(pImagesData[idx]);
                    else newPending.push(pPendingImages[idx]);
                });
                // Move the dragged element before/after dropTarget
                var targetIdx = items.indexOf(dropTarget);
                var srcIdx = items.indexOf(dragEl);
                if (srcIdx >= 0 && targetIdx >= 0) {
                    items.splice(srcIdx, 1);
                    var newTargetIdx = items.indexOf(dropTarget);
                    items.splice(newTargetIdx, 0, dragEl);
                }
                // Rebuild arrays from the reordered items
                newSaved = []; newPending = [];
                items.forEach(function(el) {
                    var arr = el.getAttribute('data-arr');
                    var idx = parseInt(el.getAttribute('data-arrindex'));
                    if (arr === 'saved') newSaved.push(pImagesData[idx]);
                    else newPending.push(pPendingImages[idx]);
                });
                pImagesData = newSaved; pPendingImages = newPending;
            }
            dragEl = null;
            saveImageOrder();
            renderProductImages();
            updateCoverBadge();
        });
        list.addEventListener('dragend', function(e) {
            list.querySelectorAll('.img-item').forEach(function(it) { it.classList.remove('dragging', 'drag-over'); });
            dragEl = null;
        });
    }
    // Initialize on render
    var _origRender = renderProductImages;
    renderProductImages = function() {
        _origRender();
        setTimeout(initDragDrop, 0);
    };
    function updateCoverBadge() {
        var items = document.querySelectorAll('#imageList .img-item');
        items.forEach(function(it, idx) {
            var badge = it.querySelector('.cover-badge');
            if (idx === 0) { if (!badge) { var b = document.createElement('span'); b.className = 'cover-badge'; b.textContent = '封面图'; it.insertBefore(b, it.firstChild); } }
            else { if (badge) badge.remove(); }
        });
    }
    function saveImageOrder() {
        // Only save saved images to the input; pending images are uploaded on save
        document.getElementById('imagesInput').value = JSON.stringify(pImagesData);
    }

    // 点击查看大图
    function showImagePreview(el) {
        var img = el.querySelector('img');
        if (!img) return;
        var overlay = document.createElement('div');
        overlay.className = 'img-preview-overlay';
        overlay.innerHTML = '<img src="' + img.src + '">';
        overlay.onclick = function() { overlay.remove(); };
        document.body.appendChild(overlay);
    }

    // 商品图片上传（兼容 shop 版本的素材）
    function handleImageUpload(event) {
        var files = event.target.files;
        if (!files || files.length === 0) return;
        var list = document.getElementById('imageList');
        if (!list) return;
        Array.from(files).forEach(function(file) {
            if (file.size > 5 * 1024 * 1024) { showMessage('图片大小不能超过 5MB', 'error'); return; }
            var blobUrl = URL.createObjectURL(file);
            var idx = pPendingImages.length;
            pPendingImages.push({ file: file, blobUrl: blobUrl });
            list.insertAdjacentHTML('beforeend', renderItemHtml(blobUrl, idx, true));
            updateCoverBadge();
            updateCount();
        });
        event.target.value = '';
    }

    function updateCount() {
        var count = pImagesData.length + pPendingImages.length;
        var el = document.getElementById('imageCount');
        if (el) el.textContent = '已选择 ' + count + ' 张图片（最少 1 张，最多 10 张）';
    }


    // base64 转 Blob
    function base64ToBlob(dataUrl) {
        var parts = dataUrl.split(',');
        var mime = parts[0].match(/:(.*?);/)[1];
        var bytes = atob(parts[1]);
        var buf = new ArrayBuffer(bytes.length);
        var view = new Uint8Array(buf);
        for (var i = 0; i < bytes.length; i++) view[i] = bytes.charCodeAt(i);
        return new Blob([buf], {type: mime});
    }

    // 上传单张图片到服务器
    function uploadImageBlob(blob, filename) {
        return new Promise(function(resolve, reject) {
            var fd = new FormData();
            fd.append('action', 'upload_image');
            fd.append('image', blob, filename || 'image_' + Date.now() + '.jpg');
            fetch('product_ajax.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) resolve(d.data.url);
                    else reject(new Error(d.message));
                })
                .catch(reject);
        });
    }

    // 删除服务器图片
    function deleteOssFile(fileUrl) {
        return new Promise(function(resolve) {
            var p = new URLSearchParams();
            p.append('action', 'delete_file');
            p.append('url', fileUrl);
            fetch('product_ajax.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p.toString() })
                .then(function(r) { return r.json(); })
                .then(function(d) { resolve(d); })
                .catch(function() { resolve({success:false}); });
        });
    }
    function submitProductForm() {
        var name = document.getElementById('pName').value.trim();
        if (!name) { alert('请输入商品名称'); return; }

        // 先上传未保存的图片，再提交表单
        var uploadPromises = [];

        // 收集需要上传的待处理图片
        pPendingImages.forEach(function(item, idx) {
            if (typeof item === 'string') {
                // data URL -> 转为 Blob 上传
                var blob = base64ToBlob(item);
                uploadPromises.push(uploadImageBlob(blob, 'image_' + Date.now() + '_' + idx + '.jpg'));
            } else if (item && item.file) {
                // File 对象 -> 直接上传
                uploadPromises.push(uploadImageBlob(item.file, item.file.name || 'image_' + Date.now() + '_' + idx + '.jpg'));
            } else if (item && item.blobUrl) {
                // 尝试通过 blob 上传
                var reader = new FileReader();
                reader.readAsArrayBuffer(new Blob([item.blobUrl]));
                uploadPromises.push(Promise.reject(new Error('图片数据异常，请重新选择')));
            }
        });

        if (uploadPromises.length > 0) {
            document.querySelector('.drawer-footer .btn-save-text').textContent = '上传中...';

            Promise.all(uploadPromises)
                .then(function(urls) {
                    // 将上传返回的 URL 加入已保存列表
                    urls.forEach(function(url) {
                        pImagesData.push(url);
                    });
                    doSubmitProductForm();
                })
                .catch(function(err) {
                    alert('❌ 图片上传失败：' + err.message);
                });
        } else {
            doSubmitProductForm();
        }
    }

    function doSubmitProductForm() {
        // 只将已保存的图片 URL 存入隐藏字段
        document.getElementById('imagesInput').value = JSON.stringify(pImagesData);

        var form = document.getElementById('productForm');
        var formData = new FormData(form);
        formData.append('action', 'save');

        fetch('product_ajax.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert('✅ ' + data.message);
                closeProductDrawer();
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(function(err) {
            alert('❌ 保存失败：' + err.message);
        });
    }


    // 规格类型切换
    function toggleSpecType(val) {
        var single = document.getElementById('singleSpecBlock');
        var multi = document.getElementById('multiSpecBlock');
        if (single) single.style.display = val === 1 ? '' : 'none';
        if (multi) multi.style.display = val === 2 ? '' : 'none';
    }

    // 限购开关
    function toggleLimitBuy(open) {
        var numInput = document.getElementById('pLimitNum');
        if (numInput) numInput.disabled = !open;
    }

    // 计算毛利率
    function calcMargin() {
        var price = parseFloat(document.getElementById('pPrice').value) || 0;
        var cost = parseFloat(document.getElementById('pCostPrice').value) || 0;
        var marginEl = document.getElementById('pMarginDisplay');
        var marginInput = document.getElementById('pMarginReadonly');
        if (!marginEl || !marginInput) return;
        if (price > 0 && cost > 0) {
            var profit = price - cost;
            var rate = (profit / price * 100).toFixed(1);
            marginEl.textContent = rate + '%';
            marginInput.value = '¥' + profit.toFixed(2) + ' (' + rate + '%)';
            marginInput.style.color = profit >= 0 ? '#52c41a' : '#ff4d4f';
        } else if (price > 0) {
            marginEl.textContent = '-';
            marginInput.value = '¥' + price.toFixed(2) + ' (100%)';
            marginInput.style.color = '#52c41a';
        } else {
            marginEl.textContent = '-';
            marginInput.value = '输入价格后自动计算';
            marginInput.style.color = '#8c8c8c';
        }
    }

    // 更新图片数量
    function updateImageCount() {
        var count = pImagesData.length + pPendingImages.length;
        var el = document.getElementById('imageCount');
        if (el) el.textContent = '已选择 ' + count + ' 张图片（最少 1 张，最多 10 张）';
    }

    // 主图视频上传
    var pPendingVideo = null;
    function handlePVideoUpload(event) {
        var file = event.target.files[0];
        if (!file) return;
        if (file.size > 100 * 1024 * 1024) { showMessage('视频大小不能超过 100MB', 'error'); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            pPendingVideo = e.target.result;
            document.getElementById('pVideoUrl').value = pPendingVideo;
            document.getElementById('pVideoPreview').innerHTML = '✅ 已上传视频（' + (file.size / 1024 / 1024).toFixed(1) + 'MB） <span style="color:#ff4d4f;cursor:pointer;" onclick="removePVideo()">删除</span>';
        };
        reader.readAsDataURL(file);
    }
    function removePVideo() {
        pPendingVideo = null;
        document.getElementById('pVideoUrl').value = '';
        document.getElementById('pVideoPreview').innerHTML = '建议视频宽高比 16:9，建议时长 8-45 秒';
    }

    // 视频封面上传
    var pPendingCover = null;
    function handlePCoverUpload(event) {
        var file = event.target.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { showMessage('图片大小不能超过 5MB', 'error'); return; }
        var reader = new FileReader();
        reader.onload = function(e) {
            pPendingCover = e.target.result;
            document.getElementById('pVideoCover').value = pPendingCover;
            document.getElementById('pCoverList').innerHTML = '<div class="image-item" style="width:100px;height:100px;border-radius:6px;overflow:hidden;border:1px solid #f0f0f0;position:relative;"><img src="' + pPendingCover + '" style="width:100%;height:100%;object-fit:cover;"><button type="button" style="position:absolute;top:2px;right:2px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,0.5);color:white;border:none;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;" onclick="removePCover()">×</button></div>';
        };
        reader.readAsDataURL(file);
    }
    function removePCover() {
        pPendingCover = null;
        document.getElementById('pVideoCover').value = '';
        document.getElementById('pCoverList').innerHTML = '';
    }
    // 加载分类下拉
    function loadProductCategories() {
        var sel = document.getElementById('pCategory');
        if (!sel) return;
        var params = new URLSearchParams();
        params.append('action', 'get_categories');
        fetch('product_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.data) return;
            data.data.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                sel.appendChild(opt);
            });
        });
    }

    // 修改新增商品按钮


</script>
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
            params.delete('page');
            window.location.href = '?' + params.toString();
        }

        // 切换上下架
        function toggleStatus(productId, newStatus) {
            if (!confirm('确定要' + (newStatus == 1 ? '上架' : '下架') + '该商品吗?')) {
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
                    alert('操作失败:' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误,请稍后重试');
            });
        }

        // 删除商品
        function deleteProduct(id, name) {
            if (!confirm('确定要删除商品"' + name + '"吗?此操作不可恢复!')) {
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
                    alert('删除失败:' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误,请稍后重试');
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

            if (!confirm('确定要将选中的 ' + ids.length + ' 个商品' + action + '吗?')) {
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
                    alert('操作失败:' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误,请稍后重试');
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

            if (!confirm('确定要删除选中的 ' + ids.length + ' 个商品吗?此操作不可恢复!')) {
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
                    alert('删除失败:' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('网络错误,请稍后重试');
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


    // 抽屉标签切换
    setTimeout(function() {
        var pEditorTabs = document.getElementById('productEditorTabs');
        if (pEditorTabs) {
            pEditorTabs.addEventListener('click', function(e) {
                var tab = e.target.closest('.drawer-tab');
                if (!tab) return;
                var name = tab.getAttribute('data-tab');
                document.querySelectorAll('#productEditorTabs .drawer-tab').forEach(function(t) { t.classList.remove('active'); });
                document.querySelectorAll('#productDrawer .tab-section').forEach(function(s) { s.classList.remove('active'); });
                tab.classList.add('active');
                var section = document.querySelector('#productDrawer .tab-section[data-tab="' + name + '"]');
                if (section) section.classList.add('active');
            });
       }
   }, 100);
    // \u52a0\u8f7d\u5546\u54c1\u5206\u7c7b
    setTimeout(loadProductCategories, 200);
    setTimeout(initDragDrop, 300);
    </script>

    <!-- 商品编辑抽屉 -->
    <div id="productDrawer" class="page-drawer">
        <div class="drawer-mask" onclick="closeProductDrawer()"></div>
        <div class="drawer-content">
            <div class="drawer-header">
                <h3 id="productDrawerTitle">新增商品</h3>
                <span id="productStoreInfo" style="font-size:12px;color:#8c8c8c;margin-left:12px;flex:1;"></span>
                <button class="drawer-close" onclick="closeProductDrawer()">&times;</button>
            </div>
            <div class="drawer-body">
                <div class="drawer-tabs" id="productEditorTabs">
                    <span class="drawer-tab active" data-tab="p-basic">基本信息</span>
                    <span class="drawer-tab" data-tab="p-spec">规格/库存</span>
                    <span class="drawer-tab" data-tab="p-detail">商品详情</span>
                    <span class="drawer-tab" data-tab="p-more">更多设置</span>
                </div>
                <form id="productForm" onsubmit="return false">
                    <input type="hidden" name="id" id="pId" value="0">

                    <!-- 基本信息 -->
                    <div class="tab-section active" data-tab="p-basic">
                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="font-size:14px;font-weight:600;color:#333;margin-bottom:10px;display:block;">商品图片 <span style="font-weight:400;font-size:12px;color:#999;">（最少 1 张，最多 10 张）</span></label>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">
                                <div class="image-list" id="imageList"></div>
                                <div class="image-uploader" onclick="document.getElementById('imageInput').click()">
                                    <div style="font-size:32px;margin-bottom:8px;">📷</div>
                                    <div style="font-size:12px;font-weight:500;color:#262626;">点击上传</div>
                                    <div style="font-size:10px;color:#8c8c8c;margin-top:4px;">JPG/PNG 5MB</div>
                                </div>
                                <input type="file" id="imageInput" multiple accept="image/*" style="display:none;" onchange="handleImageUpload(event)">
                                <input type="hidden" name="images" id="imagesInput" value='[]'>
                            </div>
                            <div class="hint" id="imageCount" style="font-size:12px;color:#8c8c8c;margin-top:8px;">已选择 0 张图片（最少 1 张，最多 10 张）</div>
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label style="font-size:14px;font-weight:600;color:#333;margin-bottom:8px;display:block;">商品名称 <span class="required" style="color:#ff4d4f;">*</span></label>
                            <input type="text" name="name" id="pName" required placeholder="请输入商品名称" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;transition:border-color 0.2s;outline:none;">
                        </div>
                        <div class="form-row-3" style="margin-bottom:18px;">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">商品类型</label>
                                <select name="type" id="pType" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;cursor:pointer;transition:border-color 0.2s;outline:none;">
                                    <option value="1">实物商品</option>
                                    <option value="2">虚拟商品</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">商品分类</label>
                                <select name="category_id" id="pCategory" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;cursor:pointer;transition:border-color 0.2s;outline:none;">
                                    <option value="">请选择分类</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">商品编码</label>
                                <input type="text" name="product_code" id="pCode" placeholder="如：SP20260625001" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;transition:border-color 0.2s;outline:none;">
                            </div>
                        </div>
                        <div class="form-row-3" style="margin-bottom:0;">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">运费模板</label>
                                <select name="freight_template_id" id="pFreight" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;cursor:pointer;transition:border-color 0.2s;outline:none;">
                                    <option value="0">默认运费</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">商品排序</label>
                                <input type="number" name="sort" id="pSort" value="0" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;transition:border-color 0.2s;outline:none;">
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:500;color:#555;margin-bottom:6px;display:block;">商品状态</label>
                                <select name="status" id="pStatus" style="width:100%;padding:10px 14px;border:1px solid #d9d9d9;border-radius:8px;font-size:14px;box-sizing:border-box;background:#fff;cursor:pointer;transition:border-color 0.2s;outline:none;">
                                    <option value="1">上架</option>
                                    <option value="0">下架</option>
                                </select>
                            </div>
                        </div>
                    </div>
<!-- 规格/库存 -->
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
                                    <label>毛利率 <span style="font-size:12px;color:#8c8c8c;" id="pMarginDisplay">-</span></label>
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
                            <div style="padding:20px;text-align:center;color:#8c8c8c;background:#fafafa;border-radius:8px;border:1px dashed #d9d9d9;">
                                ⚙️ 多规格编辑（规格项 + SKU 表格）已预留，待后续扩展
                            </div>
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

                    <!-- 商品详情 -->
                    <div class="tab-section" data-tab="p-detail">
                        <div class="form-group">
                            <label>商品详情</label>
                            <textarea name="content" id="pContent" style="min-height:350px;" placeholder="请输入商品详情（支持 HTML）"></textarea>
                        </div>
                    </div>

                    <!-- 更多设置 -->
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
                    </div>
                </form>
            </div>


            <div class="drawer-footer">
                <span onclick="closeProductDrawer()" style="display:inline-block;padding:5px 14px;font-size:13px;border:1px solid #d9d9d9;border-radius:6px;color:#595959;cursor:pointer;background:#fff;user-select:none;">取消</span>
                <span onclick="submitProductForm()" style="display:inline-block;padding:5px 14px;font-size:13px;border:none;border-radius:6px;color:#fff;cursor:pointer;background:linear-gradient(135deg,#1890ff,#40a9ff);user-select:none;"><span class="btn-save-text">保存</span></span>
            </div>
    </div>


</html>
<?php
// Strip non-standard GET parameters (prevent form data leaking into URL)
$allowedGet = ['keyword', 'code', 'category', 'status', 'page'];
$unexpected = array_diff(array_keys($_GET), $allowedGet);
if (!empty($unexpected)) {
    $clean = array_intersect_key($_GET, array_flip($allowedGet));
    $qs = http_build_query($clean);
    header('Location: index.php' . ($qs ? '?' . $qs : ''));
    exit;
}

Auth::check();
