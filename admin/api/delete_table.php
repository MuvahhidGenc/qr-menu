<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $tableId = $data['id'] ?? null;

    if (!$tableId) {
        throw new Exception('Masa ID gerekli');
    }

    $db = new Database();
    $result = $db->query("DELETE FROM tables WHERE id = ?", [$tableId]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla silindi'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 