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
    
    if (!isset($data['order_id']) || !isset($data['item_id']) || 
        !isset($data['source_table']) || !isset($data['target_table'])) {
        throw new Exception('Eksik parametreler');
    }

    $db = new Database();
    $db->beginTransaction();

    // Önce sipariş öğesinin mevcut siparişte başka öğeleri var mı kontrol et
    $itemCount = $db->query(
        "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?",
        [$data['order_id']]
    )->fetch()['count'];

    if ($itemCount > 1) {
        // Birden fazla öğe varsa, yeni bir sipariş oluştur
        $db->query(
            "INSERT INTO orders (table_id, status, created_at) 
             SELECT ?, status, NOW() 
             FROM orders WHERE id = ?",
            [$data['target_table'], $data['order_id']]
        );
        
        $newOrderId = $db->getConnection()->lastInsertId();

        // Seçili öğeyi yeni siparişe taşı
        $db->query(
            "UPDATE order_items SET order_id = ? WHERE id = ?",
            [$newOrderId, $data['item_id']]
        );
    } else {
        // Tek öğe varsa, direkt siparişi taşı
        $db->query(
            "UPDATE orders SET table_id = ? WHERE id = ?",
            [$data['target_table'], $data['order_id']]
        );
    }

    // Sipariş tutarlarını güncelle
    $db->query(
        "UPDATE orders o 
         SET total_amount = (
             SELECT COALESCE(SUM(oi.quantity * oi.price), 0)
             FROM order_items oi 
             WHERE oi.order_id = o.id
         )
         WHERE o.id = ? OR o.id = ?",
        [$data['order_id'], $newOrderId ?? null]
    );

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sipariş başarıyla aktarıldı'
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