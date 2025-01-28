<?php require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('products.view')) {
    header('Location: dashboard.php');
    exit();
}

// Yetkileri tanımla
$canViewProducts = hasPermission('products.view');
$canAddProduct = hasPermission('products.add');
$canEditProduct = hasPermission('products.edit');
$canDeleteProduct = hasPermission('products.delete');


// Sayfa içeriği
$db = new Database();

// Kategorileri sıralı şekilde getir
$categories = $db->query("
    SELECT * FROM categories 
    WHERE status = 1 
    ORDER BY sort_order, name"
)->fetchAll();

$products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name"
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
/* Ana Container */
.products-container {
    padding: 1.5rem;
    background: #f8f9fa;
}

/* Başlık Kartı */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.category-item {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: all 0.3s ease;
}

.category-header {
    cursor: pointer;
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: relative;
}

.category-header:hover {
    background: linear-gradient(to right, #e9ecef, #f8f9fa);
}

.product-row {
    border-bottom: 1px solid #eee;
    transition: all 0.3s ease;
}

.product-row:last-child {
    border-bottom: none;
}

.product-content {
    display: flex;
    align-items: center;
    padding: 1rem;
    gap: 1.5rem;
}

.product-image-container {
    width: 100px;
    height: 100px;
    border-radius: 10px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.product-info {
    flex: 1;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.product-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.product-price {
    font-weight: 700;
    color: #2ecc71;
    cursor: pointer;
}

.product-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}

.editable:hover {
    background: #f8f9fa;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
}

@media (max-width: 768px) {
    .product-content {
        flex-direction: column;
        text-align: center;
    }
    
    .product-actions {
        margin-top: 1rem;
        justify-content: center;
    }
}

.category-drag-handle {
    cursor: grab;
    padding: 10px;
    color: #6c757d;
    margin-right: 10px;
    display: flex;
    align-items: center;
}

.category-drag-handle:active {
    cursor: grabbing;
}

.category-image {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    margin: 0 15px;
    object-fit: cover;
}

.category-name {
    position: relative;
    cursor: pointer;
    flex: 1;
}

.editable-category-name {
    cursor: pointer;
}

.editable-category-name:hover {
    background-color: rgba(0,0,0,0.05);
    border-radius: 3px;
    padding: 2px 4px;
    margin: -2px -4px;
}

.edit-icon {
    font-size: 0.8em;
    cursor: pointer;
    position: absolute;
    top: 50%;
    right: -20px;
    transform: translateY(-50%);
}

.category-list {
    min-height: 10px; /* Sürükleme için minimum yükseklik */
}

.category-item.dragging {
    opacity: 0.5;
}

.category-item.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.category-item.sortable-drag {
    opacity: 0.9;
    background: white;
}

.category-actions {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}
</style>
<div class="container-fluid products-container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Ürünler</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-primary add-product-button" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus"></i> Yeni Ürün
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Yeni Kategori
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="category-list">
                <?php foreach ($categories as $category): ?>
                <div class="category-item" data-category-id="<?= $category['id'] ?>">
                    <div class="category-header">
                        <i class="fas fa-bars category-drag-handle"></i>
                        <img src="<?= $category['image'] ? '../uploads/' . $category['image'] : '../assets/images/default-category.jpg' ?>" 
                             class="category-image" 
                             alt="<?= htmlspecialchars($category['name']) ?>">
                        <h5 class="category-name"><?= htmlspecialchars($category['name']) ?></h5>
                        
                        <!-- Kategori işlem butonları -->
                        <div class="category-actions">
                            <?php if ($canAddProduct): ?>
                            <button class="btn btn-sm btn-outline-success quick-add-product" 
                                    data-category-id="<?= $category['id'] ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#quickAddProductModal">
                                <i class="fas fa-plus"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canEditProduct): ?>
                            <button class="btn btn-sm btn-outline-primary edit-category" data-id="<?= $category['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDeleteProduct): ?>
                            <button class="btn btn-sm btn-outline-danger delete-category" data-id="<?= $category['id'] ?>">
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
                                        <input class="form-check-input status-toggle" type="checkbox" 
                                               <?= $product['status'] ? 'checked' : '' ?>
                                               data-product-id="<?= $product['id'] ?>">
                                    </div>
                                    <?php if ($canDeleteProduct): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-product" 
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
                            <label class="form-label">Fiyat</label>
                            <input type="number" class="form-control" id="productPrice" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ürün Görseli</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="productImage" readonly>
                                <button type="button" class="btn btn-primary select-media" data-target="productImage">
                                    <i class="fas fa-image"></i> Dosya Seç
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" id="productDescription" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="productStatus" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Ekle</button>
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
        // Kategori açma/kapama işlevselliği
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', function(e) {
                // Eğer tıklanan element butonlardan biri ise, açma/kapama işlemini engelle
                if (e.target.closest('.category-actions') || e.target.closest('.category-drag-handle')) {
                    return;
                }
                
                const categoryItem = this.closest('.category-item');
                const categoryProducts = categoryItem.querySelector('.category-products');
                
                if (categoryProducts) {
                    if (categoryProducts.style.display === 'none') {
                        categoryProducts.style.display = 'block';
                    } else {
                        categoryProducts.style.display = 'none';
                    }
                }
            });
        });

        // Media seçici için gerekli kodlar
        let selectedImageElement = null;

        // Hızlı ürün ekleme modalında resim seçme butonu
        document.getElementById('quickProductImageSelect').addEventListener('click', function(e) {
            e.preventDefault();
            selectedImageElement = document.getElementById('quickProductImagePreview');
            
            // Önce quick add modalı kapat
            const quickAddModal = bootstrap.Modal.getInstance(document.getElementById('quickAddProductModal'));
            if (quickAddModal) {
                quickAddModal.hide();
                // Modal tamamen kapandıktan sonra media modalı aç
                document.getElementById('quickAddProductModal').addEventListener('hidden.bs.modal', function handler() {
                    const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
                    mediaModal.show();
                    // Event listener'ı temizle
                    this.removeEventListener('hidden.bs.modal', handler);
                });
            }
        });

        // Media seçildiğinde
        window.selectMedia = function(mediaUrl, mediaId) {
            if (selectedImageElement) {
                const imageFileName = String(mediaUrl).split('/').pop();
                const fullPath = 'uploads/' + imageFileName;
                
                selectedImageElement.src = '../uploads/' + imageFileName;
                selectedImageElement.style.display = 'block';
                
                document.getElementById('quickProductImage').value = imageFileName;
                document.getElementById('quickProductImagePath').value = fullPath;
                
                // Önce media modalı kapat
                const mediaModal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
                if (mediaModal) {
                    mediaModal.hide();
                    // Media modal kapandıktan sonra quick add modalı aç
                    document.getElementById('mediaModal').addEventListener('hidden.bs.modal', function handler() {
                        const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddProductModal'));
                        quickAddModal.show();
                        // Event listener'ı temizle
                        this.removeEventListener('hidden.bs.modal', handler);
                    });
                }
            }
        };

        // Resim silme işlemi
        document.getElementById('quickProductImageRemove').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('quickProductImage').value = '';
            document.getElementById('quickProductImagePreview').style.display = 'none';
            document.getElementById('quickProductImagePreview').src = '';
            document.getElementById('quickProductImagePath').value = '';
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
            field.addEventListener('click', function(e) {
                e.preventDefault();
                
                const currentValue = this.textContent.trim();
                const productId = this.closest('.product-row').dataset.productId;
                
                // Textarea oluştur
                const textarea = document.createElement('textarea');
                textarea.value = currentValue;
                textarea.className = 'form-control form-control-sm';
                textarea.rows = 3;
                
                // Textarea'yı yerleştir
                this.style.display = 'none';
                this.parentNode.insertBefore(textarea, this);
                textarea.focus();
                
                // Textarea blur olduğunda kaydet
                textarea.addEventListener('blur', function() {
                    const newValue = this.value.trim();
                    if (newValue !== currentValue) {
                        // AJAX ile güncelle
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
                                field.innerHTML = newValue ? nl2br(newValue) : '';
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

        // Kategori sıralama özelliği
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
                    
                    fetch('api/update_category.php', {  // Endpoint düzeltildi
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            categories: categories,
                            field: 'sort_order',  // Veritabanındaki alan adı
                            action: 'update_order'  // İşlem türü belirtildi
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
                window.selectMedia = function(mediaUrl, mediaId) {
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
            if (e.target.closest('.select-media')) {
                const button = e.target.closest('.select-media');
                const targetInput = button.dataset.target;
                
                // Medya modalını aç
                const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
                mediaModal.show();
                
                // Medya seçildiğinde
                window.selectMedia = function(mediaUrl) {
                    document.getElementById(targetInput).value = mediaUrl.split('/').pop();
                    mediaModal.hide();
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
