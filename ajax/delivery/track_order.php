<?php
/**
 * Web Adrese Sipariş - Sipariş Sorgulama (BACKEND)
 *
 * Güvenlik prensipleri:
 *   - Sadece ADRES siparişleri (order_type='delivery') sorgulanabilir.
 *   - Sipariş no TEK BAŞINA yetmez; kayıtlı telefon ile BİRLİKTE eşleşmelidir.
 *     (Sipariş numaraları sıralıdır; tek başına sorgu IDOR'a açıktır.)
 *   - Hız sınırlama (RateLimit) ile numara tahmin edilmesi engellenir.
 *   - Hatalı eşleşmede sipariş varlığı BİLE ele verilmez.
 *   - PDO prepared statement; çıktılar htmlspecialchars ile kaçırılır.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/delivery-tracking.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek.');
    }

    // Sorgulama özelliği her zaman açık kalmalıdır: müşteri mod kapalıyken de
    // verilmiş siparişini sorgulayabilmelidir. Bu yüzden isDeliveryEnabled kontrolü YOK.

    // CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !validateCSRFToken($csrfToken)) {
        throw new Exception('Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }

    // Rate limit: 10 dakikada en fazla 10 deneme
    if (!trackOrderCheckRateLimit($db, 10, 600)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Çok fazla sorgulama denemesi yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
        ]);
        exit;
    }

    // ---------------------------------------------------------- 1) Girdiler
    // Not: getSecureInt() yalnızca GET okur; bu endpoint POST olduğu için
    // sipariş no trackOrderSanitizeNo() ile ayrıca temizlenir.
    $orderNo = trackOrderSanitizeNo($_POST['order_no'] ?? '');

    if ($orderNo <= 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz sipariş numarası.',
        ]);
        exit;
    }

    $phone = normalizePhone($_POST['phone'] ?? '');
    if ($phone === '') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Telefon numarası gerekli.',
        ]);
        exit;
    }

    // ------------------------------------------------- 2) Doğrulanmış sorgu
    // Telefon EŞLEŞMESİ zorunlu. Sadece adres siparişleri.
    $result = trackOrderLookup($db, $orderNo, $phone);

    if (!$result['success']) {
        if ($result['code'] === 'invalid') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Geçersiz bilgi.']);
        } else {
            // Varlık bilgisi sızdırmamak için sipariş bulunamadı ile eşleşmedi aynıdır.
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Bu bilgilerle eşleşen bir sipariş bulunamadı. Sipariş numaranızı ve telefonunuzu kontrol edin.',
            ]);
        }
        exit;
    }

    $order          = $result['order'];
    $items          = $result['items'];
    $settings       = $result['settings'];
    $prepareMinutes = (int)($settings['delivery_prepare_minutes'] ?? 45);
    $statusLabels   = deliveryStatusList();
    $paymentLabels  = deliveryPaymentMethods();

    // Takip sonucunda aksiyon butonlari gosterilmez
    $showActions = false;

    // ------------------------------------------------- 4) HTML üret
    ob_start();
    include __DIR__ . '/../../includes/delivery-order-view.php';
    $html = (string)ob_get_clean();

    // Sorgulama sonrası: sipariş numarasını sonuçta göster
    echo json_encode([
        'success' => true,
        'message' => 'Siparişiniz bulundu.',
        'order_id' => (int)$order['id'],
        'status'   => $order['status'],
        'status_label' => $statusLabels[$order['status']] ?? deliveryStatusLabel($order['status']),
        'is_cancelled' => $order['status'] === 'cancelled',
        'is_finished'  => in_array($order['status'], ['delivered', 'completed', 'cancelled'], true),
        'html'    => $html,
    ]);

} catch (Exception $e) {
    error_log('Delivery track error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
