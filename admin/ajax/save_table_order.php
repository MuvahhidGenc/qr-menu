<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

$db = new Database();

try {
    $table_id = $_POST['table_id'];
    $notes = $_POST['notes'];
    $items = json_decode($_POST['items'], true);

    if(empty($items)) {
        throw new Exception('Sipariş boş olamaz!');
    }

    // Toplam tutarı hesapla
    $total = 0;
    foreach($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Siparişi kaydet
    $db->beginTransaction();

    $order_id = $db->query(
        "INSERT INTO orders (table_id, total_amount, notes, status) VALUES (?, ?, ?, 'new')",
        [$table_id, $total, $notes]
    )->lastInsertId();

    // Sipariş ürünlerini kaydet
    foreach($items as $product_id => $item) {
        $db->query(
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
            [$order_id, $product_id, $item['quantity'], $item['price']]
        );
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 