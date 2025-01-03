<?php
require_once 'includes/config.php';
require_once 'includes/cart.php';
// Session ve sepeti başlat
initCart();

$db = new Database();
// Debug için
error_log('Session ID: ' . session_id());
error_log('Cart Contents: ' . print_r($_SESSION['cart'], true));

// Kategorileri çek
$stmt = $db->query("SELECT * FROM categories WHERE status = 1");
$categories = $stmt->fetchAll();
// URL'den table parametresini al

$table_id = isset($_GET['table']) ? (int)$_GET['table'] : 1;
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
if(isset($_GET['category'])) {
   $category_id = (int)$_GET['category'];
   $stmt = $db->query("SELECT * FROM products WHERE category_id = ? AND status = 1", [$category_id]);
   $products = $stmt->fetchAll();
}

function checkExistingOrder($db, $table_id) {
    return $db->query(
        "SELECT id FROM orders 
        WHERE table_id = ? 
        AND status NOT IN ('paid', 'cancelled')
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

        .swal2-input-numeric {
    text-align: center !important;
    font-size: 1.2em !important;
    letter-spacing: 2px !important;
}

.swal2-popup {
    width: 32em !important;
}

.swal2-input {
    margin: 1em auto !important;
}
.swal2-popup .swal2-input {
    display: block !important;
    width: 100% !important;
    padding: 0.5em !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-size: 1.2em !important;
    text-align: center !important;
    margin: 1em auto !important;
}

.swal2-popup .swal2-input:focus {
    border-color: #3085d6 !important;
    box-shadow: 0 0 0 3px rgba(48,133,214,.3) !important;
}

/* Artırma/azaltma oklarını gizle */
.swal2-popup .swal2-input::-webkit-outer-spin-button,
.swal2-popup .swal2-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.swal2-popup .swal2-input[type=number] {
    -moz-appearance: textfield;
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
            padding: 30px 0;
        }

        .category-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
            cursor: pointer;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            color: white;
        }

        .category-overlay h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .menu-item {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.menu-item-image {
    position: relative;
    width: 100%;
    min-width: 150px;
    height: 250px;
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
    padding: 20px;
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
    </style>
</head>
<body>

<!-- Sepet butonu -->
<div class="cart-floating-button" id="cartButton">
    <span class="cart-count"><?= getCartCount() ?></span>
    <i class="fas fa-shopping-cart"></i>
</div>

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

<!-- Geri butonu - Tema rengine göre stil -->
<div class="position-fixed bottom-0 start-0 w-100 p-3 border-top d-md-none" 
     style="background-color: <?= $is_dark ? '#343a40' : '#ffffff' ?>;">
    <a href="javascript:history.back()" 
       class="btn <?= $is_dark ? 'btn-outline-light' : 'btn-outline-secondary' ?> w-100">
        <i class="fas fa-arrow-left"></i> Geri Dön
    </a>
</div>

<!-- Mobil için alt padding -->
<div class="d-md-none" style="height: 80px;"></div>

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
        <div class="container">
                <h2 class="section-title">Menü Kategorileri</h2>
                <div class="row">
                    <?php foreach($categories as $category): ?>
                        <div class="col-md-4">
                            <a href="?category=<?= $category['id'] ?>&table=<?= $table_id ?>" class="text-decoration-none">
                                <div class="category-card">
                                    <img src="uploads/<?= $category['image'] ?>" class="category-image" alt="<?= $category['name'] ?>">
                                    <div class="category-overlay">
                                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                $category_id = (int)$_GET['category'];
                $stmt = $db->query("SELECT * FROM products WHERE category_id = ? AND status = 1", [$category_id]);
                $products = $stmt->fetchAll();
                
                foreach($products as $product):
                ?>
                 <div class="category-section">
                 <div class="menu-item">
                    <div class="menu-item-image">
                        <img src="uploads/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                    </div>
                    <div class="menu-item-content">
                        <h5 class="menu-item-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="menu-item-description"><?= htmlspecialchars($product['description']) ?></p>
                        <div class="menu-item-footer">
                            <div class="menu-item-price"><?= number_format($product['price'], 2) ?> ₺</div>
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
                        </div>
                    </div>
                    </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
                </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="assets/js/cart.js"></script>
</body>
</html>