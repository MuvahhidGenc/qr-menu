<?php require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ana sayfa görüntüleme yetkisi kontrolü
if (!hasPermission('products.view')) {
    header('Location: dashboard.php');
    exit();
}

// Yetki kontrollerini tanımla
$canViewProducts = hasPermission('products.view');
$canAddProduct = hasPermission('products.add');
$canEditProduct = hasPermission('products.edit');
$canDeleteProduct = hasPermission('products.delete');

// Yetkileri tanımla
$canViewProducts = hasPermission('products.view');
$canAddProduct = hasPermission('products.add');
$canEditProduct = hasPermission('products.edit');
$canDeleteProduct = hasPermission('products.delete');

// Sayfa içeriği
$db = new Database();

// Sistem parametrelerini kontrol et
$posSettings = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('system_barcode_sales_enabled', 'system_stock_tracking')")->fetchAll();
$systemParams = [];
foreach ($posSettings as $setting) {
    $systemParams[$setting['setting_key']] = $setting['setting_value'];
}
$barcodeSalesEnabled = isset($systemParams['system_barcode_sales_enabled']) && $systemParams['system_barcode_sales_enabled'] == '1';
$stockTrackingEnabled = isset($systemParams['system_stock_tracking']) && $systemParams['system_stock_tracking'] == '1';

// Kategorileri sıralı şekilde getir (status filtresi kaldırıldı)
$categories = $db->query("
    SELECT * FROM categories 
    ORDER BY sort_order, name"
)->fetchAll();

// Ürünleri sıralı şekilde getir
$products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.category_id, p.sort_order, p.name"
)->fetchAll();

include 'navbar.php';
?>
<!-- Toastr ve diğer gerekli kütüphaneler -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Önce jQuery yükleyin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Sonra Sortable.js'i yükleyin -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- En son toastr'ı yükleyin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Toastr ayarları
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000"
};
</script>

<style>
/* Ana container stilleri */
.products-container {
    padding: 20px;
    background: #f8f9fa;
}

/* Kart stilleri */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    background: white;
}

.card-header {
    background: white;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
    border-radius: 15px 15px 0 0 !important;
}

.card-body {
    padding: 1.5rem;
}

/* Kategori stilleri */
.category-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
}

.category-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.category-header {
    padding: 1rem;
}

.category-header:hover {
    background: rgba(67,97,238,0.03);
}

.category-drag-handle {
    color: #a0aec0;
    cursor: grab;
    padding: 0.5rem;
    margin: -0.5rem;
    transition: all 0.3s ease;
}

.category-drag-handle:hover {
    color: #4361ee;
    background: rgba(67,97,238,0.1);
    border-radius: 8px;
}

.category-drag-handle:active {
    cursor: grabbing;
}

.category-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
}

.category-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.category-details {
    display: flex;
    flex-direction: column;
}

.category-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.2rem !important;
}

.product-count {
    font-size: 0.85rem;
    color: #6c757d;
}

.category-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-actions .btn {
    padding: 0.4rem 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.category-actions .btn:hover {
    transform: translateY(-1px);
}

.btn-outline-primary {
    color: #4361ee;
    border-color: #4361ee;
}

.btn-outline-primary:hover {
    background: #4361ee;
    color: white;
}

/* Kategori başlığı aktif olduğunda */
.category-header.active {
    background: rgba(67,97,238,0.05);
    border-radius: 12px 12px 0 0;
}

/* Gap utility class */
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 1rem; }

/* Ürün stilleri */
.product-row {
    background: white;
    margin: 0.5rem;
    padding: 1rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.product-row:hover {
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.product-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

/* Ürün görseli stilleri */
.product-image {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

/* Ürün bilgi stilleri */
.product-info {
    flex: 1;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.product-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.product-price {
    font-weight: 600;
    color: #2ecc71;
    font-size: 1.1rem;
}

/* Buton stilleri */
.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #4361ee;
    border-color: #4361ee;
}

.btn-primary:hover {
    background: #3730a3;
    border-color: #3730a3;
    transform: translateY(-1px);
}

.btn-success {
    background: #2ecc71;
    border-color: #2ecc71;
}

.btn-success:hover {
    background: #27ae60;
    border-color: #27ae60;
    transform: translateY(-1px);
}

/* Toggle switch stilleri */
.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
    background-color: #e9ecef;
    border-color: #e9ecef;
}

.form-switch .form-check-input:checked {
    background-color: #4361ee;
    border-color: #4361ee;
}

.form-switch .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(67,97,238,0.25);
}

/* Animasyon stilleri */
.category-products {
    transition: all 0.3s ease-in-out;
}

/* Boş açıklama stili */
.editable-description em {
    color: #a0aec0;
    font-style: italic;
}

/* Aksiyon butonları */
.product-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-outline-danger {
    color: #e74c3c;
    border-color: #e74c3c;
}

.btn-outline-danger:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-1px);
}

/* Mobil düzenlemeler */
@media (max-width: 768px) {
    .products-container {
        padding: 10px;
    }

    /* Kategori başlığını mobilde düzenle */
    .category-header {
        flex-direction: column;
        gap: 1rem;
    }

    /* Kategori bilgilerini mobilde düzenle */
    .category-info {
        width: 100%;
        flex-direction: column;
        text-align: center;
    }

    /* Kategori görselini mobilde büyüt */
    .category-image {
        width: 80px;
        height: 80px;
    }

    /* Kategori aksiyonlarını mobilde düzenle */
    .category-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Switch'i mobilde düzenle */
    .form-switch {
        display: flex;
        justify-content: center;
        width: 100%;
        margin: 0.5rem 0;
    }

    /* Butonları mobilde düzenle */
    .btn {
        width: 100%;
        margin: 0.25rem 0;
    }

    /* Ürün satırını mobilde düzenle */
    .product-content {
        flex-direction: column;
        text-align: center;
    }

    /* Ürün görselini mobilde düzenle */
    .product-image {
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }

    /* Ürün bilgilerini mobilde düzenle */
    .product-info {
        width: 100%;
        text-align: center;
    }

    /* Ürün aksiyonlarını mobilde düzenle */
    .product-actions {
        width: 100%;
        justify-content: center;
        margin-top: 1rem;
    }

    /* Sürükleme ikonunu mobilde gizle */
    .product-drag-handle,
    .category-drag-handle {
        display: none !important;
    }

    /* Modal içeriğini mobilde düzenle */
    .modal-dialog {
        margin: 0.5rem;
    }

    .modal-body {
        padding: 1rem;
    }

    /* Form elemanlarını mobilde düzenle */
    .form-control {
        margin-bottom: 0.5rem;
    }

    /* Toastr bildirimlerini mobilde düzenle */
    #toast-container {
        padding: 0.5rem;
        width: 100%;
    }
}

/* Pasif ürün stili */
.product-row[data-status="0"] {
    opacity: 0.6;
    background: #f8f9fa;
}

/* Hover efektleri */
.editable-field:hover {
    background: rgba(67,97,238,0.1);
    border-radius: 4px;
    padding: 2px 4px;
    margin: -2px -4px;
}

/* Modal stilleri */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 15px 15px 0 0;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

/* Form elemanları stilleri */
.form-control {
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    padding: 0.6rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 0.2rem rgba(67,97,238,0.25);
}

