<?php
ob_start(); // Çıktı tamponlamasını başlat
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('users.view')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// İşlem yetki kontrolleri
$canAdd = hasPermission('users.add');
$canEdit = hasPermission('users.edit');
$canDelete = hasPermission('users.delete');

// Ayarları getir
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'currency'")->fetch();
$currency = $settingsQuery['setting_value'] ?? '₺';

// Süper admin değilse, süper adminleri gösterme
$adminQuery = isSuperAdmin() 
    ? "SELECT a.*, r.name as role_name 
       FROM admins a 
       LEFT JOIN roles r ON a.role_id = r.id 
       ORDER BY a.id DESC" 
    : "SELECT a.*, r.name as role_name 
       FROM admins a 
       LEFT JOIN roles r ON a.role_id = r.id 
       WHERE r.slug != 'super-admin' 
       ORDER BY a.id DESC";

$admins = $db->query($adminQuery)->fetchAll();

// Normal rolleri getir - süper admin hariç
$roles = $db->query("
    SELECT * FROM roles 
    WHERE slug != 'super-admin' 
    ORDER BY name ASC
")->fetchAll();

// Navbar'ı doğru yoldan include et
include 'navbar.php';
ob_end_flush(); // Çıktı tamponlamasını bitir

// Yetki kontrolü
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

// Admin ekleme/düzenleme/silme işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $salary = !empty($_POST['salary']) ? floatval($_POST['salary']) : null;
                $bonus = !empty($_POST['bonus_percentage']) ? floatval($_POST['bonus_percentage']) : null;
                
                try {
                    $sql = "INSERT INTO admins (username, password, name, email, role_id, salary, bonus_percentage) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $params = [
                        $_POST['username'],
                        $password,
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['role_id'],
                        $salary,
                        $bonus
                    ];
                    
                    $db->query($sql, $params);
                    echo json_encode(['success' => true]);
                    exit;
                } catch (Exception $e) {
                    error_log('Hata: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                break;

            case 'edit':
                try {
                    $id = intval($_POST['id']);
                    $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? floatval($_POST['salary']) : null;
                    $bonus = isset($_POST['bonus_percentage']) && $_POST['bonus_percentage'] !== '' ? floatval($_POST['bonus_percentage']) : null;
                    
                    $updates = [
                        "username = ?",
                        "name = ?",
                        "email = ?",
                        "role_id = ?",
                        "salary = ?",
                        "bonus_percentage = ?"
                    ];
                    
                    $params = [
                        $_POST['username'],
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['role_id'],
                        $salary,
                        $bonus
                    ];

                    if (!empty($_POST['password'])) {
                        $updates[] = "password = ?";
                        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    }

                    $params[] = $id;

                    $sql = "UPDATE admins SET " . implode(", ", $updates) . " WHERE id = ?";
                    $db->query($sql, $params);
                    
                    echo json_encode(['success' => true]);
                    exit;
                } catch (Exception $e) {
                    error_log('Hata: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
                break;

            case 'delete':
                $db->query("DELETE FROM admins WHERE id = ?", [$_POST['id']]);
                break;
        }
        
        header('Location: admins.php');
        exit;
    }
}
?>

<!-- Custom CSS -->
<style>
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,.08);
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #f5f5f5;
    padding: 20px;
    border-radius: 15px 15px 0 0 !important;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
    line-height: 1.5;
}

.table {
    margin: 0;
}

.table th {
    border-bottom: 2px solid #f5f5f5;
    color: #2c3e50;
    font-weight: 600;
    padding: 15px;
}

.table td {
    padding: 15px;
    vertical-align: middle;
    color: #555;
    border-bottom: 1px solid #f5f5f5;
}

.badge {
    padding: 8px 12px;
    font-weight: 500;
    border-radius: 8px;
}

.btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #3498db;
    border-color: #3498db;
}

.btn-primary:hover {
    background: #2980b9;
    border-color: #2980b9;
}

.btn-danger {
    background: #e74c3c;
    border-color: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
    border-color: #c0392b;
}

.modal-content {
    border: none;
    border-radius: 15px;
}

.modal-header {
    border-bottom: 1px solid #f5f5f5;
    padding: 20px;
    background: #fff;
    border-radius: 15px 15px 0 0;
}

.modal-title {
    color: #2c3e50;
    font-weight: 600;
}

.modal-body {
    padding: 20px;
}

.form-label {
    color: #2c3e50;
    font-weight: 500;
    margin-bottom: 8px;
}

.form-control, .form-select {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px 15px;
    height: auto;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52,152,219,.25);
}

.text-danger {
    color: #e74c3c !important;
}

.text-muted {
    color: #95a5a6 !important;
}

