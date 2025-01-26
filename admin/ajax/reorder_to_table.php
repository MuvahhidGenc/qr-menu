<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!hasPermission('payments.manage')) {
        throw new Exception('Bu işlem için yetkiniz yok!');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $paymentId = $data['payment_id'] ?? null;
    $tableId = $data['table_id'] ?? null;

    if (!$paymentId || !$tableId) {
        throw new Exception('Geçersiz parametreler');
    }

    $db = new Database();
    
    // Transaction başlat
    $db->beginTransaction();

    // İptal edilmiş ödemeye ait siparişleri al
    $orderItems = $db->query("
        SELECT oi.product_id, oi.quantity, oi.price
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.payment_id = ?
    ", [$paymentId])->fetchAll();

    // Toplam tutarı hesapla
    $totalAmount = 0;
    foreach ($orderItems as $item) {
        $totalAmount += ($item['quantity'] * $item['price']);
    }

    // Yeni sipariş oluştur
    $db->query("
        INSERT INTO orders (table_id, status, created_at, total_amount) 
        VALUES (?, 'pending', NOW(), ?)", 
        [$tableId, $totalAmount]
    );
    
    $newOrderId = $db->lastInsertId();

    // Sipariş ürünlerini ekle
    foreach ($orderItems as $item) {
        $db->query("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)",
            [$newOrderId, $item['product_id'], $item['quantity'], $item['price']]
        );
    }

    // Masa durumunu güncelle
    $db->query("UPDATE tables SET status = 'occupied' WHERE id = ?", [$tableId]);

    // Bildirim ekle
    $tableNo = $db->query("SELECT table_no FROM tables WHERE id = ?", [$tableId])->fetch()['table_no'];
    $db->query("
        INSERT INTO notifications (order_id, type, message, is_read) 
        VALUES (?, 'new_order', ?, 0)",
        [$newOrderId, "Masa {$tableNo}'a iptal edilmiş siparişler yeniden eklendi!"]
    );

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 