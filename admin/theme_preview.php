<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('settings.view')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Tema aktivasyon
if(isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    // Önce tüm temaları pasif yap
    $db->query("UPDATE customer_themes SET is_active = 0");
    // Seçilen temayı aktif yap
    $db->query("UPDATE customer_themes SET is_active = 1 WHERE id = ?", [$_GET['activate']]);
    
    // Aktif temanın renklerini settings tablosuna kopyala
    $active_theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$_GET['activate']])->fetch();
    if($active_theme) {
        $db->query("INSERT INTO settings (setting_key, setting_value) 
                   VALUES ('theme_color', ?) ON DUPLICATE KEY UPDATE setting_value = ?", 
                   [$active_theme['primary_color'], $active_theme['primary_color']]);
        
        $db->query("INSERT INTO settings (setting_key, setting_value) 
                   VALUES ('active_theme_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?", 
                   [$_GET['activate'], $_GET['activate']]);
    }
    
    $_SESSION['message'] = 'Tema aktif edildi ve ana sayfaya uygulandı.';
    $_SESSION['message_type'] = 'success';
    header('Location: themes.php');
    exit;
}

// Preview theme ID
$preview_theme_id = isset($_GET['theme']) ? (int)$_GET['theme'] : null;

if ($preview_theme_id) {
    $preview_theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$preview_theme_id])->fetch();
    if (!$preview_theme) {
        header('Location: themes.php');
        exit();
    }
}

// Kategorileri çek
$categories = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC LIMIT 6")->fetchAll();

