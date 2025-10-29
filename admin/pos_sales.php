<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Sistem parametrelerini kontrol et
$settingsResult = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'system_%'");
$systemParams = [];
while ($row = $settingsResult->fetch()) {
    $systemParams[$row['setting_key']] = $row['setting_value'];
}

$barcodeSalesEnabled = isset($systemParams['system_barcode_sales_enabled']) && $systemParams['system_barcode_sales_enabled'] == '1';
$stockTrackingEnabled = isset($systemParams['system_stock_tracking']) && $systemParams['system_stock_tracking'] == '1';

if (!$barcodeSalesEnabled) {
    header('Location: dashboard.php?error=pos_disabled');
    exit();
}

// Kategorileri çek
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

// pos_favorites tablosunu oluştur (yoksa)
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS pos_favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NULL,
            usage_count INT DEFAULT 1,
            last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            INDEX idx_user (user_id),
            INDEX idx_last_used (last_used)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Tablo zaten varsa hata görmezden gel
}

// Sık kullanılan ürünleri çek
$currentUserId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$favoriteProducts = [];
if ($currentUserId) {
    $favoriteProducts = $db->query("
        SELECT p.*, pf.usage_count, pf.id as favorite_id
        FROM pos_favorites pf
        INNER JOIN products p ON pf.product_id = p.id
        WHERE pf.user_id = ? AND p.status = 1
        ORDER BY pf.last_used DESC, pf.usage_count DESC
        LIMIT 20
    ", [$currentUserId])->fetchAll();
}

?>
<?php include 'navbar.php'; ?>

<style>
/* Modern POS Design */
.pos-container {
    height: calc(100vh - 60px);
    overflow: hidden;
    background: #f8f9fa;
}

/* Left Side - Products */
.pos-left {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: white;
    border-right: 2px solid #e9ecef;
}

.pos-search-bar {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.barcode-input {
    position: relative;
}

.barcode-input input {
    padding: 15px 50px 15px 20px;
    font-size: 1.1rem;
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.barcode-input input:focus {
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

.barcode-input i {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.3rem;
    color: #667eea;
}

/* Category Tabs */
.category-tabs {
    padding: 15px 20px 0;
    background: white;
    border-bottom: 2px solid #e9ecef;
    overflow-x: auto;
    white-space: nowrap;
}

.category-tabs::-webkit-scrollbar {
    height: 4px;
}

.category-tabs::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 4px;
}

.category-tab {
    display: inline-block;
    padding: 12px 25px;
    margin-right: 10px;
    border: none;
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    color: #6c757d;
}

.category-tab:hover {
    background: #e9ecef;
    color: #495057;
}

.category-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 -2px 10px rgba(102, 126, 234, 0.3);
}

/* Products Grid */
.products-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.products-area::-webkit-scrollbar {
    width: 8px;
}

.products-area::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 8px;
}

.pos-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 15px;
}

.pos-product-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.pos-product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.pos-product-card:hover::before {
    transform: scaleX(1);
}

.pos-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.pos-product-card:active {
    transform: translateY(-2px) scale(0.98);
}

.pos-product-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 10px;
}

.pos-product-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.pos-product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #667eea;
}

.pos-fav-btn {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid #ddd;
    color: #999;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 2;
}

.pos-fav-btn:hover {
    background: white;
    border-color: #f5576c;
    color: #f5576c;
    transform: scale(1.1);
}

.pos-fav-btn.active {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-color: #f5576c;
    color: white;
}

.pos-fav-btn.active:hover {
    transform: scale(1.1) rotate(15deg);
}

