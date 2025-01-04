<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'error' => 'ID gerekli']));
}

try {
    $db = new Database();
    $admin = $db->query("
        SELECT a.*, r.name as role_name 
        FROM admins a 
        LEFT JOIN roles r ON a.role_id = r.id 
        WHERE a.id = ?
    ", [$_GET['id']])->fetch();

    if ($admin) {
        echo json_encode([
            'success' => true,
            'id' => $admin['id'],
            'username' => $admin['username'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role_id' => $admin['role_id']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Yönetici bulunamadı']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 