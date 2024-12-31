<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['table_id'])) {
        throw new Exception('Masa ID gerekli');
    }

    $tableId = intval($data['table_id']);
    
    // Masanın durumunu güncelle
    $query = "UPDATE tables SET status = 'empty' WHERE id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$tableId]);
    
    // Masaya ait tüm siparişleri tamamlandı olarak işaretle
    $orderQuery = "UPDATE orders SET status = 'completed' WHERE table_id = ? AND status != 'completed'";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->execute([$tableId]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Masa durumu güncellendi'
        ]);
    } else {
        throw new Exception('Masa durumu güncellenemedi');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 