.usage-count {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.pos-product-stock {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

.pos-product-stock.low {
    background: #ffc107;
}

.pos-product-stock.out {
    background: #dc3545;
}

/* Right Side - Cart */
.pos-right {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: white;
    box-shadow: -4px 0 15px rgba(0, 0, 0, 0.05);
}

.pos-cart-header {
    padding: 20px;
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    text-align: center;
}

.pos-cart-header h4 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.pos-cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.pos-cart-items::-webkit-scrollbar {
    width: 6px;
}

.pos-cart-items::-webkit-scrollbar-thumb {
    background: #43e97b;
    border-radius: 6px;
}

.pos-cart-empty {
    text-align: center;
    padding: 60px 20px;
    color: #adb5bd;
}

.pos-cart-empty i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}

.pos-cart-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.pos-cart-item:hover {
    background: white;
    border-color: #43e97b;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.2);
}

.pos-cart-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.pos-cart-item-name {
    font-weight: 600;
    color: #2c3e50;
    flex: 1;
}

.pos-cart-item-remove {
    background: #dc3545;
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pos-cart-item-remove:hover {
    background: #c82333;
    transform: scale(1.1);
}

.pos-cart-item-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pos-qty-control {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 8px;
    padding: 5px;
}

.pos-qty-btn {
    background: #667eea;
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pos-qty-btn:hover {
    background: #5568d3;
    transform: scale(1.1);
}

.pos-qty-value {
    padding: 0 15px;
    font-weight: 700;
    color: #2c3e50;
    min-width: 40px;
    text-align: center;
}

.pos-item-total {
    font-size: 1.1rem;
    font-weight: 700;
    color: #43e97b;
}

/* Summary */
.pos-summary {
    padding: 20px;
    background: #f8f9fa;
    border-top: 2px solid #e9ecef;
}

.pos-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 1.1rem;
}

.pos-summary-row.total {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    padding-top: 15px;
    border-top: 2px solid #dee2e6;
}

/* Payment Buttons */
.pos-payment-buttons {
    padding: 20px;
    background: white;
    border-top: 2px solid #e9ecef;
}

/* Kompakt Ödeme Butonları */
.pos-pay-btn-compact {
    padding: 12px 8px;
    font-size: 0.85rem;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 65px;
}

.pos-pay-btn-compact i {
    font-size: 1.3rem;
}

.pos-pay-cash {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(67, 233, 123, 0.3);
}

.pos-pay-cash:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(67, 233, 123, 0.4);
}

.pos-pay-cash:active {
    transform: translateY(0);
}

.pos-pay-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
}

.pos-pay-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(79, 172, 254, 0.4);
}

.pos-pay-card:active {
    transform: translateY(0);
}

.pos-pay-partial {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.pos-pay-partial:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(240, 147, 251, 0.4);
}

.pos-pay-partial:active {
    transform: translateY(0);
}

.pos-clear-btn {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.pos-clear-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(255, 107, 107, 0.4);
}

.pos-clear-btn:active {
    transform: translateY(0);
}

/* Kasa Sekmeleri */
.pos-tabs-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 8px 15px 0;
    display: flex;
    align-items: center;
    gap: 5px;
    overflow-x: auto;
    scrollbar-width: thin;
}

.pos-tab {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    border-radius: 10px 10px 0 0;
    padding: 10px 20px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    position: relative;
}

.pos-tab:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.pos-tab.active {
    background: white;
    color: #667eea;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.pos-tab-close {
    margin-left: 5px;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.pos-tab-close:hover {
    opacity: 1;
    color: #dc3545;
}

.pos-tab-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
}

.pos-tab.active .pos-tab-badge {
    background: #667eea;
    color: white;
}

.pos-add-tab {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border: 2px dashed rgba(255, 255, 255, 0.4);
    border-radius: 10px 10px 0 0;
    padding: 10px 16px;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.pos-add-tab:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.6);
    transform: scale(1.1);
}

/* Responsive */
@media (max-width: 768px) {
    .pos-products-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .pos-tab {
        padding: 8px 12px;
        font-size: 0.85rem;
    }
    
    .pos-tabs-container {
        padding: 5px 10px 0;
    }
}
</style>

