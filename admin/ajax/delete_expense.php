<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $db = new Database();
        
        $id = intval($_POST['id']);
        
        $db->query("DELETE FROM expenses WHERE id = ?", [$id]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log('Gider Silme Hatası: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} 