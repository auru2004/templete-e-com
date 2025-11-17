<?php
// DATABASE CONNECTION CLASS - Singleton Pattern

class Database {
    private static $instance = null;
    private $connection;
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $user = DB_USER;
    private $pass = DB_PASS;

    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

        if ($this->connection->connect_error) {
            die('Database Connection Error: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function connect() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        if (empty($params)) {
            // Simple query without parameters
            return $this->connection->query($sql);
        } else {
            // Prepared statement with parameters
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Build type string for bind_param
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            // Bind parameters
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result();
        }
    }

    public function fetch($result) {
        return $result->fetch_assoc();
    }

    public function fetchAll($result) {
        $data = [];
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    public function escape($data) {
        return $this->connection->real_escape_string($data);
    }
}

$db = Database::getInstance();
?>
