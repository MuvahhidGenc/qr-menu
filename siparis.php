<?php
/**
 * Web Adrese Sipariş - Müşteri Menü Sayfası (masa bazlı DEĞİLDİR)
 *
 * QR Menü (index.php) ve Peşin Satış akışlarına dokunmaz.
 * Parametre: system_delivery_order_enabled
 */
require_once __DIR__ . '/includes/config.php';

$db = new Database();

// --- Ayarlar -----------------------------------------------------------------
$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$deliverySettings = getDeliverySettings($db);
$customerAccess = isset($settings['system_customer_access']) && $settings['system_customer_access'] == '1';
$deliveryEnabled = isDeliveryEnabled($db);
$hours = checkDeliveryHours($db);

// --- Erişim kontrolü --------------------------------------------------------
if (!$customerAccess || !$deliveryEnabled) {
    http_response_code(403);
    $blockedReason = !$customerAccess
        ? 'Sipariş sistemi şu anda aktif değil.'
        : 'Web üzerinden sipariş alma hizmeti geçici olarak kapalıdır.';
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sipariş Kapalı</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body{background:#f5f6f8;font-family:'Segoe UI',Tahoma,sans-serif;}
            .dv-empty-box{max-width:460px;margin:12vh auto;background:#fff;border-radius:18px;padding:40px 28px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.08);}
            .dv-empty-box i{font-size:3.4rem;color:#dee2e6;}
        </style>
    </head>
    <body>
        <div class="dv-empty-box">
            <i class="fas fa-store"></i>
            <h5 class="mt-3 mb-2">Sipariş Almıyoruz</h5>
            <p class="text-muted mb-0"><?= htmlspecialchars($blockedReason, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- Kategori / Ürünler -----------------------------------------------------
$categories = $db->query(
    "SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC"
)->fetchAll();

$categoryId = getSecureInt('category', 0);
$currentCategory = null;
$products = [];

if ($categoryId > 0) {
    $currentCategory = $db->query("SELECT * FROM categories WHERE id = ? AND status = 1", [$categoryId])->fetch();
    if ($currentCategory) {
        $products = $db->query(
            "SELECT * FROM products WHERE category_id = ? AND status = 1 ORDER BY sort_order ASC, id ASC",
            [$categoryId]
        )->fetchAll();
    } else {
        $categoryId = 0;
    }
}

// --- Sepet özeti (backend) --------------------------------------------------
$cart = buildDeliveryCart($db);
$cartCount = getCartCount(DELIVERY_CART_KEY);
$deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

include __DIR__ . '/includes/customer-header.php';
?>

<link href="assets/css/delivery.css" rel="stylesheet">
<script>
    window.DV_BASE_URL = '';
    window.DV_CART_ACTION_URL = 'ajax/delivery/cart_action.php';
</script>

<div class="dv-page">

    <!-- Mağaza bilgi çubuğu -->
    <div class="dv-store-bar">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1><i class="fas fa-motorcycle me-2"></i>Online Sipariş</h1>
                <p class="dv-sub">
                    <?= htmlspecialchars($settings['restaurant_name'] ?? 'Restaurant', ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($settings['restaurant_slogan'])): ?>
                        &middot; <?= htmlspecialchars($settings['restaurant_slogan']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <span class="dv-badge"><i class="fas fa-truck me-1"></i>Adrese Teslimat</span>
        </div>
    </div>

    <!-- Sipariş sorgulama -->
    <div class="container mt-3">
        <a href="siparis-takip.php" class="dv-track-link">
            <i class="fas fa-search"></i>
            <span>Siparişimi Sorgula / Takip Et</span>
            <i class="fas fa-chevron-right ms-auto"></i>
        </a>
    </div>

    <?php if (!$hours['open']): ?>
        <div class="container mt-3">
            <div class="dv-alert dv-alert-warning">
                <i class="fas fa-clock"></i>
                <div><strong>Siparişler şu anda kapalı.</strong><br><?= htmlspecialchars($hours['message']) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Kategori sekmeleri -->
    <div class="dv-cat-bar">
        <a class="dv-cat-chip <?= $categoryId === 0 ? 'active' : '' ?>" href="siparis.php">
            <i class="fas fa-th me-1"></i>Tümü
        </a>
        <?php foreach ($categories as $cat): ?>
            <a class="dv-cat-chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>"
               href="siparis.php?category=<?= (int)$cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="container py-3">

        <?php if ($categoryId === 0): ?>
            <!-- ============ Kategori ızgarası ============ -->
            <?php if (empty($categories)): ?>
                <div class="dv-empty">
                    <i class="fas fa-utensils"></i>
                    Menüde henüz kategori bulunmuyor.
                </div>
            <?php else: ?>
                <div class="dv-cat-grid">
                    <?php foreach ($categories as $cat): ?>
                        <a class="dv-cat-card" href="siparis.php?category=<?= (int)$cat['id'] ?>">
                            <?php if (!empty($cat['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                            <?php else: ?>
                                <div class="dv-product-img placeholder" style="width:100%;height:132px;border-radius:0;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            <span class="dv-cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ============ Kategori ürünleri ============ -->
            <a href="siparis.php" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="fas fa-arrow-left me-1"></i>Kategoriler
            </a>

            <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($currentCategory['name']) ?></h2>
            <?php if (!empty($currentCategory['description'])): ?>
                <p class="text-muted small mb-3"><?= htmlspecialchars($currentCategory['description']) ?></p>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="dv-empty">
                    <i class="fas fa-box-open"></i>
                    Bu kategoride ürün bulunmuyor.
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $pid = (int)$product['id'];
                    $price = (float)$product['price'];
                    $inCart = isset($_SESSION[DELIVERY_CART_KEY][$pid])
                        ? (int)$_SESSION[DELIVERY_CART_KEY][$pid]['quantity']
                        : 0;
                    $soldOut = (int)$product['stock'] <= 0;
                    ?>
                    <div class="dv-product">
                        <?php if (!empty($product['image'])): ?>
                            <img class="dv-product-img" src="uploads/<?= htmlspecialchars($product['image']) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="dv-product-img placeholder"><i class="fas fa-utensils"></i></div>
                        <?php endif; ?>

                        <div class="dv-product-body">
                            <h3 class="dv-product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <?php if (!empty($product['description'])): ?>
                                <p class="dv-product-desc"><?= htmlspecialchars($product['description']) ?></p>
                            <?php endif; ?>

                            <div class="dv-product-foot">
                                <div class="dv-price">
                                    <?= number_format($price, 2, ',', '.') ?> ₺
                                    <?php if (!empty($product['special'])): ?>
                                        <small>ÖZEL</small>
                                    <?php endif; ?>
                                </div>

                                <?php if ($soldOut): ?>
                                    <button class="dv-btn-add" disabled>Tükendi</button>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="dv-qty">
                                            <button type="button" data-dv-dec="<?= $pid ?>" aria-label="Azalt">&minus;</button>
                                            <span data-dv-qty="<?= $pid ?>"><?= $inCart > 0 ? $inCart : 1 ?></span>
                                            <button type="button" data-dv-inc="<?= $pid ?>" aria-label="Arttır">+</button>
                                        </div>
                                        <button type="button" class="dv-btn-add" data-dv-add="<?= $pid ?>">
                                            <i class="fas fa-cart-plus"></i> Ekle
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- Alt sepet çubuğu -->
    <div class="dv-cart-bar">
        <div class="d-flex align-items-center gap-3">
            <div class="dv-cart-info flex-grow-1">
                <span class="dv-count" data-dv-count><?= $cartCount ?> ürün</span>
                <span class="dv-sum" data-dv-sum><?= number_format($cart['subtotal'] + $deliveryFee, 2, ',', '.') ?> ₺</span>
            </div>
            <a href="siparis-tamamla.php" class="btn dv-btn-primary" data-dv-go style="width:auto;min-width:150px;"
               <?= $cartCount > 0 ? '' : 'disabled' ?>>
                <i class="fas fa-receipt me-2"></i>Siparişi Tamamla
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/delivery.js"></script>
</body>
</html>
