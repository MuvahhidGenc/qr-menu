<?php
require_once '../../includes/config.php';
$db = new Database();

// Admin kontrolü
if(!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// POST verilerini al
$id = (int)$_POST['product_id'];
$name = cleanInput($_POST['name']);
$description = cleanInput($_POST['description']);
$price = (float)$_POST['price'];
$category_id = (int)$_POST['category_id'];
$image = $_POST['image'] ?? '';
$status = isset($_POST['status']) ? 1 : 0;

try {
    // Ürünü güncelle
    $db->query("
        UPDATE products 
        SET name = ?, description = ?, price = ?, 
            category_id = ?, image = ?, status = ? 
        WHERE id = ?
    ", [$name, $description, $price, $category_id, $image, $status, $id]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ürün güncellenirken bir hata oluştu']);
} 