<div class="pos-container">
    <div class="row g-0 h-100">
        <!-- Left Side - Products -->
        <div class="col-md-8 pos-left">
            <!-- Kasa Sekmeleri -->
            <div class="pos-tabs-container">
                <button class="pos-tab active" data-register="1" onclick="switchRegister(1)">
                    <i class="fas fa-cash-register"></i>
                    <span>Kasa 1</span>
                    <span class="pos-tab-badge" id="register-1-count">0</span>
                </button>
                <button class="pos-add-tab" onclick="addNewRegister()" title="Yeni Kasa Aç">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="pos-search-bar">
                <div class="barcode-input">
                    <input type="text" id="barcodeInput" class="form-control" 
                           placeholder="🔍 Barkod okutun veya ürün adı yazın..." 
                           autofocus>
                    <i class="fas fa-barcode"></i>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="category-tabs">
                <button class="category-tab active" data-category="all">
                    <i class="fas fa-th"></i> Tümü
                </button>
                <?php if (!empty($favoriteProducts)): ?>
                <button class="category-tab" data-category="favorites" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <i class="fas fa-star"></i> Sık Kullanılanlar (<?= count($favoriteProducts) ?>)
                </button>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                <button class="category-tab" data-category="<?= $category['id'] ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Products Area -->
            <div class="products-area">
                <div class="pos-products-grid" id="productsGrid">
                    <!-- Ürünler JavaScript ile yüklenecek -->
                </div>
            </div>
        </div>

        <!-- Right Side - Cart -->
        <div class="col-md-4 pos-right">
            <!-- Cart Header -->
            <div class="pos-cart-header">
                <h4>
                    <i class="fas fa-shopping-cart me-2"></i>
                    Sepet
                </h4>
            </div>

            <!-- Cart Items -->
            <div class="pos-cart-items" id="cartItems">
                <div class="pos-cart-empty">
                    <i class="fas fa-shopping-basket"></i>
                    <p>Sepet boş</p>
                    <small>Ürün eklemek için tıklayın</small>
                </div>
            </div>

            <!-- Summary -->
            <div class="pos-summary">
                <div class="pos-summary-row">
                    <span>Ürün Sayısı:</span>
                    <span id="itemCount">0</span>
                </div>
                <div class="pos-summary-row">
                    <span>Ara Toplam:</span>
                    <span id="subtotal">0.00 ₺</span>
                </div>
                <div class="pos-summary-row" id="discountRow" style="display: none; color: #dc3545;">
                    <span>İndirim:</span>
                    <span id="discountAmount">-0.00 ₺</span>
                </div>
                <div class="pos-summary-row total">
                    <span>TOPLAM:</span>
                    <span id="total">0.00 ₺</span>
                </div>
            </div>

            <!-- Payment Buttons -->
            <div class="pos-payment-buttons">
                <div class="row g-2">
                    <div class="col-6">
                        <button class="pos-pay-btn-compact pos-pay-cash w-100" onclick="openPaymentModal('cash')">
                            <i class="fas fa-money-bill-wave d-block mb-1"></i>
                            <span class="small">NAKİT</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="pos-pay-btn-compact pos-pay-card w-100" onclick="openPaymentModal('card')">
                            <i class="fas fa-credit-card d-block mb-1"></i>
                            <span class="small">KART</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="pos-pay-btn-compact pos-pay-partial w-100" onclick="openPartialPaymentModal()">
                            <i class="fas fa-hand-holding-usd d-block mb-1"></i>
                            <span class="small">KISMİ</span>
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="pos-pay-btn-compact pos-clear-btn w-100" onclick="clearCart()">
                            <i class="fas fa-trash d-block mb-1"></i>
                            <span class="small">TEMİZLE</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-cash-register me-2"></i>
                    <span id="paymentMethodTitle">NAKİT ÖDEME</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <h2 class="display-4 fw-bold text-success mb-0" id="paymentTotal">0.00 ₺</h2>
                    <small class="text-muted">Ödenecek Tutar</small>
                </div>
                
                <!-- Nakit için alınan tutar -->
                <div id="cashAmountSection" style="display: none;">
                    <label class="form-label fw-bold">Alınan Tutar</label>
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        <input type="number" class="form-control" id="cashReceived" placeholder="0.00" step="0.01" min="0">
                        <span class="input-group-text">₺</span>
                    </div>
                    
                    <!-- Hızlı tutar butonları -->
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-outline-primary flex-fill" onclick="setQuickAmount(50)">50₺</button>
                        <button class="btn btn-outline-primary flex-fill" onclick="setQuickAmount(100)">100₺</button>
                        <button class="btn btn-outline-primary flex-fill" onclick="setQuickAmount(200)">200₺</button>
                        <button class="btn btn-outline-primary flex-fill" onclick="setExactAmount()">TAM</button>
                    </div>
                    
                    <!-- Para üstü -->
                    <div class="alert" id="changeAlert" style="display: none; border-radius: 15px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Para Üstü:</span>
                            <span class="fs-4 fw-bold" id="changeAmount">0.00 ₺</span>
                        </div>
                    </div>
                </div>
                
                <!-- İndirim -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-percent text-danger me-1"></i>
                        İndirim (Opsiyonel)
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="discountInput" placeholder="0" step="0.01" min="0">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <span id="discountType">₺</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="setDiscountType('amount')">Tutar (₺)</a></li>
                            <li><a class="dropdown-item" href="#" onclick="setDiscountType('percent')">Yüzde (%)</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Not -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-sticky-note text-info me-1"></i>
                        Not (Opsiyonel)
                    </label>
                    <textarea class="form-control" id="paymentNote" rows="2" placeholder="Ödeme notu..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>İptal
                </button>
                <button type="button" class="btn btn-success btn-lg" onclick="processFullPayment()">
                    <i class="fas fa-check-circle me-2"></i>Ödemeyi Tamamla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Kısmi Ödeme Modal -->
