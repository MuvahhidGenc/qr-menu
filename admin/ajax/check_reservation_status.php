<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Rezervasyon ID gerekli']);
    exit;
}

$id = $_GET['id'];

try {
    $db = new Database();
    
    // Aktif siparişleri ve ürün detaylarını kontrol et
    $activeOrders = $db->query(
        "SELECT o.id as order_id, oi.quantity, oi.price, p.name as product_name 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE o.reservation_id = ? 
        AND o.status NOT IN ('cancelled', 'completed')", 
        [$id]
    )->fetchAll();

    // Rezervasyon bilgilerini al
    $reservation = $db->query(
        "SELECT * FROM reservations WHERE id = ?", 
        [$id]
    )->fetch();

    echo json_encode([
        'success' => true,
        'reservation' => $reservation,
        'active_orders' => $activeOrders,
        'has_active_orders' => !empty($activeOrders)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 