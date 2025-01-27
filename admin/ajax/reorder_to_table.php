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
    
    // Hedef masanın durumunu kontrol et
    $tableCheck = $db->query("
        SELECT 
            t.status,
            (SELECT COUNT(*) FROM orders o 
             WHERE o.table_id = t.id 
             AND o.status IN ('pending', 'preparing', 'ready', 'delivered')
             AND o.payment_id IS NULL) as active_orders
        FROM tables t 
        WHERE t.id = ?", 
        [$tableId]
    )->fetch();

    // Masada aktif sipariş varsa engelle
    if ($tableCheck['active_orders'] > 0) {
        throw new Exception('Hedef masada ödenmemiş siparişler bulunuyor. Lütfen önce mevcut siparişlerin ödemesini yapın.');
    }

    // Transaction başlat
    $db->beginTransaction();

    try {
        // İptal edilmiş ödemeye ait siparişleri al
        $orderItems = $db->query("
            SELECT oi.product_id, oi.quantity, oi.price
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.payment_id = ?", 
            [$paymentId]
        )->fetchAll();

        if (empty($orderItems)) {
            throw new Exception('Aktarılacak sipariş bulunamadı');
        }

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
        $db->query("
            UPDATE tables 
            SET status = 'occupied', 
                updated_at = NOW() 
            WHERE id = ?", 
            [$tableId]
        );

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
        $db->rollBack(); // PDO'nun rollBack metodunu kullan
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 