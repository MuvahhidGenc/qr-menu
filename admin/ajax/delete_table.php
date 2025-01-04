<?php
require_once '../../includes/config.php';

$db = new Database();

try {
    $id = $_POST['id'];

    // Aktif siparişleri kontrol et
    $activeOrders = $db->query(
        "SELECT COUNT(*) as count FROM orders WHERE table_id = ? AND status != 'completed'",
        [$id]
    )->fetch();

    if ($activeOrders['count'] > 0) {
        throw new Exception('Bu masada aktif siparişler var!');
    }

    // Masayı sil
    $db->query("DELETE FROM tables WHERE id = ?", [$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}