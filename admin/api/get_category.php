<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.view')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        throw new Exception('Kategori ID gerekli');
    }
    
    $db = new Database();
    $category = $db->query("SELECT * FROM categories WHERE id = ?", [$id])->fetch();
    
    if (!$category) {
        throw new Exception('Kategori bulunamadı');
    }
    
    echo json_encode([
        'success' => true,
        'category' => $category
    ]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 