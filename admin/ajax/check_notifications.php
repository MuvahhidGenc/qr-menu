<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Bildirim rozeti sayacı -> dashboard.view. Önceden auth yoktu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('dashboard.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

try {
    $db = new Database();

    // Okunmamış bildirim sayısını al
    $count = (int)$db->query(
        "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0"
    )->fetch()['count'];

    // Son kontrolden sonra gelen yeni bildirimleri kontrol et
    $last_check = (int)($_SESSION['last_notification_check'] ?? 0);
    $new_notifications = (int)$db->query(
        "SELECT COUNT(*) as count FROM notifications WHERE created_at > FROM_UNIXTIME(?)",
        [$last_check]
    )->fetch()['count'];

    // Son kontrol zamanını güncelle
    $_SESSION['last_notification_check'] = time();

    echo json_encode([
        'success' => true,
        'count' => $count,
        'new_notifications' => $new_notifications > 0
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('check_notifications hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bildirimler yüklenemedi.'
    ], JSON_UNESCAPED_UNICODE);
}