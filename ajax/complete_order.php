<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $db->beginTransaction();

    // Session'dan masa ID'sini al
    $table_id = $_SESSION['table_id'] ?? null;
    if (!$table_id) {
        throw new Exception('Masa bilgisi bulunamadı!');
    }

    // Sepetteki ürünleri al
    $cart_items = $_SESSION['cart'] ?? [];
    if (empty($cart_items)) {
        throw new Exception('Sepetiniz boş!');
    }

    // Masanın mevcut aktif ve ÖDENMEMİŞ siparişini kontrol et
    $existingOrder = $db->query(
        "SELECT id, total_amount, status FROM orders 
        WHERE table_id = ? 
        AND status = 'pending'  /* Sadece bekleyen siparişleri kontrol et */
        ORDER BY created_at DESC 
        LIMIT 1",
        [$table_id]
    )->fetch();

    // Toplam tutarı hesapla ve ürün bilgilerini güncelle
    $total = 0;
    foreach ($cart_items as $key => $item) {
        // Ürün bilgilerini veritabanından al
        $product = $db->query(
            "SELECT id, price FROM products WHERE id = ?",
            [$key]
        )->fetch();
        
        if (!$product) {
            throw new Exception('Ürün bulunamadı!');
        }

        // Sepetteki ürün bilgilerini güncelle
        $cart_items[$key]['product_id'] = $product['id'];
        $cart_items[$key]['price'] = $product['price'];
        $total += $product['price'] * $item['quantity'];
    }

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    $orderNote = $input['note'] ?? null;

    // Debug için
    error_log('Received JSON input: ' . print_r($input, true));
    error_log('Received order note: ' . print_r($orderNote, true));

    if ($existingOrder && $existingOrder['status'] == 'pending') {
        // Mevcut ödenmemiş siparişe ekleme yap
        $order_id = $existingOrder['id'];
        
        // Yeni ürünleri ekle
        foreach ($cart_items as $item) {
            // Ürün zaten var mı kontrol et
            $existingItem = $db->query(
                "SELECT id, quantity FROM order_items 
                WHERE order_id = ? AND product_id = ?",
                [$order_id, $item['product_id']]
            )->fetch();

            if ($existingItem) {
                // Varsa miktarı güncelle
                $db->query(
                    "UPDATE order_items 
                    SET quantity = quantity + ? 
                    WHERE id = ?",
                    [$item['quantity'], $existingItem['id']]
                );
            } else {
                // Yoksa yeni ekle
                $db->query(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)",
                    [$order_id, $item['product_id'], $item['quantity'], $item['price']]
                );
            }
        }

        // Not varsa, mevcut nota ekle
        if (!empty($orderNote)) {
            $currentNote = $db->query(
                "SELECT note FROM orders WHERE id = ?", 
                [$order_id]
            )->fetchColumn();

            $newNote = empty($currentNote) 
                ? date('H:i') . ": " . $orderNote 
                : $currentNote . "\n---\n" . date('H:i') . ": " . $orderNote;

            $db->query(
                "UPDATE orders SET note = ? WHERE id = ?",
                [$newNote, $order_id]
            );

            // Debug için
            error_log('Updated note for order ID: ' . $order_id);
            error_log('New note content: ' . $newNote);
        }

        // Toplam tutarı güncelle
        $db->query(
            "UPDATE orders 
            SET total_amount = (
                SELECT COALESCE(SUM(quantity * price), 0) 
                FROM order_items 
                WHERE order_id = ?
            )
            WHERE id = ?",
            [$order_id, $order_id]
        );
    } else {
        // Yeni sipariş oluştur
        $initialNote = !empty($orderNote) ? date('H:i') . ": " . $orderNote : null;
        
        $db->query(
            "INSERT INTO orders (table_id, total_amount, status, note, created_at) 
            VALUES (?, ?, 'pending', ?, NOW())",
            [$table_id, $total, $initialNote]
        );
        
        $order_id = $db->lastInsertId();
        error_log('Created new order with ID: ' . $order_id . ' and note: ' . $initialNote);
        
        // Sipariş detaylarını kaydet
        foreach ($cart_items as $key => $item) {
            $product = $db->query(
                "SELECT id, price FROM products WHERE id = ?",
                [$key]
            )->fetch();

            if (!$product) {
                throw new Exception('Ürün bulunamadı!');
            }

            // Ürün detaylarını kaydet (name sütununu kaldırdık)
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)",
                [
                    $order_id,
                    $product['id'],
                    $item['quantity'],
                    $product['price']
                ]
            );
        }

        // Toplam tutarı güncelle
        $db->query(
            "UPDATE orders 
            SET total_amount = (
                SELECT COALESCE(SUM(quantity * price), 0) 
                FROM order_items 
                WHERE order_id = ?
            )
            WHERE id = ?",
            [$order_id, $order_id]
        );
    }

    // Bildirim oluştur
    $table_info = $db->query(
        "SELECT table_no FROM tables WHERE id = ?", 
        [$table_id]
    )->fetch();

    $notification_message = $existingOrder && $existingOrder['status'] == 'pending'
        ? "Masa {$table_info['table_no']}'a yeni ürünler eklendi"
        : "Masa {$table_info['table_no']}'dan yeni sipariş geldi!";

    $db->query(
        "INSERT INTO notifications (order_id, type, message) 
        VALUES (?, ?, ?)", 
        [$order_id, ($existingOrder && $existingOrder['status'] == 'pending') ? 'order_updated' : 'new_order', $notification_message]
    );

    // Sepeti temizle
    $_SESSION['cart'] = [];

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => ($existingOrder && $existingOrder['status'] == 'pending') ? 'Siparişiniz mevcut siparişe eklendi' : 'Siparişiniz başarıyla oluşturuldu',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}