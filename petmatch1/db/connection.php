<?php
require_once __DIR__ . '/../config.php';

class Database {
    private $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->connection;
    }

    public function close() {
        $this->connection->close();
    }
}

// Función helper para obtener la conexión
function getDbConnection() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}
?>