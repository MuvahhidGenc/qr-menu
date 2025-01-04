<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}

if(isset($_GET['id'])) {
    $db = new Database();
    
    $admin = $db->query("SELECT * FROM admins WHERE id = ?", [$_GET['id']])->fetch();
    
    if($admin) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'role_id' => $admin['role_id']
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Kullanıcı bulunamadı']);
    }
} else {
    echo json_encode(['error' => 'ID parametresi gerekli']);
} 