<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

try {
    $db = new Database();
    $db->beginTransaction();

    // POST verilerini al
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    if (empty($data)) {
        throw new Exception('Geçersiz veri formatı');
    }

    // Zorunlu alanları kontrol et
    if (empty($data['customer_name']) || empty($data['reservation_date']) || 
        empty($data['reservation_time']) || empty($data['person_count'])) {
        throw new Exception('Lütfen tüm zorunlu alanları doldurun');
    }

    // Rezervasyon kaydet
    $query = "INSERT INTO reservations (
        customer_name, customer_phone, table_id, guest_count,
        reservation_date, reservation_time, special_requests, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $data['customer_name'],
        $data['phone'],
        $data['table_id'],
        $data['person_count'],
        $data['reservation_date'],
        $data['reservation_time'],
        $data['note']
    ]);

    $reservationId = $db->lastInsertId();

    // Ön sipariş ürünlerini kaydet
    if (!empty($data['pre_order'])) {
        $preOrders = json_decode($data['pre_order'], true);
        
        $query = "INSERT INTO pre_orders (
            reservation_id, product_id, quantity, price
        ) VALUES (?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        foreach ($preOrders as $item) {
            $stmt->execute([
                $reservationId,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Rezervasyon başarıyla kaydedildi'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Save Reservation Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Sorgu hatası: ' . $e->getMessage()
    ]);
} 