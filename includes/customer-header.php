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

    // Debug bilgileri
    echo "<pre style='display:none;'>";
    echo "Session Table ID: " . $_SESSION['table_id'] . "\n";
    echo "Active Order: ";
    print_r($activeOrder);
    echo "</pre>";
}

$theme_color = $settings['theme_color'] ?? '#e74c3c';
$theme_rgb = hexToRgb($theme_color);
$header_bg = isset($settings['header_bg']) && !empty($settings['header_bg']) 
    ? "url('/qr-menu/uploads/" . $settings['header_bg'] . "')"  // Tam yolu belirttik
    : "url('/qr-menu/assets/images/bg-restaurant.jpg')";


?>

<!DOCTYPE html>
<html lang="tr">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= $settings['restaurant_name'] ?? 'Restaurant Menü' ?></title>
   
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
   <style>
     :root {
        --theme-color: <?= $theme_color ?>;
        --theme-rgb: <?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>;
    }
    
    /* Genel Tema Rengi Uygulamaları */
    .text-primary,
    .text-theme {
        color: var(--theme-color) !important;
    }

    .bg-primary,
    .bg-theme {
        background-color: var(--theme-color) !important;
    }

    .border-primary,
    .border-theme {
        border-color: var(--theme-color) !important;
    }

    .btn-primary {
        background-color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
    }

    .btn-outline-primary {
        color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
    }

    .btn-outline-primary:hover {
        background-color: var(--theme-color) !important;
        color: #fff !important;
    }

    /* Badge ve Etiketler */
    .badge-primary,
    .badge-theme {
        background-color: var(--theme-color) !important;
    }

    /* Linkler */
    a.theme-link {
        color: var(--theme-color);
    }

    a.theme-link:hover {
        color: var(--theme-color);
        opacity: 0.8;
    }

    /* Form Elemanları */
    .form-control:focus {
        border-color: var(--theme-color);
        box-shadow: 0 0 0 0.2rem rgba(var(--theme-rgb), 0.25);
    }

    /* Özel Gölgeler */
    .shadow-theme {
        box-shadow: 0 0.5rem 1rem rgba(var(--theme-rgb), 0.15) !important;
    }

    /* Progress Bars */
    .progress-bar-theme {
        background-color: var(--theme-color);
    }

    /* Hover Efektleri */
    .hover-theme:hover {
        color: var(--theme-color) !important;
    }

    /* Aktif Durumlar */
    .active-theme.active {
        color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
    }

    /* Özel Butonlar */
    .floating-order-btn {
        background-color: var(--theme-color) !important;
    }

    .floating-order-btn:hover {
        background-color: var(--theme-color) !important;
        opacity: 0.9;
    }

    /* Modal ve Dialog */
    .modal-header-theme {
        border-bottom: 2px solid var(--theme-color);
    }

    /* Navigasyon */
    .nav-link.active {
        color: var(--theme-color) !important;
        border-color: var(--theme-color) !important;
    }

    /* Kaydırma Çubuğu */
    ::-webkit-scrollbar-thumb {
        background-color: var(--theme-color);
    }

    /* Seçim Rengi */
    ::selection {
        background-color: var(--theme-color);
        color: #fff;
    }

    /* Diğer stiller aynı kalacak */

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }
    .load{
        margin-bottom: 50px;
    }
    .hero-section {
        background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), <?= $header_bg ?> !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        padding: 60px 0px !important;
        color: white;
        text-align: center;
        margin-bottom: 0px !important;
        height: 200px !important;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 4px solid var(--primary-red);
    }
    .category-card {
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 0px;
        cursor: pointer;
        transition: transform 0.3s;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border: 1px solid rgba(231, 76, 60, 0.1);
    }

    .category-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary-red);
    }

    .category-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, var(--primary-red));
        padding: 20px;
        color: white;
    }

    .menu-item {
        display: flex;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s;
        border-left: 4px solid transparent;
    }

    .menu-item:hover {
        transform: translateY(-3px);
        border-left-color: var(--primary-red);
    }

    .menu-item-price {
        font-weight: 600;
        color: var(--primary-red);
        font-size: 1.2rem;
        display: inline-block;
        padding: 5px 15px;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 20px;
    }

    .back-button {
        background: var(--primary-red);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 30px;
        margin-bottom: 20px;
        transition: all 0.3s;
        display: inline-block;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(231, 76, 60, 0.2);
    }

    .back-button:hover {
        background: var(--dark-red);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }

    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 30px;
        color: #2c3e50;
        font-weight: 600;
    }

    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: var(--primary-red);
    }

    .menu-item-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0 0 5px 0;
        color: #2c3e50;
    }

    .menu-item-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 10px;
        line-height: 1.6;
    }

    /* Badge stil */
    .special-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--primary-red);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .logo-img {
   width: 100px;  /* Genişlik */
   height: 100px; /* Yükseklik */
   object-fit: contain; /* Resmi oranını koruyarak sığdır */
   margin: 0 auto 0px; /* Ortalama için */
   display: block; /* Block element yap */
  
}

