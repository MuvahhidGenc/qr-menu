<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json'); // JSON header'ı ekle

try {
    // Yetki kontrolü
    if (!hasPermission('admins.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $db = new Database();
    
    // Form verilerini al
    $id = $_POST['id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    
    // Validasyonlar
    if (empty($id) || empty($username) || empty($name) || empty($role_id)) {
        throw new Exception('Lütfen tüm zorunlu alanları doldurun.');
    }
    
    // Kullanıcı adı kontrolü (kendi kullanıcı adı hariç)
    $existingUser = $db->query(
        "SELECT id FROM admins WHERE username = ? AND id != ?", 
        [$username, $id]
    )->fetch();
    
    if ($existingUser) {
        throw new Exception('Bu kullanıcı adı zaten kullanılıyor.');
    }
    
    // Şifre değiştirilecek mi?
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "UPDATE admins SET username = ?, password = ?, name = ?, role_id = ?, email = ?, phone = ? WHERE id = ?",
            [$username, $hashedPassword, $name, $role_id, $email, $phone, $id]
        );
    } else {
        $db->query(
            "UPDATE admins SET username = ?, name = ?, role_id = ?, email = ?, phone = ? WHERE id = ?",
            [$username, $name, $role_id, $email, $phone, $id]
        );
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 