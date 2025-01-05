<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.add')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // Form verilerini al ve temizle
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image = $_POST['image'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;

    // Zorunlu alanları kontrol et
    if (empty($name) || empty($category_id)) {
        throw new Exception('Lütfen zorunlu alanları doldurun.');
    }

    $db = new Database();
    
    // Ürünü veritabanına ekle
    $db->query(
        "INSERT INTO products (name, description, price, category_id, image, status) 
         VALUES (?, ?, ?, ?, ?, ?)", 
        [$name, $description, $price, $category_id, $image, $status]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Ürün başarıyla eklendi'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}