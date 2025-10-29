<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Sadece Süper Admin erişebilir
if (!isSuperAdmin()) {
    header('Location: dashboard.php?error=unauthorized');
    exit();
}

$db = new Database();

// Parametreleri kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_parameters'])) {
    try {
        $db->beginTransaction();
        
        // Tüm parametreleri güncelle
        $parameters = [
            'system_qr_menu_enabled' => isset($_POST['qr_menu_enabled']) ? '1' : '0',
            'system_barcode_sales_enabled' => isset($_POST['barcode_sales_enabled']) ? '1' : '0',
            'system_accept_qr_orders' => isset($_POST['accept_qr_orders']) ? '1' : '0',
            'system_table_management' => isset($_POST['table_management']) ? '1' : '0',
            'system_stock_tracking' => isset($_POST['stock_tracking']) ? '1' : '0',
            'system_multi_payment' => isset($_POST['multi_payment']) ? '1' : '0',
            'system_reservation_enabled' => isset($_POST['reservation_enabled']) ? '1' : '0',
            // QR Menü Alt Modüller
            'system_qr_tables_visible' => isset($_POST['qr_tables_visible']) ? '1' : '0',
            'system_qr_orders_visible' => isset($_POST['qr_orders_visible']) ? '1' : '0',
            'system_qr_kitchen_visible' => isset($_POST['qr_kitchen_visible']) ? '1' : '0',
            'system_qr_reservations_visible' => isset($_POST['qr_reservations_visible']) ? '1' : '0',
            'system_customer_access' => isset($_POST['customer_access']) ? '1' : '0',
            // Stok Yönetimi Alt Modül
            'system_stock_management_visible' => isset($_POST['stock_management_visible']) ? '1' : '0'
        ];
        
        foreach ($parameters as $key => $value) {
            $db->query(
                "INSERT INTO settings (setting_key, setting_value) 
                 VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }
        
        $db->commit();
        
        $_SESSION['message'] = 'Sistem parametreleri başarıyla güncellendi.';
        $_SESSION['message_type'] = 'success';
        
        header('Location: system_parameters.php');
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = 'Hata: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}

// Mevcut parametreleri çek
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'system_%'");
$current_params = [];
while ($row = $stmt->fetch()) {
    $current_params[$row['setting_key']] = $row['setting_value'];
}

// Varsayılan değerler
$defaults = [
    'system_qr_menu_enabled' => '1',
    'system_barcode_sales_enabled' => '0',
    'system_accept_qr_orders' => '1',
    'system_table_management' => '1',
    'system_stock_tracking' => '0',
    'system_multi_payment' => '1',
    'system_reservation_enabled' => '1',
    // QR Menü Alt Modüller
    'system_qr_tables_visible' => '1',
    'system_qr_orders_visible' => '1',
    'system_qr_kitchen_visible' => '1',
    'system_qr_reservations_visible' => '1',
    'system_customer_access' => '1',
    // Stok Yönetimi Alt Modül
    'system_stock_management_visible' => '1'
];

foreach ($defaults as $key => $value) {
    if (!isset($current_params[$key])) {
        $current_params[$key] = $value;
    }
}

?>
<?php include 'navbar.php'; ?>

<style>
/* Modern Gradient Backgrounds */
.gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.gradient-warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

/* Modern Card Styling */
.modern-card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.modern-card-header {
    padding: 25px 30px;
    color: white;
    font-weight: 600;
    font-size: 1.25rem;
    position: relative;
    overflow: hidden;
}

.modern-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    transform: skewY(-2deg);
}

.modern-card-body {
    padding: 30px;
}

/* Parameter Box */
.parameter-box {
    padding: 25px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    overflow: hidden;
}

.parameter-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, transparent, var(--bs-primary), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.parameter-box:hover::before {
    opacity: 1;
}

.parameter-box.active {
    border-color: #28a745;
    background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.15);
}

.parameter-box.active::before {
    background: linear-gradient(180deg, transparent, #28a745, transparent);
    opacity: 1;
}

.parameter-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #dee2e6;
}

