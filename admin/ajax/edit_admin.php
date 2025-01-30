<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('users.edit')) {
    echo json_encode(['success' => false, 'error' => 'Yetkiniz yok']);
    exit;
}

try {
    $db = new Database();
    
    // POST verilerini al
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $password = $_POST['password'] ?? '';

    // Zorunlu alanları kontrol et
    if (empty($id) || empty($username) || empty($name) || empty($role_id)) {
        echo json_encode(['success' => false, 'error' => 'Zorunlu alanları doldurun']);
        exit;
    }

    // SQL sorgusunu hazırla
    $sql = "UPDATE admins SET username = ?, name = ?, email = ?, role_id = ?";
    $params = [$username, $name, $email, $role_id];

    // Şifre değiştirilecekse ekle
    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    // WHERE koşulunu ekle
    $sql .= " WHERE id = ?";
    $params[] = $id;

    // Güncelleme işlemini gerçekleştir
    $stmt = $db->query($sql, $params);

    if ($stmt) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Güncelleme başarısız']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Sistem hatası oluştu']);
} 