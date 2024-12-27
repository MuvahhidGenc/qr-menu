<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    
    $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    
    echo json_encode([
        'success' => true
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}