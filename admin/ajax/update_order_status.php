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
        "SELECT id, status, order_type FROM orders WHERE id = ?",
        [$order_id]
    )->fetch();

    if (!$currentOrder) {
        throw new Exception('Sipariş bulunamadı');
    }

    $wasCancelled = $currentOrder['status'] === 'cancelled';
    $isDelivery = ($currentOrder['order_type'] ?? 'table') === 'delivery';

    // Adres siparişi iptal ediliyorsa stoğu geri al (tekrar iptal edilirse geri alma)
    $stockRestored = false;
    if ($status === 'cancelled' && !$wasCancelled && $isDelivery) {
        $stockRow = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'system_stock_tracking'")->fetch();
        if ($stockRow && $stockRow['setting_value'] == '1') {
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
            'stock_restored' => $stockRestored
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