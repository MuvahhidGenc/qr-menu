<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Ayarları al
    $settings = $db->query("SELECT * FROM order_settings ORDER BY id DESC LIMIT 1")->fetch();
    $codeLength = $settings['code_length'] ?? '4';

    // Benzersiz kod üret
    do {
        $code = '';
        for ($i = 0; $i < $codeLength; $i++) {
            $code .= mt_rand(0, 9);
        }

        // Kodun benzersiz olduğunu kontrol et
        $exists = $db->query(
            "SELECT 1 FROM order_codes WHERE code = ?", 
            [$code]
        )->fetch();
    } while ($exists);

    // Tüm aktif kodları pasife çek
    $db->query("UPDATE order_codes SET active = 0 WHERE active = 1");

    // Yeni kodu ekle (24 saat geçerli)
    $db->query(
        "INSERT INTO order_codes (code, expires_at) 
        VALUES (?, DATE_ADD(NOW(), INTERVAL 24 HOUR))",
        [$code]
    );

    // Debug için
    error_log('Generated new code: ' . $code);

    echo json_encode([
        'success' => true,
        'message' => 'Kod başarıyla üretildi',
        'code' => $code,
        'expires_at' => date('d.m.Y H:i', strtotime('+24 hours'))
    ]);

} catch (Exception $e) {
    error_log('Code generation error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 