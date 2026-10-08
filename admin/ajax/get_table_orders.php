<?php
// GET ucu: oturum + yetki olmadan herhangi bir masa siparisini okuyabiliyor,
// ayrica yanitta kullanici izinlerini sızdırıyor ve hata mesajini ham basiyor.
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Bu uç masa siparişlerini ve kısmi ödeme toplamını döndürür: mutlaka
// tables.sales yetkisi gerekir. Önceden hiçbir kontrol yoktu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('tables.sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Geçersiz istek gövdesi.');
    }

    $tableId = (int)($data['table_id'] ?? 0);
    if ($tableId <= 0) {
        throw new Exception('Masa ID gerekli');
    }

    $db = new Database();

    // Masa gerçekten var ve aktif mi? Önceden hiç sorgulanmadı.
    $tbl = $db->query("SELECT id, table_no, status FROM tables WHERE id = ? LIMIT 1", [$tableId])->fetch();
    if (!$tbl) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Masa bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Siparişleri çek - sadece ödenmemiş ürünler
    $orders = $db->query(
        "SELECT o.id as order_id, oi.id as item_id,
                p.name as product_name, oi.quantity,
                oi.price, o.status,
                (oi.quantity * oi.price) as total
         FROM orders o
         JOIN order_items oi ON o.id = oi.order_id
         JOIN products p ON oi.product_id = p.id
         WHERE o.table_id = ?
         AND o.status NOT IN ('cancelled', 'completed')
         AND o.payment_id IS NULL
         AND oi.payment_id IS NULL
         ORDER BY o.created_at DESC",
        [$tableId]
    )->fetchAll(PDO::FETCH_ASSOC);

    // Tutar bazlı kısmi ödemelerin toplamını hesapla (sadece aktif siparişler varsa)
    $partialPaymentsTotal = 0.0;
    if (count($orders) > 0) {
        $partialPayments = $db->query(
            "SELECT COALESCE(SUM(paid_amount), 0) as total_paid
             FROM payments
             WHERE table_id = ?
             AND status = 'completed'
             AND payment_note IS NOT NULL
             AND payment_note LIKE '%\"type\":\"amount\"%'",
            [$tableId]
        )->fetch();
        $partialPaymentsTotal = (float)$partialPayments['total_paid'];
    }

    // Önceden yanıtta 'debug' bloğu dönüyordu; içinde kullanıcının TÜM izinleri
    // ve oturum durumu yer alıyordu. Kaldırıldı.
    echo json_encode([
        'success' => true,
        'orders'  => $orders,
        'partial_payments_total' => $partialPaymentsTotal,
        'table'   => ['id' => (int)$tbl['id'], 'table_no' => $tbl['table_no'], 'status' => $tbl['status']],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('get_table_orders hatası: ' . $e->getMessage());
    http_response_code(400);
    // Ham hata mesajı yerine sabit mesaj (bilgi sızıntısı önlemi)
    echo json_encode([
        'success' => false,
        'message' => 'Siparişler yüklenirken bir hata oluştu.'
    ], JSON_UNESCAPED_UNICODE);
}