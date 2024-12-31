<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();

header('Content-Type: application/json');

try {
    $db = new Database();
    
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    
    if (!isset($data['table_id']) || !is_numeric($data['table_id'])) {
        throw new Exception('Geçersiz masa ID');
    }

    // Debug için tablo yapısını kontrol et
    $tableStructure = $db->query("DESCRIBE orders")->fetchAll();
    error_log('Orders Table Structure: ' . print_r($tableStructure, true));

    $db->beginTransaction();

    try {
        // Önce basit bir insert deneyelim
        $sql = "INSERT INTO orders (table_id, status, created_at) VALUES (?, 'new', NOW())";
        $db->query($sql, [(int)$data['table_id']]);
        $orderId = $db->lastInsertId();

        // Sipariş öğelerini ekle
        foreach ($data['items'] as $item) {
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price) 
                 VALUES (?, ?, ?, ?)",
                [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']  // Gelen fiyat bilgisini kullan
                ]
            );
        }

        // Toplamı ayrı bir sorguda güncelle
        $db->query(
            "UPDATE orders SET total_amount = (
                SELECT COALESCE(SUM(oi.quantity * p.price), 0)
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ) WHERE id = ?",
            [$orderId, $orderId]
        );

        $db->commit();

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Sipariş başarıyla kaydedildi'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log('SQL Error: ' . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'table_structure' => $tableStructure ?? null,
            'error' => $e->getMessage()
        ]
    ]);
} 