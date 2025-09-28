<?php
require_once '../includes/config.php';

// Theme CSS Generator
function generateThemeCSS($theme_id = null) {
    $db = new Database();
    
    // Aktif temayı al
    if ($theme_id) {
        $theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$theme_id])->fetch();
    } else {
        $theme = $db->query("SELECT * FROM customer_themes WHERE is_active = 1 LIMIT 1")->fetch();
    }
    
    // Varsayılan tema yoksa default değerler
    if (!$theme) {
        $theme = [
            'concept' => 'modern',
            'primary_color' => '#e74c3c',
            'secondary_color' => '#c0392b',
            'accent_color' => '#f39c12',
            'background_color' => '#f8f9fa',
            'text_color' => '#2c3e50',
            'font_family' => 'Poppins',
            'category_style' => 'grid',
            'header_style' => 'modern'
        ];
    }
    
    // RGB değerlerini hesapla
    $primary_rgb = hexToRgb($theme['primary_color']);
    $secondary_rgb = hexToRgb($theme['secondary_color']);
    $accent_rgb = hexToRgb($theme['accent_color']);
    
    // CSS content-type header
    header('Content-Type: text/css');
    
    // CSS içeriğini oluştur
    echo generateThemeCSS_Content($theme, $primary_rgb, $secondary_rgb, $accent_rgb);
}

function generateThemeCSS_Content($theme, $primary_rgb, $secondary_rgb, $accent_rgb) {
    $css = "
/* Dynamic Theme CSS - {$theme['concept']} */
@import url('https://fonts.googleapis.com/css2?family={$theme['font_family']}:wght@300;400;500;600;700&display=swap');

:root {
    --theme-primary: {$theme['primary_color']};
    --theme-secondary: {$theme['secondary_color']};
    --theme-accent: {$theme['accent_color']};
    --theme-background: {$theme['background_color']};
    --theme-text: {$theme['text_color']};
    --theme-font: '{$theme['font_family']}', sans-serif;
    --primary-rgb: {$primary_rgb['r']}, {$primary_rgb['g']}, {$primary_rgb['b']};
    --secondary-rgb: {$secondary_rgb['r']}, {$secondary_rgb['g']}, {$secondary_rgb['b']};
    --accent-rgb: {$accent_rgb['r']}, {$accent_rgb['g']}, {$accent_rgb['b']};
}

/* Global Styles */
body {
    font-family: var(--theme-font) !important;
    background-color: var(--theme-background) !important;
    color: var(--theme-text) !important;
}

/* Header Styles */
.hero-section {
    border-bottom: 3px solid var(--theme-primary) !important;
}

.logo-img:hover {
    box-shadow: 0 12px 35px rgba(var(--primary-rgb), 0.3) !important;
}

/* Category Section */
.category-section::before {
    background: linear-gradient(90deg, transparent, var(--theme-primary), transparent) !important;
}

.category-section {
    border-top: 1px solid rgba(var(--primary-rgb), 0.1) !important;
}

.modern-category-item {
    background: linear-gradient(135deg, var(--theme-primary), rgba(var(--primary-rgb), 0.8)) !important;
}
";

    // Category style specific CSS
    if($theme['category_style'] === 'masonry') {
        $css .= "
.modern-category-grid {
    columns: 3;
    column-gap: 0;
    column-fill: balance;
}

.modern-category-item {
    break-inside: avoid;
    margin-bottom: 0;
    page-break-inside: avoid;
    height: auto !important;
    min-height: 150px;
}

@media (max-width: 768px) {
    .modern-category-grid {
        columns: 2;
    }
}

@media (max-width: 576px) {
    .modern-category-grid {
        columns: 1;
    }
}
";
    } elseif($theme['category_style'] === 'grid-2col') {
        $css .= "
/* Category 2-Column Grid Style */
.modern-category-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 15px;
    margin: 0;
    padding: 15px;
}

.modern-category-item {
    height: 180px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.4s ease;
}

.modern-category-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.2);
}

