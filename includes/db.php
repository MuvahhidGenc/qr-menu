<?php
class Database {
    private $db;
    
    public function __construct() {
        try {
            $this->db = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $e) {
            die("Bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    // Son eklenen ID'yi almak için metod ekledik
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
}