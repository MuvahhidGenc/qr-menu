<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('dashboard.view')) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Debug için session durumunu logla
error_log("Dashboard.php - Session Data: " . print_r($_SESSION, true));

// Oturum kontrolü
if (!isLoggedIn()) {
    error_log("Dashboard.php - User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit();
}

// Yetki kontrolü - süper admin veya admin ise devam et
if (!isAdmin() && !isSuperAdmin()) {
    error_log("Dashboard.php - User does not have required permissions");
    header('Location: login.php');
    exit();
}

// Session'ı yenile
$_SESSION['last_activity'] = time();

// Kapsamlı istatistikler
$total_categories = $db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'];
$total_products = $db->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
$total_views = $db->query("SELECT SUM(view_count) as total FROM products")->fetch()['total'] ?? 0;
$total_tables = $db->query("SELECT COUNT(*) as count FROM tables WHERE status = 'active'")->fetch()['count'];

// Yetki kontrolleri
$canViewReports = hasPermission('reports.view');
$canViewProducts = hasPermission('products.view');
$canViewOrders = hasPermission('orders.view');
$canViewTables = hasPermission('tables.view');
$canManageProducts = hasPermission('products.manage');
$canManageCategories = hasPermission('categories.manage');

// Sipariş istatistikleri
$todayOrders = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch()['count'];
$todayRevenue = $db->query("SELECT COALESCE(SUM(p.total_amount), 0) as total FROM orders o LEFT JOIN payments p ON o.payment_id = p.id WHERE DATE(o.created_at) = CURDATE() AND p.status = 'completed'")->fetch()['total'];
$activeOrders = $db->query("SELECT COUNT(*) as count FROM orders WHERE status NOT IN ('completed', 'cancelled')")->fetch()['count'];
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(p.total_amount), 0) as total FROM orders o LEFT JOIN payments p ON o.payment_id = p.id WHERE MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE()) AND p.status = 'completed'")->fetch()['total'];

