<?php
/**
 * Web Adrese Sipariş - Detaylı Ayarlar (Teslimat Ücreti, Ödeme, Zorunlu Alanlar, Saatler)
 *
 * Ana anahtar: admin/system_parameters.php -> "Web Üzerinden Adrese Sipariş"
 * Bu sayfa yalnızca detay parametrelerini yönetir.
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!hasPermission('settings.view')) {
    header('Location: dashboard.php');
    exit();
}

$canEdit = hasPermission('settings.edit');
$db = new Database();

$fieldLabels = deliveryFieldList();
$allFields = array_keys($fieldLabels);
$paymentLabels = deliveryPaymentMethods();

// Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_delivery_settings'])) {
    if (!$canEdit) {
        $_SESSION['message'] = 'Bu ayarı düzenleme yetkiniz bulunmuyor.';
        $_SESSION['message_type'] = 'error';
        header('Location: delivery_settings.php');
        exit();
    }

    try {
        $settings = [];

        // --- Ödeme yöntemleri ---
        $methods = [];
        foreach (array_keys($paymentLabels) as $slug) {
            if (isset($_POST['payment_' . $slug])) {
                $methods[] = $slug;
            }
        }
        if (empty($methods)) {
            $methods = ['cash'];
        }

        // --- Zorunlu alanlar (ad/soyad/telefon daima zorunlu kalır) ---
        $required = [];
        foreach ($allFields as $field) {
            if (isset($_POST['req_' . $field])) {
                $required[] = $field;
            }
        }
        foreach (['name', 'surname', 'phone'] as $mandatory) {
            if (!in_array($mandatory, $required, true)) {
                $required[] = $mandatory;
            }
        }

        // --- Çalışma günleri ---
        $days = [];
        for ($d = 1; $d <= 7; $d++) {
            if (isset($_POST['day_' . $d])) {
                $days[] = $d;
            }
        }

        // --- Sayısal / saat doğrulama ---
        $fee = (float)str_replace(',', '.', $_POST['delivery_fee'] ?? '0');
        $freeOver = (float)str_replace(',', '.', $_POST['delivery_free_over'] ?? '0');
        $minOrder = (float)str_replace(',', '.', $_POST['delivery_min_order'] ?? '0');
        $prepare = (int)($_POST['delivery_prepare_minutes'] ?? 45);
        $openTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_POST['delivery_open_time'] ?? '') ? $_POST['delivery_open_time'] : '10:00';
        $closeTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_POST['delivery_close_time'] ?? '') ? $_POST['delivery_close_time'] : '23:00';

        $settings = [
            'delivery_fee'                 => number_format(max(0, $fee), 2, '.', ''),
            'delivery_free_over'           => number_format(max(0, $freeOver), 2, '.', ''),
            'delivery_min_order'           => number_format(max(0, $minOrder), 2, '.', ''),
            'delivery_payment_methods'     => implode(',', $methods),
            'delivery_required_fields'     => implode(',', $required),
            'delivery_prepare_minutes'     => max(0, $prepare),
            'delivery_active_days'         => implode(',', $days),
            'delivery_open_time'           => $openTime,
            'delivery_close_time'          => $closeTime,
            'delivery_hour_check'          => isset($_POST['delivery_hour_check']) ? '1' : '0',
        ];

        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }

        $_SESSION['message'] = 'Web Adrese Sipariş ayarları kaydedildi.';
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['message'] = 'Hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }

    header('Location: delivery_settings.php');
    exit();
}

$current = getDeliverySettings($db);
$enabled = isDeliveryEnabled($db);
$requiredFields = getRequiredDeliveryFields($db);
$availableMethods = getAvailablePaymentMethods($db);
$activeDays = array_filter(array_map('trim', explode(',', (string)$current['delivery_active_days'])));
$dayNames = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];

include 'navbar.php';
?>

<div class="container-fluid px-4 py-4">
    <style>
        .dv-settings-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.05);
            background: #fff;
            margin-bottom: 1.5rem;
        }
        .dv-settings-card .card-header {
            background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%);
            color: #fff;
            border-radius: 16px 16px 0 0 !important;
            font-weight: 600;
        }
        .dv-settings-card .card-body { padding: 1.75rem; }
        .dv-field-label { font-weight: 600; font-size: .9rem; margin-bottom: 4px; }
        .dv-field-hint { font-size: .78rem; color: #78808c; }
        .dv-day-btn { min-width: 100px; }
    </style>

    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1"><i class="fas fa-motorcycle text-danger me-2"></i>Web Adrese Sipariş Ayarları</h3>
            <p class="text-muted mb-0 small">Teslimat ücreti, ödeme yöntemleri, zorunlu alanlar ve çalışma saatleri</p>
        </div>
        <div>
            <?php if ($enabled): ?>
                <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Sistem Aktif</span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6"><i class="fas fa-ban me-1"></i>Sistem Kapalı</span>
                <?php if (isSuperAdmin() || hasPermission('settings.system_parameters')): ?>
                <a href="system_parameters.php" class="btn btn-sm btn-primary ms-2">
                    <i class="fas fa-power-off me-1"></i>Parametreyi Aç
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= ($_SESSION['message_type'] ?? 'info') ?> alert-dismissible fade show">
            <strong><?= htmlspecialchars($_SESSION['message']) ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <?php if (!$enabled): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Web Adrese Sipariş kapalı.</strong> Bu sayfadaki ayarlar kaydedilse bile müşteri web sipariş
            sayfasına erişemeyecektir. Açmak için
            <?php if (isSuperAdmin() || hasPermission('settings.system_parameters')): ?>
            <a href="system_parameters.php">Sistem Parametreleri</a> &rarr; <em>Web Adrese Sipariş Sistemi</em>.
            <?php else: ?>
            <em>Web Adrese Sipariş Sistemi</em> sistem parametresini bir yöneticinin açması gerekiyor.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-4">

            <!-- ============ TESLİMAT ============ -->
            <div class="col-lg-6">
                <div class="card dv-settings-card">
                    <div class="card-header">
                        <i class="fas fa-truck me-2"></i>Teslimat Ücreti
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="dv-field-label" for="delivery_fee">Sabit Teslimat Ücreti (₺)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="delivery_fee"
                                   name="delivery_fee" value="<?= htmlspecialchars($current['delivery_fee']) ?>"
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <div class="dv-field-hint">0.00 yazarsanız teslimat ücretsiz olur.</div>
                        </div>

                        <div class="mb-3">
                            <label class="dv-field-label" for="delivery_free_over">Şu Tutarın Üzerinde Ücretsiz (₺)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="delivery_free_over"
                                   name="delivery_free_over" value="<?= htmlspecialchars($current['delivery_free_over']) ?>"
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <div class="dv-field-hint">0.00 ise ücretsiz teslimat uygulanmaz.</div>
                        </div>

                        <div class="mb-0">
                            <label class="dv-field-label" for="delivery_min_order">Minimum Sipariş Tutarı (₺)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="delivery_min_order"
                                   name="delivery_min_order" value="<?= htmlspecialchars($current['delivery_min_order']) ?>"
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <div class="dv-field-hint">İleride kullanılmak üzere; 0.00 yazarsanız uygulanmaz.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ ÖDEME ============ -->
            <div class="col-lg-6">
                <div class="card dv-settings-card">
                    <div class="card-header" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color:#0b3d2c;">
                        <i class="fas fa-credit-card me-2"></i>Ödeme Yöntemleri
                    </div>
                    <div class="card-body">
                        <?php foreach ($paymentLabels as $slug => $label): ?>
                            <?php $isOn = isset($availableMethods[$slug]); ?>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="payment_<?= htmlspecialchars($slug) ?>" name="payment_<?= htmlspecialchars($slug) ?>"
                                       <?= $isOn ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="payment_<?= htmlspecialchars($slug) ?>">
                                    <strong><?= htmlspecialchars($label) ?></strong>
                                    <br><small class="text-muted">
                                        <?= $slug === 'cash' ? 'Kurye teslimde nakit tahsil eder.'
                                            : ($slug === 'card' ? 'Kurye POS cihazı ile tahsil eder.'
                                            : 'Online ödeme (ileride entegrasyon için).') ?>
                                    </small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="alert alert-info mb-0 py-2 small">
                            <i class="fas fa-info-circle me-1"></i>
                            En az bir ödeme yöntemi seçilmelidir. Kapıda ödeme yöntemleri
                            <code>payments</code> tablosuna yazılmaz; sipariş kapıda tahsil edilir.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ ZORUNLU ALANLAR ============ -->
            <div class="col-lg-6">
                <div class="card dv-settings-card">
                    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-list-check me-2"></i>Adres Formu - Zorunlu Alanlar
                    </div>
                    <div class="card-body">
                        <?php foreach ($fieldLabels as $field => $label): ?>
                            <?php $mandatory = in_array($field, ['name', 'surname', 'phone'], true); ?>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="req_<?= htmlspecialchars($field) ?>" name="req_<?= htmlspecialchars($field) ?>"
                                       <?= in_array($field, $requiredFields, true) ? 'checked' : '' ?>
                                       <?= $mandatory ? 'disabled' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="req_<?= htmlspecialchars($field) ?>">
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($mandatory): ?>
                                        <span class="badge bg-secondary ms-1">Zorunlu</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <div class="alert alert-secondary mb-0 py-2 small mt-3">
                            <i class="fas fa-lock me-1"></i>
                            Ad, Soyad ve Telefon teslimat için her zaman zorunludur ve kapatılamaz.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ ÇALIŞMA SAATLERİ ============ -->
            <div class="col-lg-6">
                <div class="card dv-settings-card">
                    <div class="card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color:#5a3d00;">
                        <i class="fas fa-clock me-2"></i>Çalışma Saatleri
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="delivery_hour_check" name="delivery_hour_check"
                                   <?= $current['delivery_hour_check'] == '1' ? 'checked' : '' ?>
                                   <?= $canEdit ? '' : 'disabled' ?>>
                            <label class="form-check-label" for="delivery_hour_check">
                                <strong>Sipariş saatlerini uygula</strong>
                                <br><small class="text-muted">Kapalıyken 7/24 sipariş alınır.</small>
                            </label>
                        </div>

                        <label class="dv-field-label">Sipariş Alınan Günler</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($dayNames as $num => $dayName): ?>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" id="day_<?= $num ?>" name="day_<?= $num ?>"
                                           <?= in_array((string)$num, $activeDays, true) ? 'checked' : '' ?>
                                           <?= $canEdit ? '' : 'disabled' ?>>
                                    <label class="form-check-label" for="day_<?= $num ?>"><?= htmlspecialchars($dayName) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="dv-field-label" for="delivery_open_time">Açılış</label>
                                <input type="time" class="form-control" id="delivery_open_time" name="delivery_open_time"
                                       value="<?= htmlspecialchars($current['delivery_open_time']) ?>"
                                       <?= $canEdit ? '' : 'disabled' ?>>
                            </div>
                            <div class="col-6">
                                <label class="dv-field-label" for="delivery_close_time">Kapanış</label>
                                <input type="time" class="form-control" id="delivery_close_time" name="delivery_close_time"
                                       value="<?= htmlspecialchars($current['delivery_close_time']) ?>"
                                       <?= $canEdit ? '' : 'disabled' ?>>
                            </div>
                            <div class="col-12">
                                <label class="dv-field-label" for="delivery_prepare_minutes">Tahmini Teslimat Süresi (dakika)</label>
                                <input type="number" min="0" class="form-control" id="delivery_prepare_minutes"
                                       name="delivery_prepare_minutes" value="<?= (int)$current['delivery_prepare_minutes'] ?>"
                                       <?= $canEdit ? '' : 'disabled' ?>>
                                <div class="dv-field-hint">Müşteriye tahmini süre olarak gösterilir. 0 yazarsanız gösterilmez.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Kaydet -->
        <div class="card dv-settings-card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Değişiklikler kaydettiğiniz anda müşteri sipariş sayfasına yansır.
                </div>
                <div class="d-flex gap-2">
                    <?php if (isSuperAdmin() || hasPermission('settings.system_parameters')): ?>
                    <a href="system_parameters.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Geri
                    </a>
                    <?php endif; ?>
                    <button type="submit" name="save_delivery_settings" class="btn btn-success" <?= $canEdit ? '' : 'disabled' ?>>
                        <i class="fas fa-save me-1"></i>Ayarları Kaydet
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

</body>
</html>
