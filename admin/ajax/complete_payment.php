<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

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

    $db->beginTransaction();

    // Masadaki aktif siparişleri bul
    $orders = $db->query(
        "SELECT id, total_amount FROM orders 
         WHERE table_id = ? AND payment_id IS NULL 
         AND status != 'cancelled'",
        [$table_id]
    )->fetchAll();

    // Ödeme kaydı oluştur
    $db->query(
        "INSERT INTO payments (
            table_id, 
            payment_method, 
            total_amount,
            paid_amount, 
            created_at
        ) VALUES (?, ?, ?, ?, NOW())",
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
            WHERE id = ?",
            [$payment_id, $order['id']]
        );
    }

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