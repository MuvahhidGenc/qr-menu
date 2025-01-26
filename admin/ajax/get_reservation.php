<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'Rezervasyon ID gerekli']));
}

$db = new Database();

// Rezervasyon detaylarını ve masa bilgisini çekelim
$query = "SELECT 
            r.*,
            t.table_no,
            t.id as table_id
          FROM reservations r
          LEFT JOIN tables t ON r.table_id = t.id
          WHERE r.id = ?";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reservation) {
        // Ön siparişleri ayrı bir sorgu ile çekelim
        $orderQuery = "SELECT 
                        ro.id,
                        p.name,
                        ro.quantity,
                        p.price,
                        (ro.quantity * p.price) as total
                      FROM reservation_orders ro
                      JOIN products p ON ro.product_id = p.id
                      WHERE ro.reservation_id = ?";
        
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->execute([$_GET['id']]);
        $preOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ön sipariş verilerini ekleyelim
        $reservation['pre_order_items'] = json_encode($preOrders ?: []);

        echo json_encode([
            'success' => true,
            'reservation' => $reservation
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Rezervasyon bulunamadı'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
} 