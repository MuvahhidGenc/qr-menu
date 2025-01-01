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

        // Toplam tutarı güncelle
        $new_total = $existingOrder['total_amount'] + $total;
        $db->query(
            "UPDATE orders SET total_amount = ? WHERE id = ?",
            [$new_total, $order_id]
        );

    } else {
        // Yeni sipariş oluştur
        $db->query(
            "INSERT INTO orders (table_id, total_amount, status) 
            VALUES (?, ?, 'pending')",
            [$table_id, $total]
        );
        
        $order_id = $db->lastInsertId();

        // Sipariş detaylarını kaydet
        foreach ($cart_items as $item) {
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)",
                [$order_id, $item['product_id'], $item['quantity'], $item['price']]
            );
        }
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