<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'includes/print_helper.php';

// Yetki kontrolü
if (!hasPermission('orders.view')) {
    // Yetkisiz erişim durumunda yönlendirme
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
include 'navbar.php';

// Yazıcı ayarlarını al
$printerSettings = [];
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%' OR setting_key = 'restaurant_name'");
$results = $settingsQuery->fetchAll();

foreach ($results as $row) {
    $printerSettings[$row['setting_key']] = $row['setting_value'];
}

// Restoran adını ayrıca al
$restaurantName = $printerSettings['restaurant_name'] ?? 'Restaurant';

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
    'delivered' => 'Teslim Edildi',
    'cancelled' => 'İptal Edildi'
];

// Sipariş tipleri
$orderTypes = [
    'active' => 'Aktif Siparişler',
    'completed' => 'Tamamlanan Siparişler',
    'cancelled' => 'İptal Edilen Siparişler'
];

// Sorgu oluştur
$query = "SELECT o.*, t.table_no, 
          CASE 
              WHEN o.status = 'cancelled' THEN o.cancelled_at 
              WHEN o.status = 'completed' THEN o.completed_at 
              ELSE o.created_at 
          END as display_date
          FROM orders o 
          LEFT JOIN tables t ON o.table_id = t.id 
          WHERE 1=1";

$params = [];

// Aktif/Tamamlanan/İptal Edilen filtresi
if ($filter_type === 'active') {
    $query .= " AND o.status NOT IN ('completed', 'cancelled')";
} elseif ($filter_type === 'completed') {
    $query .= " AND o.status = 'completed'";
} elseif ($filter_type === 'cancelled') {
    $query .= " AND o.status = 'cancelled'";
}

