<?php
/**
 * 商品管理 API 控制器
 * 
 * 接口列表：
 *   GET    /adminapi/product          → 商品列表（分页+搜索）
 *   GET    /adminapi/product/:id      → 商品详情
 *   POST   /adminapi/product          → 新增商品
 *   PUT    /adminapi/product/:id      → 更新商品
 *   DELETE /adminapi/product/:id      → 删除商品
 *   PUT    /adminapi/product/:id/status → 上下架
 */
class ProductController extends BaseController
{
    private ProductDao $dao;

    public function __construct()
    {
        parent::__construct();
        $this->dao = new ProductDao();
    }

    /**
     * 商品列表
     * GET /adminapi/product
     */
    public function index(): JsonResponse
    {
        [$page, $pageSize] = $this->getPage();

        $params = [
            'keyword' => $this->request->string('keyword'),
            'category_id' => $this->request->get('category_id'),
            'status' => $this->request->get('status'),
            'is_hot' => $this->request->get('is_hot'),
            'is_new' => $this->request->get('is_new'),
            'is_recommend' => $this->request->get('is_recommend'),
            'order' => $this->request->string('order'),
        ];

        $result = $this->dao->getList(array_filter($params, fn($v) => $v !== '' && $v !== null), $page, $pageSize);

        return $this->success([
            'list' => $result['list'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    /**
     * 商品详情
     * GET /adminapi/product/:id
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->dao->getDetail($id);
        if (!$product) {
            return $this->notFound('商品不存在');
        }

        // 解析 images JSON
        if (!empty($product['images'])) {
            $product['images'] = json_decode($product['images'], true) ?: [];
        }

        return $this->success($product);
    }

    /**
     * 新增商品
     * POST /adminapi/product
     */
    public function store(): JsonResponse
    {
        $error = $this->validate([
            'name' => 'required',
            'price' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
        ]);
        if ($error) return $this->fail($error);

        $data = $this->dao->filterData($this->request->all());
        
        // 处理 images（JSON 字符串 → 直接存储）
        if (isset($data['images']) && is_array($data['images'])) {
            $data['images'] = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
        }

        // 处理 services
        if (isset($data['services']) && is_array($data['services'])) {
            $data['services'] = json_encode($data['services'], JSON_UNESCAPED_UNICODE);
        }

        // 设置默认值
        $data['sales'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');

        try {
            $id = $this->dao->create($data);
            return $this->success(['id' => $id], '商品创建成功');
        } catch (\Exception $e) {
            return $this->fail('创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新商品
     * PUT /adminapi/product/:id
     */
    public function update(int $id): JsonResponse
    {
        $product = $this->dao->find($id);
        if (!$product) {
            return $this->notFound('商品不存在');
        }

        $data = $this->dao->filterData($this->request->all());

        // 处理 images
        if (isset($data['images']) && is_array($data['images'])) {
            $data['images'] = json_encode($data['images'], JSON_UNESCAPED_UNICODE);
        }

        // 处理 services
        if (isset($data['services']) && is_array($data['services'])) {
            $data['services'] = json_encode($data['services'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $this->dao->update($id, $data);
            return $this->success(null, '商品更新成功');
        } catch (\Exception $e) {
            return $this->fail('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除商品
     * DELETE /adminapi/product/:id
     */
    public function destroy(int $id): JsonResponse
    {
        $product = $this->dao->find($id);
        if (!$product) {
            return $this->notFound('商品不存在');
        }

        try {
            $this->dao->delete($id);
            return $this->success(null, '商品已删除');
        } catch (\Exception $e) {
            return $this->fail('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 上下架
     * PUT /adminapi/product/:id/status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $product = $this->dao->find($id);
        if (!$product) {
            return $this->notFound('商品不存在');
        }

        $status = $this->request->int('status', $product['status'] ? 0 : 1);
        $this->dao->update($id, ['status' => $status]);

        return $this->success(
            ['status' => $status],
            $status ? '已上架' : '已下架'
        );
    }
}
