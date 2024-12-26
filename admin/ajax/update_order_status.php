<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['order_id']) || !isset($_POST['status'])) {
        throw new Exception('Eksik parametreler');
    }

    $db = new Database();
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    // Durumu güncelle
    $db->query("UPDATE orders SET status = ? WHERE id = ?", [$status, $order_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Sipariş durumu güncellendi'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}