<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');

try {
    checkAuth();
    
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 