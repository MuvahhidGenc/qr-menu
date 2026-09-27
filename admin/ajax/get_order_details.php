<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Yetki kontrolü
if (!isLoggedIn() || !hasPermission('orders.view')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    if (!isset($_POST['order_id'])) {
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

    if (!$order) {
        throw new Exception('Sipariş bulunamadı');
    }

    // Sipariş detaylarını al
    $items = $db->query("
        SELECT oi.*, p.name as product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ", [$order_id])->fetchAll();

    $isDelivery = ($order['order_type'] ?? 'table') === 'delivery';
    $paymentLabels = deliveryPaymentMethods();
    $paymentText = $paymentLabels[$order['payment_method'] ?? ''] ?? '-';

    // HTML oluştur
    $html = '<div class="order-details">';

    // ---------- Başlık: sipariş tipi ----------
    if ($isDelivery) {
        $html .= '
        <div class="alert alert-danger d-flex align-items-center mb-3">
            <i class="fas fa-motorcycle fa-2x me-3"></i>
            <div>
                <strong class="d-block">Adres Siparişi (Web)</strong>
                <small>Müşteri internet üzerinden sipariş vermiştir</small>
            </div>
        </div>';
    }

    // ---------- Temel bilgiler ----------
    $html .= '<div class="row g-2 mb-3">
        <div class="col-6"><strong>Sipariş No:</strong> #' . (int)$order['id'] . '</div>
        <div class="col-6"><strong>Durum:</strong> ' . htmlspecialchars(deliveryStatusLabel($order['status'])) . '</div>
        <div class="col-6"><strong>Tarih:</strong> ' . date('d.m.Y H:i', strtotime($order['created_at'])) . '</div>';

    if ($isDelivery) {
        $html .= '<div class="col-6"><strong>Ödeme:</strong> ' . htmlspecialchars($paymentText) . '</div>';
    } else {
        $html .= '<div class="col-6"><strong>Masa:</strong> ' . htmlspecialchars((string)($order['table_no'] ?? '-')) . '</div>';
    }

    $html .= '</div>';

    // ---------- Müşteri ve teslimat bilgileri ----------
    if ($isDelivery) {
        $html .= '
        <div class="card border-primary mb-3">
            <div class="card-header bg-light">
                <i class="fas fa-address-card me-2"></i><strong>Müşteri ve Teslimat Bilgileri</strong>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <strong>Müşteri:</strong>
                        ' . htmlspecialchars(trim(($order['customer_name'] ?? '') . ' ' . ($order['customer_surname'] ?? ''))) . '
                    </div>
                    <div class="col-12">
                        <strong>Telefon:</strong>
                        <a href="tel:' . htmlspecialchars((string)($order['customer_phone'] ?? '')) . '">'
                            . htmlspecialchars((string)($order['customer_phone'] ?? '-')) . '</a>
                    </div>
                    <div class="col-12">
                        <strong>Teslimat Adresi:</strong><br>
                        <span class="text-dark">' . nl2br(htmlspecialchars(formatDeliveryAddress($order))) . '</span>
                    </div>';

        if (!empty($order['delivery_note'])) {
            $html .= '
                    <div class="col-12">
                        <strong>Adres Tarifi:</strong><br>
                        <span class="text-muted">' . nl2br(htmlspecialchars($order['delivery_note'])) . '</span>
                    </div>';
        }

        $html .= '</div></div></div>';
    }

    // ---------- Sipariş notu ----------
    if (!empty($order['note'])) {
        $html .= '<div class="alert alert-info mt-2">
            <strong>Sipariş Notu:</strong><br>
            ' . nl2br(htmlspecialchars($order['note'])) . '
        </div>';
    }

    // ---------- Ürünler ----------
    $html .= '
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="text-end">Adet</th>
                        <th class="text-end">Fiyat</th>
                        <th class="text-end">Toplam</th>
                    </tr>
                </thead>
                <tbody>';

    $subtotal = 0.0;
    foreach ($items as $item) {
        $lineTotal = (float)$item['price'] * (int)$item['quantity'];
        $subtotal += $lineTotal;

        $html .= '
        <tr>
            <td>' . htmlspecialchars($item['product_name']) . '</td>
            <td class="text-end">' . (int)$item['quantity'] . '</td>
            <td class="text-end">' . number_format((float)$item['price'], 2, ',', '.') . ' ₺</td>
            <td class="text-end">' . number_format($lineTotal, 2, ',', '.') . ' ₺</td>
        </tr>';
    }

    $discount = (float)($order['discount_amount'] ?? 0);
    $deliveryFee = (float)($order['delivery_fee'] ?? 0);

    $html .= '</tbody><tfoot>';
    $html .= '<tr><th colspan="3" class="text-end">Ara Toplam:</th><th class="text-end">' . number_format($subtotal, 2, ',', '.') . ' ₺</th></tr>';

    if ($discount > 0) {
        $html .= '<tr><th colspan="3" class="text-end text-danger">İndirim:</th><th class="text-end text-danger">-' . number_format($discount, 2, ',', '.') . ' ₺</th></tr>';
    }
    if ($isDelivery) {
        $html .= '<tr><th colspan="3" class="text-end">Teslimat Ücreti:</th><th class="text-end">' . number_format($deliveryFee, 2, ',', '.') . ' ₺</th></tr>';
    }

    $html .= '<tr class="table-active"><th colspan="3" class="text-end">Genel Toplam:</th><th class="text-end">' . number_format((float)$order['total_amount'], 2, ',', '.') . ' ₺</th></tr>';
    $html .= '</tfoot></table></div>';

    $html .= '</div>';

    echo json_encode([
        'success' => true,
        'html'    => $html
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
