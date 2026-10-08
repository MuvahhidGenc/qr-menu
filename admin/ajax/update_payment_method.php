<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Ödeme düzenleme yetkisi: ödeme iptal yetkisi olanlar yöntemi de düzeltebilir.
if (!isLoggedIn() || !hasPermission('payments.cancel')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    // CLI/test ve form-post uyumluluğu için $_POST yedeği
    $paymentId = isset($data['payment_id']) ? (int)$data['payment_id'] : (int)($_POST['payment_id'] ?? 0);
    $orderId = isset($data['order_id']) ? (int)$data['order_id'] : (int)($_POST['order_id'] ?? 0);
    $method = $data['payment_method'] ?? $_POST['payment_method'] ?? '';

    $db = new Database();

    // ---- Adres siparişi yöntemi (orders.payment_method: cash|card|online) ----
    if ($orderId > 0) {
        if (!in_array($method, ['cash', 'card', 'online'], true)) {
            throw new Exception('Geçersiz ödeme yöntemi');
        }
        $order = $db->query(
            "SELECT id, order_type, status, payment_method FROM orders WHERE id = ?",
            [$orderId]
        )->fetch();
        if (!$order) {
            throw new Exception('Sipariş bulunamadı');
        }
        if (($order['order_type'] ?? '') !== 'delivery') {
            throw new Exception('Yalnızca adres siparişinin yöntemi buradan değişir');
        }
        if ($order['status'] === 'cancelled') {
            throw new Exception('İptal edilmiş siparişin yöntemi değiştirilemez');
        }
        if (($order['payment_method'] ?? '') === $method) {
            echo json_encode(['success' => true, 'message' => 'Değişiklik yok', 'payment_method' => $method]);
            return;
        }
        $db->query(
            "UPDATE orders SET payment_method = ? WHERE id = ?",
            [$method, $orderId]
        );
        $db->query(
            "INSERT INTO notifications (order_id, type, message, is_read)
             VALUES (?, 'order_payment_method_updated', ?, 0)",
            [$orderId, "Adres Siparişi #{$orderId} ödeme yöntemi {$order['payment_method']} → {$method} olarak düzeltildi."]
        );
        echo json_encode(['success' => true, 'message' => 'Ödeme yöntemi güncellendi', 'payment_method' => $method]);
        return;
    }

    if ($paymentId <= 0) {
        throw new Exception('Geçersiz ödeme ID');
    }

    // payments.payment_method enum('cash','pos') — sessiz bozulmayı önlemek
    // için istemci değeri beyaz listeye zorlanır.
    if (!in_array($method, ['cash', 'pos'], true)) {
        throw new Exception('Geçersiz ödeme yöntemi');
    }

    $payment = $db->query(
        "SELECT id, payment_method, status FROM payments WHERE id = ?",
        [$paymentId]
    )->fetch();
    if (!$payment) {
        throw new Exception('Ödeme bulunamadı');
    }
    if ($payment['status'] === 'cancelled') {
        throw new Exception('İptal edilmiş ödemenin yöntemi değiştirilemez');
    }
    if ($payment['payment_method'] === $method) {
        echo json_encode(['success' => true, 'message' => 'Değişiklik yok', 'payment_method' => $method]);
        return;
    }

    $db->query(
        "UPDATE payments
         SET payment_method = ?,
             payment_note = CONCAT(COALESCE(payment_note, ''), '\nÖdeme yöntemi düzeltildi (', ?, ' → ', ?, ')')
         WHERE id = ?",
        [$method, $payment['payment_method'], $method, $paymentId]
    );

    $db->query(
        "INSERT INTO notifications (type, message, is_read)
         VALUES ('payment_method_updated', ?, 0)",
        ["Ödeme #{$paymentId} yöntemi {$payment['payment_method']} → {$method} olarak düzeltildi."]
    );

    echo json_encode(['success' => true, 'message' => 'Ödeme yöntemi güncellendi', 'payment_method' => $method]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
