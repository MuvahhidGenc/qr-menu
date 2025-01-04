<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!isAdmin() && !isSuperAdmin()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // JSON verisini al
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Debug için
        error_log('Received data: ' . print_r($input, true));

        // Veri kontrolü
        if (!isset($input['id']) || !isset($input['name'])) {
            die(json_encode(['error' => 'Eksik veri']));
        }

        $db = new Database();

        $id = $input['id'];
        $name = $input['name'];
        $description = $input['description'] ?? null;
        $permissions = $input['permissions'] ?? [];

        // Permissions'ı JSON'a çevir
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE);

        // Debug için
        error_log('Permissions to save: ' . $permissionsJson);

        // Rolü güncelle
        $result = $db->query("
            UPDATE roles 
            SET name = ?, 
                description = ?, 
                permissions = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ", [$name, $description, $permissionsJson, $id]);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Güncelleme başarısız']);
        }

    } catch (Exception $e) {
        error_log("Edit Role Error: " . $e->getMessage());
        echo json_encode(['error' => 'Sistem hatası: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Geçersiz istek metodu']);
} 