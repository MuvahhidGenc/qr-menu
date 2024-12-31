<?php
// Hata yakalama fonksiyonunu en başta tanımla
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once '../../includes/config.php';
require_once '../../includes/session.php';

// Tüm çıktıyı bufferlamaya başla
ob_start();

try {
    checkAuth();
    header('Content-Type: application/json');

    $db = new Database();
    
    // POST verilerini kontrol et
    if (!isset($_POST['table_id']) || !isset($_POST['items'])) {
        throw new Exception('Gerekli veriler eksik');
    }

    $table_id = $_POST['table_id'];
    $notes = $_POST['notes'] ?? '';
    $items = json_decode($_POST['items'], true);

    if(empty($items)) {
        throw new Exception('Sipariş boş olamaz!');
    }

    $db->beginTransaction();

    // Masanın aktif siparişini kontrol et
    $active_order = $db->query(
        "SELECT o.id 
         FROM orders o 
         WHERE o.table_id = ? 
         AND o.status IN ('pending', 'preparing')
         AND o.payment_id IS NULL 
         ORDER BY o.created_at DESC 
         LIMIT 1", 
        [$table_id]
    )->fetch();

    // Debug için
    error_log('Table ID: ' . $table_id);
    error_log('Active Order Query Result: ' . print_r($active_order, true));

    if ($active_order) {
        $order_id = $active_order['id'];
        error_log('Using existing order: ' . $order_id);
        
        // Mevcut siparişe ürünleri ekle
        foreach($items as $product_id => $item) {
            $existing_item = $db->query(
                "SELECT id, quantity 
                 FROM order_items 
                 WHERE order_id = ? 
                 AND product_id = ?",
                [$order_id, $product_id]
            )->fetch();

            if ($existing_item) {
                // Varsa miktarı güncelle (updated_at kaldırıldı)
                $db->query(
                    "UPDATE order_items 
                     SET quantity = quantity + ?
                     WHERE id = ?",
                    [$item['quantity'], $existing_item['id']]
                );
            } else {
                // Yoksa yeni ekle
                $db->query(
                    "INSERT INTO order_items (order_id, product_id, quantity, price) 
                     VALUES (?, ?, ?, ?)",
                    [$order_id, $product_id, $item['quantity'], $item['price']]
                );
            }
        }

        // Sipariş tutarını güncelle (updated_at kaldırıldı)
        $db->query(
            "UPDATE orders 
             SET total_amount = (
                 SELECT SUM(quantity * price) 
                 FROM order_items 
                 WHERE order_id = ?
             ),
             status = 'pending'
             WHERE id = ?",
            [$order_id, $order_id]
        );

        // Bildirim oluştur
        $table_info = $db->query("SELECT table_no FROM tables WHERE id = ?", [$table_id])->fetch();
        $notification_message = "Masa {$table_info['table_no']}'a yeni ürünler eklendi";
        $db->query(
            "INSERT INTO notifications (order_id, type, message) 
             VALUES (?, 'order_updated', ?)", 
            [$order_id, $notification_message]
        );

    } else {
        // Yeni sipariş oluştur
        $total = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $items));

        $db->query(
            "INSERT INTO orders (table_id, total_amount, notes, status, created_at) 
             VALUES (?, ?, ?, 'pending', NOW())",
            [$table_id, $total, $notes]
        );
        
        $order_id = $db->getConnection()->lastInsertId();
        
        // Sipariş ürünlerini kaydet
        foreach($items as $product_id => $item) {
            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price) 
                 VALUES (?, ?, ?, ?)",
                [$order_id, $product_id, $item['quantity'], $item['price']]
            );
        }
    }

    $db->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => $active_order ? 'Sipariş güncellendi' : 'Yeni sipariş oluşturuldu'
    ]);

} catch (Throwable $e) {
    // Buffer'ı temizle
    ob_clean();
    
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Save Table Order Error: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} finally {
    // Buffer'ı sonlandır
    ob_end_flush();
} 