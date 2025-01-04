<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!isAdmin() && !isSuperAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Süper admin değilse sistem rollerini gösterme
$roleQuery = isSuperAdmin() 
    ? "SELECT * FROM roles ORDER BY id DESC" 
    : "SELECT * FROM roles WHERE slug != 'super-admin' AND is_system = 0 ORDER BY id DESC";

$roles = $db->query($roleQuery)->fetchAll();

// Tüm yetkilerin listesi
$allPermissions = [
    'dashboard' => [
        'title' => 'Dashboard',
        'permissions' => [
            'view' => 'Görüntüleme'
        ]
    ],
    'categories' => [
        'title' => 'Kategoriler',
        'permissions' => [
            'view' => 'Görüntüleme',
            'add' => 'Ekleme',
            'edit' => 'Düzenleme',
            'delete' => 'Silme',
            'kitchen_only' => 'Sadece Mutfak'
        ]
    ],
    'products' => [
        'title' => 'Ürünler',
        'permissions' => [
            'view' => 'Görüntüleme',
            'add' => 'Ekleme',
            'edit' => 'Düzenleme',
            'delete' => 'Silme'
        ]
    ],
    'orders' => [
        'title' => 'Siparişler',
        'permissions' => [
            'view' => 'Görüntüleme',
            'add' => 'Ekleme',
            'update' => 'Güncelleme',
            'delete' => 'Silme',
        ]
    ],
    'tables' => [
        'title' => 'Masalar',
        'permissions' => [
            'view' => 'Görüntüleme',
            'manage' => 'Yönetim',
            'payment' => 'Ödeme Alma',
            'sales' => 'Satış Ekranı Görüntüleme',
            'add_order' => 'Sipariş Ekleme',
            'edit_order' => 'Sipariş Güncelleme',
            'delete_order' => 'Sipariş Silme',
            'save_order' => 'Sipariş Kaydetme'
        ]
    ],
    'kitchen' => [
        'title' => 'Mutfak',
        'permissions' => [
            'view' => 'Görüntüleme',
            'manage' => 'Yönetim'
        ]
    ],
    'users' => [
        'title' => 'Kullanıcılar',
        'permissions' => [
            'view' => 'Görüntüleme',
            'add' => 'Ekleme',
            'edit' => 'Düzenleme',
            'delete' => 'Silme'
        ]
    ],
    'roles' => [
        'title' => 'Roller',
        'permissions' => [
            'view' => 'Görüntüleme',
            'add' => 'Ekleme',
            'edit' => 'Düzenleme',
            'delete' => 'Silme'
        ]
    ],
    'settings' => [
        'title' => 'Ayarlar',
        'permissions' => [
            'view' => 'Görüntüleme',
            'edit' => 'Düzenleme'
        ]
    ],
    'reports' => [
        'title' => 'Raporlar',
        'permissions' => [
            'view' => 'Görüntüleme'
        ]
    ],
    'payments' => [
        'title' => 'Ödemeler',
        'permissions' => [
            'view' => 'Görüntüleme',
            'create' => 'Ödeme Alma',
            'cancel' => 'Ödeme İptal'
        ]
    ]
];

// Yetki kontrollerini tanımla
$canViewRoles = hasPermission('roles.view');
$canAddRole = hasPermission('roles.add');
$canEditRole = hasPermission('roles.edit');
$canDeleteRole = hasPermission('roles.delete');

// Yetki kontrolü - Sayfa erişimi
if (!$canViewRoles) {
    header('Location: dashboard.php');
    exit();
}