/* Modern Switch */
.form-switch .form-check-input {
    width: 60px;
    height: 30px;
    cursor: pointer;
    border: none;
    background-color: #e9ecef;
    background-image: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.form-switch .form-check-input:checked {
    background-color: #28a745;
    box-shadow: 0 0 15px rgba(40, 167, 69, 0.5);
}

.form-switch .form-check-input:focus {
    border-color: transparent;
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
}

/* Icon Styling */
.param-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.parameter-box:hover .param-icon {
    transform: scale(1.1) rotate(5deg);
}

/* Small Parameter Box */
.parameter-box-small {
    padding: 15px;
    border-radius: 12px;
    background: white;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.parameter-box-small.active {
    border-color: #28a745;
    background: linear-gradient(135deg, rgba(67, 233, 123, 0.05) 0%, rgba(56, 249, 215, 0.05) 100%);
}

.parameter-box-small:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
    position: relative;
    z-index: 1;
}

/* Save Button */
.btn-save {
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.5);
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Alert Styling */
.modern-alert {
    border: none;
    border-radius: 15px;
    padding: 20px 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    animation: slideInDown 0.5s ease;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .parameter-box {
        padding: 20px;
    }
    
    .param-icon {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
        margin-right: 15px;
    }
}
</style>

<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-cogs me-3"></i>
            Sistem Parametreleri
        </h1>
        <p>
            <i class="fas fa-shield-alt me-2"></i>
            Sadece Süper Admin erişebilir - Sistem çalışma modlarını yönetin
        </p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> modern-alert alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <strong><?= htmlspecialchars($_SESSION['message']) ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- QR Menü Sistemi -->
        <div class="modern-card mb-4">
            <div class="modern-card-header gradient-primary">
                <i class="fas fa-qrcode me-3"></i>
                QR Menü Sistemi
            </div>
            <div class="modern-card-body">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="parameter-box <?= $current_params['system_qr_menu_enabled'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-primary text-white">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="qr_menu_enabled" name="qr_menu_enabled" 
                                           <?= $current_params['system_qr_menu_enabled'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">QR Menü Kullanımı</h6>
                            <p class="text-muted small mb-0">
                                Müşterilerin QR kod ile menüye erişebilmesi
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="parameter-box <?= $current_params['system_accept_qr_orders'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-success text-white">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="accept_qr_orders" name="accept_qr_orders"
                                           <?= $current_params['system_accept_qr_orders'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Sipariş Alma</h6>
                            <p class="text-muted small mb-0">
                                QR menüden sipariş alınsın mı?
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="parameter-box <?= $current_params['system_table_management'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-info text-white">
                                    <i class="fas fa-chair"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="table_management" name="table_management"
                                           <?= $current_params['system_table_management'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Masa Yönetimi</h6>
                            <p class="text-muted small mb-0">
                                Masa bazlı sipariş takibi
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="parameter-box <?= $current_params['system_reservation_enabled'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-warning text-white">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="reservation_enabled" name="reservation_enabled"
                                           <?= $current_params['system_reservation_enabled'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Rezervasyon</h6>
                            <p class="text-muted small mb-0">
                                Online rezervasyon sistemi
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- QR Menü Görünürlük Ayarları -->
                <div class="alert alert-info mt-4 mb-0" style="border-left: 4px solid #667eea; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-eye me-2"></i>
                        Admin Panel Görünürlük Ayarları
                    </h6>
                    <p class="small text-muted mb-3">Bu ayarlar sadece admin panelindeki menü görünürlüğünü etkiler</p>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <div class="parameter-box-small <?= $current_params['system_qr_tables_visible'] == '1' ? 'active' : '' ?>">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="qr_tables_visible" name="qr_tables_visible"
                                           <?= $current_params['system_qr_tables_visible'] == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="qr_tables_visible">
                                        <i class="fas fa-chair text-primary me-1"></i>
                                        <strong>Masalar</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Masalar menüsünü göster/gizle</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="parameter-box-small <?= $current_params['system_qr_orders_visible'] == '1' ? 'active' : '' ?>">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="qr_orders_visible" name="qr_orders_visible"
                                           <?= $current_params['system_qr_orders_visible'] == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="qr_orders_visible">
                                        <i class="fas fa-receipt text-success me-1"></i>
                                        <strong>Siparişler</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Siparişler menüsünü göster/gizle</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="parameter-box-small <?= $current_params['system_qr_kitchen_visible'] == '1' ? 'active' : '' ?>">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="qr_kitchen_visible" name="qr_kitchen_visible"
                                           <?= $current_params['system_qr_kitchen_visible'] == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="qr_kitchen_visible">
                                        <i class="fas fa-utensils text-danger me-1"></i>
                                        <strong>Mutfak</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Mutfak ekranını göster/gizle</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="parameter-box-small <?= $current_params['system_qr_reservations_visible'] == '1' ? 'active' : '' ?>">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="qr_reservations_visible" name="qr_reservations_visible"
                                           <?= $current_params['system_qr_reservations_visible'] == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="qr_reservations_visible">
                                        <i class="fas fa-calendar-alt text-warning me-1"></i>
                                        <strong>Rezervasyonlar</strong>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Rezervasyonlar menüsünü göster/gizle</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Müşteri Arayüzü Erişimi -->
                <div class="alert alert-danger mt-4 mb-0" style="border-left: 4px solid #dc3545; background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 77, 79, 0.1) 100%);">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-lock me-2"></i>
                        Müşteri Arayüzü Erişim Kontrolü
                    </h6>
                    <p class="small text-muted mb-3">⚠️ <strong>Dikkat:</strong> Bu seçenek pasif edildiğinde müşteriler QR menüye hiç erişemez!</p>
                    <div class="parameter-box-small <?= $current_params['system_customer_access'] == '1' ? 'active' : '' ?>">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="customer_access" name="customer_access"
                                   <?= $current_params['system_customer_access'] == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label ms-2" for="customer_access">
                                <i class="fas fa-users text-danger me-1"></i>
                                <strong>Müşteri Erişimi Aktif</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Pasif edilirse: Müşteriler menüye erişemez, QR kod çalışmaz
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barkodlu Satış Sistemi -->
        <div class="modern-card mb-4">
            <div class="modern-card-header gradient-info">
                <i class="fas fa-barcode me-3"></i>
                Barkodlu Satış Sistemi (POS)
            </div>
            <div class="modern-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="parameter-box <?= $current_params['system_barcode_sales_enabled'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-warning text-white">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="barcode_sales_enabled" name="barcode_sales_enabled"
                                           <?= $current_params['system_barcode_sales_enabled'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Barkodlu Satış</h6>
                            <p class="text-muted small mb-0">
                                Barkod okuyucu ile hızlı satış sistemi
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="parameter-box <?= $current_params['system_stock_tracking'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <i class="fas fa-boxes text-white"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="stock_tracking" name="stock_tracking"
                                           <?= $current_params['system_stock_tracking'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Stok Takibi</h6>
                            <p class="text-muted small mb-0">
                                Ürün stok miktarlarının takibi ve uyarıları
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Alt Modüller: Stok Yönetimi -->
                <?php if ($current_params['system_stock_tracking'] == '1'): ?>
                <div class="alert alert-info mt-3 mb-0" style="border-radius: 12px; border-left: 4px solid #0dcaf0;">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-cog me-2"></i>
                        Stok Yönetimi Modülleri
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="parameter-box-small <?= $current_params['system_stock_management_visible'] == '1' ? 'active' : '' ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-chart-line me-2 text-info"></i>
                                        <strong>Stok Hareketleri Görünürlüğü</strong>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="stock_management_visible" name="stock_management_visible"
                                               <?= $current_params['system_stock_management_visible'] == '1' ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Admin panelde "Stok Yönetimi" menüsü ve sayfası
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ödeme Sistemi -->
        <div class="modern-card mb-4">
            <div class="modern-card-header gradient-success">
                <i class="fas fa-credit-card me-3"></i>
                Ödeme Sistemi
            </div>
            <div class="modern-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="parameter-box <?= $current_params['system_multi_payment'] == '1' ? 'active' : '' ?>">
                            <div class="d-flex align-items-center mb-3">
                                <div class="param-icon gradient-primary text-white">
                                    <i class="fas fa-divide"></i>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="multi_payment" name="multi_payment"
                                           <?= $current_params['system_multi_payment'] == '1' ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-2">Kısmi Ödeme</h6>
                            <p class="text-muted small mb-0">
                                Masadan kısmi ödeme alma (ürün/tutar bazlı)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="modern-card">
            <div class="modern-card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        Değişiklikler anında uygulanır
                    </div>
                    <button type="submit" name="save_parameters" class="btn btn-save">
                        <i class="fas fa-save me-2"></i>
                        Parametreleri Kaydet
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Switch toggle animation
document.querySelectorAll('.form-check-input').forEach(input => {
    input.addEventListener('change', function() {
        const box = this.closest('.parameter-box');
        if (this.checked) {
            box.classList.add('active');
        } else {
            box.classList.remove('active');
        }
    });
});

// Auto-hide alert after 5 seconds
setTimeout(() => {
    const alert = document.querySelector('.modern-alert');
    if (alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }
}, 5000);
</script>

<?php include 'footer.php'; ?>
