<?php
/**
 * 订单详情页面（用于抽屉加载）
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">订单 ID 无效</div>';
    exit;
}

try {
    // 获取订单主信息
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

    if (!$order) {
        echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">订单不存在</div>';
        exit;
    }

    // 获取订单商品列表
    $items = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);

    // 获取会员信息
    $member = null;
    if (!empty($order['member_id'])) {
        $member = $db->fetchOne("SELECT * FROM members WHERE id = ?", [$order['member_id']]);
    }

    // 获取商品信息（第一个商品）
    $product = null;
    if (!empty($items[0]['product_id'])) {
        $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$items[0]['product_id']]);
    }
} catch (Exception $e) {
    echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">查询失败：' . $e->getMessage() . '</div>';
    exit;
}
?>

<style>
    .detail-section {
        margin-bottom: 24px;
        padding-bottom: 0;
        border-bottom: none;
    }

    /* TAB 切换样式 */
    .detail-tabs {
        display: flex;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 24px;
    }

    .tab-btn {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        font-size: 14px;
        color: #8c8c8c;
        transition: all 0.3s;
    }

    .tab-btn:hover {
        color: #1890ff;
    }

    .tab-btn.active {
        color: #1890ff;
        border-bottom-color: #1890ff;
        font-weight: 600;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .detail-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 16px;
    }

    .info-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 12px;
        font-size: 14px;
    }

    .info-label {
        color: #8c8c8c;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .info-value {
        color: #262626;
        font-size: 14px;
    }

    /* 蓝色竖向长方块 */
    .section-mark {
        display: inline-block;
        width: 4px;
        height: 16px;
        background: #1890ff;
        margin-right: 8px;
        vertical-align: middle;
        border-radius: 2px;
    }

    .order-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .order-status-badge.pending_pay { background: #fff2f0; color: #ff4d4f; }
    .order-status-badge.pending_ship { background: #e6f7ff; color: #1890ff; }
    .order-status-badge.pending_receive { background: #f6ffed; color: #52c41a; }
    .order-status-badge.completed { background: #f0f0f0; color: #666; }
    .order-status-badge.cancelled { background: #fafafa; color: #8c8c8c; }

    .item-row {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-image {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #f0f0f0;
    }

    .item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .item-name {
        font-size: 14px;
        color: #262626;
        font-weight: 500;
    }

    .item-spec {
        font-size: 12px;
        color: #8c8c8c;
        margin-top: 4px;
    }

    .item-price {
        font-size: 14px;
        color: #ff4d4f;
        font-weight: 600;
        margin-top: 8px;
    }

    .item-quantity {
        font-size: 13px;
        color: #8c8c8c;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 14px;
    }

    .price-label {
        color: #8c8c8c;
    }

    .price-value {
        color: #262626;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 16px 0;
        margin-top: 12px;
        border-top: 2px solid #f0f0f0;
        font-size: 16px;
        font-weight: 600;
    }

    .total-label {
        color: #262626;
    }

    .total-value {
        color: #ff4d4f;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #f0f0f0;
    }

    .btn {
        padding: 10px 24px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #1890ff;
        color: white;
    }

    .btn-primary:hover {
        background: #40a9ff;
    }

    .btn-default {
        background: #f5f5f5;
        color: #666;
    }

    .btn-default:hover {
        background: #e6e6e6;
    }
</style>

<div class="detail-section">
    <!-- 商品图片和订单号 -->
    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
        <?php if (!empty($items[0]['product_image'])): ?>
            <img src="<?= htmlspecialchars($items[0]['product_image']) ?>" alt="商品" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0;">
        <?php else: ?>
            <img src="https://via.placeholder.com/100" alt="商品" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0;">
        <?php endif; ?>
        <div style="flex: 1;">
            <div style="font-size: 16px; font-weight: 600; color: #262626; margin-bottom: 8px;">
                <?= htmlspecialchars($items[0]['product_name'] ?? '商品名称') ?>
            </div>
            <div style="font-size: 14px; color: #8c8c8c;">
                订单号：<?= htmlspecialchars($order['order_no']) ?>
            </div>
        </div>
    </div>

    <!-- 订单状态等信息 -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px;">
        <div style="text-align: center;">
            <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">订单状态</div>
            <div>
                <span class="order-status-badge <?= $order['status'] ?>">
                    <?= ['pending_pay'=>'待付款','pending_ship'=>'待发货','pending_receive'=>'待收货','completed'=>'已完成','cancelled'=>'已取消'][$order['status']] ?? $order['status'] ?>
                </span>
            </div>
        </div>
        <div style="text-align: center;">
            <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">实付金额</div>
            <div style="color: #ff4d4f; font-weight: 600; font-size: 16px;">¥<?= number_format($order['pay_amount'], 2) ?></div>
        </div>
        <div style="text-align: center;">
            <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">支付方式</div>
            <div style="color: #262626;"><?= ['wechat'=>'微信支付','balance'=>'余额支付','offline'=>'线下支付'][$order['payment_method']] ?? '未知' ?></div>
        </div>
        <div style="text-align: center;">
            <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">预约时间</div>
            <div style="color: #262626;"><?= !empty($order['appoint_time']) ? date('Y-m-d H:i', strtotime($order['appoint_time'])) : '未预约' ?></div>
        </div>
        <div style="text-align: center;">
            <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">支付时间</div>
            <div style="color: #262626;"><?= !empty($order['pay_time']) ? date('Y-m-d H:i:s', strtotime($order['pay_time'])) : '未支付' ?></div>
        </div>
    </div>
</div>

<!-- TAB 切换按钮 -->
<div class="detail-tabs">
    <button class="tab-btn active" data-tab="order">订单信息</button>
    <button class="tab-btn" data-tab="product">商品信息</button>
    <button class="tab-btn" data-tab="service">服务信息</button>
    <button class="tab-btn" data-tab="store">店铺信息</button>
</div>

<!-- 订单信息 TAB -->
<div id="tab-order" class="tab-content active">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>用户信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">用户昵称</div>
                <div class="info-value"><?= htmlspecialchars($member['nickname'] ?? '未知') ?></div>
            </div>
            <div>
                <div class="info-label">用户 ID</div>
                <div class="info-value"><?= $order['member_id'] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">绑定电话</div>
                <div class="info-value"><?= htmlspecialchars($member['phone'] ?? '未绑定') ?></div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>预约信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">联系人</div>
                <div class="info-value"><?= htmlspecialchars($order['shipping_name'] ?? '暂无') ?></div>
            </div>
            <div>
                <div class="info-label">联系电话</div>
                <div class="info-value"><?= htmlspecialchars($order['shipping_phone'] ?? '暂无') ?></div>
            </div>
            <div>
                <div class="info-label">预约时间</div>
                <div class="info-value"><?= !empty($order['appoint_time']) ? date('Y-m-d H:i', strtotime($order['appoint_time'])) : '暂无' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">上门地址</div>
                <div class="info-value" style="grid-column: span 3;"><?= htmlspecialchars($order['shipping_address'] ?? '暂无') ?></div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>订单信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">创建时间</div>
                <div class="info-value"><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></div>
            </div>
            <div>
                <div class="info-label">商品总数</div>
                <div class="info-value"><?= array_sum(array_column($items, 'quantity')) ?> 件</div>
            </div>
            <div>
                <div class="info-label">实付金额</div>
                <div class="info-value" style="color: #ff4d4f; font-weight: 600;">¥<?= number_format($order['pay_amount'], 2) ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">优惠券金额</div>
                <div class="info-value"><?= !empty($order['discount_amount']) ? '-¥'.number_format($order['discount_amount'], 2) : '¥0.00' ?></div>
            </div>
            <div>
                <div class="info-label">订单总价</div>
                <div class="info-value">¥<?= number_format($order['total_amount'], 2) ?></div>
            </div>
            <div>
                <div class="info-label">会员商品优惠</div>
                <div class="info-value"><?= !empty($order['member_discount']) ? '-¥'.number_format($order['member_discount'], 2) : '¥0.00' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">发货方式</div>
                <div class="info-value"><?= ['express'=>'快递','pickup'=>'自提','none'=>'无需配送','merchant'=>'商家配送'][$order['delivery_method']] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">支付运费</div>
                <div class="info-value"><?= !empty($order['freight_amount']) ? '¥'.number_format($order['freight_amount'], 2) : '¥0.00' ?></div>
            </div>
            <div>
                <div class="info-label">一级佣金</div>
                <div class="info-value"><?= !empty($order['commission1']) ? '¥'.number_format($order['commission1'], 2) : '¥0.00' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">二级佣金</div>
                <div class="info-value"><?= !empty($order['commission2']) ? '¥'.number_format($order['commission2'], 2) : '¥0.00' ?></div>
            </div>
            <div>
                <div class="info-label">推广人</div>
                <div class="info-value"><?= !empty($order['promoter_id']) ? 'ID:'.$order['promoter_id'] : '无' ?></div>
            </div>
            <div>
                <div class="info-label">商品类型</div>
                <div class="info-value"><?= ['normal'=>'普通商品','virtual'=>'虚拟商品','service'=>'服务类'][$order['product_type']] ?? '未知' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">活动类型</div>
                <div class="info-value"><?= ['normal'=>'普通订单','bargain'=>'砍价订单','seckill'=>'秒杀订单','group'=>'拼团订单','points'=>'积分商城'][$order['source']] ?? '未知' ?></div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>买家留言</div>
        <div style="padding: 12px 16px; background: #fafafa; border-radius: 6px; color: #262626; min-height: 20px;">
            <?= htmlspecialchars($order['user_message'] ?? '无留言') ?>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>店铺备注</div>
        <div style="padding: 12px 16px; background: #fffbe6; border: 1px solid #ffe58f; border-radius: 6px; color: #262626; min-height: 20px;">
            <?= htmlspecialchars($order['remark'] ?? '无备注') ?>
        </div>
    </div>
</div>

<!-- 商品信息 TAB -->
<div id="tab-product" class="tab-content">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>基本信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">商品 ID</div>
                <div class="info-value"><?= $product['id'] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">商品名称</div>
                <div class="info-value"><?= htmlspecialchars($product['name'] ?? $items[0]['product_name'] ?? '未知') ?></div>
            </div>
            <div>
                <div class="info-label">商品分类</div>
                <div class="info-value"><?= htmlspecialchars($product['category_name'] ?? '未知') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">封面图</div>
                <div class="info-value">
                    <?php if (!empty($product['main_image'])): ?>
                        <img src="<?= htmlspecialchars($product['main_image']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0;">
                    <?php elseif (!empty($items[0]['product_image'])): ?>
                        <img src="<?= htmlspecialchars($items[0]['product_image']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0;">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/60" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0;">
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="info-label">轮播图</div>
                <div class="info-value">
                    <?php
                    $gallery = !empty($product['gallery']) ? json_decode($product['gallery'], true) : [];
                    if (is_array($gallery) && count($gallery) > 0):
                    ?>
                        <div style="display: flex; gap: 4px;">
                            <?php foreach (array_slice($gallery, 0, 4) as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #f0f0f0;">
                            <?php endforeach; ?>
                            <?php if (count($gallery) > 4): ?>
                                <div style="width: 40px; height: 40px; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #8c8c8c;">+<?= count($gallery) - 4 ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span style="color: #8c8c8c;">无轮播图</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="info-label">店铺分类</div>
                <div class="info-value"><?= htmlspecialchars($product['shop_category'] ?? '未知') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">商品简介</div>
                <div class="info-value" style="grid-column: span 3;"><?= htmlspecialchars($product['brief'] ?? '无简介') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">品牌选择</div>
                <div class="info-value"><?= htmlspecialchars($product['brand'] ?? '无品牌') ?></div>
            </div>
            <div>
                <div class="info-label">单位</div>
                <div class="info-value"><?= htmlspecialchars($product['unit'] ?? '件') ?></div>
            </div>
            <div>
                <div class="info-label">标签</div>
                <div class="info-value">
                    <?php
                    $tags = !empty($product['tags']) ? json_decode($product['tags'], true) : [];
                    if (is_array($tags) && count($tags) > 0):
                    ?>
                        <?php foreach ($tags as $tag): ?>
                            <span style="display: inline-block; padding: 2px 8px; background: #f0f0f0; border-radius: 4px; font-size: 12px; color: #666; margin-right: 4px;"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: #8c8c8c;">无标签</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>营销信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">店铺推荐</div>
                <div class="info-value"><?= !empty($product['is_recommend']) ? '<span style="color: #52c41a;">✓ 已推荐</span>' : '<span style="color: #8c8c8c;">未推荐</span>' ?></div>
            </div>
            <div>
                <div class="info-label">收藏人数</div>
                <div class="info-value"><?= $product['favorite_count'] ?? 0 ?> 人</div>
            </div>
            <div>
                <div class="info-label">活动标签</div>
                <div class="info-value">
                    <?php
                    $activityTags = [];
                    if (!empty($product['is_hot'])) $activityTags[] = '<span style="display: inline-block; padding: 2px 8px; background: #ff4d4f; color: white; border-radius: 4px; font-size: 12px; margin-right: 4px;">热门</span>';
                    if (!empty($product['is_new'])) $activityTags[] = '<span style="display: inline-block; padding: 2px 8px; background: #1890ff; color: white; border-radius: 4px; font-size: 12px; margin-right: 4px;">新品</span>';
                    if (!empty($product['is_best'])) $activityTags[] = '<span style="display: inline-block; padding: 2px 8px; background: #fa8c16; color: white; border-radius: 4px; font-size: 12px; margin-right: 4px;">精品</span>';
                    echo empty($activityTags) ? '<span style="color: #8c8c8c;">无活动</span>' : implode('', $activityTags);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 服务信息 TAB -->
<div id="tab-service" class="tab-content">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>服务信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">配送方式</div>
                <div class="info-value"><?= ['express'=>'快递','pickup'=>'自提','none'=>'无需配送','merchant'=>'商家配送'][$order['delivery_method']] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">订单来源</div>
                <div class="info-value"><?= ['normal'=>'普通订单','bargain'=>'砍价订单','seckill'=>'秒杀订单','group'=>'拼团订单','points'=>'积分商城'][$order['source']] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">会员等级</div>
                <div class="info-value"><?= htmlspecialchars($member['level'] ?? '未知') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">会员昵称</div>
                <div class="info-value"><?= htmlspecialchars($member['nickname'] ?? '未知') ?></div>
            </div>
            <div>
                <div class="info-label">手机号</div>
                <div class="info-value"><?= htmlspecialchars($member['phone'] ?? '未知') ?></div>
            </div>
        </div>
        <?php if (!empty($order['shipping_address'])): ?>
        <div class="info-row">
            <div>
                <div class="info-label">收货地址</div>
                <div class="info-value" style="grid-column: span 3;"><?= htmlspecialchars($order['shipping_address']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 店铺信息 TAB -->
<div id="tab-store" class="tab-content">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>店铺信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">店铺名称</div>
                <div class="info-value"><?= htmlspecialchars($product['shop_name'] ?? '未知') ?></div>
            </div>
            <div>
                <div class="info-label">店铺类型</div>
                <div class="info-value"><?= ['official'=>'官方旗舰店','flagship'=>'旗舰店','specialty'=>'专营店','store'=>'专卖店','individual'=>'个人店铺'][$product['shop_type'] ?? 'unknown'] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">店铺类别</div>
                <div class="info-value"><?= htmlspecialchars($product['shop_category'] ?? '未知') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="action-buttons">
    <?php if ($order['status'] === 'pending_ship' && $order['pay_status']): ?>
    <button class="btn btn-primary" onclick="alert('发货功能开发中')">📦 发货</button>
    <?php endif; ?>
    <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
    <button class="btn btn-default" onclick="alert('关闭订单功能开发中')" style="color: #ff4d4f;">关闭订单</button>
    <?php endif; ?>
    <button class="btn btn-default" onclick="window.print()">🖨️ 打印</button>
</div>

</body>
</html>