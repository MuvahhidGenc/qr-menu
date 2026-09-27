<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('tables.sales')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

try {
    $db = new Database();
    
    // Kullanıcı ID'sini al (user_id veya admin_id)
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null);
    
    if (!$current_user_id) {
        throw new Exception('Kullanıcı oturumu bulunamadı');
    }
    
    // POST verilerini al
    $items = json_decode($_POST['items'] ?? '[]', true);
    $payment_method = $_POST['payment_method'] ?? '';
    $total = floatval($_POST['total'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $discount_type = $_POST['discount_type'] ?? 'amount';
    $note = $_POST['note'] ?? '';
    $is_partial = isset($_POST['is_partial']) && $_POST['is_partial'] == 'true';
    $partial_payments = $is_partial && isset($_POST['partial_payments']) ? json_decode($_POST['partial_payments'], true) : null;
    $register_id = isset($_POST['register_id']) ? intval($_POST['register_id']) : 1;
    
    if (empty($items) || !is_array($items)) {
        throw new Exception('Geçersiz sepet verisi');
    }

    // Ödeme yöntemi beyaz listesi. payments.payment_method yalnızca
    // enum('cash','pos') kabul eder; 'card'/'mixed' gibi değerler MySQL'in
    // sessizce boş string'e çevirmesine (bkz. payments.id=71) yol açıyordu.
    if (!in_array($payment_method, ['cash', 'pos'], true)) {
        throw new Exception('Geçersiz ödeme yöntemi');
    }

    // Sepet sunucu tarafında fiyatlandırılır. İstemciden gelen subtotal/total
    // değerleri güvenilmezdir: aksi halde total=1, subtotal=100000 gönderilerek
    // raporları kalıcı olarak şişirmek mümkündü.
    $subtotal = 0.0;
    $pricedItems = [];
    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        $quantity  = (int)($item['quantity'] ?? 0);
        if ($productId <= 0 || $quantity <= 0) {
            throw new Exception('Sepette geçersiz ürün veya miktar var');
        }
        $product = $db->query(
            "SELECT id, name, price, stock FROM products WHERE id = ?",
            [$productId]
        )->fetch();
        if (!$product) {
            throw new Exception('Ürün bulunamadı: ID ' . $productId);
        }
        $unitPrice = (float)$product['price'];
        $subtotal += $unitPrice * $quantity;
        $pricedItems[] = [
            'product' => $product,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }
    $subtotal = round($subtotal, 2);

    $discount_amount = 0.0;
    if ($discount > 0) {
        $discount_amount = ($discount_type === 'percent')
            ? ($subtotal * $discount) / 100
            : min($discount, $subtotal);
        $discount_amount = round($discount_amount, 2);
    }

    // Tahsil edilen tutar = brüt - indirim. Eski kod istemciden gelen $total'i
    // paid_amount olarak yazıyordu; artık aynı değer total_amount alanına da
    // yazılır, böylece tüm raporlar aynı anlamı taşıyan tek alanı kullanır.
    $total = round($subtotal - $discount_amount, 2);
    if ($total <= 0) {
        throw new Exception('Ödenecek tutar sıfır veya eksi olamaz');
    }
    
    // Sistem parametrelerini kontrol et
    $stockTracking = $db->query(
        "SELECT setting_value FROM settings WHERE setting_key = 'system_stock_tracking'"
    )->fetch();
    $stockTrackingEnabled = $stockTracking && $stockTracking['setting_value'] == '1';
    
    $db->beginTransaction();
    
    // Ödeme notunu hazırla
    $payment_note = 'POS Satış - Kasa ' . $register_id;
    if ($note) {
        $payment_note .= ' - ' . $note;
    }
    if ($is_partial && $partial_payments) {
        $payment_note .= ' | Kısmi ödemeler: ' . count($partial_payments) . ' adet';
    }
    
    // Payment kaydı oluştur
    $stmt = $db->query(
        "INSERT INTO payments (total_amount, subtotal, paid_amount, discount_amount, payment_method, status, created_at, payment_note) 
         VALUES (?, ?, ?, ?, ?, 'completed', NOW(), ?)",
        [$total, $subtotal, $total, $discount_amount, $payment_method, $payment_note]
    );
    
    $payment_id = $db->lastInsertId();
    
    // Her ürün için order_items kaydı oluştur ve stok düş.
    // Fiyat sunucuda doğrulandığı için $pricedItems kullanılır; sepet fiyatı
    // doğrudan order_items'a yazılmaz.
    foreach ($pricedItems as $entry) {
        $product = $entry['product'];
        $quantity = $entry['quantity'];
        $unitPrice = $entry['unit_price'];

        // Stok kontrolü
        if ($stockTrackingEnabled) {
            if ((int)$product['stock'] < $quantity) {
                throw new Exception($product['name'] . ' için stok yetersiz (Mevcut: ' . $product['stock'] . ', İstenen: ' . $quantity . ')');
            }
            
            $old_stock = (int)$product['stock'];
            $new_stock = $old_stock - $quantity;
            
            // Stok düş
            $db->query(
                "UPDATE products SET stock = ? WHERE id = ?",
                [$new_stock, $product['id']]
            );
            
            // Stok hareketini kaydet
            $db->query(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by, created_at) 
                 VALUES (?, 'out', ?, ?, ?, ?, ?, NOW())",
                [
                    $product['id'],
                    $quantity,
                    $old_stock,
                    $new_stock,
                    'POS Satış - Fiş #' . $payment_id,
                    $current_user_id
                ]
            );
        }
        
        // Order item ekle (POS için order_id NULL)
        $db->query(
            "INSERT INTO order_items (product_id, quantity, price, payment_id, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$product['id'], $quantity, $unitPrice, $payment_id]
        );
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Satış başarıyla tamamlandı',
        'sale_id' => $payment_id
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

