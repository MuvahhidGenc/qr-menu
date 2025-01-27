<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Yetki kontrolü
if (!hasPermission('order_settings.edit')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// POST verilerini al
$codeRequired = isset($_POST['code_required']) ? (int)$_POST['code_required'] : 0;
$codeLength = isset($_POST['code_length']) ? $_POST['code_length'] : '4';

try {
    $db = new Database();
    
    // Mevcut ayarları kontrol et
    $existingSettings = $db->query("SELECT id FROM order_settings LIMIT 1")->fetch();
    
    if ($existingSettings) {
        // Mevcut ayarları güncelle
        $db->query(
            "UPDATE order_settings SET code_required = ?, code_length = ?, updated_at = NOW() WHERE id = ?",
            [$codeRequired, $codeLength, $existingSettings['id']]
        );
    } else {
        // Yeni ayar ekle
        $db->query(
            "INSERT INTO order_settings (code_required, code_length) VALUES (?, ?)",
            [$codeRequired, $codeLength]
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 