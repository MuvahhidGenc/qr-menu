<?php
/**
 * Web Adrese Sipariş - Sepet + Teslimat Adresi Formu
 * Ödeme: kapıda nakit / kapıda kart (parametre ile yönetilir)
 */
require_once __DIR__ . '/includes/config.php';

$db = new Database();

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$deliveryEnabled = isDeliveryEnabled($db);

// Erişim yalnızca web sipariş anahtarına bağlıdır (QR/masa anahtarından bağımsız).
if (!$deliveryEnabled) {
    header('Location: siparis.php');
    exit;
}

$deliverySettings = getDeliverySettings($db);
$hours = checkDeliveryHours($db);
$requiredFields = getRequiredDeliveryFields($db);
$paymentMethods = getAvailablePaymentMethods($db);
$minOrder = (float)($deliverySettings['delivery_min_order'] ?? 0);
$freeOver = (float)($deliverySettings['delivery_free_over'] ?? 0);
$prepareMinutes = (int)($deliverySettings['delivery_prepare_minutes'] ?? 45);
$cities = deliveryCityList();
$csrfToken = generateCSRFToken();

// --- Sepet (backend doğrulamalı) ---------------------------------------------
$cart = buildDeliveryCart($db);
$subtotal = $cart['subtotal'];
$deliveryFee = calculateDeliveryFee($deliverySettings, $subtotal);
$total = calculateDeliveryTotal($subtotal, 0, $deliveryFee);

$isRequired = function ($field) use ($requiredFields) {
    return in_array($field, $requiredFields, true);
};

// --- Önceki siparişten saklanan bilgiler (yalnızca bu tarayıcı oturumunda) ---
$saved = $_SESSION['delivery_customer'] ?? [];

// customer-header.php'deki hero gizlenir: bu sayfa da dv-store-bar ile aynı
// restoran adı/sloganı basıyor. siparis.php ile tutarlı olması için aynı
// davranış uygulanır (mobilde ~180px dikey alan kazanılır).
$hideCustomerHero = true;
include __DIR__ . '/includes/customer-header.php';
?>

<link href="assets/css/delivery.css" rel="stylesheet">
<script>
    window.DV_BASE_URL = '';
    window.DV_CART_ACTION_URL = 'ajax/delivery/cart_action.php';
    window.DV_CREATE_ORDER_URL = 'ajax/delivery/create_order.php';
    window.DV_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
</script>

