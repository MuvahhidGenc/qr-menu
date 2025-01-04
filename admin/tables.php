<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('tables.view')) {
    header('Location: dashboard.php');
    exit();
}

// Yetki kontrollerini en başta tanımla
$canViewTables = hasPermission('tables.view');
$canManageTables = hasPermission('tables.manage');
$canTakePayment = hasPermission('tables.payment');
$canViewSales = hasPermission('tables.sales');
$canAddOrder = hasPermission('tables.add_order');
$canEditOrder = hasPermission('tables.edit_order');
$canDeleteOrder = hasPermission('tables.delete_order');
$canSaveOrder = hasPermission('tables.save_order');

// Bu değişkenleri JavaScript'e aktaralım
?>
<script>
// Yetki değişkenlerini JavaScript'te tanımla
const userPermissions = {
    canViewTables: <?php echo $canViewTables ? 'true' : 'false' ?>,
    canManageTables: <?php echo $canManageTables ? 'true' : 'false' ?>,
    canTakePayment: <?php echo $canTakePayment ? 'true' : 'false' ?>,
    canViewSales: <?php echo $canViewSales ? 'true' : 'false' ?>,
    canAddOrder: <?php echo $canAddOrder ? 'true' : 'false' ?>,
    canEditOrder: <?php echo $canEditOrder ? 'true' : 'false' ?>,
    canDeleteOrder: <?php echo $canDeleteOrder ? 'true' : 'false' ?>,
    canSaveOrder: <?php echo $canSaveOrder ? 'true' : 'false' ?>
};
</script>
<?php include 'navbar.php'; ?>  

<?php
$db = new Database();
$tables = $db->query("SELECT * FROM tables ORDER BY table_no")->fetchAll();

// Kategorileri çek
$categoriesQuery = "SELECT * FROM categories WHERE status = 1 ORDER BY name";
$categories = $db->query($categoriesQuery)->fetchAll();

// Ürünleri çek
$productsQuery = "SELECT * FROM products WHERE status = 1 ORDER BY name";
$products = $db->query($productsQuery)->fetchAll();

// Debug bilgileri
error_log('Categories Query: ' . $categoriesQuery);
error_log('Products Query: ' . $productsQuery);

