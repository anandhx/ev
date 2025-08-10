<?php
/**
 * Database Configuration and Connection
 * EV Mobile Power & Service Station
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'ev_mobile_station';
    private $username = 'root';
    private $password = '';
    private $conn;
    private static $instance = null;

    public function __construct() {
        // You can override these values with environment variables
        if (getenv('DB_HOST')) $this->host = getenv('DB_HOST');
        if (getenv('DB_NAME')) $this->db_name = getenv('DB_NAME');
        if (getenv('DB_USERNAME')) $this->username = getenv('DB_USERNAME');
        if (getenv('DB_PASSWORD')) $this->password = getenv('DB_PASSWORD');
    }

    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_EMULATE_PREPARES => false
                    )
                );
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("Database connection failed: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $conn->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTableCount($table_name) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table_name");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    public function executeQuery($sql, $params = []) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            error_log("Query execution error: " . $e->getMessage());
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }

    public function getLastInsertId() {
        try {
            $conn = $this->getConnection();
            return $conn->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    public function beginTransaction() {
        try {
            $conn = $this->getConnection();
            return $conn->beginTransaction();
        } catch (Exception $e) {
            return false;
        }
    }

    public function commit() {
        try {
            $conn = $this->getConnection();
            return $conn->commit();
        } catch (Exception $e) {
            return false;
        }
    }

    public function rollback() {
        try {
            $conn = $this->getConnection();
            return $conn->rollback();
        } catch (Exception $e) {
            return false;
        }
    }
}

// Create global database instance
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
} catch (Exception $e) {
    // If database connection fails, we'll handle it gracefully in the application
    $db = null;
    error_log("Global database connection failed: " . $e->getMessage());
}
?> 