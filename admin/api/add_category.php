<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.add')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }
    
    $name = cleanInput($_POST['name']);
    $image = $_POST['image'] ?? '';
    
    if (empty($name)) {
        throw new Exception('Kategori adı gerekli');
    }
    
    $db = new Database();
    
    // Kategoriyi ekle
    $db->query(
        "INSERT INTO categories (name, image) VALUES (?, ?)",
        [$name, $image]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori başarıyla eklendi'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 