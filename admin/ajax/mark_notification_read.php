<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Tek bildirimi okundu işaretleme -> dashboard.view. Önceden auth yoktu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('dashboard.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

try {
    if (!isset($_POST['notification_id'])) {
        throw new Exception('Bildirim ID gerekli');
    }

    $db = new Database();
    $notification_id = (int)$_POST['notification_id'];
    if ($notification_id <= 0) {
        throw new Exception('Geçersiz bildirim ID');
    }

    $db->query("UPDATE notifications SET is_read = 1 WHERE id = ?", [$notification_id]);

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('mark_notification_read hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}