<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// JSON yanıt için header
header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü - orders.manage yerine orders.status kontrolü yapılıyor
if (!hasPermission('orders.status')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
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

    $db = new Database();

    // Siparişi güncelle
    $result = $db->query(
        "UPDATE orders SET status = ? WHERE id = ?",
        [$status, $order_id]
    );

    if ($result) {
        // Bildirim ekle
        $notification_message = '';
        switch ($status) {
            case 'pending':
                $notification_message = "Sipariş #$order_id geri alındı";
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
            'success' => true,
            'message' => 'Sipariş durumu güncellendi',
            'status' => $status
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