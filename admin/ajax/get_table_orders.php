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
    
    // Siparişleri çek - sadece ödenmemiş ürünler
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
         AND oi.payment_id IS NULL
         ORDER BY o.created_at DESC",
        [$tableId]
    )->fetchAll();
    
    // Tutar bazlı kısmi ödemelerin toplamını hesapla (sadece aktif siparişler varsa)
    $partialPaymentsTotal = 0;
    if (count($orders) > 0) {
        $partialPayments = $db->query(
            "SELECT COALESCE(SUM(paid_amount), 0) as total_paid
             FROM payments
             WHERE table_id = ?
             AND status = 'completed'
             AND payment_note IS NOT NULL
             AND payment_note LIKE '%\"type\":\"amount\"%'",
            [$tableId]
        )->fetch();
        $partialPaymentsTotal = $partialPayments['total_paid'];
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'partial_payments_total' => $partialPaymentsTotal, // Masa kapatıldıysa 0
        'debug' => [
            'table_id' => $tableId,
            'order_count' => count($orders),
            'partial_payments' => $partialPaymentsTotal,
            'note' => count($orders) > 0 ? 'Fiyatlar zaten düşürüldü, toplam doğru' : 'Masa kapatıldı, kısmi ödeme bilgisi temizlendi',
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