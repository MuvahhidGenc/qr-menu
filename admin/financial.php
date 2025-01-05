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

// Ödeme türlerine göre toplam satışlar
$payment_stats = $db->query(
    "SELECT 
        payment_method,
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_amount
     FROM payments 
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY payment_method",
    [$start_date, $end_date]
)->fetchAll();

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
                <div class="stat-card card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Toplam İşlem</h6>
                        <h3 class="card-text"><?= $total_transactions ?></h3>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
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