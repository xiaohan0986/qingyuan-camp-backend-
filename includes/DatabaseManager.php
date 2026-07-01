<?php
class DatabaseManager {
    private static $instance = null;
    private $pdo = null;
    private $config = [];
    
    private function __construct() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
        } else {
            $env = [];
        }
        $this->config = [
            'host' => $env['DB_HOST'] ?? 'localhost',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_NAME'] ?? 'qianwutong',
            'username' => $env['DB_USER'] ?? 'root',
            'password' => $env['DB_PASS'] ?? 'root',
            'charset' => $env['DB_CHARSET'] ?? 'utf8mb4'
        ];
        $this->connect();
    }
    
    private function connect() {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['host'], $this->config['port'],
            $this->config['database'], $this->config['charset']);
        $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    public function getPDO() { return $this->pdo; }
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
}

function getDB() { return DatabaseManager::getInstance()->getPDO(); }
function dbQuery($sql, $params = []) { return DatabaseManager::getInstance()->query($sql, $params); }
function dbFetchAll($sql, $params = []) { return DatabaseManager::getInstance()->fetchAll($sql, $params); }
function dbFetch($sql, $params = []) { return DatabaseManager::getInstance()->fetch($sql, $params); }
function dbInsert($sql, $params = []) {
    DatabaseManager::getInstance()->query($sql, $params);
    return DatabaseManager::getInstance()->getPDO()->lastInsertId();
}
function dbExecute($sql, $params = []) {
    return DatabaseManager::getInstance()->query($sql, $params)->rowCount();
}
