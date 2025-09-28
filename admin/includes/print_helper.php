<?php

/**
 * Yazıcı ayarlarını veritabanından al
 */
function getPrinterSettings() {
    global $db;
    if (!$db) {
        $db = new Database();
    }
    
    $settings = [];
    
    // Yazıcı ayarlarını al
    $settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%' OR setting_key IN ('restaurant_name', 'logo')");
    $results = $settingsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * Kağıt genişliğine göre karakter sayısını hesapla
 */
function getCharacterWidth($paperWidth) {
    // Farklı kağıt genişlikleri için karakter sayıları
    // Bu değerler thermal yazıcılar için optimize edilmiştir
    $widthMap = [
        '58' => 24,  // 58mm -> 24 karakter
        '80' => 32,  // 80mm -> 32 karakter (varsayılan)
        '112' => 44, // 112mm -> 44 karakter
    ];
    
    // En yakın genişliği bul veya hesapla
    if (isset($widthMap[$paperWidth])) {
        return $widthMap[$paperWidth];
    }
    
    // Hesaplanmış genişlik (yaklaşık 2.5 karakter per mm)
    $calculatedWidth = (int)($paperWidth * 0.4);
    return max(20, min(60, $calculatedWidth)); // 20-60 arası sınırla
}

/**
 * Responsive metin satırı oluştur
 */
function formatResponsiveText($text, $width, $align = STR_PAD_BOTH) {
    // Uzun metinleri kes ve "..." ekle
    if (mb_strlen($text, 'UTF-8') > $width) {
        $text = mb_substr($text, 0, $width - 3, 'UTF-8') . '...';
    }
    
    return str_pad($text, $width, ' ', $align);
}

/**
 * İki kolonlu responsive satır oluştur (Geliştirilmiş)
 */
function formatTwoColumnText($leftText, $rightText, $totalWidth) {
    // Sağ kolonda fiyat/toplam olacaksa daha fazla alan ver
    // Fiyat formatını kontrol et
    $isPriceText = preg_match('/\d+[.,]\d+\s*(TL|₺)|\d+\s*x\s*\d+[.,]\d+/', $rightText);
    
    if ($isPriceText) {
        // Fiyat metni için: sol %60, sağ %40
        $leftWidth = (int)($totalWidth * 0.60);
        $rightWidth = $totalWidth - $leftWidth;
    } else {
        // Normal metin için: sol %65, sağ %35
        $leftWidth = (int)($totalWidth * 0.65);
        $rightWidth = $totalWidth - $leftWidth;
    }
    
    // Sağ metni önce kontrol et - bu fiyat olabilir, kesilmemeli
    if (mb_strlen($rightText, 'UTF-8') > $rightWidth) {
        // Sağ metin çok uzunsa, sol metni daha çok kes
        $rightWidth = min($rightWidth + 3, $totalWidth - 10); // Min 10 karakter sol için
        $leftWidth = $totalWidth - $rightWidth;
    }
    
    // Sol metni kes gerekirse
    if (mb_strlen($leftText, 'UTF-8') > $leftWidth) {
        $leftText = mb_substr($leftText, 0, $leftWidth - 3, 'UTF-8') . '...';
    }
    
    // Sağ metni son kontrol
    if (mb_strlen($rightText, 'UTF-8') > $rightWidth) {
        $rightText = mb_substr($rightText, 0, $rightWidth - 1, 'UTF-8');
    }
    
    return str_pad($leftText, $leftWidth, ' ', STR_PAD_RIGHT) . 
           str_pad($rightText, $rightWidth, ' ', STR_PAD_LEFT);
}

/**
 * Fiyat satırı için özel formatlama
 */
function formatPriceLine($productName, $quantity, $price, $totalWidth) {
    // Fiyat metni hazırla
    $priceText = number_format($price, 2) . ' TL';
    $quantityPrice = $quantity . ' x ' . number_format($price, 2);
    
    // Fiyat alanı için gerekli minimum genişlik
    $priceMinWidth = max(mb_strlen($priceText, 'UTF-8'), mb_strlen($quantityPrice, 'UTF-8')) + 1;
    
    // Fiyat alanı toplam genişliğin max %45'i olabilir, min 8 karakter
    $rightWidth = min(max($priceMinWidth, 8), (int)($totalWidth * 0.45));
    $leftWidth = $totalWidth - $rightWidth;
    
    // Ürün adını kes gerekirse
    if (mb_strlen($productName, 'UTF-8') > $leftWidth) {
        $productName = mb_substr($productName, 0, $leftWidth - 3, 'UTF-8') . '...';
    }
    
    return str_pad($productName, $leftWidth, ' ', STR_PAD_RIGHT) . 
           str_pad($quantityPrice, $rightWidth, ' ', STR_PAD_LEFT);
}

/**
 * Toplam satırı için özel formatlama
 */
function formatTotalLine($label, $amount, $totalWidth, $isBold = false) {
    $amountText = ($amount < 0 ? '-' : '') . number_format(abs($amount), 2) . ' TL';
    
    // Tutar için gerekli minimum genişlik
    $rightWidth = max(mb_strlen($amountText, 'UTF-8') + 1, 10);
    $leftWidth = $totalWidth - $rightWidth;
    
    // Etiket metni kes gerekirse
    if (mb_strlen($label, 'UTF-8') > $leftWidth) {
        $label = mb_substr($label, 0, $leftWidth - 1, 'UTF-8');
    }
    
    return str_pad($label, $leftWidth, ' ', STR_PAD_RIGHT) . 
           str_pad($amountText, $rightWidth, ' ', STR_PAD_LEFT);
}

/**
 * Uzun metni birden fazla satıra böl
 */
function wrapText($text, $width) {
    if (mb_strlen($text, 'UTF-8') <= $width) {
        return [$text];
    }
    
    $lines = [];
    $words = explode(' ', $text);
    $currentLine = '';
    
    foreach ($words as $word) {
        if (mb_strlen($currentLine . ' ' . $word, 'UTF-8') <= $width) {
            $currentLine .= ($currentLine ? ' ' : '') . $word;
        } else {
            if ($currentLine) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                // Tek kelime çok uzunsa, kes
                $lines[] = mb_substr($word, 0, $width - 3, 'UTF-8') . '...';
                $currentLine = '';
            }
        }
    }
    
    if ($currentLine) {
        $lines[] = $currentLine;
    }
    
    return $lines;
}

