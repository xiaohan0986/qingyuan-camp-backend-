<?php
/**
 * 基础 DAO（数据访问对象）
 * 
 * 在 Model 之上增加：
 *   - 搜索条件构建
 *   - 字段白名单过滤
 *   - 常用业务查询封装
 */
abstract class BaseDao
{
    /** @var BaseModel */
    protected BaseModel $model;

    /**
     * 子类必须指定对应的 Model 实例
     */
    abstract protected function setModel(): void;

    public function __construct()
    {
        if (!isset($this->model)) {
            $this->setModel();
        }
    }

    /**
     * 获取 Model
     */
    public function getModel(): BaseModel
    {
        return $this->model;
    }

    // ========== 字段过滤 ==========

    /**
     * 获取允许填充的字段（子类覆盖定义）
     */
    protected function getFillable(): array
    {
        return [];
    }

    /**
     * 过滤数据，只保留允许的字段
     */
    protected function filterData(array $data): array
    {
        $fillable = $this->getFillable();
        if (empty($fillable)) return $data;
        return array_intersect_key($data, array_flip($fillable));
    }

    // ========== 搜索构建 ==========

    /**
     * 标准搜索：支持关键词 + 条件数组
     * 
     * @param array $where 条件数组，如 ['keyword' => '手机', 'status' => 1, 'category_id' => 5]
     * @param array $searchFields 关键词搜索的字段，如 ['name', 'description']
     * @return array [whereClause, params]
     */
    protected function buildSearch(array $where, array $searchFields = []): array
    {
        $clauses = ['1=1'];
        $params = [];

        foreach ($where as $key => $value) {
            if ($value === null || $value === '') continue;

            switch ($key) {
                case 'keyword':
                    if (!empty($value) && !empty($searchFields)) {
                        $keywordClauses = [];
                        foreach ($searchFields as $i => $field) {
                            $paramKey = "_kw_{$i}";
                            $keywordClauses[] = "`{$field}` LIKE :{$paramKey}";
                            $params[$paramKey] = "%{$value}%";
                        }
                        $clauses[] = '(' . implode(' OR ', $keywordClauses) . ')';
                    }
                    break;

                case 'start_time':
                case 'start_date':
                    $clauses[] = "`created_at` >= :_start";
                    $params['_start'] = is_int($value) ? date('Y-m-d H:i:s', $value) : $value . ' 00:00:00';
                    break;

                case 'end_time':
                case 'end_date':
                    $clauses[] = "`created_at` <= :_end";
                    $params['_end'] = is_int($value) ? date('Y-m-d H:i:s', $value) : $value . ' 23:59:59';
                    break;

                case 'date':
                    $clauses[] = "DATE(`created_at`) = :_date";
                    $params['_date'] = $value;
                    break;

                default:
                    // 驼峰转下划线：categoryId → category_id
                    $fieldName = strtolower(preg_replace('/([A-Z])/', '_$1', $key));
                    $paramKey = "_{$fieldName}";
                    
                    if (is_array($value)) {
                        // IN 查询
                        $placeholders = [];
                        foreach ($value as $vi => $vv) {
                            $pk = "_{$fieldName}_{$vi}";
                            $placeholders[] = ":{$pk}";
                            $params[$pk] = $vv;
                        }
                        $clauses[] = "`{$fieldName}` IN (" . implode(',', $placeholders) . ')';
                    } elseif (strpos($value, '%') !== false) {
                        // LIKE 查询
                        $clauses[] = "`{$fieldName}` LIKE :{$paramKey}";
                        $params[$paramKey] = $value;
                    } else {
                        // 精确匹配
                        $clauses[] = "`{$fieldName}` = :{$paramKey}";
                        $params[$paramKey] = $value;
                    }
                    break;
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    // ========== CRUD（增加 fillable 过滤） ==========

    public function find(int|string $id): ?array
    {
        return $this->model->find($id);
    }

    public function findWhere(string $where, array $params = []): ?array
    {
        return $this->model->findWhere($where, $params);
    }

    public function select(string $where = '1=1', array $params = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        return $this->model->select($where, $params, $orderBy, $limit, $offset);
    }

    public function search(array $where, array $searchFields = [], string $orderBy = '', int $page = 1, int $pageSize = 20): array
    {
        [$whereClause, $params] = $this->buildSearch($where, $searchFields);
        return $this->model->paginate($whereClause, $params, $orderBy, $page, $pageSize);
    }

    public function count(array $where = [], array $searchFields = []): int
    {
        if (empty($where)) return $this->model->count();
        [$whereClause, $params] = $this->buildSearch($where, $searchFields);
        return $this->model->count($whereClause, $params);
    }

    public function create(array $data): int|string
    {
        return $this->model->insert($this->filterData($data));
    }

    public function update(int|string $id, array $data): int
    {
        return $this->model->update($id, $this->filterData($data));
    }

    public function updateWhere(string $where, array $whereParams, array $data): int
    {
        return $this->model->updateWhere($where, $whereParams, $this->filterData($data));
    }

    public function delete(int|string $id): int
    {
        return $this->model->delete($id);
    }

    public function exists(string $where, array $params = []): bool
    {
        return $this->model->exists($where, $params);
    }

    public function value(string $field, int|string $id): mixed
    {
        return $this->model->value($field, $id);
    }

    // 事务代理
    public function beginTransaction(): void { $this->model->beginTransaction(); }
    public function commit(): void { $this->model->commit(); }
    public function rollback(): void { $this->model->rollback(); }
    public function lastInsertId(): string { return $this->model->lastInsertId(); }
}
