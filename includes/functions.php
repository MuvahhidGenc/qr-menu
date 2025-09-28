<?php 

function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    return array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    );
}

function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));

    $r = max(0,min(255,$r + $steps));
    $g = max(0,min(255,$g + $steps));
    $b = max(0,min(255,$b + $steps));

    return '#'.dechex($r).dechex($g).dechex($b);
}

/**
 * Gelen input verilerini temizler ve güvenli hale getirir
 * 
 * @param string|array $input Temizlenecek veri
 * @return string|array Temizlenmiş veri
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    
    if (!is_string($input)) {
        return $input;
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    
    // SQL injection koruması
    $sqlPatterns = [
        '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|vbscript)(\s|$)/i',
        '/(\s|^)(or|and)(\s+)(\d+)(\s*)(=|<|>)(\s*)(\d+)/i',
        '/(\s|^)(or|and)(\s+)([\w\d_]+)(\s*)(=|<|>|like)(\s*)([\w\d_\'\"]+)/i',
        '/(\s|^)([\w\d_]+)(\s*)(=|<|>)(\s*)([\w\d_]+)(\s+)(or|and)(\s+)([\w\d_]+)(\s*)(=|<|>)/i',
        '/(\'|\"|;|\/\*|\*\/|--|\#)/i',
        '/(\s|^)(information_schema|mysql|sys|performance_schema)(\s|$)/i'
    ];
    
    foreach ($sqlPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            throw new Exception('Güvenlik ihlali tespit edildi: Şüpheli karakter dizisi');
        }
    }
    
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * SQL Injection koruması için PDO parametrelerini hazırlar
 * 
 * @param array $data Temizlenecek veri dizisi
 * @return array Temizlenmiş veri dizisi
 */
function prepareParams($data) {
    return array_map('cleanInput', $data);
}

/**
 * Dosya yükleme işlemi için güvenli dosya adı oluşturur
 * 
 * @param string $filename Orijinal dosya adı
 * @return string Güvenli dosya adı
 */
function sanitizeFileName($filename) {
    // Uzantıyı al
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Benzersiz bir isim oluştur
    $safeName = uniqid() . '.' . $extension;
    
    return $safeName;
}

/**
 * Tarih formatını düzenler
 * 
 * @param string $date Tarih
 * @param string $format İstenen format
 * @return string Formatlanmış tarih
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Para birimini formatlar
 * 
 * @param float $amount Miktar
 * @return string Formatlanmış miktar
 */
function formatMoney($amount) {
    return number_format($amount, 2, ',', '.') . ' ₺';
}

/**
 * URL parametrelerini güvenli şekilde alır
 * 
 * @param string $key Parametre adı
 * @param int $filter Filtre türü
 * @param mixed $default Varsayılan değer
 * @return mixed Güvenli parametre değeri
 */
function getSecureParam($key, $filter = FILTER_SANITIZE_STRING, $default = null) {
    $value = filter_input(INPUT_GET, $key, $filter);
    return $value !== false && $value !== null ? $value : $default;
}

/**
 * Integer parametre güvenli şekilde alır
 * 
 * @param string $key Parametre adı
 * @param int $default Varsayılan değer
 * @return int Güvenli integer değer
 */
function getSecureInt($key, $default = 0) {
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    return $value !== false && $value !== null ? $value : $default;
}

/**
 * Güvenli IP adresi alma
 */
function getClientIP() {
    // Öncelikle gerçek IP'yi kontrol et
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Sadece güvenilir proxy'ler varsa X-Forwarded-For'u kontrol et
    if (defined('TRUSTED_PROXIES') && TRUSTED_PROXIES) {
        // X-Forwarded-For header'ını kontrol et
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $forwarded_ip = trim($forwarded[0]);
            
            // IP formatını doğrula ve private/reserved IP'leri kabul etme
            if (filter_var($forwarded_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $forwarded_ip;
            }
        }
        
        // X-Real-IP header'ını da kontrol et
        if (isset($_SERVER['HTTP_X_REAL_IP']) && !empty($_SERVER['HTTP_X_REAL_IP'])) {
            $real_ip = $_SERVER['HTTP_X_REAL_IP'];
            if (filter_var($real_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $real_ip;
            }
        }
    }
    
    // Final IP doğrulaması
    $finalIP = filter_var($ip, FILTER_VALIDATE_IP);
    return $finalIP ? $finalIP : '0.0.0.0';
}

/**
 * Güvenli slug oluşturur
 * 
 * @param string $text Metin
 * @return string Slug
 */
function createSlug($text) {
    // Türkçe karakterleri değiştir
    $text = str_replace(
        ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
        ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
        $text
    );
    
    // Küçük harfe çevir
    $text = strtolower($text);
    
    // Alfanumerik olmayan karakterleri tire ile değiştir
    $text = preg_replace('/[^a-z0-9]/', '-', $text);
    
    // Birden fazla tireyi tek tireye indir
    $text = preg_replace('/-+/', '-', $text);
    
    // Baştaki ve sondaki tireleri kaldır
    return trim($text, '-');
}

?>