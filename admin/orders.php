<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Yetki kontrolü
if (!hasPermission('orders.view')) {
    // Yetkisiz erişim durumunda yönlendirme
    header('Location: dashboard.php');
    exit();
}

include 'navbar.php';

$db = new Database();

// Filtreleme parametreleri
$filter_type = $_GET['filter_type'] ?? 'active';
$status = $_GET['status'] ?? 'all';
$table_id = $_GET['table'] ?? 'all';
$date = $_GET['date'] ?? date('Y-m-d');

// Sipariş durumları
$orderStatuses = [
    'all' => 'Tüm Durumlar',
    'pending' => 'Beklemede',
    'preparing' => 'Hazırlanıyor',
    'ready' => 'Hazır',
    'delivered' => 'Teslim Edildi'
];

// Sipariş tipleri
$orderTypes = [
    'active' => 'Aktif Siparişler',
    'completed' => 'Tamamlanan Siparişler'
];

// Sorgu oluştur
$query = "SELECT o.*, t.table_no 
          FROM orders o 
          LEFT JOIN tables t ON o.table_id = t.id 
          WHERE 1=1";

$params = [];

// Aktif/Tamamlanan filtresi
if ($filter_type === 'active') {
    $query .= " AND o.status NOT IN ('completed', 'cancelled')";
} else {
    $query .= " AND o.status = 'completed'";
}

if($status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if($table_id != 'all') {
    $query .= " AND o.table_id = ?";
    $params[] = $table_id;
}

if($date) {
    $query .= " AND DATE(o.created_at) = ?";
    $params[] = $date;
}

$query .= " ORDER BY o.created_at DESC";

$orders = $db->query($query, $params)->fetchAll();
$tables = $db->query("SELECT * FROM tables")->fetchAll();
?>

<!-- CSS kısmına ekle -->
<style>
.highlighted-order {
    animation: highlight 2s ease-in-out;
}

@keyframes highlight {
    0% { background-color: rgba(231, 76, 60, 0.2); }
    100% { background-color: transparent; }
}

/* Siparişler sayfası modern stil */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: #fff;
    transition: all 0.3s ease;
}