@media (max-width: 768px) {
    .modern-category-grid {
        grid-template-columns: 1fr !important;
        gap: 10px;
        padding: 10px;
    }
}
";
    } elseif($theme['category_style'] === 'list') {
        $css .= "
/* Category List Style */
.modern-category-grid {
    display: block !important;
}

.modern-category-item {
    height: 120px !important;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    background: white;
    border: 1px solid rgba(var(--primary-rgb), 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.modern-category-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(var(--primary-rgb), 0.15);
    border-color: var(--theme-primary);
}

.category-bg-image {
    width: 180px !important;
    height: 120px !important;
    flex-shrink: 0;
    object-fit: cover;
    position: static !important;
}

.modern-category-content {
    position: static !important;
    background: transparent !important;
    color: var(--theme-text) !important;
    padding: 20px !important;
    transform: none !important;
    flex: 1;
    display: flex;
    align-items: center;
    z-index: 3;
}

.modern-category-title {
    color: var(--theme-primary) !important;
    font-size: 1.3rem !important;
    margin: 0 !important;
    text-shadow: none !important;
    font-weight: 600;
    text-align: left;
}
";
    } elseif($theme['category_style'] === 'list-2col') {
        $css .= "
/* Category 2-Column List Style */
.modern-category-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 15px;
}

.modern-category-item {
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

.modern-category-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.2);
    border-color: var(--theme-primary);
}

.category-bg-image {
    width: 120px !important;
    height: 100px !important;
    flex-shrink: 0;
    object-fit: cover;
}

.modern-category-content {
    position: static !important;
    background: transparent !important;
    color: var(--theme-text) !important;
    padding: 15px;
    transform: none !important;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.modern-category-title {
    color: var(--theme-primary) !important;
    font-size: 1.1rem !important;
    margin: 0 !important;
    text-shadow: none !important;
    line-height: 1.2;
    font-weight: 600;
    text-align: left;
}

/* Mobile adjustments for category list-2col */
@media (max-width: 768px) {
    .modern-category-grid {
        grid-template-columns: 1fr !important;
        gap: 10px;
        padding: 10px;
    }
    
    .modern-category-item {
        height: 80px !important;
    }
    
    .category-bg-image {
        width: 100px !important;
        height: 80px !important;
    }
    
    .modern-category-content {
        padding: 10px;
    }
    
    .modern-category-title {
        font-size: 1rem !important;
    }
}

@media (max-width: 576px) {
    .modern-category-item {
        height: 70px !important;
    }
    
    .category-bg-image {
        width: 80px !important;
        height: 70px !important;
    }
    
    .modern-category-title {
        font-size: 0.9rem !important;
    }
}
";
    }

    // Product layout specific CSS
    if(isset($theme['product_layout'])) {
        if($theme['product_layout'] === 'grid-2col') {
            $css .= "
/* Product 2-Column Grid Style */
.products-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 15px !important;
    padding: 15px;
}

.product-card {
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1) !important;
    transition: all 0.3s ease !important;
    background: white !important;
    border: 1px solid rgba(var(--primary-rgb), 0.1) !important;
}

.product-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.15) !important;
    border-color: var(--theme-primary) !important;
}

.product-image {
    width: 100% !important;
    height: 140px !important;
    object-fit: cover !important;
}

.product-info {
    padding: 15px !important;
}

.product-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: var(--theme-text) !important;
    margin-bottom: 8px !important;
    line-height: 1.3 !important;
    text-align: left !important;
}

.product-description {
    font-size: 0.9rem !important;
    color: rgba(var(--theme-text), 0.7) !important;
    margin-bottom: 10px !important;
    line-height: 1.4 !important;
    text-align: left !important;
}

