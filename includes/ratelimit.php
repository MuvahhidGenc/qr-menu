<?php
class RateLimit {
    private $db;
    private $tableName = 'rate_limits';
    private $window = 300; // 5 dakika
    private $maxAttempts = 100; // 5 dakika içinde maksimum istek sayısı

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }
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
            $ip = getClientIP();
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

    // getClientIP() fonksiyonu functions.php'de global olarak tanımlı

    public function setWindow($seconds) {
        $this->window = $seconds;
    }

    public function setMaxAttempts($attempts) {
        $this->maxAttempts = $attempts;
    }

    public function getRemainingAttempts($endpoint, $ip = null) {
        if (!$ip) {
            $ip = getClientIP();
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
            $ip = getClientIP();
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

    // Login için özel rate limiting metodları
    public function isLimited($ip) {
        try {
            $this->cleanup();
            
            $record = $this->db->query(
                "SELECT * FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = 'login'",
                [$ip]
            )->fetch();

            if (!$record) {
                return false;
            }

            // 5 başarısız denemeden sonra bloklanmış mı kontrol et
            if ($record['attempts'] >= 5) {
                if ($record['blocked_until']) {
                    return strtotime($record['blocked_until']) > time();
                } else {
                    // Eğer blocked_until NULL ise, şimdi blokla
                    $blockedUntil = date('Y-m-d H:i:s', time() + 600); // 10 dakika
                    $this->db->query(
                        "UPDATE {$this->tableName} 
                        SET blocked_until = ? 
                        WHERE ip_address = ? AND endpoint = 'login'",
                        [$blockedUntil, $ip]
                    );
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            error_log("Rate limit kontrolü hatası: " . $e->getMessage());
            return false;
        }
    }

    public function recordAttempt($ip, $success = false) {
        try {
            $record = $this->db->query(
                "SELECT * FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = 'login'",
                [$ip]
            )->fetch();

            if ($success) {
                // Başarılı giriş - kayıtları temizle
                if ($record) {
                    $this->db->query(
                        "DELETE FROM {$this->tableName} 
                        WHERE ip_address = ? AND endpoint = 'login'",
                        [$ip]
                    );
                }
            } else {
                // Başarısız giriş
                if ($record) {
                    $newAttempts = $record['attempts'] + 1;
                    $blockedUntil = null;
                    
                    // 5. denemeden sonra 10 dakika blokla
                    if ($newAttempts >= 5) {
                        $blockedUntil = date('Y-m-d H:i:s', time() + 600); // 10 dakika
                    }
                    
                    $this->db->query(
                        "UPDATE {$this->tableName} 
                        SET attempts = ?, last_attempt = CURRENT_TIMESTAMP, blocked_until = ?
                        WHERE ip_address = ? AND endpoint = 'login'",
                        [$newAttempts, $blockedUntil, $ip]
                    );
                } else {
                    // İlk deneme
                    $this->db->query(
                        "INSERT INTO {$this->tableName} (ip_address, endpoint, attempts) 
                        VALUES (?, 'login', 1)",
                        [$ip]
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Rate limit kayıt hatası: " . $e->getMessage());
        }
    }

    public function getRemainingTime($ip) {
        try {
            $record = $this->db->query(
                "SELECT blocked_until FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = 'login'",
                [$ip]
            )->fetch();

            if ($record && $record['blocked_until']) {
                $remainingTime = strtotime($record['blocked_until']) - time();
                return max(0, $remainingTime);
            }

            return 0;
        } catch (Exception $e) {
            error_log("Kalan süre hesaplama hatası: " . $e->getMessage());
            return 0;
        }
    }

    public function getAttemptsLeft($ip) {
        try {
            $record = $this->db->query(
                "SELECT attempts FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = 'login'",
                [$ip]
            )->fetch();

            if ($record) {
                return max(0, 5 - $record['attempts']);
            }

            return 5;
        } catch (Exception $e) {
            error_log("Kalan deneme hesaplama hatası: " . $e->getMessage());
            return 5;
        }
    }

    public function getAttempts($ip) {
        try {
            $record = $this->db->query(
                "SELECT attempts FROM {$this->tableName} 
                WHERE ip_address = ? AND endpoint = 'login'",
                [$ip]
            )->fetch();

            return $record ? (int)$record['attempts'] : 0;
        } catch (Exception $e) {
            error_log("Deneme sayısı alma hatası: " . $e->getMessage());
            return 0;
        }
    }
}