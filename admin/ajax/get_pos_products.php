<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

$db = new Database();

// Kategori filtresi
$category_id = isset($_GET['category']) ? $_GET['category'] : 'all';

// SQL sorgusu
if ($category_id === 'all') {
    $stmt = $db->query(
        "SELECT id, name, price, discount_percent, image, barcode, stock 
         FROM products 
         WHERE status = 1 
         ORDER BY name ASC"
    );
} else {
    $stmt = $db->query(
        "SELECT id, name, price, discount_percent, image, barcode, stock 
         FROM products 
         WHERE status = 1 AND category_id = ? 
         ORDER BY name ASC",
        [(int)$category_id]
    );
}

$products = $stmt->fetchAll();

// Boş barkod ve stok değerlerini ayarla; kasiyer ekranı için indirimli fiyat
foreach ($products as &$product) {
    $product['barcode'] = isset($product['barcode']) ? $product['barcode'] : '';
    $product['stock'] = isset($product['stock']) ? (int)$product['stock'] : 9999;
    $product['discount_percent'] = isset($product['discount_percent']) ? (float)$product['discount_percent'] : 0.0;
    $product['effective_price'] = dvEffectivePrice($product['price'], $product['discount_percent']);
}
unset($product);

echo json_encode([
    'success' => true,
    'products' => $products
]);
?>

