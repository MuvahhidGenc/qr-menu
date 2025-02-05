<?php
function printReceipt($printer, $content, $settings = []) {
    try {
        // İçeriği Windows-1254 encoding'e çevir
        $encodedContent = array_map(function($line) {
            return mb_convert_encoding($line, 'Windows-1254', 'UTF-8');
        }, $content);

        $printContent = implode("\n", $encodedContent);

        // İşletim sistemine göre yazdırma
        if (PHP_OS === 'WINNT') {
            // Windows için
            $tempFile = tempnam(sys_get_temp_dir(), 'print_');
            file_put_contents($tempFile, $printContent);
            
            // Windows yazdırma komutu
            $command = sprintf(
                'chcp 1254 > nul && powershell.exe -Command "(Get-Content -Encoding Default \'%s\') | Out-Printer -Name \'%s\'"',
                $tempFile,
                $printer
            );
            
            exec($command, $output, $returnCode);
            unlink($tempFile);
            
            if ($returnCode !== 0) {
                throw new Exception('Yazdırma işlemi başarısız oldu. Hata kodu: ' . $returnCode);
            }
        } else {
            // Linux için
            $command = sprintf('echo "%s" | iconv -f UTF-8 -t ISO-8859-9 | lpr -P "%s"', $printContent, $printer);
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Yazdırma işlemi başarısız oldu. Hata kodu: ' . $returnCode);
            }
        }

        return ['success' => true];
    } catch (Exception $e) {
        error_log('Yazdırma Hatası: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 