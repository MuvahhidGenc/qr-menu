<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

try {
    $db = new Database();
    
    // Ayarları getir
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

    // Test fişi içeriği - Windows-1254 (Turkish) encoding ile
    $content = implode("\n", [
        str_repeat('=', 32),
        mb_convert_encoding(str_pad('TEST FISI', 32, ' ', STR_PAD_BOTH), 'Windows-1254', 'UTF-8'),
        str_repeat('=', 32),
        '',
        mb_convert_encoding('Tarih: ' . date('d.m.Y H:i:s'), 'Windows-1254', 'UTF-8'),
        mb_convert_encoding('Yazici: ' . $printer, 'Windows-1254', 'UTF-8'),
        mb_convert_encoding('Kagit Genisligi: ' . ($settings['printer_paper_width'] ?? '80') . 'mm', 'Windows-1254', 'UTF-8'),
        '',
        str_repeat('=', 32),
        mb_convert_encoding($settings['printer_header'] ?? '', 'Windows-1254', 'UTF-8'),
        '',
        mb_convert_encoding('Test yazdirma basarili!', 'Windows-1254', 'UTF-8'),
        '',
        mb_convert_encoding($settings['printer_footer'] ?? '', 'Windows-1254', 'UTF-8'),
        str_repeat('=', 32),
        "\n\n\n\n" // Kağıt kesme için boşluk
    ]);

    // İşletim sistemine göre yazdırma
    if (PHP_OS === 'WINNT') {
        // Windows için
        $tempFile = tempnam(sys_get_temp_dir(), 'print_');
        file_put_contents($tempFile, $content);
        
        // Windows yazdırma komutu - chcp 1254 ile Türkçe karakter desteği
        $command = sprintf(
            'chcp 1254 > nul && powershell.exe -Command "(Get-Content -Encoding Default \'%s\') | Out-Printer -Name \'%s\'"',
            $tempFile,
            $printer
        );
        
        exec($command, $output, $returnCode);
        unlink($tempFile); // Geçici dosyayı sil
        
        if ($returnCode !== 0) {
            throw new Exception('Yazdırma işlemi başarısız oldu. Hata kodu: ' . $returnCode);
        }
    } else {
        // Linux için
        $command = sprintf('echo "%s" | iconv -f UTF-8 -t ISO-8859-9 | lpr -P "%s"', $content, $printer);
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Yazdırma işlemi başarısız oldu. Hata kodu: ' . $returnCode);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Test fişi başarıyla yazdırıldı.']);

} catch (Exception $e) {
    error_log('Yazdırma Hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 