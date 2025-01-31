<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Temel yetki kontrolleri
$canViewReservations = hasPermission('reservations.view');
$canAddReservation = hasPermission('reservations.add');
$canEditReservation = hasPermission('reservations.edit');
$canDeleteReservation = hasPermission('reservations.delete');
$canApproveReservation = hasPermission('reservations.approve');
$canRejectReservation = hasPermission('reservations.reject');
$canManageSettings = hasPermission('reservations.settings');

// Sayfa erişim kontrolü
if (!$canViewReservations) {
    header('Location: dashboard.php');
    exit();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
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
    .nav-link {
    display: -webkit-box !important;
}
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

/* Modal stillerini özelleştir */
.modal-lg {
    max-width: 800px;
}

.modal-body {
    padding: 20px;
}

.form-label {
    font-weight: 500;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 15px;
}

/* Form elemanları için stil */
.form-control, .form-select {
    padding: 0.5rem;
    border-radius: 0.375rem;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.list-group-item {
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

#productList {
    max-height: 500px;
    overflow-y: auto;
}

.product-img {
    height: 200px;
    object-fit: cover;
    object-position: center;
}

.card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,.125);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

#productList {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 10px;
}

/* Scrollbar stilleri */
#productList::-webkit-scrollbar {
    width: 8px;
}

#productList::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#productList::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

#productList::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Kategori listesi stilleri */
.list-group-item {
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    border-left: 3px solid #0d6efd;
}

.list-group-item.active {
    border-left: 3px solid #0d6efd;
}

/* Toast mesaj stili */
.swal2-toast {
    background: #fff;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.card-img-wrapper {
    height: 200px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

/* Modal z-index değerlerini ayarla */
.modal {
    z-index: 1050;
}

#productModal {
    z-index: 1060;
}

.modal-backdrop:nth-child(2) {
    z-index: 1055;
}

/* Modal stillerini güncelle */
.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
    z-index: 1040;
}

.modal {
    z-index: 1045;
}

#productModal {
    z-index: 1046;
}

.modal-dialog {
    margin: 1.75rem auto;
}

.modal.show {
    display: block;
}

.modal-backdrop + .modal-backdrop {
    opacity: 0.1;
}

/* Ürün kartı hover efekti */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Rezervasyon Detay Modal Stilleri */
.reservation-modal .swal2-html-container {
    margin: 0;
    padding: 0;
}

.reservation-details .card {
    background: #fff;
    border-radius: 15px;
}

.reservation-details .info-group label {
    color: #6c757d;
    font-weight: 500;
}

.reservation-details .info-group h6 {
    color: #2c3e50;
    font-weight: 600;
}

.reservation-details .badge {
    font-size: 0.85rem;
    padding: 0.5em 1em;
    border-radius: 8px;
}

.pre-order-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

.pre-order-section h6 {
    color: #2c3e50;
    font-weight: 600;
}

.pre-order-section .table {
    margin-bottom: 0;
}

.pre-order-section .table th {
    font-weight: 600;
    color: #495057;
    border-top: none;
}

.pre-order-section .table td {
    vertical-align: middle;
}