require_once 'navbar.php';
?>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                <i class="fas fa-user-tag me-2"></i>
                Rol Yönetimi
            </h3>
            <?php if ($canAddRole): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                <i class="fas fa-plus me-2"></i>
                Yeni Rol Ekle
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rol Adı</th>
                            <th>Açıklama</th>
                            <th>Yetkiler</th>
                            <th>Sistem Rolü</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?= $role['id'] ?></td>
                            <td><?= htmlspecialchars($role['name']) ?></td>
                            <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                            <td>
                                <?php 
                                $permissions = json_decode($role['permissions'], true);
                                if ($permissions) {
                                    foreach ($permissions as $key => $value) {
                                        if ($key === 'all' && $value === true) {
                                            echo '<span class="badge bg-success">Tam Yetki</span>';
                                            break;
                                        }
                                        if (is_array($value) && isset($allPermissions[$key])) {
                                            foreach ($value as $subKey => $subValue) {
                                                if ($subValue === true && 
                                                    isset($allPermissions[$key]['permissions'][$subKey])) {
                                                    echo "<span class='badge bg-info me-1 mb-1'>{$allPermissions[$key]['title']}.{$allPermissions[$key]['permissions'][$subKey]}</span>";
                                                }
                                            }
                                        } elseif ($value === true && isset($allPermissions[$key])) {
                                            echo "<span class='badge bg-info me-1 mb-1'>{$allPermissions[$key]['title']}</span>";
                                        }
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($role['is_system']): ?>
                                    <span class="badge bg-warning">Evet</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Hayır</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$role['is_system'] || isSuperAdmin()): ?>
                                    <?php if ($canEditRole): ?>
                                    <button class="btn btn-sm btn-primary edit-role" 
                                            data-id="<?= $role['id'] ?>"
                                            data-name="<?= htmlspecialchars($role['name']) ?>"
                                            data-description="<?= htmlspecialchars($role['description'] ?? '') ?>"
                                            data-permissions='<?= htmlspecialchars($role['permissions']) ?>'>
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($canDeleteRole && !$role['is_system']): ?>
                                    <button class="btn btn-sm btn-danger delete-role" 
                                            data-id="<?= $role['id'] ?>">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                    <?php endif; ?>
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

<!-- Rol Ekleme Modal -->
<div class="modal fade" id="addRoleModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rol Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addRoleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yetkiler</label>
                        <div class="row">
                            <?php foreach ($allPermissions as $key => $module): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="form-check">
                                                <input class="form-check-input module-check" type="checkbox" 
                                                       id="module_<?= $key ?>" 
                                                       data-module="<?= $key ?>">
                                                <label class="form-check-label" for="module_<?= $key ?>">
                                                    <?= $module['title'] ?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($module['permissions'] as $permKey => $permName): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input permission-check" 
                                                           type="checkbox" 
                                                           name="permissions[<?= $key ?>][<?= $permKey ?>]" 
                                                           id="perm_<?= $key ?>_<?= $permKey ?>"
                                                           data-module="<?= $key ?>">
                                                    <label class="form-check-label" 
                                                           for="perm_<?= $key ?>_<?= $permKey ?>">
                                                        <?= $permName ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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

<!-- Rol Düzenleme Modal -->
<div class="modal fade" id="editRoleModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rol Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoleForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yetkiler</label>
                        <div class="row">
                            <?php foreach ($allPermissions as $key => $module): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="form-check">
                                                <input class="form-check-input module-check" type="checkbox" 
                                                       id="edit_module_<?= $key ?>" 
                                                       data-module="<?= $key ?>">
                                                <label class="form-check-label" for="edit_module_<?= $key ?>">
                                                    <?= $module['title'] ?>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($module['permissions'] as $permKey => $permName): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input permission-check" 
                                                           type="checkbox" 
                                                           name="permissions[<?= $key ?>][<?= $permKey ?>]" 
                                                           id="edit_perm_<?= $key ?>_<?= $permKey ?>"
                                                           data-module="<?= $key ?>">
                                                    <label class="form-check-label" 
                                                           for="edit_perm_<?= $key ?>_<?= $permKey ?>">
                                                        <?= $permName ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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

.form-check {
    margin-bottom: 8px;
}

.module-check:checked ~ .permission-checks {
    display: block;
}

.permission-checks {
    display: none;
    margin-left: 20px;
}
</style>

<!-- JavaScript -->
<script>
// Yetki değişkenlerini JavaScript'te tanımla
const userPermissions = {
    canViewRoles: <?php echo $canViewRoles ? 'true' : 'false' ?>,
    canAddRole: <?php echo $canAddRole ? 'true' : 'false' ?>,
    canEditRole: <?php echo $canEditRole ? 'true' : 'false' ?>,
    canDeleteRole: <?php echo $canDeleteRole ? 'true' : 'false' ?>,
    canManagePermissions: <?php echo isSuperAdmin() ? 'true' : 'false' ?>
};

$(document).ready(function() {
    // Eğer tablo zaten DataTable olarak başlatılmışsa yok et
    if ($.fn.DataTable.isDataTable('.table')) {
        $('.table').DataTable().destroy();
    }

    // DataTable'ı başlat
    if ($.fn.DataTable) {
        $('.table').DataTable({
            responsive: true,
            language: {
                "emptyTable":     "Tabloda veri bulunmuyor",
                "info":           "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                "infoEmpty":      "0 kayıttan 0 - 0 arası gösteriliyor",
                "infoFiltered":   "(_MAX_ kayıt içerisinden bulunan)",
                "infoPostFix":    "",
                "thousands":      ".",
                "lengthMenu":     "_MENU_ kayıt göster",
                "loadingRecords": "Yükleniyor...",
                "processing":     "İşleniyor...",
                "search":         "Ara:",
                "zeroRecords":    "Eşleşen kayıt bulunamadı",
                "paginate": {
                    "first":      "İlk",
                    "last":       "Son",
                    "next":       "Sonraki",
                    "previous":   "Önceki"
                },
                "aria": {
                    "sortAscending":  ": artan sütun sıralamasını aktifleştir",
                    "sortDescending": ": azalan sütun sıralamasını aktifleştir"
                }
            },
            order: [[0, 'desc']], 
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    } else {
        console.error('DataTables kütüphanesi yüklenemedi!');
    }

    // Modül checkbox kontrolü
    $('.module-check').change(function() {
        var module = $(this).data('module');
        var checked = $(this).prop('checked');
        $('input[data-module="' + module + '"]').prop('checked', checked);
    });

    // Alt yetki checkbox kontrolü
    $('.permission-check').change(function() {
        var module = $(this).data('module');
        var allChecked = $('input[data-module="' + module + '"].permission-check:checked').length === 
                        $('input[data-module="' + module + '"].permission-check').length;
        $('#module_' + module).prop('checked', allChecked);
    });

    // Rol ekleme
    $('#addRoleForm').submit(function(e) {
        e.preventDefault();
        if (!userPermissions.canAddRole) {
            Swal.fire('Yetkisiz İşlem', 'Rol ekleme yetkiniz bulunmuyor.', 'error');
            return;
        }
        $.ajax({
            url: 'ajax/add_role.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Rol başarıyla eklendi.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error
                    });
                }
            }
        });
    });

    // Düzenleme butonu tıklama olayı
    $(document).on('click', '.edit-role', function(e) {
        e.preventDefault();
        
        console.log('Edit button clicked'); // Debug için

        var id = $(this).data('id');
        var name = $(this).data('name');
        var description = $(this).data('description');
        var permissions = $(this).data('permissions');

        console.log('Role data:', { id, name, description, permissions }); // Debug için

        // Form alanlarını doldur
        $('#edit_id').val(id);
        $('#edit_name').val(name);
        $('#edit_description').val(description);

        // Tüm checkboxları temizle
        $('#editRoleModal .permission-check, #editRoleModal .module-check').prop('checked', false);

        // Yetkileri işaretle
        try {
            if (typeof permissions === 'string') {
                permissions = JSON.parse(permissions);
            }
            
            if (permissions) {
                for (var module in permissions) {
                    if (permissions[module] === true) {
                        $('#edit_module_' + module).prop('checked', true);
                        $('input[data-module="' + module + '"].permission-check').prop('checked', true);
                    } else if (typeof permissions[module] === 'object') {
                        for (var perm in permissions[module]) {
                            if (permissions[module][perm] === true) {
                                $('#edit_perm_' + module + '_' + perm).prop('checked', true);
                            }
                        }
                    }
                }
            }
        } catch (e) {
            console.error('Permission parsing error:', e);
        }

        // Modalı aç
        var editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        editModal.show();
    });

    // Form gönderimi
    $('#editRoleForm').on('submit', function(e) {
        e.preventDefault();
        if (!userPermissions.canEditRole) {
            Swal.fire('Yetkisiz İşlem', 'Rol düzenleme yetkiniz bulunmuyor.', 'error');
            return;
        }
        // Form verilerini topla
        var permissions = {};
        
        // Her modül için yetkileri kontrol et
        $('#editRoleModal .card').each(function() {
            var moduleCard = $(this);
            var moduleCheckbox = moduleCard.find('.module-check');
            var moduleName = moduleCheckbox.data('module');
            
            if (moduleName) {
                permissions[moduleName] = {};
                
                // Modül altındaki tüm izinleri kontrol et
                moduleCard.find('.permission-check').each(function() {
                    var permCheckbox = $(this);
                    var permName = permCheckbox.attr('name').match(/\[([^\]]+)\]$/)[1];
                    permissions[moduleName][permName] = permCheckbox.prop('checked');
                });
            }
        });

        var formData = {
            id: $('#edit_id').val(),
            name: $('#edit_name').val(),
            description: $('#edit_description').val(),
            permissions: permissions
        };

        // Debug için
        console.log('Sending data:', formData);

        // AJAX isteği
        $.ajax({
            url: 'ajax/edit_role.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response); // Debug için
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Rol başarıyla güncellendi.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error || 'Bir hata oluştu!'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Sistem Hatası!',
                    text: 'Bir hata oluştu: ' + error
                });
            }
        });
    });

    // Rol silme
    $('.delete-role').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu rol kalıcı olarak silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax/delete_role.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: 'Rol başarıyla silindi.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata!',
                                text: response.error
                            });
                        }
                    }
                });
            }
        });
    });
});
</script> 