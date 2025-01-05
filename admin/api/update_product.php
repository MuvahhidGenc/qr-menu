<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    // Verileri doğrula ve temizle
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $name = isset($input['name']) ? cleanInput($input['name']) : '';
    $description = isset($input['description']) ? cleanInput($input['description']) : '';
    $price = isset($input['price']) ? (float)$input['price'] : 0;
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
    $image = isset($input['image']) ? $input['image'] : '';
    $status = isset($input['status']) ? 1 : 0;

    // Zorunlu alanları kontrol et
    if (!$id || !$name || !$category_id) {
        throw new Exception('Lütfen zorunlu alanları doldurun.');
    }

    $db = new Database();
    
    // Ürünü güncelle
    $db->query(
        "UPDATE products SET 
            name = ?, 
            description = ?, 
            price = ?, 
            category_id = ?, 
            image = ?, 
            status = ? 
        WHERE id = ?",
        [$name, $description, $price, $category_id, $image, $status, $id]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Ürün başarıyla güncellendi'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 