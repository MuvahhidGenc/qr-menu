<?php
require_once '../includes/config.php';
$db = new Database();

if(!isset($_SESSION['admin'])) {
   header('Location: login.php');
   exit;
}

// Ürün İşlemleri
if(isset($_POST['add_product'])) {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = (int)$_POST['category_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    $image = $_POST['image'] ?? '';
    
    $db->query("INSERT INTO products (name, description, price, category_id, image, status) 
                VALUES (?, ?, ?, ?, ?, ?)", 
               [$name, $description, $price, $category_id, $image, $status]);
 }

if(isset($_POST['delete_product'])) {
   $id = (int)$_POST['id'];
   $db->query("DELETE FROM products WHERE id = ?", [$id]);
   $_SESSION['message'] = 'Ürün silindi.';
   $_SESSION['message_type'] = 'success';
   header('Location: products.php');
   exit;
}

// Ürün Güncelleme İşlemi
if(isset($_POST['update_product'])) {
    $id = (int)$_POST['product_id'];
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = (int)$_POST['category_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    $image = $_POST['image'] ?? '';
    
    $db->query("UPDATE products 
                SET name = ?, 
                    description = ?, 
                    price = ?, 
                    category_id = ?, 
                    image = ?, 
                    status = ? 
                WHERE id = ?", 
               [$name, $description, $price, $category_id, $image, $status, $id]);

    $_SESSION['message'] = 'Ürün güncellendi.';
    $_SESSION['message_type'] = 'success';
    header('Location: products.php');
    exit;
}

// Ürünleri ve Kategorileri Çek
$products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();

$categories = $db->query("SELECT * FROM categories")->fetchAll();

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
           <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
               <i class="fas fa-plus"></i> Yeni Ürün
           </button>
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
                               <?php if($product['image']): ?>
                                   <img src="../uploads/<?= $product['image'] ?>" 
                                        style="width:50px;height:50px;object-fit:cover;border-radius:5px">
                               <?php else: ?>
                                   <div class="bg-light" style="width:50px;height:50px;border-radius:5px"></div>
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
                               <button type="button" class="btn btn-warning btn-sm" onclick="editProduct(<?= $product['id'] ?>)">
                                   <i class="fas fa-edit"></i> Düzenle
                               </button>
                               <form method="POST" style="display:inline">
                                   <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                   <button type="submit" name="delete_product" class="btn btn-danger btn-sm"
                                           onclick="return confirm('Ürünü silmek istediğinizden emin misiniz?')">
                                       <i class="fas fa-trash"></i> Sil
                                   </button>
                               </form>
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
                                        <img id="productImagePreview" src="<?= !empty($product['image']) ? '../uploads/'.$product['image'] : '' ?>" 
                                            style="max-height:100px;<?= empty($product['image']) ? 'display:none' : '' ?>" class="img-thumbnail">
                                    </div>
                                    <div class="input-group">
                                        <input type="hidden" id="productImage" name="image" value="<?= $product['image'] ?? '' ?>">
                                        <input type="text" class="form-control" id="productImageDisplay" 
                                            value="<?= $product['image'] ?? '' ?>" readonly>
                                        <button type="button" class="btn btn-primary" onclick="openMediaModal('productImage', 'productImagePreview')">
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
               <form method="POST">
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
                                       <img id="editProductImagePreview" src="" 
                                            style="max-height:100px;display:none" class="img-thumbnail">
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
   let currentMediaInput = null;
   let currentMediaPreview = null;
   let productModal = null;

   // Medya seçici modalını açma fonksiyonu
   function openMediaModal(inputId, previewId) {
       // Input ve preview ID'lerini sakla
       currentMediaInput = inputId;
       currentMediaPreview = previewId;
       
       // Mevcut ürün modalını sakla ve gizle
       productModal = bootstrap.Modal.getInstance(
           document.getElementById(
               inputId.includes('edit') ? 'editProductModal' : 'addProductModal'
           )
       );
       if (productModal) {
           productModal.hide();
       }
       
       // Medya modalını aç
       const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
       mediaModal.show();
   }

   // Medya seçildiğinde çalışacak fonksiyon
   function selectMedia(mediaUrl) {
       if (currentMediaInput && currentMediaPreview) {
           // Input değerlerini güncelle
           document.getElementById(currentMediaInput).value = mediaUrl;
           
           // Önizleme resmini güncelle
           const previewImg = document.getElementById(currentMediaPreview);
           if (previewImg) {
               previewImg.src = '../uploads/' + mediaUrl;
               previewImg.style.display = 'block';
           }
           
           // Display input'u güncelle
           const displayInput = document.getElementById(currentMediaInput + 'Display');
           if (displayInput) {
               displayInput.value = mediaUrl;
           }
       }

       // Medya modalını kapat
       const mediaModal = bootstrap.Modal.getInstance(document.getElementById('mediaModal'));
       if (mediaModal) {
           mediaModal.hide();
       }

       // Ürün modalını tekrar göster
       setTimeout(() => {
           if (productModal) {
               productModal.show();
           }
       }, 150);
   }

   // Sayfa yüklendiğinde
   document.addEventListener('DOMContentLoaded', function() {
       // Medya modalı kapandığında
       const mediaModal = document.getElementById('mediaModal');
       if (mediaModal) {
           mediaModal.addEventListener('hidden.bs.modal', function() {
               // Eğer bir medya seçilmediyse ve ürün modalı varsa geri göster
               setTimeout(() => {
                   if (productModal && !document.querySelector('.modal.show')) {
                       productModal.show();
                   }
               }, 150);
           });
       }
   });

   // Ürün düzenleme fonksiyonu
   function editProduct(productId) {
       fetch(`api/get_product.php?id=${productId}`)
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   document.getElementById('edit_product_id').value = data.product.id;
                   document.getElementById('edit_product_name').value = data.product.name;
                   document.getElementById('edit_product_description').value = data.product.description;
                   document.getElementById('edit_product_price').value = data.product.price;
                   document.getElementById('edit_product_category').value = data.product.category_id;
                   document.getElementById('edit_product_status').checked = data.product.status == 1;
                   document.getElementById('editProductImage').value = data.product.image || '';
                   document.getElementById('editProductImageDisplay').value = data.product.image || '';
                   
                   const preview = document.getElementById('editProductImagePreview');
                   if (data.product.image) {
                       preview.src = '../uploads/' + data.product.image;
                       preview.style.display = 'block';
                   } else {
                       preview.style.display = 'none';
                   }
                   
                   new bootstrap.Modal(document.getElementById('editProductModal')).show();
               }
           })
           .catch(error => {
               console.error('Error:', error);
               alert('Ürün bilgileri alınamadı');
           });
   }

   // Form submit işlemi
   document.getElementById('editProductForm').addEventListener('submit', function(e) {
       e.preventDefault();
       
       const formData = new FormData(this);
       
       fetch('api/update_product.php', {
           method: 'POST',
           body: formData
       })
       .then(response => response.json())
       .then(data => {
           if (data.success) {
               Swal.fire({
                   icon: 'success',
                   title: 'Başarılı!',
                   text: 'Ürün güncellendi',
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
   </script>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/media-modal.php'; ?>
</body>
</html>