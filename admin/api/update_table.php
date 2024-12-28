<?php
require_once '../../config.php';
require_once '../auth_check.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare("UPDATE tables SET table_number = ? WHERE id = ?");
    $result = $stmt->execute([$data['table_number'], $data['id']]);
    
    echo json_encode(['success' => $result]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 