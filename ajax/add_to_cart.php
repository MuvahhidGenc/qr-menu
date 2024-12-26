<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

error_log('POST Data: ' . print_r($_POST, true));
error_log('Session Before: ' . print_r($_SESSION, true));

try {
    $db = new Database();

    if(!isset($_POST['product_id'])) {
        throw new Exception('Ürün ID gerekli');
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Ürün kontrolü
    $product = $db->query("SELECT * FROM products WHERE id = ?", [$product_id])->fetch();
    if(!$product) {
        throw new Exception('Ürün bulunamadı');
    }

    // Sepete ekle
    addToCart($product_id, $quantity);

    // Başarılı yanıt döndür
    echo json_encode([
        'success' => true,
        'message' => 'Ürün sepete eklendi',
        'cart_count' => getCartCount()
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

error_log('Session After: ' . print_r($_SESSION, true));