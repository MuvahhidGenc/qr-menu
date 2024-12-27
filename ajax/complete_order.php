<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

try {
    $db = new Database();

    // Sepet boş mu kontrol et
    if(empty($_SESSION['cart'])) {
        throw new Exception('Sepetiniz boş!');
    }

    // Sipariş toplam tutarını hesapla
    $total = 0;
    $order_items = [];

    foreach($_SESSION['cart'] as $product_id => $item) {
        $product = $db->query("SELECT * FROM products WHERE id = ?", [$product_id])->fetch();
        if($product) {
            $subtotal = $product['price'] * $item['quantity'];
            $total += $subtotal;
            $order_items[] = [
                'product_id' => $product_id,
                'quantity' => $item['quantity'],
                'price' => $product['price']
            ];
        }
    }

    // Siparişi kaydet
        // Sipariş başlangıcında table_id kontrolü yapalım
        $table_id = isset($_SESSION['table_id']) ? $_SESSION['table_id'] : 1; // Varsayılan olarak Masa 1

        // Masanın var olup olmadığını kontrol edelim
        $table_check = $db->query("SELECT id FROM tables WHERE id = ?", [$table_id])->fetch();
        if(!$table_check) {
            throw new Exception('Geçersiz masa!');
        }

        // Siparişi kaydet
        $db->query("INSERT INTO orders (table_id, total_amount, status) VALUES (?, ?, 'pending')", 
                [$table_id, $total]);

    
    $order_id = $db->lastInsertId();

    // Sipariş detaylarını kaydet
    foreach($order_items as $item) {
        $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                   VALUES (?, ?, ?, ?)",
                   [$order_id, $item['product_id'], $item['quantity'], $item['price']]);
    }
    // Bildirim oluştur
        $table_info = $db->query("SELECT table_no FROM tables WHERE id = ?", [$table_id])->fetch();
        $table_no = $table_info['table_no'];

        $notification_message = "Masa {$table_no}'dan yeni sipariş geldi!";
        $db->query("INSERT INTO notifications (order_id, type, message) 
                VALUES (?, 'new_order', ?)", 
                [$order_id, $notification_message]);

    // Sepeti temizle
    $_SESSION['cart'] = [];

    echo json_encode([
        'success' => true,
        'message' => 'Siparişiniz alındı',
        'order_id' => $order_id
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}