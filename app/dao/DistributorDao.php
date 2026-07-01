<?php
class DistributorDao extends BaseDao
{
    protected function setModel(): void { $this->model = new Distributor(); }

    /** 成为分销员 */
    public function become(int $memberId, ?int $parentMemberId = null): array
    {
        if ($this->model->exists('member_id=:mid',['mid'=>$memberId])) return [false,'已是分销员'];
        $parentId = null;
        if ($parentMemberId) {
            $parent = $this->model->findWhere('member_id=:mid',['mid'=>$parentMemberId]);
            $parentId = $parent['id'] ?? null;
        }
        $this->model->insert([
            'member_id'=>$memberId,'parent_id'=>$parentId,
            'level'=>1,'balance'=>0,'total_commission'=>0,
            'status'=>1,
        ]);
        return [true,'已成为分销员'];
    }

    /** 计算并记录佣金 */
    public function calcCommission(int $orderId, string $orderNo, int $buyerMemberId, float $orderAmount): void
    {
        $dist = $this->model->findWhere('member_id=:mid AND status=1',['mid'=>$buyerMemberId]);
        if (!$dist) return;

        // 一级佣金（上级）
        if ($dist['parent_id']) {
            $parent = $this->model->find($dist['parent_id']);
            if ($parent && $parent['status'] == 1) {
                $level = (new DistributorLevel())->findWhere('level=:lv AND status=1',['lv'=>$parent['level']]);
                $rate1 = floatval($level['commission_rate'] ?? 5);
                $amount1 = round($orderAmount * $rate1 / 100, 2);
                if ($amount1 > 0) {
                    (new Commission())->insert([
                        'order_id'=>$orderId,'order_no'=>$orderNo,'distributor_id'=>$parent['id'],
                        'member_id'=>$buyerMemberId,'commission_level'=>1,
                        'order_amount'=>$orderAmount,'commission_rate'=>$rate1,'commission_amount'=>$amount1,
                        'status'=>0,
                    ]);
                    $this->model->increment($parent['id'],'balance',$amount1);
                    $this->model->increment($parent['id'],'total_commission',$amount1);
                }
            }

            // 二级佣金（上上级）
            $gp = $this->model->find($parent['parent_id'] ?? 0);
            if ($gp && $gp['status'] == 1) {
                $level2 = (new DistributorLevel())->findWhere('level=:lv AND status=1',['lv'=>$gp['level']]);
                $rate2 = floatval($level2['commission_rate2'] ?? 2);
                $amount2 = round($orderAmount * $rate2 / 100, 2);
                if ($amount2 > 0) {
                    (new Commission())->insert([
                        'order_id'=>$orderId,'order_no'=>$orderNo,'distributor_id'=>$gp['id'],
                        'member_id'=>$buyerMemberId,'commission_level'=>2,
                        'order_amount'=>$orderAmount,'commission_rate'=>$rate2,'commission_amount'=>$amount2,
                        'status'=>0,
                    ]);
                    $this->model->increment($gp['id'],'balance',$amount2);
                    $this->model->increment($gp['id'],'total_commission',$amount2);
                }
            }
        }
    }

    /** 获取分销员列表 */
    public function getList(array $params, int $page, int $pageSize): array
    {
        $where=[];$b=[];
        if(isset($params['status'])&&$params['status']!==''){$where[]='d.status=:st';$b['st']=$params['status'];}
        $wc=!empty($where)?implode(' AND ',$where):'1=1';
        $offset=($page-1)*$pageSize;
        $t=$this->model->db()->fetchOne("SELECT COUNT(*) as t FROM distributors d WHERE {$wc}",$b);
        $total=(int)($t['t']??0);
        $list=$this->model->db()->fetchAll(
            "SELECT d.*, m.nickname, m.phone, m.avatar FROM distributors d LEFT JOIN members m ON d.member_id=m.id WHERE {$wc} ORDER BY d.id DESC LIMIT {$pageSize} OFFSET {$offset}",$b
        );
        return compact('list','total','page','pageSize');
    }

    /** 佣金记录 */
    public function commissionLogs(int $distributorId, int $page, int $pageSize): array
    {
        $offset=($page-1)*$pageSize;
        $t=(new Commission())->count('distributor_id=:did',['did'=>$distributorId]);
        $list=$this->model->db()->fetchAll(
            "SELECT id, distributor_id, order_id, amount, rate, status, remark, created_at FROM commissions WHERE distributor_id=:did ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}",['did'=>$distributorId]
        );
        return compact('list','total','page','pageSize');
    }

    /** 提现申请 */
    public function withdraw(int $distributorId, float $amount): array
    {
        $dist = $this->model->find($distributorId);
        if(!$dist) return [false,'分销员不存在'];
        if($amount <= 0) return [false,'金额无效'];
        if($amount > $dist['balance']) return [false,'余额不足'];
        
        $this->model->decrement($distributorId,'balance',$amount);
        (new Withdrawal())->insert([
            'distributor_id'=>$distributorId,'member_id'=>$dist['member_id'],
            'amount'=>$amount,'status'=>0,
        ]);
        return [true,'提现申请已提交'];
    }
}
