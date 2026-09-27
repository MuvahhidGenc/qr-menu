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

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Yetki kontrolü - süper admin veya admin ise devam et
if (!isAdmin() && !isSuperAdmin()) {
    header('Location: login.php');
    exit();
}

// Session'ı yenile
$_SESSION['last_activity'] = time();

// ---------------------------------------------------------------------------
// AKTİF SİSTEM BAYRAKLARI
//
// Dashboard yalnızca AÇIK olan sistemlerin verisini göstermelidir. Bayraklar
// dvFeatureFlags() ile tek yerden okunur; navbar.php da aynı fonksiyonu
// kullandığı için menü ile ana sayfa birbirinden kopamaz.
//
// Kapalı bir sistemin istatistiği sorgulanmaz (AND 1 = 0) ve kartı render
// edilmez. Böylece "web sipariş kapalıyken web sipariş bilgisi" gibi
// anlamsız durumlar oluşmaz.
// ---------------------------------------------------------------------------
$flags = dvFeatureFlags($db);
$orderScopeSql = dvOrderScopeSql($flags);   // ['AND o.order_type IN (?,?)', params]
$orderScopeParams = $orderScopeSql[1];
$orderScopeLabel = dvOrderScopeLabel($flags);
$anyOrderSystem = $flags['orders'];

// Katalog istatistikleri (sistemlerden bağımsız, her zaman geçerli)
$total_categories = (int)$db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'];
$total_products   = (int)$db->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];

// Görüntülenme sayacı müşteri QR menüsünden gelir; QR menü kapalıysa
// sayaç anlamsızlaşır, bu yüzden yalnızca QR menü açıkken gösterilir.
$total_views = $flags['qrMenu']
    ? (int)($db->query("SELECT COALESCE(SUM(view_count), 0) as total FROM products")->fetch()['total'] ?? 0)
    : null;

// Masa sayacı yalnızca masalar modülü açıkken anlamlıdır.
$total_tables = $flags['tables']
    ? (int)$db->query("SELECT COUNT(*) as count FROM tables WHERE status = 'active'")->fetch()['count']
    : null;

// Yetki kontrolleri
$canViewReports = hasPermission('reports.view');
$canViewProducts = hasPermission('products.view');
$canViewOrders = hasPermission('orders.view');
$canViewTables = hasPermission('tables.view');
$canManageProducts = hasPermission('products.manage');
$canManageCategories = hasPermission('categories.manage');

// ---------------------------------------------------------------------------
// SATIŞ PANELİ (kanonik ciro katmanı)
// ---------------------------------------------------------------------------
// Ciro hesabı artık includes/sales.php içinde tanımlıdır. Dashboard'daki eski
// sorgular `FROM orders LEFT JOIN payments` üzerinden çalışıyordu; bu yaklaşım
// iki hata yapıyordu:
//   1) POS satışları orders tablosuna yazılmadığı için HİÇ görünmüyordu.
//   2) orders.total_amount, bağlı payments.total_amount'in kopyası olduğu için
//      ikisi birlikte sorgulandığında ciro iki kez sayılıyordu.
// Kanal ayrımı `table_id` üzerinden yapılır (kısmi masa tahsilatları da doğru
// sınıflanır) ve her kanal tam olarak bir kez sayılır.
require_once '../includes/sales.php';

// Kanal seçimi: beyaz listeye karşı uygunlanır, kapalı sistemler elenir.
$activeChannels = sales_active_channels($flags);
[$reqChannels] = sales_filter_channels($_GET['ch'] ?? null);
$panelChannels = array_values(array_intersect($reqChannels, array_keys(array_filter($activeChannels))));

