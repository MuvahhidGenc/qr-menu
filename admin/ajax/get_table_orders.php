<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');



try {
    $data = json_decode(file_get_contents('php://input'), true);
    $tableId = $data['table_id'] ?? null;

    if (!$tableId) {
        throw new Exception('Masa ID gerekli');
    }

    $db = new Database();
    
    // Siparişleri çek
    $orders = $db->query(
        "SELECT o.id as order_id, oi.id as item_id, 
                p.name as product_name, oi.quantity, 
                oi.price, o.status,
                (oi.quantity * oi.price) as total
         FROM orders o
         JOIN order_items oi ON o.id = oi.order_id
         JOIN products p ON oi.product_id = p.id
         WHERE o.table_id = ? 
         AND o.status NOT IN ('cancelled', 'completed')
         AND o.payment_id IS NULL
         ORDER BY o.created_at DESC",
        [$tableId]
    )->fetchAll();

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'debug' => [
            'table_id' => $tableId,
            'order_count' => count($orders),
            'user' => [
                'isLoggedIn' => isLoggedIn(),
                'permissions' => $_SESSION['permissions'] ?? []
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('Error in get_table_orders: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Siparişler yüklenirken bir hata oluştu: ' . $e->getMessage()
    ]);
} 