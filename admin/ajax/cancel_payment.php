<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Sadece payments.cancel yetkisi kontrolü yeterli
if (!hasPermission('payments.cancel')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    // CLI/test ve form-post uyumluluğu için $_POST yedeği
    $paymentId = $data['payment_id'] ?? $_POST['payment_id'] ?? null;
    $cancelNote = $data['cancel_note'] ?? $_POST['cancel_note'] ?? null;

    if (!$paymentId) {
        throw new Exception('Geçersiz ödeme ID');
    }

    if (!$cancelNote || trim($cancelNote) === '') {
        throw new Exception('İptal nedeni girilmesi zorunludur');
    }

    $db = new Database();
    
    // Transaction başlat
    $db->beginTransaction();

    // Ödeme durumunu güncelle ve iptal notunu ekle
    $db->query("
        UPDATE payments 
        SET status = 'cancelled', 
            payment_note = CONCAT(COALESCE(payment_note, ''), '\nİptal Nedeni: ', ?) 
        WHERE id = ?", 
        [$cancelNote, $paymentId]
    );
    
    // İlgili siparişleri güncelle
    $db->query("
        UPDATE orders 
        SET status = 'cancelled', cancelled_at = CURRENT_TIMESTAMP
        WHERE payment_id = ?", 
        [$paymentId]
    );

    // Stok iadesi: POS satışında stok satış anında düşülmüştü
    // (order_items.payment_id dolu, order_id 0/NULL olan satırlar).
    // Masa kapanış ödemesinde stok hiç düşülmediği için iade gerekmez.
    $stockRow = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'system_stock_tracking'")->fetch();
    if ($stockRow && $stockRow['setting_value'] == '1') {
        $posItems = $db->query(
            "SELECT product_id, quantity FROM order_items
             WHERE payment_id = ? AND (order_id = 0 OR order_id IS NULL)",
            [$paymentId]
        )->fetchAll();
        foreach ($posItems as $posItem) {
            $product = $db->query(
                "SELECT stock FROM products WHERE id = ? FOR UPDATE",
                [(int)$posItem['product_id']]
            )->fetch();
            if (!$product) {
                continue;
            }
            $oldStock = (int)$product['stock'];
            $newStock = $oldStock + (int)$posItem['quantity'];
            $db->query("UPDATE products SET stock = ? WHERE id = ?", [$newStock, (int)$posItem['product_id']]);
            $db->query(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by, created_at)
                 VALUES (?, 'in', ?, ?, ?, ?, NULL, NOW())",
                [(int)$posItem['product_id'], (int)$posItem['quantity'], $oldStock, $newStock, 'Ödeme #' . $paymentId . ' iptal - POS stok iadesi']
            );
        }
    }

    // Bildirim ekle (POS ödemesinde masa yoktur → LEFT JOIN)
    $paymentInfo = $db->query("
        SELECT t.table_no 
        FROM payments p 
        LEFT JOIN tables t ON p.table_id = t.id 
        WHERE p.id = ?", 
        [$paymentId]
    )->fetch();

    $db->query("
        INSERT INTO notifications (type, message, is_read) 
        VALUES ('payment_cancelled', ?, 0)",
        [!empty($paymentInfo['table_no']) ? "Masa {$paymentInfo['table_no']}'ın ödemesi iptal edildi. Neden: {$cancelNote}" : "POS ödemesi iptal edildi. Neden: {$cancelNote}"]
    );

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 