.product-price {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    color: var(--theme-primary) !important;
    text-align: left !important;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
        padding: 10px;
    }
    
    .product-image {
        height: 120px !important;
    }
    
    .product-info {
        padding: 12px !important;
    }
    
    .product-title {
        font-size: 1rem !important;
    }
    
    .product-description {
        font-size: 0.85rem !important;
    }
    
    .product-price {
        font-size: 1.1rem !important;
    }
}
";
        } elseif($theme['product_layout'] === 'list') {
            $css .= "
/* Product List Style */
.products-grid {
    display: block !important;
}

.product-card {
    display: flex !important;
    align-items: center !important;
    margin-bottom: 15px !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    background: white !important;
    transition: all 0.3s ease !important;
    border: 1px solid rgba(var(--primary-rgb), 0.1) !important;
}

.product-card:hover {
    transform: translateX(5px) !important;
    box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.15) !important;
    border-color: var(--theme-primary) !important;
}

.product-image {
    width: 140px !important;
    height: 120px !important;
    object-fit: cover !important;
    flex-shrink: 0 !important;
}

.product-info {
    flex: 1 !important;
    padding: 20px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}

.product-title {
    font-size: 1.3rem !important;
    font-weight: 600 !important;
    color: var(--theme-text) !important;
    margin-bottom: 8px !important;
    line-height: 1.3 !important;
    text-align: left !important;
}

.product-description {
    font-size: 1rem !important;
    color: rgba(var(--theme-text), 0.7) !important;
    margin-bottom: 10px !important;
    line-height: 1.4 !important;
    text-align: left !important;
}

.product-price {
    font-size: 1.4rem !important;
    font-weight: 700 !important;
    color: var(--theme-primary) !important;
    text-align: left !important;
}

@media (max-width: 576px) {
    .product-image {
        width: 100px !important;
        height: 100px !important;
    }
    
    .product-info {
        padding: 15px !important;
    }
    
    .product-title {
        font-size: 1.1rem !important;
    }
    
    .product-description {
        font-size: 0.9rem !important;
    }
    
    .product-price {
        font-size: 1.2rem !important;
    }
}
";
        } elseif($theme['product_layout'] === 'list-2col') {
            $css .= "
/* Product 2-Column List Style */
.products-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 12px !important;
    padding: 12px;
}

.product-card {
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

.product-card:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15) !important;
    border-color: var(--theme-primary) !important;
}

.product-image {
    width: 100% !important;
    height: 110px !important;
    object-fit: cover !important;
    flex-shrink: 0 !important;
}

.product-info {
    flex: 1 !important;
    padding: 12px !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
}

.product-title {
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

.product-description {
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

.product-price {
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    color: var(--theme-primary) !important;
    margin-top: auto !important;
    text-align: left !important;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
        padding: 10px;
    }
    
    .product-card {
        display: flex !important;
        flex-direction: row !important;
        height: 100px !important;
    }
    
    .product-image {
        width: 100px !important;
        height: 100px !important;
    }
    
    .product-info {
        padding: 10px !important;
    }
    
    .product-title {
        font-size: 0.95rem !important;
        -webkit-line-clamp: 1 !important;
    }
    
    .product-description {
        font-size: 0.75rem !important;
        -webkit-line-clamp: 1 !important;
    }
    
    .product-price {
        font-size: 1rem !important;
    }
}

@media (max-width: 576px) {
    .product-card {
        height: 90px !important;
    }
    
    .product-image {
        width: 90px !important;
        height: 90px !important;
    }
    
    .product-title {
        font-size: 0.9rem !important;
    }
    
    .product-description {
        display: none !important;
    }
    
    .product-price {
        font-size: 0.95rem !important;
    }
}
";
        }
    }

    // Konsept-specific styles (kategori stillerinden sonra uygulanır)
    switch($theme['concept']) {
        case 'modern':
            $css .= "
/* Modern Theme Specific */
.modern-category-item {
    border-radius: 0 !important;
    transform: skew(-2deg) !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.modern-category-item:hover {
    transform: skew(-2deg) scale(1.05) !important;
    box-shadow: 0 25px 50px rgba(var(--primary-rgb), 0.4) !important;
}

.modern-category-content {
    transform: skew(2deg) translateY(10px) !important;
}

.modern-category-title {
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 2px !important;
}
";
            break;
            
        case 'elegant':
            $css .= "
/* Elegant Theme Specific */
.modern-category-item {
    border-radius: 20px !important;
    border: 2px solid rgba(255,255,255,0.3) !important;
    backdrop-filter: blur(20px) !important;
}

.modern-category-item:hover {
    transform: translateY(-10px) !important;
    box-shadow: 0 30px 60px rgba(var(--primary-rgb), 0.3) !important;
}

.modern-category-title {
    font-weight: 300 !important;
    font-style: italic !important;
    text-shadow: 0 4px 8px rgba(0,0,0,0.3) !important;
}

.hero-section {
    background-attachment: fixed !important;
}
";
            break;
            
        case 'luxury':
            $css .= "
/* Luxury Theme Specific */
.modern-category-item {
    border-radius: 0 !important;
    border: 3px solid var(--theme-accent) !important;
    position: relative;
    overflow: hidden;
}

.modern-category-item::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
}

.modern-category-item:hover::after {
    animation: luxuryShine 1.5s ease-in-out;
}

@keyframes luxuryShine {
    0% { transform: translateX(-100%) rotate(45deg); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateX(100%) rotate(45deg); opacity: 0; }
}

.modern-category-title {
    font-weight: 700 !important;
    text-transform: capitalize !important;
    color: var(--theme-accent) !important;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8) !important;
}
";
            break;
            
        case 'minimal':
            $css .= "
