<?php
require_once 'includes/config.php';

$db = new Database();

// Get sample categories and products
$categories = $db->query("SELECT * FROM categories WHERE status = 1 LIMIT 6")->fetchAll();
$products = $db->query("SELECT * FROM products WHERE status = 1 LIMIT 8")->fetchAll();

// Sample theme configuration
$theme = [
    'primary_color' => '#e74c3c',
    'secondary_color' => '#c0392b', 
    'accent_color' => '#f39c12',
    'background_color' => '#f8f9fa',
    'text_color' => '#2c3e50',
    'font_family' => 'Poppins'
];

$theme_rgb = hexToRgb($theme['primary_color']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2-Column Layout Test - QR Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --theme-primary: <?= $theme['primary_color'] ?>;
            --theme-secondary: <?= $theme['secondary_color'] ?>;
            --theme-accent: <?= $theme['accent_color'] ?>;
            --theme-background: <?= $theme['background_color'] ?>;
            --theme-text: <?= $theme['text_color'] ?>;
            --theme-font: '<?= $theme['font_family'] ?>', sans-serif;
            --primary-rgb: <?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>;
        }
        
        body {
            font-family: var(--theme-font);
            background-color: var(--theme-background);
            color: var(--theme-text);
            padding: 20px 0;
        }
        
        .layout-demo {
            margin: 40px 0;
            padding: 20px;
            border: 2px solid var(--theme-primary);
            border-radius: 15px;
            background: white;
        }
        
        .layout-title {
            color: var(--theme-primary);
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background: rgba(var(--primary-rgb), 0.1);
            border-radius: 10px;
        }
        
        /* Category Grid 2-Column */
        .category-grid-2col {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 0;
            padding: 15px;
        }
        
        .category-grid-2col .modern-category-item {
            height: 180px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            background: linear-gradient(135deg, var(--theme-primary), rgba(var(--primary-rgb), 0.8));
            position: relative;
        }
        
        .category-grid-2col .modern-category-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.2);
        }
        
        .category-grid-2col .category-bg-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .category-grid-2col .modern-category-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            z-index: 2;
        }
        
        .category-grid-2col .modern-category-title {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }
        
        /* Category List 2-Column */
        .category-list-2col {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 15px;
        }
        
        .category-list-2col .modern-category-item {
            height: 100px !important;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background: white;
            border: 1px solid rgba(var(--primary-rgb), 0.1);
            transition: all 0.3s ease;
        }
        
        .category-list-2col .modern-category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.2);
            border-color: var(--theme-primary);
        }
        
        .category-list-2col .category-bg-image {
            width: 120px !important;
            height: 100px !important;
            flex-shrink: 0;
            object-fit: cover;
        }
        
        .category-list-2col .modern-category-content {
            padding: 15px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        
        .category-list-2col .modern-category-title {
            color: var(--theme-primary) !important;
            font-size: 1.1rem !important;
            margin: 0 !important;
            line-height: 1.2;
            font-weight: 600;
            text-align: left;
        }
        
        /* Product Grid 2-Column */
        .product-grid-2col {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 15px !important;
            padding: 15px;
        }
        
        .product-grid-2col .product-card {
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1) !important;
            transition: all 0.3s ease !important;
            background: white !important;
            border: 1px solid rgba(var(--primary-rgb), 0.1) !important;
        }
        
        .product-grid-2col .product-card:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.15) !important;
            border-color: var(--theme-primary) !important;
        }
        
        .product-grid-2col .product-image {
            width: 100% !important;
            height: 140px !important;
            object-fit: cover !important;
        }
        
        .product-grid-2col .product-info {
            padding: 15px !important;
        }
        
        .product-grid-2col .product-title {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            color: var(--theme-text) !important;
            margin-bottom: 8px !important;
            line-height: 1.3 !important;
            text-align: left !important;
        }
        
        .product-grid-2col .product-description {
            font-size: 0.9rem !important;
            color: rgba(var(--theme-text), 0.7) !important;
            margin-bottom: 10px !important;
            line-height: 1.4 !important;
            text-align: left !important;
        }
        
        .product-grid-2col .product-price {
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            color: var(--theme-primary) !important;
            text-align: left !important;
        }
        
        /* Product List 2-Column */
        .product-list-2col {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
            padding: 12px;
        }
        
        .product-list-2col .product-card {
            display: flex !important;
            flex-direction: column !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;
            background: white !important;
            transition: all 0.3s ease !important;
            height: 200px !important;
            border: 1px solid rgba(var(--primary-rgb), 0.1) !important;
        }
        
        .product-list-2col .product-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15) !important;
            border-color: var(--theme-primary) !important;
        }
        
        .product-list-2col .product-image {
            width: 100% !important;
            height: 110px !important;
            object-fit: cover !important;
            flex-shrink: 0 !important;
        }
        
        .product-list-2col .product-info {
            flex: 1 !important;
            padding: 12px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }
        
        .product-list-2col .product-title {
            font-size: 1rem !important;
            font-weight: 600 !important;
            color: var(--theme-text) !important;
            margin-bottom: 6px !important;
            line-height: 1.2 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            text-align: left !important;
        }
        
        .product-list-2col .product-description {
            font-size: 0.8rem !important;
            color: rgba(var(--theme-text), 0.6) !important;
            margin-bottom: 8px !important;
            line-height: 1.3 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            text-align: left !important;
        }
        
        .product-list-2col .product-price {
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            color: var(--theme-primary) !important;
            margin-top: auto !important;
            text-align: left !important;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .category-grid-2col, .category-list-2col, .product-grid-2col, .product-list-2col {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-5" style="color: var(--theme-primary);">
        <i class="fas fa-th me-3"></i>
        2-Column Layout Test - QR Menu
    </h1>
    
    <!-- Category Grid 2-Column -->
    <div class="layout-demo">
        <h2 class="layout-title">
            <i class="fas fa-th me-2"></i>
            Category Grid 2-Column (grid-2col)
        </h2>
        <div class="category-grid-2col">
            <?php foreach(array_slice($categories, 0, 4) as $category): ?>
                <div class="modern-category-item">
                    <img src="uploads/<?= $category['image'] ?>" class="category-bg-image" alt="<?= $category['name'] ?>">
                    <div class="modern-category-content">
                        <h3 class="modern-category-title"><?= htmlspecialchars($category['name']) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Category List 2-Column -->
    <div class="layout-demo">
        <h2 class="layout-title">
            <i class="fas fa-list me-2"></i>
            Category List 2-Column (list-2col)
        </h2>
        <div class="category-list-2col">
            <?php foreach(array_slice($categories, 0, 4) as $category): ?>
                <div class="modern-category-item">
                    <img src="uploads/<?= $category['image'] ?>" class="category-bg-image" alt="<?= $category['name'] ?>">
                    <div class="modern-category-content">
                        <h3 class="modern-category-title"><?= htmlspecialchars($category['name']) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Product Grid 2-Column -->
    <div class="layout-demo">
        <h2 class="layout-title">
            <i class="fas fa-th-large me-2"></i>
            Product Grid 2-Column (grid-2col)
        </h2>
        <div class="product-grid-2col">
            <?php foreach(array_slice($products, 0, 4) as $product): ?>
                <div class="product-card">
                    <img src="uploads/<?= $product['image'] ?>" class="product-image" alt="<?= $product['name'] ?>">
                    <div class="product-info">
                        <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                        <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Product List 2-Column -->
    <div class="layout-demo">
        <h2 class="layout-title">
            <i class="fas fa-list-ul me-2"></i>
            Product List 2-Column (list-2col)
        </h2>
        <div class="product-list-2col">
            <?php foreach(array_slice($products, 0, 6) as $product): ?>
                <div class="product-card">
                    <img src="uploads/<?= $product['image'] ?>" class="product-image" alt="<?= $product['name'] ?>">
                    <div class="product-info">
                        <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
                        <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <a href="admin/themes.php" class="btn btn-primary btn-lg me-3">
            <i class="fas fa-palette me-2"></i>Theme Management
        </a>
        <a href="index.php?table=1" class="btn btn-secondary btn-lg me-3">
            <i class="fas fa-eye me-2"></i>Customer Interface
        </a>
        <a href="fix_database.php" class="btn btn-warning btn-lg">
            <i class="fas fa-database me-2"></i>Fix Database
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>