// Tarih aralığı
$periodPresets = sales_date_presets();
$periodReq = (string)($_GET['period'] ?? '');
// Hem hazır dönemler hem de YYYY-MM-DD..YYYY-MM-DD biçiminde özel aralık
// kabul edilir; diğer her şey '30d'ye düşer (beyaz liste).
$isCustomPeriod = (bool)preg_match('/^\d{4}-\d{2}-\d{2}\.\.\d{4}-\d{2}-\d{2}$/', $periodReq);
$period = isset($periodPresets[$periodReq]) ? $periodReq : ($isCustomPeriod ? $periodReq : '30d');
$customFrom = trim((string)($_GET['from'] ?? ''));
$customTo   = trim((string)($_GET['to'] ?? ''));
// Tarih girdileri YYYY-MM-DD biciminde olmali; aksi halde bos birakilir
// (sales_date_range zaten gecersiz bicimi reddeder ama burada acikca
//  reddedip kullaniciyi varsayilana dondurmuyoruz).
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $customFrom)) $customFrom = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $customTo))   $customTo = '';
$useCustom  = $isCustomPeriod || (bool)($customFrom || $customTo);
if (!$isCustomPeriod && ($customFrom || $customTo)) {
    // from/to alanlari varsa tarih alanlari gecerli olmali
    $f = $customFrom !== '' ? $customFrom : '2000-01-01';
    $t = $customTo !== '' ? $customTo : date('Y-m-d');
    $period = $f . '..' . $t;
}
[$panelFrom, $panelTo] = sales_date_range($period);

$panel = [
    'summary'  => ['channels' => [], 'totals' => ['sale_count' => 0, 'revenue' => 0.0, 'gross' => 0.0,
        'discount' => 0.0, 'collected' => 0.0, 'avg_basket' => 0.0]],
    'methods'  => [],
    'daily'    => [],
    'top'      => [],
    'open'     => [],
    'warnings' => [],
];
$showSalesPanel = $canViewReports && $panelChannels;

if ($showSalesPanel) {
    $panel = sales_dashboard_data($db, [
        'channels' => $panelChannels,
        'from'     => $panelFrom,
        'to'       => $panelTo,
        'days'     => 30,
        'limit'    => 6,
    ]);
}

$panelTotals   = $panel['summary']['totals'];
$panelChannelsData = $panel['summary']['channels'];
$panelLabel    = $useCustom
    ? ($customFrom ?: (explode('..', $period)[0] ?? '')) . ' – '
      . ($customTo ?: (explode('..', $period)[1] ?? 'Bugün'))
    : ($periodPresets[$period] ?? 'Son 30 Gün');

// Sipariş istatistikleri - yalnızca aktif sipariş sistemlerinden
$todayOrders = 0;
$todayRevenue = 0;
$activeOrders = 0;
$monthlyRevenue = 0;
$popularProducts = [];
$salesTrend = [];
$recentOrders = [];

if ($anyOrderSystem) {
    // $sp = sipariş kaynak filtresi, $spp = parametreleri
    $sp = $orderScopeSql[0];
    $spp = $orderScopeParams;

    $todayOrders = (int)$db->query(
        "SELECT COUNT(*) as count FROM orders o WHERE DATE(o.created_at) = CURDATE() $sp",
        $spp
    )->fetch()['count'];

    $activeOrders = (int)$db->query(
        "SELECT COUNT(*) as count FROM orders o
          WHERE o.status NOT IN ('completed', 'cancelled') $sp",
        $spp
    )->fetch()['count'];

    // En popüler ürünler: satış paneli açıksa kanonik ürün kırılımı kullanılır
    // (eski sorgu yalnız orders üzerinden gittiği için POS satışlarını görmezdi).
    if ($showSalesPanel) {
        $popularProducts = array_map(function ($r) {
            return ['name' => $r['name'], 'order_count' => (int)$r['qty']];
        }, $panel['top']);
    } else {
        try {
            $popularProducts = $db->query(
                "SELECT p.name, COUNT(oi.id) as order_count
                   FROM products p
                   JOIN order_items oi ON p.id = oi.product_id
                   JOIN orders o ON oi.order_id = o.id
                  WHERE o.created_at >= CURDATE() - INTERVAL 7 DAY $sp
               GROUP BY p.id, p.name
               ORDER BY order_count DESC
               LIMIT 5",
                $spp
            )->fetchAll();
        } catch (Exception $e) {
            $popularProducts = [];
        }
    }

    // Son siparişler - kaynak etiketi de eklenir, böylece hangi sisteme
    // ait oldukları belli olur.
    $recentOrders = $db->query(
        "SELECT o.id, o.total_amount, o.status, o.created_at, o.order_type,
                t.table_no, o.customer_name, o.customer_surname, o.delivery_district
           FROM orders o
           LEFT JOIN tables t ON o.table_id = t.id
          WHERE 1 = 1 $sp
          ORDER BY o.created_at DESC
          LIMIT 6",
        $spp
    )->fetchAll();
}