// En popüler ürünler (güvenli sorgu)
try {
    $popularProducts = $db->query("SELECT p.name, COUNT(oi.id) as order_count FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id WHERE o.created_at >= CURDATE() - INTERVAL 7 DAY GROUP BY p.id, p.name ORDER BY order_count DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    // Fallback: Tüm ürünler
    $popularProducts = $db->query("SELECT p.name, COUNT(oi.id) as order_count FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id GROUP BY p.id, p.name ORDER BY order_count DESC LIMIT 5")->fetchAll();
}

// Son siparişler
$recentOrders = $db->query("SELECT o.id, o.total_amount, o.status, o.created_at, t.table_no FROM orders o LEFT JOIN tables t ON o.table_id = t.id ORDER BY o.created_at DESC LIMIT 6")->fetchAll();

// Günlük satış trendi (güvenli sorgu)
try {
    $salesTrend = $db->query("SELECT DATE(o.created_at) as date, COUNT(o.id) as orders, COALESCE(SUM(p.total_amount), 0) as revenue FROM orders o LEFT JOIN payments p ON o.payment_id = p.id WHERE o.created_at >= CURDATE() - INTERVAL 7 DAY AND p.status = 'completed' GROUP BY DATE(o.created_at) ORDER BY date DESC")->fetchAll();
} catch (Exception $e) {
    // Fallback: Son 7 kayıt
    $salesTrend = $db->query("SELECT DATE(o.created_at) as date, COUNT(o.id) as orders, COALESCE(SUM(p.total_amount), 0) as revenue FROM orders o LEFT JOIN payments p ON o.payment_id = p.id WHERE p.status = 'completed' GROUP BY DATE(o.created_at) ORDER BY date DESC LIMIT 7")->fetchAll();
}

// Son eklenen ürünler
$recent_products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 5")->fetchAll();

// Türkçe tarih formatı için yardımcı fonksiyon
function formatTurkishDate($date, $includeTime = false) {
    $months = [
        '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis',
        '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu',
        '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'
    ];
    
    if (is_string($date)) {
        $dateObj = new DateTime($date);
    } else {
        $dateObj = $date;
    }
    
    $formatted = $dateObj->format('d') . ' ' . $months[$dateObj->format('m')] . ' ' . $dateObj->format('Y');
    
    if ($includeTime) {
        $formatted .= ' ' . $dateObj->format('H:i');
    }
    
    return $formatted;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* Modern Dashboard CSS */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

    .dashboard-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        backdrop-filter: blur(10px);
        margin: 20px;
        padding: 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    /* Welcome Section */
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .welcome-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.7; }
        50% { transform: scale(1.1); opacity: 0.9; }
    }

    /* Modern Stat Cards */
    .stat-card {
        background: white;
        border: none;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--card-gradient);
    }

    .stat-card.primary { --card-gradient: linear-gradient(90deg, #667eea, #764ba2); }
    .stat-card.success { --card-gradient: linear-gradient(90deg, #4facfe, #00f2fe); }
    .stat-card.warning { --card-gradient: linear-gradient(90deg, #f093fb, #f5576c); }
    .stat-card.info { --card-gradient: linear-gradient(90deg, #4facfe, #00f2fe); }
    .stat-card.danger { --card-gradient: linear-gradient(90deg, #ff9a9e, #fecfef); }

    .stat-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-bottom: 15px;
        background: var(--card-gradient);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2d3748;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #718096;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-change {
        font-size: 0.8rem;
        margin-top: 8px;
    }

    .stat-change.positive { color: #38a169; }
    .stat-change.negative { color: #e53e3e; }

    /* Chart Cards */
    .chart-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .chart-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }

    .chart-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f7fafc;
    }

    .chart-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }

    /* Recent Activity Cards */
    .activity-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid var(--activity-color);
    }

    .activity-card:hover {
        transform: translateX(5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }

    .activity-card.order { --activity-color: #667eea; }
    .activity-card.product { --activity-color: #f093fb; }
    .activity-card.payment { --activity-color: #4facfe; }

    /* Tables */
    .modern-table {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .modern-table .table {
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
        padding: 20px 15px;
    }

    .modern-table .table tbody td {
        border: none;
        border-bottom: 1px solid #f1f5f9;
        padding: 15px;
        vertical-align: middle;
    }

    .modern-table .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Product Images */
    .product-img {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        object-fit: cover;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .product-img:hover {
        transform: scale(1.1) rotate(2deg);
    }

    /* Modern Badges */
    .modern-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modern-badge.success { background: #c6f6d5; color: #276749; }
    .modern-badge.danger { background: #fed7d7; color: #9b2c2c; }
    .modern-badge.warning { background: #fefcbf; color: #975a16; }
    .modern-badge.info { background: #bee3f8; color: #2a69ac; }

    /* Quick Menu Styles */
    .quick-menu-item {
        background: white;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .quick-menu-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border-color: var(--quick-border-color);
    }

    .quick-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .quick-icon.primary { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --quick-border-color: #667eea;
    }
    .quick-icon.success { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --quick-border-color: #4facfe;
    }
    .quick-icon.warning { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --quick-border-color: #f093fb;
    }
    .quick-icon.info { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --quick-border-color: #4facfe;
    }
    .quick-icon.danger { 
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        --quick-border-color: #ff9a9e;
    }
    .quick-icon.secondary { 
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --quick-border-color: #a8edea;
    }

    .quick-menu-item:hover .quick-icon {
        transform: scale(1.1);
    }

    .quick-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }

    .quick-menu-item:hover .quick-label {
        color: var(--quick-border-color, #667eea);
    }

    /* Loading Animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-slide-up {
        animation: slideInUp 0.6s ease-out;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-container {
            margin: 10px;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .welcome-section {
            padding: 20px;
        }
    }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section animate-slide-up">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2" style="font-weight: 700; font-size: 2.5rem;">
                    <i class="fas fa-chart-line me-3"></i>Dashboard
                </h1>
                <p class="mb-0 opacity-75" style="font-size: 1.1rem;">
                    Hoş geldiniz! İşletmenizin detaylı analiz raporlarını buradan takip edebilirsiniz.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="text-end">
                    <div style="font-size: 1rem; opacity: 0.8;">Bugün</div>
                    <div style="font-size: 1.5rem; font-weight: 600;">
                        <?= formatTurkishDate(new DateTime()) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hızlı Erişim Menüsü -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-rocket me-2 text-primary"></i>
                        Hızlı Erişim Menüsü
                    </h3>
                </div>
                <div class="row g-3">
                    <?php if ($canManageProducts): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="products.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon primary">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="quick-label">Ürünler</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewOrders): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="orders.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon warning">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="quick-label">Siparişler</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewTables): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="tables.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon info">
                                    <i class="fas fa-chair"></i>
                                </div>
                                <div class="quick-label">Masalar</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewReports): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="reports.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon danger">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="quick-label">Raporlar</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="settings.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon secondary">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="quick-label">Ayarlar</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana İstatistik Kartları -->
    <?php 
    // Gösterilecek kart sayısına göre column class'ı ayarla
    $visibleCards = 2; // Günlük Sipariş + Aktif Siparişler
    if ($canViewReports) $visibleCards += 2; // Günlük Gelir + Aylık Gelir
    $colClass = $visibleCards == 2 ? 'col-lg-6 col-md-6' : ($visibleCards == 3 ? 'col-lg-4 col-md-6' : 'col-lg-3 col-md-6');
    ?>
    <div class="row">
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card primary animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?= $todayOrders ?></div>
                <div class="stat-label">Bugünkü Siparişler</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>+12% dünden
                </div>
            </div>
        </div>
        
        <?php if ($canViewReports): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card success animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-lira-sign"></i>
                </div>
                <div class="stat-number"><?= number_format($todayRevenue, 0, ',', '.') ?>₺</div>
                <div class="stat-label">Günlük Gelir</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>+8% dünden
                </div>
            </div>
                        </div>
        <?php endif; ?>

        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card warning animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                    </div>
                <div class="stat-number"><?= $activeOrders ?></div>
                <div class="stat-label">Aktif Siparişler</div>
                <div class="stat-change">
                    <i class="fas fa-clock me-1"></i>Canlı
                </div>
            </div>
        </div>
        
        <?php if ($canViewReports): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card info animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-calendar-month"></i>
                </div>
                <div class="stat-number"><?= number_format($monthlyRevenue, 0, ',', '.') ?>₺</div>
                <div class="stat-label">Aylık Gelir</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up me-1"></i>+15% geçen aydan
                </div>
            </div>
        </div>
        <?php endif; ?>
                        </div>

    <!-- İkinci Seviye İstatistikler -->
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card danger animate-slide-up">
                <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                <div class="stat-number"><?= $total_products ?></div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card primary animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?= $total_categories ?></div>
                <div class="stat-label">Kategori Sayısı</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card success animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-number"><?= $total_tables ?></div>
                <div class="stat-label">Aktif Masa</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="stat-card warning animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?= number_format($total_views) ?></div>
                <div class="stat-label">Toplam Görüntülenme</div>
            </div>
        </div>
    </div>


    <!-- Grafikler ve Detaylar -->
    <?php if ($canViewReports): ?>
    <div class="row">
        <!-- Satış Trendi Grafiği -->
        <div class="col-lg-8 mb-4">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Son 7 Günlük Satış Trendi
                    </h3>
                </div>
                <div style="position: relative; height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- En Popüler Ürünler -->
        <div class="col-lg-4 mb-4">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-fire me-2 text-danger"></i>
                        Popüler Ürünler
                    </h3>
                </div>
                <div style="position: relative; height: 300px;">
                    <canvas id="popularChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alt Bölüm -->
    <div class="row">
        <?php if ($canViewOrders): ?>
        <!-- Son Siparişler -->
        <div class="<?= ($canViewProducts) ? 'col-lg-6' : 'col-12' ?> mb-4">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        Son Siparişler
                    </h3>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($recentOrders as $order): ?>
                    <div class="activity-card order">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    Sipariş #<?= $order['id'] ?>
                                </h6>
                                <small class="text-muted">
                                    <?= htmlspecialchars($order['table_no']) ?> - 
                                    <?= formatTurkishDate($order['created_at'], true) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">
                                    <?= number_format($order['total_amount'], 2) ?>₺
                                </div>
                                <span class="modern-badge <?= $order['status'] == 'completed' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canViewProducts): ?>
    <!-- Son Eklenen Ürünler -->
        <div class="<?= ($canViewOrders) ? 'col-lg-6' : 'col-12' ?> mb-4">
            <div class="modern-table animate-slide-up">
                <div class="chart-header" style="padding: 25px 25px 0 25px;">
                    <h3 class="chart-title">
                        <i class="fas fa-utensils me-2 text-success"></i>
                        Son Eklenen Ürünler
                    </h3>
        </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Kategori</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/<?= $product['image'] ?>" 
                                             class="product-img me-3" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                            <small class="text-muted">ID: <?= $product['id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="modern-badge info">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">
                                        <?= number_format($product['price'], 2) ?>₺
                                </div>
                            </td>
                                <td>
                                    <span class="modern-badge <?= $product['status'] ? 'success' : 'danger' ?>">
                                        <?= $product['status'] ? 'Aktif' : 'Pasif' ?>
                                    </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
<?php if ($canViewReports): ?>
// Satış Trendi Grafiği
const salesData = <?= json_encode($salesTrend) ?>;
const salesLabels = salesData.map(item => {
    const date = new Date(item.date);
    const months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
    return date.getDate().toString().padStart(2, '0') + ' ' + months[date.getMonth()];
}).reverse();
const salesValues = salesData.map(item => parseFloat(item.revenue)).reverse();
const orderCounts = salesData.map(item => parseInt(item.orders)).reverse();

const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Günlük Gelir (₺)',
            data: salesValues,
            borderColor: 'rgba(102, 126, 234, 1)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(102, 126, 234, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6
        }, {
            label: 'Sipariş Sayısı',
            data: orderCounts,
            borderColor: 'rgba(240, 147, 251, 1)',
            backgroundColor: 'rgba(240, 147, 251, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.4,
            pointBackgroundColor: 'rgba(240, 147, 251, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12, weight: '600' }
                }
            },
            tooltip: {
                callbacks: {
                    title: function(context) {
                        return context[0].label; // Zaten Türkçe formatlanmış
                    },
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.dataset.label.includes('Gelir')) {
                            label += context.parsed.y.toLocaleString('tr-TR') + '₺';
                        } else {
                            label += context.parsed.y + ' adet';
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('tr-TR') + '₺';
                    },
                    font: { size: 11 }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false },
                ticks: {
                    callback: function(value) {
                        return value + ' adet';
                    },
                    font: { size: 11 }
                }
            },
            x: {
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { font: { size: 11 } }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Popüler Ürünler Grafiği
const popularData = <?= json_encode($popularProducts) ?>;
const popularLabels = popularData.map(item => {
    return item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name;
});
const popularValues = popularData.map(item => parseInt(item.order_count));

const popularCtx = document.getElementById('popularChart').getContext('2d');
new Chart(popularCtx, {
    type: 'doughnut',
    data: {
        labels: popularLabels,
        datasets: [{
            data: popularValues,
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(240, 147, 251, 0.8)',
                'rgba(79, 172, 254, 0.8)',
                'rgba(255, 154, 158, 0.8)',
                'rgba(130, 202, 157, 0.8)'
            ],
            borderColor: [
                'rgba(102, 126, 234, 1)',
                'rgba(240, 147, 251, 1)',
                'rgba(79, 172, 254, 1)',
                'rgba(255, 154, 158, 1)',
                'rgba(130, 202, 157, 1)'
            ],
            borderWidth: 2,
            hoverBorderWidth: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: { size: 11 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = popularData[context.dataIndex].name;
                        const value = context.parsed;
                        return label + ': ' + value + ' sipariş';
                    }
                }
            }
        },
        cutout: '60%'
    }
});
<?php endif; ?>

// Animasyon efektleri
document.addEventListener('DOMContentLoaded', function() {
    // Stat kartlarına staggered animation
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Chart kartlarına da delay
    const chartCards = document.querySelectorAll('.chart-card');
    chartCards.forEach((card, index) => {
        card.style.animationDelay = `${(index + statCards.length) * 0.1}s`;
    });
});

// Sayı animasyonu
function animateNumbers() {
    const numbers = document.querySelectorAll('.stat-number');
    numbers.forEach(number => {
        const target = parseInt(number.textContent.replace(/[^\d]/g, ''));
        const increment = target / 50;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (number.textContent.includes('₺')) {
                number.textContent = Math.floor(current).toLocaleString('tr-TR') + '₺';
            } else {
                number.textContent = Math.floor(current).toLocaleString('tr-TR');
            }
        }, 20);
    });
}

// Sayfa yüklenince animasyonları başlat
setTimeout(animateNumbers, 500);
</script>

</body>
</html>