<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['item_id'])) {
        throw new Exception('Gerekli parametreler eksik');
    }

    // Transaction başlat
    $db->beginTransaction();

    try {
        // Önce siparişin var olduğunu kontrol et
        $order = $db->query(
            "SELECT id FROM orders WHERE id = ? AND payment_id IS NULL",
            [$data['order_id']]
        )->fetch();

        if (!$order) {
            throw new Exception('Sipariş bulunamadı veya tamamlanmış');
        }

        // Sipariş öğesini sil
        $result = $db->query(
            "DELETE FROM order_items WHERE order_id = ? AND id = ?",
            [$data['order_id'], $data['item_id']]
        );

        if ($result->rowCount() === 0) {
            throw new Exception('Sipariş öğesi bulunamadı');
        }

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

        // Kalan ürün sayısını kontrol et
        $remainingItems = $db->query(
            "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?",
            [$data['order_id']]
        )->fetch();

        if ($remainingItems['count'] == 0) {
            $db->query("DELETE FROM orders WHERE id = ?", [$data['order_id']]);
        }

        // Transaction'ı tamamla
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Ürün başarıyla silindi',
            'remaining_count' => $remainingItems['count']
        ]);

    } catch (Exception $e) {
        // Hata durumunda rollback yap
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Sipariş Silme Hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 