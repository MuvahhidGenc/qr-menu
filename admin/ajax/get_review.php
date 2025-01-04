<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!isAdmin() && !isSuperAdmin()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}
$db = new Database();

try {
    $id = $_GET['id'];
    
    // Ana değerlendirmeyi çek
    $review = $db->query(
        "SELECT r.*, t.table_no 
         FROM reviews r 
         LEFT JOIN orders o ON r.order_id = o.id
         LEFT JOIN tables t ON o.table_id = t.id 
         WHERE r.id = ?",
        [$id]
    )->fetch();

    // Ürün değerlendirmelerini çek
    $product_reviews = $db->query(
        "SELECT pr.*, p.name as product_name 
         FROM product_reviews pr 
         LEFT JOIN products p ON pr.product_id = p.id 
         WHERE pr.review_id = ?",
        [$id]
    )->fetchAll();

    if ($review) {
        echo json_encode([
            'success' => true,
            'review' => $review,
            'product_reviews' => $product_reviews
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Değerlendirme bulunamadı.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 