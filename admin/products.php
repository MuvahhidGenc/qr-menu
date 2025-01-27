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

// Kategorileri ve ürünleri tek sorguda getir
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$products = $db->query(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     ORDER BY p.name"
)->fetchAll();

include 'navbar.php';
?>
<!-- Toastr ve diğer gerekli kütüphaneler -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Toastr ayarları -->
<script>
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
</style>
<div class="products-container">
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
                <div class="category-item mb-4">
                    <div class="category-header d-flex justify-content-between align-items-center p-3" 
                         data-category="<?= $category['id'] ?>">
                        <h6 class="mb-0"><?= htmlspecialchars($category['name']) ?></h6>
                        <?php if ($canAddProduct): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle quick-add-product" 
                                data-category-id="<?= $category['id'] ?>"
                                data-category-name="<?= htmlspecialchars($category['name']) ?>">
                            <i class="fas fa-plus"></i>
                        </button>
                        <?php endif; ?>
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

    <!-- Hızlı Ürün Ekleme Modal -->
    <div class="modal fade" id="quickAddProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hızlı Ürün Ekle - <span id="categoryNameSpan"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="quickAddProductForm">
                    <div class="modal-body">
                        <input type="hidden" id="quick_category_id" name="category_id">
                        <div class="mb-3">
                            <label for="quick_name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="quick_price" class="form-label">Fiyat <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="quick_price" name="price" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="quick_status" name="status" checked>
                            <label class="form-check-label" for="quick_status">Aktif</label>
                        </div>
                        <div class="mb-3">
                            <label for="quickProductImagePreview" class="form-label">Ürün Resmi</label>
                            <div class="input-group">
                                <input type="hidden" id="quickProductImage" name="image">
                                <input type="text" class="form-control" id="quickProductImageDisplay" readonly>
                                <button type="button" class="btn btn-primary" onclick="openMediaModal('quickProductImage', 'quickProductImagePreview')">
                                    <i class="fas fa-image"></i> Resim Seç
                                </button>
                            </div>
                            <img id="quickProductImagePreview" src="" style="max-height:100px;display:none" class="img-thumbnail">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Media seçim işlemi için global değişkenler ve fonksiyonlar
    window.selectedImageElement = null;
    window.selectedProductId = null;

    // Media seçim işlemi
    window.selectMedia = function(url) {
        if (window.selectedImageElement && window.selectedProductId) {
            const imageUrl = url.replace('../uploads/', '');
            
            fetch('api/update_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: window.selectedProductId,
                    field: 'image',
                    value: imageUrl
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const imgElement = window.selectedImageElement.querySelector('img');
                    if (imgElement) {
                        imgElement.src = '../uploads/' + imageUrl;
                    }
                    toastr.success('Resim güncellendi');
                    
                    // Modal ve arkaplanı temizle
                    const mediaModal = document.getElementById('mediaModal');
                    const modalInstance = bootstrap.Modal.getInstance(mediaModal);
                    if (modalInstance) {
                        modalInstance.hide();
                        // Modal tamamen kapandıktan sonra arkaplanı temizle
                        mediaModal.addEventListener('hidden.bs.modal', function() {
                            document.body.classList.remove('modal-open');
                            const modalBackdrop = document.querySelector('.modal-backdrop');
                            if (modalBackdrop) {
                                modalBackdrop.remove();
                            }
                        }, { once: true });
                    }
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                toastr.error(error.message || 'Bir hata oluştu');
            })
            .finally(() => {
                window.selectedImageElement = null;
                window.selectedProductId = null;
            });
        }
    };

    // Media modal açma fonksiyonu
    window.openMediaModal = function(inputId, previewId) {
        window.selectedImageInput = document.getElementById(inputId);
        window.selectedImagePreview = document.getElementById(previewId);
        
        const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
        
        // Modal açıldığında event listener'ları yeniden ekle
        document.getElementById('mediaModal').addEventListener('shown.bs.modal', function() {
            initializeMediaModal();
        }, { once: true });
        
        mediaModal.show();
    };

    // Media modal işlemleri
    function initializeMediaModal() {
        const mediaItems = document.querySelectorAll('.media-grid .media-item');
        const selectButton = document.querySelector('#selectMediaButton');
        const mediaModal = document.getElementById('mediaModal');
        
        if (!mediaModal) return;
        
        // Seç butonunu başlangıçta deaktif yap
        if (selectButton) {
            selectButton.disabled = true;
        }

        // Her media item için click event listener ekle
        mediaItems.forEach(item => {
            item.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Tüm seçimleri kaldır
                mediaItems.forEach(i => i.classList.remove('selected'));
                
                // Bu öğeyi seç
                this.classList.add('selected');
                
                // URL'i kaydet
                window.selectedMediaUrl = this.dataset.url;
                
                // Seç butonunu aktifleştir
                if (selectButton) {
                    selectButton.disabled = false;
                }
            };
        });

        // Seç butonuna click event listener ekle
        if (selectButton) {
            selectButton.onclick = function(e) {
                e.preventDefault();
                
                if (window.selectedMediaUrl) {
                    // Input ve preview elementlerini güncelle
                    if (window.selectedImageInput && window.selectedImagePreview) {
                        const imageUrl = window.selectedMediaUrl.replace('../uploads/', '');
                        window.selectedImageInput.value = imageUrl;
                        window.selectedImagePreview.src = '../uploads/' + imageUrl;
                        window.selectedImagePreview.style.display = 'block';
                    }
                    
                    // Ürün resmi güncelleme durumu
                    if (window.selectedImageElement) {
                        updateProductImage(window.selectedMediaUrl);
                    }
                    
                    // Modal'ı kapat
                    const mediaModal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
                    if (mediaModal) {
                        mediaModal.hide();
                    }
                }
            };
        }

        // Modal kapandığında temizlik yap
        mediaModal.addEventListener('hidden.bs.modal', function() {
            window.selectedMediaUrl = null;
            if (selectButton) {
                selectButton.disabled = true;
            }
        });
    }

    // Resim alanı tıklama olayı
    document.querySelectorAll('.editable-image').forEach(imageField => {
        imageField.addEventListener('click', function() {
            if (!userPermissions.canEditProduct) {
                toastr.error('Bu işlem için yetkiniz bulunmuyor');
                return;
            }

            window.selectedImageElement = this;
            window.selectedProductId = this.closest('.product-row').dataset.productId;
            
            const mediaModal = document.getElementById('mediaModal');
            if (mediaModal) {
                const bsModal = new bootstrap.Modal(mediaModal);
                mediaModal.addEventListener('shown.bs.modal', function() {
                    initializeMediaModal();
                }, { once: true });
                bsModal.show();
            }
        });
    });

    // Sayfa yüklendiğinde başlat
    document.addEventListener('DOMContentLoaded', function() {
        const mediaModal = document.getElementById('mediaModal');
        if (mediaModal) {
            // Event listener'ları temizle ve yeniden ekle
            const newModal = mediaModal.cloneNode(true);
            mediaModal.parentNode.replaceChild(newModal, mediaModal);
            
            initializeMediaModal();
        }
    });

    // CSS stilleri güncellendi
    const mediaModalStyle = document.createElement('style');
    mediaModalStyle.textContent = `
        .media-grid .media-item {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .media-grid .media-item:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }

        .media-grid .media-item.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .media-grid .media-item.selected::after {
            content: '✓';
            position: absolute;
            top: 5px;
            right: 5px;
            background: #007bff;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .media-grid .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    `;
    document.head.appendChild(mediaModalStyle);

    // Kategori toggle işlemi
    document.addEventListener('DOMContentLoaded', function() {
        // Kategori başlıklarına tıklama olayı ekle
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', function(e) {
                // Hızlı ürün ekleme butonuna tıklandıysa kategoriyi açma
                if (e.target.closest('.quick-add-product')) {
                    return;
                }
                
                const categoryItem = this.closest('.category-item');
                const categoryProducts = categoryItem.querySelector('.category-products');
                const arrow = this.querySelector('.category-arrow');
                
                // Toggle işlemi
                if (categoryProducts.style.display === 'none' || !categoryProducts.style.display) {
                    // Önce diğer açık kategorileri kapat
                    document.querySelectorAll('.category-products').forEach(products => {
                        if (products !== categoryProducts) {
                            products.style.display = 'none';
                            const otherArrow = products.closest('.category-item').querySelector('.category-arrow');
                            if (otherArrow) {
                                otherArrow.style.transform = 'rotate(0deg)';
                            }
                        }
                    });
                    
                    // Sonra bu kategoriyi aç
                    categoryProducts.style.display = 'block';
                    if (arrow) {
                        arrow.style.transform = 'rotate(180deg)';
                    }
                } else {
                    // Bu kategoriyi kapat
                    categoryProducts.style.display = 'none';
                    if (arrow) {
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
            });
        });
    });

    // CSS stil eklemeleri
    const categoryStyle = document.createElement('style');
    categoryStyle.textContent = `
        .category-header {
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
        }

        .category-arrow {
            transition: transform 0.3s ease;
            font-size: 1.2rem;
        }

        .category-products {
            background: #fff;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            display: none;
        }

        .category-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .product-row {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row:hover {
            background-color: #f8f9fa;
        }
    `;
    document.head.appendChild(categoryStyle);

    // Hızlı ürün ekleme ve güncelleme işlemleri
    document.addEventListener('DOMContentLoaded', function() {
        // Hızlı ürün ekleme butonlarına tıklama olayı
        document.querySelectorAll('.quick-add-product').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const categoryId = this.dataset.categoryId;
                const categoryName = this.closest('.category-header').querySelector('h6').textContent;
                
                // Quick add modal'ı aç
                const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddProductModal'));
                
                // Form elemanlarını sıfırla
                document.getElementById('quick_category_id').value = categoryId;
                document.getElementById('categoryNameSpan').textContent = categoryName;
                document.getElementById('quick_name').value = '';
                document.getElementById('quick_price').value = '';
                document.getElementById('quick_status').checked = true;
                document.getElementById('quickProductImage').value = '';
                document.getElementById('quickProductImagePreview').style.display = 'none';
                
                quickAddModal.show();
            });
        });

        // Hızlı düzenleme işlemleri
        document.querySelectorAll('.edit-product-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.closest('.product-row').dataset.productId;
                
                // Ürün verilerini al
                fetch('ajax/get_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;
                        
                        // Edit modal form elemanlarını doldur
                        document.getElementById('edit_product_id').value = product.id;
                        document.getElementById('edit_product_name').value = product.name;
                        document.getElementById('edit_product_price').value = product.price;
                        document.getElementById('edit_product_status').checked = product.status == 1;
                        
                        // Edit modalı aç
                        const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                        editModal.show();
                    }
                });
            });
        });

        // Anlık düzenleme işlemleri
        document.querySelectorAll('.editable-field, .editable-description, .editable-image').forEach(field => {
            let originalValue = field.textContent;
            let isEditing = false;

            field.addEventListener('click', function(e) {
                if (isEditing) return;
                if (!userPermissions.canEditProduct) {
                    toastr.error('Bu işlem için yetkiniz bulunmuyor');
                    return;
                }

                const fieldType = this.dataset.type;
                const fieldName = this.dataset.field;
                const productId = this.closest('.product-row').dataset.productId;

                // Resim düzenleme için özel işlem
                if (fieldType === 'image') {
                    window.selectedImageElement = this;
                    window.selectedProductId = productId;
                    const mediaModal = document.getElementById('mediaModal');
                    if (mediaModal) {
                        const bsModal = new bootstrap.Modal(mediaModal);
                        mediaModal.addEventListener('shown.bs.modal', function() {
                            initializeMediaModal();
                        }, { once: true });
                        bsModal.show();
                    }
                    return;
                }

                isEditing = true;
                originalValue = this.textContent.trim();
                
                let input;
                // Açıklama alanı için özel işlem
                if (fieldType === 'description') {
                    input = document.createElement('textarea');
                    input.value = originalValue;
                    input.rows = 3;
                    input.style.width = '100%';
                    input.style.minHeight = '100px';
                    input.style.padding = '8px';
                    input.style.margin = '4px 0';
                    input.style.borderRadius = '4px';
                    input.style.border = '1px solid #ced4da';
                    input.style.resize = 'vertical';
                } else if (fieldType === 'price') {
                    input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.01';
                    input.value = originalValue.replace(/[^0-9.]/g, '');
                } else {
                    input = document.createElement('input');
                    input.type = 'text';
                    input.value = originalValue;
                }
                
                input.className = 'form-control form-control-sm inline-edit';
                this.textContent = '';
                this.appendChild(input);
                input.focus();

                const saveChanges = () => {
                    const newValue = input.value.trim();
                    if (newValue === originalValue) {
                        this.textContent = originalValue;
                        isEditing = false;
                        return;
                    }

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
                            if (fieldType === 'price') {
                                this.textContent = parseFloat(newValue).toFixed(2) + ' ₺';
                            } else {
                                this.textContent = newValue;
                            }
                            toastr.success('Güncellendi');
                        } else {
                            this.textContent = originalValue;
                            throw new Error(data.message);
                        }
                    })
                    .catch(error => {
                        toastr.error(error.message || 'Bir hata oluştu');
                        this.textContent = originalValue;
                    })
                    .finally(() => {
                        isEditing = false;
                    });
                };

                // Enter tuşu kontrolü - description için farklı davranış
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && fieldType !== 'description') {
                        e.preventDefault();
                        this.blur();
                    }
                    if (e.key === 'Escape') {
                        this.value = originalValue;
                        this.blur();
                    }
                });

                input.addEventListener('blur', function() {
                    saveChanges();
                });
            });
        });

        // Hızlı ürün ekleme form gönderimi
        document.getElementById('quickAddProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                category_id: document.getElementById('quick_category_id').value,
                name: document.getElementById('quick_name').value,
                price: document.getElementById('quick_price').value,
                status: document.getElementById('quick_status').checked ? 1 : 0,
                image: document.getElementById('quickProductImage').value
            };

            // API yolu düzeltildi
            fetch('api/update_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...formData,
                    action: 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Ürün başarıyla eklendi');
                    const quickAddModal = bootstrap.Modal.getInstance(document.getElementById('quickAddProductModal'));
                    quickAddModal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                toastr.error(error.message);
            });
        });

        // Hızlı düzenleme form gönderimi
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                id: document.getElementById('edit_product_id').value,
                name: document.getElementById('edit_product_name').value,
                price: document.getElementById('edit_product_price').value,
                status: document.getElementById('edit_product_status').checked ? 1 : 0
            };

            fetch('api/update_product.php', {  // URL düzeltildi
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...formData,
                    action: 'update'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Ürün başarıyla güncellendi');
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                    editModal.hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                toastr.error(error.message);
            });
        });

        // Durum toggle işlemi
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const productId = this.closest('.product-row').dataset.productId;
                const status = this.checked ? 1 : 0;

                fetch('api/update_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: productId,
                        field: 'status',
                        value: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success('Durum güncellendi');
                    } else {
                        this.checked = !this.checked;
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error(error.message || 'Bir hata oluştu');
                });
            });
        });

        // Silme işlemi düzeltildi
        document.querySelectorAll('.delete-product').forEach(button => {
            button.addEventListener('click', function() {
                if (!userPermissions.canDeleteProduct) {
                    toastr.error('Bu işlem için yetkiniz bulunmuyor');
                    return;
                }

                const productId = this.closest('.product-row').dataset.productId;
                
                if (confirm('Bu ürünü silmek istediğinize emin misiniz?')) {
                    fetch('api/delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id: productId  // product_id yerine id kullanıyoruz
                        })
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
                        toastr.error(error.message || 'Bir hata oluştu');
                    });
                }
            });
        });
    });

    // CSS stilleri ekle
    const inlineEditStyle = document.createElement('style');
    inlineEditStyle.textContent = `
        .editable-field, .editable-description {
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .editable-field:hover, .editable-description:hover {
            background-color: rgba(0,0,0,0.05);
        }

        .editable-description {
            min-height: 24px;
            white-space: pre-wrap;
        }

        .inline-edit {
            width: 100%;
            margin: 0;
            padding: 4px 8px;
        }
    `;
    document.head.appendChild(inlineEditStyle);
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
