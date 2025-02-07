<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json'); // JSON header'ı ekle

// Hata raporlamayı aç
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// POST verilerini logla
error_log("POST Data: " . print_r($_POST, true));

// Oturum kontrolü
if (!isLoggedIn()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}

try {
    // Yetki kontrolü
    if (!hasPermission('admins.add')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $db = new Database();
    
    // Form verilerini al
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    
    // Validasyonlar
    if (empty($username) || empty($password) || empty($name) || empty($role_id)) {
        throw new Exception('Lütfen tüm zorunlu alanları doldurun.');
    }
    
    // Şifreyi hashle
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kullanıcı adı kontrolü
    $existingUser = $db->query("SELECT id FROM admins WHERE username = ?", [$username])->fetch();
    if ($existingUser) {
        throw new Exception('Bu kullanıcı adı zaten kullanılıyor.');
    }
    
    // Personeli ekle
    $db->query(
        "INSERT INTO admins (username, password, name, role_id, email, phone) VALUES (?, ?, ?, ?, ?, ?)",
        [$username, $hashedPassword, $name, $role_id, $email, $phone]
    );
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Add Admin Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 