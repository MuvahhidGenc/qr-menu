<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Bildirim okundu işaretleme -> dashboard.view (her admin rolü bunu görebilir).
// Önceden hiç auth yoktu.
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

    $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('mark_all_read hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bildirimler güncellenemedi.'
    ], JSON_UNESCAPED_UNICODE);
}