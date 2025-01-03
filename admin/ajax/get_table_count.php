<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    $count = $db->query("SELECT COUNT(*) as count FROM tables")->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 