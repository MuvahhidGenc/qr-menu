<?php
require_once '../../includes/config.php';
$db = new Database();

// Admin kontrolü
if(!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// ID kontrolü
if(!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Ürün ID\'si gerekli']);
    exit;
}

$id = (int)$_GET['id'];

// Ürün bilgilerini getir
$product = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
", [$id])->fetch();

if($product) {
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            'category_id' => $product['category_id'],
            'category_name' => $product['category_name'],
            'image' => $product['image'],
            'status' => $product['status']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
} 