// Veritabanı durumunu kontrol et
$dbCheck = $db->query("SELECT 
    (SELECT COUNT(*) FROM categories WHERE status = 1) as active_categories,
    (SELECT COUNT(*) FROM products WHERE status = 1) as active_products
")->fetch();

error_log('Active Categories: ' . $dbCheck['active_categories']);
error_log('Active Products: ' . $dbCheck['active_products']);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masalar - QR Menü Admin</title>
    
    <!-- CSS Dosyaları -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    
    
</head>
<style>
.nav-link {
    display: -webkit-box !important;
    color: #ffff;
}

/* Ana container stilleri */
.tables-container {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* Başlık alanı */
.tables-header {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.tables-header h2 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Masa kartları grid */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Masa kartı */
.table-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: none;
}

.table-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

/* Masa başlığı */
.table-header {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Durum badge'leri */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-free {
    background: #d1e7dd;
    color: #0f5132;
}

.status-occupied {
    background: #fff3cd;
    color: #856404;
}

/* Masa içeriği */
.table-content {
    padding: 1.25rem;
}

.table-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 10px;
}

/* Butonlar */
.btn-primary {
    background: linear-gradient(45deg, #4CAF50, #45a049);
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.5rem;
    border-radius: 8px;
    border: none;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
}

/* Modal stilleri */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    background: linear-gradient(45deg, #f8f9fa, #fff);
    border-radius: 15px 15px 0 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .tables-container {
        padding: 1rem;
    }
    
    .tables-grid {
        grid-template-columns: 1fr;
    }
    
    .table-card {
        margin-bottom: 1rem;
    }
}

/* Satış Ekranı Modal Stilleri */
.sales-modal .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.sales-modal .modal-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    border-radius: 20px 20px 0 0;
    padding: 1.5rem;
    border: none;
}

.sales-modal .modal-title {
    font-size: 1.4rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sales-modal .modal-body {
    padding: 1.5rem;
}

/* Ürün Listesi */
.product-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 0.5rem;
}

.product-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.product-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-name {
    font-weight: 500;
    color: #2c3e50;
}

.product-price {
    color: #2ecc71;
    font-weight: 600;
}

/* Miktar Kontrolleri */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-quantity {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-decrease {
    background: #ffebee;
    color: #e53935;
}

.btn-increase {
    background: #e8f5e9;
    color: #43a047;
}

.quantity {
    font-weight: 600;
    min-width: 40px;
    text-align: center;
}

/* Toplam Kısmı */
.total-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.total-label {
    font-weight: 500;
    color: #2c3e50;
}

.total-amount {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2ecc71;
}

/* Modal Footer */
.sales-modal .modal-footer {
    border-top: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
}

/* Butonlar */
.btn-payment {
    background: linear-gradient(45deg, #2ecc71, #27ae60);
    color: white;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-payment:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
}

.btn-cancel {
    background: #f8f9fa;
    color: #2c3e50;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #e9ecef;
}

/* Scrollbar Stilleri */
.product-list::-webkit-scrollbar {
    width: 8px;
}

.product-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.product-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.product-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Animasyonlar */
@keyframes slideIn {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.sales-modal .modal-content {
    animation: slideIn 0.3s ease-out;
}
</style>
<div class="category-content">
    <div class="container-fluid p-3">
        <!-- Başlık ve Yeni Masa Butonu -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-chair me-2"></i>Masalar
            </h2>
            <?php if ($canManageTables): ?>
                <button type="button" class="btn btn-primary" onclick="showAddTableModal()">
                    <i class="fas fa-plus"></i> Yeni Masa
                </button>
            <?php endif; ?>
        </div>

        <!-- Masalar Grid -->
        <div class="row g-4">
            <?php foreach($tables as $table): 
                // Masa durumunu ve toplam tutarı çek
                $tableInfo = $db->query(
                    "SELECT 
                        CASE WHEN EXISTS (
                            SELECT 1 FROM orders 
                            WHERE table_id = ? AND payment_id IS NULL
                        ) THEN 'occupied' ELSE 'empty' END as status,
                        COALESCE((
                            SELECT SUM(total_amount) 
                            FROM orders 
                            WHERE table_id = ? AND payment_id IS NULL
                        ), 0) as total",
                    [$table['id'], $table['id']]
                )->fetch();
            ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 <?= $tableInfo['status'] === 'occupied' ? 'border-warning' : '' ?>">
                        <div class="card-header bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chair me-2"></i>Masa <?= $table['table_no'] ?>
                                </h5>
                                <span class="badge <?= $tableInfo['status'] === 'occupied' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $tableInfo['status'] === 'occupied' ? 'Dolu' : 'Boş' ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <small class="text-muted">Kapasite:</small>
                                    <div><?= $table['capacity'] ?? 4 ?> Kişilik</div>
                                </div>
                                <?php if($tableInfo['status'] === 'occupied'): ?>
                                    <div class="text-end">
                                        <small class="text-muted">Toplam Tutar:</small>
                                        <div class="fw-bold text-warning"><?= number_format($tableInfo['total'], 2) ?> ₺</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php if ($canViewSales): ?>
                                    <button class="btn btn-primary" onclick="openPaymentModal(<?= $table['id'] ?>)">
                                        <i class="fas fa-cash-register me-2"></i>Satış Ekranı
                                    </button>
                                <?php endif; ?>

                                <div class="btn-group">
                                    <?php if ($canManageTables): ?>
                                        <button class="btn btn-outline-primary" onclick="editTable(<?= $table['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteTable(<?= $table['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-info" onclick="showQRCode(<?= $table['id'] ?>)">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Satış Ekranı Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cash-register me-2"></i>
                    <span id="paymentTableInfo">Masa</span>
                    <span class="badge bg-light text-primary ms-2" id="tableStatus">-</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Sol Taraf: Kategoriler ve Ürünler -->
                    <div class="col-md-7 border-end">
                        <!-- Kategori Butonları -->
                        <div class="p-2 border-bottom bg-light">
                            <div class="d-flex gap-2 overflow-auto categories-wrapper">
                                <?php foreach($categories as $category): ?>
                                    <button type="button" 
                                            class="btn btn-outline-primary payment-category-btn flex-shrink-0" 
                                            data-category="<?= $category['id'] ?>">
                                        <?php if($category['image']): ?>
                                            <img src="../uploads/<?= $category['image'] ?>" 
                                                 class="category-img me-1" 
                                                 alt="<?= htmlspecialchars($category['name']) ?>">
                                        <?php endif; ?>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Ürün Listesi -->
                        <div class="products-wrapper" style="height: calc(100vh - 250px); overflow-y: auto;">
                            <?php foreach($categories as $category): ?>
                                <div class="category-products p-2" data-category="<?= $category['id'] ?>" style="display: none;">
                                    <div class="row g-2">
                                        <?php 
                                        $categoryProducts = array_filter($products, function($p) use ($category) {
                                            return $p['category_id'] == $category['id'];
                                        });
                                        
                                        foreach($categoryProducts as $product): 
                                        ?>
                                            <div class="col-lg-3 col-md-4 col-sm-6">
                                                <div class="card product-card h-100">
                                                    <?php if($product['image']): ?>
                                                        <img src="../uploads/<?= $product['image'] ?>" 
                                                             class="card-img-top product-img" 
                                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                                    <?php endif; ?>
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-2 text-truncate">
                                                            <?= htmlspecialchars($product['name']) ?>
                                                        </h6>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="text-primary fw-bold">
                                                                <?= number_format($product['price'], 2) ?> ₺
                                                            </span>
                                                            <button type="button" 
                                                                    class="btn btn-primary btn-sm"
                                                                    onclick="addToPaymentOrder(
                                                                        <?= $product['id'] ?>, 
                                                                        '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', 
                                                                        <?= $product['price'] ?>
                                                                    )">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sağ Taraf: Sipariş Detayları -->
                    <div class="col-md-5">
                        <div class="d-flex flex-column h-100">
                            <!-- Sipariş Başlığı -->
                            <div class="p-3 bg-light border-bottom">
                                <h6 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Sipariş Detayları
                                </h6>
                            </div>
                            
                            <!-- Siparişler -->
                            <div class="flex-grow-1 order-items-wrapper" style="height: calc(100vh - 400px); overflow-y: auto;">
                                <div id="paymentOrderDetails" class="p-3"></div>
                                <div id="newOrderItems" class="p-3 border-top"></div>
                            </div>

                            <!-- Ödeme Seçenekleri ve Toplam -->
                            <div class="border-top p-3">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">Toplam</h5>
                                        <h5 class="mb-0 text-primary" id="paymentTotal">0.00 ₺</h5>
                                    </div>
                                    
                                    <!-- Ödeme Yöntemi Seçimi -->
                                    <div class="payment-methods mb-3" style="display: <?= $canTakePayment ? 'block' : 'none' ?>">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="radio" class="btn-check" name="payment_method" id="cash" value="cash" checked>
                                                <label class="btn btn-outline-success w-100" for="cash">
                                                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                                    <span class="d-block">Nakit</span>
                                                </label>
                                            </div>
                                            <div class="col-6">
                                                <input type="radio" class="btn-check" name="payment_method" id="pos" value="pos">
                                                <label class="btn btn-outline-success w-100" for="pos">
                                                    <i class="fas fa-credit-card fa-2x mb-2"></i>
                                                    <span class="d-block">POS</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <div class="payment-buttons-container">
                                            <?php if ($canTakePayment): ?>
                                                <button type="button" class="btn btn-success w-100" onclick="completePayment()">
                                                    <i class="fas fa-check-circle me-2"></i>Ödemeyi Tamamla
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($canSaveOrder): ?>
                                            <button type="button" class="btn btn-primary" onclick="saveNewItems()">
                                                <i class="fas fa-save me-2"></i>Siparişi Kaydet
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Masa Ekleme Modalı -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Masa Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTableForm">
                    <div class="mb-3">
                        <label for="tableNo" class="form-label">Masa Numarası</label>
                        <input type="text" class="form-control" id="tableNo" name="table_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Kapasite</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" value="4" min="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="addTable()">Masa Ekle</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-xl {
    max-width: 1200px;
}

.category-img {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
}

.product-img {
    height: 120px;
    object-fit: cover;
}

.payment-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 4px;
}

.order-item {
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
    margin-bottom: 10px;
}

.order-item:hover {
    background: #f0f1f2;
}

.order-item .quantity-controls {
    visibility: hidden;
    opacity: 0;
    transition: all 0.2s;
}

.order-item:hover .quantity-controls {
    visibility: visible;
    opacity: 1;
}

/* Scrollbar Stilleri */
.products-wrapper::-webkit-scrollbar,
.order-items-wrapper::-webkit-scrollbar {
    width: 6px;
}

.products-wrapper::-webkit-scrollbar-track,
.order-items-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.products-wrapper::-webkit-scrollbar-thumb,
.order-items-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.products-wrapper::-webkit-scrollbar-thumb:hover,
.order-items-wrapper::-webkit-scrollbar-thumb:hover {
    background: #666;
}
</style>

<script>
// Global değişkenler
let paymentModal = null;
let paymentItems = {};
let currentTotal = 0;
let currentTableOrders = [];
let currentTableId = null;

// Fiyat formatlama fonksiyonu
function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ₺';
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Modal nesnesini başlat
    const modalElement = document.getElementById('paymentModal');
    if (modalElement) {
        paymentModal = new bootstrap.Modal(modalElement);
    }

    // Kategori butonlarına tıklama olayı
    document.querySelectorAll('.payment-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Aktif kategori butonunu güncelle
            document.querySelectorAll('.payment-category-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            // Ürünleri göster/gizle
            const selectedCategory = this.dataset.category;
            document.querySelectorAll('.category-products').forEach(group => {
                group.style.display = group.dataset.category === selectedCategory ? 'block' : 'none';
            });
        });
    });
});

