<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../includes/print_helper.php';

try {
    $db = new Database();
    
    // Yazıcı ayarlarını al
    $settings = getPrinterSettings();
    
    $printer = $settings['printer_default'] ?? '';
    if (empty($printer)) {
        throw new Exception('Varsayılan yazıcı seçilmemiş.');
    }

    // Sipariş bilgilerini al
    $orderId = $_POST['order_id'] ?? null;
    if (!$orderId) {
        throw new Exception('Sipariş ID bulunamadı.');
    }

    // Sipariş detaylarını getir
    $order = $db->query("SELECT o.*, t.table_no 
                        FROM orders o 
                        LEFT JOIN tables t ON o.table_id = t.id 
                        WHERE o.id = ?", [$orderId])->fetch();

    if (!$order) {
        throw new Exception('Sipariş bulunamadı.');
    }

    $orderItems = $db->query("SELECT oi.*, p.name as product_name, p.price 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?", [$orderId])->fetchAll();

    // Merkezi fiş oluşturma fonksiyonunu kullan
    $content = buildOrderReceipt($order, $orderItems);

    // Yazdırma işlemini gerçekleştir
    $result = printReceipt($printer, $content, $settings);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    echo json_encode(['success' => true, 'message' => 'Fiş başarıyla yazdırıldı.']);

} catch (Exception $e) {
    error_log('Fiş Yazdırma Hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 