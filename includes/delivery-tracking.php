<?php
/**
 * Web Adrese Sipariş - Sipariş Sorgulama (takip) ortak yardımcılar
 *
 * track_order.php endpoint'i ile test betikleri aynı sorgu kurallarını
 * paylaşır; böylece güvenlik davranışı (IDOR koruması, telefon
 * normalizasyonu, bilgi sızdırmama) tek yerde tanımlı ve test edilebilir
 * kalır.
 */

/**
 * Girdi doğrulama: sipariş numarası yalnızca rakamlardan oluşmalıdır.
 * Baştaki "#" ve boşluklar temizlenir.
 */
function trackOrderSanitizeNo($raw)
{
    $digits = preg_replace('/[^0-9]/', '', (string)$raw);
    if ($digits === null || $digits === '') {
        return 0;
    }
    return (int)$digits;
}

/**
 * Sipariş sorgusu.
 *
 * Güvenlik:
 *   - Yalnızca adres siparişleri (order_type='delivery').
 *   - Sipariş numarası TEK BAŞINA yetmez; kayıtlı telefon ile eşleşmelidir.
 *     Sipariş numaraları sıralı olduğu için yalnız numara ile sorgu IDOR'dur.
 *   - PDO prepared statement; kullanıcı girdisi hiçbir zaman SQL'e girmez.
 *
 * @return array
 *   success=false, code='invalid'   -> girdi geçersiz
 *   success=false, code='not_found' -> eşleşme yok (varlık bilgisi sızdırmaz)
 *   success=true                    -> order, items, settings
 */
function trackOrderLookup($db, $rawOrderNo, $rawPhone)
{
    $orderNo = trackOrderSanitizeNo($rawOrderNo);
    $phone   = normalizePhone($rawPhone);

    if ($orderNo <= 0 || $phone === '') {
        return ['success' => false, 'code' => 'invalid'];
    }

    $order = $db->query(
        "SELECT * FROM orders
         WHERE id = ?
           AND order_type = 'delivery'
           AND customer_phone = ?",
        [$orderNo, $phone]
    )->fetch();

    if (!$order) {
        // "Bulunamadı" ile "eşleşmedi" AYNI yanıttır; böylece siparişin
        // varlığı sızdırılmaz.
        return ['success' => false, 'code' => 'not_found'];
    }

    $items = $db->query(
        "SELECT oi.quantity, oi.price, p.name
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.id ASC",
        [$order['id']]
    )->fetchAll();

    return [
        'success'  => true,
        'order'    => $order,
        'items'    => $items,
        'settings' => getDeliverySettings($db),
    ];
}

/**
 * Takip sorgusu için hız sınırlaması.
 * 10 dakika içinde en fazla $maxAttempts deneme; aşılırsa false döner.
 */
function trackOrderCheckRateLimit($db, $maxAttempts = 10, $window = 600, $ip = null)
{
    $limit = new RateLimit($db);
    $limit->setWindow($window);
    $limit->setMaxAttempts($maxAttempts);
    return $limit->check('delivery_track_order', $ip);
}
