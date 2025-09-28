<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $tableId = $input['table_id'] ?? null;
    $paymentMethod = $input['payment_method'] ?? null;
    $totalAmount = $input['total_amount'] ?? 0; // Frontend'den gelen gerçek ödenen tutar
    $subtotal = $input['subtotal'] ?? $totalAmount;
    $discountType = $input['discount_type'] ?? null;
    $discountValue = $input['discount_value'] ?? 0;
    $discountAmount = $input['discount_amount'] ?? 0;
    
    // PAID AMOUNT HESAPLAMA DÜZELTİLDİ!
    // Frontend'den gelen total_amount zaten doğru ödenen tutarı içeriyor (subtotal - discount)
    $paidAmount = $totalAmount;
    
    // Paid amount hesaplama tamamlandı
    
    if (!$tableId || !$paymentMethod) {
        throw new Exception('Gerekli alanlar eksik');
    }

    // Masanın durumunu kontrol et
    $tableCheck = $db->query(
        "SELECT status FROM tables WHERE id = ?",
        [$tableId]
    )->fetch();

    $db->beginTransaction();

    try {
        // Masadaki aktif siparişleri bul
        $orders = $db->query(
            "SELECT id, total_amount FROM orders 
             WHERE table_id = ? 
             AND status IN ('pending', 'preparing', 'ready', 'delivered')
             AND payment_id IS NULL",
            [$tableId]
        )->fetchAll();

        // Ödeme kaydı oluştur - paid_amount eklendi
        $db->query(
            "INSERT INTO payments (
                table_id, 
                payment_method, 
                total_amount,
                subtotal,
                paid_amount,
                discount_type,
                discount_value, 
                discount_amount,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())",
            [
                $tableId,
                $paymentMethod,
                $totalAmount,
                $subtotal,
                $paidAmount,
                $discountType,
                $discountValue,
                $discountAmount
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
            [$tableId]
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