<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['notification_id'])) {
        throw new Exception('Bildirim ID gerekli');
    }
    
    $db = new Database();
    $notification_id = (int)$_POST['notification_id'];
    
    $db->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notification_id]);
    
    echo json_encode([
        'success' => true
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}