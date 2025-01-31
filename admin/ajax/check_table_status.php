<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['reservation_id'])) {
    echo json_encode(['success' => false, 'message' => 'Rezervasyon ID gerekli']);
    exit;
}

$reservationId = $_GET['reservation_id'];

try {
    // Veritabanı bağlantısını oluştur
    $db = new Database();
    
    // Rezervasyon bilgilerini al
    $reservation = $db->query(
        "SELECT * FROM reservations WHERE id = ?", 
        [$reservationId]
    )->fetch();

    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Rezervasyon bulunamadı']);
        exit;
    }

    // Masada aktif sipariş var mı kontrol et
    if ($reservation['table_id']) {
        $result = $db->query(
            "SELECT COUNT(*) as count 
            FROM orders 
            WHERE table_id = ? 
            AND status NOT IN ('completed', 'cancelled')",
            [$reservation['table_id']]
        )->fetch();

        if ($result['count'] > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Bu masada ödenmemiş sipariş bulunmaktadır. Lütfen önce ödeme alınız.',
                'has_active_orders' => true
            ]);
            exit;
        }
    }

    // Rezervasyona ait ön siparişleri al
    $preOrders = $db->query(
        "SELECT ro.*, p.name as product_name, p.price 
        FROM reservation_orders ro
        JOIN products p ON p.id = ro.product_id
        WHERE ro.reservation_id = ?",
        [$reservationId]
    )->fetchAll();

    echo json_encode([
        'success' => true,
        'reservation' => $reservation,
        'pre_orders' => $preOrders,
        'has_active_orders' => false
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
} 