<div class="modal fade" id="partialPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-hand-holding-usd me-2"></i>
                    Kısmi Ödeme
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <small class="text-muted">Toplam Tutar</small>
                                <h3 class="mb-0 fw-bold" id="partialTotalAmount">0.00 ₺</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-success text-white border-0">
                            <div class="card-body text-center">
                                <small>Kalan Tutar</small>
                                <h3 class="mb-0 fw-bold" id="partialRemainingAmount">0.00 ₺</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ödeme Tutarı -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-dollar-sign text-success me-1"></i>
                        Ödenecek Tutar
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                        <input type="number" class="form-control" id="partialAmount" placeholder="0.00" step="0.01" min="0">
                        <span class="input-group-text">₺</span>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="setPartialPercent(25)">%25</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="setPartialPercent(50)">%50</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="setPartialPercent(75)">%75</button>
                    </div>
                </div>
                
                <!-- Ödeme Yöntemi -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Ödeme Yöntemi</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="partialMethod" id="partialCash" value="cash" checked>
                        <label class="btn btn-outline-success" for="partialCash">
                            <i class="fas fa-money-bill-wave me-1"></i> Nakit
                        </label>
                        
                        <input type="radio" class="btn-check" name="partialMethod" id="partialCard" value="card">
                        <label class="btn btn-outline-primary" for="partialCard">
                            <i class="fas fa-credit-card me-1"></i> Kart
                        </label>
                    </div>
                </div>
                
                <!-- Not -->
                <div class="mb-3">
                    <label class="form-label">Not (Opsiyonel)</label>
                    <textarea class="form-control" id="partialNote" rows="2" placeholder="Kısmi ödeme notu..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>İptal
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="processPartialPayment()">
                    <i class="fas fa-check-circle me-2"></i>Kısmi Ödeme Al
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Çoklu Kasa Sistemi
let registers = {
    1: {
        cart: [],
        partialPayments: []
    }
};
let currentRegister = 1;
let registerCounter = 1;

let products = [];
let favoriteProducts = <?= json_encode($favoriteProducts) ?>;
const stockTracking = <?= $stockTrackingEnabled ? 'true' : 'false' ?>;
const currentUserId = <?= $currentUserId ?? 'null' ?>;

// Aktif sepeti al
function getCart() {
    return registers[currentRegister].cart;
}

// Aktif sepeti güncelle
function setCart(newCart) {
    registers[currentRegister].cart = newCart;
}

// Kısmi ödemeleri al
function getPartialPayments() {
    return registers[currentRegister].partialPayments;
}

// Kısmi ödemeleri güncelle
function setPartialPayments(payments) {
    registers[currentRegister].partialPayments = payments;
}

// Yeni kasa aç
function addNewRegister() {
    registerCounter++;
    const newId = registerCounter;
    
    registers[newId] = {
        cart: [],
        partialPayments: []
    };
    
    // Sekmeyi ekle
    const tabsContainer = $('.pos-tabs-container');
    const addButton = $('.pos-add-tab');
    
    const newTab = $(`
        <button class="pos-tab" data-register="${newId}" onclick="switchRegister(${newId})">
            <i class="fas fa-cash-register"></i>
            <span>Kasa ${newId}</span>
            <span class="pos-tab-badge" id="register-${newId}-count">0</span>
            <i class="fas fa-times pos-tab-close" onclick="closeRegister(${newId}, event)"></i>
        </button>
    `);
    
    newTab.insertBefore(addButton);
    switchRegister(newId);
    
    Swal.fire({
        icon: 'success',
        title: `Kasa ${newId} Açıldı!`,
        text: 'Yeni kasa aktif',
        timer: 1500,
        showConfirmButton: false
    });
}

// Kasaya geçiş yap
function switchRegister(registerId) {
    if (!registers[registerId]) return;
    
    // Eski sekmeyi pasif yap
    $('.pos-tab').removeClass('active');
    
    // Yeni sekmeyi aktif yap
    $(`.pos-tab[data-register="${registerId}"]`).addClass('active');
    
    currentRegister = registerId;
    
    // Sepeti yeniden render et
    renderCart();
}

