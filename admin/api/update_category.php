<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
    $name = cleanInput($input['name'] ?? '');
    $image = $input['image'] ?? '';
    
    if (!$category_id) {
        throw new Exception('Kategori ID gerekli');
    }
    
    if (empty($name)) {
        throw new Exception('Kategori adı gerekli');
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
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 