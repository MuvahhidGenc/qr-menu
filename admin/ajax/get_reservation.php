<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

$db = new Database();

try {
    $id = $_GET['id'];
    
    $reservation = $db->query(
        "SELECT r.*, t.table_no 
         FROM reservations r 
         LEFT JOIN tables t ON r.table_id = t.id 
         WHERE r.id = ?",
        [$id]
    )->fetch();

    if ($reservation) {
        echo json_encode([
            'success' => true,
            'reservation' => $reservation
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Rezervasyon bulunamadı.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 