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

    // Masanın durumunu kontrol et
    $tableCheck = $db->query(
        "SELECT status FROM tables WHERE id = ?",
        [$table_id]
    )->fetch();

    $db->beginTransaction();

    try {
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
                WHERE id = ?",
                [$payment_id, $order['id']]
            );
        }

        // Masayı boşalt - updated_at sütunu olmadığı için kaldırıldı
        $db->query(
            "UPDATE tables 
             SET status = 'empty'
             WHERE id = ?",
            [$table_id]
        );

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Ödeme başarıyla tamamlandı']);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    if (isset($db)) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackError) {
            // Rollback hatası görmezden gelinebilir
        }
    }
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}