// Kasa kapat
function closeRegister(registerId, event) {
    event.stopPropagation();
    
    if (registerId === 1) {
        Swal.fire('Uyarı', 'Ana kasa kapatılamaz!', 'warning');
        return;
    }
    
    const registerCart = registers[registerId].cart;
    if (registergetCart().length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Kasa Boş Değil!',
            text: 'Sepette ürün var. Yine de kapatmak istiyor musunuz?',
            showCancelButton: true,
            confirmButtonText: 'Evet, Kapat',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                performCloseRegister(registerId);
            }
        });
    } else {
        performCloseRegister(registerId);
    }
}

function performCloseRegister(registerId) {
    // Kasayı sil
    delete registers[registerId];
    
    // Sekmeyi kaldır
    $(`.pos-tab[data-register="${registerId}"]`).remove();
    
    // Eğer aktif kasa kapatıldıysa, kasa 1'e geç
    if (currentRegister === registerId) {
        switchRegister(1);
    }
    
    Swal.fire({
        icon: 'success',
        title: 'Kasa Kapatıldı',
        timer: 1500,
        showConfirmButton: false
    });
}

// Sepet sayısını güncelle
function updateRegisterBadges() {
    for (let id in registers) {
        const count = registers[id].getCart().reduce((sum, item) => sum + item.quantity, 0);
        $(`#register-${id}-count`).text(count);
    }
}

// Sayfa yüklendiğinde
$(document).ready(function() {
    loadProducts('all');
    
    // Barkod input - Enter tuşu
    $('#barcodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault(); // Enter'ın default davranışını engelle
            const search = $(this).val().trim();
            if (search) {
                searchProduct(search);
            }
        }
    });
    
    // Kategori değiştirme
    $('.category-tab').on('click', function() {
        $('.category-tab').removeClass('active');
        $(this).addClass('active');
        const categoryId = $(this).data('category');
        loadProducts(categoryId);
    });
});

// Ürünleri yükle
function loadProducts(categoryId) {
    // Sık kullanılanlar kategorisi için
    if (categoryId === 'favorites') {
        products = favoriteProducts;
        renderProducts(products, true);
        return;
    }
    
    $.ajax({
        url: 'ajax/get_pos_products.php',
        type: 'GET',
        data: { category: categoryId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                products = response.products;
                renderProducts(products, false);
            }
        },
        error: function() {
            Swal.fire('Hata', 'Ürünler yüklenemedi', 'error');
        }
    });
}

// Ürünleri render et
function renderProducts(productList, isFavorites = false) {
    const grid = $('#productsGrid');
    grid.empty();
    
    productList.forEach(product => {
        const stockClass = product.stock > 10 ? '' : (product.stock > 0 ? 'low' : 'out');
        const stockText = stockTracking ? `<span class="pos-product-stock ${stockClass}">${product.stock}</span>` : '';
        
        // Favorilerde mi kontrol et
        const isFav = favoriteProducts.some(f => f.id == product.id);
        const favIcon = isFav ? 
            `<button class="pos-fav-btn active" onclick="event.stopPropagation(); toggleFavorite(${product.id}, this)" title="Favorilerden Çıkar">
                <i class="fas fa-star"></i>
            </button>` : 
            `<button class="pos-fav-btn" onclick="event.stopPropagation(); toggleFavorite(${product.id}, this)" title="Favorilere Ekle">
                <i class="far fa-star"></i>
            </button>`;
        
        const usageCount = product.usage_count ? `<span class="usage-count" title="${product.usage_count} kez kullanıldı">${product.usage_count}×</span>` : '';
        
        const card = $(`
            <div class="pos-product-card" onclick="addToCart(${product.id})">
                ${stockText}
                ${favIcon}
                ${isFavorites ? usageCount : ''}
                <img src="../uploads/${product.image}" class="pos-product-img" alt="${product.name}">
                <div class="pos-product-name">${product.name}</div>
                <div class="pos-product-price">${parseFloat(product.price).toFixed(2)} ₺</div>
            </div>
        `);
        
        grid.append(card);
    });
}

