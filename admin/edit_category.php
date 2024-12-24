
<?php
require_once '../includes/config.php';
$db = new Database();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = $db->query("SELECT * FROM categories WHERE id = ?", [$id])->fetch();

if(!$category) {
    header('Location: categories.php');
    exit;
}

if(isset($_POST['update_category'])) {
    $name = cleanInput($_POST['name']);
    $image = $category['image'];
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = secureUpload($_FILES['image']);
    }
    
    $db->query("UPDATE categories SET name = ?, image = ? WHERE id = ?", 
               [$name, $image, $id]);
               
    $_SESSION['message'] = 'Kategori güncellendi.';
    $_SESSION['message_type'] = 'success';
    header('Location: categories.php');
    exit;
}

include 'navbar.php';
?>

<div class="main-content">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Kategori Düzenle</h5>
        <a href="categories.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Kategori Adı</label>
                        <input type="text" name="name" 
                               value="<?= htmlspecialchars($category['name']) ?>" 
                               class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Mevcut Resim</label>
                        <?php if($category['image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/<?= $category['image'] ?>" 
                                     class="img-thumbnail" style="max-height:200px">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label>Yeni Resim</label>
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted">Yeni resim yüklemezseniz mevcut resim kullanılmaya devam edecek.</small>
                    </div>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save"></i> Güncelle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/media-modal.php'; ?>
</body>
</html>