<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Composer autoload kontrolü
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require $autoloadFile;
}

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

// Filtreler - Saat destekli
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d\T00:00', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d\T23:59');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'all';
$export_format = isset($_GET['export']) ? $_GET['export'] : null;

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

// Genel İstatistikler
$general_stats = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN o.status IN ('pending', 'preparing', 'ready', 'delivered') AND (p.status IS NULL OR p.status = 'pending') THEN 1 END) as active_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as completed_revenue,
        COALESCE(SUM(CASE WHEN o.status IN ('pending', 'preparing', 'ready', 'delivered') AND (p.status IS NULL OR p.status = 'pending') THEN o.total_amount ELSE 0 END), 0) as active_revenue,
        COALESCE(SUM(CASE WHEN p.status = 'cancelled' THEN o.total_amount ELSE 0 END), 0) as total_cancelled_amount,
        COUNT(CASE WHEN p.status = 'cancelled' THEN 1 END) as total_cancelled_orders
    FROM orders o
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE o.created_at BETWEEN ? AND ?",
    [$start_date, $end_date]
)->fetch();

// İndirim ve Gelir İstatistikleri
$discount_revenue_stats = $db->query("
    SELECT 
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.discount_amount ELSE 0 END), 0) as total_discount,
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.paid_amount ELSE 0 END), 0) as actual_revenue,
        COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.subtotal ELSE 0 END), 0) as gross_revenue,
        COUNT(CASE WHEN p.status = 'completed' AND p.discount_amount > 0 THEN 1 END) as discounted_orders,
        COALESCE(AVG(CASE WHEN p.status = 'completed' AND p.discount_amount > 0 THEN p.discount_amount END), 0) as avg_discount_amount,
        COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.payment_method = 'cash' THEN p.paid_amount ELSE 0 END), 0) as cash_revenue,
        COALESCE(SUM(CASE WHEN p.status = 'completed' AND p.payment_method = 'pos' THEN p.paid_amount ELSE 0 END), 0) as card_revenue
    FROM payments p
    WHERE p.created_at BETWEEN ? AND ?",
    [$start_date, $end_date]
)->fetch();

// İndirim Analizi
$discount_analysis = $db->query("
    SELECT 
        DATE(p.created_at) as date,
        COUNT(CASE WHEN p.discount_amount > 0 THEN 1 END) as discounted_count,
        COALESCE(SUM(p.discount_amount), 0) as total_discount,
        COALESCE(SUM(CASE WHEN p.discount_type = 'percent' THEN p.discount_amount ELSE 0 END), 0) as percent_discount,
        COALESCE(SUM(CASE WHEN p.discount_type = 'amount' THEN p.discount_amount ELSE 0 END), 0) as amount_discount,
        COALESCE(AVG(CASE WHEN p.discount_amount > 0 THEN p.discount_amount END), 0) as avg_discount,
        COALESCE(SUM(p.subtotal), 0) as gross_revenue,
        COALESCE(SUM(p.paid_amount), 0) as net_revenue
    FROM payments p
    WHERE p.status = 'completed' 
    AND p.created_at BETWEEN ? AND ?
    GROUP BY DATE(p.created_at)
    ORDER BY date DESC",
    [$start_date, $end_date]
)->fetchAll();

// En Çok Satan Ürünler
$top_products = $db->query("
    SELECT 
        p.name as product_name,
        c.name as category_name,
        COUNT(oi.id) as order_count,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price) as total_revenue,
        AVG(oi.price) as avg_price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id
    LEFT JOIN payments pay ON o.payment_id = pay.id
    WHERE pay.status = 'completed' 
    AND o.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.name, c.name
    ORDER BY total_revenue DESC
    LIMIT 10",
    [$start_date, $end_date]
)->fetchAll();

// Günlük satış grafiği için veri
$daily_sales = $db->query("
    SELECT 
        DATE(o.created_at) as sale_date,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN p.total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN p.payment_method = 'cash' AND p.status = 'completed' THEN p.total_amount ELSE 0 END), 0) as cash_revenue,
        COALESCE(SUM(CASE WHEN p.payment_method = 'pos' AND p.status = 'completed' THEN p.total_amount ELSE 0 END), 0) as card_revenue
    FROM orders o
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY sale_date",
    [$start_date, $end_date]
)->fetchAll();

// Saatlik Analiz
$hourly_analysis = $db->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value
    FROM orders
    WHERE status = 'completed'
    AND created_at BETWEEN ? AND ?
    GROUP BY HOUR(created_at)
    ORDER BY hour",
    [$start_date, $end_date]
)->fetchAll();

