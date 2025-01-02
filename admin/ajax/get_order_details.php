<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['order_id'])) {
        throw new Exception('Sipariş ID gerekli');
    }

    $db = new Database();
    $order_id = (int)$_POST['order_id'];

    // Sipariş bilgilerini al
    $order = $db->query("
        SELECT o.*, t.table_no 
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.id 
        WHERE o.id = ?
    ", [$order_id])->fetch();

    if(!$order) {
        throw new Exception('Sipariş bulunamadı');
    }

    // Sipariş detaylarını al
    $items = $db->query("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ", [$order_id])->fetchAll();

    // HTML oluştur
    $html = '
    <div class="order-details">
        <div class="mb-3">
            <strong>Sipariş No:</strong> #'.$order['id'].'<br>
            <strong>Masa:</strong> '.$order['table_no'].'<br>
            <strong>Tarih:</strong> '.date('d.m.Y H:i', strtotime($order['created_at']));

    // Sipariş notu varsa göster
    if (!empty($order['note'])) {
        $html .= '<br><div class="alert alert-info mt-2">
            <strong>Sipariş Notu:</strong><br>
            '.htmlspecialchars($order['note']).'
        </div>';
    }

    $html .= '</div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Adet</th>
                        <th>Fiyat</th>
                        <th>Toplam</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach($items as $item) {
        $html .= '
        <tr>
            <td>'.htmlspecialchars($item['product_name']).'</td>
            <td>'.$item['quantity'].'</td>
            <td>'.number_format($item['price'], 2).' ₺</td>
            <td>'.number_format($item['price'] * $item['quantity'], 2).' ₺</td>
        </tr>';
    }
    
    $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Toplam:</th>
                        <th>'.number_format($order['total_amount'], 2).' ₺</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}