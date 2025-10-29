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
        "SELECT id, name, price, image, barcode, stock 
         FROM products 
         WHERE status = 1 
         ORDER BY name ASC"
    );
} else {
    $stmt = $db->query(
        "SELECT id, name, price, image, barcode, stock 
         FROM products 
         WHERE status = 1 AND category_id = ? 
         ORDER BY name ASC",
        [(int)$category_id]
    );
}

$products = $stmt->fetchAll();

// Boş barkod ve stok değerlerini ayarla
foreach ($products as &$product) {
    $product['barcode'] = isset($product['barcode']) ? $product['barcode'] : '';
    $product['stock'] = isset($product['stock']) ? (int)$product['stock'] : 9999;
}

echo json_encode([
    'success' => true,
    'products' => $products
]);
?>

