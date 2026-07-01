<?php
/**
 * 基础模型
 * 封装 Database 类，提供优雅的 CRUD 接口
 * 
 * 用法：
 *   class Product extends BaseModel {
 *       protected string $table = 'products';
 *       protected string $pk = 'id';
 *   }
 */
abstract class BaseModel
{
    /** @var string 表名（子类必须定义） */
    protected string $table = '';
    
    /** @var string 主键 */
    protected string $pk = 'id';
    
    /** @var bool 是否自动维护时间戳 */
    protected bool $timestamps = true;
    
    /** @var string 创建时间字段 */
    protected string $createdAt = 'created_at';
    
    /** @var string 更新时间字段 */
    protected string $updatedAt = 'updated_at';
    
    /** @var Database|null */
    protected ?Database $db = null;

    public function __construct()
    {
        // 确保 Database 类已加载
        if (!class_exists('Database', false)) {
            $dbFile = INCLUDES_PATH . 'Database.php';
            if (file_exists($dbFile)) require_once $dbFile;
        }
        $this->db = Database::getInstance();
    }

    /**
     * 获取表名
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 获取主键
     */
    public function getPk(): string
    {
        return $this->pk;
    }

    /**
     * 获取 Database 实例
     */
    public function db(): Database
    {
        return $this->db;
    }

    // ========== CRUD 操作 ==========

    /**
     * 查询单条
     */
    public function find(int|string $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->pk}` = :id",
            ['id' => $id]
        ) ?: null;
    }

    /**
     * 查询单条（自定义条件）
     */
    public function findWhere(string $where, array $params = []): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE {$where} LIMIT 1",
            $params
        ) ?: null;
    }

    /**
     * 查询多条
     */
    public function select(string $where = '1=1', array $params = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$where}";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        if ($limit > 0) $sql .= " LIMIT {$limit}";
        if ($offset > 0) $sql .= " OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 分页查询
     */
    public function paginate(string $where = '1=1', array $params = [], string $orderBy = '', int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;
        
        // 总数
        $countResult = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$where}",
            $params
        );
        $total = (int)($countResult['total'] ?? 0);
        
        // 数据
        $list = $this->select($where, $params, $orderBy, $pageSize, $offset);
        
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => (int)ceil($total / $pageSize),
        ];
    }

    /**
     * 计数
     */
    public function count(string $where = '1=1', array $params = []): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `{$this->table}` WHERE {$where}",
            $params
        );
        return (int)($result['total'] ?? 0);
    }

    /**
     * 新增
     */
    public function insert(array $data): int|string
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!isset($data[$this->createdAt])) $data[$this->createdAt] = $now;
            if (!isset($data[$this->updatedAt])) $data[$this->updatedAt] = $now;
        }
        return $this->db->insert($this->table, $data);
    }

    /**
     * 批量新增
     */
    public function insertAll(array $records): int
    {
        $count = 0;
        foreach ($records as $data) {
            $this->insert($data);
            $count++;
        }
        return $count;
    }

    /**
     * 更新
     */
    public function update(int|string $id, array $data): int
    {
        if ($this->timestamps) {
            if (!isset($data[$this->updatedAt])) $data[$this->updatedAt] = date('Y-m-d H:i:s');
        }
        return $this->db->update(
            $this->table,
            $data,
            "`{$this->pk}` = :_pk",
            ['_pk' => $id]
        );
    }

    /**
     * 条件更新
     */
    public function updateWhere(string $where, array $whereParams, array $data): int
    {
        if ($this->timestamps) {
            if (!isset($data[$this->updatedAt])) $data[$this->updatedAt] = date('Y-m-d H:i:s');
        }
        return $this->db->update($this->table, $data, $where, $whereParams);
    }

    /**
     * 删除
     */
    public function delete(int|string $id): int
    {
        return $this->db->delete(
            $this->table,
            "`{$this->pk}` = ?",
            [$id]
        );
    }

    /**
     * 条件删除
     */
    public function deleteWhere(string $where, array $params = []): int
    {
        return $this->db->delete($this->table, $where, $params);
    }

    /**
     * 自增字段
     */
    public function increment(int|string $id, string $field, int $amount = 1): bool
    {
        $this->db->query(
            "UPDATE `{$this->table}` SET `{$field}` = `{$field}` + :amount WHERE `{$this->pk}` = :id",
            ['amount' => $amount, 'id' => $id]
        );
        return true;
    }

    /**
     * 自减字段
     */
    public function decrement(int|string $id, string $field, int $amount = 1): bool
    {
        return $this->increment($id, $field, -$amount);
    }

    /**
     * 执行原生 SQL（返回结果集）
     */
    public function query(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 执行原生 SQL（不返回结果）
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->db->query($sql, $params);
    }

    /**
     * 获取某个字段值
     */
    public function value(string $field, int|string $id): mixed
    {
        $result = $this->db->fetchOne(
            "SELECT `{$field}` FROM `{$this->table}` WHERE `{$this->pk}` = :id",
            ['id' => $id]
        );
        return $result[$field] ?? null;
    }

    /**
     * 判断是否存在
     */
    public function exists(string $where, array $params = []): bool
    {
        return $this->count($where, $params) > 0;
    }

    /**
     * 获取最后插入 ID
     */
    public function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }
}
