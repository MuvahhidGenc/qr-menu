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

    // Ödeme bilgilerini al
    $paymentId = $_POST['payment_id'] ?? null;
    if (!$paymentId) {
        throw new Exception('Ödeme ID bulunamadı.');
    }

    // Ödeme detaylarını getir
    $payment = $db->query("SELECT p.*, t.table_name, t.table_number 
                          FROM payments p 
                          LEFT JOIN tables t ON p.table_id = t.id 
                          WHERE p.id = ?", [$paymentId])->fetch();

    // Fiş içeriğini oluştur
    $content = [
        str_repeat('=', 32),
        str_pad('ÖDEME FİŞİ', 32, ' ', STR_PAD_BOTH),
        str_repeat('=', 32),
        '',
        'Tarih: ' . date('d.m.Y H:i:s', strtotime($payment['created_at'])),
        'Masa: ' . $payment['table_name'] . ' (#' . $payment['table_number'] . ')',
        'Fiş No: ' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT),
        '',
        str_repeat('-', 32),
        str_pad('ÖDEME DETAYLARI', 32, ' ', STR_PAD_BOTH),
        str_repeat('-', 32),
        '',
        str_pad('TOPLAM TUTAR:', 20) . 
        str_pad(number_format($payment['amount'], 2) . ' TL', 12, ' ', STR_PAD_LEFT),
        str_pad('ÖDEME TİPİ:', 20) . 
        str_pad($payment['payment_type'], 12, ' ', STR_PAD_LEFT),
        '',
        str_repeat('=', 32)
    ];

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