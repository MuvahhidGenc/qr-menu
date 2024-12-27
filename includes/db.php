<?php
class Database {
    private $db;
    
    public function __construct() {
        try {
            $this->db = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+03:00'"
                ]
            );
        } catch(PDOException $e) {
            die("Bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        if(!$result) {
            error_log('SQL Error: ' . print_r($stmt->errorInfo(), true));
        }
        return $stmt;
    }
    // Son eklenen ID'yi almak için metod ekledik
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
    // includes/config.php içinde Database sınıfına ekleyin:
    
}