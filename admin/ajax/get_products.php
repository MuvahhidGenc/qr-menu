<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/db.php';

try {
    $db = new Database();
    $categoryId = intval($_GET['category_id'] ?? 0);

    if ($categoryId <= 0) {
        throw new Exception('Geçersiz kategori');
    }

    $query = "SELECT id, name, price, image, description FROM products 
              WHERE category_id = ? AND status = 1 
              ORDER BY name";

    $products = $db->query($query, [$categoryId])->fetchAll();

    echo json_encode($products);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 