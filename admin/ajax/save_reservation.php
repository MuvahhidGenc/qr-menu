<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/db.php';

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

    // Ön siparişleri al
    $preOrders = json_decode($data['pre_order'] ?? '[]', true);

    // Rezervasyon kaydet
    $query = "INSERT INTO reservations (
        customer_name,
        customer_phone,
        reservation_date,
        reservation_time,
        guest_count,
        special_requests,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, 'pending')";

    $params = [
        $data['customer_name'],
        $data['phone'] ?? null,
        $data['reservation_date'],
        $data['reservation_time'],
        $data['person_count'],
        $data['note'] ?? null
    ];

    $db->query($query, $params);
    $reservationId = $db->lastInsertId();

    // Ön siparişleri reservation_orders tablosuna kaydet
    if (!empty($preOrders)) {
        foreach ($preOrders as $item) {
            $itemQuery = "INSERT INTO reservation_orders (
                reservation_id,
                product_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)";

            $db->query($itemQuery, [
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