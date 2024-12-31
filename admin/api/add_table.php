<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';

header('Content-Type: application/json');

try {
    checkAuth();
    
    // JSON verilerini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug için
    error_log('Received input: ' . print_r($input, true));
    
    if (empty($input['table_no'])) {
        throw new Exception('Masa numarası gerekli');
    }
    
    $db = new Database();
    
    // Masa numarası benzersiz mi kontrol et
    $existing = $db->query(
        "SELECT id FROM tables WHERE table_no = ?", 
        [$input['table_no']]
    )->fetch();
    
    if ($existing) {
        throw new Exception('Bu masa numarası zaten kullanımda');
    }
    
    // Masayı ekle
    $result = $db->query(
        "INSERT INTO tables (table_no, capacity) VALUES (?, ?)",
        [
            $input['table_no'],
            intval($input['capacity'] ?? 4)
        ]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla eklendi'
    ]);

} catch (Exception $e) {
    error_log('Error in add_table.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'input' => $input ?? null,
            'error' => $e->getMessage()
        ]
    ]);
} 