<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mutfak yönetim yetkisi VEYA sipariş durumu değiştirme yetkisi kontrolü
if (!hasPermission('kitchen.manage') && !hasPermission('orders.status')) {
    echo json_encode(['success' => false, 'message' => 'Sipariş durumunu değiştirme yetkiniz bulunmamaktadır.']);
    exit;
}

try {
    // POST verilerini al
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;

    // Verileri kontrol et
    if (!$order_id || !$status) {
        throw new Exception('Geçersiz parametreler');
    }

    // Durum beyaz listesi (enum ile uyumlu, injection koruması)
    $allowedStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'on_the_way', 'delivered', 'completed', 'cancelled', 'partial_paid'];
    if (!in_array($status, $allowedStatuses, true)) {
        throw new Exception('Geçersiz sipariş durumu.');
    }

    $db = new Database();

    // Mevcut sipariş bilgisi (stok iadesi ve bildirim için)
    $currentOrder = $db->query(
        "SELECT id, status, order_type, payment_id FROM orders WHERE id = ?",
        [$order_id]
    )->fetch();

    if (!$currentOrder) {
        throw new Exception('Sipariş bulunamadı');
    }

    $wasCancelled = $currentOrder['status'] === 'cancelled';
    $wasDelivered = in_array($currentOrder['status'], ['delivered', 'completed'], true);
    $isDelivery = ($currentOrder['order_type'] ?? 'table') === 'delivery';

    $stockTrackingRow = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'system_stock_tracking'")->fetch();
    $stockTrackingOn = $stockTrackingRow && $stockTrackingRow['setting_value'] == '1';

    // TESLİM: masa siparişinde ürünler satılmış sayılır → stok düş.
    // (Adres siparişinde stok oluşurken düşülmüştü; tekrar düşülmez.)
    // completed/cancelled/delivered durumundan gelen tekrarlar çift düşürmez.
    $stockDeducted = false;
    if ($status === 'delivered' && !$wasCancelled && !$wasDelivered && !$isDelivery && $stockTrackingOn) {
        $db->beginTransaction();
        try {
            dvDeductTableStock($db, (int)$order_id, 'Masa Siparişi #' . $order_id . ' teslim - stok çıkışı');
            $db->commit();
            $stockDeducted = true;
        } catch (Exception $ex) {
            $db->rollBack();
            error_log('Stok düşüş hatası (order #' . $order_id . '): ' . $ex->getMessage());
            throw new Exception('Stok yetersiz olduğu için teslim alınamadı: ' . $ex->getMessage());
        }
    }

    // Adres siparişi iptal ediliyorsa stoğu geri al (tekrar iptal edilirse geri alma)
    $stockRestored = false;
    if ($status === 'cancelled' && !$wasCancelled && $isDelivery) {
        if ($stockTrackingOn) {
            $db->beginTransaction();
            try {
                restoreDeliveryStock($db, (int)$order_id);
                $db->commit();
                $stockRestored = true;
            } catch (Exception $ex) {
                $db->rollBack();
                error_log('Stok iadesi hatası (order #' . $order_id . '): ' . $ex->getMessage());
            }
        }
    }

    // Masa siparişi teslimden SONRA iptal edilirse düşülen stoğu geri al.
    // (Teslim öncesi iptalde stok hiç düşülmemişti; iade gerekmez.)
    if ($status === 'cancelled' && !$wasCancelled && !$isDelivery
        && $currentOrder['status'] === 'delivered' && $stockTrackingOn) {
        $db->beginTransaction();
        try {
            dvRestoreTableStock($db, (int)$order_id, 'Masa Siparişi #' . $order_id . ' iptal - stok iadesi');
            $db->commit();
            $stockRestored = true;
        } catch (Exception $ex) {
            $db->rollBack();
            error_log('Stok iadesi hatası (order #' . $order_id . '): ' . $ex->getMessage());
        }
    }

    // Siparişi güncelle
    if ($status === 'cancelled') {
        // İptal edildiğinde cancelled_at'i güncelle
        $result = $db->query(
            "UPDATE orders 
             SET status = ?, cancelled_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            ['cancelled', $order_id]
        );
    } else if ($status === 'completed') {
        // Tamamlandığında completed_at'i güncelle
        $result = $db->query(
            "UPDATE orders 
             SET status = ?, completed_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            ['completed', $order_id]
        );
    } else {
        // Diğer durumlarda sadece status'ü güncelle
        $result = $db->query(
            "UPDATE orders 
             SET status = ? 
             WHERE id = ?",
            [$status, $order_id]
        );
    }

    // İPTALDE ÖDEME: siparişin bağlı olduğu ödeme, başka aktif siparişi
    // kalmadıysa iptal edilir (alınmış ödemelerde görünmez, ciroya girmez).
    // Kardeş siparişler duruyorsa ödeme korunur (kısmi iptal).
    $paymentCancelled = false;
    if ($status === 'cancelled' && !$wasCancelled && !empty($currentOrder['payment_id'])) {
        $paymentId = (int)$currentOrder['payment_id'];
        $sibling = $db->query(
            "SELECT id FROM orders WHERE payment_id = ? AND id <> ? AND status <> 'cancelled' LIMIT 1",
            [$paymentId, $order_id]
        )->fetch();
        if (!$sibling) {
            $db->beginTransaction();
            try {
                $db->query(
                    "UPDATE payments SET status = 'cancelled',
                        payment_note = CONCAT(COALESCE(payment_note, ''), '\nSipariş #', ?, ' iptali nedeniyle otomatik iptal')
                     WHERE id = ? AND status = 'completed'",
                    [$order_id, $paymentId]
                );
                $db->commit();
                $paymentCancelled = true;
            } catch (Exception $ex) {
                $db->rollBack();
                error_log('Ödeme iptal hatası (payment #' . $paymentId . '): ' . $ex->getMessage());
            }
        }
    }

    if ($result) {
        // Bildirim ekle
        $notification_message = '';
        switch ($status) {
            case 'pending':
                $notification_message = "Sipariş #$order_id geri alındı";
                break;
            case 'confirmed':
                $notification_message = "Sipariş #$order_id onaylandı";
                break;
            case 'cancelled':
                $notification_message = "Sipariş #$order_id iptal edildi";
                break;
            case 'preparing':
                $notification_message = "Sipariş #$order_id hazırlanıyor";
                break;
            case 'ready':
                $notification_message = "Sipariş #$order_id hazır";
                break;
            case 'on_the_way':
                $notification_message = "Sipariş #$order_id yola çıktı";
                break;
            case 'delivered':
                $notification_message = "Sipariş #$order_id teslim edildi";
                break;
        }

        if ($notification_message) {
            $db->query(
                "INSERT INTO notifications (order_id, type, message) VALUES (?, 'order_status', ?)",
                [$order_id, $notification_message]
            );
        }

        echo json_encode([
            'success'        => true,
            'message'        => 'Sipariş durumu güncellendi',
            'status'         => $status,
            'stock_restored' => $stockRestored,
            'stock_deducted' => $stockDeducted,
            'payment_cancelled' => $paymentCancelled
        ]);
    } else {
        throw new Exception('Sipariş güncellenirken bir hata oluştu');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}