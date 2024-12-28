<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

$db = new Database();

try {
    $table_id = $_POST['table_id'];
    $payment_method = $_POST['payment_method'];
    $paid_amount = $_POST['paid_amount'];
    $payment_note = $_POST['payment_note'];
    $orders = json_decode($_POST['orders'], true);

    $db->beginTransaction();

    // Ödeme kaydı oluştur
    $payment_id = $db->query(
        "INSERT INTO payments (
            table_id, 
            payment_method, 
            total_amount,
            paid_amount, 
            payment_note,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())",
        [
            $table_id,
            $payment_method,
            array_sum(array_column($orders, 'total_amount')),
            $paid_amount,
            $payment_note
        ]
    )->lastInsertId();

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
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 