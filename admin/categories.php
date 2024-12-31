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
   $image = $_POST['image'] ?? '';
   
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
                                <button type="button" class="btn btn-warning btn-sm" onclick="editCategory(<?= $category['id'] ?>)">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                    <i class="fas fa-trash"></i> Sil
                                </button>
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
                <form id="addCategoryForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Kategori Adı</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                             <div class="mb-2">
                                 <img id="categoryImagePreview" src="<?= !empty($category['image']) ? '../uploads/'.$category['image'] : '' ?>" 
                                      style="max-height:100px;<?= empty($category['image']) ? 'display:none' : '' ?>" class="img-thumbnail">
                             </div>
                             <div class="input-group">
                                 <input type="hidden" id="categoryImage" name="image" value="<?= $category['image'] ?? '' ?>">
                                 <input type="text" class="form-control" id="categoryImageDisplay" 
                                      value="<?= $category['image'] ?? '' ?>" readonly>
                                 <button type="button" class="btn btn-primary" onclick="openMediaModal('categoryImage')">
                                     <i class="fas fa-image"></i> Dosya Seç
                                 </button>
                             </div>
                         </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kategori Düzenleme Modal -->
    <div class="modal fade" id="editCategoryModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kategori Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm" method="POST">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Kategori Adı</label>
                            <input type="text" name="name" id="edit_category_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <div class="mb-2">
                                <img id="editCategoryImagePreview" src="" 
                                     style="max-height:100px;display:none" class="img-thumbnail">
                            </div>
                            <div class="input-group">
                                <input type="hidden" id="editCategoryImage" name="image">
                                <input type="text" class="form-control" id="editCategoryImageDisplay" readonly>
                                <button type="button" class="btn btn-primary" onclick="openMediaModal('editCategoryImage')">
                                    <i class="fas fa-image"></i> Dosya Seç
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/media-modal.php'; ?>
    <script>
    function editCategory(categoryId) {
        // Kategori bilgilerini getir
        fetch(`api/get_category.php?id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Form alanlarını doldur
                    document.getElementById('edit_category_id').value = data.category.id;
                    document.getElementById('edit_category_name').value = data.category.name;
                    document.getElementById('editCategoryImage').value = data.category.image || '';
                    document.getElementById('editCategoryImageDisplay').value = data.category.image || '';
                    
                    // Resim önizleme
                    const preview = document.getElementById('editCategoryImagePreview');
                    if (data.category.image) {
                        preview.src = '../uploads/' + data.category.image;
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                    }
                    
                    // Modal'ı aç
                    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
                } else {
                    Swal.fire('Hata!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Hata!', 'Kategori bilgileri alınamadı', 'error');
            });
    }

    // Form submit işlemi
    document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('api/update_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Kategori güncellendi',
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

    // Kategori ekleme form submit
    document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('api/add_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Kategori başarıyla eklendi',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Modal'ı kapat
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addCategoryModal'));
                    modal.hide();
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

    function deleteCategory(categoryId, categoryName) {
        Swal.fire({
            title: 'Emin misiniz?',
            html: `<b>${categoryName}</b> kategorisini silmek istediğinize emin misiniz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('api/delete_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        category_id: categoryId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: 'Kategori başarıyla silindi',
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
    </script>
</body>
</html>