/**
 * Fiş başlığı oluştur (Logo + Restaurant Adı + Header)
 */
function buildReceiptHeader($settings = null) {
    if (!$settings) {
        $settings = getPrinterSettings();
    }
    
    $header = [];
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Özel fiş başlığı varsa ekle
    if (!empty($settings['printer_header'])) {
        $headerLines = wrapText($settings['printer_header'], $charWidth);
        foreach ($headerLines as $line) {
            $header[] = formatResponsiveText($line, $charWidth, STR_PAD_BOTH);
        }
        $header[] = '';
    }
    
    // Logo etkinse ve varsa bilgi ekle (gerçek logo fiş yazıcısında gösterilemez, sadece bilgi)
    if (isset($settings['printer_logo_enabled']) && $settings['printer_logo_enabled'] == '1' && !empty($settings['logo'])) {
        $header[] = formatResponsiveText('[LOGO]', $charWidth, STR_PAD_BOTH);
    }
    
    // Restoran adı
    if (!empty($settings['restaurant_name'])) {
        $nameLines = wrapText($settings['restaurant_name'], $charWidth);
        foreach ($nameLines as $line) {
            $header[] = formatResponsiveText($line, $charWidth, STR_PAD_BOTH);
        }
    }
    
    if (!empty($header)) {
        $header[] = str_repeat('=', $charWidth);
        $header[] = '';
    }
    
    return $header;
}

/**
 * Fiş altlığı oluştur (Footer + Minimal Boşluk)
 */
