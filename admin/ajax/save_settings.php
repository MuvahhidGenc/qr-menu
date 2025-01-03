<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Form verilerini al
    $codeRequired = isset($_POST['code_required']) ? 1 : 0;
    $codeLength = $_POST['code_length'] ?? '4';
    
    // Geçerlilik kontrolü
    if (!in_array($codeLength, ['4', '6'])) {
        throw new Exception('Geçersiz kod uzunluğu');
    }
    
    // Debug için
    error_log('Saving settings: ' . print_r($_POST, true));
    
    // Önce tüm ayarları sil (temiz başlangıç)
    $db->query("TRUNCATE TABLE order_settings");
    
    // Yeni ayarları ekle
    $db->query(
        "INSERT INTO order_settings (code_required, code_length) 
        VALUES (?, ?)",
        [$codeRequired, $codeLength]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Ayarlar başarıyla kaydedildi'
    ]);

} catch (Exception $e) {
    error_log('Settings save error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 