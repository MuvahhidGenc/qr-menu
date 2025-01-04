<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    // Form verilerini al
    $data = [
        'customer_name' => $_POST['customer_name'],
        'customer_phone' => $_POST['customer_phone'],
        'customer_email' => $_POST['customer_email'] ?? null,
        'table_id' => $_POST['table_id'],
        'guest_count' => $_POST['guest_count'],
        'reservation_date' => $_POST['reservation_date'],
        'reservation_time' => $_POST['reservation_time'],
        'special_requests' => $_POST['special_requests'] ?? null,
        'status' => 'pending'
    ];

    // Masa müsaitlik kontrolü
    $existingReservation = $db->query(
        "SELECT id FROM reservations 
         WHERE table_id = ? 
         AND reservation_date = ? 
         AND reservation_time = ? 
         AND status IN ('pending', 'confirmed')",
        [$data['table_id'], $data['reservation_date'], $data['reservation_time']]
    )->fetch();

    if ($existingReservation) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu masa için seçilen tarih ve saatte başka bir rezervasyon bulunmaktadır.'
        ]);
        exit;
    }

    // Rezervasyonu kaydet
    $db->query(
        "INSERT INTO reservations 
         (customer_name, customer_phone, customer_email, table_id, guest_count, 
          reservation_date, reservation_time, special_requests, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($data)
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 