function buildReceiptFooter($settings = null) {
    if (!$settings) {
        $settings = getPrinterSettings();
    }
    
    $footer = [];
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Fiş altı notu varsa ekle
    if (!empty($settings['printer_footer']) && trim($settings['printer_footer'])) {
        $footer[] = '';
        $footerLines = wrapText($settings['printer_footer'], $charWidth);
        foreach ($footerLines as $line) {
            $footer[] = formatResponsiveText($line, $charWidth, STR_PAD_BOTH);
        }
    }
    
    // Minimal kağıt kesme boşluğu - auto-cut ayarına göre
    if (isset($settings['printer_auto_cut']) && $settings['printer_auto_cut'] == '1') {
        // Otomatik kesme etkin: minimal boşluk
        $footer[] = "\n";
    } else {
        // Manuel kesme: elle yırtma için biraz daha fazla boşluk
        $footer[] = "\n\n";
    }
    
    return $footer;
}

/**
 * Ödeme fişi oluştur
 */
function buildPaymentReceipt($payment, $orderItems = []) {
    $settings = getPrinterSettings();
    $content = [];
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Başlık
    $content = array_merge($content, buildReceiptHeader($settings));
    
    // Fiş türü
    $content[] = formatResponsiveText('ÖDEME FİŞİ', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('=', $charWidth);
    
    // Temel bilgiler
    $content[] = 'Tarih: ' . date('d.m.Y H:i:s', strtotime($payment['created_at']));
    if (isset($payment['table_no']) && $payment['table_no']) {
        $content[] = 'Masa: ' . $payment['table_no'];
    } elseif (isset($payment['table_name']) && $payment['table_name']) {
        $tableName = $payment['table_name'] . (isset($payment['table_number']) ? ' (#' . $payment['table_number'] . ')' : '');
        $content[] = 'Masa: ' . $tableName;
    }
    $content[] = 'Fiş No: ' . str_pad($payment['payment_id'] ?? $payment['id'], 6, '0', STR_PAD_LEFT);
    $content[] = '';
    
    // Sipariş detayları varsa
    if (!empty($orderItems)) {
        $content[] = str_repeat('-', $charWidth);
        $content[] = formatResponsiveText('SİPARİŞ DETAYLARI', $charWidth, STR_PAD_BOTH);
        $content[] = str_repeat('-', $charWidth);
        
        $total = 0;
        foreach ($orderItems as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $total += $itemTotal;
            
            $productName = $item['product_name'] ?? $item['name'];
            $content[] = formatPriceLine($productName, $item['quantity'], $item['price'], $charWidth);
        }
        
        $content[] = str_repeat('-', $charWidth);
        $content[] = formatTotalLine('TOPLAM:', $total, $charWidth);
        $content[] = '';
    }
    
    // Ödeme bilgileri
    $content[] = str_repeat('-', $charWidth);
    $content[] = formatResponsiveText('ÖDEME DETAYLARI', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('-', $charWidth);
    
    // Ara toplam
    if (isset($payment['subtotal']) && $payment['subtotal'] > 0) {
        $content[] = formatTotalLine('Ara Toplam:', $payment['subtotal'], $charWidth);
    }
    
    // İskonto
    if (isset($payment['discount_amount']) && $payment['discount_amount'] > 0) {
        $discountText = 'İskonto';
        if (isset($payment['discount_type']) && $payment['discount_type'] == 'percent' && isset($payment['discount_value'])) {
            $discountText .= ' (%' . $payment['discount_value'] . ')';
        }
        $content[] = formatTotalLine($discountText . ':', -$payment['discount_amount'], $charWidth);
    }
    
    // Genel toplam
    $content[] = formatTotalLine('GENEL TOPLAM:', $payment['total_amount'], $charWidth);
    
    // Ödeme yöntemi
    if (isset($payment['payment_method'])) {
        $paymentMethodText = $payment['payment_method'] == 'cash' ? 'NAKİT' : 
                           ($payment['payment_method'] == 'pos' ? 'KART' : strtoupper($payment['payment_method']));
        $content[] = formatTwoColumnText('ÖDEME TİPİ:', $paymentMethodText, $charWidth);
    }
    
    // Ödeme notu
    if (isset($payment['payment_note']) && !empty($payment['payment_note']) && trim($payment['payment_note'])) {
        $content[] = '';
        $noteLines = wrapText('Not: ' . $payment['payment_note'], $charWidth);
        foreach ($noteLines as $line) {
            $content[] = $line;
        }
    }
    
    $content[] = str_repeat('=', $charWidth);
    
    // Altlık
    $content = array_merge($content, buildReceiptFooter($settings));
    
    return $content;
}

/**
 * Sipariş fişi oluştur
 */
function buildOrderReceipt($order, $orderItems) {
    $settings = getPrinterSettings();
    $content = [];
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Başlık
    $content = array_merge($content, buildReceiptHeader($settings));
    
    // Fiş türü
    $content[] = formatResponsiveText('SİPARİŞ FİŞİ', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('=', $charWidth);
    
    // Temel bilgiler
    $content[] = 'Tarih: ' . date('d.m.Y H:i:s', strtotime($order['created_at']));
    if (isset($order['table_name'])) {
        $tableName = $order['table_name'] . (isset($order['table_number']) ? ' (#' . $order['table_number'] . ')' : '');
        $content[] = 'Masa: ' . $tableName;
    }
    $content[] = 'Sipariş No: ' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
    $content[] = '';
    
    // Ürünler
    $content[] = str_repeat('-', $charWidth);
    $content[] = formatResponsiveText('ÜRÜNLER', $charWidth, STR_PAD_BOTH);
    $content[] = str_repeat('-', $charWidth);
    
    $total = 0;
    foreach ($orderItems as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $total += $itemTotal;
        
        $productName = $item['product_name'] ?? $item['name'];
        $content[] = formatPriceLine($productName, $item['quantity'], $item['price'], $charWidth);
    }
    
    // Toplam
    $content[] = str_repeat('-', $charWidth);
    $content[] = formatTotalLine('TOPLAM:', $total, $charWidth);
    $content[] = str_repeat('=', $charWidth);
    
    // Sipariş notu (sadece varsa)
    if (isset($order['notes']) && !empty($order['notes']) && trim($order['notes'])) {
        $content[] = '';
        $noteLines = wrapText('Not: ' . $order['notes'], $charWidth);
        foreach ($noteLines as $line) {
            $content[] = $line;
        }
    }
    
    // Altlık
    $content = array_merge($content, buildReceiptFooter($settings));
    
    return $content;
}

/**
 * Web tabanlı fiş içeriği oluştur (HTML)
 */
function buildWebReceipt($type, $data, $orderItems = []) {
    $settings = getPrinterSettings();
    $paperWidth = $settings['printer_paper_width'] ?? '80';
    $charWidth = getCharacterWidth($paperWidth);
    
    // Font boyutunu kağıt genişliğine göre ayarla
    $fontSize = $paperWidth <= 58 ? '10px' : ($paperWidth <= 80 ? '12px' : '14px');
    
    $content = "<div style='font-family: \"Courier New\", monospace; width: {$paperWidth}mm; margin: 0 auto; padding: 10px; font-size: {$fontSize}; line-height: 1.2;'>";
    
    // Başlık
    $content .= "<div style='text-align: center; margin-bottom: 20px;'>";
    
    if (!empty($settings['printer_header'])) {
        $headerLines = wrapText($settings['printer_header'], $charWidth);
        foreach ($headerLines as $line) {
            $content .= "<div style='margin-bottom: 5px;'>{$line}</div>";
        }
    }
    
    if (!empty($settings['restaurant_name'])) {
        $nameLines = wrapText($settings['restaurant_name'], $charWidth);
        foreach ($nameLines as $line) {
            $content .= "<h3 style='margin: 3px 0; font-size: calc({$fontSize} + 2px);'>{$line}</h3>";
        }
    }
    
    $content .= "<p style='margin: 5px 0;'>Tarih: " . date('d.m.Y H:i:s') . "</p>";
    
    if ($type === 'payment') {
        $content .= "<p style='margin: 5px 0;'>Fiş No: #" . ($data['payment_id'] ?? $data['id']) . "</p>";
        if (isset($data['table_no'])) {
            $content .= "<p style='margin: 5px 0;'>Masa: {$data['table_no']}</p>";
        }
    } elseif ($type === 'order') {
        $content .= "<p style='margin: 5px 0;'>Sipariş No: #" . $data['id'] . "</p>";
        if (isset($data['table_name'])) {
            $content .= "<p style='margin: 5px 0;'>Masa: {$data['table_name']}</p>";
        }
    }
    
    $content .= "</div>";
    
    // İçerik
    if (!empty($orderItems)) {
        $content .= "<div style='margin-bottom: 15px; font-family: \"Courier New\", monospace;'>";
        $content .= "<div style='margin-bottom: 5px; text-align: center;'>" . str_repeat('=', $charWidth) . "</div>";
        $content .= "<div style='text-align: center; margin-bottom: 5px; font-weight: bold;'>SİPARİŞ DETAYLARI</div>";
        $content .= "<div style='margin-bottom: 10px;'>" . str_repeat('-', $charWidth) . "</div>";
        
        foreach ($orderItems as $item) {
            $quantity = $item['quantity'];
            $name = $item['product_name'] ?? $item['name'];
            $price = $item['price'];
            $total = $quantity * $price;
            
            // Responsive iki kolonlu düzen için HTML formatla
            $leftWidth = (int)($charWidth * 0.60); // Fiyat için daha fazla alan
            $rightWidth = $charWidth - $leftWidth;
            
            $leftText = "{$quantity}x {$name}";
            $rightText = number_format($total, 2) . " ₺";
            
            if (mb_strlen($leftText, 'UTF-8') > $leftWidth) {
                $leftText = mb_substr($leftText, 0, $leftWidth - 3, 'UTF-8') . '...';
            }
            
            $leftPadded = str_pad($leftText, $leftWidth, ' ', STR_PAD_RIGHT);
            $rightPadded = str_pad($rightText, $rightWidth, ' ', STR_PAD_LEFT);
            
            $content .= "<div style='margin: 2px 0; white-space: pre;'>{$leftPadded}{$rightPadded}</div>";
        }
        
        $content .= "</div>";
    }
    
    // Ödeme detayları
    if ($type === 'payment') {
        $content .= "<div style='margin-bottom: 10px; font-family: \"Courier New\", monospace;'>";
        $content .= "<div style='margin-bottom: 5px;'>" . str_repeat('-', $charWidth) . "</div>";
        
        $leftWidth = (int)($charWidth * 0.60); // Fiyat için daha fazla alan
        $rightWidth = $charWidth - $leftWidth;
        
        if (isset($data['subtotal']) && $data['subtotal'] > 0) {
            $leftPadded = str_pad('Ara Toplam:', $leftWidth, ' ', STR_PAD_RIGHT);
            $rightPadded = str_pad(number_format($data['subtotal'], 2) . ' ₺', $rightWidth, ' ', STR_PAD_LEFT);
            $content .= "<div style='margin: 2px 0; white-space: pre;'>{$leftPadded}{$rightPadded}</div>";
        }
        
        if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
            $leftPadded = str_pad('İskonto:', $leftWidth, ' ', STR_PAD_RIGHT);
            $rightPadded = str_pad('-' . number_format($data['discount_amount'], 2) . ' ₺', $rightWidth, ' ', STR_PAD_LEFT);
            $content .= "<div style='margin: 2px 0; white-space: pre;'>{$leftPadded}{$rightPadded}</div>";
        }
        
        $leftPadded = str_pad('GENEL TOPLAM:', $leftWidth, ' ', STR_PAD_RIGHT);
        $rightPadded = str_pad(number_format($data['total_amount'], 2) . ' ₺', $rightWidth, ' ', STR_PAD_LEFT);
        $content .= "<div style='margin: 2px 0; white-space: pre; font-weight: bold;'>{$leftPadded}{$rightPadded}</div>";
        
        $content .= "</div>";
    }
    
    // Altlık
    if (!empty($settings['printer_footer'])) {
        $content .= "<div style='margin-top: 10px; text-align: center; font-size: 0.9em;'>";
        $footerLines = wrapText($settings['printer_footer'], $charWidth);
        foreach ($footerLines as $line) {
            $content .= "<div style='margin: 2px 0;'>{$line}</div>";
        }
        $content .= "</div>";
    }
    
    $content .= "</div>";
    
    return $content;
}

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