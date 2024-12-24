
<?php
require_once '../includes/config.php';
$db = new Database();

if(!isset($_SESSION['admin'])) {
   header('Location: login.php');
   exit;
}

// Kategori İşlemleri
if(isset($_POST['add_category'])) {
   $name = cleanInput($_POST['name']);
   $image = '';
   
   if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
       $image = secureUpload($_FILES['image']);
   }
   
   $db->query("INSERT INTO categories (name, image) VALUES (?, ?)", [$name, $image]);
   $_SESSION['message'] = 'Kategori başarıyla eklendi.';
   $_SESSION['message_type'] = 'success';
   header('Location: categories.php');
   exit;
}

if(isset($_POST['delete_category'])) {
   $id = (int)$_POST['id'];
   $db->query("DELETE FROM categories WHERE id = ?", [$id]);
   $_SESSION['message'] = 'Kategori silindi.';
   $_SESSION['message_type'] = 'success';
   header('Location: categories.php');
   exit;
}

// Kategorileri Çek
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id, c.name, c.image
")->fetchAll();

include 'navbar.php';
?>
<div class="main-content">
<div class="card">
   <div class="card-header d-flex justify-content-between align-items-center">
       <h5 class="mb-0">Kategoriler</h5>
       <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
           <i class="fas fa-plus"></i> Yeni Kategori
       </button>
   </div>
   <div class="card-body">
       <div class="table-responsive">
           <table class="table table-hover">
               <thead>
                   <tr>
                       <th>Resim</th>
                       <th>Kategori Adı</th>
                       <th>Ürün Sayısı</th>
                       <th>İşlemler</th>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach($categories as $category): ?>
                   <tr>
                       <td>
                           <?php if($category['image']): ?>
                               <img src="../uploads/<?= $category['image'] ?>" 
                                    style="width:50px;height:50px;object-fit:cover;border-radius:5px">
                           <?php else: ?>
                               <div class="bg-light" style="width:50px;height:50px;border-radius:5px"></div>
                           <?php endif; ?>
                       </td>
                       <td class="align-middle"><?= htmlspecialchars($category['name']) ?></td>
                       <td class="align-middle">
                           <span class="badge bg-info"><?= $category['product_count'] ?></span>
                       </td>
                       <td class="align-middle">
                           <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn btn-warning btn-sm">
                               <i class="fas fa-edit"></i> Düzenle
                           </a>
                           <form method="POST" style="display:inline">
                               <input type="hidden" name="id" value="<?= $category['id'] ?>">
                               <button type="submit" name="delete_category" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Kategoriyi silmek istediğinizden emin misiniz?')">
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

<!-- Kategori Ekleme Modal -->
<div class="modal fade" id="addCategoryModal">
   <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title">Yeni Kategori</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
           </div>
           <form method="POST" enctype="multipart/form-data">
               <div class="modal-body">
                   <div class="mb-3">
                       <label>Kategori Adı</label>
                       <input type="text" name="name" class="form-control" required>
                   </div>
                   <div class="mb-3">
                       <label>Kategori Resmi</label>
                       <input type="file" name="image" class="form-control">
                   </div>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                   <button type="submit" name="add_category" class="btn btn-primary">Ekle</button>
               </div>
           </form>
       </div>
   </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>