/* Görsel önizleme stilleri */
.image-preview-container {
    width: 100%;
    height: 200px;
    border-radius: 10px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-preview-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Input group stilleri */
.input-group {
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid rgba(0,0,0,0.1);
    color: #6c757d;
}

/* Switch stilleri */
.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-top: 0.2em;
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>
<div class="container-fluid products-container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Ürünler</h5>
            <div class="d-flex gap-2">
                <?php if ($canAddProduct): ?>
                <button class="btn btn-primary add-product-button" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Yeni Ürün
                </button>
                <?php endif; ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Yeni Kategori
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="category-list">
                <?php foreach ($categories as $category): ?>
                <div class="category-item" data-category-id="<?= $category['id'] ?>" style="opacity: <?= $category['status'] ? '1' : '0.6' ?>">
                    <div class="category-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-bars category-drag-handle"></i>
                            <div class="category-info d-flex align-items-center gap-3">
                                <img src="<?= $category['image'] ? '../uploads/' . $category['image'] : '../assets/images/default-category.jpg' ?>" 
                                     class="category-image" 
                                     alt="<?= htmlspecialchars($category['name']) ?>">
                                <div class="category-details">
                                    <h5 class="category-name mb-0"><?= htmlspecialchars($category['name']) ?></h5>
                                    <span class="product-count">
                                        <?php 
                                        $count = count(array_filter($products, function($p) use ($category) {
                                            return $p['category_id'] == $category['id'];
                                        }));
                                        echo "<small class='text-muted'>$count ürün</small>";
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="category-actions d-flex align-items-center gap-2">
                            <!-- Görünürlük toggle'ı -->
                            <div class="form-check form-switch me-2">
                                <input type="checkbox" 
                                       class="form-check-input category-status-toggle" 
                                       id="categoryStatus_<?= $category['id'] ?>"
                                       data-category-id="<?= $category['id'] ?>"
                                       <?= $category['status'] ? 'checked' : '' ?>>
                            </div>

                            <?php if ($canAddProduct): ?>
                            <button class="btn btn-sm btn-outline-primary quick-add-product" 
                                    data-category-id="<?= $category['id'] ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#quickAddProductModal">
                                <i class="fas fa-plus"></i>
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($canDeleteProduct): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-category" data-id="<?= $category['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="category-products" style="display: none;">
                        <?php 
                        $categoryProducts = array_filter($products, function($product) use ($category) {
                            return $product['category_id'] == $category['id'];
                        });
                        foreach ($categoryProducts as $product): 
                        ?>
                        <div class="product-row" data-product-id="<?= $product['id'] ?>">
                            <div class="product-content">
                                <div class="product-image-container">
                                    <div class="editable-image" data-type="image" data-field="image">
                                        <img src="../uploads/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                                             alt="Ürün resmi" 
                                             class="product-image">
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h6 class="product-name editable" data-field="name">
                                        <span class="editable-field" 
                                              data-field="name" 
                                              data-type="text">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </span>
                                    </h6>
                                    <div class="editable-description" data-type="description" data-field="description">
                                        <?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?>
                                    </div>
                                    <div class="product-price editable" data-field="price">
                                        <span class="editable-field" 
                                              data-field="price" 
                                              data-type="price">
                                            <?php echo number_format($product['price'], 2); ?> ₺
                                        </span>
                                    </div>
                                    
                                    <?php if ($barcodeSalesEnabled): ?>
                                    <!-- Barkod Bilgisi (POS Aktifse) -->
                                    <div class="product-barcode mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-barcode text-primary"></i> Barkod:
                                        </small>
                                        <span class="editable-field ms-1" 
                                              data-field="barcode" 
                                              data-type="text"
                                              style="font-family: monospace; color: #495057; cursor: pointer;">
                                            <?php echo !empty($product['barcode']) ? htmlspecialchars($product['barcode']) : '<span style="color: #dc3545;">Girilmedi</span>'; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($stockTrackingEnabled): ?>
                                    <!-- Stok Bilgisi (Stok Takibi Aktifse) -->
                                    <div class="product-stock mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-boxes text-success"></i> Stok:
                                        </small>
                                        <span class="editable-field ms-1" 
                                              data-field="stock" 
                                              data-type="number"
                                              style="cursor: pointer;">
                                            <?php 
                                            $stock = $product['stock'] ?? 0;
                                            $stockClass = $stock == 0 ? 'text-danger' : ($stock < 10 ? 'text-warning' : 'text-success');
                                            $stockIcon = $stock == 0 ? 'fa-times-circle' : ($stock < 10 ? 'fa-exclamation-triangle' : 'fa-check-circle');
                                            ?>
                                            <strong class="<?= $stockClass ?>">
                                                <i class="fas <?= $stockIcon ?>"></i> <?= $stock ?> Adet
                                            </strong>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" 
                                               type="checkbox" 
                                               data-product-id="<?= $product['id'] ?>"
                                               <?= $product['status'] ? 'checked' : '' ?>>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-info print-label" 
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>"
                                            data-barcode="<?= htmlspecialchars($product['barcode'] ?? '') ?>"
                                            data-price="<?= $product['price'] ?>"
                                            title="Etiket Yazdır">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <?php if ($canDeleteProduct): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-product" 
                                            data-id="<?= $product['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Ürün Ekleme Modal -->
    <div class="modal fade" id="addProductModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Ürün</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm">
                        <div class="row">
                            <!-- Sol Grid -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ürün Adı</label>
                                    <input type="text" class="form-control" id="productName" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-control" id="productCategory" required>
                                        <option value="">Kategori Seçin</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>">
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Açıklama</label>
                                    <textarea class="form-control" id="productDescription" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <!-- Sağ Grid -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fiyat</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="productPrice" step="0.01" required>
                                        <span class="input-group-text">₺</span>
                                    </div>
                                </div>
                                
                                <?php if ($barcodeSalesEnabled): ?>
                                <!-- Barkod Alanı (POS Aktifse Zorunlu) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-barcode text-primary"></i> Barkod No
                                        <span class="badge bg-danger ms-1">Zorunlu</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        <input type="text" class="form-control" id="productBarcode" 
                                               placeholder="Barkod numarası girin" required>
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="generateRandomBarcode()" 
                                                title="Rastgele barkod oluştur">
                                            <i class="fas fa-random"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">POS satış sistemi aktif - Barkod zorunlu</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($stockTrackingEnabled): ?>
                                <!-- Stok Alanı (Stok Takibi Aktifse Zorunlu) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-boxes text-success"></i> Stok Miktarı
                                        <span class="badge bg-danger ms-1">Zorunlu</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        <input type="number" class="form-control" id="productStock" 
                                               min="0" value="0" required>
                                        <span class="input-group-text">Adet</span>
                                    </div>
                                    <small class="text-muted">Stok takibi aktif - Başlangıç stoku girin</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-4">
                                    <label class="form-label">Ürün Görseli</label>
                                    <div class="image-preview-container mb-2">
                                        <img id="productImagePreview" src="../assets/images/default-product.jpg" 
                                             class="img-thumbnail product-preview-image">
                                    </div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="productImage" readonly>
                                        <button type="button" class="btn btn-primary select-media" data-target="productImage">
                                            <i class="fas fa-image"></i> Dosya Seç
                                        </button>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" class="form-check-input" id="productStatus" checked>
                                    <label class="form-check-label">Ürün Aktif</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" form="addProductForm" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ekle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ürün Düzenleme Modal -->
    <div class="modal fade" id="editProductModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ürün Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProductForm" method="POST">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Ürün Adı</label>
                                    <input type="text" name="name" id="edit_product_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Açıklama</label>
                                    <textarea name="description" id="edit_product_description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label>Kategori</label>
                                    <select name="category_id" id="edit_product_category" class="form-control" required>
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>">
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Fiyat</label>
                                    <input type="number" step="0.01" name="price" id="edit_product_price" class="form-control" required>
                                </div>
                                
                                <?php if ($barcodeSalesEnabled): ?>
                                <!-- Barkod Alanı (POS Aktifse Zorunlu) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-barcode text-primary"></i> Barkod No
                                        <span class="badge bg-danger ms-1">Zorunlu</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        <input type="text" class="form-control" name="barcode" id="edit_product_barcode" 
                                               placeholder="Barkod numarası" required>
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="generateRandomBarcodeEdit()" 
                                                title="Rastgele barkod oluştur">
                                            <i class="fas fa-random"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($stockTrackingEnabled): ?>
                                <!-- Stok Alanı (Stok Takibi Aktifse) -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-boxes text-success"></i> Stok Miktarı
                                        <span class="badge bg-info ms-1">Mevcut</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                        <input type="number" class="form-control" name="stock" id="edit_product_stock" 
                                               min="0" readonly style="background: #f8f9fa;">
                                        <span class="input-group-text">Adet</span>
                                        <button type="button" class="btn btn-success" 
                                                onclick="openStockAdjustModal()" 
                                                title="Stok düzenle">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                    </div>
                                    <small class="text-muted">Stok düzenlemek için "Düzenle" butonuna tıklayın</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                     <div class="mb-2">
                                         <img id="editProductImagePreview" 
                                              src="<?= $product['image'] ? '../uploads/' . htmlspecialchars($product['image']) : '' ?>" 
                                              style="max-height:100px;<?= $product['image'] ? '' : 'display:none' ?>" 
                                              class="img-thumbnail">
                                     </div>
                                     <div class="input-group">
                                         <input type="hidden" id="editProductImage" name="image">
                                         <input type="text" class="form-control" id="editProductImageDisplay" readonly>
                                         <button type="button" class="btn btn-primary" onclick="openMediaModal('editProductImage', 'editProductImagePreview')">
                                             <i class="fas fa-image"></i> Dosya Seç
                                         </button>
                                     </div>
                                 </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="status" id="edit_product_status" class="form-check-input">
                                        <label class="form-check-label">Aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Product Modal -->
    <div class="modal fade" id="quickAddProductModal" tabindex="-1" role="dialog" aria-labelledby="quickAddProductModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hızlı Ürün Ekle - <span id="categoryNameSpan"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddProductForm">
                        <input type="hidden" id="quick_category_id" name="category_id">
                        <input type="hidden" id="quickProductImage" name="image">
                        
                        <div class="mb-3">
                            <label class="form-label">Ürün Resmi</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img id="quickProductImagePreview" src="" style="display:none; max-width: 100px; max-height: 100px; object-fit: cover;">
                                <button type="button" id="quickProductImageSelect" class="btn btn-sm btn-primary">
                                    <i class="fas fa-image"></i> Resim Seç
                                </button>
                                <button type="button" id="quickProductImageRemove" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control" id="quickProductImagePath" readonly placeholder="Resim yolu burada görünecek">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ürün Adı</label>
                            <input type="text" class="form-control" id="quick_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fiyat</label>
                            <input type="number" step="0.01" class="form-control" id="quick_price" required>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="quick_status" checked>
                                <label class="form-check-label">Aktif</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" form="quickAddProductForm" class="btn btn-primary">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kategori Ekleme Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <div class="mb-3">
                            <label class="form-label">Kategori Adı</label>
                            <input type="text" class="form-control" id="categoryName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori Görseli</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="categoryImage" readonly>
                                <button type="button" class="btn btn-primary select-media" data-target="categoryImage">
                                    <i class="fas fa-image"></i> Seç
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="saveCategory()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stok Düzenleme Modal -->
    <?php if ($stockTrackingEnabled): ?>
    <div class="modal fade" id="stockAdjustModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-boxes me-2"></i>Stok Düzenleme
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="stock-display">
                            <span class="text-muted">Mevcut Stok</span>
                            <h2 class="mb-0" id="currentStockDisplay">0</h2>
                            <span class="text-muted">Adet</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">İşlem Tipi</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="stockOperation" id="stockAdd" value="add" checked>
                            <label class="btn btn-outline-success" for="stockAdd">
                                <i class="fas fa-plus-circle"></i> Stok Ekle
                            </label>
                            
                            <input type="radio" class="btn-check" name="stockOperation" id="stockRemove" value="remove">
                            <label class="btn btn-outline-danger" for="stockRemove">
                                <i class="fas fa-minus-circle"></i> Stok Çıkar
                            </label>
                            
                            <input type="radio" class="btn-check" name="stockOperation" id="stockSet" value="set">
                            <label class="btn btn-outline-primary" for="stockSet">
                                <i class="fas fa-edit"></i> Manuel Ayarla
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <span id="stockAmountLabel">Eklenecek Miktar</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                            <input type="number" class="form-control" id="stockAmount" min="0" value="0" required>
                            <span class="input-group-text">Adet</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Not <span class="text-muted">(Opsiyonel)</span></label>
                        <textarea class="form-control" id="stockNote" rows="3" placeholder="Stok hareket notu..."></textarea>
                    </div>
                    
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong id="resultPreview">Yeni Stok: 0 Adet</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> İptal
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveStockAdjustment()">
                        <i class="fas fa-check"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Kategori açma/kapama animasyonları
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', function(e) {
                // Eğer tıklanan element butonlardan biri ise, açma/kapama işlemini engelle
                if (e.target.closest('.category-actions') || e.target.closest('.category-drag-handle')) {
                    return;
                }
                
                const categoryItem = this.closest('.category-item');
                const categoryProducts = categoryItem.querySelector('.category-products');
                
                if (categoryProducts) {
                    // Animasyon için gerekli stiller
                    categoryProducts.style.transition = 'all 0.3s ease';
                    categoryProducts.style.overflow = 'hidden';
                    
                    if (categoryProducts.style.display === 'none' || !categoryProducts.style.display) {
                        // Açılma animasyonu
                        categoryProducts.style.display = 'block';
                        const height = categoryProducts.scrollHeight;
                        categoryProducts.style.maxHeight = '0px';
                        
                        // Force reflow
                        categoryProducts.offsetHeight;
                        
                        categoryProducts.style.maxHeight = height + 'px';
                        
                        // Kategori header'a active class ekle
                        this.classList.add('active');
                        
                        // Ok ikonunu döndür (eğer varsa)
                        const arrow = this.querySelector('.category-arrow');
                        if (arrow) {
                            arrow.style.transform = 'rotate(180deg)';
                        }
                        
                        // Animasyon tamamlandıktan sonra
                        setTimeout(() => {
                            categoryProducts.style.maxHeight = 'none';
                        }, 300);
                    } else {
                        // Kapanma animasyonu
                        const height = categoryProducts.scrollHeight;
                        categoryProducts.style.maxHeight = height + 'px';
                        
                        // Force reflow
                        categoryProducts.offsetHeight;
                        
                        categoryProducts.style.maxHeight = '0px';
                        
                        // Kategori header'dan active class'ı kaldır
                        this.classList.remove('active');
                        
                        // Ok ikonunu eski haline getir (eğer varsa)
                        const arrow = this.querySelector('.category-arrow');
                        if (arrow) {
                            arrow.style.transform = 'rotate(0deg)';
                        }
                        
                        // Animasyon tamamlandıktan sonra
                        setTimeout(() => {
                            categoryProducts.style.display = 'none';
                            categoryProducts.style.maxHeight = 'none';
                        }, 300);
                    }
                }
            });
        });

        // Media seçici için gerekli kodlar
        let selectedImageElement = null;

        // Hızlı ürün ekleme modalında resim seçme butonu
        document.getElementById('quickProductImageSelect').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Media modalını aç - focustrap devre dışı
            const mediaModalElement = document.getElementById('mediaModal');
            mediaModalElement.setAttribute('data-bs-focus', 'false');
            const mediaModal = new bootstrap.Modal(mediaModalElement, {
                focus: false
            });
            mediaModal.show();
            
            // Özel bir selectMedia fonksiyonu tanımla
            window.selectMedia = function(mediaUrl) {
                const imageFileName = String(mediaUrl).split('/').pop();
                const fullPath = 'uploads/' + imageFileName;
                
                // Hızlı ürün ekleme modalındaki resim alanlarını güncelle
                document.getElementById('quickProductImagePreview').src = '../uploads/' + imageFileName;
                document.getElementById('quickProductImagePreview').style.display = 'block';
                document.getElementById('quickProductImage').value = imageFileName;
                document.getElementById('quickProductImagePath').value = fullPath;
                
                // Media modalını kapat
                mediaModal.hide();
                
                // Modal arkaplanını temizle
                document.body.classList.remove('modal-open');
                const modalBackdrop = document.querySelector('.modal-backdrop');
                if (modalBackdrop) {
                    modalBackdrop.remove();
                }
            };
        });

        // Resim silme işlemi
        document.getElementById('quickProductImageRemove').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('quickProductImage').value = '';
            document.getElementById('quickProductImagePreview').style.display = 'none';
            document.getElementById('quickProductImagePreview').src = '';
            document.getElementById('quickProductImagePreview').src = '';
        });

        // Hızlı ürün ekleme form submit
        document.getElementById('quickAddProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('name', document.getElementById('quick_name').value);
            formData.append('category_id', document.getElementById('quick_category_id').value);
            formData.append('price', document.getElementById('quick_price').value);
            formData.append('image', document.getElementById('quickProductImage').value);
            formData.append('status', document.getElementById('quick_status').checked ? '1' : '0');
            formData.append('description', '');

            fetch('add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Ürün başarıyla eklendi');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                toastr.error(error.message || 'Ürün eklenirken bir hata oluştu');
            });
        });

        // Quick Add Product butonuna tıklandığında kategori ID'sini set et
        document.querySelectorAll('.quick-add-product').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.dataset.categoryId;
                document.getElementById('quick_category_id').value = categoryId;
                
                // Kategori adını modalda göster
                const categoryName = this.closest('.category-header').querySelector('.category-name').textContent;
                document.getElementById('categoryNameSpan').textContent = categoryName;
            });
        });

        // Inline edit için yardımcı fonksiyonlar
        function handleBlur(e) {
            const input = e.target;
            const field = input.nextElementSibling;
            let currentValue = field.textContent.trim();
            const newValue = input.value.trim();
            const fieldType = field.dataset.type;
            const fieldName = field.dataset.field;
            const productId = field.closest('.product-row').dataset.productId;
            
            // Stok için özel temizleme
            if (fieldName === 'stock') {
                currentValue = currentValue.replace(/[^\d]/g, ''); // Sadece rakamları al
            }
            
            // Barkod için özel temizleme
            if (fieldName === 'barcode') {
                currentValue = currentValue.replace('Girilmedi', '').trim();
            }

            if (newValue !== currentValue) {
                // AJAX ile güncelle
                fetch('api/update_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: productId,
                        field: fieldName,
                        value: newValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Başarılı - içeriği güncelle
                        if (fieldType === 'price') {
                            field.textContent = newValue + ' ₺';
                        } else if (fieldName === 'stock') {
                            // Stok için renk ve ikon güncelle
                            const stockVal = parseInt(newValue) || 0;
                            const stockClass = stockVal == 0 ? 'text-danger' : (stockVal < 10 ? 'text-warning' : 'text-success');
                            const stockIcon = stockVal == 0 ? 'fa-times-circle' : (stockVal < 10 ? 'fa-exclamation-triangle' : 'fa-check-circle');
                            field.innerHTML = `<strong class="${stockClass}"><i class="fas ${stockIcon}"></i> ${stockVal} Adet</strong>`;
                        } else if (fieldName === 'barcode') {
                            field.textContent = newValue || 'Girilmedi';
                            field.style.color = newValue ? '#495057' : '#dc3545';
                        } else {
                            field.textContent = newValue;
                        }
                        toastr.success('Güncelleme başarılı');
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                    // Hata durumunda eski değeri geri yükle
                    if (fieldName === 'price') {
                        field.textContent = currentValue + ' ₺';
                    } else {
                        field.textContent = currentValue;
                    }
                });
            }

            // Input'u kaldır ve span'i göster
            input.remove();
            field.style.display = '';
        }

        function handleKeyPress(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.target.blur();
            }
        }

        // Inline edit özelliği - metin, fiyat, barkod ve stok alanları için
        document.querySelectorAll('.editable-field').forEach(field => {
            field.addEventListener('click', function(e) {
                e.preventDefault();
                
                let currentValue = this.textContent.trim();
                const fieldType = this.dataset.type;
                const fieldName = this.dataset.field;
                
                // Stok için özel işlem
                if (fieldName === 'stock') {
                    currentValue = currentValue.replace(/[^\d]/g, ''); // Sadece rakamları al
                }
                
                // Barkod için özel işlem
                if (fieldName === 'barcode') {
                    currentValue = currentValue.replace('Girilmedi', '').trim();
                }
                
                // Mevcut içeriği input ile değiştir
                const input = document.createElement('input');
                
                // Input tipini belirle
                if (fieldType === 'price' || fieldName === 'stock') {
                    input.type = 'number';
                    input.step = fieldType === 'price' ? '0.01' : '1';
                    input.min = '0';
                } else {
                    input.type = 'text';
                }
                
                // Value'yu ayarla
                if (fieldType === 'price') {
                    input.value = currentValue.replace('₺', '').trim();
                } else if (fieldName === 'stock') {
                    input.value = currentValue;
                } else if (fieldName === 'barcode') {
                    input.value = currentValue;
                    input.placeholder = 'Barkod numarası';
                } else {
                    input.value = currentValue;
                }
                
                input.className = 'form-control form-control-sm';
                
                // Input'u yerleştir
                this.style.display = 'none';
                this.parentNode.insertBefore(input, this);
                input.focus();
                input.select(); // Tüm metni seç
                
                // Input blur olduğunda kaydet
                input.addEventListener('blur', handleBlur);
                input.addEventListener('keypress', handleKeyPress);
            });
        });

        // Açıklama alanı için inline edit
        document.querySelectorAll('.editable-description').forEach(field => {
            // Boş açıklama kontrolü ve placeholder ekleme
            if (!field.textContent.trim()) {
                field.innerHTML = '<em class="text-muted">Açıklama ekle...</em>';
            }

            field.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Placeholder varsa gerçek değeri boş string olarak al
                const currentValue = this.querySelector('em') ? '' : this.textContent.trim();
                const productId = this.closest('.product-row').dataset.productId;
                
                // Textarea oluştur
                const textarea = document.createElement('textarea');
                textarea.value = currentValue;
                textarea.className = 'form-control form-control-sm';
                textarea.rows = 3;
                textarea.placeholder = 'Ürün açıklaması girin...';
                
                // Textarea'yı yerleştir
                this.style.display = 'none';
                this.parentNode.insertBefore(textarea, this);
                textarea.focus();
                
                // Textarea blur olduğunda kaydet
                textarea.addEventListener('blur', function() {
                    const newValue = this.value.trim();
                    
                    fetch('api/update_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: productId,
                            field: 'description',
                            value: newValue
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Boş değer kontrolü
                            if (newValue) {
                                field.innerHTML = nl2br(newValue);
                            } else {
                                field.innerHTML = '<em class="text-muted">Açıklama ekle...</em>';
                            }
                            toastr.success('Açıklama güncellendi');
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                        field.innerHTML = currentValue || '<em class="text-muted">Açıklama ekle...</em>';
                    });
                    
                    // Textarea'yı kaldır ve div'i göster
                    this.remove();
                    field.style.display = '';
                });
                
                // Enter tuşuna basıldığında blur tetikle (Shift+Enter için tetikleme)
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.blur();
                    }
                });
            });
        });

        // Yardımcı fonksiyon - new line to br
        function nl2br(str) {
            return str.replace(/\n/g, '<br>');
        }

        // Ürün silme butonu için event listener
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.id;
                
                if (confirm('Bu ürünü silmek istediğinize emin misiniz?')) {
                    fetch('api/delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: productId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.product-row').remove();
                            toastr.success('Ürün başarıyla silindi');
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        toastr.error(error.message || 'Ürün silinirken bir hata oluştu');
                    });
                }
            });
        });

        // Kategori ve ürün sıralama özelliği
        const categoryList = document.querySelector('.category-list');
        if (categoryList) {
            new Sortable(categoryList, {
                handle: '.category-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    const categories = Array.from(document.querySelectorAll('.category-item'))
                        .map((item, index) => ({
                            id: item.dataset.categoryId,
                            sort_order: index
                        }));
                    
                    fetch('api/update_category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            categories: categories,
                            field: 'sort_order',
                            action: 'update_order'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success('Kategori sıralaması güncellendi');
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        toastr.error(error.message || 'Sıralama güncellenirken hata oluştu');
                    });
                }
            });
        }

        // Önce tüm ürün satırlarını bulalım ve başlarına grip ikonunu ekleyelim
        document.querySelectorAll('.product-content').forEach(content => {
            // Grip ikonunu en başa ekle
            content.insertAdjacentHTML('afterbegin', `
                <div class="product-drag-handle">
                    <i class="fas fa-grip-vertical"></i>
                </div>
            `);
        });

        // Sonra her kategori için sürükleme özelliğini ekleyelim
        document.querySelectorAll('.category-products').forEach(category => {
            new Sortable(category, {
                handle: '.product-drag-handle',
                animation: 150,
                draggable: '.product-row',
                onEnd: function(evt) {
                    const categoryProducts = evt.to.children;
                    const updates = [];
                    
                    // Tüm ürünlerin yeni sırasını hesapla
                    Array.from(categoryProducts).forEach((product, index) => {
                        const productId = product.dataset.productId;
                        updates.push(
                            fetch('api/update_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: productId,
                                    field: 'sort_order',
                                    value: index
                                })
                            }).then(r => r.json())
                        );
                    });
                    
                    // Tüm güncellemeleri yap
                    Promise.all(updates)
                        .then(() => {
                            toastr.success('Ürün sıralaması güncellendi');
                        })
                        .catch(error => {
                            console.error('Güncelleme hatası:', error);
                            toastr.error('Sıralama güncellenirken hata oluştu');
                            location.reload();
                        });
                }
            });
        });

        // Kategori adı düzenleme
        document.querySelectorAll('.category-name').forEach(nameElement => {
            // Mevcut içeriği al
            const originalText = nameElement.textContent.trim();
            
            // Düzenlenebilir span oluştur
            const editableSpan = document.createElement('span');
            editableSpan.className = 'editable-category-name';
            editableSpan.textContent = originalText;
            
            // Mevcut içeriği temizle ve span'i ekle
            nameElement.textContent = '';
            nameElement.appendChild(editableSpan);
            
            // Kalem iconu ekle
            const editIcon = document.createElement('i');
            editIcon.className = 'fas fa-pencil-alt text-primary ms-2 edit-icon';
            editIcon.style.display = 'none';
            nameElement.appendChild(editIcon);

            // Hover efekti
            nameElement.addEventListener('mouseenter', function() {
                editIcon.style.display = 'inline-block';
            });
            nameElement.addEventListener('mouseleave', function() {
                editIcon.style.display = 'none';
            });

            // Düzenleme fonksiyonu
            function makeEditable(e) {
                // Eğer tıklanan element badge veya icon ise işlemi durdur
                if (e.target.classList.contains('badge') || 
                    e.target.classList.contains('edit-icon') || 
                    e.target.closest('.category-actions')) {
                    return;
                }

                const currentValue = editableSpan.textContent;
                const categoryId = nameElement.closest('.category-item').dataset.categoryId;
                const currentImage = nameElement.closest('.category-item').querySelector('.category-image').getAttribute('src').split('/').pop(); // Mevcut resmi al
                
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentValue;
                input.className = 'form-control form-control-sm d-inline-block';
                input.style.width = 'auto';
                
                editableSpan.style.display = 'none';
                nameElement.insertBefore(input, editableSpan);
                input.focus();
                
                function saveChanges() {
                    const newValue = input.value.trim();
                    if (newValue !== currentValue) {
                        fetch('api/update_category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                category_id: categoryId,
                                name: newValue,
                                image: currentImage // Mevcut resmi gönder
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                editableSpan.textContent = newValue;
                                toastr.success('Kategori adı güncellendi');
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                            editableSpan.textContent = currentValue;
                        });
                    }
                    input.remove();
                    editableSpan.style.display = '';
                }

                input.addEventListener('blur', saveChanges);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveChanges();
                    }
                });
            }

            // Hem span'e hem de kalem iconuna tıklama eventi ekle
            editableSpan.addEventListener('click', makeEditable);
            editIcon.addEventListener('click', makeEditable);
        });

        // Kategori ve ürün resmi düzenleme
        document.querySelectorAll('.category-image, .product-image').forEach(img => {
            img.addEventListener('click', function(e) {
                e.preventDefault();
                const isCategory = this.classList.contains('category-image');
                const parentElement = this.closest(isCategory ? '.category-item' : '.product-row');
                const itemId = parentElement.dataset[isCategory ? 'categoryId' : 'productId'];
                const itemName = isCategory ? 
                    parentElement.querySelector('.category-name .editable-category-name').textContent.trim() :
                    parentElement.querySelector('.product-name').textContent.trim();
                
                // Media modalı aç - focustrap devre dışı
                const mediaModalElement = document.getElementById('mediaModal');
                mediaModalElement.setAttribute('data-bs-focus', 'false');
                const mediaModal = new bootstrap.Modal(mediaModalElement, {
                    focus: false
                });
                mediaModal.show();
                
                // Media seçildiğinde
                window.selectMedia = function(mediaUrl) {
                    const imageFileName = String(mediaUrl).split('/').pop();
                    const endpoint = isCategory ? 'update_category.php' : 'update_product.php';
                    
                    let requestData;
                    if (isCategory) {
                        requestData = {
                            category_id: itemId,
                            name: itemName,
                            image: imageFileName
                        };
                    } else {
                        requestData = {
                            id: itemId,
                            field: 'image',
                            value: imageFileName
                        };
                    }
                    
                    fetch(`api/${endpoint}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            img.src = '../uploads/' + imageFileName;
                            mediaModal.hide();
                            toastr.success(isCategory ? 'Kategori resmi güncellendi' : 'Ürün resmi güncellendi');
                        } else {
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        toastr.error(error.message || 'Resim güncellenirken bir hata oluştu');
                    });
                };
            });
        });

        // Kategori ürün sayısını göster
        document.querySelectorAll('.category-item').forEach(category => {
            const productCount = category.querySelectorAll('.product-row').length;
            const countBadge = document.createElement('span');
            countBadge.className = 'badge bg-primary ms-2';
            countBadge.textContent = productCount;
            category.querySelector('.category-name').appendChild(countBadge);
        });

        // Kategori kaydetme fonksiyonu
        window.saveCategory = function() {
            const name = document.getElementById('categoryName').value.trim();
            const image = document.getElementById('categoryImage').value;

            if (!name) {
                toastr.error('Kategori adı gereklidir');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('image', image);

            fetch('api/add_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Kategori başarıyla eklendi');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
                    modal.hide();
                    // Modal arkaplanını temizle
                    document.body.classList.remove('modal-open');
                    const modalBackdrop = document.querySelector('.modal-backdrop');
                    if (modalBackdrop) {
                        modalBackdrop.remove();
                    }
                    // Sayfayı yenile
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                toastr.error(error.message || 'Bir hata oluştu');
            });
        };

        // Medya seçici için event listener
        document.addEventListener('click', function(e) {
            if (e.target.closest('.select-media:not(#quickProductImageSelect)')) {
                const button = e.target.closest('.select-media');
                const targetInput = button.dataset.target;
                const parentModal = button.closest('.modal');
                
                // Media modalını aç - focustrap devre dışı
                const mediaModalElement = document.getElementById('mediaModal');
                mediaModalElement.setAttribute('data-bs-focus', 'false');
                const mediaModal = new bootstrap.Modal(mediaModalElement, {
                    focus: false
                });
                mediaModal.show();
                
                // Normal selectMedia fonksiyonu
                window.selectMedia = function(mediaUrl) {
                    const imageFileName = mediaUrl.split('/').pop();
                    
                    // Hedef input'u güncelle
                    document.getElementById(targetInput).value = imageFileName;
                    
                    // Medya modalını kapat
                    mediaModal.hide();
                    
                    // Eğer bir ürün/kategori düzenleme işlemi ise API'ye gönder
                    if (!parentModal) {
                        const itemContainer = button.closest('.product-row, .category-item');
                        if (itemContainer) {
                            const isCategory = itemContainer.classList.contains('category-item');
                            const itemId = itemContainer.dataset[isCategory ? 'categoryId' : 'productId'];
                            const endpoint = isCategory ? 'api/update_category.php' : 'api/update_product.php';
                            
                            let requestData;
                            if (isCategory) {
                                const itemName = itemContainer.querySelector('.category-name .editable-category-name').textContent.trim();
                                requestData = {
                                    category_id: itemId,
                                    name: itemName,
                                    image: imageFileName
                                };
                            } else {
                                requestData = {
                                    id: itemId,
                                    field: 'image',
                                    value: imageFileName
                                };
                            }
                            
                            fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(requestData)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const imgElement = itemContainer.querySelector('img');
                                    if (imgElement) {
                                        imgElement.src = '../uploads/' + imageFileName;
                                    }
                                    toastr.success(isCategory ? 'Kategori resmi güncellendi' : 'Ürün resmi güncellendi');
                                    
                                    // Form verilerini temizle
                                    if (itemContainer.querySelector('form')) {
                                        itemContainer.querySelector('form').reset();
                                    }
                                    
                                    // Sayfayı güvenli bir şekilde yenile
                                    setTimeout(() => {
                                        window.location.href = window.location.href;
                                    }, 1000);
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(error => {
                                toastr.error(error.message || 'Resim güncellenirken bir hata oluştu');
                            });
                        }
                    }
                    
                    // Modal arkaplanını temizle
                    document.body.classList.remove('modal-open');
                    const modalBackdrop = document.querySelector('.modal-backdrop');
                    if (modalBackdrop) {
                        modalBackdrop.remove();
                    }
                };
            }
        });

        // Ürün kaydetme fonksiyonu
        document.querySelector('#addProductModal .modal-footer .btn-primary').addEventListener('click', function() {
            const name = document.getElementById('productName').value.trim();
            const category = document.getElementById('productCategory').value;
            const price = document.getElementById('productPrice').value;
            const image = document.getElementById('productImage').value;
            const description = document.getElementById('productDescription').value.trim();
            const status = document.getElementById('productStatus').checked ? 1 : 0;
            
            // Barkod ve stok (varsa)
            const barcodeInput = document.getElementById('productBarcode');
            const stockInput = document.getElementById('productStock');
            const barcode = barcodeInput ? barcodeInput.value.trim() : '';
            const stock = stockInput ? parseInt(stockInput.value) : 0;

            if (!name || !category || !price) {
                toastr.error('Lütfen gerekli alanları doldurun');
                return;
            }
            
            // Barkod zorunlu kontrolü (POS aktifse)
            if (barcodeInput && barcodeInput.hasAttribute('required') && !barcode) {
                toastr.error('Barkod numarası zorunludur');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('category_id', category);
            formData.append('price', price);
            formData.append('image', image);
            formData.append('description', description);
            formData.append('status', status);
            if (barcode) formData.append('barcode', barcode);
            if (stockInput) formData.append('stock', stock);

            fetch('add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Ürün başarıyla eklendi');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal.hide();
                    // Modal arkaplanını temizle
                    document.body.classList.remove('modal-open');
                    const modalBackdrop = document.querySelector('.modal-backdrop');
                    if (modalBackdrop) {
                        modalBackdrop.remove();
                    }
                    // Sayfayı yenile
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                toastr.error(error.message || 'Bir hata oluştu');
            });
        });

        // Sayfa yüklendiğinde animasyonları başlat
        document.querySelectorAll('.category-item').forEach((category, index) => {
            category.style.opacity = '0';
            category.style.transform = 'translateY(20px)';
            category.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                category.style.opacity = '1';
                category.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Ürünlere fade-in ve hover efekti ekle
        document.querySelectorAll('.product-row').forEach(product => {
            // Temel stil
            product.style.transition = 'all 0.2s ease';
            
            // Hover efekti
            product.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            });
            
            product.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Modal animasyonlarını geliştir
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                this.style.animation = 'modalFadeIn 0.3s ease';
            });
        });

        // Butonlara hover efekti ekle
        document.querySelectorAll('.btn').forEach(button => {
            button.style.transition = 'all 0.2s ease';
            
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Toastr bildirim stillerini özelleştir
        toastr.options = {
            progressBar: true,
            timeOut: 3000,
            positionClass: "toast-top-right",
            preventDuplicates: true,
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',
            showDuration: 300,
            hideDuration: 300
        };

        // CSS stillerini güncelle
        const style = document.createElement('style');
        style.textContent += `
            /* Ürün satırı düzeni */
            .product-content {
                display: flex !important;
                align-items: center !important;
                gap: 10px;
                width: 100%;
            }
            
            /* Sürükleme ikonu stilleri */
            .product-drag-handle {
                cursor: move;
                padding: 5px;
                opacity: 0.5;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                margin-right: 10px;
                position: relative;
                z-index: 1;
                min-width: 20px;
            }
            
            .product-drag-handle:hover {
                opacity: 1;
            }
            
            .product-drag-handle i {
                font-size: 16px;
                color: #6c757d;
                display: inline-block !important;
                visibility: visible !important;
            }
            
            /* Sürükleme sırasındaki görünüm */
            .sortable-ghost {
                opacity: 0.5;
                background: #f8f9fa !important;
            }
            
            .sortable-drag {
                background: #fff !important;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            
            /* Ürün satırı hover efekti */
            .product-row:hover .product-drag-handle {
                opacity: 1;
            }
        `;

        document.head.appendChild(style);

        // Ürün durumu toggle
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const productId = this.dataset.productId;
                const newStatus = this.checked ? 1 : 0;
                
                fetch('api/update_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: productId,
                        field: 'status',
                        value: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const productRow = this.closest('.product-row');
                        if (newStatus === 0) {
                            productRow.style.opacity = '0.5';
                        } else {
                            productRow.style.opacity = '1';
                        }
                        toastr.success('Ürün durumu güncellendi');
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                    this.checked = !this.checked; // Toggle'ı eski haline getir
                });
            });
        });

        // Kategori silme işlemi
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const categoryItem = this.closest('.category-item');
                const categoryId = categoryItem.dataset.categoryId;
                const categoryName = categoryItem.querySelector('.category-name').textContent;
                const productCount = categoryItem.querySelectorAll('.product-row').length;

                Swal.fire({
                    title: 'Kategoriyi Sil',
                    html: productCount > 0 
                        ? `<div class="text-center">
                             <div class="mb-3">
                                 <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                             </div>
                             <p class="mb-2"><strong>${categoryName}</strong> kategorisinde</p>
                             <p class="mb-3"><strong>${productCount} ürün</strong> bulunuyor.</p>
                           </div>`
                        : `<div class="text-center">
                             <div class="mb-3">
                                 <i class="fas fa-question-circle text-info" style="font-size: 3rem;"></i>
                             </div>
                             <p><strong>${categoryName}</strong> kategorisini silmek istediğinize emin misiniz?</p>
                           </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Evet, Sil',
                    cancelButtonText: 'İptal',
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#7f8c8d',
                    reverseButtons: true,
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Silme işlemi
                        fetch('api/delete_category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                category_id: categoryId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Başarılı!',
                                    text: 'Kategori başarıyla silindi',
                                    icon: 'success',
                                    confirmButtonColor: '#2ecc71',
                                    showClass: {
                                        popup: 'animate__animated animate__fadeInDown'
                                    },
                                    hideClass: {
                                        popup: 'animate__animated animate__fadeOutUp'
                                    }
                                });
                                categoryItem.remove();
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Hata!',
                                text: error.message || 'Kategori silinirken bir hata oluştu',
                                icon: 'error',
                                confirmButtonColor: '#e74c3c'
                            });
                        });
                    }
                });
            });
        });

        // Kategori durumu toggle
        document.querySelectorAll('.category-status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const categoryId = this.dataset.categoryId;
                const newStatus = this.checked ? 1 : 0;
                
                Swal.fire({
                    title: newStatus ? 'Kategoriyi Aktifleştir' : 'Kategoriyi Devre Dışı Bırak',
                    text: newStatus ? 'Bu kategori görünür olacak. Onaylıyor musunuz?' : 'Bu kategori gizlenecek. Onaylıyor musunuz?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: newStatus ? '#28a745' : '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: newStatus ? 'Evet, Aktifleştir' : 'Evet, Devre Dışı Bırak',
                    cancelButtonText: 'İptal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('api/update_category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                category_id: categoryId,
                                field: 'status',
                                value: newStatus,
                                name: this.closest('.category-item').querySelector('.category-name').textContent.trim(),
                                image: this.closest('.category-item').querySelector('.category-image').getAttribute('src').split('/').pop()
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const categoryItem = this.closest('.category-item');
                                if (newStatus === 0) {
                                    categoryItem.style.opacity = '0.6';
                                } else {
                                    categoryItem.style.opacity = '1';
                                }
                                toastr.success('Kategori durumu güncellendi');
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                            this.checked = !this.checked;
                        });
                    } else {
                        this.checked = !this.checked;
                    }
                });
            });
        });
    });
    </script>
   
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const userPermissions = {
    canViewProducts: <?php echo $canViewProducts ? 'true' : 'false' ?>,
    canAddProduct: <?php echo $canAddProduct ? 'true' : 'false' ?>,
    canEditProduct: <?php echo $canEditProduct ? 'true' : 'false' ?>,
    canDeleteProduct: <?php echo $canDeleteProduct ? 'true' : 'false' ?>
};

