<?php
/**
 * 商品编辑/新增
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::check();

$db = Database::getInstance();
$admin = Auth::user();
$pageTitle = '编辑商品';

$id = intval($_GET['id'] ?? 0);
$product = null;

if ($id > 0) {
    $product = $db->fetchOne("SELECT p.*, pc.name as category_name FROM products p LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE p.id = :id", ['id' => $id]);
    if (!$product) {
        die('商品不存在');
    }
    $pageTitle = '编辑商品';
} else {
    $pageTitle = '新增商品';
}

// 检查是否是抽屉模式（必须在 POST 处理之前）
$isDrawer = isset($_GET['drawer']) && $_GET['drawer'] == 1;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'category_id' => intval($_POST['category_id'] ?? 0),
        'product_code' => trim($_POST['product_code'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'member_price' => floatval($_POST['member_price'] ?? 0),
        'cost_price' => floatval($_POST['cost_price'] ?? 0),
        'original_price' => floatval($_POST['original_price'] ?? 0) ?: null,
        'stock' => intval($_POST['stock'] ?? 0),
        'weight' => floatval($_POST['weight'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'sort' => intval($_POST['sort'] ?? 0),
        'is_hot' => isset($_POST['is_hot']) ? 1 : 0,
        'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'is_recommend' => isset($_POST['is_recommend']) ? 1 : 0,
        'status' => intval($_POST['status'] ?? 1),
        'type' => intval($_POST['type'] ?? 1),
        'freight_template_id' => intval($_POST['freight_template_id'] ?? 0),
        'spec_type' => intval($_POST['spec_type'] ?? 1),
        'stock_method' => intval($_POST['stock_method'] ?? 2),
        'limit_buy' => isset($_POST['limit_buy']) ? intval($_POST['limit_buy']) : 0,
        'commission_type' => intval($_POST['commission_type'] ?? 1),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 处理图片
    $imagesInput = $_POST['images'] ?? '[]';
    $images = is_string($imagesInput) ? json_decode($imagesInput, true) : $imagesInput;
    if (!is_array($images)) $images = [];
    $data['images'] = json_encode($images, JSON_UNESCAPED_UNICODE);
    
    // 处理视频相关字段
    $data['video_url'] = trim($_POST['video_url'] ?? '');
    $data['video_cover'] = trim($_POST['video_cover'] ?? '');
    
    // 处理服务承诺
    $services = [];
    if (isset($_POST['service_7day']) && $_POST['service_7day']) $services[] = '7 天无理由退换';
    if (isset($_POST['service_freight']) && $_POST['service_freight']) $services[] = '运费险';
    if (isset($_POST['service_genuine']) && $_POST['service_genuine']) $services[] = '正品保证';
    if (isset($_POST['service_express']) && $_POST['service_express']) $services[] = '极速发货';
    $data['services'] = json_encode($services, JSON_UNESCAPED_UNICODE);
    
    // 处理其他字段
    $data['selling_points'] = trim($_POST['selling_points'] ?? '');
    $data['initial_sales'] = intval($_POST['initial_sales'] ?? 0);
    $data['member_discount'] = isset($_POST['member_discount']) ? intval($_POST['member_discount']) : 0;
    $data['points_gift'] = isset($_POST['points_gift']) ? intval($_POST['points_gift']) : 1;
    $data['points_deduct'] = isset($_POST['points_deduct']) ? intval($_POST['points_deduct']) : 1;
    $data['points_deduct_type'] = intval($_POST['points_deduct_type'] ?? 1);
    
    try {
        if ($id > 0) {
            // 更新
            $db->update('products', $data, 'id = :id', ['id' => $id]);
            $productId = $id;
            $message = '商品更新成功';
        } else {
            // 新增
            $data['sales'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $productId = $db->insert('products', $data);
            $message = '商品创建成功';
        }
        
        // ===== 处理多规格 SKU =====
        if ($data['spec_type'] == 2) {
            $specDataRaw = $_POST['spec_data'] ?? '';
            $specData = json_decode($specDataRaw, true);
            $skus = $specData['skus'] ?? [];
            
            if (!empty($skus)) {
                // 删除旧 SKU
                $db->delete('product_skus', 'product_id = ?', [$productId]);
                
                $totalStock = 0;
                foreach ($skus as $sku) {
                    $skuPrice = floatval($sku['price'] ?? 0);
                    $skuStock = intval($sku['stock'] ?? 0);
                    $skuCode = trim($sku['code'] ?? '');
                    
                    $db->insert('product_skus', [
                        'product_id' => $productId,
                        'spec_name' => $sku['specCombination'] ?? '',
                        'spec_value' => json_encode($sku, JSON_UNESCAPED_UNICODE),
                        'price' => $skuPrice > 0 ? $skuPrice : $data['price'],
                        'cost_price' => $data['cost_price'] ?? 0,
                        'stock' => $skuStock,
                        'weight' => $data['weight'] ?? 0,
                        'code' => $skuCode,
                        'image' => $sku['image'] ?? '',
                        'status' => 1,
                    ]);
                    $totalStock += $skuStock;
                }
                
                // 汇总库存更新到主表
                $db->update('products', ['stock' => $totalStock], 'id = :id', ['id' => $productId]);
            }
            
            // 存储原始 spec_data 到主表
            $db->update('products', ['spec_data' => $specDataRaw], 'id = :id', ['id' => $productId]);
        }
        
        // 记录日志
        $db->insert('admin_logs', [
            'admin_id' => $admin['id'],
            'action' => $id > 0 ? '编辑商品' : '新增商品',
            'detail' => "商品：{$data['name']}",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($isDrawer) {
            // 抽屉模式：返回 JavaScript 关闭抽屉并刷新父页面
            echo "<script>
                alert('{$message}');
                // 调用父窗口的关闭函数
                if (window.parent && window.parent.closeProductDrawerAndRefresh) {
                    window.parent.closeProductDrawerAndRefresh();
                } else {
                    // 降级方案：刷新当前页面
                    location.reload();
                }
            </script>";
            exit;
        } else {
            // 完整页面模式：跳转回列表页
            header('Location: index.php?msg=' . urlencode($message));
            exit;
        }
        
    } catch (Exception $e) {
        $error = '保存失败: ' . $e->getMessage();
    }
    
    // 成功则跳转（$message 和 $isDrawer 已在上方设置）
    if (empty($error) && !empty($message)) {
        if ($isDrawer) {
            echo "<script>alert('{$message}');if(window.parent&&window.parent.closeProductDrawerAndRefresh){window.parent.closeProductDrawerAndRefresh();}else{location.reload();}</script>";
            exit;
        } else {
            header('Location: index.php?msg=' . urlencode($message));
            exit;
        }
    }
}

// 获取商品分类
$categories = $db->fetchAll("SELECT * FROM product_categories WHERE status = 1 ORDER BY sort DESC");

if ($isDrawer) {
    // 抽屉模式：只返回表单内容，不返回完整 HTML
    header('Content-Type: text/html; charset=utf-8');
} else {
    // 完整页面模式
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 青园营地管理后台</title>
    <link rel="stylesheet" href="../assets/css/admin.min.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/layout.min.css?v=<?= time() ?>">
    <style>
    /* 动态背景 - 已移到 admin.css 全局样式 */
    
    /* 表单容器 */
    form {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    /* 卡片样式 */
    .card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    
    .card-header {
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #262626;
        display: flex;
        align-items: center;
    }
    
    .card-title::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 18px;
        background: #1890ff;
        margin-right: 10px;
        border-radius: 2px;
    }
    
    .content-wrapper {
        margin: 0 8px 0 12px;
        padding: 24px 32px 24px 32px;
    }
    
    
    
    /* 可搜索分类下拉 */
    .category-select-wrapper { position: relative; width: 100%; }
    .category-search-input {
        width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px;
        font-size: 14px; box-sizing: border-box; background: #fff;
    }
    .category-search-input:focus { border-color: #1890ff; outline: none; box-shadow: 0 0 0 2px rgba(24,144,255,.2); }
    .category-dropdown {
        display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 100;
        background: #fff; border: 1px solid #d9d9d9; border-radius: 6px; margin-top: 2px;
        max-height: 260px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,.12);
        padding: 4px 0;
    }
    .category-dropdown.show { display: block; }
    .category-dropdown .cat-item { padding: 8px 14px; cursor: pointer; font-size: 14px; color: #333; }
    .category-dropdown .cat-item:hover { background: #e6f7ff; }
    .category-dropdown .cat-item.selected { background: #bae7ff; color: #1890ff; font-weight: 600; }
    .category-dropdown .empty-hint { padding: 20px; text-align: center; color: #999; font-size: 13px; }
/* 表单行 */
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        flex: 1;
        margin-bottom: 20px;
    }
    
    /* 表单标签 */
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #262626;
    }
    
    .form-group label .required {
        color: #ff4d4f;
        margin-left: 2px;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #1890ff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(24, 144, 255, 0.1);
    }
    
    .form-group textarea {
        font-family: 'Microsoft YaHei', sans-serif;
        resize: vertical;
    }
    
    .hint {
        font-size: 12px;
        color: #8c8c8c;
        margin-top: 8px;
    }
    
    /* 图片上传器 */
    .image-uploader {
        border: 2px dashed #d9d9d9;
        border-radius: 12px;
        width: 150px;
        height: 150px;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #fafafa;
    }
    
    .image-uploader:hover {
        border-color: #1890ff;
        background: #f0f5ff;
    }
    
    /* 视频上传器样式复用 image-uploader */
    
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
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .image-item .remove:hover {
        background: #ff4d4f;
        transform: scale(1.1);
    }
    
    /* 复选框组 */
    .checkbox-group {
        display: flex;
        gap: 24px;
        padding: 12px 0;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .checkbox-item input[type="checkbox"],
    .checkbox-item input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .checkbox-item span {
        font-size: 14px;
        color: #262626;
    }
    
    /* 表单操作栏 */
    .form-actions {
        display: flex;
        gap: 16px;
        justify-content: center;
        padding: 24px 0;
    }
    
    .btn {
        padding: 12px 32px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #1890ff, #40a9ff);
        color: white;
        box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #40a9ff, #69c0ff);
        box-shadow: 0 6px 16px rgba(24, 144, 255, 0.4);
        transform: translateY(-2px);
    }
    
    .btn-outline {
        background: white;
        color: #666;
        border: 2px solid #d9d9d9;
    }
    
    .btn-outline:hover {
        border-color: #999;
        color: #333;
        background: #f5f5f5;
    }
    
    /* TAB 切换样式 */
    .form-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 0;
    }
    
    .tab-btn {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        transition: all 0.3s;
        position: relative;
        bottom: -2px;
    }
    
    .tab-btn:hover {
        color: #262626;
    }
    
    .tab-btn.active {
        color: #262626;
        border-bottom-color: #262626;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* 响应式 */
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .content-wrapper {
            padding: 16px;
        }
        
        .card {
            padding: 20px;
        }
    }
    </style>
</head>
<body class="admin-body">
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-content">
        <?php if (!empty($error)): ?>
        <div style="background:#fff2f0;border:1px solid #ffccc7;border-radius:8px;padding:12px 16px;margin:16px 24px 0;color:#ff4d4f;font-size:14px;">
            <strong>⚠️ <?= htmlspecialchars($error) ?></strong>
        </div>
        <?php endif; ?>
        <div class="content-wrapper">
        <div class="content-wrapper">
            <form method="POST" enctype="multipart/form-data" id="productForm" onsubmit="handleSubmit(event)">
                <!-- TAB 切换 -->
                <div class="form-tabs">
                    <button type="button" class="tab-btn active" data-tab="basic">基本信息</button>
                    <button type="button" class="tab-btn" data-tab="stock">规格/库存</button>
                    <button type="button" class="tab-btn" data-tab="detail">商品详情</button>
                    <button type="button" class="tab-btn" data-tab="advanced">更多设置</button>
                </div>
                
                <!-- 基本信息 -->
                <div class="tab-content active" id="tab-basic">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">基本信息</div>
                        </div>
                        
                        <!-- 商品类型 -->
                        <div class="form-group">
                            <label>商品类型 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="type" value="1" <?= (!isset($product['type']) || $product['type'] == 1) ? 'checked' : '' ?>>
                                    <span>实物商品</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="type" value="2" <?= (isset($product['type']) && $product['type'] == 2) ? 'checked' : '' ?>>
                                    <span>虚拟商品</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 商品名称 -->
                        <div class="form-group">
                            <label>商品名称 <span class="required">*</span></label>
                            <input type="text" name="name" required 
                                   value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                   placeholder="请输入商品名称">
                        </div>
                        
                        <!-- 商品分类 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>商品分类 <span class="required">*</span></label>
                                <div class="category-select-wrapper">
                                    <input type="text" class="category-search-input" id="categorySearch" 
                                           placeholder="搜索或选择分类..." autocomplete="off"
                                           value="<?= htmlspecialchars($product['category_name'] ?? '') ?>"
                                           onfocus="openCategoryDropdown()" oninput="filterCategories(this.value)"
                                           onkeydown="if(event.key==='Escape')closeCategoryDropdown();">
                                    <input type="hidden" name="category_id" id="categoryId" 
                                           value="<?= intval($product['category_id'] ?? 0) ?>">
                                    <div class="category-dropdown" id="categoryDropdown"></div>
                                </div>
                            </div>
                            
                            <!-- 商品编码 -->
                            <div class="form-group">
                                <label>商品编码</label>
                                <input type="text" name="product_code" 
                                       value="<?= htmlspecialchars($product['product_code'] ?? '') ?>"
                                       placeholder="可选，如 SKU-001">
                            </div>
                        </div>
                        
                        <!-- 商品图片 -->
                        <div class="form-group">
                            <label>商品图片 <span class="required">*</span>（最少 1 张，最多 10 张）</label>
                            
                            <!-- 图片预览列表 -->
                            <div class="image-list" id="imageList">
                                <?php
                                $images = json_decode($product['images'] ?? '[]', true) ?: [];
                                foreach ($images as $index => $img):
                                    // 判断是否是文件 key 格式（不包含 http）
                                    $isFileKey = (strpos($img, 'http') !== 0);
                                ?>
                                    <div class="image-item">
                                        <img src="" 
                                             data-file-key="<?= htmlspecialchars($img) ?>" 
                                             class="preview-image"
                                             style="width:100%;height:100%;object-fit:cover;">
                                        <button type="button" class="remove" onclick="removeImage(this, <?= $index ?>)">×</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- 上传按钮 -->
                            <div class="image-uploader" onclick="document.getElementById('imageInput').click()" style="margin-top: 16px;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📷</div>
                                <div style="font-size: 12px; font-weight: 500; color: #262626;">点击上传</div>
                                <div style="font-size: 10px; color: #8c8c8c; margin-top: 4px;">JPG/PNG 5MB</div>
                            </div>
                            <input type="file" id="imageInput" multiple accept="image/*" style="display: none;" onchange="handleImageUpload(event)">
                            
                            <input type="hidden" name="images" id="imagesInput" value='<?= htmlspecialchars(json_encode($images, JSON_UNESCAPED_UNICODE)) ?>'>
                            <div class="hint" id="imageCount">已上传 <?= count($images) ?> 张图片</div>
                        </div>
                        
                        <!-- 运费模板 -->
                        <div class="form-group">
                            <label>运费模板 <span class="required">*</span></label>
                            <select name="freight_template_id" required>
                                <option value="0">请选择运费模板</option>
                                <?php
                                // 假设运费模板数据
                                $freight_templates = [
                                    ['id' => 1, 'name' => '默认模板 - 满 99 包邮'],
                                    ['id' => 2, 'name' => '生鲜模板 - 冷链配送'],
                                    ['id' => 3, 'name' => '大件模板 - 物流配送']
                                ];
                                foreach ($freight_templates as $tpl):
                                ?>
                                    <option value="<?= $tpl['id'] ?>" <?= ($product['freight_template_id'] ?? 0) == $tpl['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tpl['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- 商品状态和排序 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>商品状态 <span class="required">*</span></label>
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="radio" name="status" value="1" <?= ($product['status'] ?? 1) ? 'checked' : '' ?>>
                                        <span>上架</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="radio" name="status" value="0" <?= !($product['status'] ?? 1) ? 'checked' : '' ?>>
                                        <span>下架</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>商品排序 <span class="required">*</span></label>
                                <input type="number" name="sort" required min="0"
                                       value="<?= htmlspecialchars($product['sort'] ?? '0') ?>"
                                       placeholder="数字越大越靠前">
                                <div class="hint">系统自动识别排序，数字越大越靠前</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 规格/库存 -->
                <div class="tab-content" id="tab-stock">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">规格/库存</div>
                        </div>
                        
                        <!-- 规格类型 -->
                        <div class="form-group">
                            <label>规格类型 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="spec_type" id="spec_type_single" value="1" <?= (!isset($product['spec_type']) || $product['spec_type'] == 1) ? 'checked' : '' ?>>
                                    <span>单规格</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="spec_type" id="spec_type_multi" value="2" <?= (isset($product['spec_type']) && $product['spec_type'] == 2) ? 'checked' : '' ?>>
                                    <span>多规格</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 多规格编辑区 -->
                        <div id="multi-spec-section" style="display:none;">
                            <div class="card" style="margin-top: 0; border: 1px solid #e8e8e8;">
                                <div class="card-header">
                                    <div class="card-title">规格编辑</div>
                                </div>
                                
                                <!-- 添加规格名：内联输入 -->
                                <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                                    <input type="text" id="spec-group-name-input" placeholder="输入规格名，如：颜色、尺码、版本" 
                                           onkeydown="if(event.key==='Enter'){event.preventDefault();addSpecGroup();}"
                                           style="flex:1; padding:8px 12px; border:1px solid #d9d9d9; border-radius:6px; font-size:13px;">
                                    <button type="button" onclick="addSpecGroup()" style="padding:8px 20px; background:#1890ff; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:13px; white-space:nowrap;">+ 添加规格</button>
                                </div>
                                
                                <!-- 规格列表 -->
                                <div id="spec-groups"></div>
                                
                                <!-- SKU 明细 -->
                                <div class="card-header" style="margin-top: 20px;">
                                    <div class="card-title">SKU 明细（自动生成）</div>
                                </div>
                                <div style="overflow-x: auto;">
                                    <table id="sku-table" style="width:100%; border-collapse:collapse; font-size:13px; display:none;">
                                        <thead><tr id="sku-header" style="background:#fafafa;"></tr></thead>
                                        <tbody id="sku-body"></tbody>
                                    </table>
                                </div>
                                <div class="hint" id="sku-hint" style="margin-top:12px; color:#999;">添加规格和规格值后，系统将自动生成 SKU 组合表</div>
                                <input type="hidden" name="spec_data" id="spec_data_input" value=''>
                            </div>
                        </div>
                        
                        <!-- 单规格字段区 -->
                        <div id="single-spec-section">
                        
                        <!-- 价格信息 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>商品价格 <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="number" name="price" required step="0.01" min="0"
                                           value="<?= htmlspecialchars($product['price'] ?? '1.00') ?>"
                                           placeholder="1.00">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">元</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>会员价 <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="number" name="member_price" required step="0.01" min="0"
                                           value="<?= htmlspecialchars($product['member_price'] ?? '1.00') ?>"
                                           placeholder="1.00">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">元</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>成本价格 <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="number" name="cost_price" id="cost_price" required step="0.01" min="0"
                                           value="<?= htmlspecialchars($product['cost_price'] ?? '1.00') ?>"
                                           placeholder="1.00" oninput="calculateMargin()">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">元</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>毛利率</label>
                                <div style="position: relative;">
                                    <input type="text" id="margin_rate" readonly 
                                           style="background: #f5f5f5; cursor: not-allowed;"
                                           value="0%">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">%</span>
                                </div>
                                <div class="hint">毛利率 = （售价 - 成本）÷ 售价 × 100%</div>
                            </div>
                        </div>
                        
                        <!-- 库存和重量 -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>库存量 <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="number" name="stock" required min="0"
                                           value="<?= htmlspecialchars($product['stock'] ?? '999') ?>"
                                           placeholder="999">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">件</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>商品重量 <span class="required">*</span></label>
                                <div style="position: relative;">
                                    <input type="number" name="weight" required step="0.001" min="0"
                                           value="<?= htmlspecialchars($product['weight'] ?? '0') ?>"
                                           placeholder="0">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #8c8c8c;">千克（Kg）</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 库存计算方式 -->
                        <div class="form-group">
                            <label>库存计算方式 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="stock_method" value="1" <?= (!isset($product['stock_method']) || $product['stock_method'] == 1) ? 'checked' : '' ?>>
                                    <span>下单减库存</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="stock_method" value="2" <?= (isset($product['stock_method']) && $product['stock_method'] == 2) ? 'checked' : '' ?>>
                                    <span>付款减库存</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 商品限购 -->
                        <div class="form-group">
                            <label>商品限购 <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="limit_buy" value="0" <?= (!isset($product['limit_buy']) || $product['limit_buy'] == 0) ? 'checked' : '' ?>>
                                    <span>关闭</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="limit_buy" value="1" <?= (isset($product['limit_buy']) && $product['limit_buy'] == 1) ? 'checked' : '' ?>>
                                    <span>开启</span>
                                </label>
                            </div>
                            <div class="hint">开启后可设置每人限购数量</div>
                        </div>
                        
                        </div><!-- /#single-spec-section -->
                        
                    </div>
                </div>
                
                <!-- 商品详情 -->
                <div class="tab-content" id="tab-detail">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">商品详情</div>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="content" rows="15" 
                                      placeholder="在此输入商品详细介绍，支持 HTML 代码"><?= htmlspecialchars($product['content'] ?? '') ?></textarea>
                            <div class="hint">支持 HTML 代码，可插入图片、表格等</div>
                        </div>
                    </div>
                </div>
                
                <!-- 更多设置 -->
                <div class="tab-content" id="tab-advanced">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">更多设置</div>
                        </div>
                        
                        <!-- 主图视频 -->
                        <div class="form-group">
                            <label>主图视频</label>
                            <div class="image-uploader" onclick="document.getElementById('videoInput').click()" style="width: 140px; height: 140px;">
                                <div style="font-size: 32px; margin-bottom: 8px;">🎬</div>
                                <div style="font-size: 11px; font-weight: 500; color: #262626;">点击上传视频</div>
                                <div style="font-size: 9px; color: #8c8c8c; margin-top: 4px;">16:9 8-45 秒</div>
                            </div>
                            <input type="file" id="videoInput" accept="video/*" style="display: none;" onchange="handleVideoUpload(event)">
                            <div class="hint" id="videoPreview">建议视频宽高比 16:9，建议时长 8-45 秒</div>
                            <input type="hidden" name="video_url" id="videoUrl" value="<?= htmlspecialchars($product['video_url'] ?? '') ?>">
                        </div>
                        
                        <!-- 视频封面图 -->
                        <div class="form-group">
                            <label>视频封面图</label>
                            <div class="image-uploader" onclick="document.getElementById('videoCoverInput').click()" style="width: 140px; height: 140px;">
                                <div style="font-size: 32px; margin-bottom: 8px;">🖼️</div>
                                <div style="font-size: 11px; font-weight: 500; color: #262626;">点击上传封面</div>
                                <div style="font-size: 9px; color: #8c8c8c; margin-top: 4px;">JPG/PNG 5MB</div>
                            </div>
                            <input type="file" id="videoCoverInput" accept="image/*" style="display: none;" onchange="handleVideoCoverUpload(event)">
                            <div class="image-list" id="videoCoverList" style="margin-top: 12px;"></div>
                            <input type="hidden" name="video_cover" id="videoCover" value="<?= htmlspecialchars($product['video_cover'] ?? '') ?>">
                        </div>
                        
                        <!-- 商品买点 -->
                        <div class="form-group">
                            <label>商品买点</label>
                            <textarea name="selling_points" rows="4" 
                                      placeholder="请输入商品核心卖点，每行一个"><?= htmlspecialchars($product['selling_points'] ?? '') ?></textarea>
                            <div class="hint">突出商品优势，吸引用户购买</div>
                        </div>
                        
                        <!-- 服务承诺 -->
                        <div class="form-group">
                            <label>服务承诺</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_7day" value="1" <?= ($product['service_7day'] ?? 0) ? 'checked' : '' ?>>
                                    <span>7 天无理由退换</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_freight" value="1" <?= ($product['service_freight'] ?? 0) ? 'checked' : '' ?>>
                                    <span>运费险</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_genuine" value="1" <?= ($product['service_genuine'] ?? 0) ? 'checked' : '' ?>>
                                    <span>正品保证</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_express" value="1" <?= ($product['service_express'] ?? 0) ? 'checked' : '' ?>>
                                    <span>极速发货</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 初始销量 -->
                        <div class="form-group">
                            <label>初始销量</label>
                            <input type="number" name="initial_sales" min="0"
                                   value="<?= htmlspecialchars($product['initial_sales'] ?? '0') ?>"
                                   placeholder="0">
                            <div class="hint">用于商品展示的基础销量数据</div>
                        </div>
                        
                        <!-- 会员打折 -->
                        <div class="form-group">
                            <label>是否参与会员打折活动</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="member_discount" value="1" <?= (isset($product['member_discount']) && $product['member_discount'] == 1) ? 'checked' : '' ?>>
                                    <span>是</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="member_discount" value="0" <?= (!isset($product['member_discount']) || $product['member_discount'] == 0) ? 'checked' : '' ?>>
                                    <span>否</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 积分赠送 -->
                        <div class="form-group">
                            <label>积分赠送设置</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="points_gift" value="1" <?= (!isset($product['points_gift']) || $product['points_gift'] == 1) ? 'checked' : '' ?>>
                                    <span>是</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="points_gift" value="0" <?= (isset($product['points_gift']) && $product['points_gift'] == 0) ? 'checked' : '' ?>>
                                    <span>否</span>
                                </label>
                            </div>
                            <div class="hint">购买此商品是否赠送积分</div>
                        </div>
                        
                        <!-- 积分抵扣 -->
                        <div class="form-group">
                            <label>积分抵扣</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="points_deduct" value="1" <?= (!isset($product['points_deduct']) || $product['points_deduct'] == 1) ? 'checked' : '' ?>>
                                    <span>开启</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="points_deduct" value="0" <?= (isset($product['points_deduct']) && $product['points_deduct'] == 0) ? 'checked' : '' ?>>
                                    <span>关闭</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- 积分抵扣设置 -->
                        <div class="form-group">
                            <label>积分抵扣设置</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="points_deduct_type" value="1" <?= (!isset($product['points_deduct_type']) || $product['points_deduct_type'] == 1) ? 'checked' : '' ?>>
                                    <span>系统默认</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="points_deduct_type" value="2" <?= (isset($product['points_deduct_type']) && $product['points_deduct_type'] == 2) ? 'checked' : '' ?>>
                                    <span>单独设置</span>
                                </label>
                            </div>
                            <div class="hint">系统默认使用全局积分抵扣比例</div>
                        </div>
                        
                        <!-- 分销佣金 -->
                        <div class="form-group">
                            <label>分销佣金</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input type="radio" name="commission_type" value="1" <?= (!isset($product['commission_type']) || $product['commission_type'] == 1) ? 'checked' : '' ?>>
                                    <span>系统默认</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="radio" name="commission_type" value="2" <?= (isset($product['commission_type']) && $product['commission_type'] == 2) ? 'checked' : '' ?>>
                                    <span>单独设置</span>
                                </label>
                            </div>
                            <div class="hint">系统默认使用全局分销佣金比例</div>
                        </div>
                    </div>
                </div>
                
                <!-- 操作按钮 -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 保存</button>
                    <a href="index.php" class="btn btn-outline">❌ 取消</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/admin.min.js"></script>
    <script>
        // ========================================
        // 规格类型切换：单规格 / 多规格
        // ========================================
        
        // ===== 分类搜索下拉组件 =====
        (function() {
            var allCats = <?= json_encode($categories ?? [], JSON_UNESCAPED_UNICODE) ?>;
            
            function buildTree() {
                var parents = [], children = {};
                allCats.forEach(function(c) {
                    if (c.parent_id == 0) parents.push(c);
                    else { if (!children[c.parent_id]) children[c.parent_id] = []; children[c.parent_id].push(c); }
                });
                var ordered = [];
                parents.forEach(function(p) {
                    ordered.push(p);
                    if (children[p.id]) children[p.id].forEach(function(ch) { ordered.push(ch); });
                });
                return ordered;
            }
            
            window.openCategoryDropdown = function() {
                var dd = document.getElementById('categoryDropdown');
                dd.classList.add('show');
                renderDropdown(document.getElementById('categorySearch').value);
                document.addEventListener('click', closeOutside);
            };
            
            window.closeCategoryDropdown = function() {
                document.getElementById('categoryDropdown').classList.remove('show');
                document.removeEventListener('click', closeOutside);
            };
            
            function closeOutside(e) {
                var w = document.querySelector('.category-select-wrapper');
                if (w && !w.contains(e.target)) window.closeCategoryDropdown();
            }
            
            function renderDropdown(kw) {
                var dd = document.getElementById('categoryDropdown');
                var tree = buildTree();
                kw = (kw || '').trim().toLowerCase();
                var sid = parseInt(document.getElementById('categoryId').value) || 0;
                var html = '';
                tree.forEach(function(cat) {
                    if (kw && cat.name.toLowerCase().indexOf(kw) === -1) return;
                    var ch = cat.parent_id > 0;
                    var sel = cat.id == sid ? ' selected' : '';
                    html += '<div class="cat-item' + sel + '" style="padding-left:' + (14 + (ch ? 24 : 0)) + 'px;" ' +
                            'onclick="document.getElementById(\'categoryId\').value=' + cat.id + ';' +
                            'document.getElementById(\'categorySearch\').value=\'' + cat.name.replace(/'/g, "\\'") + '\';' +
                            'closeCategoryDropdown();">' + (ch ? '├ ' : '') + cat.name + '</div>';
                });
                dd.innerHTML = html || '<div class="empty-hint">无匹配分类</div>';
            }
            
            window.filterCategories = function(keyword) {
                if (document.getElementById('categoryDropdown').classList.contains('show')) renderDropdown(keyword);
            };
        })();
        

        function toggleSpecMode() {
            const isMulti = document.getElementById('spec_type_multi').checked;
            const singleSection = document.getElementById('single-spec-section');
            const multiSection = document.getElementById('multi-spec-section');
            
            if (isMulti) {
                singleSection.style.display = 'none';
                multiSection.style.display = 'block';
                // 单规格字段置为可选填
                singleSection.querySelectorAll('input[required]').forEach(el => el.removeAttribute('required'));
            } else {
                singleSection.style.display = 'block';
                multiSection.style.display = 'none';
                // 恢复必填
                singleSection.querySelectorAll('input[name="price"], input[name="member_price"], input[name="cost_price"], input[name="stock"], input[name="weight"]').forEach(el => el.setAttribute('required', ''));
            }
        }
        
        // 绑定切换事件
        document.querySelectorAll('input[name="spec_type"]').forEach(radio => {
            radio.addEventListener('change', toggleSpecMode);
        });
        
        // 多规格数据结构
        let specGroups = []; // [{name: '颜色', values: ['红色','蓝色']}]
        let skuRows = [];    // [{specCombination: '红色,S', price:'', stock:'', code:'', image:''}]
        
        // 添加规格组（从页面输入框读取）
        function addSpecGroup() {
            const input = document.getElementById('spec-group-name-input');
            const groupName = (input.value || '').trim();
            if (!groupName) {
                input.focus();
                return;
            }
            if (specGroups.find(g => g.name === groupName)) {
                alert('规格名「' + groupName + '」已存在');
                return;
            }
            specGroups.push({ name: groupName, values: [] });
            input.value = '';
            input.focus();
            renderSpecGroups();
            generateSKUTable();
        }
        
        // 删除规格组
        function removeSpecGroup(index) {
            const group = specGroups[index];
            if (group.values.length > 0) {
                if (!confirm('删除「' + group.name + '」将同时清除 ' + group.values.length + ' 个规格值及所有 SKU 数据，确定？')) return;
            }
            specGroups.splice(index, 1);
            renderSpecGroups();
            generateSKUTable();
        }
        
        // 添加规格值
        function addSpecValue(groupIndex) {
            const valueInput = document.getElementById('spec-value-input-' + groupIndex);
            if (!valueInput) return;
            const val = valueInput.value.trim();
            if (!val) { valueInput.focus(); return; }
            if (specGroups[groupIndex].values.includes(val)) {
                // 重复值提示：闪烁输入框
                valueInput.style.borderColor = '#ff4d4f';
                valueInput.style.background = '#fff2f0';
                setTimeout(() => { valueInput.style.borderColor = '#d9d9d9'; valueInput.style.background = ''; }, 800);
                return;
            }
            specGroups[groupIndex].values.push(val);
            renderSpecGroups();
            generateSKUTable();
            // renderSpecGroups 重建了 DOM，重新获取输入框并聚焦
            const newInput = document.getElementById('spec-value-input-' + groupIndex);
            if (newInput) newInput.focus();
        }
        
        // 删除规格值
        function removeSpecValue(groupIndex, valueIndex) {
            specGroups[groupIndex].values.splice(valueIndex, 1);
            renderSpecGroups();
            generateSKUTable();
        }
        
        // 渲染规格组
        function renderSpecGroups() {
            const container = document.getElementById('spec-groups');
            container.innerHTML = specGroups.map((group, gi) => `
                <div style="margin-bottom: 12px; padding: 12px; background: #fafafa; border-radius: 8px; border: 1px solid #f0f0f0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <strong style="color: #262626;">${group.name}</strong>
                        <button type="button" onclick="removeSpecGroup(${gi})" style="background:none; border:none; color:#ff4d4f; cursor:pointer; font-size:18px;" title="删除规格">&times;</button>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                        ${group.values.map((v, vi) => `
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; background:#e6f7ff; border:1px solid #91d5ff; border-radius:4px; font-size:13px; color:#1890ff;">
                                ${v}
                                <button type="button" onclick="removeSpecValue(${gi},${vi})" style="background:none; border:none; color:#ff4d4f; cursor:pointer; font-size:14px; padding:0; line-height:1;">&times;</button>
                            </span>
                        `).join('')}
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="spec-value-input-${gi}" placeholder="输入${group.name}值，回车添加" 
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addSpecValue(${gi});}"
                               style="flex:1; padding:6px 10px; border:1px solid #d9d9d9; border-radius:4px; font-size:13px;">
                        <button type="button" onclick="addSpecValue(${gi})" style="padding:6px 12px; background:#1890ff; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px;">添加</button>
                    </div>
                </div>
            `).join('');
        }
        
        // 生成笛卡尔积
        function cartesianProduct(arrays) {
            return arrays.reduce((acc, curr) => {
                const result = [];
                acc.forEach(a => curr.forEach(b => result.push([...a, b])));
                return result;
            }, [[]]);
        }
        
        // 生成 SKU 表格
        function generateSKUTable() {
            const table = document.getElementById('sku-table');
            const header = document.getElementById('sku-header');
            const body = document.getElementById('sku-body');
            const hint = document.getElementById('sku-hint');
            const specInput = document.getElementById('spec_data_input');
            
            // 过滤出有值的规格组
            const validGroups = specGroups.filter(g => g.values.length > 0);
            
            if (validGroups.length === 0) {
                table.style.display = 'none';
                hint.style.display = 'block';
                specInput.value = '';
                return;
            }
            
            // 笛卡尔积组合
            const valueArrays = validGroups.map(g => g.values);
            const combinations = cartesianProduct(valueArrays);
            
            // 表头
            const headerCells = validGroups.map(g => `<th style="padding:8px 12px; text-align:left; border-bottom:2px solid #f0f0f0; font-weight:600;">${g.name}</th>`).join('');
            header.innerHTML = headerCells + `
                <th style="padding:8px 12px; text-align:left; border-bottom:2px solid #f0f0f0; font-weight:600;">价格(元)</th>
                <th style="padding:8px 12px; text-align:left; border-bottom:2px solid #f0f0f0; font-weight:600;">库存</th>
                <th style="padding:8px 12px; text-align:left; border-bottom:2px solid #f0f0f0; font-weight:600;">SKU编码</th>
            `;
            
            // 更新 skuRows 以保持数据
            const newSkuRows = combinations.map(combo => {
                const key = combo.join(',');
                const existing = skuRows.find(r => r.specCombination === key);
                return existing || { specCombination: key, price: '', stock: '', code: '' };
            });
            skuRows = newSkuRows;
            
            // 表体
            body.innerHTML = skuRows.map((row, ri) => {
                const comboValues = row.specCombination.split(',');
                const specCells = comboValues.map(v => `<td style="padding:6px 12px; border-bottom:1px solid #f5f5f5;">${v}</td>`).join('');
                return `<tr>
                    ${specCells}
                    <td style="padding:4px 8px; border-bottom:1px solid #f5f5f5;"><input type="number" step="0.01" min="0" value="${row.price}" onchange="updateSKU(${ri},'price',this.value)" style="width:80px; padding:4px 8px; border:1px solid #d9d9d9; border-radius:4px; font-size:13px;"></td>
                    <td style="padding:4px 8px; border-bottom:1px solid #f5f5f5;"><input type="number" min="0" value="${row.stock}" onchange="updateSKU(${ri},'stock',this.value)" style="width:70px; padding:4px 8px; border:1px solid #d9d9d9; border-radius:4px; font-size:13px;"></td>
                    <td style="padding:4px 8px; border-bottom:1px solid #f5f5f5;"><input type="text" value="${row.code}" onchange="updateSKU(${ri},'code',this.value)" style="width:120px; padding:4px 8px; border:1px solid #d9d9d9; border-radius:4px; font-size:13px;" placeholder="选填"></td>
                </tr>`;
            }).join('');
            
            table.style.display = 'table';
            hint.style.display = 'none';
            
            // 同步到隐藏字段
            specInput.value = JSON.stringify({ groups: specGroups, skus: skuRows });
        }
        
        // 更新 SKU 行数据
        function updateSKU(rowIndex, field, value) {
            if (skuRows[rowIndex]) {
                skuRows[rowIndex][field] = value;
                document.getElementById('spec_data_input').value = JSON.stringify({ groups: specGroups, skus: skuRows });
            }
        }
        
        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            toggleSpecMode();
        });
        
        // 计算毛利率
        function calculateMargin() {
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
            
            let margin = 0;
            if (price > 0) {
                margin = ((price - costPrice) / price) * 100;
            }
            
            document.getElementById('margin_rate').value = margin.toFixed(2) + '%';
        }
        
        // 价格变化时也重新计算
        document.querySelector('input[name="price"]').addEventListener('input', calculateMargin);
        
        // 初始化计算
        calculateMargin();
        
        // 拦截器只在有待传文件时才启用
        async function handleSubmit(event) {
            // 没有待传文件时，让表单正常提交
            if (pendingImageFiles.length === 0 && !pendingVideoFile && !pendingCoverFile) {
                const totalImages = images.length;
                if (totalImages < 1) {
                    alert('请至少上传 1 张商品图片');
                    event.preventDefault();
                    return;
                }
                return true; // 正常提交
            }
            
            // 有待传文件：拦截提交，先上传再提交
            event.preventDefault();
            if (uploading) return;
            
            const totalImages = images.length + pendingImageFiles.length;
            if (totalImages < 1) {
                alert('请至少上传 1 张商品图片');
                return;
            }
            
            uploading = true;
            const submitBtn = document.querySelector('#productForm button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) { submitBtn.textContent = '上传文件中...'; submitBtn.disabled = true; }
            
            try {
                if (pendingImageFiles.length > 0) {
                    const urls = await uploadImageFiles(pendingImageFiles);
                    images = images.concat(urls);
                    pendingImageFiles = [];
                    document.getElementById('imagesInput').value = JSON.stringify(images);
                }
                if (pendingVideoFile) {
                    const vUrl = await uploadVideoFile();
                    document.getElementById('videoUrl').value = vUrl;
                    pendingVideoFile = null;
                }
                if (pendingCoverFile) {
                    const cUrl = await uploadCoverFile();
                    document.getElementById('videoCover').value = cUrl;
                    pendingCoverFile = null;
                }
                event.target.submit();
            } catch (error) {
                alert('文件上传失败: ' + (error.message || '请重试'));
                uploading = false;
                if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
            }
        }
        
        // 表单验证（保留用于非上传场景）
        function validateForm() {
            if (images.length < 1) {
                alert('请至少上传 1 张商品图片');
                return false;
            }
            return true;
        }
        
        // TAB 切换
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // 切换按钮状态
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // 切换内容
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
        
        let images = <?= json_encode($images, JSON_UNESCAPED_UNICODE) ?>;
        
        // 待上传文件（选完不传，保存时才统一上传）
        let pendingImageFiles = [];  // 图片 File 对象
        let pendingVideoFile = null; // 视频 File 对象
        let pendingCoverFile = null; // 封面图 File 对象
        let uploading = false;       // 防重复提交
        
        // 更新图片计数
        function updateImageCount() {
            const countEl = document.getElementById('imageCount');
            if (countEl) {
                const total = images.length + pendingImageFiles.length;
                const pending = pendingImageFiles.length > 0 ? '（' + pendingImageFiles.length + ' 张待上传）' : '';
                countEl.textContent = '已选择 ' + total + ' 张图片' + pending + '（最少 1 张，最多 10 张）';
            }
        }
        
        // 初始化：渲染图片列表和计数
        renderImages();
        updateImageCount();
        console.log('✅ 页面加载完成，初始化图片数量:', images.length);
        
        async function handleImageUpload(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;
            
            // 验证数量
            const totalCount = images.length + pendingImageFiles.length + files.length;
            if (totalCount > 10) {
                alert('最多只能上传 10 张图片，当前已有 ' + (images.length + pendingImageFiles.length) + ' 张');
                return;
            }
            
            // 不立即上传，生成 blob URL 即时预览
            files.forEach(file => {
                const blobUrl = URL.createObjectURL(file);
                pendingImageFiles.push({ file: file, blobUrl: blobUrl, name: file.name });
            });
            
            // 更新 totalCount(要加上 pendingImageFiles)
            updateImageCount();
            renderImages();
            event.target.value = '';
        }
        
        // 批量上传图片
        async function uploadImageFiles(files) {
            const urls = [];
            for (const item of files) {
                const formData = new FormData();
                formData.append('image', item.file);
                try {
                    const response = await fetch('product_upload.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        const imageUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
                        urls.push(imageUrl);
                        console.log('✅ 图片上传成功:', imageUrl);
                        // 释放 blob URL
                        URL.revokeObjectURL(item.blobUrl);
                    } else {
                        throw new Error(result.message || '上传失败');
                    }
                } catch (error) {
                    alert('图片上传失败: ' + (error.message || error));
                    throw error;
                }
            }
            return urls;
        }
        
        // removeImage 函数已在下方增强版中定义，此处删除旧版本
        
        function renderImages() {
            const container = document.getElementById('imageList');
            
            // 已存 URL 的 HTML
            const storedHtml = images.map((img, index) => {
                return `
                    <div class="image-item">
                        <img src="" 
                             data-file-key="${img}" 
                             class="preview-image stored-image"
                             style="width:100%;height:100%;object-fit:cover;">
                        <button type="button" class="remove" onclick="removeImage(this, ${index})">×</button>
                    </div>
                `;
            }).join('');
            
            // 待传文件 blob URL 的 HTML
            const pendingHtml = pendingImageFiles.map((item, idx) => {
                const globalIndex = images.length + idx;
                return `
                    <div class="image-item" style="position:relative;">
                        <img src="${item.blobUrl}" 
                             class="preview-image"
                             style="width:100%;height:100%;object-fit:cover;">
                        <span style="position:absolute;top:4px;left:4px;background:#fa8c16;color:white;font-size:10px;padding:1px 5px;border-radius:3px;">待上传</span>
                        <button type="button" class="remove" onclick="removePendingImage(${idx})">×</button>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = storedHtml + pendingHtml;
            document.getElementById('imagesInput').value = JSON.stringify(images);
            
            // 加载已存图片的签名 URL
            container.querySelectorAll('.stored-image').forEach(img => {
                const fileKey = img.dataset.fileKey;
                if (fileKey.startsWith('http://') || fileKey.startsWith('https://')) {
                    img.src = fileKey;
                } else if (fileKey.startsWith('/')) {
                    img.src = fileKey;
                } else if (fileKey.includes('/')) {
                    fetch('../api/file_api.php?action=get_preview_url&type=image&expires=7200&file_key=' + encodeURIComponent(fileKey))
                        .then(r => r.json())
                        .then(data => { if (data.success) img.src = data.url; });
                } else {
                    img.src = '/uploads/products/' + fileKey;
                }
            });
        }
        
        // 删除待传图片
        function removePendingImage(index) {
            URL.revokeObjectURL(pendingImageFiles[index].blobUrl);
            pendingImageFiles.splice(index, 1);
            updateImageCount();
            renderImages();
        }
        
        // 视频初始化
        let videoUrl = '<?= htmlspecialchars($product['video_url'] ?? '') ?>';
        const videoUploader = document.querySelector('.image-uploader[onclick*="videoInput"]');
        
        // 初始化显示已有视频（和商品图片一样大小，正方形）
        if (videoUrl) {
            const videoName = videoUrl.split('/').pop();
            
            // 判断是否是 OSS 文件 key（包含 / 但不以 http 或 https 开头）
            const isOSSVideoKey = videoUrl.includes('/') && !videoUrl.startsWith('http://') && !videoUrl.startsWith('https://');
            
            if (isOSSVideoKey) {
                // OSS 文件 key：请求签名 URL
                console.log('🎬 检测到 OSS 视频 key，请求签名 URL:', videoUrl);
                fetch('../api/file_api.php?action=get_preview_url&type=video&expires=7200&file_key=' + encodeURIComponent(videoUrl))
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ OSS 视频签名 URL 生成成功:', data.url);
                            // 保持 videoUrl 为原始 file key，只在 video 标签中使用签名 URL
                            loadUploadedVideo(data.url, videoName, videoUrl);
                        } else {
                            console.error('❌ OSS 视频签名 URL 生成失败:', videoUrl, data.message);
                            loadVideoDirectly(videoUrl, videoName);
                        }
                    })
                    .catch(error => {
                        console.error('❌ OSS 视频请求失败:', videoUrl, error);
                        loadVideoDirectly(videoUrl, videoName);
                    });
            } else {
                // 完整 URL（本地或已是签名 URL）：直接加载
                console.log('🎬 检测到完整 URL，直接加载:', videoUrl);
                loadVideoDirectly(videoUrl, videoName);
            }
        }
        
        // 加载视频的辅助函数
        function loadVideoDirectly(url, videoName) {
            videoName = videoName || url.split('/').pop();
            document.getElementById('videoUrl').value = url;
            document.getElementById('videoPreview').innerHTML = 
                '<div style="margin-top: 12px;">' +
                '<div class="image-item" style="width: 140px; height: 140px;">' +
                '<video src="' + url + '" controls style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px; background: #000;"></video>' +
                '</div>' +
                '<div style="margin-top: 8px; font-size: 12px;"><span style="color: #52c41a;">✓ 视频已上传：</span>' + videoName + 
                ' <a href="#" onclick="removeVideo(); return false;" style="color: #ff4d4f; margin-left: 12px;">删除</a></div>' +
                '</div>';
            if (videoUploader) videoUploader.style.display = 'none';
            console.log('🎬 视频加载完成:', url);
        }
        
        // 视频上传处理：选完即时 blob 预览，不立即上传
        async function handleVideoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            if (!validTypes.includes(file.type)) { alert('请上传 MP4、WebM 或 OGG 格式的视频'); return; }
            if (file.size > 100 * 1024 * 1024) { alert('视频大小不能超过 100MB'); return; }
            
            // 生成 blob URL 即时预览
            const blobUrl = URL.createObjectURL(file);
            pendingVideoFile = { file: file, blobUrl: blobUrl, name: file.name };
            
            document.getElementById('videoUrl').value = ''; // 清空旧 key，保存时会填入
            document.getElementById('videoPreview').innerHTML = 
                '<div style="margin-top: 12px;">' +
                '<div class="image-item" style="width: 140px; height: 140px;">' +
                '<video src="' + blobUrl + '" controls style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px; background: #000;"></video>' +
                '</div>' +
                '<div style="margin-top: 8px; font-size: 12px;"><span style="color: #fa8c16;">⏳ 待上传：</span>' + file.name +
                ' <a href="#" onclick="removePendingVideo(); return false;" style="color: #ff4d4f; margin-left: 12px;">删除</a></div>' +
                '</div>';
            if (videoUploader) videoUploader.style.display = 'none';
            event.target.value = '';
        }
        
        // 上传视频文件到 OSS
        async function uploadVideoFile() {
            if (!pendingVideoFile) return videoUrl;
            const formData = new FormData();
            formData.append('video', pendingVideoFile.file);
            const response = await fetch('product_upload.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                const vUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
                URL.revokeObjectURL(pendingVideoFile.blobUrl);
                console.log('✅ 视频上传成功:', vUrl);
                return vUrl;
            }
            throw new Error(result.message || '视频上传失败');
        }
        
        function removePendingVideo() {
            if (pendingVideoFile) { URL.revokeObjectURL(pendingVideoFile.blobUrl); pendingVideoFile = null; }
            document.getElementById('videoUrl').value = '';
            document.getElementById('videoPreview').innerHTML = '<div class="hint">建议视频宽高比 16:9，建议时长 8-45 秒</div>';
            document.getElementById('videoInput').value = '';
            if (videoUploader) videoUploader.style.display = 'flex';
        }
        
        // 加载已上传视频的辅助函数
        function loadUploadedVideo(url, videoName, originalFileKey = null) {
            const videoUrl = originalFileKey || url; // 优先使用原始 file key
            document.getElementById('videoUrl').value = videoUrl; // 存储原始 file key
            
            document.getElementById('videoPreview').innerHTML = 
                '<div style="margin-top: 12px;">' +
                '<div class="image-item" style="width: 140px; height: 140px;">' +
                '<video src="' + url + '" controls style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px; background: #000;"></video>' +
                '</div>' +
                '<div style="margin-top: 8px; font-size: 12px;"><span style="color: #52c41a;">✓ 视频已上传：</span>' + videoName + 
                ' <a href="#" onclick="removeVideo(); return false;" style="color: #ff4d4f; margin-left: 12px;">删除</a></div>' +
                '</div>';
            if (videoUploader) videoUploader.style.display = 'none';
            console.log('🎬 视频加载完成，存储 key:', videoUrl);
        }
        
        // 删除视频
        async function removeVideo() {
            if (!confirm('确定要删除这个视频吗？删除后不可恢复！')) {
                return;
            }
            
            const videoUrl = document.getElementById('videoUrl').value;
            
            if (videoUrl) {
                // 判断是否是 OSS 文件
                const isOSSVideo = videoUrl.includes('/') && !videoUrl.startsWith('http') && !videoUrl.startsWith('/');
                
                if (isOSSVideo) {
                    // OSS 文件：调用后端 API 删除
                    try {
                        const formData = new FormData();
                        formData.append('file_key', videoUrl);
                        formData.append('file_type', 'video');
                        
                        const response = await fetch('../api/file_api.php?action=delete_file', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            console.log('✅ OSS 视频文件删除成功:', videoUrl);
                        } else {
                            console.error('❌ OSS 视频删除失败:', data.message);
                        }
                    } catch (error) {
                        console.error('❌ 视频删除请求失败:', error);
                    }
                }
            }
            
            // 清空 UI
            document.getElementById('videoUrl').value = '';
            document.getElementById('videoPreview').innerHTML = '<div class="hint" id="videoPreview">建议视频宽高比 16:9，建议时长 8-45 秒</div>';
            document.getElementById('videoInput').value = '';
            
            // 显示上传按钮
            const videoUploader = document.querySelector('.image-uploader[onclick*="videoInput"]');
            if (videoUploader) videoUploader.style.display = 'flex';
            
            console.log('视频已删除');
        }
        
        // 视频封面上传处理
        let videoCover = '<?= htmlspecialchars($product['video_cover'] ?? '') ?>';
        const videoCoverUploader = document.querySelector('.image-uploader[onclick*="videoCoverInput"]');
        
        // 初始化显示已有封面（和商品图片一样大小，正方形）
        if (videoCover) {
            // 判断是否是 OSS 文件 key
            const isOSSCover = videoCover.includes('/') && !videoCover.startsWith('http://') && !videoCover.startsWith('https://') && !videoCover.startsWith('/');
            
            if (isOSSCover) {
                // OSS 文件 key：请求签名 URL
                console.log('🖼️ 检测到 OSS 封面图 key，请求签名 URL:', videoCover);
                fetch('../api/file_api.php?action=get_preview_url&type=image&expires=7200&file_key=' + encodeURIComponent(videoCover))
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ 封面图签名 URL 生成成功:', data.url);
                            renderVideoCover(data.url);
                        } else {
                            console.error('❌ 封面图签名 URL 生成失败:', videoCover, data.message);
                            renderVideoCover(videoCover); // 降级
                        }
                    })
                    .catch(error => {
                        console.error('❌ 封面图请求失败:', videoCover, error);
                        renderVideoCover(videoCover); // 降级
                    });
            } else {
                // 完整 URL 或本地路径：直接使用
                console.log('🖼️ 封面图直接使用:', videoCover);
                renderVideoCover(videoCover);
            }
        }
        
        // 渲染封面图的辅助函数
        function renderVideoCover(coverUrl, isPending) {
            isPending = isPending || false;
            const coverList = document.getElementById('videoCoverList');
            const badge = isPending ? '<span style="position:absolute;top:4px;left:4px;background:#fa8c16;color:white;font-size:10px;padding:1px 5px;border-radius:3px;">待上传</span>' : '';
            coverList.innerHTML = `
                <div class="image-item" style="width: 140px; height: 140px; position:relative;">
                    ${badge}
                    <img src="${coverUrl}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" class="remove" onclick="removeVideoCover()">×</button>
                </div>
            `;
            if (videoCoverUploader) videoCoverUploader.style.display = 'none';
        }
        
        async function handleVideoCoverUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) { alert('请上传 JPG/PNG/GIF/WebP 格式的封面图片'); return; }
            if (file.size > 5 * 1024 * 1024) { alert('封面图片大小不能超过 5MB'); return; }
            
            // 生成 blob URL 即时预览
            const blobUrl = URL.createObjectURL(file);
            pendingCoverFile = { file: file, blobUrl: blobUrl, name: file.name };
            
            document.getElementById('videoCover').value = ''; // 清空旧 key
            renderVideoCover(blobUrl, true); // 标记为待上传
            event.target.value = '';
        }
        
        // 上传封面文件
        async function uploadCoverFile() {
            if (!pendingCoverFile) return videoCover;
            const formData = new FormData();
            formData.append('image', pendingCoverFile.file);
            const response = await fetch('product_upload.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                const cUrl = result.storage === 'OSS' ? (result.file_key || result.url) : (result.preview_url || result.url);
                URL.revokeObjectURL(pendingCoverFile.blobUrl);
                console.log('✅ 封面上传成功:', cUrl);
                return cUrl;
            }
            throw new Error(result.message || '封面上传失败');
        }
        
        async function removeVideoCover() {
            // 如果有待上传文件，先释放 blob URL
            if (pendingCoverFile) {
                URL.revokeObjectURL(pendingCoverFile.blobUrl);
                pendingCoverFile = null;
            } else {
                // 已存文件的 OSS 删除逻辑（保留不变）
                const currentCover = document.getElementById('videoCover').value;
                if (currentCover && currentCover.includes('/') && !currentCover.startsWith('http') && !currentCover.startsWith('/')) {
                    try {
                        const formData = new FormData();
                        formData.append('file_key', currentCover);
                        formData.append('file_type', 'image');
                        await fetch('../api/file_api.php?action=delete_file', { method: 'POST', body: formData });
                    } catch (e) { console.error('删除封面失败:', e); }
                }
            }
            
            videoCover = '';
            document.getElementById('videoCoverList').innerHTML = '';
            document.getElementById('videoCover').value = '';
            document.getElementById('videoCoverInput').value = '';
            if (videoCoverUploader) videoCoverUploader.style.display = 'flex';
        }
        
        // ========================================
        // 新增：加载签名 URL 用于图片预览
        // ========================================
        document.addEventListener('DOMContentLoaded', function() {
            const previewImages = document.querySelectorAll('.preview-image');
            
            previewImages.forEach(img => {
                const fileKey = img.dataset.fileKey;
                
                if (!fileKey) return;
                
                // 情况 1：完整 HTTP URL（本地存储）- 直接使用
                if (fileKey.startsWith('http://') || fileKey.startsWith('https://')) {
                    img.src = fileKey;
                    console.log('🖼️ 本地图片（HTTP）:', fileKey);
                    return;
                }
                
                // 情况 2：以 / 开头的路径（本地存储）- 直接使用
                if (fileKey.startsWith('/')) {
                    img.src = fileKey;
                    console.log('🖼️ 本地图片（路径）:', fileKey);
                    return;
                }
                
                // 情况 3：文件 key 格式（OSS 存储）- 请求签名 URL
                // 格式如：products/202606/03/xxx.jpg
                if (fileKey.includes('/')) {
                    fetch('../api/file_api.php?action=get_preview_url&type=image&expires=7200&file_key=' + encodeURIComponent(fileKey))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                img.src = data.url;
                                console.log('✅ OSS 签名 URL 加载成功:', fileKey);
                            } else {
                                console.error('❌ OSS 签名 URL 生成失败:', fileKey, data.message);
                                // 降级：尝试直接作为路径加载
                                img.src = '/' + fileKey;
                            }
                        })
                        .catch(error => {
                            console.error('❌ OSS 请求失败:', fileKey, error);
                            // 降级：尝试直接作为路径加载
                            img.src = '/' + fileKey;
                        });
                } else {
                    // 情况 4：纯文件名 - 尝试作为本地路径
                    img.src = '/uploads/products/' + fileKey;
                    console.log('🖼️ 本地图片（文件名）:', fileKey);
                }
            });
        });
        
        // 增强版：删除图片时调用后端 API
        async function removeImage(btn, index) {
            if (!confirm('确定要删除这张图片吗？')) {
                return;
            }
            
            const imgElement = btn.parentElement.querySelector('.preview-image');
            const fileKey = imgElement ? imgElement.dataset.fileKey : null;
            
            try {
                // 只有 OSS 文件（包含 / 但不以 http 或 / 开头）才调用后端删除
                if (fileKey && fileKey.includes('/') && !fileKey.startsWith('http') && !fileKey.startsWith('/')) {
                    const formData = new FormData();
                    formData.append('file_key', fileKey);
                    formData.append('file_type', 'image');
                    
                    const response = await fetch('../api/file_api.php?action=delete_file', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (!data.success) {
                        console.error('OSS 删除失败:', data.message);
                    } else {
                        console.log('✅ OSS 文件删除成功:', fileKey);
                    }
                } else if (fileKey) {
                    // 本地文件：只删除 UI，记录日志
                    console.log('本地文件，仅删除 UI:', fileKey);
                }
                
                // 删除 UI
                if (imgElement && imgElement.parentElement) {
                    imgElement.parentElement.remove();
                }
                
                // 从数组中移除
                if (images[index]) {
                    images.splice(index, 1);
                }
                
                // 更新图片计数和隐藏输入框
                updateImageCount();
                document.getElementById('imagesInput').value = JSON.stringify(images);
                
                console.log('✅ 图片删除完成，剩余:', images.length);
                
            } catch (error) {
                console.error('❌ 删除出错:', error);
            }
        }
    </script>
</body>
</html>
<?php
} // 结束完整页面模式的判断