// Diğer filtreler
if($status != 'all' && $filter_type === 'active') {
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
                <?= $filter_type === 'active' ? 'Aktif Siparişler' : ($filter_type === 'completed' ? 'Tamamlanan Siparişler' : 'İptal Edilen Siparişler') ?>
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
                            <th>
                                <?php if($filter_type === 'active'): ?>
                                    Bekleme Süresi
                                <?php else: ?>
                                    <?= $filter_type === 'cancelled' ? 'İptal Edilme Tarihi' : 'Tamamlanma Tarihi' ?>
                                <?php endif; ?>
                            </th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="filter-type-<?= $filter_type ?>">
                        <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <?= $filter_type === 'active' ? 'Aktif sipariş bulunmuyor.' : ($filter_type === 'completed' ? 'Tamamlanan sipariş bulunmuyor.' : 'İptal edilen sipariş bulunmuyor.') ?>
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
                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>İptal Et</option>
                                            </select>
                                        <?php elseif($filter_type === 'cancelled'): ?>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-danger me-2">İptal Edildi</span>
                                                <button class="btn btn-sm btn-outline-success restore-order" 
                                                        data-order-id="<?= $order['id'] ?>"
                                                        title="Siparişi Geri Al">
                                                    <i class="fas fa-undo"></i> Geri Al
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <?php
                                            $statusText = '';
                                            switch($order['status']) {
                                                case 'completed':
                                                    $statusText = '<span class="badge bg-success">Tamamlandı</span>';
                                                    break;
                                                case 'cancelled':
                                                    $statusText = '<span class="badge bg-danger">İptal Edildi</span>';
                                                    break;
                                                default:
                                                    $statusText = '<span class="badge bg-secondary">'.ucfirst($order['status']).'</span>';
                                            }
                                            echo $statusText;
                                            ?>
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
                                            <?php 
                                            $displayDate = $filter_type === 'cancelled' ? $order['cancelled_at'] : $order['completed_at'];
                                            echo !empty($displayDate) ? date('d.m.Y H:i', strtotime($displayDate)) : date('d.m.Y H:i', strtotime($order['created_at']));
                                            ?>
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
        $.post('ajax/get_order_details.php', {order_id: orderId}, function(response) {
            if(response.success) {
                $('#orderModal .modal-body').html(response.html);
                $('#orderModal').modal('show');
            } else {
                alert(response.message || 'Sipariş detayları alınamadı.');
            }
        }, 'json');
    });

    // Responsive sipariş detayı yazdırma fonksiyonu
    $('.btn-print').on('click', function() {
        // PHP'den gelen yazıcı ayarlarını al
        const printerSettings = <?php echo json_encode($printerSettings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const restaurantName = <?php echo json_encode($restaurantName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        
        // Modal içeriğinden sipariş verilerini çıkar
        const orderDetails = extractOrderDataFromModal();
        
        if (!orderDetails) {
            alert('Sipariş verileri alınamadı. Lütfen tekrar deneyin.');
            return;
        }
        
        // Responsive receipt içeriği oluştur
        const receiptContent = buildResponsiveOrderReceipt(orderDetails, printerSettings);

        // Dynamic window size based on content
        const printWindow = window.open('', '', 'width=400,height=500,scrollbars=yes,resizable=yes');
        
        const paperWidth = printerSettings['printer_paper_width'] || '80';
        const fontSize = paperWidth <= 58 ? '10px' : (paperWidth <= 80 ? '12px' : '14px');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Sipariş Detayı #${orderDetails.id}</title>
                    <meta charset="UTF-8">
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        body { 
                            font-family: "Courier New", monospace;
                            font-size: ${fontSize};
                            line-height: 1.1;
                            padding: 5px;
                            background: white;
                        }
                        .receipt-content {
                            width: ${paperWidth}mm;
                            margin: 0 auto;
                            font-family: "Courier New", monospace;
                            min-height: auto;
                        }
                        .responsive-line {
                            white-space: pre;
                            margin: 1px 0;
                            line-height: 1.1;
                        }
                        @media print {
                            body { 
                                margin: 0; 
                                padding: 2px;
                                background: white;
                                -webkit-print-color-adjust: exact;
                            }
                            @page { 
                                margin: 0;
                                size: ${paperWidth}mm auto;
                            }
                            .no-print { display: none; }
                            .receipt-content {
                                width: 100%;
                                margin: 0;
                            }
                        }
                        @media screen {
                            body {
                                padding: 10px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-content">
                        ${receiptContent}
                    </div>
                    <script>
                        window.onload = function() {
                            // Auto-resize window to content
                            const content = document.querySelector('.receipt-content');
                            const contentHeight = content.offsetHeight;
                            const windowHeight = Math.min(contentHeight + 60, 700); // Max 700px
                            
                            window.resizeTo(400, windowHeight);
                            
                            // Auto print after a short delay
                            setTimeout(() => {
                                window.print();
                            }, 100);
                            
                            window.onafterprint = function() {
                                window.close();
                            }
                        }
                    <\/script>
                </body>
            </html>
        `);
        
        printWindow.document.close();
    });
});

// Modal içeriğinden sipariş verilerini çıkar
function extractOrderDataFromModal() {
    try {
        const modalBody = document.querySelector('#orderModal .modal-body');
        if (!modalBody) return null;

        // Sipariş bilgilerini çıkar
        const orderDetails = modalBody.querySelector('.order-details');
        if (!orderDetails) return null;

        // Temel bilgileri al
        const orderInfo = orderDetails.textContent;
        const orderIdMatch = orderInfo.match(/Sipariş No:\s*#(\d+)/);
        const tableMatch = orderInfo.match(/Masa:\s*([^\n]+)/);
        const dateMatch = orderInfo.match(/Tarih:\s*([^\n]+)/);

        // Sipariş notunu al
        const noteElement = orderDetails.querySelector('.alert-info');
        const note = noteElement ? noteElement.textContent.replace('Sipariş Notu:', '').trim() : '';

        // Ürünleri al
        const items = [];
        const rows = orderDetails.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                items.push({
                    product_name: cells[0].textContent.trim(),
                    quantity: parseInt(cells[1].textContent.trim()),
                    price: parseFloat(cells[2].textContent.replace(/[^0-9.]/g, '')),
                    total: parseFloat(cells[3].textContent.replace(/[^0-9.]/g, ''))
                });
            }
        });

        // Toplam tutarı al
        const totalElement = orderDetails.querySelector('tfoot th:last-child');
        const total = totalElement ? parseFloat(totalElement.textContent.replace(/[^0-9.]/g, '')) : 0;

        return {
            id: orderIdMatch ? orderIdMatch[1] : '',
            table_name: tableMatch ? tableMatch[1] : '',
            created_at: dateMatch ? dateMatch[1] : '',
            note: note,
            items: items,
            total_amount: total
        };
    } catch (error) {
        console.error('Modal veri çıkarma hatası:', error);
        return null;
    }
}

// Responsive sipariş fişi oluştur
function buildResponsiveOrderReceipt(orderData, settings) {
    const paperWidth = settings['printer_paper_width'] || '80';
    const charWidth = getJSCharacterWidth(paperWidth);
    const autoCut = settings['printer_auto_cut'] == '1';
    
    let content = '';
    
    // Başlık
    if (settings['printer_header']) {
        const headerLines = wrapJSText(settings['printer_header'], charWidth);
        headerLines.forEach(line => {
            content += `<div class="responsive-line" style="text-align: center;">${line}</div>`;
        });
        content += '<div class="responsive-line"></div>';
    }
    
    // Restoran adı
    if (settings['restaurant_name']) {
        const nameLines = wrapJSText(settings['restaurant_name'], charWidth);
        nameLines.forEach(line => {
            content += `<div class="responsive-line" style="text-align: center; font-weight: bold;">${line}</div>`;
        });
    }
    
    content += `<div class="responsive-line" style="text-align: center;">${'='.repeat(charWidth)}</div>`;
    
    // Fiş türü
    content += `<div class="responsive-line" style="text-align: center; font-weight: bold;">SİPARİŞ DETAYI</div>`;
    content += `<div class="responsive-line">${'='.repeat(charWidth)}</div>`;
    
    // Temel bilgiler
    content += `<div class="responsive-line">Tarih: ${orderData.created_at}</div>`;
    if (orderData.table_name) {
        content += `<div class="responsive-line">Masa: ${orderData.table_name}</div>`;
    }
    content += `<div class="responsive-line">Sipariş No: #${orderData.id}</div>`;
    content += '<div class="responsive-line"></div>';
    
    // Ürünler
    content += `<div class="responsive-line">${'-'.repeat(charWidth)}</div>`;
    content += `<div class="responsive-line" style="text-align: center; font-weight: bold;">ÜRÜNLER</div>`;
    content += `<div class="responsive-line">${'-'.repeat(charWidth)}</div>`;
    
    let total = 0;
    orderData.items.forEach(item => {
        const itemTotal = item.quantity * item.price;
        total += itemTotal;
        
        const formattedLine = formatJSPriceLine(item.product_name, item.quantity, item.price, charWidth);
        content += `<div class="responsive-line">${formattedLine}</div>`;
    });
    
    // Toplam
    content += `<div class="responsive-line">${'-'.repeat(charWidth)}</div>`;
    const totalLine = formatJSTotalLine('TOPLAM:', total, charWidth);
    content += `<div class="responsive-line" style="font-weight: bold;">${totalLine}</div>`;
    content += `<div class="responsive-line">${'='.repeat(charWidth)}</div>`;
    
    // Sipariş notu (sadece varsa)
    if (orderData.note && orderData.note.trim()) {
        content += '<div class="responsive-line"></div>';
        const noteLines = wrapJSText('Not: ' + orderData.note, charWidth);
        noteLines.forEach(line => {
            content += `<div class="responsive-line">${line}</div>`;
        });
    }
    
    // Alt bilgi (sadece varsa)
    if (settings['printer_footer'] && settings['printer_footer'].trim()) {
        content += '<div class="responsive-line"></div>';
        const footerLines = wrapJSText(settings['printer_footer'], charWidth);
        footerLines.forEach(line => {
            content += `<div class="responsive-line" style="text-align: center;">${line}</div>`;
        });
    }
    
    // Minimal spacing based on auto-cut setting
    if (autoCut) {
        // Auto-cut enabled: minimal spacing
        content += '<div class="responsive-line"></div>';
    } else {
        // Manual cut: slightly more space for tearing
        content += '<div class="responsive-line"></div>';
        content += '<div class="responsive-line"></div>';
    }
    
    return content;
}

// JavaScript karakter genişliği hesapla
function getJSCharacterWidth(paperWidth) {
    const widthMap = {
        '58': 24,
        '80': 32,
        '112': 44
    };
    
    if (widthMap[paperWidth]) {
        return widthMap[paperWidth];
    }
    
    const calculatedWidth = Math.floor(paperWidth * 0.4);
    return Math.max(20, Math.min(60, calculatedWidth));
}

// JavaScript metin sarma
function wrapJSText(text, width) {
    if (text.length <= width) {
        return [text];
    }
    
    const lines = [];
    const words = text.split(' ');
    let currentLine = '';
    
    words.forEach(word => {
        if ((currentLine + ' ' + word).length <= width) {
            currentLine += (currentLine ? ' ' : '') + word;
        } else {
            if (currentLine) {
                lines.push(currentLine);
                currentLine = word;
            } else {
                lines.push(word.substring(0, width - 3) + '...');
                currentLine = '';
            }
        }
    });
    
    if (currentLine) {
        lines.push(currentLine);
    }
    
    return lines;
}

// JavaScript iki kolon formatı (Geliştirilmiş)
function formatJSTwoColumns(leftText, rightText, totalWidth) {
    // Sağ kolonda fiyat/toplam olacaksa daha fazla alan ver
    const isPriceText = /\d+[.,]\d+\s*(TL|₺)|\d+\s*x\s*\d+[.,]\d+/.test(rightText);
    
    let leftWidth, rightWidth;
    if (isPriceText) {
        // Fiyat metni için: sol %60, sağ %40
        leftWidth = Math.floor(totalWidth * 0.60);
        rightWidth = totalWidth - leftWidth;
    } else {
        // Normal metin için: sol %65, sağ %35
        leftWidth = Math.floor(totalWidth * 0.65);
        rightWidth = totalWidth - leftWidth;
    }
    
    // Sağ metni önce kontrol et - bu fiyat olabilir, kesilmemeli
    if (rightText.length > rightWidth) {
        // Sağ metin çok uzunsa, sol metni daha çok kes
        rightWidth = Math.min(rightWidth + 3, totalWidth - 10); // Min 10 karakter sol için
        leftWidth = totalWidth - rightWidth;
    }
    
    // Sol metni kes gerekirse
    if (leftText.length > leftWidth) {
        leftText = leftText.substring(0, leftWidth - 3) + '...';
    }
    
    // Sağ metni son kontrol
    if (rightText.length > rightWidth) {
        rightText = rightText.substring(0, rightWidth - 1);
    }
    
    return leftText.padEnd(leftWidth, ' ') + rightText.padStart(rightWidth, ' ');
}

// JavaScript fiyat satırı formatlama
function formatJSPriceLine(productName, quantity, price, totalWidth) {
    const priceNum = parseFloat(price);
    const quantityPrice = quantity + ' x ' + priceNum.toFixed(2);
    
    // Fiyat alanı için gerekli minimum genişlik
    const priceMinWidth = Math.max(quantityPrice.length, 8) + 1;
    
    // Fiyat alanı toplam genişliğin max %45'i olabilir, min 8 karakter
    const rightWidth = Math.min(Math.max(priceMinWidth, 8), Math.floor(totalWidth * 0.45));
    const leftWidth = totalWidth - rightWidth;
    
    // Ürün adını kes gerekirse
    if (productName.length > leftWidth) {
        productName = productName.substring(0, leftWidth - 3) + '...';
    }
    
    return productName.padEnd(leftWidth, ' ') + quantityPrice.padStart(rightWidth, ' ');
}

// JavaScript toplam satırı formatlama
function formatJSTotalLine(label, amount, totalWidth) {
    const amountNum = parseFloat(amount);
    const amountText = amountNum.toFixed(2) + ' TL';
    
    // Tutar için gerekli minimum genişlik
    const rightWidth = Math.max(amountText.length + 1, 10);
    const leftWidth = totalWidth - rightWidth;
    
    // Etiket metni kes gerekirse
    if (label.length > leftWidth) {
        label = label.substring(0, leftWidth - 1);
    }
    
    return label.padEnd(leftWidth, ' ') + amountText.padStart(rightWidth, ' ');
}

function applyFilters() {
    const filterType = document.getElementById('filterType').value;
    const status = document.getElementById('statusFilter').value;
    const tableId = document.getElementById('tableFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    window.location.href = `orders.php?filter_type=${filterType}&status=${status}&table=${tableId}&date=${date}`;
}

// Filtre değişikliklerini dinle
document.getElementById('filterType').addEventListener('change', function() {
    const statusFilter = document.getElementById('statusFilter');
    const filterType = this.value;
    
    // Filtre tipine göre durum filtresini ayarla
    if (filterType === 'completed' || filterType === 'cancelled') {
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
    
    if (filterType === 'completed' || filterType === 'cancelled') {
        statusFilter.value = 'all';
        statusFilter.disabled = true;
    }
});

// Sipariş geri alma işlemi
$(document).on('click', '.restore-order', function() {
    const orderId = $(this).data('order-id');
    
    Swal.fire({
        title: 'Sipariş Geri Alma',
        text: `#${orderId} numaralı siparişi geri almak istediğinize emin misiniz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Evet, Geri Al',
        cancelButtonText: 'Vazgeç'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax/update_order_status.php', {
                order_id: orderId,
                status: 'pending'  // İptal edilen siparişi beklemede durumuna al
            }, function(response) {
                if(response.success) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: `#${orderId} numaralı sipariş başarıyla geri alındı`,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();  // Sayfayı yenile
                    });
                } else {
                    Swal.fire({
                        title: 'Hata!',
                        text: response.message || 'Bir hata oluştu',
                        icon: 'error'
                    });
                }
            }).fail(function() {
                Swal.fire({
                    title: 'Hata!',
                    text: 'Sunucu ile iletişim kurulamadı',
                    icon: 'error'
                });
            });
        }
    });
});

// Durum değişikliği için AJAX - İptal onayı ekle
$(document).on('change', '.status-select', function() {
    const orderId = $(this).data('order-id');
    const newStatus = $(this).val();
    
    if(newStatus === 'cancelled') {
        Swal.fire({
            title: 'Sipariş İptali',
            text: `#${orderId} numaralı siparişi iptal etmek istediğinize emin misiniz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, İptal Et',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                updateOrderStatus(orderId, newStatus);
            } else {
                location.reload(); // Seçimi geri al
            }
        });
    } else {
        updateOrderStatus(orderId, newStatus);
    }
});

function updateOrderStatus(orderId, newStatus) {
    $.post('ajax/update_order_status.php', {
        order_id: orderId,
        status: newStatus
    }, function(response) {
        if(response.success) {
            Swal.fire({
                title: 'Başarılı!',
                text: 'Sipariş durumu güncellendi',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Hata!',
                text: response.message || 'Bir hata oluştu',
                icon: 'error'
            });
        }
    });
}
</script>

</body>
</html>