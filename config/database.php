<?php
class Database {
    // private $host = "localhost";
    // private $db_name = "u112926345_bookmyproduct";
    // private $username = "u112926345_bookmyproduct";
    // private $password = "6b=K@JZK";


    private $host = "localhost";
    private $db_name = "glas_online";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
