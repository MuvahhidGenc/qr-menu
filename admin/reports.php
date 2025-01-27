<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('reports.view')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Tarih filtreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Ödeme yöntemi metni için yardımcı fonksiyon
function getPaymentMethodText($method) {
    switch($method) {
        case 'cash':
            return 'Nakit';
        case 'credit_card':
            return 'Kredi Kartı';
        case 'debit_card':
            return 'Banka Kartı';
        default:
            return $method;
    }
}

// Genel İstatistikler (sadece tamamlanan siparişler ve ödemeler)
$stats = $db->query("SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(o.total_amount), 0) as total_revenue,
    COALESCE(AVG(o.total_amount), 0) as avg_order_value
    FROM orders o
    JOIN payments p ON o.payment_id = p.id 
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'completed'
    AND p.status = 'completed'", 
    [$start_date, $end_date]
)->fetch();

// İptal edilen ödemeler için istatistikler
$cancelled_stats = $db->query("
    SELECT 
        p.id,
        p.created_at,
        p.total_amount,
        p.payment_method,
        p.payment_note,
        o.table_id,
        COUNT(*) as count,
        SUM(p.total_amount) as total
    FROM payments p
    LEFT JOIN orders o ON p.id = o.payment_id
    WHERE p.status = 'cancelled'
    AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY p.id", 
    [$start_date, $end_date]
)->fetchAll();

// En çok satan ürünler (iptal edilmeyenler)
$top_products = $db->query("
    SELECT 
        p.name, 
        COUNT(DISTINCT CASE WHEN o.status = 'completed' AND pay.status = 'completed' THEN o.id END) as order_count,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND pay.status = 'completed' THEN oi.quantity ELSE 0 END), 0) as total_quantity,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND pay.status = 'completed' THEN oi.quantity * oi.price ELSE 0 END), 0) as total_revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    JOIN payments pay ON o.payment_id = pay.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    HAVING total_quantity > 0
    ORDER BY total_quantity DESC
    LIMIT 10", 
    [$start_date, $end_date]
)->fetchAll();

// Masa bazlı analiz
$table_stats = $db->query("
    SELECT 
        t.table_no,
        COUNT(DISTINCT CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN o.id END) as order_count,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_revenue
    FROM tables t
    LEFT JOIN orders o ON o.table_id = t.id 
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY t.id, t.table_no
    HAVING total_revenue > 0
    ORDER BY total_revenue DESC", 
    [$start_date, $end_date]
)->fetchAll();

// Saatlik satış grafiği için veriler (sadece tamamlanan siparişler)
$hourly_sales = $db->query("
    SELECT 
        DATE_FORMAT(o.created_at, '%H:00') as hour,
        p.payment_method,
        SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN o.total_amount ELSE 0 END) as total_amount
    FROM orders o
    JOIN payments p ON o.payment_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(o.created_at, '%H'), p.payment_method
    HAVING total_amount > 0
    ORDER BY hour",
    [$start_date, $end_date]
)->fetchAll();

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Raporlar - QR Menü Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Raporlar</h2>
            
            <!-- Tarih Filtresi -->
            <div class="card mb-0">
                <div class="card-body p-2">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="small">Başlangıç</label>
                            <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <label class="small">Bitiş</label>
                            <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">Filtrele</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Genel İstatistikler -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-0">Toplam Sipariş</h6>
                        <h3 class="mt-2 mb-0"><?= number_format((float)$stats['total_orders']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-0">Toplam Gelir</h6>
                        <h3 class="mt-2 mb-0"><?= number_format((float)$stats['total_revenue'], 2) ?> ₺</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-0">Ortalama Sipariş</h6>
                        <h3 class="mt-2 mb-0"><?= number_format((float)$stats['avg_order_value'], 2) ?> ₺</h3>
                    </div>
                </div>
            </div>
            <!-- İptal Edilen Ödemeler Kartı -->
            <div class="col-12 col-md-4">
                <div class="card h-100 border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-danger mb-0">İptal Edilen Ödemeler</h6>
                                <h3 class="mt-2 mb-0 text-danger">
                                    <?php
                                    $total_cancelled = 0;
                                    foreach ($cancelled_stats as $stat) {
                                        $total_cancelled += $stat['total_amount'];
                                    }
                                    echo number_format($total_cancelled, 2);
                                    ?> ₺
                                </h3>
                                <small class="text-muted"><?= count($cancelled_stats) ?> işlem</small>
                            </div>
                            <i class="fas fa-ban fa-2x text-danger"></i>
                        </div>
                        <?php if (!empty($cancelled_stats)): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-3" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#cancelledPaymentsModal">
                                Detayları Görüntüle
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- İptal Edilen Ödemeler Modal -->
        <div class="modal fade" id="cancelledPaymentsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">İptal Edilen Ödemeler</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Masa</th>
                                        <th>Ödeme Türü</th>
                                        <th>Tutar</th>
                                        <th>İptal Nedeni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($cancelled_stats as $payment): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td><?= $payment['table_id'] ? 'Masa ' . $payment['table_id'] : '-' ?></td>
                                        <td><?= getPaymentMethodText($payment['payment_method']) ?></td>
                                        <td><?= number_format($payment['total_amount'], 2) ?> ₺</td>
                                        <td><?= $payment['payment_note'] ?? '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafikler -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">En Çok Satan Ürünler</h6>
                        <div style="height: 300px;">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">Masa Bazlı Gelir</h6>
                        <div style="height: 300px;">
                            <canvas id="tableRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detaylı Tablo -->
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">En Çok Satan Ürünler - Detay</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th class="text-end">Sipariş</th>
                                <th class="text-end">Adet</th>
                                <th class="text-end">Gelir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td class="text-end"><?= $product['order_count'] ?></td>
                                <td class="text-end"><?= $product['total_quantity'] ?></td>
                                <td class="text-end"><?= number_format($product['total_revenue'], 2) ?> ₺</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js ayarları -->
    <script>
    Chart.defaults.font.size = 12;
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    // En çok satan ürünler grafiği
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_products, 'name')) ?>,
            datasets: [{
                label: 'Satış Adedi',
                data: <?= json_encode(array_column($top_products, 'total_quantity')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Masa bazlı gelir grafiği
    new Chart(document.getElementById('tableRevenueChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_map(function($t) { return 'Masa ' . $t['table_no']; }, $table_stats)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($table_stats, 'total_revenue')) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ]
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    </script>
</body>
</html> 