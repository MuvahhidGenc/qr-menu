<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.add')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // Form verilerini al ve temizle
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image = $_POST['image'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;
    $barcode = isset($_POST['barcode']) ? cleanInput($_POST['barcode']) : null;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $discount_percent = isset($_POST['discount_percent']) ? floatval($_POST['discount_percent']) : 0.0;
    if ($discount_percent < 0 || $discount_percent > 100) {
        throw new Exception('İndirim yüzdesi 0-100 arasında olmalıdır');
    }

    // Zorunlu alanları kontrol et
    if (empty($name) || empty($category_id)) {
        throw new Exception('Lütfen zorunlu alanları doldurun.');
    }

    $db = new Database();
    
    // Sistem parametrelerini kontrol et
    $posSettings = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('system_barcode_sales_enabled', 'system_stock_tracking')")->fetchAll();
    $systemParams = [];
    foreach ($posSettings as $setting) {
        $systemParams[$setting['setting_key']] = $setting['setting_value'];
    }
    $barcodeSalesEnabled = isset($systemParams['system_barcode_sales_enabled']) && $systemParams['system_barcode_sales_enabled'] == '1';
    $stockTrackingEnabled = isset($systemParams['system_stock_tracking']) && $systemParams['system_stock_tracking'] == '1';
    
    // POS aktifse barkod zorunlu
    if ($barcodeSalesEnabled && empty($barcode)) {
        throw new Exception('POS satış sistemi aktif - Barkod numarası zorunludur');
    }
    
    // Barkod benzersizliği kontrolü
    if (!empty($barcode)) {
        $existingBarcode = $db->query("SELECT id FROM products WHERE barcode = ? AND id != 0", [$barcode])->fetch();
        if ($existingBarcode) {
            throw new Exception('Bu barkod numarası başka bir ürüne ait');
        }
    }
    
    // Ürünü veritabanına ekle
    $db->query(
        "INSERT INTO products (name, description, price, discount_percent, category_id, image, status, barcode, stock) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
        [$name, $description, $price, $discount_percent, $category_id, $image, $status, $barcode, $stock]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Ürün başarıyla eklendi'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}