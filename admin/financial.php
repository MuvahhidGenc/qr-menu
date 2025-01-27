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
    ob_end_flush(); // Tamponu temizle ve çıktıyı gönder

    exit();
}

$db = new Database();

// Tarih filtresi
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Aktif ödemeler için istatistikler
$payment_stats = $db->query(
    "SELECT 
        payment_method,
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_amount
     FROM payments 
     WHERE DATE(created_at) BETWEEN ? AND ?
     AND status = 'completed'
     GROUP BY payment_method",
    [$start_date, $end_date]
)->fetchAll();

// İptal edilen ödemeler için istatistikler
$cancelled_stats = $db->query(
    "SELECT 
        p.payment_method,
        p.created_at,
        p.total_amount,
        p.payment_note,
        o.table_id,
        COUNT(*) as total_transactions
     FROM payments p
     LEFT JOIN orders o ON p.id = o.payment_id
     WHERE DATE(p.created_at) BETWEEN ? AND ?
     AND p.status = 'cancelled'
     GROUP BY p.id, p.payment_method, p.created_at, p.total_amount, p.payment_note, o.table_id",
    [$start_date, $end_date]
)->fetchAll();

// İptal edilen toplam tutarı hesapla
$total_cancelled = 0;
foreach ($cancelled_stats as $stat) {
    $total_cancelled += $stat['total_amount'];
}

// Saatlik satış grafiği için veriler
$hourly_sales = $db->query(
    "SELECT 
        DATE_FORMAT(created_at, '%H:00') as hour,
        payment_method,
        SUM(total_amount) as total_amount
     FROM payments
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(created_at, '%H'), payment_method
     ORDER BY hour",
    [$start_date, $end_date]
)->fetchAll();

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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finansal Raporlar - QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Finansal Raporlar</h2>
            <form class="d-flex gap-2">
                <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </form>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <?php
            $total_revenue = 0;
            $total_transactions = 0;
            foreach ($payment_stats as $stat) {
                $total_revenue += $stat['total_amount'];
                $total_transactions += $stat['total_transactions'];
            }
            ?>
            <div class="col-md-3">
                <div class="stat-card card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Ciro</h6>
                        <h3 class="card-text"><?= number_format($total_revenue, 2) ?> ₺</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-danger text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">İptal Edilen Ödemeler</h6>
                        <h3 class="card-text"><?= number_format($total_cancelled, 2) ?> ₺</h3>
                        <small><?= array_sum(array_column($cancelled_stats, 'total_transactions')) ?> işlem</small>
                        <button class="btn btn-sm btn-outline-light mt-2" onclick="showCancelledPayments()">
                            Detayları Gör
                        </button>
                    </div>
                </div>
            </div>
            <?php foreach ($payment_stats as $stat): ?>
            <div class="col-md-3">
                <div class="stat-card card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?= getPaymentMethodText($stat['payment_method']) ?> Toplam</h6>
                        <h3 class="card-text"><?= number_format($stat['total_amount'], 2) ?> ₺</h3>
                        <small><?= $stat['total_transactions'] ?> işlem</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Grafikler -->
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5>Saatlik Satış Grafiği</h5>
                    <div id="hourlyChart"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5>Ödeme Türleri Dağılımı</h5>
                    <div id="paymentPieChart"></div>
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
                                <?php if (empty($cancelled_stats)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">İptal edilen ödeme bulunmuyor.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($cancelled_stats as $stat): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i', strtotime($stat['created_at'])) ?></td>
                                        <td><?= $stat['table_id'] ? 'Masa ' . $stat['table_id'] : '-' ?></td>
                                        <td><?= getPaymentMethodText($stat['payment_method']) ?></td>
                                        <td><?= number_format($stat['total_amount'], 2) ?> ₺</td>
                                        <td><?= $stat['payment_note'] ?? '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function showCancelledPayments() {
            new bootstrap.Modal(document.getElementById('cancelledPaymentsModal')).show();
        }

        // Saatlik satış grafiği
        const hourlyData = <?= json_encode($hourly_sales) ?>;
        const hours = [...new Set(hourlyData.map(item => item.hour))];
        const paymentTypes = [...new Set(hourlyData.map(item => item.payment_method))];
        
        const series = paymentTypes.map(type => ({
            name: type,
            data: hours.map(hour => {
                const entry = hourlyData.find(item => item.hour === hour && item.payment_method === type);
                return entry ? parseFloat(entry.total_amount) : 0;
            })
        }));

        new ApexCharts(document.querySelector("#hourlyChart"), {
            series: series,
            chart: {
                type: 'area',
                height: 350,
                stacked: true
            },
            xaxis: {
                categories: hours
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toFixed(2) + ' ₺';
                    }
                }
            },
            colors: ['#0d6efd', '#198754'],
            fill: {
                opacity: 0.8
            }
        }).render();

        // Ödeme türleri pasta grafiği
        const paymentStats = <?= json_encode($payment_stats) ?>;
        new ApexCharts(document.querySelector("#paymentPieChart"), {
            series: paymentStats.map(stat => parseFloat(stat.total_amount)),
            chart: {
                type: 'pie',
                height: 350
            },
            labels: paymentStats.map(stat => stat.payment_method),
            colors: ['#0d6efd', '#198754']
        }).render();
    </script>
</body>
</html> 