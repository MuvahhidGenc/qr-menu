<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (!isset($data['table_id'])) {
        throw new Exception('Masa ID gerekli');
    }

    $table_id = (int)$data['table_id'];

    $db->beginTransaction();

    try {
        // Masadaki ödenecek siparişleri al
        $orders = $db->query(
            "SELECT id, total_amount FROM orders 
             WHERE table_id = ? AND payment_id IS NULL",
            [$table_id]
        )->fetchAll();

        if (empty($orders)) {
            throw new Exception('Ödenecek sipariş bulunamadı');
        }

        // Toplam tutarı hesapla
        $total_amount = 0;
        $order_ids = [];
        foreach ($orders as $order) {
            $total_amount += $order['total_amount'];
            $order_ids[] = $order['id'];
        }

        // Ödeme kaydı oluştur
        $db->query(
            "INSERT INTO payments (table_id, total_amount, created_at) 
             VALUES (?, ?, NOW())",
            [$table_id, $total_amount]
        );
        $payment_id = $db->lastInsertId();

        // Siparişleri güncelle
        $order_ids_str = implode(',', $order_ids);
        $db->query(
            "UPDATE orders 
             SET payment_id = ?, 
                 status = 'completed',
                 completed_at = NOW()
             WHERE id IN ($order_ids_str)",
            [$payment_id]
        );

        // Masayı boşalt (sadece status'ü güncelle)
        $db->query(
            "UPDATE tables 
             SET status = 'available'
             WHERE id = ?",
            [$table_id]
        );

        $db->commit();

        echo json_encode([
            'success' => true,
            'payment_id' => $payment_id,
            'total_amount' => $total_amount,
            'message' => 'Ödeme başarıyla tamamlandı'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Ödeme Hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 