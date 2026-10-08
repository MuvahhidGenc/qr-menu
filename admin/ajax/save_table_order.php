<?php
// Hata yakalama fonksiyonunu en başta tanımla
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Tüm çıktıyı bufferlamaya başla
ob_start();

try {
    header('Content-Type: application/json; charset=utf-8');

    // Yetki kontrolü: bu uç sipariş oluşturur/günceller, tables.sales gerekir.
    // Önceden includes/session.php checkAuth() çağrılıyordu ancak checkAuth
    // yalnız $_SESSION['admin'] varlığını kontrol ediyor ve permission
    // düzeyinde hiçbir doğrulama yapmıyordu.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isLoggedIn() || !hasPermission('tables.sales')) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
        exit;
    }

    $db = new Database();

    // POST verilerini kontrol et
    if (!isset($_POST['table_id']) || !isset($_POST['items'])) {
        throw new Exception('Gerekli veriler eksik');
    }

    $table_id = (int)$_POST['table_id'];
    $notes = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500);

    if ($table_id <= 0) {
        throw new Exception('Geçersiz masa ID');
    }

    $raw_items = $_POST['items'];
    $items = is_string($raw_items) ? json_decode($raw_items, true) : $raw_items;

    if (!is_array($items) || $items === []) {
        throw new Exception('Sipariş boş olamaz!');
    }

    // Masa var mı ve hizmette mi?
    $table = $db->query("SELECT id, table_no, status FROM tables WHERE id = ? LIMIT 1", [$table_id])->fetch();
    if (!$table) {
        throw new Exception('Masa bulunamadı.');
    }
    if ($table['status'] !== 'active') {
        throw new Exception('Bu masa şu anda hizmet dışı.');
    }

    // --- KRİTİK: Fiyatları sunucuda doğrula -------------------------------
    // Önceden $item['price'] doğrudan istemciden alınıp order_items'a
    // yazılıyordu. Bu, kasiyere ödenecek tutarı istemci tarafında
    // değiştirmeye yarıyordu (negatif/1 TL ürün ile sınırsız ciro).
    // Fiyat artık yalnızca products tablosundan gelir.
    $cleanItems = [];
    foreach ($items as $key => $item) {
        if (!is_array($item)) continue;

        // İstemci ürünü anahtar olarak da gönderebiliyor
        $productId = (int)($item['product_id'] ?? $key);
        $quantity  = (int)($item['quantity'] ?? 0);
        if ($productId <= 0 || $quantity <= 0) continue;

        // products.status tinyint(1): 1 = aktif
        $pr = $db->query(
            "SELECT id, name, price FROM products WHERE id = ? AND status = 1 LIMIT 1",
            [$productId]
        )->fetch();
        if (!$pr) {
            throw new Exception("Ürün bulunamadı veya satışta değil (ID: {$productId}).");
        }

        $cleanItems[$productId] = [
            'product_id' => $productId,
            'quantity'   => $quantity,
            'price'      => (float)$pr['price'],   // sunucu fiyatı
            'name'       => $pr['name'],
        ];
    }
    if ($cleanItems === []) {
        throw new Exception('Geçerli ürün satırı bulunamadı.');
    }

    $db->beginTransaction();

    // Masanın aktif siparişini kontrol et
    $active_order = $db->query(
        "SELECT o.id
         FROM orders o
         WHERE o.table_id = ?
         AND o.status IN ('pending', 'preparing')
         AND o.payment_id IS NULL
         ORDER BY o.created_at DESC
         LIMIT 1",
        [$table_id]
    )->fetch();

    if ($active_order) {
        $order_id = (int)$active_order['id'];

        // Mevcut siparişe ürünleri ekle
        foreach ($cleanItems as $productId => $item) {
            $existing_item = $db->query(
                "SELECT id, quantity
                 FROM order_items
                 WHERE order_id = ?
                 AND product_id = ?",
                [$order_id, $productId]
            )->fetch();

            if ($existing_item) {
                // Varsa miktarı güncelle. Fiyat SADECE ilk eklemede yazılır;
                // sonradan ürün fiyatı değişmişse eski sipariş fiyatı korunur
                // (yeniden fiyatlandırma kasıtlı bir iş akışıdır, sessizce
                //  olmasın).
                $db->query(
                    "UPDATE order_items
                     SET quantity = quantity + ?
                     WHERE id = ?",
                    [$item['quantity'], $existing_item['id']]
                );
            } else {
                $db->query(
                    "INSERT INTO order_items (order_id, product_id, quantity, price)
                     VALUES (?, ?, ?, ?)",
                    [$order_id, $productId, $item['quantity'], $item['price']]
                );
            }
        }

        // Sipariş tutarını güncelle
        $db->query(
            "UPDATE orders
             SET total_amount = (
                 SELECT COALESCE(SUM(quantity * price), 0)
                 FROM order_items
                 WHERE order_id = ?
             ),
             notes = ?,
             status = 'pending'
             WHERE id = ?",
            [$order_id, $notes !== '' ? $notes : null, $order_id]
        );

        // Bildirim oluştur
        $notification_message = "Masa {$table['table_no']}'a yeni ürünler eklendi";
        $db->query(
            "INSERT INTO notifications (order_id, type, message)
             VALUES (?, 'order_updated', ?)",
            [$order_id, $notification_message]
        );

    } else {
        // Yeni sipariş oluştur
        $total = 0.0;
        foreach ($cleanItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $db->query(
            "INSERT INTO orders (table_id, total_amount, notes, status, created_at)
             VALUES (?, ?, ?, 'pending', NOW())",
            [$table_id, round($total, 2), $notes !== '' ? $notes : null]
        );

        $order_id = (int)$db->lastInsertId();

        foreach ($cleanItems as $item) {
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (?, ?, ?, ?)",
                [$order_id, $item['product_id'], $item['quantity'], $item['price']]
            );
        }
    }

    $db->commit();

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'message'  => $active_order ? 'Sipariş güncellendi' : 'Yeni sipariş oluşturuldu'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db)) {
        try { $db->rollBack(); } catch (Throwable $ignored) {}
    }

    error_log('Save Table Order Error: ' . $e->getMessage());

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    // Önceden ham hata mesajı istemciye dönüyordu (SQLSTATE dahil).
    // Artık log'a yazılıyor, istemciye güvenli mesaj gidiyor.
    http_response_code(400);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Sipariş kaydedilemedi.'
    ], JSON_UNESCAPED_UNICODE);
} finally {
    @ob_end_flush();
}