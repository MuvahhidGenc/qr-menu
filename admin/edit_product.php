
<?php
require_once '../includes/config.php';
$db = new Database();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $db->query("SELECT * FROM products WHERE id = ?", [$id])->fetch();

if(!$product) {
   header('Location: products.php');
   exit;
}

if(isset($_POST['update_product'])) {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = (int)$_POST['category_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Resim kontrolü
    $image = $_POST['image'] ?? $product['image']; // Hidden input'tan veya mevcut resimden al
    
    $db->query("UPDATE products SET 
                name = ?, 
                description = ?, 
                price = ?, 
                category_id = ?, 
                image = ?,
                status = ? 
                WHERE id = ?", 
               [$name, $description, $price, $category_id, $image, $status, $id]);
               
    $_SESSION['message'] = 'Ürün başarıyla güncellendi.';
    $_SESSION['message_type'] = 'success';
    header('Location: products.php');
    exit;
}

$categories = $db->query("SELECT * FROM categories")->fetchAll();

include 'navbar.php';
?>

<div class="main-content">
<div class="card">
   <div class="card-header d-flex justify-content-between align-items-center">
       <h5 class="mb-0">Ürün Düzenle</h5>
       <a href="products.php" class="btn btn-secondary">
           <i class="fas fa-arrow-left"></i> Geri
       </a>
   </div>
   <div class="card-body">
       <form method="POST" enctype="multipart/form-data">
           <div class="row">
               <div class="col-md-6">
                   <div class="mb-3">
                       <label>Ürün Adı</label>
                       <input type="text" name="name" 
                              value="<?= htmlspecialchars($product['name']) ?>" 
                              class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Açıklama</label>
                       <textarea name="description" class="form-control" 
                                 rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                   </div>
                   <div class="mb-3">
                       <label>Kategori</label>
                       <select name="category_id" class="form-control" required>
                           <?php foreach($categories as $category): ?>
                               <option value="<?= $category['id'] ?>" 
                                       <?= $category['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                   <?= htmlspecialchars($category['name']) ?>
                               </option>
                           <?php endforeach; ?>
                       </select>
                   </div>
               </div>
               <div class="col-md-6">
                   <div class="mb-3">
                       <label>Fiyat</label>
                       <input type="number" step="0.01" name="price" 
                              value="<?= $product['price'] ?>" 
                              class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Mevcut Resim</label>
                       <?php if($product['image']): ?>
                           <div class="mb-2">
                               <img src="../uploads/<?= $product['image'] ?>" 
                                    class="img-thumbnail" style="max-height:200px">
                           </div>
                       <?php endif; ?>
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
                           <input type="checkbox" name="status" class="form-check-input"
                                  <?= $product['status'] ? 'checked' : '' ?>>
                           <label class="form-check-label">Aktif</label>
                       </div>
                   </div>
               </div>
           </div>
           <button type="submit" name="update_product" class="btn btn-primary">
               <i class="fas fa-save"></i> Güncelle
           </button>
       </form>
   </div>
</div>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/media-modal.php'; ?>
</body>
</html>