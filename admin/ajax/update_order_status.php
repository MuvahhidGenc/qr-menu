<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Yetki kontrolü
if (!hasPermission('kitchen.manage')) {
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok']);
    exit;
}

$orderId = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit;
}

$db = new Database();

try {
    $result = $db->query(
        "UPDATE orders SET status = ? WHERE id = ?",
        [$status, $orderId]
    );

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Güncelleme başarısız']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}