// Ayarları çek
$settingsResult = $db->query("SELECT * FROM settings");
$settings = array();
foreach($settingsResult->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$theme_color = $preview_theme ? $preview_theme['primary_color'] : ($settings['theme_color'] ?? '#e74c3c');
$theme_rgb = hexToRgb($theme_color);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tema Önizleme - <?= $preview_theme ? $preview_theme['name'] : 'Varsayılan' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <?php if($preview_theme_id): ?>
    <link href="theme.css.php?theme=<?= $preview_theme_id ?>" rel="stylesheet">
    <?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        .preview-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px 0;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }
        
        .preview-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .theme-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .color-dots {
            display: flex;
            gap: 5px;
        }
        
        .color-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .preview-body {
            margin-top: 60px;
        }
        
        /* Override existing styles for preview */
        body {
            font-family: <?= $preview_theme ? "'{$preview_theme['font_family']}', sans-serif" : "'Poppins', sans-serif" ?> !important;
            background-color: <?= $preview_theme ? $preview_theme['background_color'] : '#f8f9fa' ?> !important;
            color: <?= $preview_theme ? $preview_theme['text_color'] : '#2c3e50' ?> !important;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('../assets/images/restaurant-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 25px 0;
            color: white;
            min-height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px solid <?= $theme_color ?>;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
            padding: 0 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            justify-content: center;
        }
        
        .logo-container {
            flex-shrink: 0;
            margin-right: 25px;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 15px;
            background: rgba(255,255,255,0.9);
            padding: 8px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            border: 3px solid rgba(255,255,255,0.3);
        }
        
        .restaurant-info h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 3px 6px rgba(0,0,0,0.6);
            margin-left: 10px;
        }
        
        .category-section {
            padding: 0;
            background: <?= $preview_theme ? $preview_theme['background_color'] : '#f8f9fa' ?>;
            margin-top: 30px;
            position: relative;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
            border-top: 1px solid rgba(<?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>, 0.1);
        }
        
        .category-section::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, transparent, <?= $theme_color ?>, transparent);
            border-radius: 2px;
        }
        
        .modern-category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0;
            margin: 0;
            padding: 0;
        }
        
        .modern-category-item {
            position: relative;
            height: 180px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, <?= $theme_color ?>, rgba(<?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>, 0.8));
        }
        
        .modern-category-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(0,0,0,0.3) 100%);
            z-index: 2;
            transition: opacity 0.4s ease;
        }
        
        .modern-category-item:hover {
            transform: scale(1.05) translateZ(0);
            z-index: 10;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .modern-category-item:hover::before {
            opacity: 0.5;
        }
        
        .category-bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }
        
        .modern-category-item:hover .category-bg-image {
            transform: scale(1.1);
        }
        
        .modern-category-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 25px;
            color: white;
            z-index: 3;
            background: linear-gradient(transparent, rgba(0,0,0,0.8) 60%);
            transform: translateY(10px);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modern-category-item:hover .modern-category-content {
            transform: translateY(0);
        }
        
        .modern-category-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            letter-spacing: 0.5px;
        }
        
        /* Theme specific colors */
        .btn-primary {
            background-color: <?= $theme_color ?> !important;
            border-color: <?= $theme_color ?> !important;
        }
        
        .btn-primary:hover {
            background-color: <?= $preview_theme ? $preview_theme['secondary_color'] : '#c0392b' ?> !important;
            border-color: <?= $preview_theme ? $preview_theme['secondary_color'] : '#c0392b' ?> !important;
        }
        
        .text-primary {
            color: <?= $theme_color ?> !important;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(<?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>, 0.15);
        }
        
        /* Concept-specific preview styles */
        <?php if($preview_theme): ?>
            <?php if($preview_theme['concept'] === 'modern'): ?>
                .modern-category-item {
                    transform: skew(-2deg) !important;
                }
                .modern-category-item:hover {
                    transform: skew(-2deg) scale(1.05) !important;
                }
                .modern-category-content {
                    transform: skew(2deg) translateY(10px) !important;
                }
                .modern-category-title {
                    text-transform: uppercase !important;
                    letter-spacing: 2px !important;
                    font-weight: 800 !important;
                }
            <?php elseif($preview_theme['concept'] === 'elegant'): ?>
                .modern-category-item {
                    border-radius: 20px !important;
                    border: 2px solid rgba(255,255,255,0.3) !important;
                    backdrop-filter: blur(20px) !important;
                }
                .modern-category-title {
                    font-weight: 300 !important;
                    font-style: italic !important;
                }
            <?php elseif($preview_theme['concept'] === 'luxury'): ?>
                .modern-category-item {
                    border: 3px solid <?= $preview_theme['accent_color'] ?> !important;
                    border-radius: 0 !important;
                }
                .modern-category-title {
                    color: <?= $preview_theme['accent_color'] ?> !important;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.8) !important;
                    text-transform: capitalize !important;
                }
            <?php elseif($preview_theme['concept'] === 'minimal'): ?>
                .modern-category-item {
                    background: <?= $preview_theme['background_color'] ?> !important;
                    border: 1px solid <?= $preview_theme['primary_color'] ?> !important;
                    border-radius: 0 !important;
                }
                .modern-category-content {
                    background: transparent !important;
                    color: <?= $preview_theme['text_color'] ?> !important;
                }
                .modern-category-title {
                    text-transform: lowercase !important;
                    font-weight: 400 !important;
                }
            <?php elseif($preview_theme['concept'] === 'vintage'): ?>
                .modern-category-item {
                    border: 3px solid rgba(255,255,255,0.8) !important;
                    filter: sepia(20%) saturate(80%) !important;
                }
                .modern-category-title {
                    font-family: serif !important;
                }
            <?php elseif($preview_theme['concept'] === 'corporate'): ?>
                .modern-category-item {
                    border-radius: 5px !important;
                    border-left: 5px solid <?= $preview_theme['accent_color'] ?> !important;
                }
                .modern-category-title {
                    text-transform: uppercase !important;
                    letter-spacing: 1px !important;
                }
            <?php endif; ?>
        <?php endif; ?>
    </style>
</head>
<body>

