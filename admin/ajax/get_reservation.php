<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

try {
    $db = new Database();
    
    // ID kontrolü
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Geçersiz rezervasyon ID');
    }
    
    $id = (int)$_GET['id'];

    // Rezervasyon bilgilerini çek
    $reservation = $db->query(
        "SELECT r.*, t.table_no,
        (SELECT GROUP_CONCAT(
            JSON_OBJECT(
                'name', p.name,
                'quantity', po.quantity,
                'price', po.price,
                'total', (po.quantity * po.price)
            )
        )
        FROM pre_orders po
        JOIN products p ON po.item_id = p.id
        WHERE po.reservation_id = r.id) as pre_order_items
        FROM reservations r
        LEFT JOIN tables t ON r.table_id = t.id
        WHERE r.id = ?",
        [$id]
    )->fetch();

    if (!$reservation) {
        throw new Exception('Rezervasyon bulunamadı');
    }

    // Pre-order items'ı JSON array'e çevir
    if ($reservation['pre_order_items']) {
        $reservation['pre_order_items'] = '[' . $reservation['pre_order_items'] . ']';
    }

    echo json_encode([
        'success' => true,
        'reservation' => $reservation
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 