<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Session kontrolü
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Oturum kontrolü
    if (!isLoggedIn()) {
        throw new Exception('Oturum açmanız gerekiyor');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['source_table']) || !isset($data['target_table'])) {
        throw new Exception('Kaynak ve hedef masa gerekli');
    }

    $db = new Database();
    $db->beginTransaction();

    // Kaynak masadaki ödenmemiş siparişleri al
    $orders = $db->query(
        "SELECT id FROM orders 
         WHERE table_id = ? AND payment_id IS NULL",
        [$data['source_table']]
    )->fetchAll();

    if (empty($orders)) {
        throw new Exception('Aktarılacak sipariş bulunamadı');
    }

    // Siparişleri yeni masaya aktar
    foreach ($orders as $order) {
        $db->query(
            "UPDATE orders SET table_id = ? WHERE id = ?",
            [$data['target_table'], $order['id']]
        );
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tüm siparişler başarıyla aktarıldı'
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 