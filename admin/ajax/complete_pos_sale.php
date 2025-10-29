<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
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
    $items = json_decode($_POST['items'], true);
    $payment_method = $_POST['payment_method']; // cash, card or mixed
    $total = floatval($_POST['total']);
    $subtotal = floatval($_POST['subtotal'] ?? $total);
    $discount = floatval($_POST['discount'] ?? 0);
    $discount_type = $_POST['discount_type'] ?? 'amount';
    $note = $_POST['note'] ?? '';
    $is_partial = isset($_POST['is_partial']) && $_POST['is_partial'] == 'true';
    $partial_payments = $is_partial && isset($_POST['partial_payments']) ? json_decode($_POST['partial_payments'], true) : null;
    $register_id = isset($_POST['register_id']) ? intval($_POST['register_id']) : 1;
    
    if (empty($items) || $total <= 0) {
        throw new Exception('Geçersiz sepet verisi');
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
    $discount_amount = 0;
    if ($discount > 0) {
        if ($discount_type === 'percent') {
            $discount_amount = ($subtotal * $discount) / 100;
        } else {
            $discount_amount = $discount;
        }
    }
    
    $stmt = $db->query(
        "INSERT INTO payments (total_amount, subtotal, paid_amount, discount_amount, payment_method, status, created_at, payment_note) 
         VALUES (?, ?, ?, ?, ?, 'completed', NOW(), ?)",
        [$subtotal, $subtotal, $total, $discount_amount, $payment_method, $payment_note]
    );
    
    $payment_id = $db->lastInsertId();
    
    // Her ürün için order_items kaydı oluştur ve stok düş
    foreach ($items as $item) {
        // Stok kontrolü
        if ($stockTrackingEnabled) {
            $product = $db->query(
                "SELECT stock, name FROM products WHERE id = ?",
                [$item['id']]
            )->fetch();
            
            if (!$product) {
                throw new Exception('Ürün bulunamadı: ID ' . $item['id']);
            }
            
            if ($product['stock'] < $item['quantity']) {
                throw new Exception($product['name'] . ' için stok yetersiz (Mevcut: ' . $product['stock'] . ', İstenen: ' . $item['quantity'] . ')');
            }
            
            $old_stock = $product['stock'];
            $new_stock = $old_stock - $item['quantity'];
            
            // Stok düş
            $db->query(
                "UPDATE products SET stock = ? WHERE id = ?",
                [$new_stock, $item['id']]
            );
            
            // Stok hareketini kaydet
            $db->query(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by, created_at) 
                 VALUES (?, 'out', ?, ?, ?, ?, ?, NOW())",
                [
                    $item['id'],
                    $item['quantity'],
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
            [$item['id'], $item['quantity'], $item['price'], $payment_id]
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

