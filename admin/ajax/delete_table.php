<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Oturum + tables.manage yetkisi. Önceden hiç kontrol yoktu: herkes masa
// silebiliyor / oluşturabiliyordu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('tables.manage')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

try {
    $db = new Database();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('Geçersiz masa ID');
    }

    // Aktif siparişleri kontrol et
    $activeOrders = $db->query(
        "SELECT COUNT(*) as count FROM orders
         WHERE table_id = ? AND status NOT IN ('completed','cancelled')",
        [$id]
    )->fetch();

    if ((int)$activeOrders['count'] > 0) {
        throw new Exception('Bu masada aktif siparişler var!');
    }

    $tbl = $db->query("SELECT id FROM tables WHERE id = ? LIMIT 1", [$id])->fetch();
    if (!$tbl) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Masa bulunamadı.']);
        exit;
    }

    $db->query("DELETE FROM tables WHERE id = ?", [$id]);

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('delete_table hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),   // bilinçli mesaj; SQLSTATE sızmaz (log'a yazıldı)
    ], JSON_UNESCAPED_UNICODE);
}