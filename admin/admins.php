<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Süper admin kontrolü
$isSuperAdmin = isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'super-admin';

// Rolleri ve adminleri getir - süper admin hariç
$admins = $db->query("
    SELECT a.*, r.name as role_name, r.slug as role_slug 
    FROM admins a 
    LEFT JOIN roles r ON a.role_id = r.id 
    WHERE r.slug != 'super-admin' OR a.id = ?
    ORDER BY a.id DESC
", [$_SESSION['admin_id']])->fetchAll();

// Normal rolleri getir - süper admin hariç
$roles = $db->query("
    SELECT * FROM roles 
    WHERE slug != 'super-admin' 
    ORDER BY name ASC
")->fetchAll();

// Navbar'ı doğru yoldan include et
require_once __DIR__ . '/navbar.php';

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
                $db->query("INSERT INTO admins (username, password, name, email, role) 
                           VALUES (?, ?, ?, ?, ?)", 
                           [$_POST['username'], $password, $_POST['name'], 
                            $_POST['email'], $_POST['role']]);
                break;

            case 'edit':
                $updates = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'role' => $_POST['role'],
                    'status' => $_POST['status']
                ];
                
                if (!empty($_POST['password'])) {
                    $updates['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $db->update('admins', $updates, 'id = ?', [$_POST['id']]);
                break;

            case 'delete':
                $db->query("DELETE FROM admins WHERE id = ?", [$_POST['id']]);
                break;
        }
        
        header('Location: admins.php?success=1');
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
            <h3 class="card-title">
                <i class="fas fa-users me-2"></i>
                Personel Yönetimi
            </h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-plus me-2"></i>
                Yeni Personel Ekle
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Kullanıcı Adı</th>
                            <th width="15%">Ad Soyad</th>
                            <th width="15%">İletişim</th>
                            <th width="15%">Rol</th>
                            <th width="35%">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= $admin['id'] ?? '' ?></td>
                            <td><?= htmlspecialchars($admin['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars($admin['name'] ?? '') ?></td>
                            <td>
                                <?php if(!empty($admin['email'])): ?>
                                    <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($admin['email']) ?></small><br>
                                <?php endif; ?>
                                <?php if(!empty($admin['phone'])): ?>
                                    <small><i class="fas fa-phone"></i> <?= htmlspecialchars($admin['phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($admin['role_name'] ?? 'Rol Atanmamış') ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary edit-admin" 
                                            data-id="<?= $admin['id'] ?>">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-admin" 
                                            data-id="<?= $admin['id'] ?>"
                                            data-super="<?= ($admin['role_slug'] === 'super-admin') ? '1' : '0' ?>">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                </div>
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
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">Personel Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm">
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
                        <small class="text-muted">Opsiyonel</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Telefon</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                        <small class="text-muted">Opsiyonel</small>
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
                        <label for="edit_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="text-muted">Boş bırakılabilir</small>
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

<!-- Ekleme Modal -->
<div class="modal fade" id="addAdminModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Personel Ekle</h5>
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
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
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

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

<!-- jQuery ve DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/admins.js"></script>

</body>
</html> 