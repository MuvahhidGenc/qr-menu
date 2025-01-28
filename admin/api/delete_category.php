<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.delete')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['category_id'])) {
        throw new Exception('Kategori ID gerekli');
    }

    $db = new Database();
    
    // Transaction başlat
    $db->beginTransaction();
    
    try {
        $categoryId = (int)$input['category_id'];
        
        // Önce kategoriye ait ürünleri sil
        $db->query("DELETE FROM products WHERE category_id = ?", [$categoryId]);
        
        // Sonra kategoriyi sil
        $db->query("DELETE FROM categories WHERE id = ?", [$categoryId]);
        
        // Transaction'ı tamamla
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kategori ve ilişkili ürünler başarıyla silindi'
        ]);
    } catch (Exception $e) {
        // Hata durumunda rollback yap
        $db->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 