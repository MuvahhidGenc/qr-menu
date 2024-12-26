<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'count' => getCartCount()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}