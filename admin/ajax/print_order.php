<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../includes/print_helper.php';

try {
    $db = new Database();
    
    // Yazıcı ayarlarını al
    $settings = [];
    $settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%'");
    $results = $settingsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
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
    $order = $db->query("SELECT o.*, t.table_name, t.table_number 
                        FROM orders o 
                        LEFT JOIN tables t ON o.table_id = t.id 
                        WHERE o.id = ?", [$orderId])->fetch();

    $orderItems = $db->query("SELECT oi.*, p.name as product_name, p.price 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?", [$orderId])->fetchAll();

    // Fiş içeriğini oluştur
    $content = [
        str_repeat('=', 32),
        str_pad('SİPARİŞ FİŞİ', 32, ' ', STR_PAD_BOTH),
        str_repeat('=', 32),
        '',
        'Tarih: ' . date('d.m.Y H:i:s'),
        'Masa: ' . $order['table_name'] . ' (#' . $order['table_number'] . ')',
        'Sipariş No: ' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
        '',
        str_repeat('-', 32),
        str_pad('ÜRÜNLER', 32, ' ', STR_PAD_BOTH),
        str_repeat('-', 32)
    ];

    // Ürünleri ekle
    $total = 0;
    foreach ($orderItems as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $total += $itemTotal;
        
        $content[] = str_pad($item['product_name'], 20) . 
                     str_pad($item['quantity'] . ' x ' . number_format($item['price'], 2), 12, ' ', STR_PAD_LEFT);
    }

    // Toplam tutarı ekle
    $content[] = str_repeat('-', 32);
    $content[] = str_pad('TOPLAM:', 20) . 
                 str_pad(number_format($total, 2) . ' TL', 12, ' ', STR_PAD_LEFT);
    $content[] = str_repeat('=', 32);

    // Varsa header ve footer ekle
    if (!empty($settings['printer_header'])) {
        array_unshift($content, '', $settings['printer_header'], '');
    }
    if (!empty($settings['printer_footer'])) {
        $content[] = '';
        $content[] = $settings['printer_footer'];
    }

    // Kağıt kesme için boşluk
    $content[] = "\n\n\n\n";

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