<?php if ($barcodeSalesEnabled): ?>
// Rastgele barkod oluştur (Add Form)
function generateRandomBarcode() {
    const barcode = '89' + Math.floor(Math.random() * 100000000000).toString().padStart(11, '0');
    document.getElementById('productBarcode').value = barcode;
}

// Rastgele barkod oluştur (Edit Form)
function generateRandomBarcodeEdit() {
    const barcode = '89' + Math.floor(Math.random() * 100000000000).toString().padStart(11, '0');
    document.getElementById('edit_product_barcode').value = barcode;
}
<?php endif; ?>

<?php if ($stockTrackingEnabled): ?>
// Stok düzenleme modalını aç
let currentEditingProductId = null;
let currentStock = 0;

function openStockAdjustModal() {
    currentEditingProductId = document.getElementById('edit_product_id').value;
    currentStock = parseInt(document.getElementById('edit_product_stock').value) || 0;
    
    document.getElementById('currentStockDisplay').textContent = currentStock;
    document.getElementById('stockAmount').value = 0;
    document.getElementById('stockNote').value = '';
    document.getElementById('stockAdd').checked = true;
    updateStockPreview();
    
    const modal = new bootstrap.Modal(document.getElementById('stockAdjustModal'));
    modal.show();
}

// Stok işlem tipini değiştir
document.querySelectorAll('input[name="stockOperation"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const label = document.getElementById('stockAmountLabel');
        if (this.value === 'add') {
            label.textContent = 'Eklenecek Miktar';
        } else if (this.value === 'remove') {
            label.textContent = 'Çıkarılacak Miktar';
        } else {
            label.textContent = 'Yeni Stok Miktarı';
        }
        updateStockPreview();
    });
});

