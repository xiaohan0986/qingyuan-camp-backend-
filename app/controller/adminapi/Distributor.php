<?php
class DistributorController extends BaseController
{
    private DistributorDao $dao;
    public function __construct() { parent::__construct(); $this->dao = new DistributorDao(); }

    public function index(): JsonResponse {
        [$p,$ps] = $this->getPage();
        $params = ['status'=>$this->request->get('status')];
        $r = $this->dao->getList(array_filter($params,fn($v)=>$v!==''&&$v!==null), $p, $ps);
        return $this->success($r);
    }
    /** 申请成为分销员 */
    public function apply(): JsonResponse {
        $mid = $this->request->int('member_id');
        $pid = $this->request->int('parent_member_id', 0);
        if(!$mid) return $this->fail('member_id 必填');
        [$ok,$msg] = $this->dao->become($mid, $pid ?: null);
        return $ok ? $this->success(null,$msg) : $this->fail($msg);
    }
    /** 分销员佣金记录 */
    public function commissions(int $id): JsonResponse {
        [$p,$ps] = $this->getPage();
        $r = $this->dao->commissionLogs($id, $p, $ps);
        return $this->success($r);
    }
    /** 提现申请 */
    public function withdraw(): JsonResponse {
        $did = $this->request->int('distributor_id');
        $amount = $this->request->float('amount');
        if(!$did||$amount<=0) return $this->fail('参数不全');
        [$ok,$msg] = $this->dao->withdraw($did, $amount);
        return $ok ? $this->success(null,$msg) : $this->fail($msg);
    }
    /** 分销等级列表 */
    public function levels(): JsonResponse {
        $list = (new DistributorLevel())->select('status=1');
        return $this->success($list);
    }
}
