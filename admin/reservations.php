<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();
include 'navbar.php';

$db = new Database();

// Filtreleme
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Rezervasyonları çek
$query = "SELECT r.*, t.table_no 
          FROM reservations r 
          LEFT JOIN tables t ON r.table_id = t.id 
          WHERE 1=1";

if ($status != 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

if ($date) {
    $query .= " AND r.reservation_date = ?";
    $params[] = $date;
}

$query .= " ORDER BY r.reservation_date, r.reservation_time";

$reservations = $db->query($query, $params ?? [])->fetchAll();

// Boş masaları çek
$tables = $db->query("SELECT * FROM tables ORDER BY table_no")->fetchAll();
?>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
/* Ana Container */
.container-fluid {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* Başlık Alanı */
.header-section {
    margin-bottom: 2rem;
}

.header-section h2 {
    color: #2c3e50;
    font-weight: 600;
}

/* Kartlar */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: white;
    margin-bottom: 2rem;
    overflow: hidden;
}

/* Tablo Tasarımı */
.table {
    margin: 0;
}

.table th {
    background: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
    border: none;
}

.table td {
    vertical-align: middle;
    border-color: #f1f1f1;
    padding: 1rem;
}

/* Durum Badge'leri */
.badge {
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 500;
}

/* Butonlar */
.btn {
    border-radius: 10px;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.btn-group .btn {
    border-radius: 8px;
    margin: 0 2px;
}

/* Modal Tasarımı */
.modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    border: none;
    border-radius: 20px 20px 0 0;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #f1f1f1;
    padding: 1.5rem;
}

/* Form Elemanları */
.form-control, .form-select {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
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
    .container-fluid {
        padding: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.4rem 0.8rem;
    }
    
    .table td {
        padding: 0.75rem;
    }
}
</style>
    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Rezervasyonlar</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                <i class="fas fa-plus"></i> Yeni Rezervasyon
            </button>
        </div>

        <!-- Filtreler -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label>Tarih</label>
                        <input type="date" name="date" class="form-control" value="<?= $date ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Durum</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Tümü</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                            <option value="confirmed" <?= $status == 'confirmed' ? 'selected' : '' ?>>Onaylandı</option>
                            <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>İptal</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrele</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rezervasyon Listesi -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Masa</th>
                                <th>Müşteri</th>
                                <th>Kişi</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservations as $res): ?>
                                <tr>
                                    <td>Masa <?= $res['table_no'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($res['customer_name']) ?><br>
                                        <small class="text-muted"><?= $res['customer_phone'] ?></small>
                                    </td>
                                    <td><?= $res['guest_count'] ?></td>
                                    <td><?= date('d.m.Y', strtotime($res['reservation_date'])) ?></td>
                                    <td><?= date('H:i', strtotime($res['reservation_time'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($res['status']) ?>">
                                            <?= getStatusText($res['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewReservation(<?= $res['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="updateStatus(<?= $res['id'] ?>, 'confirmed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="updateStatus(<?= $res['id'] ?>, 'cancelled')">
                                                <i class="fas fa-times"></i>
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
</div>

<!-- Yeni Rezervasyon Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1" aria-labelledby="addReservationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReservationModalLabel">Yeni Rezervasyon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <div class="mb-3">
                        <label>Müşteri Adı</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Telefon</label>
                        <input type="tel" name="customer_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>E-posta</label>
                        <input type="email" name="customer_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Masa</label>
                        <select name="table_id" class="form-select" required>
                            <option value="">Masa Seçin</option>
                            <?php foreach($tables as $table): ?>
                                <option value="<?= $table['id'] ?>">Masa <?= $table['table_no'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Kişi Sayısı</label>
                        <input type="number" name="guest_count" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label>Tarih</label>
                        <input type="date" name="reservation_date" class="form-control" required 
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Saat</label>
                        <input type="time" name="reservation_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Özel İstekler</label>
                        <textarea name="special_requests" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="saveReservation()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return [
        'pending' => 'warning',
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'completed' => 'info'
    ][$status] ?? 'secondary';
}

function getStatusText($status) {
    return [
        'pending' => 'Bekliyor',
        'confirmed' => 'Onaylandı',
        'cancelled' => 'İptal',
        'completed' => 'Tamamlandı'
    ][$status] ?? $status;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal nesnesini oluştur
    const reservationModal = new bootstrap.Modal(document.getElementById('addReservationModal'));

    // Form gönderme işlemi
    function saveReservation() {
        const form = document.getElementById('reservationForm');
        const formData = new FormData(form);

        // Form validasyonu
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        fetch('ajax/save_reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Modalı kapat
                reservationModal.hide();
                
                // Başarılı mesajı göster
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Rezervasyon başarıyla kaydedildi.',
                    confirmButtonText: 'Tamam'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: data.message || 'Bir hata oluştu!',
                    confirmButtonText: 'Tamam'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: 'Bir hata oluştu!',
                confirmButtonText: 'Tamam'
            });
        });
    }

    // saveReservation fonksiyonunu global scope'a ekle
    window.saveReservation = saveReservation;

    // Modal kapanınca formu sıfırla
    document.getElementById('addReservationModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('reservationForm').reset();
    });
});

function updateStatus(id, status) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Rezervasyon durumunu güncellemek istediğinize emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/update_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Rezervasyon durumu güncellendi.',
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: data.message || 'Bir hata oluştu!',
                        confirmButtonText: 'Tamam'
                    });
                }
            });
        }
    });
}

function viewReservation(id) {
    fetch(`ajax/get_reservation.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    title: 'Rezervasyon Detayları',
                    html: `
                        <div class="text-start">
                            <p><strong>Müşteri:</strong> ${data.reservation.customer_name}</p>
                            <p><strong>Telefon:</strong> ${data.reservation.customer_phone}</p>
                            <p><strong>E-posta:</strong> ${data.reservation.customer_email || '-'}</p>
                            <p><strong>Masa:</strong> ${data.reservation.table_no}</p>
                            <p><strong>Kişi Sayısı:</strong> ${data.reservation.guest_count}</p>
                            <p><strong>Tarih:</strong> ${data.reservation.reservation_date}</p>
                            <p><strong>Saat:</strong> ${data.reservation.reservation_time}</p>
                            <p><strong>Özel İstekler:</strong> ${data.reservation.special_requests || '-'}</p>
                        </div>
                    `,
                    confirmButtonText: 'Kapat'
                });
            }
        });
}
</script> 