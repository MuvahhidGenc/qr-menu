<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        throw new Exception('Geçersiz ürün ID');
    }

    $db = new Database();
    
    // Ürün bilgilerini getir
    $product = $db->query(
        "SELECT * FROM products WHERE id = ?", 
        [$id]
    )->fetch();
    
    if (!$product) {
        throw new Exception('Ürün bulunamadı');
    }

    echo json_encode([
        'success' => true,
        'product' => $product
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 