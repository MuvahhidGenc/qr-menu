<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!isAdmin() && !isSuperAdmin()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}
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