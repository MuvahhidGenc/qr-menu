<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!hasPermission('categories.edit')) {
        throw new Exception('Bu işlem için yetkiniz yok!');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['categories']) || !is_array($data['categories'])) {
        throw new Exception('Geçersiz veri formatı');
    }

    $db = new Database();
    $db->beginTransaction();

    foreach ($data['categories'] as $category) {
        $db->query(
            "UPDATE categories 
             SET sort_order = ? 
             WHERE id = ?",
            [$category['sort_order'], $category['id']]
        );
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 