<?php
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
        --primary-red: <?= $theme_color ?>;
        --primary-red-rgb: <?= implode(',', $theme_rgb) ?>;
        --dark-red: <?= adjustBrightness($theme_color, -20) ?>;
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