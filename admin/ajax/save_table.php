<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Bu uç masa oluşturur/günceller -> tables.manage. Önceden kontrol yoktu.
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

    $tableNo = trim((string)($_POST['table_no'] ?? ''));
    if ($tableNo === '' || mb_strlen($tableNo) > 20) {
        throw new Exception('Geçersiz masa numarası');
    }

    $capacity = (int)($_POST['capacity'] ?? 0);
    if ($capacity < 0 || $capacity > 100) {
        throw new Exception('Geçersiz kapasite');
    }

    // tables.status enum: active / inactive
    $status = (string)($_POST['status'] ?? 'active');
    if (!in_array($status, ['active', 'inactive'], true)) {
        throw new Exception('Geçersiz durum');
    }

    // Masa numarası kontrolü
    $existing = $db->query(
        "SELECT id FROM tables WHERE table_no = ? AND id != ?",
        [$tableNo, $id]
    )->fetch();

    if ($existing) {
        throw new Exception('Bu masa numarası zaten kullanımda!');
    }

    if ($id > 0) {
        $tbl = $db->query("SELECT id FROM tables WHERE id = ? LIMIT 1", [$id])->fetch();
        if (!$tbl) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Masa bulunamadı.']);
            exit;
        }
        $db->query(
            "UPDATE tables SET table_no = ?, capacity = ?, status = ? WHERE id = ?",
            [$tableNo, $capacity, $status, $id]
        );
    } else {
        $db->query(
            "INSERT INTO tables (table_no, capacity, status) VALUES (?, ?, ?)",
            [$tableNo, $capacity, $status]
        );
        $id = (int)$db->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('save_table hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),   // bilinçli mesaj; ayrıntı log'da
    ], JSON_UNESCAPED_UNICODE);
}