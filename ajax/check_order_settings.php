<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Sipariş ayarlarını kontrol et
    $settings = $db->query("SELECT * FROM order_settings WHERE id = 1")->fetch();
    
    if (!$settings) {
        // Varsayılan ayarları döndür
        echo json_encode([
            'code_required' => false,
            'code_length' => '4'
        ]);
        exit;
    }
    
    echo json_encode([
        'code_required' => (bool)$settings['code_required'],
        'code_length' => $settings['code_length']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Sistem hatası: ' . $e->getMessage()]);
} 