<?php
/**
 * 订单管理 API
 */
class OrderController extends BaseController
{
    private OrderDao $dao;
    public function __construct() { parent::__construct(); $this->dao = new OrderDao(); }

    /** 订单列表 */
    public function index(): JsonResponse
    {
        [$page, $pageSize] = $this->getPage();
        $params = [
            'keyword' => $this->request->string('keyword'),
            'status' => $this->request->string('status'),
            'start_date' => $this->request->string('start_date'),
            'end_date' => $this->request->string('end_date'),
        ];
        $result = $this->dao->getList(array_filter($params, fn($v) => $v !== '' && $v !== null), $page, $pageSize);
        return $this->success($result);
    }

    /** 订单详情 */
    public function show(int $id): JsonResponse
    {
        $order = $this->dao->getDetail($id);
        if (!$order) return $this->notFound();
        return $this->success($order);
    }

    /** 支付 */
    public function pay(int $id): JsonResponse
    {
        [$ok, $msg] = $this->dao->pay($id);
        return $ok ? $this->success(null, $msg) : $this->fail($msg);
    }

    /** 发货 */
    public function ship(int $id): JsonResponse
    {
        [$ok, $msg] = $this->dao->ship($id);
        return $ok ? $this->success(null, $msg) : $this->fail($msg);
    }

    /** 收货 */
    public function receive(int $id): JsonResponse
    {
        [$ok, $msg] = $this->dao->receive($id);
        return $ok ? $this->success(null, $msg) : $this->fail($msg);
    }

    /** 完成 */
    public function complete(int $id): JsonResponse
    {
        [$ok, $msg] = $this->dao->complete($id);
        return $ok ? $this->success(null, $msg) : $this->fail($msg);
    }

    /** 取消 */
    public function cancel(int $id): JsonResponse
    {
        $remark = $this->request->string('remark');
        [$ok, $msg] = $this->dao->cancel($id, $remark);
        return $ok ? $this->success(null, $msg) : $this->fail($msg);
    }

    /** 自动取消超时订单 */
    public function autoCancel(): JsonResponse
    {
        $minutes = $this->request->int('minutes', 30);
        $count = $this->dao->autoCancelPending($minutes);
        return $this->success(['cancelled' => $count], "已取消 {$count} 笔超时订单");
    }
}
