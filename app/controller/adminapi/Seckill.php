<?php
class SeckillController extends BaseController
{
    private SeckillDao $dao;
    public function __construct() { parent::__construct(); $this->dao = new SeckillDao(); }

    public function index(): JsonResponse {
        [$page, $pageSize] = $this->getPage();
        $params = ['keyword'=>$this->request->string('keyword'),'status'=>$this->request->get('status')];
        $r = $this->dao->getList(array_filter($params,fn($v)=>$v!==''&&$v!==null), $page, $pageSize);
        return $this->success($r);
    }
    public function show(int $id): JsonResponse {
        $a = $this->dao->find($id); if(!$a) return $this->notFound(); return $this->success($a);
    }
    public function store(): JsonResponse {
        $e = $this->validate(['name'=>'required','product_id'=>'required|integer','seckill_price'=>'required|numeric','stock'=>'required|integer|min:1','start_time'=>'required','end_time'=>'required']);
        if($e) return $this->fail($e);
        $id = $this->dao->create($this->request->all());
        return $this->success(['id'=>$id],'秒杀活动创建成功');
    }
    public function update(int $id): JsonResponse {
        if(!$this->dao->find($id)) return $this->notFound();
        $this->dao->update($id, $this->request->all());
        return $this->success(null,'更新成功');
    }
    public function destroy(int $id): JsonResponse {
        if(!$this->dao->find($id)) return $this->notFound();
        $this->dao->delete($id);
        return $this->success(null,'删除成功');
    }
    public function toggleStatus(int $id): JsonResponse {
        $a = $this->dao->find($id); if(!$a) return $this->notFound();
        $s = $this->request->int('status', $a['status']?0:1);
        $this->dao->update($id, ['status'=>$s]);
        return $this->success(['status'=>$s]);
    }
    /** 进行中的秒杀 */
    public function active(): JsonResponse {
        return $this->success($this->dao->getActive());
    }
    /** 秒杀下单 */
    public function place(): JsonResponse {
        $aid = $this->request->int('activity_id');
        $mid = $this->request->int('member_id');
        $qty = $this->request->int('quantity', 1);
        if(!$aid||!$mid) return $this->fail('参数不全');
        [$ok,$msg,$data] = array_pad($this->dao->place($aid,$mid,$qty),3,null);
        return $ok ? $this->success($data,$msg) : $this->fail($msg);
    }
}
