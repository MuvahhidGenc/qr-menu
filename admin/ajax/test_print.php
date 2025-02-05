<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

try {
    $db = new Database();
    
    // Ayarları getir - Fetch modunu düzelttik
    $settings = [];
    $settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%'");
    $results = $settingsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Sonuçları düzenle
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $printer = $settings['printer_default'] ?? '';
    if (empty($printer)) {
        throw new Exception('Varsayılan yazıcı seçilmemiş.');
    }

    // Test fişi içeriği
    $content = [
        str_repeat('=', 32),
        str_pad('TEST FİŞİ', 32, ' ', STR_PAD_BOTH),
        str_repeat('=', 32),
        '',
        'Tarih: ' . date('d.m.Y H:i:s'),
        'Yazıcı: ' . $printer,
        'Kağıt Genişliği: ' . ($settings['printer_paper_width'] ?? '80') . 'mm',
        '',
        str_repeat('=', 32),
        ($settings['printer_header'] ?? ''),
        '',
        'Test yazdırma başarılı!',
        '',
        ($settings['printer_footer'] ?? ''),
        str_repeat('=', 32),
        "\n\n\n\n" // Kağıt kesme için boşluk
    ];

    // İşletim sistemine göre yazdırma
    if (PHP_OS === 'WINNT') {
        // Windows için
        $handle = printer_open($printer);
        printer_write($handle, implode("\n", $content));
        if ($settings['printer_auto_cut'] ?? false) {
            printer_write($handle, "\x1B\x69"); // Kağıt kesme komutu
        }
        printer_close($handle);
    } else {
        // Linux için
        $command = sprintf('echo "%s" | lpr -P "%s"', 
            implode("\n", $content), 
            $printer
        );
        exec($command);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 