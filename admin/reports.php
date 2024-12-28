<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();
include 'navbar.php';

$db = new Database();

// Tarih filtreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Genel İstatistikler
$stats = $db->query("SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?", 
    [$start_date, $end_date])->fetch();

// En çok satan ürünler
$top_products = $db->query("SELECT 
    p.name, 
    COUNT(*) as order_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT 10", 
    [$start_date, $end_date])->fetchAll();

// Masa bazlı analiz
$table_stats = $db->query("SELECT 
    t.table_no,
    COUNT(o.id) as order_count,
    SUM(o.total_amount) as total_revenue
    FROM tables t
    LEFT JOIN orders o ON o.table_id = t.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY total_revenue DESC", 
    [$start_date, $end_date])->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Raporlar - QR Menü Admin</title>
    <!-- Chart.js kütüphanesi -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="main-content">
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
                            <h3 class="mt-2 mb-0"><?= number_format($stats['total_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-0">Toplam Gelir</h6>
                            <h3 class="mt-2 mb-0"><?= number_format($stats['total_revenue'], 2) ?> ₺</h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-0">Ortalama Sipariş</h6>
                            <h3 class="mt-2 mb-0"><?= number_format($stats['avg_order_value'], 2) ?> ₺</h3>
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