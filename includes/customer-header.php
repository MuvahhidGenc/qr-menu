<?php
// Database bağlantısı
if (!isset($db)) {
    require_once __DIR__ . '/../includes/config.php';
    $db = new Database();
}

// Sadece aktif sipariş kontrolü
$activeOrder = null;

if (isset($_SESSION['table_id'])) {
    // Aktif siparişi kontrol et (paid ve completed hariç)
    $activeOrder = $db->query(
        "SELECT id 
         FROM orders 
         WHERE table_id = ? 
         AND status NOT IN ('paid', 'completed')  /* Ödenmiş ve tamamlanmış siparişleri hariç tut */
         ORDER BY created_at DESC 
         LIMIT 1",
        [$_SESSION['table_id']]
    )->fetch();

    // Debug bilgileri - sadece development modunda
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<pre style='display:none;'>";
        echo "Session Table ID: " . htmlspecialchars($_SESSION['table_id']) . "\n";
        echo "Active Order: ";
        print_r($activeOrder);
        echo "</pre>";
    }
}

$theme_color = $settings['theme_color'] ?? '#e74c3c';
$theme_rgb = hexToRgb($theme_color);
// Header background güvenli şekilde ayarla
$header_bg = "url('/qr-menu/assets/images/bg-restaurant.jpg')"; // Varsayılan
if (isset($settings['header_bg']) && !empty($settings['header_bg'])) {
    $safe_header_bg = htmlspecialchars($settings['header_bg'], ENT_QUOTES, 'UTF-8');
    // Dosya adının güvenli olduğunu kontrol et
    if (preg_match('/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png|gif)$/i', $safe_header_bg)) {
        $header_bg = "url('/qr-menu/uploads/" . $safe_header_bg . "')";
    }
}


?>

<!DOCTYPE html>
<html lang="tr">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurant Menü', ENT_QUOTES, 'UTF-8') ?></title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
   <?php 
   // Dynamic theme CSS - TEK KAYNAK!
   $active_theme = $db->query("SELECT id FROM customer_themes WHERE is_active = 1 LIMIT 1")->fetch();
   if($active_theme): ?>
   <link href="admin/theme.css.php?theme=<?= $active_theme['id'] ?>&v=<?= time() ?>" rel="stylesheet" id="dynamic-theme">
   <?php else: ?>
   <!-- Fallback: Eğer tema yoksa varsayılan stiller -->
   <style>
     :root {
        --theme-primary: <?= $theme_color ?>;
        --theme-rgb: <?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>;
    }
   </style>
   <?php endif; ?>
   
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
   
   <!-- SADECE HEADER CSS - DİĞER HER ŞEY theme.css.php'de! -->
   <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    
    .hero-section {
        background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), <?= $header_bg ?> !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        padding: 25px 0 !important;
        color: white;
        margin-bottom: 0px !important;
        min-height: 140px !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .header-content {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 20px;
    }

    .logo-img {
        max-height: 90px;
        width: auto;
        border-radius: 50%;
        border: 3px solid white;
        transition: all 0.3s ease;
    }

    .logo-img:hover {
        transform: scale(1.05);
    }

    .restaurant-name {
        font-size: 2rem;
        margin: 0;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .restaurant-slogan {
        font-size: 1rem;
        margin: 0;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .restaurant-name {
            font-size: 1.5rem;
        }
        .restaurant-slogan {
            font-size: 0.9rem;
        }
        .logo-img {
            max-height: 70px;
        }
    }
    
    /* Floating Order Button */
    .floating-order-btn {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 1000;
        background: var(--theme-primary, #e74c3c);
        color: white;
        border: none;
        padding: 15px 25px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .floating-order-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        color: white;
    }

    .floating-order-btn i {
        font-size: 1.2rem;
    }

    @media (max-width: 768px) {
        .floating-order-btn span {
            display: none;
        }

        .floating-order-btn {
            padding: 15px;
            border-radius: 50%;
        }
    }
   </style>
</head>
<body>
<?php 
   // Sistem parametrelerini kontrol et
   $acceptOrders = isset($settings['system_accept_qr_orders']) && $settings['system_accept_qr_orders'] == '1';
   
   // Sadece sipariş alımı aktifse ve aktif sipariş varsa butonu göster
   if ($acceptOrders && isset($_SESSION['existing_order_id']) && $_SESSION['existing_order_id']): ?>
       <a href="orders.php?table=<?= $_SESSION['table_id'] ?>" class="floating-order-btn">
           <i class="fas fa-receipt"></i>
           <span>Siparişlerim</span>
       </a>
   <?php endif; ?>

    <div class="hero-section">
        <div class="header-content">
            <?php if (!empty($settings['restaurant_logo'])): ?>
            <img src="uploads/<?= htmlspecialchars($settings['restaurant_logo']) ?>" 
                 alt="Logo" 
                 class="logo-img">
            <?php endif; ?>
            <div class="text-center">
                <h1 class="restaurant-name"><?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurant', ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($settings['restaurant_slogan'])): ?>
                <p class="restaurant-slogan"><?= htmlspecialchars($settings['restaurant_slogan']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
