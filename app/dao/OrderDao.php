<?php
/**
 * 订单 DAO
 */
class OrderDao extends BaseDao
{
    protected function setModel(): void { $this->model = new Order(); }
    
    protected function getFillable(): array
    {
        return ['order_no','member_id','total_amount','pay_amount','discount_amount','status','remark'];
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $where = []; $bindings = [];
        if (!empty($params['keyword'])) {
            $where[] = '(o.order_no LIKE :kw OR m.nickname LIKE :kw2 OR m.phone LIKE :kw3)';
            $bindings['kw'] = "%{$params['keyword']}%";
            $bindings['kw2'] = "%{$params['keyword']}%";
            $bindings['kw3'] = "%{$params['keyword']}%";
        }
        if (!empty($params['status'])) { $where[] = 'o.status = :st'; $bindings['st'] = $params['status']; }
        if (!empty($params['start_date'])) { $where[] = 'o.created_at >= :sd'; $bindings['sd'] = $params['start_date'].' 00:00:00'; }
        if (!empty($params['end_date'])) { $where[] = 'o.created_at <= :ed'; $bindings['ed'] = $params['end_date'].' 23:59:59'; }
        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        $offset = ($page - 1) * $pageSize;
        
        $count = $this->model->db()->fetchOne(
            "SELECT COUNT(*) as t FROM orders o LEFT JOIN members m ON o.member_id=m.id WHERE {$whereClause}", $bindings
        );
        $total = (int)($count['t'] ?? 0);
        
        $list = $this->model->db()->fetchAll(
            "SELECT o.*, m.nickname, m.phone 
             FROM orders o LEFT JOIN members m ON o.member_id=m.id 
             WHERE {$whereClause} ORDER BY o.created_at DESC LIMIT {$pageSize} OFFSET {$offset}",
            $bindings
        );
        
        // 附加订单商品
        if (!empty($list)) {
            $orderIds = array_column($list, 'id');
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $items = $this->model->db()->fetchAll(
                "SELECT id, order_id, product_id, product_name, product_image, product_spec, price, quantity, subtotal, created_at FROM order_items WHERE order_id IN ({$placeholders}) ORDER BY id",
                $orderIds
            );
            foreach ($list as &$order) {
                $order['items'] = array_values(array_filter($items, fn($i) => $i['order_id'] == $order['id']));
            }
        }
        
        return compact('list','total','page','pageSize');
    }

    /** 获取订单详情 */
    public function getDetail(int $id): ?array
    {
        $order = $this->model->db()->fetchOne(
            "SELECT o.*, m.nickname, m.phone FROM orders o LEFT JOIN members m ON o.member_id=m.id WHERE o.id=:id",
            ['id' => $id]
        );
        if (!$order) return null;
        $order['items'] = $this->model->db()->fetchAll("SELECT id, order_id, product_id, product_name, product_image, product_spec, price, quantity, subtotal, created_at FROM order_items WHERE order_id=:id", ['id' => $id]);
        return $order;
    }

    /** 生成订单号 */
    public function generateOrderNo(): string { return 'SO' . date('YmdHis') . str_pad(mt_rand(1,9999),4,'0',STR_PAD_LEFT); }

    // ===== 状态机 =====
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_RECEIVED  = 'received';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /** 状态流转规则：允许的流转目标 */
    public static function canTransition(string $from, string $to): bool
    {
        $rules = [
            self::STATUS_PENDING   => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID      => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED   => [self::STATUS_RECEIVED],
            self::STATUS_RECEIVED  => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];
        return in_array($to, $rules[$from] ?? []);
    }

    /** 支付 */
    public function pay(int $id): array
    {
        $order = $this->model->find($id);
        if (!$order) return [false, '订单不存在'];
        if (!self::canTransition($order['status'], self::STATUS_PAID)) return [false, '当前状态不可支付'];
        $this->model->update($id, ['status' => self::STATUS_PAID, 'pay_time' => date('Y-m-d H:i:s')]);
        return [true, '支付成功'];
    }

    /** 发货 */
    public function ship(int $id): array
    {
        $order = $this->model->find($id);
        if (!$order) return [false, '订单不存在'];
        if (!self::canTransition($order['status'], self::STATUS_SHIPPED)) return [false, '当前状态不可发货'];
        $this->model->update($id, ['status' => self::STATUS_SHIPPED, 'ship_time' => date('Y-m-d H:i:s')]);
        return [true, '发货成功'];
    }

    /** 确认收货 */
    public function receive(int $id): array
    {
        $order = $this->model->find($id);
        if (!$order) return [false, '订单不存在'];
        if (!self::canTransition($order['status'], self::STATUS_RECEIVED)) return [false, '当前状态不可收货'];
        $this->model->update($id, ['status' => self::STATUS_RECEIVED]);
        return [true, '确认收货'];
    }

    /** 完成 */
    public function complete(int $id): array
    {
        $order = $this->model->find($id);
        if (!$order) return [false, '订单不存在'];
        if (!self::canTransition($order['status'], self::STATUS_COMPLETED)) return [false, '当前状态不可完成'];
        $this->model->update($id, ['status' => self::STATUS_COMPLETED, 'complete_time' => date('Y-m-d H:i:s')]);
        return [true, '订单已完成'];
    }

    /** 取消 */
    public function cancel(int $id, string $remark = ''): array
    {
        $order = $this->model->find($id);
        if (!$order) return [false, '订单不存在'];
        if (!self::canTransition($order['status'], self::STATUS_CANCELLED)) return [false, '当前状态不可取消'];
        $data = ['status' => self::STATUS_CANCELLED, 'cancel_time' => date('Y-m-d H:i:s')];
        if ($remark) $data['remark'] = trim(($order['remark'] ?? '') . ' | 取消原因: ' . $remark);
        $this->model->update($id, $data);
        return [true, '订单已取消'];
    }

    /** 自动取消超时未付订单 */
    public function autoCancelPending(int $timeoutMinutes = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$timeoutMinutes} minutes"));
        $orders = $this->model->select(
            "status = :st AND created_at <= :ct", 
            ['st' => self::STATUS_PENDING, 'ct' => $cutoff]
        );
        $count = 0;
        foreach ($orders as $o) {
            $this->cancel($o['id'], "超时{$timeoutMinutes}分钟未支付，系统自动取消");
            $count++;
        }
        return $count;
    }
}
