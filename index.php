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

// Sistem parametrelerini kontrol et
$acceptOrders = isset($settings['system_accept_qr_orders']) && $settings['system_accept_qr_orders'] == '1';
$qrMenuEnabled = isset($settings['system_qr_menu_enabled']) && $settings['system_qr_menu_enabled'] == '1';

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

<!-- Additional CSS for index page -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<link href="assets/css/cart.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/cart.js" defer></script>

<style>
        /* SADECE ANİMASYONLAR - Renkler theme.css.php'de! */
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
        
        .modern-category-grid {
            animation: fadeInUp 0.8s ease-out;
        }
        
        .modern-category-item {
            animation: slideInCategory 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .modern-category-item:nth-child(1) { animation-delay: 0.1s; }
        .modern-category-item:nth-child(2) { animation-delay: 0.2s; }
        .modern-category-item:nth-child(3) { animation-delay: 0.3s; }
        .modern-category-item:nth-child(4) { animation-delay: 0.4s; }
        .modern-category-item:nth-child(5) { animation-delay: 0.5s; }
        .modern-category-item:nth-child(6) { animation-delay: 0.6s; }
    </style>

<!-- Sepet butonu - Sadece sipariş alımı aktifse göster -->
<?php if ($acceptOrders): ?>
<div class="cart-floating-button" id="cartButton">
    <span class="cart-count"><?= getCartCount() ?></span>
    <i class="fas fa-shopping-cart"></i>
</div>
<?php endif; ?>

<!-- Sepet Modal - Sadece sipariş alımı aktifse göster -->
<?php if ($acceptOrders): ?>
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
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <h5 class="mb-0">Toplam: <span id="cartTotal">0.00</span> ₺</h5>
                    <button type="button" class="btn btn-primary" onclick="completeOrder()">
                        <i class="fas fa-check me-2"></i>
                        Siparişi Tamamla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Geri butonu - Dinamik tema renkleri -->
<div class="position-fixed bottom-0 start-0 w-100 p-3 border-top d-md-none back-button-container">
    <a href="javascript:history.back()" class="btn btn-back w-100">
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
                    <div class="products-grid" data-layout="<?= $product_layout ?>">
                        <?php foreach($products as $product): ?>
                            <div class="product-card">
                                <img src="uploads/<?= $product['image'] ?>" class="product-image" alt="<?= $product['name'] ?>">
                                <div class="product-info">
                                    <h5 class="product-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <?php if(!empty($product['description'])): ?>
                                    <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                    <?php endif; ?>
                                    <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                                    <?php if ($acceptOrders): ?>
                                    <div class="order-controls">
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn minus" onclick="decreaseAmount(<?= $product['id'] ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" id="qty_<?= $product['id'] ?>" class="quantity-input" value="1" min="1" max="99" readonly>
                                            <button type="button" class="quantity-btn plus" onclick="increaseAmount(<?= $product['id'] ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <button class="add-to-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
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
                                            <?php if ($acceptOrders): ?>
                                            <div class="order-controls">
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
                                            </div>
                                            <?php endif; ?>
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