.pre-order-section .table tfoot {
    background: #fff;
}
</style>
    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Rezervasyonlar</h2>
            <?php if ($canAddReservation): ?>
            <button type="button" class="btn btn-primary btn-add-reservation">
                <i class="fas fa-plus"></i> Yeni Rezervasyon
            </button>
            <?php endif; ?>
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
                                            <?php if ($canViewReservations): ?>
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewReservation(<?= $res['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($canApproveReservation): ?>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="updateStatus(<?= $res['id'] ?>, 'confirmed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($canRejectReservation): ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="updateStatus(<?= $res['id'] ?>, 'cancelled')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
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

<!-- Rezervasyon Modal -->
<div class="modal fade" id="addReservationModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rezervasyon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <!-- Rezervasyon Bilgileri -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Müşteri Adı *</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon *</label>
                            <input type="tel" name="customer_phone" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Masa *</label>
                            <select name="table_id" class="form-select" required>
                                <option value="">Masa Seçiniz</option>
                                <?php foreach ($tables as $table): ?>
                                <option value="<?= $table['id'] ?>"><?= $table['table_no'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kişi Sayısı *</label>
                            <input type="number" name="guest_count" class="form-control" min="1" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tarih *</label>
                            <input type="date" name="reservation_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Saat *</label>
                            <input type="time" name="reservation_time" class="form-control" required>
                        </div>
                    </div>

                    <!-- Ön Sipariş Bölümü -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Ön Sipariş</h6>
                                <button type="button" class="btn btn-primary btn-sm" onclick="showProductModal()">
                                    <i class="fas fa-plus"></i> Ürün Ekle
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Seçilen Ürünler Tablosu -->
                    <div class="table-responsive">
                        <table class="table table-sm" id="preOrderTable">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Adet</th>
                                    <th>Fiyat</th>
                                    <th>Toplam</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Genel Toplam:</strong></td>
                                    <td colspan="2"><strong id="preOrderTotal">0.00 ₺</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Özel İstekler</label>
                            <textarea name="special_requests" class="form-control" rows="3"></textarea>
                        </div>
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

<!-- Ürün Seçme Modalı -->
<div class="modal fade" id="productModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ürün Seç</h5>
                <button type="button" class="btn-close" onclick="closeProductModal()"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Kategoriler -->
                    <div class="col-md-4 mb-3">
                        <div class="list-group">
                            <?php
                            $categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY name")->fetchAll();
                            foreach ($categories as $category):
                            ?>
                            <a href="#" class="list-group-item list-group-item-action" 
                               onclick="loadProducts(<?= $category['id'] ?>)">
                                <?= $category['name'] ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Ürünler -->
                    <div class="col-md-8">
                        <div class="row" id="productList">
                            <div class="col-12 text-center text-muted">
                                Lütfen kategori seçiniz
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Kapat</button>
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
let preOrderItems = [];
let productModal = null;
let reservationModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Modal'ları başlat
    productModal = new bootstrap.Modal(document.getElementById('productModal'), {
        backdrop: 'static'
    });
    reservationModal = new bootstrap.Modal(document.getElementById('addReservationModal'), {
        backdrop: 'static'
    });

    // Yeni Rezervasyon butonu için event listener
    document.querySelector('.btn-add-reservation').addEventListener('click', function(e) {
        e.preventDefault();
        showNewReservationModal();
    });
});

function loadProducts(categoryId) {
    fetch(`ajax/get_products.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(products => {
            const productList = document.getElementById('productList');
            productList.innerHTML = '';
            
            products.forEach(product => {
                // Resim yolunu düzelt - tam yol kullanarak
                const defaultImage = '/qr-menu/admin/assets/images/no-image.jpg';
                const imageUrl = product.image 
                    ? `../uploads/${product.image}` 
                    : defaultImage;
                
                productList.innerHTML += `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-img-wrapper">
                                <img src="${imageUrl}" class="card-img-top product-img" 
                                     alt="${product.name}" 
                                     onerror="this.src='${defaultImage}'">
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">${product.name}</h6>
                                <p class="card-text text-primary fw-bold">${product.price} ₺</p>
                                <div class="d-flex align-items-center">
                                    <input type="number" class="form-control form-control-sm me-2" 
                                           id="qty_${product.id}" value="1" min="1" style="width: 70px">
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="addToPreOrder(${product.id}, '${product.name}', ${product.price})">
                                        <i class="fas fa-plus"></i> Ekle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
}

function showNewReservationModal() {
    // Form'u sıfırla
    document.getElementById('reservationForm').reset();
    preOrderItems = [];
    updatePreOrderTable();
    
    // Modalı aç
    reservationModal.show();
}

function showProductModal() {
    // Sadece ürün modalını aç, rezervasyon modalını kapatma
    productModal.show();
}

function closeProductModal() {
    // Sadece ürün modalını kapat
    productModal.hide();
}

function addToPreOrder(productId, productName, productPrice) {
    const quantity = parseInt(document.getElementById(`qty_${productId}`).value);
    
    if (quantity < 1) {
        Swal.fire('Uyarı', 'Lütfen geçerli bir miktar giriniz', 'warning');
        return;
    }

    const existingItem = preOrderItems.find(item => item.product_id === productId);

    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        preOrderItems.push({
            product_id: productId,
            name: productName,
            price: productPrice,
            quantity: quantity
        });
    }

    updatePreOrderTable();
    
    // Miktar inputunu sıfırla
    document.getElementById(`qty_${productId}`).value = 1;
    
    // Başarılı mesajı göster (modal kapanmadan)
    Swal.fire({
        icon: 'success',
        title: 'Ürün Eklendi',
        text: `${quantity} adet ${productName} sepete eklendi.`,
        showConfirmButton: false,
        timer: 1000,
        position: 'top-end',
        toast: true
    });
}

function updatePreOrderTable() {
    const tbody = document.querySelector('#preOrderTable tbody');
    tbody.innerHTML = '';
    let total = 0;

    preOrderItems.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${item.price.toFixed(2)} ₺</td>
                <td>${itemTotal.toFixed(2)} ₺</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePreOrderItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    document.getElementById('preOrderTotal').textContent = total.toFixed(2) + ' ₺';
}

function removePreOrderItem(index) {
    preOrderItems.splice(index, 1);
    updatePreOrderTable();
}

function saveReservation() {
    const form = document.getElementById('reservationForm');
    
    // Form verilerini JSON formatında hazırla
    const formData = {
        customer_name: form.querySelector('[name="customer_name"]').value,
        phone: form.querySelector('[name="customer_phone"]').value,
        reservation_date: form.querySelector('[name="reservation_date"]').value,
        reservation_time: form.querySelector('[name="reservation_time"]').value,
        person_count: form.querySelector('[name="guest_count"]').value,
        note: form.querySelector('[name="special_requests"]').value,
        pre_order: JSON.stringify(preOrderItems)
    };

    // Konsola yazdırarak kontrol et
    console.log('Gönderilen veriler:', formData);

    // AJAX isteği
    fetch('ajax/save_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Rezervasyon başarıyla kaydedildi.',
                confirmButtonText: 'Tamam'
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: error.message || 'Bir hata oluştu!',
            confirmButtonText: 'Tamam'
        });
    });
}

