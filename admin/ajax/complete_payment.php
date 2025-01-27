<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $table_id = $input['table_id'] ?? null;
    $payment_method = $input['payment_method'] ?? null;
    $total_amount = $input['total_amount'] ?? 0;
    
    if (!$table_id || !$payment_method) {
        throw new Exception('Gerekli alanlar eksik');
    }

    // Masanın durumunu ve mevcut siparişleri detaylı kontrol et
    $tableCheck = $db->query(
        "SELECT 
            t.status,
            (SELECT COUNT(*) FROM orders o 
             WHERE o.table_id = t.id 
             AND o.status IN ('pending', 'preparing', 'ready', 'delivered')
             AND o.payment_id IS NULL) as unpaid_orders,
            (SELECT COUNT(*) FROM orders o2 
             WHERE o2.table_id = t.id 
             AND o2.status = 'completed'
             AND o2.payment_id IS NOT NULL) as paid_orders
         FROM tables t 
         WHERE t.id = ?",
        [$table_id]
    )->fetch();

    // Masada ödenmemiş sipariş kontrolü
    if ($tableCheck['unpaid_orders'] > 0) {
        throw new Exception('Bu masada ödenmemiş siparişler bulunuyor. Lütfen önce mevcut siparişlerin ödemesini yapın.');
    }

    // Masada ödenmiş sipariş kontrolü
    if ($tableCheck['paid_orders'] > 0) {
        throw new Exception('Bu masada henüz tamamlanmamış ödemeli sipariş bulunuyor. Lütfen önce mevcut siparişi kapatın.');
    }

    $db->beginTransaction();

    // Masadaki aktif siparişleri bul
    $orders = $db->query(
        "SELECT id, total_amount FROM orders 
         WHERE table_id = ? 
         AND status IN ('pending', 'preparing', 'ready', 'delivered')
         AND payment_id IS NULL",
        [$table_id]
    )->fetchAll();

    // Ödeme kaydı oluştur
    $db->query(
        "INSERT INTO payments (
            table_id, 
            payment_method, 
            total_amount,
            paid_amount,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, 'completed', NOW())",
        [
            $table_id,
            $payment_method,
            $total_amount,
            $total_amount
        ]
    );
    
    $payment_id = $db->lastInsertId();

    // Siparişleri güncelle
    foreach($orders as $order) {
        $db->query(
            "UPDATE orders SET 
                status = 'completed',
                payment_id = ?,
                completed_at = NOW()
            WHERE id = ? 
            AND status IN ('pending', 'preparing', 'ready', 'delivered')
            AND payment_id IS NULL",
            [$payment_id, $order['id']]
        );
    }

    // Masayı boşalt
    $db->query(
        "UPDATE tables 
         SET status = 'empty',
             updated_at = NOW()
         WHERE id = ? 
         AND NOT EXISTS (
             SELECT 1 FROM orders 
             WHERE table_id = ? 
             AND status IN ('pending', 'preparing', 'ready', 'delivered')
         )",
        [$table_id, $table_id]
    );

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}