<?php
/**
 * Web Adrese Sipariş - Ortak Sipariş Görünümü (PARÇA)
 *
 * Hem siparis-basarili.php hem siparis-takip.php tarafından include edilir.
 * GEREKLİ DEĞİŞKEN: $order  (orders tablosundan gelen satır)
 * İsteğe bağlı: $prepareMinutes, $showActions (bool)
 *
 * Güvenlik: Bu parça siparişi doğrulamaz; doğrulama çağıran sayfada yapılır.
 * Çağıran sayfa $order'ı SADECE yetkili sorgu sonucu olarak almalıdır.
 */

if (!isset($order) || empty($order) || !is_array($order)) {
    return;
}

$orderId        = (int)$order['id'];
$statusText     = deliveryStatusLabel($order['status']);
$paymentLabels  = deliveryPaymentMethods();
$paymentText    = $paymentLabels[$order['payment_method'] ?? ''] ?? '-';
$prepareMinutes = $prepareMinutes ?? 45;
$customerName   = trim(($order['customer_name'] ?? '') . ' ' . ($order['customer_surname'] ?? ''));
$showActions    = $showActions ?? true;
$isCancelled    = $order['status'] === 'cancelled';

// Zaman çizelgesi
$timeline = [
    'pending'    => 'Sipariş Alındı',
    'confirmed'  => 'Onaylandı',
    'preparing'  => 'Hazırlanıyor',
    'ready'      => 'Hazır',
    'on_the_way' => 'Yola Çıktı',
    'delivered'  => 'Teslim Edildi',
];
$currentIndex = array_search($order['status'], array_keys($timeline), true);
if ($currentIndex === false) {
    $currentIndex = 0;
}
$isFinished = in_array($order['status'], ['delivered', 'completed', 'cancelled'], true);
$totalItems = array_sum(array_map(static fn($i) => (int)$i['quantity'], $items));
?>

<!-- Sipariş durumu -->
<div class="dv-card mt-3">
    <h3 class="dv-card-title"><i class="fas fa-truck"></i>Sipariş Durumu</h3>
    <ul class="dv-timeline">
        <?php foreach ($timeline as $i => $label): ?>
            <?php $isDone = !$isCancelled && $i < $currentIndex; ?>
            <li class="<?= $isDone ? 'done' : '' ?><?= $i === $currentIndex ? ' current' : '' ?>">
                <?= htmlspecialchars($label) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="text-muted small mt-2">
        Durum: <strong><?= htmlspecialchars($statusText) ?></strong>
        <?php if ($prepareMinutes > 0 && !$isFinished): ?>
            &middot; Tahmini süre: <?= (int)$prepareMinutes ?> dk
        <?php endif; ?>
    </div>
</div>

<?php if ($isCancelled): ?>
    <div class="dv-alert dv-alert-danger mt-3">
        <i class="fas fa-ban"></i>
        <div>Bu sipariş iptal edildi. Bilgi için işletmeyi arayabilirsiniz.</div>
    </div>
<?php endif; ?>

<!-- Teslimat bilgileri -->
<div class="dv-card">
    <h3 class="dv-card-title"><i class="fas fa-map-marker-alt"></i>Teslimat Bilgileri</h3>
    <div class="small">
        <div class="mb-1"><span class="text-muted">Ad Soyad:</span> <strong><?= htmlspecialchars($customerName) ?></strong></div>
        <div class="mb-1"><span class="text-muted">Telefon:</span> <strong><?= htmlspecialchars($order['customer_phone'] ?? '-') ?></strong></div>
        <div class="mb-1"><span class="text-muted">Adres:</span> <?= htmlspecialchars(formatDeliveryAddress($order)) ?></div>
        <?php if (!empty($order['delivery_note'])): ?>
            <div class="mb-1"><span class="text-muted">Adres Tarifi:</span> <?= htmlspecialchars($order['delivery_note']) ?></div>
        <?php endif; ?>
        <div><span class="text-muted">Ödeme:</span> <strong><?= htmlspecialchars($paymentText) ?></strong></div>
    </div>
</div>

<!-- Sipariş özeti -->
<div class="dv-card">
    <h3 class="dv-card-title"><i class="fas fa-receipt"></i>Sipariş Özeti</h3>

    <?php foreach ($items as $item): ?>
        <div class="dv-summary-row">
            <span><?= (int)$item['quantity'] ?> x <?= htmlspecialchars($item['name']) ?></span>
            <span class="dv-val"><?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?> ₺</span>
        </div>
    <?php endforeach; ?>

    <div class="dv-summary-row mt-2" style="border-top:1px dashed #dfe2e7;padding-top:10px;">
        <span>Ara Toplam</span>
        <span class="dv-val"><?= number_format((float)$order['subtotal'], 2, ',', '.') ?> ₺</span>
    </div>
    <div class="dv-summary-row">
        <span>Teslimat Ücreti</span>
        <span class="dv-val"><?= number_format((float)$order['delivery_fee'], 2, ',', '.') ?> ₺</span>
    </div>
    <div class="dv-summary-row total">
        <span>Genel Toplam</span>
        <span class="dv-val"><?= number_format((float)$order['total_amount'], 2, ',', '.') ?> ₺</span>
    </div>

    <div class="text-muted small mt-2 pt-2" style="border-top:1px dashed #dfe2e7;">
        <?= (int)$totalItems ?> ürün &middot;
        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
    </div>
</div>

<?php if ($showActions): ?>
    <a href="siparis.php" class="btn dv-btn-outline w-100 mb-2">
        <i class="fas fa-utensils me-2"></i>Yeni Sipariş Ver
    </a>
    <button type="button" class="btn dv-btn-primary" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Özeti Yazdır
    </button>
<?php endif; ?>
