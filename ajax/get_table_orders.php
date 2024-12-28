<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    $table_id = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;

    // Debug için
    error_log('Getting orders for table_id: ' . $table_id);

    // Siparişleri çek
    $orders = $db->query(
        "SELECT o.id, o.table_id, o.status, o.created_at,
                oi.id as item_id, oi.quantity,
                p.name, p.price
         FROM orders o 
         LEFT JOIN order_items oi ON o.id = oi.order_id 
         LEFT JOIN products p ON oi.product_id = p.id 
         WHERE o.table_id = ? AND o.payment_id IS NULL
         ORDER BY o.created_at DESC",
        [$table_id]
    )->fetchAll();

    // Debug için
    error_log('Raw orders: ' . print_r($orders, true));

    // Siparişleri düzenle
    $formattedOrders = [];
    foreach ($orders as $order) {
        if (!isset($formattedOrders[$order['id']])) {
            $formattedOrders[$order['id']] = [
                'id' => $order['id'],
                'table_id' => $order['table_id'],
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'items' => []
            ];
        }

        if ($order['item_id']) {
            $formattedOrders[$order['id']]['items'][] = [
                'id' => $order['item_id'],
                'name' => $order['name'],
                'quantity' => $order['quantity'],
                'price' => $order['price']
            ];
        }
    }

    // Debug için
    error_log('Formatted orders: ' . print_r(array_values($formattedOrders), true));

    echo json_encode(array_values($formattedOrders));

} catch (Exception $e) {
    error_log('Error in get_table_orders: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 