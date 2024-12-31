<?php
class Database {
    private $host = "localhost";
    private $dbname = "fonmunsimulator";
    private $username = "root";
    private $password = "mylocalserver";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
