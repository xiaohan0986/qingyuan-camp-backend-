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
    $product = $db->fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $id]);
    if (!$product) {
        die('商品不存在');
    }
    $pageTitle = '编辑商品';
} else {
    $pageTitle = '新增商品';
}
