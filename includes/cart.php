<?php
/**
 * Sepet yardımcı fonksiyonları
 *
 * NOT: Geriye uyumlu imza - $cartKey parametresi opsiyoneldir ve varsayılan
 * değeri 'cart'tır. Mevcut QR Menü çağrıları (addToCart($id, $qty) vb.)
 * hiçbir değişiklik olmadan çalışmaya devam eder.
 * Adres siparişi (Web Adrese Sipariş) modu 'delivery_cart' anahtarını kullanır.
 */

/**
 * Sepet başlatır
 *
 * @param string $cartKey Session anahtarı
 * @return void
 */
function initCart($cartKey = 'cart') {
    if (!isset($_SESSION[$cartKey]) || !is_array($_SESSION[$cartKey])) {
        $_SESSION[$cartKey] = [];
    }
}

/**
 * Sepete ürün ekler
 *
 * @param int    $product_id Ürün ID
 * @param int    $quantity   Miktar
 * @param string $cartKey    Session anahtarı
 * @return void
 * @throws Exception Geçersiz girdi durumunda
 */
function addToCart($product_id, $quantity = 1, $cartKey = 'cart') {
    initCart($cartKey);

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

    if (isset($_SESSION[$cartKey][$product_id])) {
        $new_quantity = $_SESSION[$cartKey][$product_id]['quantity'] + $quantity;
        if ($new_quantity > 99) {
            throw new Exception('Maksimum miktar 99 olabilir');
        }
        $_SESSION[$cartKey][$product_id]['quantity'] = $new_quantity;
    } else {
        $_SESSION[$cartKey][$product_id] = [
            'quantity' => $quantity
        ];
    }
}

/**
 * Sepetteki toplam ürün adedini döner
 *
 * @param string $cartKey Session anahtarı
 * @return int
 */
function getCartCount($cartKey = 'cart') {
    initCart($cartKey);
    $count = 0;

    if (is_array($_SESSION[$cartKey])) {
        foreach ($_SESSION[$cartKey] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $count += (int)$item['quantity'];
            }
        }
    }

    return max(0, $count); // Negatif değerlere karşı korunma
}

/**
 * Sepetten ürünü siler
 *
 * @param int    $product_id Ürün ID
 * @param string $cartKey    Session anahtarı
 * @return void
 */
function removeFromCart($product_id, $cartKey = 'cart') {
    initCart($cartKey);
    unset($_SESSION[$cartKey][(int)$product_id]);
}

/**
 * Sepetteki ürün miktarını değiştirir (0'a düşerse ürünü siler)
 *
 * @param int    $product_id Ürün ID
 * @param int    $change     Değişim (+1 / -1)
 * @param string $cartKey    Session anahtarı
 * @return int Yeni miktar
 */
function changeCartQuantity($product_id, $change, $cartKey = 'cart') {
    initCart($cartKey);
    $product_id = (int)$product_id;
    $change = (int)$change;

    $current = isset($_SESSION[$cartKey][$product_id]['quantity'])
        ? (int)$_SESSION[$cartKey][$product_id]['quantity']
        : 0;

    $new_quantity = $current + $change;

    if ($new_quantity <= 0) {
        unset($_SESSION[$cartKey][$product_id]);
        return 0;
    }

    if ($new_quantity > 99) {
        $new_quantity = 99;
    }

    $_SESSION[$cartKey][$product_id]['quantity'] = $new_quantity;
    return $new_quantity;
}

/**
 * Sepeti boşaltır
 *
 * @param string $cartKey Session anahtarı
 * @return void
 */
function clearCart($cartKey = 'cart') {
    $_SESSION[$cartKey] = [];
}
