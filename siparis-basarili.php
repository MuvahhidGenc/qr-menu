<?php
/**
 * Web Adrese Sipariş - Sipariş Alındı Ekranı
 * ?no=1024  (Sipariş numarası)
 *
 * Müşteri yalnızca kendi oturumunda saklanan delivery_token ile siparişini
 * görebilir. Telefon numarası ile sorgulama YAPILMAZ (KVKK / IDOR koruması).
 */
require_once __DIR__ . '/includes/config.php';

$db = new Database();

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$orderNumber = getSecureInt('no', 0);
$deliveryEnabled = isDeliveryEnabled($db);
$customerAccess = isset($settings['system_customer_access']) && $settings['system_customer_access'] == '1';

// Token doğrulaması: session'daki son sipariş ile eşleşmeli
$sessionToken = $_SESSION['delivery_last_order']['token'] ?? '';
$order = null;

if ($orderNumber > 0 && $sessionToken !== '') {
    $order = $db->query(
        "SELECT * FROM orders WHERE id = ? AND order_type = 'delivery' AND delivery_token = ?",
        [$orderNumber, $sessionToken]
    )->fetch();
}

if (!$order) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sipariş Bulunamadı</title>
        <link href="assets/css/delivery.css" rel="stylesheet">
    </head>
    <body class="dv-page">
        <div class="container py-4">
            <div class="dv-empty">
                <i class="fas fa-search"></i>
                <h5>Sipariş bilgisi bulunamadı</h5>
                <p class="mb-3">Sipariş özeti yalnızca siparişi verdiğiniz cihazda görüntülenebilir.</p>
                <a href="siparis.php" class="btn dv-btn-primary" style="width:auto">Menüye Git</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Sipariş kalemleri
$items = $db->query(
    "SELECT oi.quantity, oi.price, p.name
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC",
    [$order['id']]
)->fetchAll();

$prepareMinutes = (int)(getDeliverySettings($db)['delivery_prepare_minutes'] ?? 45);

include __DIR__ . '/includes/customer-header.php';
?>

<link href="assets/css/delivery.css" rel="stylesheet">

<div class="dv-page">
    <div class="dv-success-wrap">

        <div class="text-center">
            <div class="dv-success-icon"><i class="fas fa-check"></i></div>
            <h2 class="fw-bold mb-1">Siparişiniz Alındı</h2>
            <p class="text-muted mb-2">Siparişiniz işletmeye iletildi.</p>
            <div class="dv-order-no"><?= htmlspecialchars('#' . $order['id']) ?></div>
        </div>

        <?php include __DIR__ . '/includes/delivery-order-view.php'; ?>

        <a href="siparis-takip.php" class="btn dv-btn-outline w-100 mb-2">
            <i class="fas fa-search me-2"></i>Siparişimi Sorgula
        </a>

    </div>
</div>

</body>
</html>