// Satış ekranını aç
window.openPaymentModal = function(tableId) {
    if (!userPermissions.canViewSales) {
        Swal.fire('Yetkisiz İşlem', 'Satış ekranını görüntüleme yetkiniz bulunmuyor.', 'error');
        return;
    }
    
    currentTableId = tableId;
    console.log('Opening modal for table:', currentTableId);

    // Masa bilgisini güncelle
    document.getElementById('paymentTableInfo').textContent = `Masa ${currentTableId}`;
    
    // Modal'ı aç
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
    
    // İlk kategoriyi seç
    const firstCategoryBtn = document.querySelector('.payment-category-btn');
    if (firstCategoryBtn) {
        selectCategory(firstCategoryBtn.dataset.category);
    }
    
    // Mevcut siparişleri yükle
    loadTableOrders(currentTableId);
}

// Ürün ekle
function addToPaymentOrder(productId, productName, productPrice) {
    if (!userPermissions.canAddOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş ekleme yetkiniz bulunmuyor.', 'error');
        return;
    }
    console.log('Adding Product:', { productId, productName, productPrice });

    if (!paymentItems[productId]) {
        paymentItems[productId] = {
            product_id: productId,
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            quantity: 1
        };
    } else {
        paymentItems[productId].quantity++;
    }
    updateNewOrderItems();
}

