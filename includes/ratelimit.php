<?php
class RateLimit {
    private $db;
    private $tableName = 'rate_limits';
    private $window = 300; // 5 dakika
    private $maxAttempts = 100; // 5 dakika içinde maksimum istek sayısı

    public function __construct($db = null) {
        $this->db = $db;
        $this->createTable();
    }

    private function createTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                endpoint VARCHAR(255) NOT NULL,
                attempts INT DEFAULT 1,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                blocked_until TIMESTAMP NULL,
                INDEX idx_ip_endpoint (ip_address, endpoint)
            )";
            $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Rate limit tablo oluşturma hatası: " . $e->getMessage());
        }
    }

    public function check($endpoint, $ip = null) {
        if (!$ip) {
            $ip = $this->getClientIP();
        }

        try {
            // Eski kayıtları temizle
            $this->cleanup();

            // Mevcut kayıtları kontrol et
            $record = $this->db->query(
                "SELECT * FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = ?",
                [$ip, $endpoint]
            )->fetch();

            if (!$record) {
                // Yeni kayıt oluştur
                $this->db->query(
                    "INSERT INTO {$this->tableName} (ip_address, endpoint) 
                    VALUES (?, ?)",
                    [$ip, $endpoint]
                );
                return true;
            }

            // Bloke kontrolü
            if ($record['blocked_until'] && strtotime($record['blocked_until']) > time()) {
                return false;
            }

            // Son deneme zamanı kontrolü
            $timeDiff = time() - strtotime($record['last_attempt']);
            if ($timeDiff > $this->window) {
                // Süre geçmiş, sayacı sıfırla
                $this->db->query(
                    "UPDATE {$this->tableName} 
                    SET attempts = 1, last_attempt = CURRENT_TIMESTAMP 
                    WHERE id = ?",
                    [$record['id']]
                );
                return true;
            }

            // Deneme sayısı kontrolü
            if ($record['attempts'] >= $this->maxAttempts) {
                // Limiti aştı, bloke et
                $blockedUntil = date('Y-m-d H:i:s', time() + $this->window);
                $this->db->query(
                    "UPDATE {$this->tableName} 
                    SET blocked_until = ? 
                    WHERE id = ?",
                    [$blockedUntil, $record['id']]
                );
                return false;
            }

            // Deneme sayısını artır
            $this->db->query(
                "UPDATE {$this->tableName} 
                SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP 
                WHERE id = ?",
                [$record['id']]
            );

            return true;

        } catch (Exception $e) {
            error_log("Rate limit kontrolü hatası: " . $e->getMessage());
            return true; // Hata durumunda erişime izin ver
        }
    }

    private function cleanup() {
        try {
            // Eski kayıtları temizle
            $this->db->query(
                "DELETE FROM {$this->tableName} 
                WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$this->window * 2]
            );
        } catch (Exception $e) {
            error_log("Rate limit temizleme hatası: " . $e->getMessage());
        }
    }

    private function getClientIP() {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public function setWindow($seconds) {
        $this->window = $seconds;
    }

    public function setMaxAttempts($attempts) {
        $this->maxAttempts = $attempts;
    }

    public function getRemainingAttempts($endpoint, $ip = null) {
        if (!$ip) {
            $ip = $this->getClientIP();
        }

        try {
            $record = $this->db->query(
                "SELECT attempts FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = ?",
                [$ip, $endpoint]
            )->fetch();

            return $record ? max(0, $this->maxAttempts - $record['attempts']) : $this->maxAttempts;
        } catch (Exception $e) {
            error_log("Kalan deneme sayısı kontrolü hatası: " . $e->getMessage());
            return $this->maxAttempts;
        }
    }

    public function reset($endpoint, $ip = null) {
        if (!$ip) {
            $ip = $this->getClientIP();
        }

        try {
            $this->db->query(
                "DELETE FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = ?",
                [$ip, $endpoint]
            );
            return true;
        } catch (Exception $e) {
            error_log("Rate limit sıfırlama hatası: " . $e->getMessage());
            return false;
        }
    }
}