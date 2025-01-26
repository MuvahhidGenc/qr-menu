<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$db = new Database();

try {
    // Session kontrolü
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Oturum kontrolü
    if (!isLoggedIn()) {
        throw new Exception('Oturum açmanız gerekiyor');
    }

    $id = $_GET['id'];
    
    $table = $db->query(
        "SELECT * FROM tables WHERE id = ?",
        [$id]
    )->fetch();

    if ($table) {
        echo json_encode([
            'success' => true,
            'data' => $table
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Masa bulunamadı.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 