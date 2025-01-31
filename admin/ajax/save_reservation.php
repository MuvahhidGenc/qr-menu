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
    
    // POST verilerini al
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    // Gelen veriyi logla
    error_log('Gelen Veri: ' . print_r($data, true));

    if (empty($data)) {
        throw new Exception('Geçersiz veri formatı');
    }

    // Zorunlu alanları kontrol et ve değerleri logla
    $requiredFields = [
        'customer_name' => isset($data['customer_name']) ? $data['customer_name'] : null,
        'reservation_date' => isset($data['reservation_date']) ? $data['reservation_date'] : null,
        'reservation_time' => isset($data['reservation_time']) ? $data['reservation_time'] : null,
        'person_count' => isset($data['person_count']) ? $data['person_count'] : null,  // guest_count yerine person_count
    ];

    error_log('Zorunlu Alanlar: ' . print_r($requiredFields, true));

    // Eksik alanları kontrol et
    $missingFields = array_filter($requiredFields, function($value) {
        return empty($value);
    });

    if (!empty($missingFields)) {
        throw new Exception('Eksik alanlar: ' . implode(', ', array_keys($missingFields)));
    }

    // Opsiyonel alanlar için varsayılan değerler
    $table_id = isset($data['table_id']) ? $data['table_id'] : null;
    $phone = isset($data['phone']) ? $data['phone'] : '';  // customer_phone yerine phone
    $special_requests = isset($data['note']) ? $data['note'] : '';  // special_requests yerine note

    // Rezervasyon kaydet
    $db->query(
        "INSERT INTO reservations (
            customer_name, customer_phone, table_id, guest_count,
            reservation_date, reservation_time, special_requests, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')",
        [
            $data['customer_name'],
            $phone,
            $table_id,
            $data['person_count'],  // guest_count yerine person_count
            $data['reservation_date'],
            $data['reservation_time'],
            $special_requests
        ]
    );

    $reservationId = $db->lastInsertId();

    // Ön sipariş ürünlerini kaydet
    if (!empty($data['pre_order'])) {
        $preOrders = json_decode($data['pre_order'], true);
        
        foreach ($preOrders as $item) {
            $db->query(
                "INSERT INTO pre_orders (
                    reservation_id, item_id, quantity, price
                ) VALUES (?, ?, ?, ?)",
                [
                    $reservationId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]
            );
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Rezervasyon başarıyla kaydedildi',
        'reservation_id' => $reservationId
    ]);

} catch (Exception $e) {
    error_log('Save Reservation Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Sorgu hatası: ' . $e->getMessage()
    ]);
} 