.floating-order-btn {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 1000;
    background: var(--primary-red);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(var(--primary-red-rgb), 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.floating-order-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(var(--primary-red-rgb), 0.4);
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

/* Sepet Butonu */
.cart-button {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Geri Butonu */
.back-button {
    color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

.back-button:hover {
    background-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Sepete Ekle Butonu */
.add-to-cart {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Miktar Artır/Azalt Butonları */
.quantity-btn {
    color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

.quantity-btn:hover,
.quantity-btn.plus:hover {
    background-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Ürün Fiyatı */
.menu-item-price {
    color: var(--theme-color) !important;
}

/* Sepet Rozeti */
.cart-badge {
    background-color: var(--theme-color) !important;
}

/* Miktar Rozeti */
.quantity-badge {
    background-color: var(--theme-color) !important;
}

/* Aktif Kategori */
.category-btn.active {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Sipariş Ver Butonu */
.order-button {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Toplam Fiyat */
.total-price {
    color: var(--theme-color) !important;
}

/* Ürün Kartı Hover */
.product-card:hover {
    border-color: var(--theme-color) !important;
}

/* Sipariş Durumu Badge */
.status-badge {
    background-color: var(--theme-color) !important;
}

/* Kategoriye Dön Butonu */
.category-back-btn {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
    color: #fff !important;
}

.category-back-btn:hover {
    opacity: 0.9;
}

/* Yüzen Sepet Butonu */
.cart-floating-button {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
    color: #fff !important;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(var(--theme-rgb), 0.3);
}

.cart-floating-button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(var(--theme-rgb), 0.4);
}

.cart-floating-button .cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #fff !important;
    color: var(--theme-color) !important;
    border: 2px solid var(--theme-color);
}

/* Tüm Kırmızı Vurguların Dinamikleştirilmesi */

/* Butonlar */
.btn-danger,
.btn-red,
.delete-btn,
.remove-item {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Text Renkleri */
.text-danger,
.text-red,
.error-text,
.required-field,
.price-text,
.discount-text {
    color: var(--theme-color) !important;
}

/* Kenarlıklar */
.border-danger,
.border-red,
.highlight-border {
    border-color: var(--theme-color) !important;
}

/* Arkaplan Renkleri */
.bg-danger,
.bg-red,
.alert-danger,
.status-urgent {
    background-color: var(--theme-color) !important;
}

/* Form Elemanları */
.form-control:focus {
    border-color: var(--theme-color) !important;
    box-shadow: 0 0 0 0.2rem rgba(var(--theme-rgb), 0.25) !important;
}

/* Seçim Rengi */
::selection {
    background-color: var(--theme-color);
    color: #fff;
}

/* Alert ve Bildirimler */
.alert-danger {
    border-left: 4px solid var(--theme-color);
    background-color: rgba(var(--theme-rgb), 0.1);
    color: var(--theme-color);
}

/* İkonlar */
.text-danger i,
.red-icon {
    color: var(--theme-color) !important;
}

/* Hover Efektleri */
.hover-danger:hover,
.hover-red:hover {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Özel Elementler */
.special-price,
.discount-badge,
.hot-item,
.featured-tag {
    background-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Validation */
.is-invalid {
    border-color: var(--theme-color) !important;
}

.invalid-feedback {
    color: var(--theme-color) !important;
}

/* Progress Bars */
.progress-bar-danger {
    background-color: var(--theme-color) !important;
}

/* Liste İşaretleri */
.list-danger li::before {
    color: var(--theme-color) !important;
}

/* Tooltip */
.tooltip-danger .tooltip-inner {
    background-color: var(--theme-color) !important;
}

/* Checkbox ve Radio */
.form-check-input:checked {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Switch */
.form-switch .form-check-input:checked {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

/* Input Group */
.input-group-text-danger {
    background-color: var(--theme-color) !important;
    color: #fff !important;
}

/* Pagination */
.page-item.active .page-link {
    background-color: var(--theme-color) !important;
    border-color: var(--theme-color) !important;
}

</style>
</head>
<body>
   <div class="hero-section">
       <div class="container">
           <?php if(isset($settings['logo']) && !empty($settings['logo'])): ?>
               <img src="uploads/<?= $settings['logo'] ?>" alt="Logo" class="logo-img">
           <?php endif; ?>
           <h1><?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurant İsmi') ?></h1>
           <p class="lead">Lezzetli yemeklerimizi keşfedin</p>
       </div>
   </div>

   <?php 
   // Sadece aktif sipariş varsa butonu göster
   if ($activeOrder): ?>
       <a href="orders.php?table=<?= $_SESSION['table_id'] ?>" class="floating-order-btn">
           <i class="fas fa-receipt"></i>
           <span>Siparişlerim</span>
       </a>
   <?php endif; ?>