// Stok önizlemesini güncelle
document.getElementById('stockAmount').addEventListener('input', updateStockPreview);

function updateStockPreview() {
    const operation = document.querySelector('input[name="stockOperation"]:checked').value;
    const amount = parseInt(document.getElementById('stockAmount').value) || 0;
    let newStock = currentStock;
    
    if (operation === 'add') {
        newStock = currentStock + amount;
    } else if (operation === 'remove') {
        newStock = Math.max(0, currentStock - amount);
    } else {
        newStock = amount;
    }
    
    document.getElementById('resultPreview').textContent = 'Yeni Stok: ' + newStock + ' Adet';
    
    // Renk göstergesi
    const alert = document.querySelector('#stockAdjustModal .alert');
    if (newStock === 0) {
        alert.className = 'alert alert-danger d-flex align-items-center';
    } else if (newStock < 10) {
        alert.className = 'alert alert-warning d-flex align-items-center';
    } else {
        alert.className = 'alert alert-success d-flex align-items-center';
    }
}

// Stok değişikliğini kaydet
function saveStockAdjustment() {
    const operation = document.querySelector('input[name="stockOperation"]:checked').value;
    const amount = parseInt(document.getElementById('stockAmount').value) || 0;
    const note = document.getElementById('stockNote').value;
    
    if (amount === 0 && operation !== 'set') {
        Swal.fire('Uyarı', 'Lütfen geçerli bir miktar girin', 'warning');
        return;
    }
    
    let newStock = currentStock;
    if (operation === 'add') {
        newStock = currentStock + amount;
    } else if (operation === 'remove') {
        newStock = Math.max(0, currentStock - amount);
    } else {
        newStock = amount;
    }
    
    // AJAX ile stok güncelle
    $.ajax({
        url: 'ajax/update_stock.php',
        type: 'POST',
        data: {
            product_id: currentEditingProductId,
            old_stock: currentStock,
            new_stock: newStock,
            operation: operation,
            amount: amount,
            note: note
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                document.getElementById('edit_product_stock').value = newStock;
                currentStock = newStock;
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('stockAdjustModal'));
                modal.hide();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı',
                    text: 'Stok güncellendi',
                    timer: 2000
                });
            } else {
                Swal.fire('Hata', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Hata', 'Stok güncellenemedi', 'error');
        }
    });
}
<?php endif; ?>

