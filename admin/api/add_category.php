<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 