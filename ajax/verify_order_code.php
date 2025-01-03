<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';

    // Debug log
    error_log("Gelen kod: " . $code);

    if (empty($code)) {
        throw new Exception('Sipariş kodu gerekli');
    }

    $db = new Database();
    
    // Mevcut tablo yapısına uygun sorgu
    $sql = "SELECT id FROM order_codes 
            WHERE code = ? 
            AND active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())";
            
    error_log("SQL Sorgusu: " . $sql);
    error_log("Aranan kod: " . $code);
    
    // Kodu veritabanında kontrol et
    $result = $db->query($sql, [$code])->fetch();
    
    // Debug için sonucu yazdır
    error_log("Sorgu sonucu: " . print_r($result, true));

    if (!$result) {
        // Hata durumunda daha detaylı bilgi
        $error_msg = 'Geçersiz sipariş kodu';
        error_log("Doğrulama hatası: " . $error_msg);
        echo json_encode([
            'success' => false, 
            'message' => $error_msg,
            'debug' => [
                'code' => $code,
                'sql' => $sql
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Hata oluştu: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 