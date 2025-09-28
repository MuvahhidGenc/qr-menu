<?php
require_once 'includes/config.php';
require_once 'includes/cart.php';
// Session ve sepeti başlat
initCart();

$db = new Database();
// Debug log'ları sadece development modunda
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log('Session ID: ' . session_id());
    error_log('Cart Contents: ' . print_r($_SESSION['cart'], true));
}

// Kategorileri çek - sort_order'a göre sıralama
$stmt = $db->query(
    "SELECT * FROM categories 
     WHERE status = 1 
     ORDER BY sort_order ASC, id ASC"
);
$categories = $stmt->fetchAll();
// URL'den table parametresini güvenli şekilde al
$table_id = getSecureInt('table', 1);
if ($table_id <= 0) {
    $table_id = 1; // Negatif değerlere karşı korunma
}
$_SESSION['table_id'] = $table_id; // Session'a kaydet


// Ayarları çek
$settingsResult = $db->query("SELECT * FROM settings");
$settings = array();
foreach($settingsResult->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Tema rengini al
$theme_color = $_SESSION['theme_color'] ?? '#343a40'; // Varsayılan koyu renk
$is_dark = isset($_SESSION['theme_color']) && $_SESSION['theme_color'] === '#343a40';
$theme_rgb = hexToRgb($theme_color);

// Eğer kategori seçilmişse ürünleri çek
$products = [];
$category_input = getSecureInt('category', 0);
if ($category_input > 0) {
    $stmt = $db->query(
        "SELECT * FROM products 
         WHERE category_id = ? 
         AND status = 1 
         ORDER BY sort_order ASC, id ASC", 
        [$category_input]
    );
    $products = $stmt->fetchAll();
}

function checkExistingOrder($db, $table_id) {
    return $db->query(
        "SELECT id FROM orders 
        WHERE table_id = ? 
        AND status IN ('pending', 'preparing', 'ready')
        ORDER BY created_at DESC 
        LIMIT 1",
        [$table_id]
    )->fetch();
}

$existingOrder = checkExistingOrder($db, $table_id);
if ($existingOrder) {
    $_SESSION['existing_order_id'] = $existingOrder['id'];
} else {
    $_SESSION['existing_order_id'] = null;
}

include 'includes/customer-header.php';
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <link href="assets/css/cart.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/cart.js" defer></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

      
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('assets/images/restaurant-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .category-section {
            padding: 0;
            background: #f8f9fa;
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
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modern-category-item {
            position: relative;
            height: 180px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, <?= $theme_color ?>, rgba(<?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>, 0.8));
            animation: slideInCategory 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .modern-category-item:nth-child(1) { animation-delay: 0.1s; }
        .modern-category-item:nth-child(2) { animation-delay: 0.2s; }
        .modern-category-item:nth-child(3) { animation-delay: 0.3s; }
        .modern-category-item:nth-child(4) { animation-delay: 0.4s; }
        .modern-category-item:nth-child(5) { animation-delay: 0.5s; }
        .modern-category-item:nth-child(6) { animation-delay: 0.6s; }
        
        @keyframes slideInCategory {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
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

        .modern-category-item:hover::before {
            opacity: 0.5;
        }

        .modern-category-item:hover {
            transform: scale(1.05) translateZ(0);
            z-index: 10;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
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

        @media (max-width: 768px) {
            .modern-category-grid {
                grid-template-columns: 1fr;
            }
            
            .modern-category-item {
                height: 150px;
            }
            
            .modern-category-title {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .modern-category-item {
                height: 130px;
            }
            
            .modern-category-content {
                padding: 20px;
            }
            
            .modern-category-title {
                font-size: 1.2rem;
            }
        }
        .menu-item {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.menu-item-image {
    position: relative;
    width: 100%;
    min-width: 150px;
    height: 200px;
    overflow: hidden;
    border-radius: 8px 8px 0 0;
}

.menu-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.menu-item-content {
    padding: 15px;
}

.menu-item-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #2c3e50;
}

.menu-item-description {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}

.menu-item-footer {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
}

.menu-item-price {
    font-size: 20px;
    font-weight: 600;
    color: #e74c3c;
    white-space: nowrap;
}

.order-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
}

.quantity-control {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border-radius: 25px;
    padding: 5px;
    flex-grow: 1;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: white;
    color: #e74c3c;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-btn:hover {
    background: #e74c3c;
    color: white;
}

.quantity-input {
    width: 40px;
    border: none;
    background: transparent;
    text-align: center;
    font-weight: 600;
    color: #2c3e50;
}

.add-to-cart {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e74c3c;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.add-to-cart:hover {
    background: <?= $theme_color ?> !important; /*#c0392b;*/
    transform: scale(1.1);
}

/* Sepet Butonu */
.cart-floating-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 65px;
    height: 65px;
    background: #e74c3c;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 1000;
}

.cart-floating-button:hover {
    transform: scale(1.1);
    background: <?= $theme_color ?> !important; /*#c0392b;*/
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #2c3e50;
    color: white;
    font-size: 14px;
    min-width: 25px;
    height: 25px;
    border-radius: 12.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
}

        .back-button {
            background:<?= $theme_color ?> !important; /*#e74c3c;*/
            color: white !important;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-button:hover {
            background: #c0392b;
            color: white;
        }

        .cart-item {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item .quantity-control {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 5px;
}

.cart-item .price {
    font-weight: 600;
    color: #e74c3c;
}

/* Tema rengi ile ilgili tüm stiller */
.category-btn.active {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
}

.category-btn:hover {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
    opacity: 0.9;
}

.btn-primary {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
}

.btn-primary:hover {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
    opacity: 0.9;
}

.text-primary {
    color: <?= $theme_color ?> !important;
}

.product-card:hover {
    border-color: <?= $theme_color ?> !important;
}

.product-price {
    color: <?= $theme_color ?> !important;
}

.cart-badge {
    background-color: <?= $theme_color ?> !important;
}

.quantity-badge {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
}

.btn-outline-primary {
    color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
}

.btn-outline-primary:hover {
    background-color: <?= $theme_color ?> !important;
    color: #fff !important;
}

/* Kaydırma çubuğu rengi */
::-webkit-scrollbar-thumb {
    background-color: <?= $theme_color ?>;
}

/* Modal başlık ve butonlar */
.modal-header {
    border-bottom: 2px solid <?= $theme_color ?>;
}

.modal-title {
    color: <?= $theme_color ?>;
}

/* Ürün kartı içindeki fiyat ve butonlar */
.card-body h5.product-price {
    color: <?= $theme_color ?> !important;
}

.add-to-cart-btn {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
}

.add-to-cart-btn:hover {
    opacity: 0.9;
}

/* Sepet butonu */
.cart-button {
    background-color: <?= $theme_color ?> !important;
}

/* Kategori seçimi */
.nav-link.active {
    color: <?= $theme_color ?> !important;
    border-bottom-color: <?= $theme_color ?> !important;
}

/* Miktar ayarlama butonları */
.quantity-control .btn:hover {
    background-color: <?= $theme_color ?> !important;
    border-color: <?= $theme_color ?> !important;
    color: #fff;
}

/* Vurgu renkleri */
.highlight {
    color: <?= $theme_color ?>;
}

/* Hover efektleri */
.hover-effect:hover {
    color: <?= $theme_color ?> !important;
}

/* Özel gölge efekti */
.custom-shadow:hover {
    box-shadow: 0 0 15px rgba(<?= $theme_rgb['r'] ?>, <?= $theme_rgb['g'] ?>, <?= $theme_rgb['b'] ?>, 0.3);
}

/* Kategori başlığı için yeni stiller */
.category-header {
    background: <?= $theme_color ?>;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.category-header h2 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}

.category-header i {
    margin-right: 10px;
}

.category-description {
    margin-top: 5px;
    font-size: 0.9rem;
    opacity: 0.9;
}
    </style>
</head>
<body>

<!-- Sepet butonu -->
<!--<div class="cart-floating-button" id="cartButton">
    <span class="cart-count"><?= getCartCount() ?></span>
    <i class="fas fa-shopping-cart"></i>
</div>-->

<!-- Sepet Modal -->
<!-- index.php içinde modal kısmı -->
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Sepetim
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cartItems">
                    <!-- Sepet içeriği AJAX ile yüklenecek -->
                </div>
            </div>
            <!--<div class="modal-footer">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <h5 class="mb-0">Toplam: <span id="cartTotal">0.00</span> ₺</h5>
                    <button type="button" class="btn btn-primary" onclick="completeOrder()">
                        <i class="fas fa-check me-2"></i>
                        Siparişi Tamamla
                    </button>
                </div>-->
            </div>
        </div>
    </div>
</div>

<!-- Geri butonu - Tema rengine göre stil -->
<div class="position-fixed bottom-0 start-0 w-100 p-3 border-top d-md-none" 
     style="background-color: <?= $is_dark ? '#343a40' : '#ffffff' ?>;">
    <a href="javascript:history.back()" 
       class="btn <?= $is_dark ? 'btn-outline-light' : 'btn-outline-secondary' ?> w-100">
        <i class="fas fa-arrow-left"></i> Geri Dön
    </a>
</div>



<!-- JavaScript ile geri dönüş kontrolü -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const backButtons = document.querySelectorAll('a[href="javascript:history.back()"]');
    
    backButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = 'index.php';
            }
        });
    });
});
</script>