.card-header {
    background: linear-gradient(45deg, #f8f9fa, #fff);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.25rem;
    border-radius: 15px 15px 0 0;
}

.card-header h5 {
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

/* Filtreler bölümü */
.filters-section {
    background: #fff;
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.form-select, .form-control {
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.form-select:focus, .form-control:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.15);
}

/* Tablo stilleri */
.table {
    margin: 0;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid rgba(0,0,0,0.05);
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
    color: #2c3e50;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Sipariş durumu select */
.status-select {
    min-width: 140px;
    font-size: 0.9rem;
    padding: 0.4rem;
    border-radius: 6px;
}

/* Durum renkleri */
.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-preparing {
    background-color: #cce5ff;
    color: #004085;
}

.status-ready {
    background-color: #d4edda;
    color: #155724;
}

.status-delivered {
    background-color: #d1e7dd;
    color: #0f5132;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

/* Butonlar */
.btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(45deg, #4CAF50, #45a049);
    border: none;
    box-shadow: 0 2px 6px rgba(76, 175, 80, 0.2);
}

.btn-primary:hover {
    background: linear-gradient(45deg, #45a049, #3d8b40);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.btn-info {
    background: linear-gradient(45deg, #2196F3, #1976D2);
    border: none;
    color: white;
    box-shadow: 0 2px 6px rgba(33, 150, 243, 0.2);
}

.btn-info:hover {
    background: linear-gradient(45deg, #1976D2, #1565C0);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

/* Sipariş detay modal */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    background: linear-gradient(45deg, #f8f9fa, #fff);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    border-radius: 15px 15px 0 0;
    padding: 1.25rem;
}

.modal-title {
    font-weight: 600;
    color: #2c3e50;
}

.modal-body {
    padding: 1.5rem;
}

/* Animasyonlar */
.highlighted-order {
    animation: highlightFade 2s ease-in-out;
}

@keyframes highlightFade {
    0% { background-color: rgba(76, 175, 80, 0.2); }
    100% { background-color: transparent; }
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .card {
        border-radius: 0;
        margin: -1rem;
    }
    
    .filters-section {
        padding: 1rem;
    }
    
    .table-responsive {
        margin: 0 -1rem;
    }
    
    .status-select {
        min-width: 120px;
    }
}

/* Disabled select için stil */
select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

/* Sadece aktif siparişler için stil tanımlamaları */
.filter-type-active .order-new {
    border-left: 4px solid #28a745 !important;
    background-color: rgba(40, 167, 69, 0.05);
}

.filter-type-active .order-waiting {
    border-left: 4px solid #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.05);
}

/* Sipariş durumları için rozetler */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Tablo satırları için hover efekti */
.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}
</style>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?= $filter_type === 'active' ? 'Aktif Siparişler' : 'Tamamlanan Siparişler' ?>
            </h5>
        </div>
        <div class="card-body">
            <!-- Filtreler -->
            <div class="filters-section mb-4">
                <div class="row g-3">
                    <!-- Sipariş Tipi Filtresi -->
                    <div class="col-md-3">
                        <select class="form-select" id="filterType">
                            <?php foreach($orderTypes as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $filter_type === $key ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sipariş Durumu Filtresi -->
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <?php foreach($orderStatuses as $key => $value): ?>
                                <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>>
                                    <?= $value ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Masa Filtresi -->
                    <div class="col-md-2">
                        <select class="form-select" id="tableFilter">
                            <option value="all">Tüm Masalar</option>
                            <?php foreach($tables as $table): ?>
                                <option value="<?= $table['id'] ?>" <?= $table_id == $table['id'] ? 'selected' : '' ?>>
                                    Masa <?= htmlspecialchars($table['table_no']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Tarih Filtresi -->
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="dateFilter" value="<?= $date ?>">
                    </div>
                    
                    <!-- Filtreleme Butonu -->
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                    </div>
                </div>
            </div>

            <!-- Siparişler Tablosu -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Masa</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <?php if($filter_type === 'active'): ?>
                                <th>Bekleme Süresi</th>
                            <?php else: ?>
                                <th>Tamamlanma Tarihi</th>
                            <?php endif; ?>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="filter-type-<?= $filter_type ?>">
                        <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <?= $filter_type === 'active' ? 'Aktif sipariş bulunmuyor.' : 'Tamamlanan sipariş bulunmuyor.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($orders as $order): 
                                $rowClass = '';
                                
                                if($filter_type === 'active') {
                                    // Sadece aktif siparişler için zaman hesaplaması ve sınıf ataması
                                    $orderTime = strtotime($order['created_at']);
                                    $currentTime = time();
                                    $waitingTime = $currentTime - $orderTime;
                                    $waitingMinutes = floor($waitingTime / 60);
                                    
                                    if($order['status'] === 'pending') {
                                        $rowClass = 'order-new';
                                    } elseif($waitingMinutes > 30) {
                                        $rowClass = 'order-waiting';
                                    }
                                }
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td>
                                        #<?= $order['id'] ?>
                                        <?php if($filter_type === 'active' && $waitingMinutes < 5): ?>
                                            <span class="badge bg-success">Yeni</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Masa <?= htmlspecialchars($order['table_no']) ?></td>
                                    <td><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                    <td>
                                        <?php if($filter_type === 'active'): ?>
                                            <select class="form-select form-select-sm status-select" 
                                                    data-order-id="<?= $order['id'] ?>"
                                                    style="max-width: 150px;">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Beklemede</option>
                                                <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                                                <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Hazır</option>
                                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge bg-success">Tamamlandı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($filter_type === 'active'): ?>
                                            <?php
                                            if($waitingMinutes < 60) {
                                                echo $waitingMinutes . ' dakika';
                                            } else {
                                                $hours = floor($waitingMinutes / 60);
                                                $mins = $waitingMinutes % 60;
                                                echo $hours . ' saat ' . $mins . ' dakika';
                                            }
                                            if($waitingMinutes > 30) {
                                                echo ' <span class="badge bg-warning">Uzun Bekleme</span>';
                                            }
                                            ?>
                                        <?php else: ?>
                                            <?= date('d.m.Y H:i', strtotime($order['completed_at'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm view-order" 
                                                data-order-id="<?= $order['id'] ?>"
                                                title="Detayları Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sipariş Detay Modal -->
<div class="modal fade" id="orderModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sipariş Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Sipariş detayları AJAX ile yüklenecek -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary btn-print">
                    <i class="fas fa-print"></i> Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sayfanın en altında, body kapanmadan önce -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/orders.js"></script>

<!-- JavaScript kısmına ekle -->
<script>
$(document).ready(function() {
    // URL'den highlight parametresini kontrol et
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('highlight') === 'true' && urlParams.get('table')) {
        // İlgili masanın siparişlerini vurgula
        $('tr[data-table-id="' + urlParams.get('table') + '"]').addClass('highlighted-order');
        
        // Sayfayı ilgili siparişe kaydır
        $('html, body').animate({
            scrollTop: $('tr[data-table-id="' + urlParams.get('table') + '"]').offset().top - 100
        }, 1000);
    }

    // Mevcut view-order click eventi (değiştirmeyin)
    $('.view-order').on('click', function() {
        var orderId = $(this).data('order-id');
        $.get('ajax/get_order_detail.php', {order_id: orderId}, function(response) {
            $('#orderModal .modal-body').html(response);
            $('#orderModal').modal('show');
        });
    });

    // Sadece yazdırma özelliğini ekleyelim
    $('.btn-print').on('click', function() {
        const printContent = $('#orderModal .modal-body').html();
        const printWindow = window.open('', '', 'height=600,width=800');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Sipariş Detayı</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        ${printContent}
                    </div>
                </body>
            </html>
        `);
        
        printWindow.document.write('<script>');
        printWindow.document.write(`
            window.onload = function() {
                window.print();
                window.onafterprint = function() {
                    window.close();
                }
            }
        `);
        printWindow.document.write('</scr' + 'ipt>');
        
        printWindow.document.close();
    });
});

function applyFilters() {
    const filterType = document.getElementById('filterType').value;
    const status = document.getElementById('statusFilter').value;
    const tableId = document.getElementById('tableFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    window.location.href = `orders.php?filter_type=${filterType}&status=${status}&table=${tableId}&date=${date}`;
}

// Filtre değişikliklerini dinle
document.getElementById('filterType').addEventListener('change', function() {
    // Eğer tamamlanan siparişler seçilirse, durum filtresini gizle
    const statusFilter = document.getElementById('statusFilter');
    if (this.value === 'completed') {
        statusFilter.value = 'all';
        statusFilter.disabled = true;
    } else {
        statusFilter.disabled = false;
    }
});

// Sayfa yüklendiğinde durum filtresinin durumunu kontrol et
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filterType').value;
    const statusFilter = document.getElementById('statusFilter');
    if (filterType === 'completed') {
        statusFilter.value = 'all';
        statusFilter.disabled = true;
    }
});

// Durum değişikliği için AJAX
$(document).on('change', '.status-select', function() {
    const orderId = $(this).data('order-id');
    const newStatus = $(this).val();
    
    $.post('ajax/update_order_status.php', {
        order_id: orderId,
        status: newStatus
    }, function(response) {
        if(response.success) {
            // Başarılı güncelleme bildirimi
            toastr.success('Sipariş durumu güncellendi');
            
            // Sayfayı yenileme
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error('Bir hata oluştu!');
        }
    });
});
</script>