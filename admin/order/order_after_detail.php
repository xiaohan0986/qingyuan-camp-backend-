<?php
/**
 * 售后详情页面（用于抽屉加载）
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$db = Database::getInstance();
$afterId = intval($_GET['id'] ?? 0);

if (!$afterId) {
    echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">售后 ID 无效</div>';
    exit;
}

try {
    // 获取售后主信息
    $after = $db->fetchOne("SELECT * FROM order_after WHERE id = ?", [$afterId]);
    
    if (!$after) {
        echo '<div style="text-align: center; padding: 40px; color: #ff4d4f;">售后单不存在</div>';
        exit;
    }
    
    // 获取订单信息
    $order = null;
    if (!empty($after['order_no'])) {
        $order = $db->fetchOne("SELECT * FROM orders WHERE order_no = ?", [$after['order_no']]);
    }
    
    // 获取会员信息
    $member = null;
    if (!empty($order['member_id'])) {
        $member = $db->fetchOne("SELECT * FROM members WHERE id = ?", [$order['member_id']]);
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
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #262626;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
    }
    
    .section-mark {
        display: inline-block;
        width: 4px;
        height: 16px;
        background: #1890ff;
        margin-right: 8px;
        vertical-align: middle;
        border-radius: 2px;
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
    
    .after-type-badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    .after-type-badge.return_refund { background: #fff2f0; color: #ff4d4f; }
    .after-type-badge.refund_only { background: #e6f7ff; color: #1890ff; }
    .after-type-badge.exchange { background: #f6ffed; color: #52c41a; }
    
    .after-status-badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }
    
    .after-status-badge.processing { background: #e6f7ff; color: #1890ff; }
    .after-status-badge.handled { background: #f6ffed; color: #52c41a; }
    .after-status-badge.rejected { background: #fff2f0; color: #ff4d4f; }
    .after-status-badge.cancelled { background: #fafafa; color: #8c8c8c; }
</style>

<!-- 售后单基本信息 -->
<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
    <?php if (!empty($after['goods_image'])): ?>
        <img src="<?= htmlspecialchars($after['goods_image']) ?>" alt="商品" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0;">
    <?php else: ?>
        <img src="https://via.placeholder.com/100" alt="商品" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #f0f0f0;">
    <?php endif; ?>
    <div style="flex: 1;">
        <div style="font-size: 16px; font-weight: 600; color: #262626; margin-bottom: 8px;">
            <?= htmlspecialchars($after['goods_name']) ?>
        </div>
        <div style="font-size: 14px; color: #8c8c8c;">
            售后单号：<?= htmlspecialchars($after['after_no']) ?>
        </div>
        <div style="font-size: 14px; color: #8c8c8c;">
            订单号：<?= htmlspecialchars($after['order_no']) ?>
        </div>
    </div>
</div>

<!-- 售后状态等信息 -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px;">
    <div style="text-align: center;">
        <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">售后类型</div>
        <div>
            <span class="after-type-badge <?= $after['after_type'] ?>">
                <?= ['return_refund'=>'退货退款','refund_only'=>'仅退款','exchange'=>'换货'][$after['after_type']] ?? $after['after_type'] ?>
            </span>
        </div>
    </div>
    <div style="text-align: center;">
        <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">售后状态</div>
        <div>
            <span class="after-status-badge <?= $after['after_status'] ?>">
                <?= ['processing'=>'进行中','handled'=>'已处理','rejected'=>'已拒绝','cancelled'=>'已取消'][$after['after_status']] ?? $after['after_status'] ?>
            </span>
        </div>
    </div>
    <div style="text-align: center;">
        <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">退款金额</div>
        <div style="color: #ff4d4f; font-weight: 600; font-size: 16px;">¥<?= number_format($after['refund_amount'], 2) ?></div>
    </div>
    <div style="text-align: center;">
        <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">申请时间</div>
        <div style="color: #262626;"><?= date('Y-m-d H:i:s', strtotime($after['apply_time'])) ?></div>
    </div>
    <div style="text-align: center;">
        <div style="color: #8c8c8c; font-size: 13px; margin-bottom: 6px;">处理时间</div>
        <div style="color: #262626;"><?= !empty($after['handle_time']) ? date('Y-m-d H:i:s', strtotime($after['handle_time'])) : '未处理' ?></div>
    </div>
</div>

<!-- TAB 切换按钮 -->
<div class="detail-tabs">
    <button class="tab-btn active" data-tab="after">售后信息</button>
    <button class="tab-btn" data-tab="order">订单信息</button>
    <button class="tab-btn" data-tab="user">用户信息</button>
</div>

<!-- 售后信息 TAB -->
<div id="tab-after" class="tab-content active">
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>售后详情</div>
        <div class="info-row">
            <div>
                <div class="info-label">售后原因</div>
                <div class="info-value"><?= htmlspecialchars($after['reason'] ?? '无') ?></div>
            </div>
            <div>
                <div class="info-label">商品数量</div>
                <div class="info-value"><?= $after['goods_quantity'] ?? 0 ?> 件</div>
            </div>
            <div>
                <div class="info-label">商品单价</div>
                <div class="info-value">¥<?= number_format($after['goods_price'], 2) ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">售后说明</div>
                <div class="info-value" style="grid-column: span 3;"><?= htmlspecialchars($after['description'] ?? '无') ?></div>
            </div>
        </div>
        <?php if (!empty($after['images'])): ?>
        <div class="info-row">
            <div>
                <div class="info-label">凭证图片</div>
                <div class="info-value" style="grid-column: span 3;">
                    <?php 
                    $images = json_decode($after['images'], true);
                    if (is_array($images)):
                    ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($images as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #f0f0f0;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <div>
                <div class="info-label">处理备注</div>
                <div class="info-value" style="grid-column: span 3;"><?= htmlspecialchars($after['handle_remark'] ?? '无') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 订单信息 TAB -->
<div id="tab-order" class="tab-content">
    <?php if ($order): ?>
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>订单信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">订单状态</div>
                <div class="info-value">
                    <span style="padding: 4px 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px;">
                        <?= ['pending_pay'=>'待付款','pending_ship'=>'待发货','pending_receive'=>'待收货','completed'=>'已完成','cancelled'=>'已取消'][$order['status']] ?? $order['status'] ?>
                    </span>
                </div>
            </div>
            <div>
                <div class="info-label">实付金额</div>
                <div class="info-value" style="color: #ff4d4f; font-weight: 600;">¥<?= number_format($order['pay_amount'], 2) ?></div>
            </div>
            <div>
                <div class="info-label">支付方式</div>
                <div class="info-value"><?= ['wechat'=>'微信支付','balance'=>'余额支付','offline'=>'线下支付'][$order['payment_method']] ?? '未知' ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">创建时间</div>
                <div class="info-value"><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></div>
            </div>
            <div>
                <div class="info-label">支付时间</div>
                <div class="info-value"><?= !empty($order['pay_time']) ? date('Y-m-d H:i:s', strtotime($order['pay_time'])) : '未支付' ?></div>
            </div>
            <div>
                <div class="info-label">发货方式</div>
                <div class="info-value"><?= ['express'=>'快递','pickup'=>'自提','none'=>'无需配送','merchant'=>'商家配送'][$order['delivery_method']] ?? '未知' ?></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #8c8c8c;">订单信息不存在</div>
    <?php endif; ?>
</div>

<!-- 用户信息 TAB -->
<div id="tab-user" class="tab-content">
    <?php if ($member): ?>
    <div class="detail-section">
        <div class="section-title"><span class="section-mark"></span>用户信息</div>
        <div class="info-row">
            <div>
                <div class="info-label">用户昵称</div>
                <div class="info-value"><?= htmlspecialchars($member['nickname'] ?? '未知') ?></div>
            </div>
            <div>
                <div class="info-label">用户 ID</div>
                <div class="info-value"><?= $member['id'] ?? '未知' ?></div>
            </div>
            <div>
                <div class="info-label">绑定电话</div>
                <div class="info-value"><?= htmlspecialchars($member['phone'] ?? '未绑定') ?></div>
            </div>
        </div>
        <div class="info-row">
            <div>
                <div class="info-label">会员等级</div>
                <div class="info-value"><?= htmlspecialchars($member['level'] ?? '未知') ?></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #8c8c8c;">用户信息不存在</div>
    <?php endif; ?>
</div>