// İptal Analizi
$cancellation_analysis = $db->query("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(*) as cancelled_count,
        SUM(o.total_amount) as cancelled_amount,
        COUNT(DISTINCT o.id) as total_cancelled_orders,
        COALESCE(SUM(CASE 
            WHEN p.payment_method = 'cash' THEN o.total_amount 
            ELSE 0 
        END), 0) as cancelled_cash_amount,
        COALESCE(SUM(CASE 
            WHEN p.payment_method = 'pos' THEN o.total_amount 
            ELSE 0 
        END), 0) as cancelled_card_amount
    FROM orders o
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE p.status = 'cancelled'
    AND o.created_at BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date DESC",
    [$start_date, $end_date]
)->fetchAll();

// İptal Edilen Ürünler
$cancelled_items = $db->query("
    SELECT 
        p.name as product_name,
        c.name as category_name,
        COUNT(oi.id) as cancel_count,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price) as total_amount,
        GROUP_CONCAT(DISTINCT t.table_no) as table_numbers
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN payments pay ON o.payment_id = pay.id
    JOIN tables t ON o.table_id = t.id
    WHERE pay.status = 'cancelled'
    AND o.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.name, c.name
    ORDER BY cancel_count DESC",
    [$start_date, $end_date]
)->fetchAll();

// Personel Performansı
$staff_performance = $db->query("
    SELECT 
        a.name as staff_name,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN p.total_amount ELSE 0 END) as total_revenue,
        AVG(CASE WHEN o.status = 'completed' AND p.status = 'completed' THEN p.total_amount ELSE NULL END) as avg_order_value,
        COUNT(DISTINCT o.table_id) as tables_served,
        COALESCE(SUM(CASE 
            WHEN p.payment_method = 'cash' AND p.status = 'completed' THEN p.total_amount 
            ELSE 0 
        END), 0) as cash_revenue,
        COALESCE(SUM(CASE 
            WHEN p.payment_method = 'pos' AND p.status = 'completed' THEN p.total_amount 
            ELSE 0 
        END), 0) as card_revenue,
        COUNT(DISTINCT DATE(o.created_at)) as working_days
    FROM admins a
    LEFT JOIN orders o ON o.id IN (
        SELECT order_id 
        FROM notifications 
        WHERE type = 'new_order' 
        AND message LIKE CONCAT('%', a.name, '%')
    )
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE a.role_id != 1  -- Sistem yöneticisini hariç tut
    AND o.created_at BETWEEN ? AND ?
    GROUP BY a.id, a.name
    ORDER BY total_revenue DESC",
    [$start_date, $end_date]
)->fetchAll();

