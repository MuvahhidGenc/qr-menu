<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['product_id'])) {
        throw new Exception('Ürün ID gerekli');
    }

    $product_id = (int)$_POST['product_id'];
    
    // Sepetten kaldır
    if(isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ürün sepetten kaldırıldı',
        'cart_count' => getCartCount()
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}