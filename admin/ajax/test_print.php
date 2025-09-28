<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../includes/print_helper.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

try {
    $db = new Database();
    
    // Yazıcı ayarlarını al
    $settings = getPrinterSettings();
    
    $printer = $settings['printer_default'] ?? '';
    if (empty($printer)) {
        throw new Exception('Varsayılan yazıcı seçilmemiş.');
    }

    // Test fişi içeriği
    $content = [];
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Başlık
    $content = array_merge($content, buildReceiptHeader($settings));
    
    // Test fişi içeriği
    $content[] = formatResponsiveText('TEST FİŞİ', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('=', $charWidth);
    $content[] = '';
    $content[] = 'Tarih: ' . date('d.m.Y H:i:s');
    $content[] = 'Yazıcı: ' . $printer;
    $content[] = 'Kağıt Genişliği: ' . $paperWidth . 'mm';
    $content[] = 'Karakter Genişliği: ' . $charWidth;
    $content[] = '';
    $content[] = str_repeat('=', $charWidth);
    $content[] = formatResponsiveText('Test yazdırma başarılı!', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('=', $charWidth);
    
    // Altlık
    $content = array_merge($content, buildReceiptFooter($settings));

    // Yazdırma işlemini gerçekleştir
    $result = printReceipt($printer, $content, $settings);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    echo json_encode(['success' => true, 'message' => 'Test fişi başarıyla yazdırıldı.']);

} catch (Exception $e) {
    error_log('Test Yazdırma Hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 