// Favorilere ekle/çıkar
function toggleFavorite(productId, btn) {
    if (!currentUserId) {
        Swal.fire('Hata', 'Kullanıcı bilgisi bulunamadı', 'error');
        return;
    }
    
    const isFav = favoriteProducts.some(f => f.id == productId);
    
    $.ajax({
        url: 'ajax/toggle_favorite.php',
        type: 'POST',
        data: {
            product_id: productId,
            action: isFav ? 'remove' : 'add'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Favori listesini güncelle
                if (isFav) {
                    favoriteProducts = favoriteProducts.filter(f => f.id != productId);
                    $(btn).removeClass('active').html('<i class="far fa-star"></i>').attr('title', 'Favorilere Ekle');
                } else {
                    // Yeni favoriyi ekle
                    const product = products.find(p => p.id == productId);
                    if (product) {
                        favoriteProducts.push({...product, usage_count: 1, favorite_id: response.favorite_id});
                        $(btn).addClass('active').html('<i class="fas fa-star"></i>').attr('title', 'Favorilerden Çıkar');
                    }
                }
                
                // Favori sayısını güncelle
                const favTab = $('[data-category="favorites"]');
                if (favoriteProducts.length > 0) {
                    if (favTab.length) {
                        favTab.html(`<i class="fas fa-star"></i> Sık Kullanılanlar (${favoriteProducts.length})`);
                    } else {
                        // Favori tab yoksa oluştur
                        $('.category-tab[data-category="all"]').after(`
                            <button class="category-tab" data-category="favorites" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                <i class="fas fa-star"></i> Sık Kullanılanlar (${favoriteProducts.length})
                            </button>
                        `);
                    }
                } else {
                    favTab.remove();
                }
            }
        },
        error: function() {
            Swal.fire('Hata', 'İşlem başarısız', 'error');
        }
    });
}

// Ürün ara
function searchProduct(search) {
    // ÖNCE textbox'ı temizle
    $('#barcodeInput').val('');
    
    const found = products.find(p => 
        p.barcode === search || 
        p.name.toLowerCase().includes(search.toLowerCase())
    );
    
    if (found) {
        addToCart(found.id);
        // Focus'u koru
        $('#barcodeInput').focus();
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Ürün Bulunamadı',
            text: 'Barkod veya ürün adı bulunamadı',
            timer: 2000,
            didClose: () => {
                // Alert kapandığında focus yap
                $('#barcodeInput').focus();
            }
        });
    }
}

// Sepete ekle
function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) {
        // Ürün bulunamadı - sadece focus yap (textbox zaten temiz)
        $('#barcodeInput').focus();
        return;
    }
    
    // Stok kontrolü
    if (stockTracking && product.stock <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Stok Yok',
            text: 'Bu ürün stokta yok',
            timer: 2000,
            didClose: () => {
                // Alert kapanınca focus yap
                $('#barcodeInput').focus();
            }
        });
        return;
    }
    
    const existingItem = getCart().find(item => item.id == productId);
    
    if (existingItem) {
        if (stockTracking && existingItem.quantity >= product.stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stok Yetersiz',
                text: `Maksimum ${product.stock} adet`,
                timer: 2000,
                didClose: () => {
                    // Alert kapanınca focus yap
                    $('#barcodeInput').focus();
                }
            });
            return;
        }
        existingItem.quantity++;
    } else {
        getCart().push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            stock: product.stock
        });
    }
    
    renderCart();
    
    // Başarılı ekleme - sadece focus yap (textbox zaten temizlendi)
    $('#barcodeInput').focus();
}

// Sepeti render et
function renderCart() {
    const cartEl = $('#cartItems');
    cartEl.empty();
    
    if (getCart().length === 0) {
        cartEl.html(`
            <div class="pos-cart-empty">
                <i class="fas fa-shopping-basket"></i>
                <p>Sepet boş</p>
                <small>Ürün eklemek için tıklayın</small>
            </div>
        `);
    } else {
        getCart().forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            const itemHtml = $(`
                <div class="pos-cart-item">
                    <div class="pos-cart-item-header">
                        <span class="pos-cart-item-name">${item.name}</span>
                        <button class="pos-cart-item-remove" onclick="removeFromCart(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="pos-cart-item-controls">
                        <div class="pos-qty-control">
                            <button class="pos-qty-btn" onclick="updateQuantity(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="pos-qty-value">${item.quantity}</span>
                            <button class="pos-qty-btn" onclick="updateQuantity(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <span class="pos-item-total">${itemTotal.toFixed(2)} ₺</span>
                    </div>
                </div>
            `);
            cartEl.append(itemHtml);
        });
    }
    
    updateSummary();
    updateRegisterBadges();
}

// Miktarı güncelle
function updateQuantity(index, change) {
    const cart = getCart();
    const item = cart[index];
    const newQty = item.quantity + change;
    
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    
    if (stockTracking && newQty > item.stock) {
        Swal.fire('Stok Yetersiz', `Maksimum ${item.stock} adet`, 'warning');
        return;
    }
    
    item.quantity = newQty;
    setCart(cart);
    renderCart();
}

// Sepetten çıkar
function removeFromCart(index) {
    const cart = getCart();
    cart.splice(index, 1);
    setCart(cart);
    renderCart();
}

