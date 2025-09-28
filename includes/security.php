<?php
// Güvenlik fonksiyonları

// XSS koruması için
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// CSRF token oluşturma
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token kontrolü
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF token doğrulaması başarısız');
    }
    return true;
}

// SQL Injection koruması için
function escapeSQL($string) {
    global $db;
    if (is_array($string)) {
        return array_map('escapeSQL', $string);
    }
    if (is_numeric($string)) {
        return $string;
    }
    return $db->quote($string);
}

// Güvenli dosya yükleme kontrolü
function validateFile($file, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('Dosya boyutu çok büyük');
    }

    $fileInfo = pathinfo($file['name']);
    if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
        throw new Exception('Geçersiz dosya türü');
    }

    return true;
}

// getClientIP() fonksiyonu functions.php'de tanımlı

// Güvenli şifreleme
function secureEncrypt($data, $key) {
    $cipher = "aes-256-gcm";
    if (in_array($cipher, openssl_get_cipher_methods())) {
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = "";
        $ciphertext = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, "", 16);
        return base64_encode($iv . $tag . $ciphertext);
    }
    throw new Exception('Şifreleme metodu desteklenmiyor');
}

// Güvenli şifre çözme
function secureDecrypt($data, $key) {
    $cipher = "aes-256-gcm";
    if (in_array($cipher, openssl_get_cipher_methods())) {
        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($c, 0, $ivlen);
        $tag = substr($c, $ivlen, 16);
        $ciphertext = substr($c, $ivlen + 16);
        return openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
    throw new Exception('Şifreleme metodu desteklenmiyor');
}

// Güvenli oturum başlatma
function secureSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
        session_start();
    }
}

// Güvenli çıkış
function secureLogout() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

// Varsayılan güvenlik ayarları
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CSP header'ını güncelle
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com");

// Diğer güvenlik header'ları
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
?>