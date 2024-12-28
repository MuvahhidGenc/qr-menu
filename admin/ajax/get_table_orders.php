<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

$db = new Database();

try {
    $table_id = $_GET['table_id'];
    
    // Masanın tüm aktif (ödenmemiş) siparişlerini çek
    $orders = $db->query(
        "SELECT o.*, 
         GROUP_CONCAT(
            CONCAT(oi.quantity, 'x ', p.name, ' (', FORMAT(oi.price, 2), ' ₺)') 
            SEPARATOR '<br>'
         ) as items
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE o.table_id = ? AND o.payment_id IS NULL
         GROUP BY o.id
         ORDER BY o.created_at DESC",
        [$table_id]
    )->fetchAll();

    // HTML oluştur
    $html = '';
    $total = 0;

    if($orders) {
        foreach($orders as $order) {
            $html .= '<div class="mb-2">
                <div class="d-flex justify-content-between">
                    <small>Sipariş #' . $order['id'] . '</small>
                    <small>' . date('H:i', strtotime($order['created_at'])) . '</small>
                </div>
                <div>' . $order['items'] . '</div>
                <div class="text-end">
                    <strong>' . number_format($order['total_amount'], 2) . ' ₺</strong>
                </div>
            </div>';
            $total += $order['total_amount'];
        }
    } else {
        $html = '<div class="text-center text-muted">Aktif sipariş yok</div>';
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'html' => $html,
        'total' => $total
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 