.table-responsive {
    border-radius: 0 0 15px 15px;
}

.btn-group {
    gap: 8px;
}

/* DataTables özelleştirme */
.dataTables_wrapper .dataTables_length select {
    border-radius: 8px;
    padding: 5px 10px;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: 8px;
    padding: 5px 10px;
    border: 1px solid #e0e0e0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px;
    padding: 5px 12px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #3498db;
    border-color: #3498db;
    color: #fff !important;
}
</style>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Yöneticiler</h5>
            <?php if ($canAdd): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-plus"></i> Yeni Yönetici
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Kullanıcı Adı</th>
                            <th width="15%">Ad Soyad</th>
                            <th width="15%">E-posta</th>
                            <th width="15%">Rol</th>
                            <th width="15%">Maaş</th>
                            <th width="15%">Prim Yüzdesi</th>
                            <th width="35%">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= $admin['id'] ?></td>
                            <td><?= htmlspecialchars($admin['username']) ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['role_name']) ?></td>
                            <td><?= $admin['salary'] !== null ? number_format($admin['salary'], 2) . ' ' . $currency : 'N/A' ?></td>
                            <td><?= $admin['bonus_percentage'] !== null ? '%' . number_format($admin['bonus_percentage'], 2) : 'N/A' ?></td>
                            <td class="align-middle">
                                <?php if ($canEdit): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-primary edit-admin" 
                                        data-id="<?= $admin['id'] ?>"
                                        data-username="<?= htmlspecialchars($admin['username']) ?>"
                                        data-name="<?= htmlspecialchars($admin['name']) ?>"
                                        data-email="<?= htmlspecialchars($admin['email']) ?>"
                                        data-role="<?= $admin['role_id'] ?>"
                                        data-salary="<?= $admin['salary'] ?>"
                                        data-bonus="<?= $admin['bonus_percentage'] ?>">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($canDelete && $admin['id'] != $_SESSION['admin_id']): ?>
                                <button type="button" class="btn btn-sm btn-danger delete-admin" data-id="<?= $admin['id'] ?>">
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
</div>

<!-- Düzenleme Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yönetici Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAdminForm" onsubmit="return false;">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <?php foreach($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_salary" class="form-label">Maaş</label>
                        <input type="text" class="form-control" id="edit_salary" name="salary">
                    </div>
                    <div class="mb-3">
                        <label for="edit_bonus_percentage" class="form-label">Prim Yüzdesi</label>
                        <input type="text" class="form-control" id="edit_bonus_percentage" name="bonus_percentage">
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="text-muted">Değiştirmek istemiyorsanız boş bırakın</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveEditButton">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ekleme Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Yönetici Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAdminForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">Rol Seçin</option>
                            <?php foreach($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="salary" class="form-label">Maaş</label>
                        <input type="text" class="form-control" id="salary" name="salary">
                    </div>
                    <div class="mb-3">
                        <label for="bonus_percentage" class="form-label">Prim Yüzdesi</label>
                        <input type="text" class="form-control" id="bonus_percentage" name="bonus_percentage">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
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

<!-- Footer -->
<?php include 'footer.php'; ?>

<!-- DataTables -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Responsive -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables-responsive/2.2.9/responsive.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/admins.js"></script>

<!-- Input Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Personel ekleme formu submit işlemi -->
<script>
$(document).ready(function() {
    // Form submit işlemlerini tek bir yerde tanımla
    const handleFormSubmit = (formId, url) => {
        $(`#${formId}`).on('submit', function(e) {
            e.preventDefault();
            
            // Form verilerini al
            const formData = $(this).serialize();
            
            // Submit butonunu devre dışı bırak
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                type: 'POST',
                url: url,
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Modalı kapat
                        $(`#${formId}`).closest('.modal').modal('hide');
                        
                        // Başarı mesajı göster
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            text: formId === 'addAdminForm' ? 
                                  'Personel başarıyla eklendi.' : 
                                  'Personel bilgileri güncellendi.',
                            confirmButtonText: 'Tamam'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                        
                        // Ekleme formuysa temizle
                        if (formId === 'addAdminForm') {
                            $(`#${formId}`)[0].reset();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata!',
                            text: response.error || 'Bir hata oluştu!'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Hatası:', error);
                    console.error('Detay:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Sistem Hatası!',
                        text: 'İşlem sırasında bir hata oluştu.'
                    });
                },
                complete: function() {
                    // Submit butonunu tekrar aktif et
                    submitBtn.prop('disabled', false);
                }
            });
        });
    };

    // Form submit işlemlerini başlat
    handleFormSubmit('addAdminForm', 'ajax/add_admin.php');
    handleFormSubmit('editAdminForm', 'ajax/update_admin.php');
});
</script>

</body>
</html> 