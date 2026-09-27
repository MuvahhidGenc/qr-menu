<?php
/**
 * Web Adrese Sipariş - Sipariş Sorgulama / Takip Sayfası
 *
 * Müşteri sipariş numarası + telefon numarası ile siparişini sorgular.
 *
 * Güvenlik:
 *   - Sipariş numarası TEK BAŞINA yetmez, kayıtlı telefon ile eşleşmelidir.
 *   - Doğrulama sunucuda yapılır (ajax/delivery/track_order.php).
 *   - Sorgulama, web sipariş modu KAPALIYKEN de çalışır; müşteri daha önce
 *     verdiği siparişini takip edebilmelidir.
 */
require_once __DIR__ . '/includes/config.php';

$db = new Database();

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$csrfToken = generateCSRFToken();

// Son sorgulanan numarayı hatırlat (konfor; doğrulama yine sunucuda)
$lastOrderNo = getSecureInt('no', 0);

// Telefon alanı için kayıtlı son değer (oturumden)
$savedPhone = '';
if (!empty($_SESSION['delivery_customer']['phone'])) {
    $savedPhone = $_SESSION['delivery_customer']['phone'];
}

include __DIR__ . '/includes/customer-header.php';
?>

<link href="assets/css/delivery.css" rel="stylesheet">

<div class="dv-page">
    <div class="dv-success-wrap">

        <div class="text-center">
            <h2 class="fw-bold mb-1">Sipariş Sorgulama</h2>
            <p class="text-muted mb-3">Sipariş numaranız ve telefonunuzla siparişinizi takip edin.</p>
        </div>

        <!-- ============ SORGU FORMU ============ -->
        <div class="dv-card">
            <h3 class="dv-card-title"><i class="fas fa-search"></i>Sipariş Bilgileri</h3>

            <form id="dvTrackForm" novalidate autocomplete="on">
                <div class="mb-3">
                    <label class="dv-label" for="dvTrackNo">Sipariş No <span class="req">*</span></label>
                    <input type="text" class="dv-form-control" id="dvTrackNo" name="order_no"
                           inputmode="numeric" placeholder="Örn: 128" maxlength="12" required>
                    <div class="dv-hint">Siparişinizi aldıktan sonra size verilen numarayı girin (başında # yok).</div>
                    <div class="dv-invalid" data-for="order_no"></div>
                </div>

                <div class="mb-3">
                    <label class="dv-label" for="dvTrackPhone">Telefon <span class="req">*</span></label>
                    <input type="tel" class="dv-form-control" id="dvTrackPhone" name="phone"
                           inputmode="tel" placeholder="0555 123 45 67" maxlength="20" required
                           value="<?= htmlspecialchars($savedPhone) ?>">
                    <div class="dv-hint">Siparişi verdiğiniz telefon numarası.</div>
                    <div class="dv-invalid" data-for="phone"></div>
                </div>

                <button type="submit" class="btn dv-btn-primary w-100" id="dvTrackBtn">
                    <i class="fas fa-search me-2"></i>Siparişi Sorgula
                </button>
            </form>
        </div>

        <!-- ============ SONUÇ ============ -->
        <div id="dvTrackResult"></div>

        <a href="siparis.php" class="btn dv-btn-outline w-100 mt-3">
            <i class="fas fa-utensils me-2"></i>Menüye Dön
        </a>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    'use strict';

    var form   = document.getElementById('dvTrackForm');
    var result = document.getElementById('dvTrackResult');
    var btn    = document.getElementById('dvTrackBtn');
    var inputNo = document.getElementById('dvTrackNo');

    <?php if ($lastOrderNo > 0): ?>
    inputNo.value = '<?= $lastOrderNo ?>';
    <?php endif; ?>

    // Sipariş numarasından baştaki # ve olası boşlukları temizle
    inputNo.addEventListener('blur', function () {
        var v = this.value.replace(/[^0-9]/g, '');
        if (v) { this.value = v; }
    });

    function clearErrors() {
        form.querySelectorAll('.dv-invalid').forEach(function (el) {
            el.textContent = '';
            el.classList.remove('show');
        });
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function showError(message) {
        result.innerHTML =
            '<div class="dv-alert dv-alert-danger mt-3">' +
                '<i class="fas fa-circle-exclamation"></i><div>' + message + '</div>' +
            '</div>';
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // SweetAlert2 yerine gömülü uyarı kullan (CDN yüklenemezse de çalışır)
    function notifyError(text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Sipariş Bulunamadı',
                text: text,
                confirmButtonText: 'Tamam'
            });
            return;
        }
        showError(text);
    }

    function render(res) {
        var head =
            '<div class="dv-card mt-3">' +
                '<div class="text-center mb-3">' +
                    '<div class="dv-order-no">#' + res.order_id + '</div>' +
                    '<div class="text-muted small mt-1">Sipariş Durumu</div>' +
                    '<div class="mt-2"><span class="badge ' + (res.is_cancelled ? 'bg-danger' : (res.is_finished ? 'bg-success' : 'bg-primary')) + ' fs-6">' +
                        res.status_label +
                    '</span></div>' +
                '</div>' +
                res.html +
            '</div>';

        // Sunucu $showActions=false ile butonlari uretmez; JS'de temizleme gerekmez.

        result.innerHTML =
            '<div class="dv-alert dv-alert-success mt-3">' +
                '<i class="fas fa-check-circle"></i><div>' + res.message + '</div>' +
            '</div>' + head;

        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var payload = new FormData(form);
        payload.append('csrf_token', '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>');

        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sorgulanıyor...';

        fetch('ajax/delivery/track_order.php', {
            method: 'POST',
            body: payload,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btn.disabled = false;
                btn.innerHTML = original;

                if (res.success) {
                    render(res);
                    return;
                }

                notifyError(res.message || 'Bir hata oluştu.');
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = original;
                notifyError('Sunucuya ulaşılamadı. Lütfen tekrar deneyin.');
            });
    });
})();
</script>

</body>
</html>