function updateStatus(id, status) {
    if (status === 'cancelled') {
        // Önce rezervasyon ve sipariş durumunu kontrol et
        fetch(`ajax/check_reservation_status.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let confirmMessage = 'Rezervasyonu iptal etmek istediğinize emin misiniz?';
                    let orderDetailsHtml = '';

                    // Eğer aktif siparişler varsa
                    if (data.active_orders && data.active_orders.length > 0) {
                        orderDetailsHtml = `
                            <div class="mt-3">
                                <h6 class="text-warning mb-2">Aşağıdaki siparişler iptal edilecek:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Ürün</th>
                                                <th>Adet</th>
                                                <th>Fiyat</th>
                                                <th>Toplam</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.active_orders.map(item => `
                                                <tr>
                                                    <td>${item.product_name}</td>
                                                    <td>${item.quantity}</td>
                                                    <td>${item.price} ₺</td>
                                                    <td>${(item.price * item.quantity).toFixed(2)} ₺</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Toplam:</strong></td>
                                                <td><strong>${data.active_orders.reduce((total, item) => total + (item.price * item.quantity), 0).toFixed(2)} ₺</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        `;
                        confirmMessage = 'Bu işlem masaya aktarılan siparişleri de iptal edecektir.';
                    }

                    Swal.fire({
                        title: 'Dikkat!',
                        html: `
                            <p>${confirmMessage}</p>
                            ${orderDetailsHtml}
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Evet, İptal Et',
                        cancelButtonText: 'Vazgeç',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('id', id);
                            formData.append('status', status);

                            fetch('ajax/update_reservation.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Başarılı!',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => location.reload());
                                } else {
                                    throw new Error(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Hata:', error);
                                Swal.fire('Hata!', error.message, 'error');
                            });
                        }
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                Swal.fire('Hata!', error.message, 'error');
            });
    } else if (status === 'confirmed') {
        // Önce rezervasyon detaylarını al
        fetch(`ajax/get_reservation.php?id=${id}`)
            .then(response => response.json())
            .then(reservationData => {
                if (!reservationData.success) {
                    throw new Error(reservationData.message);
                }

                const reservation = reservationData.reservation;
                
                // Masa seçim modalını göster
                Swal.fire({
                    title: 'Masa Seçimi',
                    html: `
                        <select id="table_id" class="form-select">
                            <option value="">Masa Seçin</option>
                            ${tables.map(table => `
                                <option value="${table.id}" 
                                    ${reservation.table_id == table.id ? 'selected' : ''}>
                                    Masa ${table.table_no}
                                </option>
                            `).join('')}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Onayla',
                    cancelButtonText: 'İptal',
                    preConfirm: () => {
                        const tableId = document.getElementById('table_id').value;
                        if (!tableId) {
                            Swal.showValidationMessage('Lütfen bir masa seçin');
                            return false;
                        }
                        return tableId;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Form verilerini oluştur
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('status', status);
                        formData.append('table_id', result.value);
                        formData.append('transfer_orders', '1'); // Siparişleri aktarmak için flag

                        // Debug için konsola yazdır
                        console.log('Gönderilen veriler:', {
                            id: id,
                            status: status,
                            table_id: result.value,
                            transfer_orders: '1'
                        });

                        // Siparişleri aktar
                        fetch('ajax/update_reservation.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Sunucu yanıtı:', data); // Debug için yanıtı yazdır
                            
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Başarılı!',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => location.reload());
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Hata:', error); // Debug için hataları yazdır
                            Swal.fire('Hata!', error.message, 'error');
                        });
                    }
                });
            })
            .catch(error => {
                console.error('Hata:', error);
                Swal.fire('Hata!', error.message, 'error');
            });
    }
}

