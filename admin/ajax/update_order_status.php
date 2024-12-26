<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(empty($_POST['order_id']) || empty($_POST['status'])) {
        throw new Exception('Eksik parametreler');
    }

    $db = new Database();
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    // Debug için
    error_log("SQL Parametreleri: order_id = $order_id, status = $status");

    // Sipariş var mı kontrol et
    $check = $db->query("SELECT id, status FROM orders WHERE id = ?", [$order_id])->fetch();
    if(!$check) {
        throw new Exception('Sipariş bulunamadı');
    }

    // Güncelleme yap
    $stmt = $db->query("UPDATE orders SET status = ? WHERE id = ?", [$status, $order_id]);
    
    // Debug için
    error_log("Etkilenen satır sayısı: " . $stmt->rowCount());

    if($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Sipariş durumu güncellendi'
        ]);
    } else {
        throw new Exception('Güncelleme başarısız oldu');
    }

} catch(Exception $e) {
    error_log('Hata: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}