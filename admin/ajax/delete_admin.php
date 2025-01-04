<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum ve yetki kontrolü
if (!isLoggedIn()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}

if(isset($_POST['id'])) {
    $db = new Database();
    
    try {
        // Silinecek kullanıcının rolünü kontrol et
        $admin = $db->query("
            SELECT a.*, r.slug as role_slug 
            FROM admins a 
            LEFT JOIN roles r ON a.role_id = r.id 
            WHERE a.id = ?
        ", [$_POST['id']])->fetch();
        
        if($admin['role_slug'] === 'super-admin') {
            die(json_encode(['error' => 'Süper Admin kullanıcısı silinemez']));
        }
        
        // Kullanıcıyı sil
        $db->query("DELETE FROM admins WHERE id = ?", [$_POST['id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Delete Admin Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veritabanı hatası oluştu']);
    }
} else {
    echo json_encode(['error' => 'Geçersiz istek']);
} 