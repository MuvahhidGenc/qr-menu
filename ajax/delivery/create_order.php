<?php
/**
 * Web Adrese Sipariş - Sipariş Oluşturma (BACKEND)
 *
 * Güvenlik prensipleri:
 *   - Frontend'den GELEN FİYAT KULLANILMAZ. Ürün fiyatları DB'den okunur.
 *   - Toplam tutar backend'de hesaplanır.
 *   - CSRF token doğrulanır.
 *   - Hız sınırlama (RateLimit) uygulanır.
 *   - Stok takibi açıksa stok düşülür ve stock_movements kaydı yazılır.
 *   - Tüm yazma işlemleri transaction içindedir.
 *   - PDO prepared statement (SQL injection koruması).
 */
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$db = null;

try {
    $db = new Database();

    // ---------------------------------------------------------- 1) Kontroller
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek.');
    }

    if (!isDeliveryEnabled($db)) {
        throw new Exception('Web üzerinden sipariş alma hizmeti kapalı.');
    }

    $hours = checkDeliveryHours($db);
    if (!$hours['open']) {
        throw new Exception($hours['message'] !== '' ? $hours['message'] : 'Şu anda sipariş kabul edilmiyor.');
    }

    // CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !validateCSRFToken($csrfToken)) {
        throw new Exception('Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }

    // Rate limit: 10 dakikada en fazla 10 sipariş
    $limit = new RateLimit($db);
    $limit->setWindow(600);
    $limit->setMaxAttempts(10);
    if (!$limit->check('delivery_create_order')) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Çok fazla sipariş denemesi yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
        ]);
        exit;
    }

    // ------------------------------------------------- 2) Sepet (DB doğrulamalı)
    $cart = buildDeliveryCart($db);

    if (empty($cart['items'])) {
        throw new Exception('Sepetiniz boş veya ürünler satıştan kaldırılmış.');
    }

    $deliverySettings = getDeliverySettings($db);

    // Minimum sipariş tutarı
    $minOrder = (float)($deliverySettings['delivery_min_order'] ?? 0);
    if ($minOrder > 0 && $cart['subtotal'] < $minOrder) {
        throw new Exception('Minimum sipariş tutarı ' . number_format($minOrder, 2, ',', '.') . ' ₺ olmalıdır.');
    }

    // ------------------------------------------------- 3) Form doğrulama
    $requiredFields = getRequiredDeliveryFields($db);
    $validation = validateDeliveryForm($_POST, $requiredFields);

    if (!empty($validation['errors'])) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Lütfen eksik/geçersiz bilgileri düzeltin.',
            'errors'  => $validation['errors'],
        ]);
        exit;
    }
    $customer = $validation['data'];

    // ------------------------------------------------- 4) Ödeme yöntemi
    $paymentMethods = getAvailablePaymentMethods($db);
    $paymentMethod = deliveryCleanText($_POST['payment_method'] ?? '', 20);

    if (!isset($paymentMethods[$paymentMethod])) {
        throw new Exception('Geçersiz ödeme yöntemi.');
    }

    $orderNote = deliveryCleanText($_POST['order_note'] ?? '', 500);

    // ------------------------------------------------- 5) Tutar hesaplama
    $subtotal    = $cart['subtotal'];
    $discount    = 0.0;                                   // İlk aşamada web siparişinde indirim uygulanmaz
    $deliveryFee = calculateDeliveryFee($deliverySettings, $subtotal);
    $total       = calculateDeliveryTotal($subtotal, $discount, $deliveryFee);

    $token = generateDeliveryToken();

    // ------------------------------------------------- 6) Transaction
    $db->beginTransaction();

    try {
        $customerName = trim($customer['name'] . ' ' . $customer['surname']);

        // Sipariş oluştur (masa = NULL, order_type = delivery)
        $db->query(
            "INSERT INTO orders (
                order_type, table_id, status, subtotal, discount_amount, delivery_fee,
                total_amount, payment_method, delivery_token, note, created_at
             ) VALUES (
                'delivery', NULL, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW()
             )",
            [
                $subtotal,
                $discount,
                $deliveryFee,
                $total,
                $paymentMethod,
                $token,
                !empty($orderNote) ? $orderNote : null,
            ]
        );

        $orderId = (int)$db->lastInsertId();

        // Adres bilgileri snapshot olarak yazılır (müşteri adresi sonradan
        // değişse bile eski sipariş değişmez).
        $db->query(
            "UPDATE orders SET
                customer_name = ?, customer_surname = ?, customer_phone = ?,
                delivery_city = ?, delivery_district = ?, delivery_neighborhood = ?,
                delivery_address = ?, delivery_building_no = ?, delivery_apartment_no = ?,
                delivery_note = ?
             WHERE id = ?",
            [
                $customer['name'],
                $customer['surname'],
                $customer['phone'],
                $customer['city'],
                $customer['district'],
                $customer['neighborhood'],
                $customer['address'],
                $customer['building_no'],
                $customer['apartment_no'],
                $customer['note'],
                $orderId,
            ]
        );

        // Ürün satırları (fiyat DB'den gelen $item['price'])
        $stockTracking = isset($deliverySettings['system_stock_tracking'])
            ? ((string)$deliverySettings['system_stock_tracking'] === '1')
            : false;
        if (!$stockTracking) {
            $stockRow = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'system_stock_tracking'")->fetch();
            $stockTracking = $stockRow && $stockRow['setting_value'] == '1';
        }

        foreach ($cart['items'] as $item) {
            if ($stockTracking) {
                deductDeliveryStock($db, $item, 'Web Adres Siparişi #' . $orderId);
            }

            $db->query(
                "INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$orderId, $item['product_id'], $item['quantity'], $item['price']]
            );
        }

        // İşletme bilgilendirme bildirimi (mevcut notifications mekanizması)
        $db->query(
            "INSERT INTO notifications (order_id, type, message, created_at)
             VALUES (?, 'new_delivery_order', ?, NOW())",
            [
                $orderId,
                'Yeni web siparişi #' . $orderId . ' - ' . $customerName . ' (' . $customer['phone'] . ') - ' . number_format($total, 2, ',', '.') . ' ₺',
            ]
        );

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    // ---------------------------------------------- 7) Session temizliği
    clearCart(DELIVERY_CART_KEY);
    unset($_SESSION['existing_order_id']);

    // Adresi bu tarayıcı oturumunda sakla (formu tekrar doldurmamak için).
    // NOT: Kalıcı müşteri tablosu tutulmaz (KVKK) - sadece session.
    $_SESSION['delivery_customer'] = $customer;
    $_SESSION['delivery_last_order'] = [
        'id'    => $orderId,
        'token' => $token,
    ];

    echo json_encode([
        'success'       => true,
        'message'       => 'Siparişiniz başarıyla alındı.',
        'order_id'      => $orderId,
        'order_number'  => '#' . $orderId,
        'total'         => $total,
        'total_formatted'=> number_format($total, 2, ',', '.') . ' ₺',
    ]);

} catch (Exception $e) {
    if ($db instanceof Database) {
        try {
            $db->rollBack();
        } catch (Exception $ignored) {
            // Transaction yoksa yoksay
        }
    }

    error_log('Delivery order error: ' . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