function viewReservation(id) {
    fetch(`ajax/get_reservation.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reservation = data.reservation;
                const statusBadge = `<span class="badge bg-${getStatusBadgeClass(reservation.status)}">${getStatusText(reservation.status)}</span>`;
                
                // Ön sipariş ürünlerini formatlayalım
                const preOrderItems = JSON.parse(reservation.pre_order_items || '[]');
                const preOrderHtml = preOrderItems.length > 0 ? `
                    <div class="pre-order-section mt-4">
                        <h6 class="border-bottom pb-2 mb-3">Ön Sipariş Ürünleri</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Adet</th>
                                        <th>Fiyat</th>
                                        <th>Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${preOrderItems.map(item => `
                                        <tr>
                                            <td>${item?.name || '-'}</td>
                                            <td>${item?.quantity || 0}</td>
                                            <td>${item?.price || 0} ₺</td>
                                            <td>${item?.total || 0} ₺</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Toplam:</strong></td>
                                        <td><strong>${preOrderItems.reduce((total, item) => total + (parseFloat(item?.total) || 0), 0).toFixed(2)} ₺</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                ` : '<p class="text-muted mt-3 mb-0">Ön sipariş bulunmamaktadır.</p>';

                Swal.fire({
                    title: '<h5 class="text-primary mb-0">Rezervasyon Detayları</h5>',
                    html: `
                        <div class="reservation-details">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Müşteri Adı</label>
                                                <h6 class="mb-0">${reservation.customer_name}</h6>
                                            </div>
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Telefon</label>
                                                <h6 class="mb-0">${reservation.customer_phone}</h6>
                                            </div>
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Kişi Sayısı</label>
                                                <h6 class="mb-0">${reservation.guest_count} Kişi</h6>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Masa</label>
                                                <h6 class="mb-0">${reservation.table_no ? 'Masa ' + reservation.table_no : '-'}</h6>
                                            </div>
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Tarih & Saat</label>
                                                <h6 class="mb-0">${reservation.reservation_date} ${reservation.reservation_time}</h6>
                                            </div>
                                            <div class="info-group mb-3">
                                                <label class="text-muted small mb-1">Durum</label>
                                                <div>${statusBadge}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label class="text-muted small mb-1">Özel İstekler</label>
                                        <p class="mb-0">${reservation.special_requests || '-'}</p>
                                    </div>

                                    ${preOrderHtml}
                                    
                                    <div class="mt-3 pt-3 border-top">
                                        <label class="text-muted small mb-1">Oluşturulma Tarihi</label>
                                        <p class="mb-0 small">${reservation.created_at}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Kapat',
                    confirmButtonColor: '#3085d6',
                    width: '800px',
                    customClass: {
                        container: 'reservation-modal'
                    }
                });
            } else {
                Swal.fire('Hata!', data.message || 'Rezervasyon bilgileri alınamadı.', 'error');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            Swal.fire('Hata!', 'Bir hata oluştu.', 'error');
        });
}

// Mevcut masaları global değişkene ata
const tables = <?php echo json_encode($tables); ?>;

// Helper fonksiyonları ekleyelim
function getStatusBadgeClass(status) {
    switch(status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'success';
        case 'cancelled': return 'danger';
        case 'completed': return 'info';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'pending': return 'Bekliyor';
        case 'confirmed': return 'Onaylandı';
        case 'cancelled': return 'İptal';
        case 'completed': return 'Tamamlandı';
        default: return status;
    }
}

// JavaScript'te kullanılmak üzere yetki değişkenlerini tanımla
const userPermissions = {
    canView: <?php echo $canViewReservations ? 'true' : 'false' ?>,
    canAdd: <?php echo $canAddReservation ? 'true' : 'false' ?>,
    canEdit: <?php echo $canEditReservation ? 'true' : 'false' ?>,
    canDelete: <?php echo $canDeleteReservation ? 'true' : 'false' ?>,
    canApprove: <?php echo $canApproveReservation ? 'true' : 'false' ?>,
    canReject: <?php echo $canRejectReservation ? 'true' : 'false' ?>,
    canManageSettings: <?php echo $canManageSettings ? 'true' : 'false' ?>,
    canManageTables: <?php echo hasPermission('tables.manage') ? 'true' : 'false' ?>
};

// Masa atama yetkisi kontrolü
function canAssignTable() {
    return userPermissions.canManageTables || userPermissions.canApprove;
}

// Masa seçimi kontrolü
function checkTableAssignPermission() {
    if (!canAssignTable()) {
        Swal.fire({
            icon: 'error',
            title: 'Yetkisiz İşlem',
            text: 'Masa atama yetkiniz bulunmamaktadır.'
        });
        return false;
    }
    return true;
}

// Yetki kontrolü fonksiyonu
function checkPermission(permission) {
    if (!userPermissions[permission]) {
        Swal.fire({
            icon: 'error',
            title: 'Yetkisiz İşlem',
            text: 'Bu işlem için yetkiniz bulunmamaktadır.'
        });
        return false;
    }
    return true;
}

// İşlem butonlarını yetkilere göre kontrol et
$(document).ready(function() {
    if (!userPermissions.canAdd) {
        $('.add-reservation-btn').hide();
    }
    if (!userPermissions.canEdit) {
        $('.edit-reservation-btn').hide();
    }
    if (!userPermissions.canDelete) {
        $('.delete-reservation-btn').hide();
    }
    if (!userPermissions.canApprove) {
        $('.approve-reservation-btn').hide();
    }
    if (!userPermissions.canReject) {
        $('.reject-reservation-btn').hide();
    }
    if (!userPermissions.canManageSettings) {
        $('.settings-btn').hide();
    }
});
</script> 