// Etiket Yazdırma
$(document).on('click', '.print-label', function() {
    const productId = $(this).data('id');
    const productName = $(this).data('name');
    const barcode = $(this).data('barcode');
    const price = $(this).data('price');
    
    openLabelPrintModal(productId, productName, barcode, price);
});

function openLabelPrintModal(productId, productName, barcode, price) {
    console.log('Modal açılıyor:', {productId, productName, barcode, price});
    
    const modal = new bootstrap.Modal(document.getElementById('labelPrintModal'));
    
    // Form verilerini doldur
    $('#labelProductName').val(productName);
    $('#labelBarcode').val(barcode);
    $('#labelPrice').val(price);
    $('#labelQuantity').val(1);
    $('#currentProductId').val(productId);
    
    // Standart boyutu sıfırla
    $('#standardLabelSize').val('');
    
    // Varsayılan olarak barkod seçili olsun
    $('input[name="labelType"][value="barcode"]').prop('checked', true);
    
    console.log('QRCode kütüphanesi:', typeof QRCode);
    
    // Önizlemeyi güncelle (modal göründükten sonra)
    setTimeout(() => {
        updateLabelPreview();
    }, 100);
    
    modal.show();
}

function updateLabelPreview() {
    const labelType = $('input[name="labelType"]:checked').val();
    const productName = $('#labelProductName').val();
    const barcode = $('#labelBarcode').val();
    const price = parseFloat($('#labelPrice').val()) || 0;
    const showPrice = $('#labelShowPrice').is(':checked');
    const showName = $('#labelShowName').is(':checked');
    const labelWidth = parseInt($('#labelWidth').val()) || 50;
    const labelHeight = parseInt($('#labelHeight').val()) || 30;
    const fontSize = parseInt($('#labelFontSize').val()) || 10;
    const barcodeHeight = parseInt($('#barcodeHeight').val()) || 50;
    
    // Boyut bilgisini güncelle
    $('#currentLabelSize').text(labelWidth + '×' + labelHeight + 'mm');
    
    let previewHTML = '';
    
    if (labelType === 'barcode') {
        previewHTML = `
            <div class="label-preview-item barcode-label" style="width: ${labelWidth}mm; height: ${labelHeight}mm; font-size: ${fontSize}px;">
                ${showName ? `<div class="label-name" style="font-size: ${fontSize}px;">${productName}</div>` : ''}
                <svg id="barcode-preview"></svg>
                ${showPrice ? `<div class="label-price" style="font-size: ${fontSize + 2}px;">${price.toFixed(2)} ₺</div>` : ''}
            </div>
        `;
    } else if (labelType === 'qrcode') {
        const qrSize = Math.min(labelWidth, labelHeight) * 3; // mm to px approximation
        previewHTML = `
            <div class="label-preview-item qr-label" style="
                width: ${labelWidth}mm; 
                height: ${labelHeight}mm; 
                font-size: ${fontSize}px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2mm;
                box-sizing: border-box;
            ">
                ${showName ? `<div class="label-name" style="font-size: ${fontSize}px; margin-bottom: 2mm; text-align: center;">${productName}</div>` : ''}
                <div id="qrcode-preview" style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    max-width: 100%;
                    max-height: 100%;
                "></div>
                ${showPrice ? `<div class="label-price" style="font-size: ${fontSize + 2}px; margin-top: 2mm; text-align: center;">${price.toFixed(2)} ₺</div>` : ''}
            </div>
        `;
    }
    
    $('#labelPreview').html(previewHTML);
    
    // Barkod/QR kod oluştur
    if (labelType === 'barcode' && barcode) {
        try {
            setTimeout(() => {
                JsBarcode("#barcode-preview", barcode, {
                    format: "CODE128",
                    width: 2,
                    height: barcodeHeight,
                    displayValue: true,
                    fontSize: fontSize,
                    margin: 5
                });
            }, 100);
        } catch (e) {
            console.error('Barkod hatası:', e);
            $('#barcode-preview').replaceWith('<p class="text-danger small">Geçersiz barkod</p>');
        }
    } else if (labelType === 'qrcode') {
        setTimeout(() => {
            const qrContainer = document.getElementById("qrcode-preview");
            console.log('QR Container:', qrContainer);
            
            if (qrContainer) {
                // Önce tamamen temizle
                qrContainer.innerHTML = '';
                
                // QR kod verisini hazırla
                const qrData = barcode || 'ID:' + $('#currentProductId').val();
                console.log('QR Data:', qrData);
                
                // QRCode kütüphanesinin yüklendiğini kontrol et
                if (typeof QRCode === 'undefined') {
                    console.error('QRCode kütüphanesi yüklenmedi!');
                    qrContainer.innerHTML = '<p class="text-danger small">QRCode kütüphanesi yüklenmedi</p>';
                    return;
                }
                
                try {
                    // Yeni QR kod oluştur
                    const qr = new QRCode(qrContainer, {
                        text: qrData,
                        width: 128,
                        height: 128,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    console.log('QR kod oluşturuldu:', qr);
                } catch(e) {
                    console.error('QR kod hatası:', e);
                    qrContainer.innerHTML = '<p class="text-danger small">QR kod oluşturulamadı: ' + e.message + '</p>';
                }
            } else {
                console.error('QR Container bulunamadı!');
            }
        }, 150);
    }
}

function printLabels() {
    const quantity = parseInt($('#labelQuantity').val()) || 1;
    const labelType = $('input[name="labelType"]:checked').val();
    const labelWidth = parseInt($('#labelWidth').val()) || 50;
    const labelHeight = parseInt($('#labelHeight').val()) || 30;
    const fontSize = parseInt($('#labelFontSize').val()) || 10;
    
    // Yazdırma penceresini aç
    const printWindow = window.open('', '_blank');
    
    let labelsHTML = '';
    for (let i = 0; i < quantity; i++) {
        labelsHTML += $('#labelPreview').html();
    }
    
    const pageSize = labelWidth + 'mm ' + labelHeight + 'mm';
    const pageWidth = labelWidth + 'mm';
    const pageHeight = labelHeight + 'mm';
    
    const printContent = '<!DOCTYPE html>' +
        '<html>' +
        '<head>' +
        '<meta charset="UTF-8">' +
        '<title>Etiket Yazdır</title>' +
        '<style>' +
        '* { margin: 0; padding: 0; box-sizing: border-box; }' +
        '@page { size: ' + pageSize + '; margin: 0; }' +
        'body {' +
        '  margin: 0;' +
        '  padding: 0;' +
        '  font-family: Arial, sans-serif;' +
        '  background: white;' +
        '}' +
        '.label-preview-item {' +
        '  width: ' + pageWidth + ';' +
        '  height: ' + pageHeight + ';' +
        '  padding: 1mm;' +
        '  text-align: center;' +
        '  display: flex;' +
        '  flex-direction: column;' +
        '  justify-content: center;' +
        '  align-items: center;' +
        '  page-break-after: always;' +
        '  page-break-inside: avoid;' +
        '  overflow: hidden;' +
        '  box-sizing: border-box;' +
        '}' +
        '.label-name {' +
        '  font-size: ' + fontSize + 'px;' +
        '  font-weight: bold;' +
        '  margin: 0 0 1mm 0;' +
        '  line-height: 1.2;' +
        '  max-width: 100%;' +
        '  overflow: hidden;' +
        '  text-overflow: ellipsis;' +
        '  white-space: nowrap;' +
        '}' +
        '.label-price {' +
        '  font-size: ' + (fontSize + 2) + 'px;' +
        '  font-weight: bold;' +
        '  margin: 1mm 0 0 0;' +
        '  line-height: 1;' +
        '}' +
        'svg {' +
        '  max-width: calc(' + pageWidth + ' - 2mm);' +
        '  max-height: calc(' + pageHeight + ' - 10mm);' +
        '  display: block;' +
        '  margin: 0 auto;' +
        '}' +
        '#qrcode-preview, #qrcode-preview img, #qrcode-preview canvas {' +
        '  max-width: calc(' + pageWidth + ' - 4mm);' +
        '  max-height: calc(' + pageHeight + ' - 10mm);' +
        '  margin: 0 auto;' +
        '  display: block;' +
        '}' +
        '@media print {' +
        '  body { background: white; }' +
        '  .label-preview-item {' +
        '    border: none !important;' +
        '    box-shadow: none !important;' +
        '  }' +
        '  @page { margin: 0; }' +
        '}' +
        '</style>' +
        '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>' +
        '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>' +
        '</head>' +
        '<body>' +
        labelsHTML +
        '<script>' +
        'window.onload = function() {' +
        '  setTimeout(function() {' +
        '    window.print();' +
        '    setTimeout(function() { window.close(); }, 500);' +
        '  }, 800);' +
        '};' +
        '<\/script>' +
        '</body>' +
        '</html>';
    
    printWindow.document.write(printContent);
    printWindow.document.close();
}

// Standart boyut uygulama fonksiyonu
function applyStandardSize() {
    const value = $('#standardLabelSize').val();
    if (!value) return;
    
    const [width, height] = value.split(',').map(v => parseInt(v));
    $('#labelWidth').val(width);
    $('#labelHeight').val(height);
    
    // Font boyutunu otomatik ayarla
    if (width <= 30) {
        $('#labelFontSize').val(8);
        $('#barcodeHeight').val(35);
    } else if (width <= 40) {
        $('#labelFontSize').val(9);
        $('#barcodeHeight').val(40);
    } else if (width <= 50) {
        $('#labelFontSize').val(10);
        $('#barcodeHeight').val(50);
    } else {
        $('#labelFontSize').val(12);
        $('#barcodeHeight').val(60);
    }
    
    updateLabelPreview();
}

// Debounce fonksiyonu - gereksiz render'ları önler
let previewTimeout;
function debouncedUpdatePreview() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(() => {
        updateLabelPreview();
    }, 150);
}

// Label modal event listeners - TÜM DEĞİŞİKLİKLERDE ANINDA ÖNİZLEME
$('input[name="labelType"]').on('change', function() {
    // QR kod seçildiğinde boyutları kare yap
    if ($(this).val() === 'qrcode') {
        const width = parseInt($('#labelWidth').val());
        const height = parseInt($('#labelHeight').val());
        const size = Math.max(width, height);
        $('#labelWidth').val(size);
        $('#labelHeight').val(size);
    }
    
    // Standart boyut seçimini sıfırla
    $('#standardLabelSize').val('');
    
    // ANINDA önizle (debounce YOK)
    updateLabelPreview();
});

// Checkboxlarda ANINDA önizleme
$('#labelShowPrice, #labelShowName').on('change', function() {
    updateLabelPreview();
});

// Text inputlarda ANINDA önizleme (her tuş vuruşunda)
$('#labelProductName, #labelBarcode, #labelPrice').on('input', function() {
    updateLabelPreview();
});

// Boyut değişikliklerinde ANINDA önizleme
$('#labelWidth, #labelHeight, #labelFontSize, #barcodeHeight').on('input', function() {
    $('#standardLabelSize').val(''); // Özel boyut kullanılıyor
    updateLabelPreview();
});

</script>

<!-- Etiket Yazdırma Modal -->
<div class="modal fade" id="labelPrintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-print me-2"></i>Etiket Yazdır
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentProductId">
                
                <div class="row">
                    <!-- Sol: Ayarlar -->
                    <div class="col-md-6">
                        <h6 class="mb-3">
                            <i class="fas fa-cog text-primary"></i> Etiket Ayarları
                        </h6>
                        
                        <!-- Etiket Tipi -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Etiket Tipi</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="labelType" id="typeBarkod" value="barcode" checked>
                                    <label class="form-check-label" for="typeBarkod">
                                        <i class="fas fa-barcode"></i> Barkod
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="labelType" id="typeQR" value="qrcode">
                                    <label class="form-check-label" for="typeQR">
                                        <i class="fas fa-qrcode"></i> QR Kod
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ürün Adı -->
                        <div class="mb-3">
                            <label class="form-label">Ürün Adı</label>
                            <input type="text" class="form-control" id="labelProductName">
                        </div>
                        
                        <!-- Barkod -->
                        <div class="mb-3">
                            <label class="form-label">Barkod No</label>
                            <input type="text" class="form-control" id="labelBarcode">
                        </div>
                        
                        <!-- Fiyat -->
                        <div class="mb-3">
                            <label class="form-label">Fiyat</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="labelPrice" step="0.01">
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        
                        <!-- Etiket Boyutları -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-ruler-combined text-info"></i> Etiket Boyutları
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small">Genişlik (mm)</label>
                                    <input type="number" class="form-control form-control-sm" id="labelWidth" min="20" max="100" value="50">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Yükseklik (mm)</label>
                                    <input type="number" class="form-control form-control-sm" id="labelHeight" min="20" max="100" value="30">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Yazı Boyutları -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-text-height text-success"></i> Yazı Ayarları
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small">Font Boyutu (px)</label>
                                    <input type="number" class="form-control form-control-sm" id="labelFontSize" min="6" max="20" value="10">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Barkod Yükseklik (px)</label>
                                    <input type="number" class="form-control form-control-sm" id="barcodeHeight" min="30" max="100" value="50">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Görünüm Seçenekleri -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-eye text-primary"></i> Görünüm
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="labelShowName" checked>
                                <label class="form-check-label" for="labelShowName">
                                    Ürün adını göster
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="labelShowPrice" checked>
                                <label class="form-check-label" for="labelShowPrice">
                                    Fiyatı göster
                                </label>
                            </div>
                        </div>
                        
                        <!-- Adet -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-copy text-warning"></i> Etiket Adedi
                            </label>
                            <input type="number" class="form-control" id="labelQuantity" min="1" max="100" value="1">
                        </div>
                        
                        <!-- Standart Etiket Boyutları -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tags text-danger"></i> Standart Etiket Boyutları
                            </label>
                            <select class="form-select" id="standardLabelSize" onchange="applyStandardSize()">
                                <option value="">Özel Boyut</option>
                                <optgroup label="Barkod Etiketleri">
                                    <option value="50,30">50×30mm - Standart Ürün</option>
                                    <option value="40,25">40×25mm - Küçük Ürün</option>
                                    <option value="60,40">60×40mm - Büyük Ürün</option>
                                    <option value="70,30">70×30mm - Geniş Etiket</option>
                                    <option value="38,25">38×25mm - Mini Etiket</option>
                                </optgroup>
                                <optgroup label="QR Kod Etiketleri">
                                    <option value="50,50">50×50mm - Standart Kare</option>
                                    <option value="40,40">40×40mm - Küçük Kare</option>
                                    <option value="60,60">60×60mm - Büyük Kare</option>
                                </optgroup>
                                <optgroup label="Fiyat Etiketleri">
                                    <option value="30,20">30×20mm - Küçük Fiyat</option>
                                    <option value="40,30">40×30mm - Orta Fiyat</option>
                                    <option value="50,35">50×35mm - Büyük Fiyat</option>
                                </optgroup>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Etiket yazıcınıza uygun boyutu seçin
                            </small>
                        </div>
                    </div>
                    
                    <!-- Sağ: Önizleme -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="fas fa-eye text-success"></i> Canlı Önizleme
                            </h6>
                            <small class="text-muted" id="previewSizeInfo">
                                <i class="fas fa-ruler-combined"></i> <span id="currentLabelSize">50×30mm</span>
                            </small>
                        </div>
                        <div id="labelPreview" style="
                            border: 2px dashed #ccc; 
                            padding: 20px; 
                            text-align: center; 
                            min-height: 250px; 
                            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                            border-radius: 10px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            position: relative;
                            overflow: hidden;
                        ">
                            <div style="color: #6c757d; font-size: 14px;">
                                <i class="fas fa-tag fa-3x mb-3" style="opacity: 0.3;"></i>
                                <p>Etiket önizlemesi burada görünecek</p>
                            </div>
                        </div>
                        <div class="alert alert-info mt-2 mb-0 py-2 px-3" style="font-size: 12px;">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Not:</strong> Değişiklikler anında önizlemede görünür
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>İptal
                </button>
                <button type="button" class="btn btn-primary" onclick="printLabels()">
                    <i class="fas fa-print me-2"></i>Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JsBarcode ve QRCode Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<?php include '../includes/media-modal.php'; ?>
</body>
</html>
