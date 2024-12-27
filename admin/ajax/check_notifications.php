<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Okunmamış bildirim sayısını al
    $count = $db->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch()['count'];
    
    // Son kontrolden sonra gelen yeni bildirimleri kontrol et
    $last_check = $_SESSION['last_notification_check'] ?? 0;
    $new_notifications = $db->query(
        "SELECT COUNT(*) as count FROM notifications WHERE created_at > FROM_UNIXTIME(?)",
        [$last_check]
    )->fetch()['count'];
    
    // Son kontrol zamanını güncelle
    $_SESSION['last_notification_check'] = time();
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'new_notifications' => $new_notifications > 0
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}