// Ürün çıkar
function removeFromPaymentOrder(productId) {
    if (paymentItems[productId] && paymentItems[productId].quantity > 0) {
        paymentItems[productId].quantity--;
        if (paymentItems[productId].quantity === 0) {
            delete paymentItems[productId];
        }
        updateNewOrderItems();
    }
}

// Yeni siparişleri güncelle
function updateNewOrderItems() {
    const container = document.getElementById('newOrderItems');
    let html = '';
    let total = 0;

    for (const [productId, item] of Object.entries(paymentItems)) {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        html += `
            <div class="order-item mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${item.name}</div>
                        <div class="text-muted small">
                            ${item.quantity} x ${formatPrice(item.price)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateNewItemQuantity('${productId}', -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateNewItemQuantity('${productId}', 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="removeNewItem('${productId}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <span class="text-primary fw-bold">
                            ${formatPrice(itemTotal)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    // Sipariş yoksa mesaj göster
    if (!html) {
        html = '<div class="text-center text-muted p-3">Henüz ürün eklenmedi</div>';
    }

    container.innerHTML = html;
    document.getElementById('paymentTotal').textContent = formatPrice(total);
    currentTotal = total;
}

// Masa siparişlerini yükle
function loadTableOrders(tableId, retryCount = 0) {
    console.log('Loading orders for table:', tableId);

    const container = document.getElementById('paymentOrderDetails');
    if (!container) {
        console.error('Container not found, retry count:', retryCount);
        
        if (retryCount < 3) {
            setTimeout(() => loadTableOrders(tableId, retryCount + 1), 100);
            return;
        } else {
            console.error('Container could not be found after 3 retries');
            return;
        }
    }

    fetch('ajax/get_table_orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ table_id: tableId })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Received orders data:', data);

        if (data.success) {
            let html = '';
            let total = 0;

            if (data.orders.length === 0) {
                html = '<div class="alert alert-info">Henüz sipariş bulunmuyor.</div>';
            } else {
                data.orders.forEach(order => {
                    const itemTotal = parseFloat(order.total);
                    total += itemTotal;

                    html += `
                        <div class="order-item mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">${order.product_name}</span>
                                    <div class="text-muted small">
                                        ${order.quantity} x ${formatPrice(order.price)}
                                        <span class="badge ${order.status === 'pending' ? 'bg-warning' : 'bg-info'} ms-2">
                                            ${order.status === 'pending' ? 'Bekliyor' : 'Hazırlanıyor'}
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="updateOrderQuantity(${order.order_id}, ${order.item_id}, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="btn btn-outline-secondary disabled">
                                            ${order.quantity}
                                        </span>
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="updateOrderQuantity(${order.order_id}, ${order.item_id}, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="removeOrderItem(${order.order_id}, ${order.item_id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <span class="text-primary fw-bold">
                                        ${formatPrice(itemTotal)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            container.innerHTML = html;
            document.getElementById('paymentTotal').textContent = formatPrice(total);
            console.log('Orders rendered successfully');
        } else {
            throw new Error(data.message || 'Siparişler yüklenirken bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Sipariş yükleme hatası:', error);
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    Siparişler yüklenirken bir hata oluştu: ${error.message}
                </div>
            `;
        }
    });
}

// Sipariş kaydedildikten sonra modalı güncelle
function updateModalAfterSave() {
    // Mevcut siparişleri yeniden yükle
    loadTableOrders(currentTableId);
    
    // Yeni sipariş listesini temizle
    paymentItems = {};
    updateNewOrderItems();
    
    // Başarı mesajı göster
    Swal.fire({
        icon: 'success',
        title: 'Başarılı!',
        text: 'Sipariş kaydedildi',
        showConfirmButton: false,
        timer: 1500
    });
}

// Siparişi kaydet
function saveNewItems() {
    if (!userPermissions.canSaveOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş kaydetme yetkiniz bulunmuyor.', 'error');
        return;
    }

    if (Object.keys(paymentItems).length === 0) {
        Swal.fire('Uyarı!', 'Lütfen sipariş ekleyin', 'warning');
        return;
    }

    // Debug için
    console.log('Current Table ID:', currentTableId);
    console.log('Payment Items:', paymentItems);

    // Sipariş verilerini hazırla
    const formData = new FormData();
    formData.append('table_id', currentTableId);
    formData.append('notes', '');
    
    const items = {};
    Object.values(paymentItems).forEach(item => {
        items[item.product_id || item.id] = {
            quantity: parseInt(item.quantity),
            price: parseFloat(item.price)
        };
    });
    
    formData.append('items', JSON.stringify(items));

    // Doğru endpoint'i kullan: admin/ajax/save_table_order.php
    fetch('ajax/save_table_order.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        console.log('Server Response:', text); // Debug için
        return JSON.parse(text);
    })
    .then(data => {
        if (data.success) {
            // Mevcut siparişleri yeniden yükle
            loadTableOrders(currentTableId);
            
            // Yeni sipariş listesini temizle
            paymentItems = {};
            updateNewOrderItems();
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'success',
                title: data.message // Sunucudan gelen mesajı kullan
            });
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Hata!', error.message, 'error');
    });
}

// Ödemeyi tamamla
function completePayment() {
    if (!userPermissions.canTakePayment) {
        Swal.fire('Yetkisiz İşlem', 'Ödeme alma yetkiniz bulunmuyor.', 'error');
        return;
    }

    // Önce yeni eklenen ürünleri kontrol et
    if (Object.keys(paymentItems).length > 0) {
        Swal.fire({
            title: 'Bekleyen Siparişler',
            text: 'Kaydedilmemiş yeni siparişler var. Önce bu siparişleri kaydetmeniz gerekmektedir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Siparişleri Kaydet',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                saveNewItems(); // Yeni siparişleri kaydet
            }
        });
        return;
    }

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    
    if (!paymentMethod) {
        Swal.fire('Uyarı!', 'Lütfen bir ödeme yöntemi seçin', 'warning');
        return;
    }
    
    if (currentTotal <= 0) {
        Swal.fire('Uyarı!', 'Ödenecek tutar bulunamadı', 'warning');
        return;
    }

    Swal.fire({
        title: 'Ödeme Onayı',
        text: `Toplam ${formatPrice(currentTotal)} tutarındaki ödemeyi ${paymentMethod === 'cash' ? 'nakit' : 'POS'} ile tamamlamak istiyor musunuz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Tamamla',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/complete_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    table_id: currentTableId,
                    payment_method: paymentMethod,
                    total_amount: currentTotal
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ödeme Tamamlandı!',
                        text: 'Ödeme başarıyla kaydedildi',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#paymentModal').modal('hide');
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Sipariş öğesi HTML'i
function getOrderItemHtml(order, item) {
    return `
        <div class="order-item" data-order-id="${order.id}" data-item-id="${item.id}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${item.name}</div>
                    <div class="text-muted small">
                        <span class="item-quantity">${item.quantity}</span> x 
                        ${parseFloat(item.price).toFixed(2)} ₺
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="quantity-controls btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="updateOrderQuantity(${order.id}, ${item.id}, -1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="updateOrderQuantity(${order.id}, ${item.id}, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="removeOrderItem(${order.id}, ${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <span class="item-price text-primary fw-bold">
                        ${(item.quantity * item.price).toFixed(2)} ₺
                    </span>
                </div>
            </div>
        </div>
    `;
}

// Sipariş öğesi sil
function removeOrderItem(orderId, itemId) {
    if (!userPermissions.canDeleteOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş silme yetkiniz bulunmuyor.', 'error');
        return;
    }

    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu siparişi silmek istediğinize emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../ajax/remove_order_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTableOrders(currentTableId);
                    Swal.fire('Başarılı!', 'Sipariş başarıyla silindi.', 'success');
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Kategori seçimi
function selectCategory(categoryId) {
    // Tüm kategori butonlarını pasif yap
    document.querySelectorAll('.payment-category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Seçili kategoriyi aktif yap
    const selectedBtn = document.querySelector(`.payment-category-btn[data-category="${categoryId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }
    
    // Tüm ürün listelerini gizle
    document.querySelectorAll('.category-products').forEach(div => {
        div.style.display = 'none';
    });
    
    // Seçili kategorinin ürünlerini göster
    const productDiv = document.querySelector(`.category-products[data-category="${categoryId}"]`);
    if (productDiv) {
        productDiv.style.display = 'block';
    }
}

// Mevcut sipariş miktarını güncelle
function updateOrderQuantity(orderId, itemId, change) {
    if (!userPermissions.canEditOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş güncelleme yetkiniz bulunmuyor.', 'error');
        return;
    }

    fetch('../ajax/update_order_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            item_id: itemId,
            change: change
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTableOrders(currentTableId);
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', error.message, 'error');
    });
}

// Yeni sipariş miktarını güncelle
function updateNewItemQuantity(productId, change) {
    if (!userPermissions.canEditOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş güncelleme yetkiniz bulunmuyor.', 'error');
        return;
    }
    console.log('Updating new item quantity:', { productId, change }); // Debug için

    if (paymentItems[productId]) {
        const newQuantity = Math.max(1, paymentItems[productId].quantity + change);
        
        if (newQuantity === 0) {
            // Miktar 0 ise ürünü sil
            delete paymentItems[productId];
        } else {
            paymentItems[productId].quantity = newQuantity;
        }
        
        updateNewOrderItems();
    }
}

// Yeni ürünü sil
function removeNewItem(productId) {
    if (!userPermissions.canDeleteOrder) {
        Swal.fire('Yetkisiz İşlem', 'Sipariş silme yetkiniz bulunmuyor.', 'error');
        return;
    }
    console.log('Removing new item:', productId); // Debug için

    if (paymentItems[productId]) {
        delete paymentItems[productId];
        updateNewOrderItems();
    }
}

// Yeni masa modalını göster
function showAddTableModal() {
    if (!userPermissions.canManageTables) {
        Swal.fire('Yetkisiz İşlem', 'Masa yönetim yetkiniz bulunmuyor.', 'error');
        return;
    }

    const modalElement = document.getElementById('addTableModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Yeni masa kaydet
function saveTable() {
    if (!userPermissions.canManageTables) {
        Swal.fire('Yetkisiz İşlem', 'Masa yönetim yetkiniz bulunmuyor.', 'error');
        return;
    }

    const tableNumber = document.getElementById('tableNumber').value;
    
    if (!tableNumber) {
        Swal.fire('Hata!', 'Masa numarası giriniz', 'error');
        return;
    }

    fetch('api/add_table.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            table_no: tableNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            Swal.fire('Başarılı!', 'Masa eklendi', 'success')
            .then(() => location.reload());
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', error.message, 'error');
    });
}

// QR kod oluştur
function showQRCode(tableId) {
    if (typeof qrcode === 'undefined') {
        console.error('QR Code kütüphanesi yüklenemedi!');
        return;
    }
    
    const url = `${window.location.origin}/menu.php?table=${tableId}`;
    const qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();
    
    Swal.fire({
        title: 'Masa QR Kodu',
        html: `
            <div class="text-center">
                ${qr.createImgTag(5)}
                <div class="mt-3">
                    <button class="btn btn-primary me-2" onclick="downloadQR('png', '${tableId}')">
                        <i class="fas fa-download"></i> PNG İndir
                    </button>
                    <button class="btn btn-success" onclick="downloadQR('svg', '${tableId}')">
                        <i class="fas fa-download"></i> SVG İndir
                    </button>
                </div>
            </div>
        `,
        showConfirmButton: false,
        width: 400
    });
}

// QR kodu indir
function downloadQR(format, tableId) {
    const url = `${window.location.origin}/menu.php?table=${tableId}`;
    const qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();

    if (format === 'svg') {
        // SVG indir
        const svgString = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' + qr.createSvgTag(5, 0);
        
        const blob = new Blob([svgString], { type: 'image/svg+xml' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `masa_${tableId}_qr.svg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } else {
        // PNG indir
        const canvas = document.createElement('canvas');
        const qrImage = new Image();
        qrImage.src = 'data:image/svg+xml;base64,' + btoa(svgString);
        qrImage.onload = function() {
            canvas.width = qrImage.width;
            canvas.height = qrImage.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(qrImage, 0, 0);
            
            canvas.toBlob(function(blob) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `masa_${tableId}_qr.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 'image/png');
        };
    }
}

// Masa düzenle
function editTable(tableId) {
    if (!userPermissions.canManageTables) {
        Swal.fire('Yetkisiz İşlem', 'Masa yönetim yetkiniz bulunmuyor.', 'error');
        return;
    }

    Swal.fire({
        title: 'Masa Numarası',
        input: 'number',
        inputLabel: 'Yeni masa numarasını giriniz',
        showCancelButton: true,
        confirmButtonText: 'Güncelle',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            fetch('api/update_table.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: tableId,
                    table_number: result.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Başarılı!', 'Masa güncellendi', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Masa sil
function deleteTable(tableId) {
    if (!userPermissions.canManageTables) {
        Swal.fire('Yetkisiz İşlem', 'Masa yönetim yetkiniz bulunmuyor.', 'error');
        return;
    }

    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu masa silinecek!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_table.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: tableId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Silindi!', 'Masa silindi', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Sipariş kodu üret
function generateOrderCode(tableId) {
    Swal.fire({
        title: 'Sipariş Kodu Üret',
        text: 'Bu masa için yeni bir sipariş kodu üretilecek. Onaylıyor musunuz?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Üret',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/generate_order_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    table_id: tableId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Kod Üretildi!',
                        html: `Yeni sipariş kodu: <strong>${data.code}</strong>`,
                        icon: 'success'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Satış modalını açma fonksiyonu
function openSalesModal(tableId) {
    const modal = document.getElementById('salesModal');
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Masa ${tableId} - Siparişler</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderItems">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    modal.addEventListener('shown.bs.modal', function () {
        loadTableOrders(tableId);
    }, { once: true }); // Event listener'ı bir kez çalıştır
}
</script>

<!-- QR Code kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<!-- JavaScript Dosyaları -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>

<!-- Bootstrap ve diğer gerekli scriptler -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/tables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>

</body>
</html>