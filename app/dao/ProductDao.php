<?php
/**
 * 商品 DAO
 */
class ProductDao extends BaseDao
{
    protected function setModel(): void
    {
        $this->model = new Product();
    }

    /**
     * 可填充字段
     */
    protected function getFillable(): array
    {
        return [
            'name', 'category_id', 'product_code',
            'price', 'member_price', 'cost_price', 'original_price',
            'stock', 'weight',
            'images', 'description', 'content',
            'sort', 'is_hot', 'is_new', 'is_recommend',
            'status', 'type', 'freight_template_id',
            'spec_type', 'stock_method', 'limit_buy',
            'commission_type', 'video_url', 'video_cover',
            'services', 'selling_points', 'initial_sales',
            'member_discount', 'points_gift', 'points_deduct', 'points_deduct_type',
        ];
    }

    /**
     * 搜索字段（支持关键词搜索）
     */
    public function getSearchFields(): array
    {
        return ['name', 'description', 'product_code'];
    }

    /**
     * 查询商品列表（含分类名、支持搜索和筛选）
     */
    public function getList(array $params, int $page, int $pageSize): array
    {
        // 构建查询
        $where = [];
        $bindings = [];

        // 关键词搜索
        if (!empty($params['keyword'])) {
            $where[] = '(p.name LIKE :keyword OR p.description LIKE :keyword2 OR p.product_code LIKE :keyword3)';
            $bindings['keyword'] = "%{$params['keyword']}%";
            $bindings['keyword2'] = "%{$params['keyword']}%";
            $bindings['keyword3'] = "%{$params['keyword']}%";
        }

        // 分类筛选
        if (!empty($params['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $bindings['category_id'] = $params['category_id'];
        }

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = 'p.status = :status';
            $bindings['status'] = $params['status'];
        }

        // 推荐/新品/热销
        foreach (['is_hot', 'is_new', 'is_recommend'] as $flag) {
            if (isset($params[$flag]) && $params[$flag] !== '') {
                $where[] = "p.{$flag} = :{$flag}";
                $bindings[$flag] = $params[$flag];
            }
        }

        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

        // 排序
        $orderBy = 'p.sort DESC, p.created_at DESC';
        if (!empty($params['order'])) {
            $orderMap = [
                'price_asc' => 'p.price ASC',
                'price_desc' => 'p.price DESC',
                'sales_desc' => 'p.sales DESC',
                'newest' => 'p.created_at DESC',
            ];
            $orderBy = $orderMap[$params['order']] ?? $orderBy;
        }

        // 分页
        $offset = ($page - 1) * $pageSize;

        // 总数
        $countResult = $this->model->db()->fetchOne(
            "SELECT COUNT(*) as total FROM products p WHERE {$whereClause}",
            $bindings
        );
        $total = (int)($countResult['total'] ?? 0);

        // 数据（关联分类名）
        $list = $this->model->db()->fetchAll(
            "SELECT p.*, pc.name as category_name 
             FROM products p 
             LEFT JOIN product_categories pc ON p.category_id = pc.id 
             WHERE {$whereClause} 
             ORDER BY {$orderBy} 
             LIMIT {$pageSize} OFFSET {$offset}",
            $bindings
        );

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * 商品详情（含分类）
     */
    public function getDetail(int $id): ?array
    {
        return $this->model->db()->fetchOne(
            "SELECT p.*, pc.name as category_name 
             FROM products p 
             LEFT JOIN product_categories pc ON p.category_id = pc.id 
             WHERE p.id = :id",
            ['id' => $id]
        ) ?: null;
    }
}
