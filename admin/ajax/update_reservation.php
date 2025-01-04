<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Durum güncelle
    $db->query(
        "UPDATE reservations SET status = ? WHERE id = ?",
        [$status, $id]
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 