// Özet güncelle
function updateSummary() {
    const itemCount = getCart().reduce((sum, item) => sum + item.quantity, 0);
    const total = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    $('#itemCount').text(itemCount);
    $('#subtotal').text(total.toFixed(2) + ' ₺');
    $('#total').text(total.toFixed(2) + ' ₺');
}

// Sepeti temizle
function clearCart() {
    if (getCart().length === 0) return;
    
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Sepetteki tüm ürünler silinecek',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Temizle',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            setCart([]);
            renderCart();
        }
    });
}

// Global değişkenler
let currentPaymentMethod = 'cash';
let currentDiscountType = 'amount'; // 'amount' veya 'percent'
// Ödeme modalını aç
function openPaymentModal(method) {
    if (getCart().length === 0) {
        Swal.fire('Sepet Boş', 'Lütfen ürün ekleyin', 'warning');
        return;
    }
    
    currentPaymentMethod = method;
    const total = calculateTotal();
    
    $('#paymentMethodTitle').text(method === 'cash' ? 'NAKİT ÖDEME' : 'KART ÖDEME');
    $('#paymentTotal').text(total.toFixed(2) + ' ₺');
    
    // Nakit için tutar girişini göster
    if (method === 'cash') {
        $('#cashAmountSection').show();
        $('#cashReceived').val('').focus();
    } else {
        $('#cashAmountSection').hide();
    }
    
    // Alanları temizle
    $('#discountInput').val('');
    $('#paymentNote').val('');
    $('#changeAlert').hide();
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// Toplam hesapla (indirimle)
function calculateTotal() {
    const subtotal = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = parseFloat($('#discountInput').val()) || 0;
    
    let discountAmount = 0;
    if (discount > 0) {
        if (currentDiscountType === 'percent') {
            discountAmount = (subtotal * discount) / 100;
        } else {
            discountAmount = discount;
        }
    }
    
    return Math.max(0, subtotal - discountAmount);
}

// Hızlı tutar ayarla
function setQuickAmount(amount) {
    $('#cashReceived').val(amount);
    calculateChange();
}

// Tam tutar
function setExactAmount() {
    const total = calculateTotal();
    $('#cashReceived').val(total.toFixed(2));
    calculateChange();
}

// Para üstü hesapla
function calculateChange() {
    const received = parseFloat($('#cashReceived').val()) || 0;
    const total = calculateTotal();
    const change = received - total;
    
    if (change >= 0) {
        $('#changeAmount').text(change.toFixed(2) + ' ₺');
        $('#changeAlert').removeClass('alert-danger').addClass('alert-success').show();
    } else {
        $('#changeAmount').text('Yetersiz tutar!');
        $('#changeAlert').removeClass('alert-success').addClass('alert-danger').show();
    }
}

// İndirim input değişince
$('#discountInput').on('input', function() {
    const discount = parseFloat($(this).val()) || 0;
    const subtotal = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    let discountAmount = 0;
    if (discount > 0) {
        if (currentDiscountType === 'percent') {
            discountAmount = (subtotal * discount) / 100;
        } else {
            discountAmount = discount;
        }
    }
    
    if (discountAmount > 0) {
        $('#discountRow').show();
        $('#discountAmount').text('-' + discountAmount.toFixed(2) + ' ₺');
    } else {
        $('#discountRow').hide();
    }
    
    const total = calculateTotal();
    $('#paymentTotal').text(total.toFixed(2) + ' ₺');
    
    if (currentPaymentMethod === 'cash') {
        calculateChange();
    }
});

// Nakit tutar değişince
$('#cashReceived').on('input', calculateChange);

// İndirim tipi değiştir
function setDiscountType(type) {
    currentDiscountType = type;
    $('#discountType').text(type === 'percent' ? '%' : '₺');
    $('#discountInput').trigger('input'); // Yeniden hesapla
}

// Tam ödeme işle
function processFullPayment() {
    const total = calculateTotal();
    
    // Nakit için para kontrolü
    if (currentPaymentMethod === 'cash') {
        const received = parseFloat($('#cashReceived').val()) || 0;
        if (received < total) {
            Swal.fire('Yetersiz Tutar', 'Alınan tutar yetersiz', 'error');
            return;
        }
    }
    
    const discount = parseFloat($('#discountInput').val()) || 0;
    const note = $('#paymentNote').val();
    
    processPayment(currentPaymentMethod, total, discount, currentDiscountType, note, false);
}

// Kısmi ödeme modalını aç
function openPartialPaymentModal() {
    if (getCart().length === 0) {
        Swal.fire('Sepet Boş', 'Lütfen ürün ekleyin', 'warning');
        return;
    }
    
    const total = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const paid = getPartialPayments().reduce((sum, p) => sum + p.amount, 0);
    const remaining = total - paid;
    
    $('#partialTotalAmount').text(total.toFixed(2) + ' ₺');
    $('#partialRemainingAmount').text(remaining.toFixed(2) + ' ₺');
    $('#partialAmount').val('').attr('max', remaining);
    $('#partialNote').val('');
    
    const modal = new bootstrap.Modal(document.getElementById('partialPaymentModal'));
    modal.show();
}

// Kısmi ödeme yüzde ayarla
function setPartialPercent(percent) {
    const total = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const paid = getPartialPayments().reduce((sum, p) => sum + p.amount, 0);
    const remaining = total - paid;
    const amount = (remaining * percent) / 100;
    $('#partialAmount').val(amount.toFixed(2));
}

// Kısmi ödeme işle
function processPartialPayment() {
    const amount = parseFloat($('#partialAmount').val()) || 0;
    const total = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const paid = getPartialPayments().reduce((sum, p) => sum + p.amount, 0);
    const remaining = total - paid;
    
    if (amount <= 0) {
        Swal.fire('Hata', 'Lütfen geçerli bir tutar girin', 'error');
        return;
    }
    
    if (amount > remaining) {
        Swal.fire('Hata', 'Tutar kalan tutardan fazla olamaz', 'error');
        return;
    }
    
    const method = $('input[name="partialMethod"]:checked').val();
    const note = $('#partialNote').val();
    
    // Kısmi ödemeyi kaydet
    const partialPayments = getPartialPayments();
    partialPayments.push({
        amount: amount,
        method: method,
        note: note,
        time: new Date()
    });
    setPartialPayments(partialPayments);
    
    // Modal'ı kapat
    bootstrap.Modal.getInstance(document.getElementById('partialPaymentModal')).hide();
    
    // Başarı mesajı
    Swal.fire({
        icon: 'success',
        title: 'Kısmi Ödeme Alındı!',
        html: `
            <div class="fs-5 mb-3">${amount.toFixed(2)} ₺</div>
            <div class="text-muted">Kalan: ${(remaining - amount).toFixed(2)} ₺</div>
        `,
        timer: 2000,
        showConfirmButton: false
    });
    
    // Eğer tam ödeme tamamlandıysa
    if (remaining - amount <= 0) {
        setTimeout(() => {
            const totalAmount = total;
            processPayment('mixed', totalAmount, 0, 'amount', 'Kısmi ödemeler ile tamamlandı', true);
        }, 2100);
    }
}

// Ödemeyi işle
function processPayment(paymentMethod, finalTotal, discount, discountType, note, isPartial) {
    const subtotal = getCart().reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const total = finalTotal || subtotal;
    
    const partialPayments = getPartialPayments();
    
    $.ajax({
        url: 'ajax/complete_pos_sale.php',
        type: 'POST',
        data: {
            items: JSON.stringify(getCart()),
            payment_method: paymentMethod,
            total: total,
            subtotal: subtotal,
            discount: discount || 0,
            discount_type: discountType || 'amount',
            note: note || '',
            is_partial: isPartial || false,
            partial_payments: isPartial ? JSON.stringify(partialPayments) : null,
            register_id: currentRegister
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Modal'ları kapat
                const paymentModal = document.getElementById('paymentModal');
                if (paymentModal) {
                    bootstrap.Modal.getInstance(paymentModal)?.hide();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Satış Tamamlandı!',
                    html: `
                        <div style="font-size: 1.5rem; color: #43e97b; margin: 20px 0;">
                            <strong>${total.toFixed(2)} ₺</strong>
                        </div>
                        <div class="mb-2">Fiş No: <strong>#${response.sale_id}</strong></div>
                        ${discount > 0 ? `<div class="text-muted small">İndirim: ${discount} ${discountType === 'percent' ? '%' : '₺'}</div>` : ''}
                        ${isPartial ? `<div class="text-info small mt-2">${partialPayments.length} kısmi ödeme ile tamamlandı</div>` : ''}
                    `,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    setCart([]);
                    setPartialPayments([]);
                    $('#discountRow').hide();
                    renderCart();
                    loadProducts($('.category-tab.active').data('category'));
                });
            } else {
                Swal.fire('Hata', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Hata', 'Satış tamamlanamadı', 'error');
        }
    });
}
</script>

<?php include 'footer.php'; ?>

