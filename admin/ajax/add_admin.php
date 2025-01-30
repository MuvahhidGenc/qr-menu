<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    try {
        // Zorunlu alanları kontrol et
        if (empty($_POST['username']) || empty($_POST['name']) || empty($_POST['password']) || empty($_POST['role_id'])) {
            error_log("Validation Error: Missing required fields");
            die(json_encode(['error' => 'Lütfen zorunlu alanları doldurun']));
        }

        // Kullanıcı adı benzersiz mi kontrol et
        $exists = $db->query("SELECT id FROM admins WHERE username = ?", [$_POST['username']])->fetch();
        if ($exists) {
            error_log("Validation Error: Username already exists");
            die(json_encode(['error' => 'Bu kullanıcı adı zaten kullanılıyor']));
        }

        // Şifreyi hashle
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // SQL sorgusunu logla
        $sql = "INSERT INTO admins (username, name, email, phone, password, role_id) VALUES (?, ?, ?, ?, ?, ?)";
        error_log("SQL Query: " . $sql);
        error_log("SQL Params: " . print_r([
            $_POST['username'],
            $_POST['name'],
            $_POST['email'] ?? null,
            $_POST['phone'] ?? null,
            $hashedPassword,
            $_POST['role_id']
        ], true));

        // Yeni kullanıcıyı ekle
        $result = $db->query($sql, [
            $_POST['username'],
            $_POST['name'],
            $_POST['email'] ?? null,
            $_POST['phone'] ?? null,
            $hashedPassword,
            $_POST['role_id']
        ]);

        error_log("Query Result: " . print_r($result, true));
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log("Add Admin Error: " . $e->getMessage());
        error_log("Stack Trace: " . $e->getTraceAsString());
        echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Geçersiz istek']);
} 