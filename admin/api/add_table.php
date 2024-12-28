<?php
require_once '../../config.php';
require_once '../auth_check.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare("INSERT INTO tables (table_number) VALUES (?)");
    $result = $stmt->execute([$data['table_number']]);
    
    echo json_encode(['success' => $result]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 