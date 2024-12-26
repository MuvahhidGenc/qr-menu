<?php
require_once '../includes/config.php';
require_once '../includes/cart.php';
header('Content-Type: application/json');

// Debug için session içeriğini kontrol et
error_log('SESSION içeriği: ' . print_r($_SESSION, true));

try {
    $db = new Database();
    $html = '';
    $total = 0;

    // Session cart kontrolü ekle
    if (!isset($_SESSION['cart'])) {
        error_log('Cart session yok!');
        $_SESSION['cart'] = [];
    }

    if(!empty($_SESSION['cart'])) {
        error_log('Cart dolu, ürünler: ' . print_r($_SESSION['cart'], true));
        
        foreach($_SESSION['cart'] as $product_id => $item) {
            // SQL sorgusu debug
            error_log('SQL sorgusu için product_id: ' . $product_id);
            
            $product = $db->query("SELECT * FROM products WHERE id = ?", [$product_id])->fetch();
            if($product) {
                error_log('Ürün bulundu: ' . print_r($product, true));
                
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;

                $html .= '<div class="cart-item mb-3">
                <div class="d-flex align-items-center">
                    <img src="uploads/'.$product['image'].'" class="cart-item-image me-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">'.htmlspecialchars($product['name']).'</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity('.$product_id.', -1)">-</button>
                                <span class="form-control text-center">'.$item['quantity'].'</span>
                                <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity('.$product_id.', 1)">+</button>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">'.number_format($product['price'], 2).' ₺</div>
                                <small class="text-muted">Toplam: '.number_format($subtotal, 2).' ₺</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
            } else {
                error_log('Ürün bulunamadı: ' . $product_id);
            }
        }
    } else {
        error_log('Cart boş!');
    }

    if(empty($html)) {
        $html = '<div class="text-center py-4">Sepetiniz boş</div>';
    }

    $response = [
        'success' => true,
        'html' => $html,
        'total' => number_format($total, 2),
        'count' => getCartCount()
    ];

    error_log('Response: ' . print_r($response, true));
    echo json_encode($response);

} catch(Exception $e) {
    error_log('Hata: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}