<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');

try {
    checkAuth();
    
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $name = cleanInput($_POST['name']);
    $image = $_POST['image'] ?? '';
    
    if (!$category_id) {
        throw new Exception('Kategori ID gerekli');
    }
    
    $db = new Database();
    
    // Kategoriyi güncelle
    $db->query(
        "UPDATE categories SET name = ?, image = ? WHERE id = ?",
        [$name, $image, $category_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori başarıyla güncellendi'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 