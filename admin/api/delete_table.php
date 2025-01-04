<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('tables.manage')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $tableId = $data['id'] ?? null;

    if (!$tableId) {
        throw new Exception('Masa ID gerekli');
    }

    // Masada aktif sipariş var mı kontrol et
    $db = new Database();
    $activeOrders = $db->query(
        "SELECT COUNT(*) as count FROM orders WHERE table_id = ? AND payment_id IS NULL",
        [$tableId]
    )->fetch();

    if ($activeOrders['count'] > 0) {
        throw new Exception('Bu masada aktif siparişler var. Önce siparişleri tamamlayın.');
    }

    $result = $db->query("DELETE FROM tables WHERE id = ?", [$tableId]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla silindi'
    ]);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 