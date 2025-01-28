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

.category-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
    font-weight: 600;
    margin: 0;
    flex-grow: 1;
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
</style>
<div class="container-fluid products-container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Ürünler</h5>
            <?php if ($canAddProduct): ?>
            <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Yeni Ürün
            </button>
            <?php endif; ?>
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label>Ürün Adı</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Açıklama</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control" required>
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
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                     <div class="mb-2">
                                         <img id="addProductImagePreview" src="" style="max-height:100px;display:none" class="img-thumbnail">
                                     </div>
                                     <div class="input-group">
                                         <input type="hidden" id="addProductImage" name="image">
                                         <input type="text" class="form-control" id="addProductImageDisplay" readonly>
                                         <button type="button" class="btn btn-primary" onclick="openMediaModal('addProductImage', 'addProductImagePreview')">
                                             <i class="fas fa-image"></i> Dosya Seç
                                         </button>
                                     </div>
                                 </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="status" class="form-check-input" checked>
                                        <label class="form-check-label">Aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Ekle</button>
                    </div>
                </form>
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
