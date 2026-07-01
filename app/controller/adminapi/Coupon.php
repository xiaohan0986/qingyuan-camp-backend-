<?php
/**
 * 优惠券管理 API
 */
class CouponController extends BaseController
{
    private CouponDao $dao;
    private CouponReceiveLogDao $logDao;

    public function __construct()
    {
        parent::__construct();
        $this->dao = new CouponDao();
        $this->logDao = new CouponReceiveLogDao();
    }

    /** 优惠券列表 */
    public function index(): JsonResponse
    {
        [$page, $pageSize] = $this->getPage();
        $params = [
            'keyword' => $this->request->string('keyword'),
            'status' => $this->request->get('status'),
            'type' => $this->request->string('type'),
        ];
        $result = $this->dao->getList(array_filter($params, fn($v) => $v !== '' && $v !== null), $page, $pageSize);
        return $this->success($result);
    }

    /** 优惠券详情 */
    public function show(int $id): JsonResponse
    {
        $coupon = $this->dao->find($id);
        if (!$coupon) return $this->notFound();
        return $this->success($coupon);
    }

    /** 新增优惠券 */
    public function store(): JsonResponse
    {
        $error = $this->validate(['name' => 'required', 'value' => 'required|numeric|min:0']);
        if ($error) return $this->fail($error);

        $data = $this->dao->filterData($this->request->all());
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['received'] = 0;
        $data['used'] = 0;

        if (isset($data['use_range']) && is_array($data['use_range'])) {
            $data['use_range'] = json_encode($data['use_range'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $id = $this->dao->create($data);
            return $this->success(['id' => $id], '优惠券创建成功');
        } catch (\Exception $e) {
            return $this->fail('创建失败: ' . $e->getMessage());
        }
    }

    /** 更新优惠券 */
    public function update(int $id): JsonResponse
    {
        $coupon = $this->dao->find($id);
        if (!$coupon) return $this->notFound();

        $data = $this->dao->filterData($this->request->all());
        if (isset($data['use_range']) && is_array($data['use_range'])) {
            $data['use_range'] = json_encode($data['use_range'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $this->dao->update($id, $data);
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->fail('更新失败: ' . $e->getMessage());
        }
    }

    /** 删除优惠券 */
    public function destroy(int $id): JsonResponse
    {
        if (!$this->dao->find($id)) return $this->notFound();
        $this->dao->delete($id);
        return $this->success(null, '删除成功');
    }

    /** 上下架 */
    public function toggleStatus(int $id): JsonResponse
    {
        $coupon = $this->dao->find($id);
        if (!$coupon) return $this->notFound();
        $status = $this->request->int('status', $coupon['status'] ? 0 : 1);
        $this->dao->update($id, ['status' => $status]);
        return $this->success(['status' => $status]);
    }

    /** 领取记录 */
    public function receiveLogs(int $id): JsonResponse
    {
        if (!$this->dao->find($id)) return $this->notFound();
        [$page, $pageSize] = $this->getPage();
        $result = $this->logDao->getReceiveLogs($id, $page, $pageSize);
        return $this->success($result);
    }

    /** 用户领券 */
    public function receive(): JsonResponse
    {
        $couponId = $this->request->int('coupon_id');
        $memberId = $this->request->int('member_id');
        if (!$couponId || !$memberId) return $this->fail('参数不全');

        $result = $this->logDao->receive($couponId, $memberId);
        if (!$result['success']) return $this->fail($result['msg']);
        return $this->success(null, $result['msg']);
    }

    /** 获取用户可用优惠券 */
    public function userCoupons(): JsonResponse
    {
        $memberId = $this->request->int('member_id');
        $orderAmount = $this->request->float('order_amount', 0);
        $productIds = $this->request->array('product_ids', []);

        if (!$memberId) return $this->fail('member_id 必填');

        $coupons = $this->logDao->getUserCoupons($memberId, $orderAmount, $productIds);
        return $this->success($coupons);
    }
}
