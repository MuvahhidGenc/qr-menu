<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/db.php';

try {
    $db = new Database();
    $db->beginTransaction();

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $table_id = intval($_POST['table_id'] ?? 0);

    if (!$id || !$status) {
        throw new Exception('Geçersiz parametreler');
    }

    // Rezervasyon durumunu güncelle
    $updateQuery = "UPDATE reservations 
                   SET status = ?, 
                       table_id = ? 
                   WHERE id = ?";
    $db->query($updateQuery, [$status, $table_id, $id]);

    // Eğer rezervasyon onaylandıysa, siparişleri orders tablosuna ekle
    if ($status === 'confirmed') {
        // Rezervasyon siparişlerini kontrol et
        $reservationOrders = $db->query(
            "SELECT * FROM reservation_orders WHERE reservation_id = ?", 
            [$id]
        )->fetchAll();

        if (!empty($reservationOrders)) {
            // Toplam tutarı hesapla
            $totalAmount = array_reduce($reservationOrders, function($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            // Ana sipariş kaydını oluştur
            $orderQuery = "INSERT INTO orders (
                reservation_id,
                table_id,
                status,
                total_amount,
                order_code
            ) VALUES (?, ?, 'pending', ?, ?)";

            $orderCode = strtoupper(substr(uniqid(), -6));
            $db->query($orderQuery, [$id, $table_id, $totalAmount, $orderCode]);
            $orderId = $db->lastInsertId();

            // Sipariş detaylarını ekle
            foreach ($reservationOrders as $item) {
                $itemQuery = "INSERT INTO order_items (
                    order_id,
                    product_id,
                    quantity,
                    price
                ) VALUES (?, ?, ?, ?)";

                $db->query($itemQuery, [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Bildirim oluştur
            $notificationQuery = "INSERT INTO notifications (
                type,
                message,
                order_id
            ) SELECT 
                'new_order',
                CONCAT('Masa ', t.table_no, ' için onaylanan rezervasyondan yeni sipariş'),
                o.id
            FROM orders o
            JOIN tables t ON t.id = o.table_id
            WHERE o.id = ?
            LIMIT 1";

            $db->query($notificationQuery, [$orderId]);
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Rezervasyon durumu güncellendi'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Reservation Update Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 