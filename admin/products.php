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
                                </div>
                                <div class="product-actions">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" 
                                               type="checkbox" 
                                               data-product-id="<?= $product['id'] ?>"
                                               <?= $product['status'] ? 'checked' : '' ?>>
                                    </div>
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
            
            // Media modalını aç
            const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
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
            const currentValue = field.textContent.trim();
            const newValue = input.value.trim();
            const fieldType = field.dataset.type;
            const fieldName = field.dataset.field;
            const productId = field.closest('.product-row').dataset.productId;

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
                        field.textContent = fieldType === 'price' ? newValue + ' ₺' : newValue;
                        toastr.success('Güncelleme başarılı');
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'Güncelleme sırasında bir hata oluştu');
                    field.textContent = currentValue;
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

        // Inline edit özelliği - metin ve fiyat alanları için
        document.querySelectorAll('.editable-field').forEach(field => {
            field.addEventListener('click', function(e) {
                e.preventDefault();
                
                const currentValue = this.textContent.trim();
                const fieldType = this.dataset.type;
                const fieldName = this.dataset.field;
                
                // Mevcut içeriği input ile değiştir
                const input = document.createElement('input');
                input.type = fieldType === 'price' ? 'number' : 'text';
                input.step = fieldType === 'price' ? '0.01' : null;
                input.value = fieldType === 'price' ? currentValue.replace('₺', '').trim() : currentValue;
                input.className = 'form-control form-control-sm';
                
                // Input'u yerleştir
                this.style.display = 'none';
                this.parentNode.insertBefore(input, this);
                input.focus();
                
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
                
                // Media modalı aç
                const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
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
                
                // Media modalını aç
                const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
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

            if (!name || !category || !price) {
                toastr.error('Lütfen gerekli alanları doldurun');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('category_id', category);
            formData.append('price', price);
            formData.append('image', image);
            formData.append('description', description);
            formData.append('status', status);

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
</script>
<?php include '../includes/media-modal.php'; ?>
</body>
</html>
