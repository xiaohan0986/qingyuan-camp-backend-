<?php
/**
 * 数据库连接类（使用 MySQLi）
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            if ($this->connection->connect_error) {
                throw new Exception("数据库连接失败：" . $this->connection->connect_error);
            }
            
            $this->connection->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            die("数据库连接失败：" . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        // 支持命名参数（:name）转换为位置参数（?）
        if (!empty($params) && $this->hasNamedParams($sql)) {
            return $this->executeNamedParams($sql, $params);
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 准备失败：" . $this->connection->error);
        }
        
        if (!empty($params)) {
            $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    private function hasNamedParams($sql) {
        return preg_match('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql);
    }
    
    private function executeNamedParams($sql, $params) {
        // 将命名参数转换为位置参数
        $placeholderMap = [];
        $typeMap = [];
        $values = [];
        $newSql = $sql;
        
        // 找出所有命名参数
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $matches);
        $paramNames = array_unique($matches[1]);
        
        // 替换为位置参数
        foreach ($paramNames as $name) {
            if (!isset($params[$name])) {
                throw new Exception("缺少参数：:$name");
            }
            $placeholder = ':' . $name;
            $newSql = str_replace($placeholder, '?', $newSql);
            
            $value = $params[$name];
            if (is_int($value)) {
                $typeMap[] = 'i';
            } elseif (is_float($value)) {
                $typeMap[] = 'd';
            } else {
                $typeMap[] = 's';
            }
            $values[] = $value;
        }
        
        $stmt = $this->connection->prepare($newSql);
        if (!$stmt) {
            throw new Exception("SQL 准备失败：" . $this->connection->error);
        }
        
        if (!empty($values)) {
            $stmt->bind_param(implode('', $typeMap), ...$values);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
    
    public function fetchOne($sql, $params = []) {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode('`,`', $keys);
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL 准备失败：" . $this->connection->error);
        }
        
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $insertId = $this->connection->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        $values = [];
        $types = '';
        
        foreach ($data as $key => $value) {
            $set[] = "`{$key}` = ?";
            $values[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        
        $setStr = implode(', ', $set);
        
        // 处理 WHERE 子句中的命名参数
        $whereSql = $where;
        if (!empty($whereParams) && $this->hasNamedParams($where)) {
            $whereResult = $this->convertNamedParams($where, $whereParams);
            $whereSql = $whereResult['sql'];
            $types .= $whereResult['types'];
            $values = array_merge($values, $whereResult['values']);
        } elseif (!empty($whereParams)) {
            // 位置参数
            foreach ($whereParams as $value) {
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
                $values[] = $value;
            }
        }
        
        $sql = "UPDATE `{$table}` SET {$setStr} WHERE {$whereSql}";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 准备失败：" . $this->connection->error);
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    private function convertNamedParams($sql, $params) {
        $typeMap = [];
        $values = [];
        $newSql = $sql;
        
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $matches);
        $paramNames = array_unique($matches[1]);
        
        foreach ($paramNames as $name) {
            if (!isset($params[$name])) {
                throw new Exception("缺少参数：:$name");
            }
            $placeholder = ':' . $name;
            $newSql = str_replace($placeholder, '?', $newSql);
            
            $value = $params[$name];
            if (is_int($value)) {
                $typeMap[] = 'i';
            } elseif (is_float($value)) {
                $typeMap[] = 'd';
            } else {
                $typeMap[] = 's';
            }
            $values[] = $value;
        }
        
        return [
            'sql' => $newSql,
            'types' => implode('', $typeMap),
            'values' => $values
        ];
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL 准备失败：" . $this->connection->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $values = [];
            foreach ($params as $value) {
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
                $values[] = $value;
            }
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
}
