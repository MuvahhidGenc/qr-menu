<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['product_id']) || !isset($_POST['change'])) {
        throw new Exception('Eksik parametre');
    }

    $product_id = (int)$_POST['product_id'];
    $change = (int)$_POST['change'];

    // Mevcut miktar
    $current_quantity = isset($_SESSION['cart'][$product_id]['quantity']) ? 
                       $_SESSION['cart'][$product_id]['quantity'] : 0;
    
    // Yeni miktar hesapla (minimum 1, maksimum 99)
    $new_quantity = $current_quantity + $change;
    
    // Miktar kontrolü
    if($new_quantity <= 0) {
        // Eğer miktar 0 veya daha az ise ürünü sepetten kaldır
        unset($_SESSION['cart'][$product_id]);
    } elseif($new_quantity > 99) {
        // Maksimum 99 adet
        $new_quantity = 99;
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        // Normal güncelleme
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    }

    // Sepet toplamını hesapla
    $db = new Database();
    $total = 0;
    foreach($_SESSION['cart'] as $pid => $item) {
        $product = $db->query("SELECT price FROM products WHERE id = ?", [$pid])->fetch();
        $total += $product['price'] * $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sepet güncellendi',
        'new_quantity' => $new_quantity,
        'total' => number_format($total, 2),
        'cart_count' => getCartCount()
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}