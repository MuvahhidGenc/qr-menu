<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Bu uç şu anda hiçbir istemci tarafından çağrılmıyor (ölü kod), ancak
// public `ajax/` klasöründe durduğu için adresi bilen biri doğrudan
// isteği yine de gönderebilir. Oturum + yetki kontrolü bu yüzden zorunlu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('tables.sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

try {
    $db = new Database();

    $data = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Geçersiz istek gövdesi.');
    }

    $tableId = (int)($data['table_id'] ?? 0);
    if ($tableId <= 0) {
        throw new Exception('Geçersiz masa ID.');
    }

    $items = $data['items'] ?? null;
    if (!is_array($items) || $items === []) {
        throw new Exception('Sipariş en az bir ürün içermelidir.');
    }

    // Ürün satırlarını sunucuda doğrula: fiyat istemciden gelmez,
    // ürünün kendi fiyatı ve aktiflik kontrolü yapılır.
    $cleanItems = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $productId = (int)($item['product_id'] ?? 0);
        $quantity  = (int)($item['quantity'] ?? 0);
        if ($productId <= 0 || $quantity <= 0) continue;

        // products.status tinyint(1): 1 = aktif, 0 = pasif
        $pr = $db->query(
            "SELECT id, name, price, status FROM products WHERE id = ? AND status = 1 LIMIT 1",
            [$productId]
        )->fetch();
        if (!$pr) {
            throw new Exception("Ürün bulunamadı veya satışta değil (ID: {$productId}).");
        }
        $cleanItems[] = [
            'product_id' => $productId,
            'quantity'   => $quantity,
            'price'      => (float)$pr['price'],   // sunucu fiyatı
            'name'       => $pr['name'],
        ];
    }
    if ($cleanItems === []) {
        throw new Exception('Geçerli ürün satırı bulunamadı.');
    }

    // Masanın var olduğunu doğrula
    $tbl = $db->query("SELECT id, table_no, status FROM tables WHERE id = ? LIMIT 1", [$tableId])->fetch();
    if (!$tbl) {
        throw new Exception('Masa bulunamadı.');
    }
    if ($tbl['status'] !== 'active') {
        throw new Exception('Bu masa şu anda hizmet dışı.');
    }

    $db->beginTransaction();
    try {
        // orders.status ENUM değerleri: pending, preparing, ready, delivered,
        // completed, cancelled, partial_paid, confirmed, on_the_way.
        // 'new' bu listede YOK; önceki sürüm 'new' yazıyordu ve strict mod
        // kapalı olduğu için sessizce '' (boş string) olarak kaydediliyordu.
        $db->query(
            "INSERT INTO orders (table_id, status, created_at) VALUES (?, 'pending', NOW())",
            [$tableId]
        );
        $orderId = $db->lastInsertId();

        foreach ($cleanItems as $ci) {
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$orderId, $ci['product_id'], $ci['quantity'], $ci['price']]
            );
        }

        // Toplam, kaydedilmiş order_items fiyatlarından hesaplanır.
        $db->query(
            "UPDATE orders SET total_amount = (
                SELECT COALESCE(SUM(oi.quantity * oi.price), 0)
                FROM order_items oi
                WHERE oi.order_id = ?
            ) WHERE id = ?",
            [$orderId, $orderId]
        );

        $db->commit();

        echo json_encode([
            'success'   => true,
            'order_id'  => (int)$orderId,
            'message'   => 'Sipariş başarıyla kaydedildi',
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $db->rollBack();
        error_log('save_order SQL hatası: ' . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    // Önceden yanıt gövdesinde DESCRIBE orders çıktısı ve ham SQL hata
    // mesajı dönüyordu; bu bilgi sızıntısıdır. Artık sadece güvenli mesaj.
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
