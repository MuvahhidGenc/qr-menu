<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('tables.manage')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['table_number'])) {
        throw new Exception('Gerekli bilgiler eksik');
    }

    $db = new Database();
    $result = $db->query("UPDATE tables SET table_no = ? WHERE id = ?", 
        [$data['table_number'], $data['id']]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla güncellendi'
    ]);
} catch(Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 