/* Minimal Theme Specific */
.modern-category-item {
    border-radius: 0 !important;
    background: var(--theme-background) !important;
    border: 1px solid var(--theme-primary) !important;
    box-shadow: none !important;
}

.modern-category-item:hover {
    background: var(--theme-primary) !important;
    transform: none !important;
    box-shadow: none !important;
}

.modern-category-content {
    background: transparent !important;
    color: var(--theme-text) !important;
}

.modern-category-item:hover .modern-category-content {
    color: white !important;
}

.modern-category-title {
    font-weight: 400 !important;
    text-transform: lowercase !important;
    font-size: 1.2rem !important;
}
";
            break;
            
        case 'vintage':
            $css .= "
/* Vintage Theme Specific */
.modern-category-item {
    border-radius: 15px !important;
    border: 3px solid rgba(255,255,255,0.8) !important;
    filter: sepia(20%) saturate(80%) !important;
    position: relative;
}

.modern-category-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at center, transparent 40%, rgba(139,69,19,0.2) 100%);
    z-index: 2;
}

.modern-category-title {
    font-family: 'serif' !important;
    font-weight: 400 !important;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8) !important;
}
";
            break;
            
        case 'corporate':
            $css .= "
/* Corporate Theme Specific */
.modern-category-item {
    border-radius: 5px !important;
    background: linear-gradient(180deg, var(--theme-primary), var(--theme-secondary)) !important;
    border-left: 5px solid var(--theme-accent) !important;
}

.modern-category-item:hover {
    transform: translateX(10px) !important;
    border-left-width: 10px !important;
}

.modern-category-title {
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
}
";
            break;
    }
    $css .= "
/* Common Theme Applications */
.btn-primary, .add-to-cart, .cart-floating-button {
    background-color: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
}

.btn-primary:hover, .add-to-cart:hover, .cart-floating-button:hover {
    background-color: var(--theme-secondary) !important;
}

.text-primary, .menu-item-price, .product-price {
    color: var(--theme-primary) !important;
}

.border-primary {
    border-color: var(--theme-primary) !important;
}

.bg-primary {
    background-color: var(--theme-primary) !important;
}

.category-header {
    background: var(--theme-primary) !important;
}

.back-button {
    background: var(--theme-primary) !important;
}

.quantity-btn:hover {
    background: var(--theme-primary) !important;
    color: white !important;
}

::selection {
    background-color: var(--theme-primary) !important;
    color: white !important;
}

::-webkit-scrollbar-thumb {
    background-color: var(--theme-primary) !important;
}
";

    return $css;
}

// Eğer dosya doğrudan çağrılıyorsa CSS oluştur
if (basename($_SERVER['PHP_SELF']) === 'theme.css.php') {
    $theme_id = isset($_GET['theme']) ? (int)$_GET['theme'] : null;
    generateThemeCSS($theme_id);
}
?>