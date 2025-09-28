<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('settings.view')) {
    http_response_code(403);
    exit();
}

$db = new Database();

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$_GET['id']])->fetch();
    
    if($theme) {
        header('Content-Type: application/json');
        echo json_encode($theme);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Theme not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>