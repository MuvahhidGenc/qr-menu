
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

<div class="main-content">
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
                           <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">
                               <i class="fas fa-edit"></i> Düzenle
                           </a>
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
                                    <button type="button" class="btn btn-primary" onclick="openMediaModal('productImage')">
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
</script>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/media-modal.php'; ?>
</body>
</html>