<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.delete')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
    
    if (!$category_id) {
        throw new Exception('Kategori ID gerekli');
    }
    
    $db = new Database();
    
    // Önce bu kategoriye bağlı ürün var mı kontrol et
    $productCount = $db->query(
        "SELECT COUNT(*) as count FROM products WHERE category_id = ?", 
        [$category_id]
    )->fetch()['count'];
    
    if ($productCount > 0) {
        throw new Exception('Bu kategoriye ait ürünler bulunmaktadır. Önce ürünleri silmelisiniz.');
    }
    
    // Kategoriyi sil
    $db->query("DELETE FROM categories WHERE id = ?", [$category_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori başarıyla silindi'
    ]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 