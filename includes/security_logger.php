<?php
class SecurityLogger {
    private $logFile;
    private $db;
    
    public function __construct() {
        $this->logFile = dirname(__FILE__) . '/../logs/security.log';
        $this->db = new Database();
        $this->createLogTable();
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // .htaccess ile log dosyalarını koru
        $htaccessFile = $logDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Deny from all\nOptions -Indexes");
        }
    }
    
    private function createLogTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS security_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
                message TEXT NOT NULL,
                user_agent TEXT,
                referer VARCHAR(500),
                request_uri VARCHAR(500),
                request_method VARCHAR(10),
                additional_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_time (ip_address, created_at),
                INDEX idx_event_severity (event_type, severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $this->db->query($sql);
        } catch (Exception $e) {
            error_log("Security log tablo oluşturma hatası: " . $e->getMessage());
        }
    }
    
    public function logSecurityEvent($eventType, $message, $severity = 'MEDIUM', $additionalData = null) {
        $ip = getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // File log
        $this->writeToFile($eventType, $message, $severity, $ip, $userAgent);
        
        // Database log
        $this->writeToDatabase($ip, $eventType, $severity, $message, $userAgent, $referer, $requestUri, $requestMethod, $additionalData);
        
        // Critical events için immediate alert
        if ($severity === 'CRITICAL') {
            $this->handleCriticalAlert($eventType, $message, $ip);
        }
    }
    
    private function writeToFile($eventType, $message, $severity, $ip, $userAgent) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$severity}] [{$eventType}] IP: {$ip} - {$message}";
        
        if (!empty($userAgent)) {
            $logEntry .= " | UA: " . substr($userAgent, 0, 200);
        }
        
        $logEntry .= PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function writeToDatabase($ip, $eventType, $severity, $message, $userAgent, $referer, $requestUri, $requestMethod, $additionalData) {
        try {
            $this->db->query(
                "INSERT INTO security_logs (ip_address, event_type, severity, message, user_agent, referer, request_uri, request_method, additional_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $ip,
                    $eventType,
                    $severity,
                    $message,
                    substr($userAgent, 0, 1000),
                    substr($referer, 0, 500),
                    substr($requestUri, 0, 500),
                    $requestMethod,
                    $additionalData ? json_encode($additionalData) : null
                ]
            );
        } catch (Exception $e) {
            error_log("Security log database yazma hatası: " . $e->getMessage());
        }
    }
    
    private function handleCriticalAlert($eventType, $message, $ip) {
        // Critical events için özel işlemler
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("CRITICAL SECURITY ALERT: [{$eventType}] from IP {$ip} - {$message}");
        }
        
        // Burada email veya SMS gönderilebilir
        // mail($adminEmail, "Critical Security Alert", $message);
    }
    
    public function logLoginAttempt($username, $success, $reason = '') {
        $severity = $success ? 'LOW' : 'MEDIUM';
        $message = $success ? 
            "Başarılı giriş: {$username}" : 
            "Başarısız giriş: {$username}" . ($reason ? " - {$reason}" : "");
            
        $this->logSecurityEvent('LOGIN_ATTEMPT', $message, $severity, [
            'username' => $username,
            'success' => $success,
            'reason' => $reason
        ]);
    }
    
    public function logBruteForceAttempt($attempts, $blocked = false) {
        $severity = $blocked ? 'HIGH' : 'MEDIUM';
        $message = $blocked ? 
            "IP bloklandı - {$attempts} başarısız deneme" : 
            "Brute force denemesi - {$attempts} başarısız deneme";
            
        $this->logSecurityEvent('BRUTE_FORCE', $message, $severity, [
            'attempts' => $attempts,
            'blocked' => $blocked
        ]);
    }
    
    public function logSecurityViolation($violationType, $details) {
        $this->logSecurityEvent('SECURITY_VIOLATION', "Güvenlik ihlali: {$violationType} - {$details}", 'HIGH', [
            'violation_type' => $violationType,
            'details' => $details
        ]);
    }
    
    public function logSuspiciousActivity($activity, $details = '') {
        $this->logSecurityEvent('SUSPICIOUS_ACTIVITY', "Şüpheli aktivite: {$activity}" . ($details ? " - {$details}" : ""), 'MEDIUM', [
            'activity' => $activity,
            'details' => $details
        ]);
    }
    
    public function getRecentEvents($limit = 100, $severity = null) {
        try {
            $sql = "SELECT * FROM security_logs";
            $params = [];
            
            if ($severity) {
                $sql .= " WHERE severity = ?";
                $params[] = $severity;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            return $this->db->query($sql, $params)->fetchAll();
        } catch (Exception $e) {
            error_log("Security log okuma hatası: " . $e->getMessage());
            return [];
        }
    }
    
    public function getEventsByIP($ip, $hours = 24) {
        try {
            $sql = "SELECT * FROM security_logs 
                    WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                    ORDER BY created_at DESC";
            
            return $this->db->query($sql, [$ip, $hours])->fetchAll();
        } catch (Exception $e) {
            error_log("IP bazlı log okuma hatası: " . $e->getMessage());
            return [];
        }
    }
    
    public function cleanOldLogs($days = 30) {
        try {
            $this->db->query(
                "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
                [$days]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Eski log temizleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // getClientIP() fonksiyonu functions.php'de global olarak tanımlı
}
?>
