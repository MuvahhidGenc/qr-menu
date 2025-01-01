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

<style>
/* Kategoriler Container */
.categories-container {
    padding: 1.5rem;
    background: #f8f9fa;
}

/* Başlık Kartı */
.header-card {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.header-card h5 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.btn-add-category {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.btn-add-category:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

/* Kategori Tablosu */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: white;
}

.table {
    margin: 0;
}

.table th {
    border-bottom: 2px solid #f1f1f1;
    color: #2c3e50;
    font-weight: 600;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Kategori Resmi */
.category-image {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.category-image:hover {
    transform: scale(1.1);
}

/* Ürün Sayısı Badge */
.product-count {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Aksiyon Butonları */
.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 0.2rem;
}

.btn-edit {
    background: #f1f9f1;
    color: #27ae60;
}

.btn-delete {
    background: #fee7e7;
    color: #e74c3c;
}

.btn-action:hover {
    transform: translateY(-2px);
}

/* Modal Tasarımı */
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

/* Resim Önizleme */
.img-thumbnail {
    border-radius: 10px;
    border: 2px solid #f1f1f1;
    transition: all 0.3s ease;
}

.img-thumbnail:hover {
    transform: scale(1.05);
}

/* Dosya Seçme Butonu */
.btn-file-select {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-file-select:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
}

/* Responsive Düzenlemeler */
@media (max-width: 768px) {
    .categories-container {
        padding: 1rem;
    }
    
    .header-card {
        padding: 1.5rem;
    }
    
    .btn-action {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    .category-image {
        width: 50px;
        height: 50px;
    }
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

/* Tablo Satır Hover Efekti */
.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}
</style>
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
                                 <button type="button" class="btn btn-primary" onclick="openMediaSelector('categoryImage')">
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
                                <button type="button" class="btn btn-primary" onclick="openMediaSelector('editCategoryImage')">
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

    // Global değişken
    let currentMediaInput = null;

    // Medya seçici modalını açma fonksiyonu
    function openMediaSelector(inputId) {
        currentMediaInput = inputId;
        
        // Kategori modalını gizle
        const categoryModal = document.getElementById('addCategoryModal');
        if (categoryModal) {
            categoryModal.style.display = 'none';
        }
        
        // Medya modalını aç
        const mediaModal = document.getElementById('mediaModal');
        if (mediaModal) {
            new bootstrap.Modal(mediaModal).show();
        }
    }

    // Medya seçildiğinde çalışacak fonksiyon
    function selectMedia(mediaUrl) {
        if (currentMediaInput) {
            // Input değerlerini güncelle
            document.getElementById(currentMediaInput).value = mediaUrl;
            
            // Hangi modalda olduğumuzu kontrol et
            const isEditModal = currentMediaInput === 'editCategoryImage';
            
            // Display input'u güncelle
            const displayInput = document.getElementById(isEditModal ? 'editCategoryImageDisplay' : 'categoryImageDisplay');
            if (displayInput) {
                displayInput.value = mediaUrl;
            }
            
            // Önizleme resmini güncelle
            const previewImg = document.getElementById(isEditModal ? 'editCategoryImagePreview' : 'categoryImagePreview');
            if (previewImg) {
                previewImg.src = '../uploads/' + mediaUrl;
                previewImg.style.display = 'block';
            }
        }

        // Medya modalını kapat
        const mediaModal = document.getElementById('mediaModal');
        const bsMediaModal = bootstrap.Modal.getInstance(mediaModal);
        if (bsMediaModal) {
            bsMediaModal.hide();
        }

        // Doğru kategori modalını göster
        const isEditModal = currentMediaInput === 'editCategoryImage';
        const modalId = isEditModal ? 'editCategoryModal' : 'addCategoryModal';
        const categoryModal = document.getElementById(modalId);
        if (categoryModal) {
            categoryModal.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }
    </script>
</body>
</html>