// Masa Analizi
$table_analysis = $db->query("
    SELECT 
        t.table_no,
        COUNT(o.id) as total_orders,
        SUM(o.total_amount) as total_revenue,
        AVG(o.total_amount) as avg_order_value,
        AVG(TIMESTAMPDIFF(MINUTE, o.created_at, o.completed_at)) as avg_duration,
        COALESCE(SUM(CASE WHEN p.payment_method = 'cash' AND p.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as cash_revenue,
        COALESCE(SUM(CASE WHEN p.payment_method = 'pos' AND p.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as card_revenue
    FROM tables t
    LEFT JOIN orders o ON o.table_id = t.id
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY t.id, t.table_no
    ORDER BY total_revenue DESC",
    [$start_date, $end_date]
)->fetchAll();

// Tamamlanan Siparişler
$completed_orders = $db->query("
    SELECT o.*, t.table_no 
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE o.status = 'completed' 
    AND p.status = 'completed'
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC",
    [$start_date, $end_date]
)->fetchAll();

// İptal Edilen Siparişler
$cancelled_orders = $db->query("
    SELECT o.*, t.table_no 
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE p.status = 'cancelled'
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC",
    [$start_date, $end_date]
)->fetchAll();

// Aktif Siparişler
$active_orders = $db->query("
    SELECT o.*, t.table_no 
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN payments p ON o.payment_id = p.id
    WHERE o.status IN ('pending', 'preparing', 'ready', 'delivered') 
    AND (p.status IS NULL OR p.status = 'pending')
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC",
    [$start_date, $end_date]
)->fetchAll();

// Export işlemleri
if ($export_format && file_exists($autoloadFile)) {
    if ($export_format === 'excel') {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Excel export kodları...
    } elseif ($export_format === 'pdf') {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // PDF export kodları...
    }
}

// Sipariş detayları için yeni sorgu fonksiyonunu ekleyelim (PHP kısmına)
function getOrderDetails($order_id) {
    global $db;
    
    // Sipariş ve masa bilgileri
    $order = $db->query("
        SELECT o.*, t.table_no, p.payment_method, p.status as payment_status 
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        LEFT JOIN payments p ON o.payment_id = p.id
        WHERE o.id = ?", 
        [$order_id]
    )->fetch();

    if (!$order) {
        return null;
    }

    // Sipariş ürünleri
    $order_items = $db->query("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?",
        [$order_id]
    )->fetchAll();

    return [
        'order' => $order,
        'order_items' => $order_items
    ];
}

// AJAX isteğini kontrol et
if (isset($_GET['action']) && $_GET['action'] == 'getOrderDetails' && isset($_GET['order_id'])) {
    $orderDetails = getOrderDetails($_GET['order_id']);
    echo json_encode($orderDetails);
    exit;
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Detaylı Raporlar</title>
    <!-- CSS dosyaları -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    
    <!-- JavaScript dosyaları -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables ve Butonlar için gerekli JS dosyaları -->
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.7.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.1.70/build/pdfmake.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.1.70/build/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.2.2/js/buttons.print.min.js"></script>
    <style>
 .nav-link i {
    display: inline !important;
}

    /* DataTables ve diğer stil tanımlamaları buraya gelebilir */
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>Başlangıç Tarihi ve Saati
                        </label>
                        <input type="datetime-local" name="start_date" class="form-control" value="<?= $start_date ?>">
                        <small class="form-text text-muted">Tarih ve saat seçiniz</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>Bitiş Tarihi ve Saati
                        </label>
                        <input type="datetime-local" name="end_date" class="form-control" value="<?= $end_date ?>">
                        <small class="form-text text-muted">Tarih ve saat seçiniz</small>
                    </div>
                    <div class="col-md-2">
                        <label>Rapor Tipi</label>
                        <select name="report_type" class="form-control">
                            <option value="all">Tümü</option>
                            <option value="sales">Satışlar</option>
                            <option value="products">Ürünler</option>
                            <option value="staff">Personel</option>
                            <option value="tables">Masalar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <div class="btn-group w-100">
                            <button type="submit" name="export" value="excel" class="btn btn-success">Excel</button>
                            <button type="submit" name="export" value="pdf" class="btn btn-danger">PDF</button>
                        </div>
                    </div>
                </form>
                
                <!-- Hızlı Zaman Seçimi -->
                <div class="mt-3">
                    <small class="text-muted me-2">Hızlı Seçim:</small>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('today')">Bugün</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('yesterday')">Dün</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('thisWeek')">Bu Hafta</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('lastWeek')">Geçen Hafta</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('thisMonth')">Bu Ay</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setTimeRange('lastMonth')">Geçen Ay</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Özet Kartları -->
        <div class="row g-4 mb-4">
            <!-- Toplam Gelir Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Net Gelir</h6>
                                <h3 class="card-title mb-0"><?= number_format($discount_revenue_stats['actual_revenue'], 2) ?> ₺</h3>
                                <a href="#" class="text-white" data-bs-toggle="modal" data-bs-target="#ordersModal">
                                    Gerçek Ödenen
                                </a>
                            </div>
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Brüt Gelir Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Brüt Gelir</h6>
                                <h3 class="card-title mb-0"><?= number_format($discount_revenue_stats['gross_revenue'], 2) ?> ₺</h3>
                                <small>İndirim Öncesi</small>
                            </div>
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Toplam İndirim Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Toplam İndirim</h6>
                                <h3 class="card-title mb-0"><?= number_format($discount_revenue_stats['total_discount'], 2) ?> ₺</h3>
                                <small><?= number_format($discount_revenue_stats['discounted_orders']) ?> Sipariş</small>
                            </div>
                            <i class="fas fa-percentage fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tamamlanan Siparişler Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Tamamlanan</h6>
                                <h3 class="card-title mb-0"><?= number_format($general_stats['completed_orders']) ?></h3>
                                <small><?= number_format($general_stats['completed_revenue'], 2) ?> ₺</small>
                            </div>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- İptal Edilen Siparişler Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">İptal Edilen</h6>
                                <h3 class="card-title mb-0"><?= number_format($general_stats['total_cancelled_orders']) ?></h3>
                                <small><?= number_format($general_stats['total_cancelled_amount'], 2) ?> ₺</small>
                            </div>
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Aktif Siparişler Kartı -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Aktif</h6>
                                <h3 class="card-title mb-0"><?= number_format($general_stats['active_orders']) ?></h3>
                                <small><?= number_format($general_stats['active_revenue'], 2) ?> ₺</small>
                            </div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ödeme Yöntemi İstatistikleri -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="fas fa-money-bill me-2"></i>Nakit Ödemeler
                        </h6>
                        <h4><?= number_format($discount_revenue_stats['cash_revenue'], 2) ?> ₺</h4>
                        <small class="text-muted">
                            <?php 
                            $cashPercentage = $discount_revenue_stats['actual_revenue'] > 0 
                                ? ($discount_revenue_stats['cash_revenue'] / $discount_revenue_stats['actual_revenue']) * 100 
                                : 0;
                            echo number_format($cashPercentage, 1) . '% toplam gelirin';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="fas fa-credit-card me-2"></i>Kartlı Ödemeler
                        </h6>
                        <h4><?= number_format($discount_revenue_stats['card_revenue'], 2) ?> ₺</h4>
                        <small class="text-muted">
                            <?php 
                            $cardPercentage = $discount_revenue_stats['actual_revenue'] > 0 
                                ? ($discount_revenue_stats['card_revenue'] / $discount_revenue_stats['actual_revenue']) * 100 
                                : 0;
                            echo number_format($cardPercentage, 1) . '% toplam gelirin';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafikler -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line me-2"></i>Günlük Satış Grafiği
                        </h5>
                        <div style="height: 400px; position: relative;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Diğer grafikler -->
        </div>

        <!-- Detaylı Tablolar -->
        <div class="row g-4">
            <!-- İndirim Analizi -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-percentage me-2"></i>İndirim Analizi
                            <small class="text-muted ms-2">Günlük indirim detayları</small>
                        </h5>
                        <?php if (empty($discount_analysis)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Seçilen tarih aralığında indirim verisi bulunamadı.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="discountTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tarih</th>
                                            <th>İndirimli Sipariş</th>
                                            <th>Toplam İndirim</th>
                                            <th>Yüzde İndirim</th>
                                            <th>Tutar İndirim</th>
                                            <th>Ortalama İndirim</th>
                                            <th>Brüt Gelir</th>
                                            <th>Net Gelir</th>
                                            <th>İndirim Oranı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($discount_analysis as $discount): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($discount['date'])) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?= number_format($discount['discounted_count']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-danger">
                                                    -<?= number_format($discount['total_discount'], 2) ?> ₺
                                                </strong>
                                            </td>
                                            <td><?= number_format($discount['percent_discount'], 2) ?> ₺</td>
                                            <td><?= number_format($discount['amount_discount'], 2) ?> ₺</td>
                                            <td><?= number_format($discount['avg_discount'], 2) ?> ₺</td>
                                            <td><?= number_format($discount['gross_revenue'], 2) ?> ₺</td>
                                            <td>
                                                <strong class="text-success">
                                                    <?= number_format($discount['net_revenue'], 2) ?> ₺
                                                </strong>
                                            </td>
                                            <td>
                                                <?php 
                                                $discountRate = $discount['gross_revenue'] > 0 
                                                    ? ($discount['total_discount'] / $discount['gross_revenue']) * 100 
                                                    : 0;
                                                ?>
                                                <span class="badge bg-<?= $discountRate > 15 ? 'danger' : ($discountRate > 10 ? 'warning' : 'success') ?>">
                                                    <?= number_format($discountRate, 1) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th>Toplam</th>
                                            <th><?= number_format(array_sum(array_column($discount_analysis, 'discounted_count'))) ?></th>
                                            <th>
                                                <strong class="text-danger">
                                                    -<?= number_format(array_sum(array_column($discount_analysis, 'total_discount')), 2) ?> ₺
                                                </strong>
                                            </th>
                                            <th><?= number_format(array_sum(array_column($discount_analysis, 'percent_discount')), 2) ?> ₺</th>
                                            <th><?= number_format(array_sum(array_column($discount_analysis, 'amount_discount')), 2) ?> ₺</th>
                                            <th>
                                                <?php 
                                                $totalDiscountCount = array_sum(array_column($discount_analysis, 'discounted_count'));
                                                $avgTotalDiscount = $totalDiscountCount > 0 
                                                    ? array_sum(array_column($discount_analysis, 'total_discount')) / $totalDiscountCount 
                                                    : 0;
                                                echo number_format($avgTotalDiscount, 2);
                                                ?> ₺
                                            </th>
                                            <th><?= number_format(array_sum(array_column($discount_analysis, 'gross_revenue')), 2) ?> ₺</th>
                                            <th>
                                                <strong class="text-success">
                                                    <?= number_format(array_sum(array_column($discount_analysis, 'net_revenue')), 2) ?> ₺
                                                </strong>
                                            </th>
                                            <th>
                                                <?php 
                                                $totalGrossRevenue = array_sum(array_column($discount_analysis, 'gross_revenue'));
                                                $totalDiscountAmount = array_sum(array_column($discount_analysis, 'total_discount'));
                                                $overallDiscountRate = $totalGrossRevenue > 0 
                                                    ? ($totalDiscountAmount / $totalGrossRevenue) * 100 
                                                    : 0;
                                                ?>
                                                <span class="badge bg-<?= $overallDiscountRate > 15 ? 'danger' : ($overallDiscountRate > 10 ? 'warning' : 'success') ?>">
                                                    <?= number_format($overallDiscountRate, 1) ?>%
                                                </span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Ürün Analizi -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-crown me-2"></i>En Çok Satan Ürünler
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Kategori</th>
                                        <th>Sipariş Sayısı</th>
                                        <th>Toplam Adet</th>
                                        <th>Toplam Gelir</th>
                                        <th>Ort. Fiyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($top_products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><?= htmlspecialchars($product['category_name']) ?></td>
                                        <td><?= number_format($product['order_count']) ?></td>
                                        <td><?= number_format($product['total_quantity']) ?></td>
                                        <td><?= number_format($product['total_revenue'], 2) ?> ₺</td>
                                        <td><?= number_format($product['avg_price'], 2) ?> ₺</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- İptal Analizi Tablosu -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">İptal Edilen Siparişler Analizi</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="cancellationTable">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>İptal Sayısı</th>
                                        <th>Toplam Tutar</th>
                                        <th>Nakit İptal</th>
                                        <th>Kart İptal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($cancellation_analysis as $cancel): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($cancel['date'])) ?></td>
                                        <td><?= number_format($cancel['cancelled_count']) ?></td>
                                        <td><?= number_format($cancel['cancelled_amount'], 2) ?> ₺</td>
                                        <td><?= number_format($cancel['cancelled_cash_amount'], 2) ?> ₺</td>
                                        <td><?= number_format($cancel['cancelled_card_amount'], 2) ?> ₺</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th>Toplam</th>
                                        <th><?= number_format(array_sum(array_column($cancellation_analysis, 'cancelled_count'))) ?></th>
                                        <th><?= number_format(array_sum(array_column($cancellation_analysis, 'cancelled_amount')), 2) ?> ₺</th>
                                        <th><?= number_format(array_sum(array_column($cancellation_analysis, 'cancelled_cash_amount')), 2) ?> ₺</th>
                                        <th><?= number_format(array_sum(array_column($cancellation_analysis, 'cancelled_card_amount')), 2) ?> ₺</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- İptal Edilen Ürünler -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">İptal Edilen Ürünler</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="cancelledItemsTable">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Kategori</th>
                                        <th>İptal Sayısı</th>
                                        <th>Toplam Adet</th>
                                        <th>Toplam Tutar</th>
                                        <th>Masa Numaraları</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($cancelled_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td><?= number_format($item['cancel_count']) ?></td>
                                        <td><?= number_format($item['total_quantity']) ?></td>
                                        <td><?= number_format($item['total_amount'], 2) ?> ₺</td>
                                        <td><?= htmlspecialchars($item['table_numbers']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th colspan="2">Toplam</th>
                                        <th><?= number_format(array_sum(array_column($cancelled_items, 'cancel_count'))) ?></th>
                                        <th><?= number_format(array_sum(array_column($cancelled_items, 'total_quantity'))) ?></th>
                                        <th><?= number_format(array_sum(array_column($cancelled_items, 'total_amount')), 2) ?> ₺</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Personel Performans Tablosu -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Personel Performans Analizi</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="staffTable">
                                <thead>
                                    <tr>
                                        <th>Personel</th>
                                        <th>Sipariş Sayısı</th>
                                        <th>Toplam Gelir</th>
                                        <th>Nakit Gelir</th>
                                        <th>Kart Gelir</th>
                                        <th>Ort. Sipariş</th>
                                        <th>Masa Sayısı</th>
                                        <th>Çalışma Günü</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($staff_performance as $staff): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($staff['staff_name']) ?></td>
                                        <td><?= number_format($staff['total_orders']) ?></td>
                                        <td><?= number_format($staff['total_revenue'], 2) ?> ₺</td>
                                        <td><?= number_format($staff['cash_revenue'], 2) ?> ₺</td>
                                        <td><?= number_format($staff['card_revenue'], 2) ?> ₺</td>
                                        <td><?= number_format($staff['avg_order_value'], 2) ?> ₺</td>
                                        <td><?= number_format($staff['tables_served']) ?></td>
                                        <td><?= number_format($staff['working_days']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th>Toplam</th>
                                        <th><?= number_format(array_sum(array_column($staff_performance, 'total_orders'))) ?></th>
                                        <th><?= number_format(array_sum(array_column($staff_performance, 'total_revenue')), 2) ?> ₺</th>
                                        <th><?= number_format(array_sum(array_column($staff_performance, 'cash_revenue')), 2) ?> ₺</th>
                                        <th><?= number_format(array_sum(array_column($staff_performance, 'card_revenue')), 2) ?> ₺</th>
                                        <th><?= count($staff_performance) > 0 ? number_format(array_sum(array_column($staff_performance, 'total_revenue')) / count($staff_performance), 2) : '0.00' ?> ₺</th>
                                        <th><?= number_format(array_sum(array_column($staff_performance, 'tables_served'))) ?></th>
                                        <th>-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparişler Modal -->
    <div class="modal fade" id="ordersModal" tabindex="-1" data-bs-focus="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sipariş Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#completed">Tamamlanan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#cancelled">İptal Edilen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#active">Aktif</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="completed">
                            <div class="table-responsive">
                                <table class="table" id="completedOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Sipariş No</th>
                                            <th>Masa</th>
                                            <th>Tutar</th>
                                            <th>Tarih</th>
                                            <th>Detay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($completed_orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td>Masa <?= $order['table_no'] ?></td>
                                            <td><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="cancelled">
                            <div class="table-responsive">
                                <table class="table" id="cancelledOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Sipariş No</th>
                                            <th>Masa</th>
                                            <th>Tutar</th>
                                            <th>Tarih</th>
                                            <th>Detay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($cancelled_orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td>Masa <?= $order['table_no'] ?></td>
                                            <td><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="active">
                            <div class="table-responsive">
                                <table class="table" id="activeOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>Sipariş No</th>
                                            <th>Masa</th>
                                            <th>Tutar</th>
                                            <th>Tarih</th>
                                            <th>Detay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($active_orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td>Masa <?= $order['table_no'] ?></td>
                                            <td><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
        </div>
    </div>

    <!-- Sipariş Detayları Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" data-bs-focus="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sipariş Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-3">
                            <tbody id="orderInfo">
                                <!-- Sipariş bilgileri buraya gelecek -->
                            </tbody>
                        </table>

                        <h6>Sipariş Ürünleri</h6>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Adet</th>
                                    <th>Birim Fiyat</th>
                                    <th>Toplam</th>
                                </tr>
                            </thead>
                            <tbody id="orderItems">
                                <!-- Ürün listesi buraya gelecek -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Toplam:</th>
                                    <th id="orderTotal">0.00 ₺</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // DataTables Türkçe dil tanımlaması
    const dataTablesTurkish = {
        "emptyTable": "Tabloda herhangi bir veri mevcut değil",
        "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
        "infoEmpty": "Kayıt yok",
        "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
        "infoThousands": ".",
        "lengthMenu": "Sayfada _MENU_ kayıt göster",
        "loadingRecords": "Yükleniyor...",
        "processing": "İşleniyor...",
        "search": "Ara:",
        "zeroRecords": "Eşleşen kayıt bulunamadı",
        "paginate": {
            "first": "İlk",
            "last": "Son",
            "next": "Sonraki",
            "previous": "Önceki"
        },
        "aria": {
            "sortAscending": ": artan sütun sıralamasını aktifleştir",
            "sortDescending": ": azalan sütun sıralamasını aktifleştir"
        },
        "buttons": {
            "excel": "Excel",
            "pdf": "PDF",
            "print": "Yazdır",
            "collection": "Koleksiyon <span class=\"ui-button-icon-primary ui-icon ui-icon-triangle-1-s\"></span>",
            "colvis": "Sütun Görünürlüğü",
            "copy": "Kopyala",
            "copyKeys": "Tablodaki veriyi kopyalamak için CTRL veya u2318 + C tuşlarına basınız.",
            "copySuccess": {
                "1": "1 satır panoya kopyalandı",
                "_": "%d satır panoya kopyalandı"
            },
            "copyTitle": "Panoya Kopyala",
            "csv": "CSV",
            "selectAll": "Tümünü Seç",
            "selectNone": "Seçimi Kaldır"
        }
    };

    // DataTables için ortak buton konfigürasyonu
    const buttonConfig = {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: function() {
                    return this.title || 'Rapor';
                },
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: function() {
                    return this.title || 'Rapor';
                },
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.title.fontSize = 14;
                    doc.content[0].text = doc.content[0].text.trim();
                    doc.pageMargins = [20, 20, 20, 20];
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Yazdır',
                className: 'btn btn-primary btn-sm',
                title: function() {
                    return this.title || 'Rapor';
                },
                exportOptions: {
                    columns: ':visible'
                }
            }
        ]
    };

    // Tablolar için DataTables inicializasyonu
    $('#discountTable').DataTable({
        ...buttonConfig,
        language: dataTablesTurkish,
        pageLength: 25,
        order: [[0, 'desc']],
        responsive: true,
        title: 'İndirim Analizi Raporu'
    });

    $('#productsTable').DataTable({
        ...buttonConfig,
        language: dataTablesTurkish,
        pageLength: 25,
        order: [[4, 'desc']],
        responsive: true,
        title: 'En Çok Satan Ürünler Raporu'
    });

    $('#cancellationTable').DataTable({
        ...buttonConfig,
        language: dataTablesTurkish,
        pageLength: 25,
        order: [[0, 'desc']],
        responsive: true,
        title: 'İptal Edilen Siparişler Analizi'
    });

    $('#cancelledItemsTable').DataTable({
        ...buttonConfig,
        language: dataTablesTurkish,
        pageLength: 25,
        order: [[2, 'desc']],
        responsive: true,
        title: 'İptal Edilen Ürünler Raporu'
    });

    $('#staffTable').DataTable({
        ...buttonConfig,
        language: dataTablesTurkish,
        pageLength: 25,
        order: [[2, 'desc']],
        responsive: true,
        title: 'Personel Performans Analizi'
    });

    function showOrderDetails(orderId) {
        // Bootstrap modal'ı göster - focustrap devre dışı
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'), {
            focus: false
        });
        modal.show();

        // Sipariş detaylarını getir
        $.ajax({
            url: 'reports.php', // URL'yi reports.php olarak değiştirdik
            type: 'GET',
            data: { 
                action: 'getOrderDetails',
                order_id: orderId
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (!data || !data.order) {
                        throw new Error('Sipariş bulunamadı');
                    }
                    
                    // Sipariş bilgilerini doldur
                    let orderInfo = `
                        <tr>
                            <th style="width: 25%">Sipariş No:</th>
                            <td style="width: 25%">#${data.order.id}</td>
                            <th style="width: 25%">Masa:</th>
                            <td style="width: 25%">Masa ${data.order.table_no}</td>
                        </tr>
                        <tr>
                            <th>Tarih:</th>
                            <td>${formatDate(data.order.created_at)}</td>
                            <th>Durum:</th>
                            <td>${getOrderStatus(data.order.status)}</td>
                        </tr>
                        <tr>
                            <th>Ödeme Yöntemi:</th>
                            <td>${data.order.payment_method ? getPaymentMethod(data.order.payment_method) : '-'}</td>
                            <th>Ödeme Durumu:</th>
                            <td>${data.order.payment_status ? getPaymentStatus(data.order.payment_status) : 'Bekliyor'}</td>
                        </tr>
                    `;
                    $('#orderInfo').html(orderInfo);

                    // Ürün listesini doldur
                    let itemsHtml = '';
                    let total = 0;

                    if (data.order_items && data.order_items.length > 0) {
                        data.order_items.forEach(item => {
                            const itemTotal = item.quantity * item.price;
                            total += itemTotal;
                            itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-end">${formatPrice(item.price)} ₺</td>
                                    <td class="text-end">${formatPrice(itemTotal)} ₺</td>
                                </tr>
                            `;
                        });
                    } else {
                        itemsHtml = '<tr><td colspan="4" class="text-center">Ürün bulunamadı</td></tr>';
                    }

                    $('#orderItems').html(itemsHtml);
                    $('#orderTotal').text(formatPrice(total) + ' ₺');
                } catch (e) {
                    console.error('Hata:', e);
                    $('#orderDetailsContent').html('<div class="alert alert-danger">Sipariş detayları yüklenirken bir hata oluştu: ' + e.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Hatası:', error);
                $('#orderDetailsContent').html('<div class="alert alert-danger">Sipariş detayları yüklenirken bir hata oluştu.</div>');
            }
        });
    }

    // Tarih formatı için yardımcı fonksiyon
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Yardımcı fonksiyonlar
    function getOrderStatus(status) {
        const statusMap = {
            'pending': 'Beklemede',
            'preparing': 'Hazırlanıyor',
            'ready': 'Hazır',
            'delivered': 'Teslim Edildi',
            'completed': 'Tamamlandı',
            'cancelled': 'İptal Edildi'
        };
        return statusMap[status] || status;
    }

    function getPaymentMethod(method) {
        const methodMap = {
            'cash': 'Nakit',
            'credit_card': 'Kredi Kartı',
            'debit_card': 'Banka Kartı',
            'pos': 'POS'
        };
        return methodMap[method] || method;
    }

    function getPaymentStatus(status) {
        const statusMap = {
            'pending': 'Bekliyor',
            'completed': 'Tamamlandı',
            'cancelled': 'İptal Edildi'
        };
        return statusMap[status] || status;
    }

    function formatPrice(price) {
        return Number(price).toLocaleString('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    $(document).ready(function() {
        // DataTables inicializasyonu
        $('#completedOrdersTable').DataTable({
            language: dataTablesTurkish,
            pageLength: 10,
            order: [[3, 'desc']]
        });

        // Yeni tablolar için DataTables inicializasyonu
        $('#cancelledOrdersTable, #activeOrdersTable').DataTable({
            language: dataTablesTurkish,
            pageLength: 10,
            order: [[3, 'desc']]
        });

        // Modal focus sorununu çöz
        $('#ordersModal').on('show.bs.modal', function () {
            // Modal açılırken focustrap'i devre dışı bırak
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(this);
                if (modal && modal._config) {
                    modal._config.focus = false;
                }
            }, 50);
        });

        // Günlük Satış Grafiği
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // PHP verilerini JavaScript'e aktar
        const dailySalesData = <?= json_encode($daily_sales) ?>;
        
        // Grafik verilerini hazırla
        const labels = dailySalesData.map(item => {
            const date = new Date(item.sale_date);
            return date.toLocaleDateString('tr-TR', { 
                day: '2-digit', 
                month: '2-digit' 
            });
        });
        
        const revenueData = dailySalesData.map(item => parseFloat(item.total_revenue));
        const orderCountData = dailySalesData.map(item => parseInt(item.total_orders));
        
        // Chart.js ile grafik oluştur
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Günlük Gelir (₺)',
                    data: revenueData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Sipariş Sayısı',
                    data: orderCountData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    yAxisID: 'y1',
                    type: 'bar',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Günlük Satış Performansı',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Günlük Gelir (₺)') {
                                    label += new Intl.NumberFormat('tr-TR', {
                                        style: 'currency',
                                        currency: 'TRY'
                                    }).format(context.parsed.y);
                                } else {
                                    label += context.parsed.y + ' adet';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Tarih'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Gelir (₺)',
                            color: 'rgb(75, 192, 192)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('tr-TR', {
                                    style: 'currency',
                                    currency: 'TRY'
                                }).format(value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Sipariş Sayısı',
                            color: 'rgb(255, 99, 132)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' adet';
                            }
                        }
                    }
                }
            }
        });

        // Hızlı zaman seçimi fonksiyonu
        window.setTimeRange = function(range) {
            const now = new Date();
            let startDate, endDate;

            switch(range) {
                case 'today':
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
                    break;
                case 'yesterday':
                    const yesterday = new Date(now);
                    yesterday.setDate(yesterday.getDate() - 1);
                    startDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 0, 0);
                    endDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59);
                    break;
                case 'thisWeek':
                    const startOfWeek = new Date(now);
                    startOfWeek.setDate(now.getDate() - now.getDay() + 1); // Pazartesi
                    startDate = new Date(startOfWeek.getFullYear(), startOfWeek.getMonth(), startOfWeek.getDate(), 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
                    break;
                case 'lastWeek':
                    const lastWeekStart = new Date(now);
                    lastWeekStart.setDate(now.getDate() - now.getDay() - 6); // Geçen hafta pazartesi
                    const lastWeekEnd = new Date(lastWeekStart);
                    lastWeekEnd.setDate(lastWeekStart.getDate() + 6); // Geçen hafta pazar
                    startDate = new Date(lastWeekStart.getFullYear(), lastWeekStart.getMonth(), lastWeekStart.getDate(), 0, 0);
                    endDate = new Date(lastWeekEnd.getFullYear(), lastWeekEnd.getMonth(), lastWeekEnd.getDate(), 23, 59);
                    break;
                case 'thisMonth':
                    startDate = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
                    break;
                case 'lastMonth':
                    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const lastDayOfLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
                    startDate = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1, 0, 0);
                    endDate = new Date(lastDayOfLastMonth.getFullYear(), lastDayOfLastMonth.getMonth(), lastDayOfLastMonth.getDate(), 23, 59);
                    break;
            }

            // Input alanlarını güncelle
            const formatDateTime = (date) => {
                return date.toISOString().slice(0, 16); // YYYY-MM-DDTHH:MM formatı
            };

            document.querySelector('input[name="start_date"]').value = formatDateTime(startDate);
            document.querySelector('input[name="end_date"]').value = formatDateTime(endDate);
        };
    });
    </script>

    <!-- Font Awesome için -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</body>
</html> 