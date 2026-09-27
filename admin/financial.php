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

// ---------------------------------------------------------------------------
// Tarih filtresi
// ---------------------------------------------------------------------------
// Önceden $_GET değerleri doğrudan DATE(created_at) BETWEEN ? AND ? içine
// konduğu kadar form input'larının value="" özniteliğine de kaçışsız basılıyordu
// (?start_date="><script>... reflected XSS). Artık önce biçim doğrulanıyor,
// sonra her yerde htmlspecialchars() ile basılıyor.
$start_date = (string)($_GET['start_date'] ?? '');
$end_date   = (string)($_GET['end_date'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date = date('Y-m-d');
if ($start_date > $end_date) { [$start_date, $end_date] = [$end_date, $start_date]; }

// Ciro hesabı kanonik katmandan gelir: masa + POS + web kanalları tek yerde
// toplanır, hiçbir kanal iki kez sayılmaz. Önceden bu sayfa yalnız
// `payments` tablosunu okuyordu, bu yüzden web/online cirosu hiç görünmüyordu.
require_once '../includes/sales.php';
$from = $start_date . ' 00:00:00';
$to   = $end_date . ' 23:59:59';

$salesSummary = sales_summary($db, ['channels' => sales_channel_keys(), 'from' => $from, 'to' => $to]);
$salesMethods = sales_method_breakdown($db, ['channels' => sales_channel_keys(), 'from' => $from, 'to' => $to]);
$salesTotal   = $salesSummary['totals'];

// Ödeme yöntemi kırılımı: kanonik sözlüğü kullanır, grup bazında döner
$payment_stats = [];
foreach ($salesMethods as $m) {
    $payment_stats[] = [
        'payment_method'      => $m['label'],   // etiket artık kanonik
        'raw_method'          => $m['method'],
        'total_transactions'  => $m['count'],
        'total_amount'        => $m['total'],
    ];
}

// Kanal bazlı ciro tablosu
$channel_rows = [];
foreach ($salesSummary['channels'] as $key => $c) {
    $channel_rows[] = [
        'channel'   => $key,
        'label'     => $c['label'],
        'icon'      => $c['icon'],
        'count'     => $c['sale_count'],
        'revenue'   => $c['revenue'],
        'collected' => $c['collected'],
        'basket'    => $c['avg_basket'],
    ];
}

// İptal edilen ödemeler (ciroya girmez, yalnızca raporlanır)
$cancelled_stats = $db->query(
    "SELECT 
        p.payment_method,
        p.created_at,
        p.total_amount,
        p.payment_note,
        p.table_id,
        COUNT(*) as total_transactions
     FROM payments p
     WHERE DATE(p.created_at) BETWEEN ? AND ?
     AND p.status = 'cancelled'
     GROUP BY p.id, p.payment_method, p.created_at, p.total_amount, p.payment_note, p.table_id",
    [$start_date, $end_date]
)->fetchAll(PDO::FETCH_ASSOC);

// İptal edilen toplam tutarı hesapla
$total_cancelled = 0;
foreach ($cancelled_stats as $stat) {
    $total_cancelled += (float)$stat['total_amount'];
}

// Saatlik satış grafiği.
// ÖNEMLİ: Bu sorguda önceden `status` filtresi YOKTU; iptal edilen ödemeler
// de grafiğe giriyordu ve saatlik ciro olduğundan yüksek görünüyordu.
// Ayrıca web/online cirosu hiç dahil edilmiyordu.
$hourly_sales = $db->query(
    "SELECT 
        DATE_FORMAT(created_at, '%H:00') as hour,
        payment_method,
        SUM(total_amount) as total_amount
     FROM payments
     WHERE DATE(created_at) BETWEEN ? AND ?
     AND status = 'completed'
     GROUP BY DATE_FORMAT(created_at, '%H'), payment_method
     ORDER BY hour",
    [$start_date, $end_date]
)->fetchAll(PDO::FETCH_ASSOC);

/**
 * Ödeme yöntemi etiketi — kanonik sözlüğe yönlendirir.
 * Önceden 'credit_card'/'debit_card' gibi bu şemada hiç bulunmayan değerler
 * aranıyordu ve eşleşmeyen her şey ham biçimde döndürülüyordu.
 */
function getPaymentMethodText($method) {
    return sales_method_label(sales_method_group($method));
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
                <input type="date" class="form-control" name="start_date"
                       value="<?= htmlspecialchars($start_date) ?>">
                <input type="date" class="form-control" name="end_date"
                       value="<?= htmlspecialchars($end_date) ?>">
                <button type="submit" class="btn btn-primary">Filtrele</button>
            </form>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Ciro</h6>
                        <h3 class="card-text"><?= number_format($salesTotal['revenue'], 2, ',', '.') ?> ₺</h3>
                        <small><?= (int)$salesTotal['sale_count'] ?> işlem</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Tahsilat</h6>
                        <h3 class="card-text"><?= number_format($salesTotal['collected'], 2, ',', '.') ?> ₺</h3>
                        <small>ort. sepet <?= number_format($salesTotal['avg_basket'], 2, ',', '.') ?> ₺</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-danger text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">İptal Edilen Ödemeler</h6>
                        <h3 class="card-text"><?= number_format($total_cancelled, 2, ',', '.') ?> ₺</h3>
                        <small><?= array_sum(array_column($cancelled_stats, 'total_transactions')) ?> işlem</small>
                        <button class="btn btn-sm btn-outline-light mt-2" onclick="showCancelledPayments()">
                            Detayları Gör
                        </button>
                    </div>
                </div>
            </div>
            <?php foreach ($channel_rows as $ch): ?>
                <?php if ($ch['revenue'] > 0 || $ch['count'] > 0): ?>
            <div class="col-md-3">
                <div class="stat-card card bg-secondary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas <?= htmlspecialchars($ch['icon']) ?> me-1"></i><?= htmlspecialchars($ch['label']) ?></h6>
                        <h3 class="card-text"><?= number_format($ch['revenue'], 2, ',', '.') ?> ₺</h3>
                        <small><?= (int)$ch['count'] ?> işlem</small>
                    </div>
                </div>
            </div>
                <?php endif; ?>
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
                                        <td><?= $stat['table_id'] ? 'Masa ' . (int)$stat['table_id'] : ($stat['payment_method'] === 'pos' ? 'POS' : '-') ?></td>
                                        <td><?= getPaymentMethodText($stat['payment_method']) ?></td>
                                        <td><?= number_format($stat['total_amount'], 2, ',', '.') ?> ₺</td>
                                        <td><?= htmlspecialchars((string)($stat['payment_note'] ?? '')) ?: '-' ?></td>
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
    <?php
    // JS verisi hazırlığı (script bloğunun DIŞINDA olmalı, aksi halde PHP
    // satırları düz metin olarak tarayıcıya gönderilir).
    $hourly_js = [];
    foreach ($hourly_sales as $hr) {
        $hourly_js[] = [
            'hour'   => $hr['hour'],
            'method' => getPaymentMethodText($hr['payment_method']),
            'amount' => (float)$hr['total_amount'],
        ];
    }
    // </script> içinde </script> ya da yorum dizisi üretmemek için
    // JSON_HEX_* bayrakları kullanılır (XSS önlemi).
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
    ?>
    <script>
        function showCancelledPayments() {
            new bootstrap.Modal(document.getElementById('cancelledPaymentsModal')).show();
        }

        // Saatlik satış grafiği.
        // Not: bu grafik yalnız `payments` tablosunu okur, yani masa + POS
        // kanallarını kapsar; teslimat/web cirosu saatlik dağılımda yer almaz.
        // Etiketler kanonik sözlükten gelir, ham enum değeri JS'e sızmaz.
        const hourlyData = <?= json_encode($hourly_js, $jsonFlags) ?>;
        const hours = [...new Set(hourlyData.map(item => item.hour))];
        const paymentTypes = [...new Set(hourlyData.map(item => item.method))];

        const series = paymentTypes.map(type => ({
            name: type,
            data: hours.map(hour => {
                const entry = hourlyData.find(item => item.hour === hour && item.method === type);
                return entry ? entry.amount : 0;
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
        const paymentStats = <?= json_encode($payment_stats, $jsonFlags) ?>;
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