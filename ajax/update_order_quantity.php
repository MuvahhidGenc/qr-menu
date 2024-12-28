<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Ham veriyi al ve logla
    $raw_input = file_get_contents('php://input');
    error_log('Raw Input: ' . $raw_input);
    
    $data = json_decode($raw_input, true);
    error_log('Decoded Data: ' . print_r($data, true));
    
    if (!isset($data['order_id']) || !isset($data['item_id']) || !isset($data['change'])) {
        throw new Exception('Gerekli parametreler eksik');
    }

    // Transaction başlat
    $db->beginTransaction();

    try {
        // Mevcut miktarı kontrol et
        $currentQuantity = $db->query(
            "SELECT quantity FROM order_items WHERE order_id = ? AND id = ?",
            [$data['order_id'], $data['item_id']]
        )->fetch();

        if (!$currentQuantity) {
            throw new Exception('Sipariş öğesi bulunamadı');
        }

        $newQuantity = max(1, $currentQuantity['quantity'] + $data['change']);

        // Miktarı güncelle
        $db->query(
            "UPDATE order_items SET quantity = ? WHERE order_id = ? AND id = ?",
            [$newQuantity, $data['order_id'], $data['item_id']]
        );

        // Sipariş toplamını güncelle
        $db->query(
            "UPDATE orders o 
             SET total_amount = (
                 SELECT COALESCE(SUM(oi.quantity * p.price), 0)
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = o.id
             )
             WHERE o.id = ?",
            [$data['order_id']]
        );

        // Transaction'ı tamamla
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Miktar güncellendi'
        ]);

    } catch (Exception $e) {
        // Hata durumunda rollback yap
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Miktar Güncelleme Hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 