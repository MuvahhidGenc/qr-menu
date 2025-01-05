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
<style>
/* Ana Container */
.products-container {
    padding: 1.5rem;
    background: #f8f9fa;
}

/* Başlık Kartı */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: #fff;
    transition: all 0.3s ease;
}

.card-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
    border: none;
}

/* Tablo Stilleri */
.table {
    margin: 0;
}

.table th {
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid #f1f1f1;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Ürün Resmi */
.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.1);
}

/* Durum Switch */
.form-switch .form-check-input {
    width: 45px;
    height: 24px;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
    background-color: #2ecc71;
    border-color: #2ecc71;
}

/* Butonlar */
.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 0.2rem;
}

.btn-warning {
    background: #f1c40f;
    border: none;
    color: #fff;
}

.btn-danger {
    background: #e74c3c;
    border: none;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Modal Stilleri */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    border-radius: 15px 15px 0 0;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Form Elemanları */
.form-control {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 0.8rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

/* Responsive Düzenlemeler */
@media (max-width: 768px) {
    .products-container {
        padding: 1rem;
    }
    
    .btn-action {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
    }
}
</style>
<div class="products-container">
   <div class="card">
       <div class="card-header d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Ürünler</h5>
           <?php if ($canAddProduct): ?>
           <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
               <i class="fas fa-plus"></i> Yeni Ürün
           </button>
           <?php endif; ?>
       </div>
       <div class="card-body">
           <div class="table-responsive">
               <table class="table table-hover">
                   <thead>
                       <tr>
                           <th>Resim</th>
                           <th>Ürün Adı</th>
                           <th>Kategori</th>
                           <th>Fiyat</th>
                           <th>Durum</th>
                           <th>İşlemler</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php foreach($products as $product): ?>
                       <tr>
                           <td>
                               <?php if ($product['image']): ?>
                                   <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" 
                                        class="product-image" 
                                        alt="<?= htmlspecialchars($product['name']) ?>">
                               <?php else: ?>
                                   <img src="assets/img/no-image.png" class="product-image" alt="No Image">
                               <?php endif; ?>
                           </td>
                           <td class="align-middle">
                               <?= htmlspecialchars($product['name']) ?>
                               <small class="d-block text-muted"><?= substr($product['description'], 0, 50) ?>...</small>
                           </td>
                           <td class="align-middle"><?= htmlspecialchars($product['category_name']) ?></td>
                           <td class="align-middle"><?= number_format($product['price'], 2) ?> TL</td>
                           <td class="align-middle">
                               <div class="form-check form-switch">
                                   <input type="checkbox" class="form-check-input" 
                                          <?= $product['status'] ? 'checked' : '' ?>
                                          onchange="updateStatus(<?= $product['id'] ?>, this.checked)">
                               </div>
                           </td>
                           <td class="align-middle">
                               <?php if ($canEditProduct): ?>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editProduct(<?= $product['id'] ?>)">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                               <?php endif; ?>
                               <?php if ($canDeleteProduct): ?>
                               <button type="button" class="btn btn-sm btn-danger" 
                                       onclick="deleteProduct(<?= $product['id'] ?>)">
                                   <i class="fas fa-trash"></i> Sil
                               </button>
                               <?php endif; ?>
                           </td>
                       </tr>
                       <?php endforeach; ?>
                   </tbody>
               </table>
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

   <script>
   function updateStatus(id, status) {
       fetch('ajax/update_status.php', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json',
           },
           body: JSON.stringify({
               id: id,
               status: status ? 1 : 0
           })
       });
   }

   // Global değişkenler
   let activeModal = null;
   let previousModal = null;

   // DOMContentLoaded içine taşıyalım
   document.addEventListener('DOMContentLoaded', function() {
       // Media modal işlemleri
       const mediaModal = document.getElementById('mediaModal');
       
       if (mediaModal) {
           mediaModal.addEventListener('hidden.bs.modal', function (event) {
               // Eğer başka bir modal açılmadıysa ve aktif modal varsa, onu aç
               setTimeout(() => {
                   if (activeModal && !document.querySelector('.modal.show')) {
                       activeModal.show();
                   }
               }, 300);
           });
       }
   });

   function openMediaModal(inputId, previewId) {
       // Açık olan modalı sakla
       const currentModal = document.querySelector('.modal.show');
       if (currentModal) {
           previousModal = bootstrap.Modal.getInstance(currentModal);
           previousModal.hide();
       }
       
       // Input ve preview elementlerini sakla
       window.selectedImageInput = document.getElementById(inputId);
       window.selectedImagePreview = document.getElementById(previewId);
       
       // Media modalı aç
       const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
       mediaModal.show();
   }

   function selectMedia(imagePath) {
       // Input ve preview güncelleme
       if (window.selectedImageInput) {
           window.selectedImageInput.value = imagePath;
       }
       
       if (window.selectedImagePreview) {
           window.selectedImagePreview.src = '../uploads/' + imagePath;
           window.selectedImagePreview.style.display = 'block';
       }
       
       // Display input güncelleme
       if (window.selectedImageInput) {
           const displayInput = document.getElementById(window.selectedImageInput.id + 'Display');
           if (displayInput) {
               displayInput.value = imagePath;
           }
       }
       
       // Modal geçişleri
       const mediaModal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
       mediaModal.hide();
       
       if (previousModal) {
           previousModal.show();
       }
   }

   // Ürün düzenleme fonksiyonu
   function editProduct(productId) {
       if (!userPermissions.canEditProduct) {
           Swal.fire('Yetkisiz İşlem', 'Ürün düzenleme yetkiniz bulunmuyor.', 'error');
           return;
       }

       // Ürün bilgilerini getir
       fetch(`api/get_product.php?id=${productId}`)
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   // Form alanlarını doldur
                   document.getElementById('edit_product_id').value = data.product.id;
                   document.getElementById('edit_product_name').value = data.product.name;
                   document.getElementById('edit_product_description').value = data.product.description;
                   document.getElementById('edit_product_category').value = data.product.category_id;
                   document.getElementById('edit_product_price').value = data.product.price;
                   document.getElementById('edit_product_status').checked = data.product.status == 1;
                   
                   // Resim önizleme
                   const preview = document.getElementById('editProductImagePreview');
                   const imageInput = document.getElementById('editProductImage');
                   const imageDisplay = document.getElementById('editProductImageDisplay');
                   
                   if (data.product.image) {
                       preview.src = '../uploads/' + data.product.image;
                       preview.style.display = 'block';
                       imageInput.value = data.product.image;
                       imageDisplay.value = data.product.image;
                   } else {
                       preview.style.display = 'none';
                       imageInput.value = '';
                       imageDisplay.value = '';
                   }

                   // Düzenleme modalını göster
                   const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                   editModal.show();
               } else {
                   throw new Error(data.message || 'Ürün bilgileri alınamadı');
               }
           })
           .catch(error => {
               console.error('Error:', error);
               Swal.fire('Hata!', error.message, 'error');
           });
   }

   // Ürün düzenleme form işlemi
   document.addEventListener('DOMContentLoaded', function() {
       const editProductForm = document.getElementById('editProductForm');
       
       if (editProductForm) {
           editProductForm.addEventListener('submit', function(e) {
               e.preventDefault();
               
               if (!userPermissions.canEditProduct) {
                   Swal.fire('Yetkisiz İşlem', 'Ürün düzenleme yetkiniz bulunmuyor.', 'error');
                   return;
               }

               const data = {
                   id: document.getElementById('edit_product_id').value,
                   name: document.getElementById('edit_product_name').value,
                   description: document.getElementById('edit_product_description').value,
                   category_id: document.getElementById('edit_product_category').value,
                   price: document.getElementById('edit_product_price').value,
                   image: document.getElementById('editProductImage').value,
                   status: document.getElementById('edit_product_status').checked ? 1 : 0
               };

               // Form verilerini kontrol et
               if (!data.name || !data.category_id) {
                   Swal.fire('Hata!', 'Lütfen zorunlu alanları doldurun.', 'error');
                   return;
               }

               fetch('api/update_product.php', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify(data)
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       Swal.fire({
                           icon: 'success',
                           title: 'Başarılı!',
                           text: 'Ürün başarıyla güncellendi',
                           showConfirmButton: false,
                           timer: 1500
                       }).then(() => {
                           window.location.reload();
                       });
                   } else {
                       throw new Error(data.message || 'Bir hata oluştu');
                   }
               })
               .catch(error => {
                   console.error('Error:', error);
                   Swal.fire('Hata!', error.message, 'error');
               });
           });
       }
   });

   // Ürün ekleme form işlemi
   document.addEventListener('DOMContentLoaded', function() {
       const addProductForm = document.querySelector('#addProductModal form');
       
       if (addProductForm) {
           addProductForm.addEventListener('submit', function(e) {
               e.preventDefault();
               
               if (!userPermissions.canAddProduct) {
                   Swal.fire('Yetkisiz İşlem', 'Ürün ekleme yetkiniz bulunmuyor.', 'error');
                   return;
               }

               const formData = new FormData(this);
               
               fetch('add_product.php', {
                   method: 'POST',
                   body: formData
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       Swal.fire({
                           icon: 'success',
                           title: 'Başarılı!',
                           text: data.message,
                           showConfirmButton: false,
                           timer: 1500
                       }).then(() => {
                           // Modal'ı kapat
                           bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
                           // Sayfayı yenile
                           window.location.reload();
                       });
                   } else {
                       throw new Error(data.message || 'Bir hata oluştu');
                   }
               })
               .catch(error => {
                   console.error('Error:', error);
                   Swal.fire('Hata!', error.message, 'error');
               });
           });
       }
   });

   // Silme fonksiyonu
   function deleteProduct(productId) {
       if (!userPermissions.canDeleteProduct) {
           Swal.fire('Yetkisiz İşlem', 'Ürün silme yetkiniz bulunmuyor.', 'error');
           return;
       }

       Swal.fire({
           title: 'Emin misiniz?',
           text: "Bu ürün kalıcı olarak silinecek!",
           icon: 'warning',
           showCancelButton: true,
           confirmButtonColor: '#d33',
           cancelButtonColor: '#3085d6',
           confirmButtonText: 'Evet, sil!',
           cancelButtonText: 'İptal'
       }).then((result) => {
           if (result.isConfirmed) {
               fetch('api/delete_product.php', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                   },
                   body: JSON.stringify({ product_id: productId })
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       Swal.fire({
                           icon: 'success',
                           title: 'Başarılı!',
                           text: 'Ürün başarıyla silindi',
                           showConfirmButton: false,
                           timer: 1500
                       }).then(() => {
                           window.location.reload();
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
       });
   }

   // Resim seçimini temizle
   function clearMediaSelection() {
       if (window.selectedImageInput && window.selectedImagePreview) {
           window.selectedImageInput.value = '';
           window.selectedImagePreview.src = '';
           window.selectedImagePreview.style.display = 'none';
           
           const displayInput = document.getElementById(window.selectedImageInput.id + 'Display');
           if (displayInput) {
               displayInput.value = '';
           }
       }
       
       // Media modalı kapat
       bootstrap.Modal.getInstance(document.getElementById('mediaModal')).hide();
   }
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