// Günlük gelir ve aylık gelir KANONİK katmandan gelir.
// Önceden `orders LEFT JOIN payments` ile hesaplanıyordu; bu sorgu POS
// cirosunu tamamen atlıyor ve bağlı siparişlerde ciroyu iki kez sayıyordu.
if ($showSalesPanel) {
    $todayRevenue = sales_summary($db, [
        'channels' => $panelChannels,
        'from'     => date('Y-m-d 00:00:00'),
        'to'       => date('Y-m-d 23:59:59'),
    ])['totals']['revenue'];

    $monthlyRevenue = sales_summary($db, [
        'channels' => $panelChannels,
        'from'     => date('Y-m-01 00:00:00'),
        'to'       => date('Y-m-t 23:59:59'),
    ])['totals']['revenue'];
}

// Günlük satış trendi - kanonik katmandan, seçili kanallar ve dönem için
if ($showSalesPanel) {
    $salesTrend = array_map(function ($r) {
        return ['date' => $r['date'], 'orders' => $r['count'], 'revenue' => $r['revenue']];
    }, $panel['daily']);
} elseif ($anyOrderSystem) {
    $salesTrend = [];
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
<?php // NOT: Modül görünürlüğü artık dvFeatureFlags() -> $flags üzerinden tek
      // yerden okunur; navbar'ın $showTables gibi değişkenlerine burada
      // gerek yoktur (eskiden burada "??" ile true'ya zorlanıyordu ve
      // masalar kapalıyken bile panelde görünmesine yol açabiliyordu). ?>

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

                    <?php // Her kart, kendi sistemi AÇIKKEN ve kullanıcı yetkiliyken
                    // görünür. Kapalı sistemlere ait hiçbir kart render edilmez.
                    // Böylece menü yalnızca aktif olan sistemleri listeler. ?>

                    <?php // Masa siparişleri (QR ile sipariş)
                    if ($flags['tableOrders'] && $canViewOrders): ?>                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="orders.php?source=table" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon warning">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div class="quick-label">Masa Siparişleri</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php // Web adrese sipariş
                    if ($flags['webOrders'] && $canViewOrders): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="orders.php?source=delivery" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon primary">
                                    <i class="fas fa-motorcycle"></i>
                                </div>
                                <div class="quick-label">Web Siparişleri</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php // Peşin satış (POS)
                    if ($flags['posSales'] && $canViewTables): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                        <a href="pos_sales.php" class="text-decoration-none">
                            <div class="quick-menu-item">
                                <div class="quick-icon success">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div class="quick-label">Peşin Satış</div>
                            </div>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php // Masa yönetimi
                    if ($flags['tables'] && $canViewTables): ?>
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
                <?php // orders tablosunu dolduran sistem (masa siparişi / web siparişi)
                // açık değilse sipariş istatistikleri anlamsızlaşır. Peşin satış
                // (POS) ayrı bir kanal olduğu için bu istatistiklere girmez.
                if (!$anyOrderSystem): ?>
                <div class="alert alert-info mt-3 mb-0 py-2 small">
                    <i class="fas fa-info-circle me-1"></i>
                    <?php if ($flags['posSales']): ?>
                        Peşin satış açık ancak <strong>masa ve web sipariş sistemlerinin
                        ikisi de kapalı</strong>. Sipariş istatistikleri için Sistem
                        Parametreleri &rarr; Sipariş Sistemleri bölümünden bunlardan
                        en az birini etkinleştirin.
                    <?php else: ?>
                        Şu anda <strong>hiçbir sipariş sistemi açık değil</strong>.
                        Sipariş istatistikleri panelde gösterilmez. Sistem Parametreleri
                        &rarr; Sipariş Sistemleri bölümünden masa ve/veya web siparişini
                        etkinleştirebilirsiniz.
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ana İstatistik Kartları -->
    <?php
    // Sipariş kartları iki koşulla render edilir: en az bir sipariş sistemi AÇIK
    // olmalı VE kullanıcı orders.view iznine sahip olmalıdır. Önceden kartlar
    // yalnızca sistem bayrağına bakıyordu; orders.view yetkisi olmayan kullanıcı
    // sipariş adetlerini görebiliyordu. Önceden sabit "+12% dünden" gibi gerçek
    // veriden hesaplanmayan değerler gösteriliyordu; onların yerine kartın hangi
    // sistemi kapsadığı yazılır.
    $showOrderStats = $canViewOrders && $anyOrderSystem;
    // Ciro KARTLARI yalnız tutar gösterir -> reports.view yeterlidir. Böylece
    // yalnız POS açıkken de ciro görünür (eski kod ciroyu masaya bağlıydı).
    $showRevenueStats = $canViewReports && ($showSalesPanel || $showOrderStats);
    // Ciro GRAFİĞİ ayrıca "Sipariş Sayısı" serisi taşır; bu bir ADET verisi
    // olduğu için orders.view olmadan gösterilemez. Test E bunu doğruluyor.
    $showTrendChart = $showRevenueStats && $showOrderStats;
    $visibleCards = ($showOrderStats ? 2 : 0) + ($showRevenueStats ? 2 : 0);
    $colClass = $visibleCards === 0 ? 'col-lg-12 col-md-12'
        : ($visibleCards === 2 ? 'col-lg-6 col-md-6'
        : ($visibleCards === 3 ? 'col-lg-4 col-md-6' : 'col-lg-3 col-md-6'));
    ?>
    <?php if ($showOrderStats || $showRevenueStats): ?>
    <div class="row">
        <?php if ($showOrderStats): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card primary animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?= number_format($todayOrders, 0, ',', '.') ?></div>
                <div class="stat-label">Bugünkü Siparişler</div>
                <div class="stat-change">
                    <i class="fas fa-filter me-1"></i><?= htmlspecialchars($orderScopeLabel) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showRevenueStats): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card success animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-lira-sign"></i>
                </div>
                <div class="stat-number"><?= number_format($todayRevenue, 0, ',', '.') ?>₺</div>
                <div class="stat-label">Günlük Gelir</div>
                <div class="stat-change">
                    <i class="fas fa-filter me-1"></i><?= htmlspecialchars($orderScopeLabel) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showOrderStats): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card warning animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= number_format($activeOrders, 0, ',', '.') ?></div>
                <div class="stat-label">Aktif Siparişler</div>
                <div class="stat-change">
                    <i class="fas fa-clock me-1"></i>Canlı
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showRevenueStats): ?>
        <div class="<?= $colClass ?> mb-4">
            <div class="stat-card info animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-calendar-month"></i>
                </div>
                <div class="stat-number"><?= number_format($monthlyRevenue, 0, ',', '.') ?>₺</div>
                <div class="stat-label">Aylık Gelir</div>
                <div class="stat-change">
                    <i class="fas fa-filter me-1"></i><?= htmlspecialchars($orderScopeLabel) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ===================== SATIŞLAR PANELİ =====================
         Masa / POS / Web kanalları ayrı ayrı gösterilir, toplam ciro ise
         kanonik katmandan (includes/sales.php) gelir. Kanal ve tarih
         filtreleri GET ile taşınır; kanal anahtarları beyaz listeye
         karşı kontrol edilir ve kapalı sistemler her zaman elenir.
    -->
    <?php if ($showSalesPanel): ?>
    <div class="chart-card animate-slide-up mb-4">
        <div class="chart-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="chart-title mb-0">
                <i class="fas fa-chart-pie me-2 text-primary"></i>Satışlar ve Ciro
            </h3>

            <!-- Filtre çubuğu -->
            <form method="get" class="d-flex flex-wrap align-items-center gap-2">
                <!-- Kanal anahtarları tüm kanallarda sabit; kapalı olanlar devre dışı -->
                <?php foreach (sales_channels() as $ck => $cm): ?>
                    <?php $isOn = in_array($ck, $panelChannels, true); ?>
                    <div class="form-check form-check-inline mb-0">
                        <input class="form-check-input" type="checkbox"
                               name="ch[]" value="<?= htmlspecialchars($ck) ?>"
                               id="ch_<?= htmlspecialchars($ck) ?>"
                               <?= $isOn ? 'checked' : '' ?>
                               <?= $activeChannels[$ck] ? '' : 'disabled' ?>
                               <?= $activeChannels[$ck] ? '' : 'title="Bu sistem kapalı"' ?>>
                        <label class="form-check-label small" for="ch_<?= htmlspecialchars($ck) ?>"
                               style="cursor:pointer">
                            <i class="fas <?= htmlspecialchars($cm['icon']) ?> me-1"
                               style="color:<?= htmlspecialchars($cm['color']) ?>"></i>
                            <?= htmlspecialchars($cm['label']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <select name="period" class="form-select form-select-sm" style="width:auto"
                        onchange="this.form.submit()" aria-label="Tarih aralığı">
                    <?php foreach ($periodPresets as $pk => $pl): ?>
                        <option value="<?= htmlspecialchars($pk) ?>"
                            <?= (!$useCustom && $period === $pk) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="from" class="form-control form-control-sm" style="width:auto"
                       value="<?= htmlspecialchars($customFrom) ?>" aria-label="Başlangıç tarihi">
                <input type="date" name="to" class="form-control form-control-sm" style="width:auto"
                       value="<?= htmlspecialchars($customTo) ?>" aria-label="Bitiş tarihi">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-filter me-1"></i>Uygula
                </button>
            </form>
        </div>

        <div class="chart-body">
            <!-- Veri kalitesi uyarıları -->
            <?php if ($panel['warnings']): ?>
                <?php foreach ($panel['warnings'] as $w): ?>
                    <div class="alert alert-<?= htmlspecialchars($w['level']) ?> py-2 px-3 small"
                         role="alert">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <?= htmlspecialchars($w['message']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Toplam ciro bandı -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 p-3 rounded"
                         style="background:linear-gradient(135deg,#0d6efd,#6f42c1);color:#fff">
                        <div>
                            <div class="small text-uppercase" style="opacity:.85;letter-spacing:.5px">
                                Toplam Ciro
                            </div>
                            <div class="fs-2 fw-bold" id="panelRevenue">
                                <?= number_format($panelTotals['revenue'], 2, ',', '.') ?> ₺
                            </div>
                            <div class="small" style="opacity:.85">
                                <i class="fas fa-calendar me-1"></i><?= htmlspecialchars($panelLabel) ?>
                                &middot; <?= htmlspecialchars(implode(' + ', array_map(
                                    fn($k) => $panelChannelsData[$k]['label'], $panelChannels
                                ))) ?>
                            </div>
                        </div>
                        <div class="d-flex gap-4 flex-wrap">
                            <div>
                                <div class="small" style="opacity:.85">Tahsilat</div>
                                <div class="fs-5 fw-semibold">
                                    <?= number_format($panelTotals['collected'], 2, ',', '.') ?> ₺
                                </div>
                            </div>
                            <?php if ($canViewOrders): ?>
                            <div>
                                <div class="small" style="opacity:.85">İşlem</div>
                                <div class="fs-5 fw-semibold">
                                    <?= number_format($panelTotals['sale_count'], 0, ',', '.') ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="small" style="opacity:.85">Ortalama Sepet</div>
                                <div class="fs-5 fw-semibold">
                                    <?= number_format($panelTotals['avg_basket'], 2, ',', '.') ?> ₺
                                </div>
                            </div>
                            <?php if ($panelTotals['discount'] > 0): ?>
                            <div>
                                <div class="small" style="opacity:.85">İndirim</div>
                                <div class="fs-5 fw-semibold">
                                    <?= number_format($panelTotals['discount'], 2, ',', '.') ?> ₺
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Kanal kartları -->
                <?php foreach ($panelChannels as $ck): ?>
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded h-100" style="border:1px solid #e9ecef;border-top:3px solid <?= htmlspecialchars($panelChannelsData[$ck]['color']) ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">
                                    <i class="fas <?= htmlspecialchars($panelChannelsData[$ck]['icon']) ?> me-1"
                                       style="color:<?= htmlspecialchars($panelChannelsData[$ck]['color']) ?>"></i>
                                    <?= htmlspecialchars($panelChannelsData[$ck]['label']) ?>
                                </div>
                                <div class="small text-muted">
                                    <?php if ($canViewOrders): ?>
                                        <?= (int)$panelChannelsData[$ck]['sale_count'] ?> satış
                                    <?php else: ?>
                                        ciro payı
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 fw-bold">
                                    <?= number_format($panelChannelsData[$ck]['revenue'], 2, ',', '.') ?> ₺
                                </div>
                                <div class="small text-muted">
                                    sepet <?= number_format($panelChannelsData[$ck]['avg_basket'], 2, ',', '.') ?> ₺
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height:6px" role="progressbar"
                             aria-label="<?= htmlspecialchars($panelChannelsData[$ck]['label']) ?> ciro payı"
                             aria-valuenow="<?= $panelTotals['revenue'] > 0
                                 ? round($panelChannelsData[$ck]['revenue'] / $panelTotals['revenue'] * 100) : 0 ?>"
                             aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width:<?= $panelTotals['revenue'] > 0
                                ? round($panelChannelsData[$ck]['revenue'] / $panelTotals['revenue'] * 100) : 0 ?>%;
                                background:<?= htmlspecialchars($panelChannelsData[$ck]['color']) ?>"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Ödeme yöntemi kırılımı + açık siparişler -->
            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <h6 class="fw-semibold mb-2"><i class="fas fa-credit-card me-2 text-muted"></i>Ödeme Yöntemi</h6>
                    <?php if (!$panel['methods']): ?>
                        <p class="text-muted small mb-0">Bu dönemde ödeme kaydı yok.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($panel['methods'] as $m):
                                $pct = $panelTotals['revenue'] > 0
                                    ? round($m['total'] / $panelTotals['revenue'] * 100) : 0; ?>
                            <li class="mb-2">
                                <div class="d-flex justify-content-between small">
                                    <span>
                                        <i class="bi <?= $m['method'] === 'cash' ? 'bi-cash-coin'
                                            : ($m['method'] === 'card' ? 'bi-credit-card' : 'bi-wallet') ?> me-1"></i>
                                        <?= htmlspecialchars($m['label']) ?>
                                        <?php if ($canViewOrders): ?>
                                            <span class="text-muted">(<?= (int)$m['count'] ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="fw-semibold">
                                        <?= number_format($m['total'], 2, ',', '.') ?> ₺
                                        <span class="text-muted">%<?= $pct ?></span>
                                    </span>
                                </div>
                                <div class="progress" style="height:6px" role="progressbar"
                                     aria-label="<?= htmlspecialchars($m['label']) ?> payı"
                                     aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-secondary" style="width:<?= $pct ?>%"></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-lg-7">
                    <h6 class="fw-semibold mb-2">
                        <i class="fas fa-hourglass-half me-2 text-muted"></i>Tahsil Edilmemiş Siparişler
                    </h6>
                    <?php if (!$panel['open']): ?>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Açık bakiye yok — tüm siparişler tahsil edildi.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th>Masa</th>
                                        <th>Sipariş</th>
                                        <th class="text-end">Tutar</th>
                                        <th class="text-end">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($panel['open'] as $o): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($o['table_no'] ?? '-')) ?></td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars(substr((string)($o['created_at'] ?? ''), 0, 16)) ?>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format((float)$o['total_amount'], 2, ',', '.') ?> ₺
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-warning text-dark">
                                                <?= htmlspecialchars((string)$o['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- İkinci Seviye İstatistikler -->
    <?php
    // Katalog kartları her zaman; masa kartı yalnızca masalar açıkken,
    // görüntülenme kartı yalnızca müşteri QR menüsü açıkken.
    $catalogCards = 2
        + ($flags['tables'] ? 1 : 0)
        + ($total_views !== null ? 1 : 0);
    $catalogCol = $catalogCards >= 4 ? 'col-lg-3 col-md-6' : 'col-lg-4 col-md-6';
    ?>
    <div class="row">
        <div class="<?= $catalogCol ?> mb-4">
            <div class="stat-card danger animate-slide-up">
                <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                <div class="stat-number"><?= number_format($total_products, 0, ',', '.') ?></div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
        </div>

        <div class="<?= $catalogCol ?> mb-4">
            <div class="stat-card primary animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-number"><?= number_format($total_categories, 0, ',', '.') ?></div>
                <div class="stat-label">Kategori Sayısı</div>
            </div>
        </div>

        <?php if ($flags['tables']): ?>
        <div class="<?= $catalogCol ?> mb-4">
            <div class="stat-card success animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-number"><?= number_format($total_tables, 0, ',', '.') ?></div>
                <div class="stat-label">Aktif Masa</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($total_views !== null): ?>
        <div class="<?= $catalogCol ?> mb-4">
            <div class="stat-card warning animate-slide-up">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?= number_format($total_views, 0, ',', '.') ?></div>
                <div class="stat-label">Toplam Görüntülenme</div>
            </div>
        </div>
        <?php endif; ?>
    </div>


    <!-- Grafikler ve Detaylar -->
    <?php // Satış trendi ve popüler ürünler sipariş verisinden türer;
    // sipariş sistemlerinin hiçbiri açık değilse grafikler gösterilmez.
    // Grafik "Sipariş Sayısı" serisi de içerdiği için orders.view gerekir. ?>
    <?php if ($showTrendChart): ?>
    <div class="row">
        <!-- Satış Trendi Grafiği -->
        <div class="col-lg-8 mb-4">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Günlük Ciro Trendi
                    </h3>
                    <small class="text-muted"><?= htmlspecialchars($showSalesPanel ? $panelLabel : $orderScopeLabel) ?></small>
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
    <?php // "Son Siparişler" ve "Son Eklenen Ürünler" yan yana durabilsin diye
    // her biri ancak kendi sistemi/ yetkisi açıkken render edilir. ?>
    <?php
    $showRecentOrders = $canViewOrders && $anyOrderSystem;
    $showRecentProducts = $canViewProducts;
    $bottomCards = ($showRecentOrders ? 1 : 0) + ($showRecentProducts ? 1 : 0);
    $bottomCol = $bottomCards >= 2 ? 'col-lg-6' : 'col-12';
    ?>
    <?php if ($showRecentOrders || $showRecentProducts): ?>
    <div class="row">
        <?php if ($showRecentOrders): ?>
        <!-- Son Siparişler -->
        <div class="<?= $bottomCol ?> mb-4">
            <div class="chart-card animate-slide-up">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        Son Siparişler
                    </h3>
                    <small class="text-muted"><?= htmlspecialchars($orderScopeLabel) ?></small>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <div>Bu sistem için henüz sipariş yok.</div>
                        </div>
                    <?php endif; ?>
                    <?php foreach($recentOrders as $order): ?>
                    <div class="activity-card order">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                                <h6 class="mb-1">
                                    <i class="fas <?= $order['order_type'] === 'delivery' ? 'fa-motorcycle' : 'fa-qrcode' ?> me-2"></i>
                                    Sipariş #<?= (int)$order['id'] ?>
                                    <span class="badge bg-light text-dark ms-1">
                                        <?= $order['order_type'] === 'delivery' ? 'Web' : 'Masa' ?>
                                    </span>
                                </h6>
                                <small class="text-muted">
                                    <?php // Masa bilgisi yalnızca masalar modülü açıkken ve
                                    // sipariş bir masa siparişiyken gösterilir; web
                                    // siparişlerinde müşteri/ilçe bilgisi tercih edilir.
                                    if ($order['order_type'] === 'delivery') {
                                        $who = trim((string)($order['customer_name'] ?? ''));
                                        if ($who === '') $who = 'Web müşteri';
                                        $dist = trim((string)($order['delivery_district'] ?? ''));
                                        echo htmlspecialchars($who) . ($dist !== '' ? ' / ' . htmlspecialchars($dist) : '');
                                    } elseif ($flags['tables'] && !empty($order['table_no'])) {
                                        echo 'Masa ' . htmlspecialchars((string)$order['table_no']);
                                    } else {
                                        echo 'Masa siparişi';
                                    }
                                    echo ' - ' . formatTurkishDate($order['created_at'], true); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">
                                    <?= number_format((float)$order['total_amount'], 2) ?>₺
                                </div>
                                <span class="modern-badge <?= $order['status'] == 'completed' ? 'success' : 'warning' ?>">
                                    <?= deliveryStatusLabel($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showRecentProducts): ?>
    <!-- Son Eklenen Ürünler -->
        <div class="<?= $bottomCol ?> mb-4">
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
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../uploads/<?= htmlspecialchars($product['image']) ?>"
                                             class="product-img me-3" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="product-img me-3 d-flex align-items-center justify-content-center bg-secondary text-white rounded">
                                            <?= mb_substr($product['name'] ?? '?', 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                            <small class="text-muted">ID: <?= (int)$product['id'] ?></small>
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
                                        <?= number_format((float)$product['price'], 2) ?>₺
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
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
<?php // Grafik JS'i, yukarıdaki canvas ile birebir aynı koşulda üretilir.
      // Koşullar ayrışırsa getContext(null) üzerinden JS hatası verir.
if ($showTrendChart): ?>
// Satış Trendi Grafiği
// Tarih dizgisi yerel saate cevrilmez: new Date('YYYY-MM-DD') UTC kabul edip
// Türkiye'de bir gün geri gösterirdi. Bu yüzden metin parçalanır.
const salesData = <?= json_encode($salesTrend) ?>;
const months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
const salesLabels = salesData.map(item => {
    const parts = String(item.date).slice(0, 10).split('-');
    const m = parseInt(parts[1], 10) - 1;
    return parts[2] + ' ' + (months[m] || '');
});
const salesValues = salesData.map(item => parseFloat(item.revenue));
const orderCounts = salesData.map(item => parseInt(item.orders));

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
        }<?php // "Sipariş Sayısı" bir ADET verisidir; orders.view olmadan
        // gösterilemez. reports.view yalnız tutar (ciro) bilgisini açar.
        // Bu yüzden canvas reports.view ile açılır ama sayım serisi
        // orders.view'e bağlıdır. ?>
        <?php if ($showOrderStats): ?>, {
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
        }<?php endif; ?>
        ]
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
                display: <?= $showOrderStats ? 'true' : 'false' ?>,
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