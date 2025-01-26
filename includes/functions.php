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
    
    $input = trim($input);
    $input = stripslashes($input);
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