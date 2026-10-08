<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Rezervasyon + aktif sipariş kontrolü -> reservations.view. Önceden auth yoktu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('reservations.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rezervasyon ID gerekli']);
    exit;
}

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
    )->fetchAll(PDO::FETCH_ASSOC);

    // Rezervasyon bilgilerini al
    $reservation = $db->query(
        "SELECT * FROM reservations WHERE id = ?",
        [$id]
    )->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Rezervasyon bulunamadı.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'reservation' => $reservation,
        'active_orders' => $activeOrders,
        'has_active_orders' => !empty($activeOrders)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('check_reservation_status hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Rezervasyon bilgisi alınamadı.'
    ], JSON_UNESCAPED_UNICODE);
}