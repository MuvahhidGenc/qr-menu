<?php
/**
 * Web Adrese Sipariş - Sepet İşlemleri
 * action: add | set_qty | remove | summary
 *
 * Fiyatlar burada hesaplanmaz; yalnızca session sepeti güncellenir.
 * Gerçek fiyat/total doğrulaması create_order.php tarafından yapılır.
 */
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();

    // Özellik kapalıyken sepet manipülasyonuna izin verme
    if (!isDeliveryEnabled($db)) {
        throw new Exception('Web üzerinden sipariş alma hizmeti kapalı.');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_REQUEST['action'] ?? 'summary';
    $productId = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

    $limit = new RateLimit($db);
    $limit->setWindow(300);
    $limit->setMaxAttempts(120);
    if (!$limit->check('delivery_cart')) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Çok fazla işlem yaptınız, lütfen biraz bekleyin.',
        ]);
        exit;
    }

    switch ($action) {

        // ---------------------------------------------------------------- add
        case 'add':
            if ($method !== 'POST') {
                throw new Exception('Geçersiz istek.');
            }
            $quantity = isset($_REQUEST['quantity']) ? (int)$_REQUEST['quantity'] : 1;

            if ($productId <= 0) {
                throw new Exception('Geçersiz ürün.');
            }

            $product = $db->query(
                "SELECT id, name, status, stock FROM products WHERE id = ?",
                [$productId]
            )->fetch();

            if (!$product || (int)$product['status'] !== 1) {
                throw new Exception('Ürün bulunamadı.');
            }

            if ((int)$product['stock'] <= 0) {
                throw new Exception($product['name'] . ' tükendi.');
            }

            $current = isset($_SESSION[DELIVERY_CART_KEY][$productId])
                ? (int)$_SESSION[DELIVERY_CART_KEY][$productId]['quantity']
                : 0;

            if ($current + $quantity > (int)$product['stock']) {
                throw new Exception('Stoktan fazla ürün eklenemez (Kalan: ' . (int)$product['stock'] . ').');
            }

            addToCart($productId, $quantity, DELIVERY_CART_KEY);
            break;

        // ------------------------------------------------------------ set_qty
        case 'set_qty':
            if ($method !== 'POST') {
                throw new Exception('Geçersiz istek.');
            }
            $delta = isset($_REQUEST['qty']) ? (int)$_REQUEST['qty'] : 0;

            if ($productId <= 0 || ($delta !== 1 && $delta !== -1)) {
                throw new Exception('Geçersiz miktar.');
            }

            $product = $db->query("SELECT stock FROM products WHERE id = ?", [$productId])->fetch();
            if (!$product) {
                throw new Exception('Ürün bulunamadı.');
            }

            $current = isset($_SESSION[DELIVERY_CART_KEY][$productId])
                ? (int)$_SESSION[DELIVERY_CART_KEY][$productId]['quantity']
                : 0;

            if ($delta > 0 && $current >= (int)$product['stock']) {
                throw new Exception('Stok sınırına ulaşıldı.');
            }

            $newQuantity = changeCartQuantity($productId, $delta, DELIVERY_CART_KEY);
            $deliverySettings = getDeliverySettings($db);
            $cart = buildDeliveryCart($db);
            $deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

            echo json_encode([
                'success'     => true,
                'quantity'    => $newQuantity,
                'count'       => getCartCount(DELIVERY_CART_KEY),
                'subtotal'    => $cart['subtotal'],
                'delivery_fee'=> $deliveryFee,
                'total'       => calculateDeliveryTotal($cart['subtotal'], 0, $deliveryFee),
            ]);
            exit;

        // ------------------------------------------------------------- remove
        case 'remove':
            if ($method !== 'POST') {
                throw new Exception('Geçersiz istek.');
            }
            if ($productId <= 0) {
                throw new Exception('Geçersiz ürün.');
            }

            removeFromCart($productId, DELIVERY_CART_KEY);

            $deliverySettings = getDeliverySettings($db);
            $cart = buildDeliveryCart($db);
            $deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

            echo json_encode([
                'success'     => true,
                'message'     => 'Ürün sepetten çıkarıldı.',
                'count'       => getCartCount(DELIVERY_CART_KEY),
                'subtotal'    => $cart['subtotal'],
                'delivery_fee'=> $deliveryFee,
                'total'       => calculateDeliveryTotal($cart['subtotal'], 0, $deliveryFee),
            ]);
            exit;

        // ------------------------------------------------------------ summary
        case 'summary':
        default:
            $deliverySettings = getDeliverySettings($db);
            $cart = buildDeliveryCart($db);
            $deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

            echo json_encode([
                'success'        => true,
                'count'          => getCartCount(DELIVERY_CART_KEY),
                'total'          => calculateDeliveryTotal($cart['subtotal'], 0, $deliveryFee),
                'total_formatted'=> number_format(calculateDeliveryTotal($cart['subtotal'], 0, $deliveryFee), 2, ',', '.') . ' ₺',
            ]);
            exit;
    }

    $deliverySettings = getDeliverySettings($db);
    $cart = buildDeliveryCart($db);
    $deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

    echo json_encode([
        'success'     => true,
        'message'     => 'Ürün sepete eklendi.',
        'count'       => getCartCount(DELIVERY_CART_KEY),
        'subtotal'    => $cart['subtotal'],
        'delivery_fee'=> $deliveryFee,
        'total'       => calculateDeliveryTotal($cart['subtotal'], 0, $deliveryFee),
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
