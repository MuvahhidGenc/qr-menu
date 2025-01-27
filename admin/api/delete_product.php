<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.delete')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('Geçersiz veri formatı');
    }

    $product_id = (int)$input['id'];

    if (!$product_id) {
        throw new Exception('Geçersiz ürün ID');
    }

    $db = new Database();
    
    // Önce ürünün var olduğunu kontrol et
    $product = $db->query("SELECT * FROM products WHERE id = ?", [$product_id])->fetch();
    
    if (!$product) {
        throw new Exception('Ürün bulunamadı');
    }

    // Ürünü sil
    $db->query("DELETE FROM products WHERE id = ?", [$product_id]);

    // Eğer ürünün bir resmi varsa, resmi de sil
    if (!empty($product['image'])) {
        $image_path = __DIR__ . '/../../uploads/' . $product['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Ürün başarıyla silindi'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 