<div class="dv-page">

    <div class="dv-store-bar">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1><i class="fas fa-receipt me-2"></i>Sipariş Bilgileri</h1>
                <p class="dv-sub">Adres ve ödeme bilgilerinizi girerek siparişinizi tamamlayın</p>
            </div>
            <a href="siparis.php" class="btn btn-sm btn-light">
                <i class="fas fa-plus me-1"></i>Ürün Ekle
            </a>
        </div>
    </div>

    <div class="container py-3">

        <?php if ($cart['error']): ?>
            <div class="dv-alert dv-alert-warning mb-3">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?= htmlspecialchars($cart['error']) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$hours['open']): ?>
            <div class="dv-alert dv-alert-warning mb-3">
                <i class="fas fa-clock"></i>
                <div><strong>Şu anda sipariş kabul edilmiyor.</strong><br><?= htmlspecialchars($hours['message']) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($minOrder > 0 && $subtotal < $minOrder && $subtotal > 0): ?>
            <div class="dv-alert dv-alert-warning mb-3">
                <i class="fas fa-coins"></i>
                <div>Minimum sipariş tutarı <strong><?= number_format($minOrder, 2, ',', '.') ?> ₺</strong>.
                    Sepetinizde <strong><?= number_format($subtotal, 2, ',', '.') ?> ₺</strong> bulunuyor.</div>
            </div>
        <?php endif; ?>

        <!-- ============ SEPET ============ -->
        <div class="dv-card">
            <h2 class="dv-card-title"><i class="fas fa-shopping-basket"></i>Sepetim (<?= getCartCount(DELIVERY_CART_KEY) ?>)</h2>

            <div id="dvCartList">
                <?php if (empty($cart['items'])): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-shopping-cart fa-2x d-block mb-2"></i>
                        Sepetiniz boş. <a href="siparis.php">Ürünlere gidin</a>.
                    </div>
                <?php else: ?>
                    <?php foreach ($cart['items'] as $item): ?>
                        <div class="dv-cart-item" data-dv-row="<?= $item['product_id'] ?>">
                            <?php if (!empty($item['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <div class="dv-product-img placeholder" style="width:58px;height:58px;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>

                            <div class="dv-ci-body">
                                <div class="dv-ci-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="dv-ci-price"><?= number_format($item['price'], 2, ',', '.') ?> ₺ / adet</div>
                                <div class="dv-qty mt-1">
                                    <button type="button" data-dv-dec="<?= $item['product_id'] ?>" aria-label="Azalt">&minus;</button>
                                    <span data-dv-qty="<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span>
                                    <button type="button" data-dv-inc="<?= $item['product_id'] ?>" aria-label="Arttır">+</button>
                                </div>
                            </div>

                            <div class="text-end">
                                <div class="dv-ci-total"><?= number_format($item['line_total'], 2, ',', '.') ?> ₺</div>
                                <button type="button" class="dv-btn-remove" data-dv-remove="<?= $item['product_id'] ?>"
                                        title="Sepetten çıkar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form id="dvOrderForm" novalidate autocomplete="on">

            <!-- ============ TESLİMAT ADRESİ ============ -->
            <div class="dv-card">
                <h2 class="dv-card-title"><i class="fas fa-map-marker-alt"></i>Teslimat Adresi</h2>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="dv-label" for="dvName">Ad <?= $isRequired('name') ? '<span class="req">*</span>' : '' ?></label>
                        <input type="text" class="dv-form-control" id="dvName" name="name" maxlength="100"
                               value="<?= htmlspecialchars($saved['name'] ?? '') ?>"
                               <?= $isRequired('name') ? 'required' : '' ?>>
                        <div class="dv-invalid" data-for="name"></div>
                    </div>

                    <div class="col-6">
                        <label class="dv-label" for="dvSurname">Soyad <?= $isRequired('surname') ? '<span class="req">*</span>' : '' ?></label>
                        <input type="text" class="dv-form-control" id="dvSurname" name="surname" maxlength="100"
                               value="<?= htmlspecialchars($saved['surname'] ?? '') ?>"
                               <?= $isRequired('surname') ? 'required' : '' ?>>
                        <div class="dv-invalid" data-for="surname"></div>
                    </div>

                    <div class="col-12">
                        <label class="dv-label" for="dvPhone">Telefon <?= $isRequired('phone') ? '<span class="req">*</span>' : '' ?></label>
                        <input type="tel" class="dv-form-control" id="dvPhone" name="phone" maxlength="20"
                               inputmode="tel" placeholder="0555 123 45 67"
                               value="<?= htmlspecialchars($saved['phone'] ?? '') ?>"
                               <?= $isRequired('phone') ? 'required' : '' ?>>
                        <div class="dv-hint">Kurye siparişinizi teslim ederken sizi arayabilir.</div>
                        <div class="dv-invalid" data-for="phone"></div>
                    </div>

                    <div class="col-6">
                        <label class="dv-label" for="dvCity">İl <?= $isRequired('city') ? '<span class="req">*</span>' : '' ?></label>
                        <select class="dv-form-select" id="dvCity" name="city" <?= $isRequired('city') ? 'required' : '' ?>>
                            <option value="">İl seçiniz</option>
                            <?php foreach ($cities as $cityName): ?>
                                <option value="<?= htmlspecialchars($cityName) ?>"
                                    <?= ($saved['city'] ?? '') === $cityName ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cityName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="dv-invalid" data-for="city"></div>
                    </div>

                    <div class="col-6">
                        <label class="dv-label" for="dvDistrict">İlçe <?= $isRequired('district') ? '<span class="req">*</span>' : '' ?></label>
                        <input type="text" class="dv-form-control" id="dvDistrict" name="district" maxlength="50"
                               value="<?= htmlspecialchars($saved['district'] ?? '') ?>"
                               <?= $isRequired('district') ? 'required' : '' ?>>
                        <div class="dv-invalid" data-for="district"></div>
                    </div>

                    <div class="col-12">
                        <label class="dv-label" for="dvNeighborhood">Mahalle / Köy <?= $isRequired('neighborhood') ? '<span class="req">*</span>' : '' ?></label>
                        <input type="text" class="dv-form-control" id="dvNeighborhood" name="neighborhood" maxlength="100"
                               value="<?= htmlspecialchars($saved['neighborhood'] ?? '') ?>"
                               <?= $isRequired('neighborhood') ? 'required' : '' ?>>
                        <div class="dv-invalid" data-for="neighborhood"></div>
                    </div>

                    <div class="col-12">
                        <label class="dv-label" for="dvAddress">Adres <?= $isRequired('address') ? '<span class="req">*</span>' : '' ?></label>
                        <textarea class="dv-form-control" id="dvAddress" name="address" rows="2" maxlength="500"
                                  placeholder="Mahalle, sokak, cadde ve benzeri adres bilgisi"
                                  <?= $isRequired('address') ? 'required' : '' ?>><?= htmlspecialchars($saved['address'] ?? '') ?></textarea>
                        <div class="dv-invalid" data-for="address"></div>
                    </div>

                    <div class="col-6">
                        <label class="dv-label" for="dvBuildingNo">Bina No</label>
                        <input type="text" class="dv-form-control" id="dvBuildingNo" name="building_no" maxlength="20"
                               value="<?= htmlspecialchars($saved['building_no'] ?? '') ?>">
                    </div>

                    <div class="col-6">
                        <label class="dv-label" for="dvApartmentNo">Daire No</label>
                        <input type="text" class="dv-form-control" id="dvApartmentNo" name="apartment_no" maxlength="20"
                               value="<?= htmlspecialchars($saved['apartment_no'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="dv-label" for="dvNote">Adres Tarifi / Açıklama</label>
                        <textarea class="dv-form-control" id="dvNote" name="note" rows="2" maxlength="1000"
                                  placeholder="Örn: Kat 3, zili çalmayın, sarı binanın yanı"><?= htmlspecialchars($saved['note'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="dv-label" for="dvOrderNote">Sipariş Notu</label>
                        <input type="text" class="dv-form-control" id="dvOrderNote" name="order_note" maxlength="500"
                               placeholder="Örn: Az acılı, ekmeği kıta kıta doğrayın">
                    </div>
                </div>
            </div>

            <!-- ============ ÖDEME ============ -->
            <div class="dv-card">
                <h2 class="dv-card-title"><i class="fas fa-credit-card"></i>Ödeme Yöntemi</h2>
                <?php foreach ($paymentMethods as $slug => $label): ?>
                    <label class="dv-pay-option <?= $slug === array_key_first($paymentMethods) ? 'active' : '' ?>"
                           data-value="<?= htmlspecialchars($slug) ?>">
                        <i class="fas <?= $slug === 'cash' ? 'fa-money-bill-wave' : ($slug === 'card' ? 'fa-credit-card' : 'fa-globe') ?>"></i>
                        <span>
                            <strong><?= htmlspecialchars($label) ?></strong><br>
                            <small class="text-muted">
                                <?= $slug === 'cash' ? 'Siparişinizi teslim alırken nakit ödersiniz.'
                                    : ($slug === 'card' ? 'Kurye POS cihazı ile teslimatta tahsil edilir.'
                                    : 'Sipariş anında online ödeme yapılır.') ?>
                            </small>
                        </span>
                        <input type="radio" name="payment_method" value="<?= htmlspecialchars($slug) ?>"
                               <?= $slug === array_key_first($paymentMethods) ? 'checked' : '' ?>>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- ============ ÖZET ============ -->
            <div class="dv-card">
                <h2 class="dv-card-title"><i class="fas fa-calculator"></i>Ödeme Özeti</h2>

                <div class="dv-summary-row">
                    <span>Ara Toplam</span>
                    <span class="dv-val"><?= number_format($subtotal, 2, ',', '.') ?> ₺</span>
                </div>
                <div class="dv-summary-row">
                    <span>İndirim</span>
                    <span class="dv-val"><?= number_format(0, 2, ',', '.') ?> ₺</span>
                </div>
                <div class="dv-summary-row">
                    <span>Teslimat Ücreti
                        <?php if ($freeOver > 0): ?>
                            <small class="text-muted">(&gt;= <?= number_format($freeOver, 2, ',', '.') ?> ₺ ücretsiz)</small>
                        <?php endif; ?>
                    </span>
                    <span class="dv-val" id="dvFeeText"><?= number_format($deliveryFee, 2, ',', '.') ?> ₺</span>
                </div>
                <div class="dv-summary-row total">
                    <span>Genel Toplam</span>
                    <span class="dv-val" id="dvTotalText"><?= number_format($total, 2, ',', '.') ?> ₺</span>
                </div>

                <?php if ($prepareMinutes > 0): ?>
                    <div class="dv-alert dv-alert-info mt-3">
                        <i class="fas fa-clock"></i>
                        <div>Tahmini teslimat süresi: <strong><?= $prepareMinutes ?> dakika</strong></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn dv-btn-primary mt-3" id="dvSubmit"
                        <?= (empty($cart['items']) || !$hours['open']) ? 'disabled' : '' ?>>
                    <i class="fas fa-check-circle me-2"></i>Siparişi Tamamla
                </button>
            </div>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/delivery.js"></script>
<script>
/* Sepetten ürün silinince satırı DOM'dan kaldır ve özeti güncelle */
window.dvCartRowRemoved = function (productId, res) {
    var row = document.querySelector('[data-dv-row="' + productId + '"]');
    if (row) row.remove();

    if (res.subtotal !== undefined) {
        document.querySelector('#dvFeeText').textContent = dvMoney(res.delivery_fee);
        document.querySelector('#dvTotalText').textContent = dvMoney(res.total);
    }

    var list = document.getElementById('dvCartList');
    if (!list.querySelector('.dv-cart-item')) {
        list.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-shopping-cart fa-2x d-block mb-2"></i>Sepetiniz boş. <a href="siparis.php">Ürünlere gidin</a>.</div>';
    }

    DV.refreshCartBar();
};

function dvMoney(v) {
    return dvFmt(v) + ' ₺';
}

function dvFmt(v) {
    var n = parseFloat(v) || 0;
    return n.toFixed(2).replace('.', ',');
}

(function () {
    var form = document.getElementById('dvOrderForm');
    var submit = document.getElementById('dvSubmit');
    if (!form) return;

    // Telefon maskesı
    var phone = document.getElementById('dvPhone');
    if (phone) {
        phone.addEventListener('input', function () {
            var digits = phone.value.replace(/\D/g, '').slice(0, 11);
            if (digits.length > 0 && digits[0] !== '0') digits = '0' + digits;
            phone.value = digits;
        });
    }

    // Hata mesajlarını temizle
    form.addEventListener('input', function (e) {
        var field = e.target.getAttribute('name');
        if (!field) return;
        e.target.classList.remove('is-invalid');
        var box = form.querySelector('.dv-invalid[data-for="' + field + '"]');
        if (box) box.classList.remove('show');
    });

    // Ödeme yöntemi seçimi
    form.querySelectorAll('.dv-pay-option').forEach(function (el) {
        el.addEventListener('click', function () { DV.selectPayment(el.dataset.value); });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Temel geçerlilik kontrolü
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            form.querySelector(':invalid').focus();
            Swal.fire({ icon: 'warning', title: 'Eksik Bilgi', text: 'Lütfen işaretli alanları doldurun.', confirmButtonText: 'Tamam' });
            return;
        }

        var payload = new FormData(form);
        payload.append('csrf_token', window.DV_CSRF_TOKEN);

        var original = submit.innerHTML;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';

        fetch(window.DV_CREATE_ORDER_URL, {
            method: 'POST',
            body: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    // order_number '#44' biçiminde döner; sayfa saf tam sayı bekliyor
                    window.location.href = 'siparis-basarili.php?no=' + encodeURIComponent(res.order_id);
                    return;
                }

                submit.disabled = false;
                submit.innerHTML = original;

                if (res.errors) {
                    Object.keys(res.errors).forEach(function (field) {
                        var input = form.querySelector('[name="' + field + '"]');
                        if (input) input.classList.add('is-invalid');
                        var box = form.querySelector('.dv-invalid[data-for="' + field + '"]');
                        if (box) {
                            box.textContent = res.errors[field];
                            box.classList.add('show');
                        }
                    });
                    var firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                Swal.fire({ icon: 'error', title: 'Sipariş Alınamadı', text: res.message || 'Bir hata oluştu.', confirmButtonText: 'Tamam' });
            })
            .catch(function () {
                submit.disabled = false;
                submit.innerHTML = original;
                Swal.fire({ icon: 'error', title: 'Bağlantı Hatası', text: 'Sunucuya ulaşılamadı. Lütfen tekrar deneyin.', confirmButtonText: 'Tamam' });
            });
    });
})();
</script>
</body>
</html>
