<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    $tables = $db->query("
        SELECT id, table_no 
        FROM tables 
        WHERE status = 'free' 
        ORDER BY table_no
    ")->fetchAll();

    echo json_encode(['success' => true, 'tables' => $tables]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 