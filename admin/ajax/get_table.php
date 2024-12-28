<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

$db = new Database();

try {
    $id = $_GET['id'];
    
    $table = $db->query(
        "SELECT * FROM tables WHERE id = ?",
        [$id]
    )->fetch();

    if ($table) {
        echo json_encode([
            'success' => true,
            'table' => $table
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Masa bulunamadı.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 