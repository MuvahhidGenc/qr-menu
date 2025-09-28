<?php
function initCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addToCart($product_id, $quantity = 1) {
    initCart();
    
    // Input validation
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    // Güvenlik kontrolleri
    if ($product_id <= 0) {
        throw new Exception('Geçersiz ürün ID');
    }
    
    if ($quantity <= 0 || $quantity > 99) {
        throw new Exception('Geçersiz miktar (1-99 arası olmalı)');
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
        if ($new_quantity > 99) {
            throw new Exception('Maksimum miktar 99 olabilir');
        }
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => $quantity
        ];
    }
}

function getCartCount() {
    initCart();
    $count = 0;
    
    if (is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $count += (int)$item['quantity'];
            }
        }
    }
    
    return max(0, $count); // Negatif değerlere karşı korunma
}