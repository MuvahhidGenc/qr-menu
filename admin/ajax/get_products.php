<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Kategoriye göre aktif ürün listesi -> products.view.
// Önceden hiç auth yoktu: ürün listesi anonim okunabiliyordu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('products.view')) {
    http_response_code(403);
    echo json_encode(['error' => 'Yetkiniz yok.']);
    exit;
}

try {
    $db = new Database();
    $categoryId = (int)($_GET['category_id'] ?? 0);

    if ($categoryId <= 0) {
        throw new Exception('Geçersiz kategori');
    }

    // products.status tinyint(1): 1 = aktif
    $products = $db->query(
        "SELECT id, name, price, image, description FROM products
         WHERE category_id = ? AND status = 1
         ORDER BY name",
        [$categoryId]
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_products hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Ürünler yüklenemedi.'], JSON_UNESCAPED_UNICODE);
}