<?php if(!isset($_GET['category'])): ?>
    <div class="category-section">
        <!-- Ana Sayfa -->
       

        <div class="category-section">
            <div class="modern-category-grid">
                <?php foreach($categories as $index => $category): ?>
                    <a href="?category=<?= $category['id'] ?>&table=<?= $table_id ?>" class="text-decoration-none">
                        <div class="modern-category-item">
                            <img src="uploads/<?= $category['image'] ?>" class="category-bg-image" alt="<?= $category['name'] ?>">
                            
                            <div class="modern-category-content">
                                <h3 class="modern-category-title"><?= htmlspecialchars($category['name']) ?></h3>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
        <?php else: ?>
        <!-- Kategori Ürünleri -->
        <div class="container mt-4">
            <a href="index.php?table=<?= $table_id ?>" class="back-button text-decoration-none">
                <i class="fas fa-arrow-left"></i> Kategorilere Dön
            </a>

            <div class="menu-section">
                <?php
                $category_input = getSecureInt('category', 0);
                $current_category = null;
                $products = [];
                
                if ($category_input > 0) {
                    $stmt = $db->query(
                        "SELECT * FROM products 
                         WHERE category_id = ? 
                         AND status = 1 
                         ORDER BY sort_order ASC, id ASC", 
                        [$category_input]
                    );
                    $products = $stmt->fetchAll();
                    
                    // Kategori bilgilerini getir
                    $current_category = $db->query(
                        "SELECT * FROM categories WHERE id = ?", 
                        [$category_input]
                    )->fetch();
                }
                
                // Aktif tema layout ayarını al
                $active_theme = null;
                $product_layout = 'grid'; // default
                
                try {
                    $active_theme = $db->query("SELECT product_layout FROM customer_themes WHERE is_active = 1 LIMIT 1")->fetch();
                    if ($active_theme && isset($active_theme['product_layout'])) {
                        $product_layout = $active_theme['product_layout'];
                    }
                } catch (Exception $e) {
                    // Column doesn't exist yet, use default
                    $product_layout = 'grid';
                }
                ?>
                <?php if ($current_category): ?>
                 <div class="category-header">
                    <h2>
                        <i class="fas fa-utensils"></i>
                        <?= htmlspecialchars($current_category['name']) ?>
                    </h2>
                    <?php if(!empty($current_category['description'])): ?>
                        <div class="category-description">
                            <?= htmlspecialchars($current_category['description']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Kategori bulunamadı.
                    </div>
                <?php endif; ?>
                
                <?php if($product_layout === 'grid-2col' || $product_layout === 'list' || $product_layout === 'list-2col'): ?>
                    <!-- Theme-based layout -->
                    <div class="products-grid">
                        <?php foreach($products as $product): ?>
                            <div class="product-card">
                                <img src="uploads/<?= $product['image'] ?>" class="product-image" alt="<?= $product['name'] ?>">
                                <div class="product-info">
                                    <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                                        <div class="order-controls d-flex align-items-center gap-2">
                                            <div class="quantity-control d-flex align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn minus" onclick="decreaseAmount(<?= $product['id'] ?>)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" id="qty_<?= $product['id'] ?>" class="form-control form-control-sm quantity-input mx-1" value="1" min="1" max="99" readonly style="width: 50px; text-align: center;">
                                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn plus" onclick="increaseAmount(<?= $product['id'] ?>)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <button class="btn btn-sm btn-primary add-to-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Default Bootstrap grid layout -->
                    <div class="row g-3">
                        <?php foreach($products as $product): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="menu-item">
                                    <div class="menu-item-image">
                                        <img src="uploads/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                                    </div>
                                    <div class="menu-item-content">
                                        <h5 class="menu-item-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <p class="menu-item-description"><?= htmlspecialchars($product['description']) ?></p>
                                        <div class="menu-item-footer">
                                            <div class="menu-item-price"><?= number_format($product['price'], 2) ?> ₺</div>
                                           <!-- <div class="order-controls">
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn minus" onclick="decreaseAmount(<?= $product['id'] ?>)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" id="qty_<?= $product['id'] ?>" class="quantity-input" value="1" min="1" max="99" readonly>
                                                    <button type="button" class="quantity-btn plus" onclick="increaseAmount(<?= $product['id'] ?>)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                               
                                                <button class="add-to-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                                </div>
                                            </div>-->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
                </div>
    <?php endif; ?>
<div class="d-md-none" style="height: 80px;"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="assets/js/cart.js"></script>
</body>
</html>