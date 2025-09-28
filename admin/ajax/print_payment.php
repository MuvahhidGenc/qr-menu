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

    // Ödeme bilgilerini al
    $paymentId = $_POST['payment_id'] ?? null;
    if (!$paymentId) {
        throw new Exception('Ödeme ID bulunamadı.');
    }

    // Ödeme detaylarını getir
    $payment = $db->query("SELECT p.*, t.table_no 
                          FROM payments p 
                          LEFT JOIN tables t ON p.table_id = t.id 
                          WHERE p.id = ?", [$paymentId])->fetch();

    if (!$payment) {
        throw new Exception('Ödeme bulunamadı.');
    }

    // Bu ödemeye ait sipariş öğelerini getir (varsa)
    $orderItems = $db->query("
        SELECT oi.*, p.name as product_name, oi.price
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.payment_id = ?
    ", [$paymentId])->fetchAll();

    // Merkezi fiş oluşturma fonksiyonunu kullan
    $content = buildPaymentReceipt($payment, $orderItems);

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