<?php
/**
 * 优惠券 DAO
 */
class CouponDao extends BaseDao
{
    protected function setModel(): void
    {
        $this->model = new Coupon();
    }

    protected function getFillable(): array
    {
        return [
            'name', 'description', 'type', 'value',
            'min_amount', 'max_amount', 'use_type', 'use_range',
            'total', 'per_limit', 'start_time', 'end_time',
            'receive_start', 'receive_end',
            'use_start_type', 'use_start_day', 'use_end_day',
            'merchant_id', 'sort', 'instructions', 'is_show', 'status',
        ];
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $where = [];
        $bindings = [];

        if (!empty($params['keyword'])) {
            $where[] = 'name LIKE :kw';
            $bindings['kw'] = "%{$params['keyword']}%";
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = 'status = :st';
            $bindings['st'] = $params['status'];
        }
        if (isset($params['type']) && $params['type'] !== '') {
            $where[] = 'type = :tp';
            $bindings['tp'] = $params['type'];
        }

        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        $offset = ($page - 1) * $pageSize;

        $countResult = $this->model->db()->fetchOne(
            "SELECT COUNT(*) as total FROM coupons WHERE {$whereClause}", $bindings
        );
        $total = (int)($countResult['total'] ?? 0);

        $list = $this->model->db()->fetchAll(
            "SELECT id, name, description, type, value, min_amount, max_amount, use_type, use_range, total, per_limit, received, start_time, end_time, receive_start, receive_end, use_start_type, use_start_day, use_end_day, sort, instructions, is_show, status, created_at FROM coupons WHERE {$whereClause} ORDER BY sort DESC, id DESC LIMIT {$pageSize} OFFSET {$offset}",
            $bindings
        );

        return compact('list', 'total', 'page', 'pageSize');
    }
}

/**
 * 优惠券领取记录 DAO
 */
class CouponReceiveLogDao
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getReceiveLogs(int $couponId, int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;
        $countResult = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM coupon_receive_log WHERE coupon_id = :cid",
            ['cid' => $couponId]
        );
        $total = (int)($countResult['total'] ?? 0);

        $list = $this->db->fetchAll(
            "SELECT r.*, m.nickname, m.phone 
             FROM coupon_receive_log r 
             LEFT JOIN members m ON r.member_id = m.id 
             WHERE r.coupon_id = :cid 
             ORDER BY r.receive_time DESC 
             LIMIT {$pageSize} OFFSET {$offset}",
            ['cid' => $couponId]
        );

        return compact('list', 'total', 'page', 'pageSize');
    }

    /**
     * 用户领取优惠券
     */
    public function receive(int $couponId, int $memberId): array
    {
        // 检查优惠券
        $coupon = $this->db->fetchOne("SELECT id, name, description, type, value, min_amount, max_amount, use_type, use_range, total, per_limit, received, start_time, end_time, receive_start, receive_end, use_start_type, use_start_day, use_end_day, sort, instructions, is_show, status, created_at, updated_at FROM coupons WHERE id = :id", ['id' => $couponId]);
        if (!$coupon) return ['success' => false, 'msg' => '优惠券不存在'];
        if (!$coupon['status']) return ['success' => false, 'msg' => '优惠券已下架'];
        if ($coupon['received'] >= $coupon['total']) return ['success' => false, 'msg' => '已领完'];
        if ($coupon['receive_start'] && date('Y-m-d H:i:s') < $coupon['receive_start']) return ['success' => false, 'msg' => '未到领取时间'];
        if ($coupon['receive_end'] && date('Y-m-d H:i:s') > $coupon['receive_end']) return ['success' => false, 'msg' => '已过领取时间'];

        // 检查每人限领
        $userReceived = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM coupon_receive_log WHERE coupon_id = :cid AND member_id = :mid",
            ['cid' => $couponId, 'mid' => $memberId]
        );
        if (($userReceived['cnt'] ?? 0) >= $coupon['per_limit']) {
            return ['success' => false, 'msg' => '已达领取上限'];
        }

        // 领取
        $this->db->execute(
            "INSERT INTO coupon_receive_log (coupon_id, member_id, status) VALUES (:cid, :mid, 0)",
            ['cid' => $couponId, 'mid' => $memberId]
        );
        $this->db->execute("UPDATE coupons SET received = received + 1 WHERE id = :id", ['id' => $couponId]);

        return ['success' => true, 'msg' => '领取成功'];
    }

    /**
     * 获取用户可用优惠券
     */
    public function getUserCoupons(int $memberId, float $orderAmount = 0, array $productIds = []): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, c.name, c.type, c.value, c.min_amount, c.max_amount, c.use_type, c.use_range,
                    c.receive_start, c.receive_end, c.use_start_type, c.use_start_day, c.use_end_day,
                    r.receive_time
             FROM coupon_receive_log r 
             JOIN coupons c ON r.coupon_id = c.id 
             WHERE r.member_id = :mid AND r.status = 0 AND c.status = 1
             ORDER BY c.value DESC, r.receive_time ASC",
            ['mid' => $memberId]
        );
    }

    /**
     * 使用优惠券
     */
    public function useCoupon(int $receiveId, int $orderId, string $orderNo): bool
    {
        $this->db->execute(
            "UPDATE coupon_receive_log SET status = 1, used_at = NOW(), order_id = :oid, order_no = :ono WHERE id = :id AND status = 0",
            ['id' => $receiveId, 'oid' => $orderId, 'ono' => $orderNo]
        );
        return true;
    }
}
