<?php
class SeckillDao extends BaseDao
{
    protected function setModel(): void { $this->model = new Seckill(); }
    protected function getFillable(): array
    {
        return ['name','product_id','image','seckill_price','original_price','stock','limit_buy',
                'start_time','end_time','status','sort','description'];
    }

    public function getList(array $params, int $page, int $pageSize): array
    {
        $where = []; $b = [];
        if (!empty($params['keyword'])) { $where[]='s.name LIKE :kw'; $b['kw']="%{$params['keyword']}%"; }
        if (isset($params['status'])&&$params['status']!=='') { $where[]='s.status=:st'; $b['st']=$params['status']; }
        $wc = !empty($where)?implode(' AND ',$where):'1=1';
        $offset = ($page-1)*$pageSize;
        $t = $this->model->db()->fetchOne("SELECT COUNT(*) as t FROM seckill_activities s WHERE {$wc}",$b);
        $total = (int)($t['t']??0);
        $list = $this->model->db()->fetchAll(
            "SELECT s.*, p.name as product_name, p.images as product_images 
             FROM seckill_activities s LEFT JOIN products p ON s.product_id=p.id 
             WHERE {$wc} ORDER BY s.sort DESC, s.id DESC LIMIT {$pageSize} OFFSET {$offset}", $b
        );
        return compact('list','total','page','pageSize');
    }

    /** 获取进行中的秒杀（API用） */
    public function getActive(): array
    {
        $now = date('Y-m-d H:i:s');
        return $this->model->db()->fetchAll(
            "SELECT s.*, p.name as product_name, p.images as product_images 
             FROM seckill_activities s LEFT JOIN products p ON s.product_id=p.id 
             WHERE s.status=1 AND s.start_time <= :t1 AND s.end_time > :t2 
             AND s.sold < s.stock ORDER BY s.sort DESC, s.start_time ASC",
            ['t1'=>$now,'t2'=>$now]
        );
    }

    /** 秒杀下单（库存扣减，防超卖） */
    public function place(int $activityId, int $memberId, int $quantity = 1): array
    {
        $db = $this->model->db();
        $conn = $db->getConnection();
        $conn->query('START TRANSACTION');
        
        // 锁定活动行
        $act = $db->fetchOne("SELECT id, name, product_id, price, stock, sold, status, start_time, end_time, sort, created_at FROM seckill_activities WHERE id=:id FOR UPDATE", ['id'=>$activityId]);
        if (!$act) { $conn->query('ROLLBACK'); return [false,'活动不存在']; }
        if (!$act['status']) { $conn->query('ROLLBACK'); return [false,'活动已关闭']; }
        
        $now = date('Y-m-d H:i:s');
        if ($now < $act['start_time']) { $conn->query('ROLLBACK'); return [false,'活动未开始']; }
        if ($now > $act['end_time']) { $conn->query('ROLLBACK'); return [false,'活动已结束']; }
        
        // 库存检查
        if ($act['sold'] + $quantity > $act['stock']) {
            $conn->query('ROLLBACK');
            return [false,"库存不足，仅剩 ".($act['stock']-$act['sold'])." 件"];
        }
        
        // 限购检查
        $bought = $db->fetchOne(
            "SELECT COALESCE(SUM(quantity),0) as total FROM seckill_orders WHERE activity_id=:aid AND member_id=:mid",
            ['aid'=>$activityId,'mid'=>$memberId]
        );
        if (($bought['total']??0) + $quantity > $act['limit_buy']) {
            $conn->query('ROLLBACK'); return [false,'已达限购数量'];
        }
        
        // 扣库存
        $db->query("UPDATE seckill_activities SET sold = sold + :q WHERE id=:id", ['q'=>$quantity,'id'=>$activityId]);
        
        // 创建秒杀订单
        $db->insert('seckill_orders', [
            'activity_id'=>$activityId, 'product_id'=>$act['product_id'],
            'member_id'=>$memberId, 'quantity'=>$quantity,
            'seckill_price'=>$act['seckill_price'],
        ]);
        
        $conn->query('COMMIT');
        return [true,'秒杀成功',['price'=>$act['seckill_price']*$quantity,'product_name'=>$act['product_name']??'']];
    }
}