<!-- Preview Header -->
<div class="preview-header">
    <div class="container">
        <div class="preview-controls">
            <div class="theme-info">
                <i class="fas fa-eye me-2"></i>
                <strong>Tema Önizleme:</strong>
                <span><?= $preview_theme ? $preview_theme['name'] : 'Varsayılan Tema' ?></span>
                <span class="badge bg-secondary"><?= $preview_theme ? ucfirst($preview_theme['concept']) : 'Modern' ?></span>
                
                <?php if($preview_theme): ?>
                <div class="color-dots">
                    <div class="color-dot" style="background: <?= $preview_theme['primary_color'] ?>;" title="Ana Renk"></div>
                    <div class="color-dot" style="background: <?= $preview_theme['secondary_color'] ?>;" title="İkincil Renk"></div>
                    <div class="color-dot" style="background: <?= $preview_theme['accent_color'] ?>;" title="Vurgu Rengi"></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="preview-actions">
                <button class="btn btn-outline-light btn-sm me-2" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Yenile
                </button>
                
                <?php if($preview_theme): ?>
                <a href="?activate=<?= $preview_theme['id'] ?>" class="btn btn-success btn-sm me-2" 
                   onclick="return confirm('Bu temayı aktif etmek ve müşteri arayüzüne uygulamak istediğinizden emin misiniz?')">
                    <i class="fas fa-check me-1"></i>Bu Temayı Aktif Et
                </a>
                <a href="themes.php?edit=<?= $preview_theme['id'] ?>" class="btn btn-outline-warning btn-sm me-2">
                    <i class="fas fa-edit me-1"></i>Düzenle
                </a>
                <?php endif; ?>
                
                <a href="themes.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Preview Body -->
<div class="preview-body">
    <!-- Header -->
    <div class="hero-section">
        <div class="header-content">
            <div class="header-left">
                <?php if(isset($settings['logo']) && !empty($settings['logo'])): ?>
                    <div class="logo-container">
                        <img src="../uploads/<?= $settings['logo'] ?>" alt="Logo" class="logo-img">
                    </div>
                <?php endif; ?>
                <div class="restaurant-info">
                    <h1><?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurant İsmi') ?></h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="category-section">
        <div class="modern-category-grid">
            <?php foreach($categories as $index => $category): ?>
                <div class="modern-category-item">
                    <img src="../uploads/<?= $category['image'] ?>" class="category-bg-image" alt="<?= $category['name'] ?>">
                    <div class="modern-category-content">
                        <h3 class="modern-category-title"><?= htmlspecialchars($category['name']) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Sample Product Section -->
    <div class="container mt-5">
        <h3 class="mb-4" style="color: <?= $theme_color ?>;">Örnek Ürün Görünümü</h3>
        <div class="row">
            <div class="col-md-4">
                <div class="card h-100">
                    <?php if(!empty($categories[0]['image'])): ?>
                        <img src="../uploads/<?= $categories[0]['image'] ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Örnek Ürün">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: <?= $theme_color ?>; color: white;">
                            <i class="fas fa-utensils fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">Örnek Yemek</h5>
                        <p class="card-text" style="color: <?= $preview_theme ? $preview_theme['text_color'] : '#666' ?>;">Bu tema ile nasıl göründüğünün örneği. Menü öğeleri bu şekilde görünecektir.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-primary">25.00 ₺</span>
                            <button class="btn btn-primary">
                                <i class="fas fa-cart-plus me-1"></i>Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <?php if(!empty($categories[1]['image'])): ?>
                        <img src="../uploads/<?= $categories[1]['image'] ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Örnek Ürün">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: <?= $preview_theme ? $preview_theme['secondary_color'] : '#c0392b' ?>; color: white;">
                            <i class="fas fa-coffee fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">İçecek Örneği</h5>
                        <p class="card-text" style="color: <?= $preview_theme ? $preview_theme['text_color'] : '#666' ?>;">İçecekler ve diğer ürünlerin bu temada nasıl görüneceği.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-primary">15.00 ₺</span>
                            <button class="btn btn-primary">
                                <i class="fas fa-cart-plus me-1"></i>Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <?php if(!empty($categories[2]['image'])): ?>
                        <img src="../uploads/<?= $categories[2]['image'] ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="Örnek Ürün">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px; background: <?= $preview_theme ? $preview_theme['accent_color'] : '#f39c12' ?>; color: white;">
                            <i class="fas fa-pizza-slice fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">Tatlı Örneği</h5>
                        <p class="card-text" style="color: <?= $preview_theme ? $preview_theme['text_color'] : '#666' ?>;">Tatlılar ve özel ürünlerin tema görünümü.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0" style="color: <?= $preview_theme ? $preview_theme['accent_color'] : '#f39c12' ?>;">35.00 ₺</span>
                            <button class="btn" style="background: <?= $preview_theme ? $preview_theme['accent_color'] : '#f39c12' ?>; color: white; border: none;">
                                <i class="fas fa-cart-plus me-1"></i>Sepete Ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="height: 100px;"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>