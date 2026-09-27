<?php
/**
 * Web Adrese Sipariş - Müşteri Menü Sayfası (masa bazlı DEĞİLDİR)
 *
 * QR Menü (index.php) ve Peşin Satış akışlarına dokunmaz.
 *
 * Erişim anahtarı: YALNIZCA system_delivery_order_enabled
 * Bu sayfa QR/masa anahtarından (system_table_qr_order_enabled) BAĞIMSIZ çalışır;
 * QR siparişi kapatıldığında web adres siparişi açık kalır.
 */
require_once __DIR__ . '/includes/config.php';
// Arama mantığı + kart üretimi: ajax/delivery/search.php ile AYNI dosyadan.
require_once __DIR__ . '/includes/delivery-search.php';

$db = new Database();

// --- Ayarlar -----------------------------------------------------------------
$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$deliverySettings = getDeliverySettings($db);
$deliveryEnabled = isDeliveryEnabled($db);
$hours = checkDeliveryHours($db);

// --- Erişim kontrolü --------------------------------------------------------
if (!$deliveryEnabled) {
    http_response_code(403);
    $blockedReason = 'Web üzerinden sipariş alma hizmeti şu anda kapalıdır.';
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
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.category_id = ? AND p.status = 1
             ORDER BY p.sort_order ASC, p.id ASC",
            [$categoryId]
        )->fetchAll();
    } else {
        $categoryId = 0;
    }
}

// --- Gelişmiş ürün arama (mantık includes/delivery-search.php'te) ----------
// AYNI fonksiyonlar ajax/delivery/search.php tarafından da kullanılır; böylece
// ilk sayfa yüklemesi ile canlı (yenilemesiz) arama asla ayrışamaz.
//
// NEDEN saf SQL LIKE kullanılmıyor?
//   MySQL utf8mb4_general_ci, ASCII'de büyük/küçük harf ayrımı yapmaz ama
//   TÜRKÇE harfleri ayırır: 'I' / 'ı' / 'İ' / 'i' birbirinden farklıdır.
//   "irmak" araması "IRMAK" ürününü LIKE ile BULAMAZ. Eşleştirme bu yüzden
//   dvSearchFold() ile normalize edilmiş metin üzerinde PHP'de yapılır.
$searchTerm = trim((string)($_GET['q'] ?? ''));
$isSearching = $searchTerm !== '';
$searchPayload = $isSearching
    ? dvSearchExecute($db, $searchTerm, 60)
    : ['term' => '', 'terms' => [], 'results' => [], 'total' => 0];
$searchTerms   = $searchPayload['terms'];
$searchResults = $searchPayload['results'];

// --- Hızlı erişim (ana ekranda boş alanı doldurur) ---------------------------
// Yalnızca kategori seçiliyken/aranmıyorken ve ürün varken gösterilir.
$quickProducts = [];
if (!$isSearching && $categoryId === 0 && !empty($categories)) {
    $quickProducts = $db->query(
        "SELECT p.* FROM products p
         WHERE p.status = 1 AND p.stock > 0
         ORDER BY p.sort_order ASC, p.id ASC
         LIMIT 6"
    )->fetchAll();
}

// --- Sepet özeti (backend) --------------------------------------------------
$cart = buildDeliveryCart($db);
$cartCount = getCartCount(DELIVERY_CART_KEY);
$deliveryFee = calculateDeliveryFee($deliverySettings, $cart['subtotal']);

// customer-header.php'deki hero (restoran adı + slogan) gizlenir: bu sayfa
// zaten dv-store-bar içinde aynı bilgiyi gösteriyor. İkisi üst üste basılırsa
// mobilde ~180px dikey alan boşa gider. Tema değişkenleri aynen korunur.
$hideCustomerHero = true;
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

    <!-- Hızlı ürün arama -->
    <form class="dv-search" method="get" action="siparis.php" id="dvSearchForm" autocomplete="off">
        <i class="fas fa-search dv-search-icon"></i>
        <input type="search" name="q" id="dvSearchInput" class="dv-search-input"
               value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Ürün ara&hellip;" aria-label="Ürün ara">
        <button type="submit" class="dv-search-btn" aria-label="Ara">
            <i class="fas fa-arrow-right"></i>
        </button>
        <?php if ($isSearching): ?>
            <a href="siparis.php" class="dv-search-clear" aria-label="Aramayı temizle">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if (!$isSearching): ?>
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
    <?php endif; ?>

    <div class="container py-3">

        <!-- Sonuç konteyneri: hem ilk yüklemede hem canlı aramada
             AYNI konteyner yenilenir. Canlı arama (delivery.js) yanıtı
             doğrudan buraya yazar; sayfa yenilenmez. -->
        <div id="dvSearchResults">
        <?php if ($isSearching): ?>
            <?php
            // Başlık + kartlar + boş durum: includes/delivery-search.php
            // üretir (AJAX isteğiyle birebir aynı HTML).
            echo dvRenderSearchResults($searchPayload, '');
            ?>
        <?php else: ?>
            <!-- ============ Aramarsız menü listesi ============
                 includes/delivery-search.php üretir; arama kutusu
                 temizlendiğinde AJAX ile de aynı HTML çağrılır. -->
            <?php
            echo dvRenderMenuListing($categories, $quickProducts, $categoryId,
                                     $currentCategory, $products, '');
            ?>
        <?php endif; ?>
        </div><!-- /#dvSearchResults -->

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
<script>
    // Canlı aramanın ihtiyaç duyduğu yollar. Uygulama bir alt dizinde de
    // çalışabildiği için mutlak yol değil, göreli yol verilir.
    window.DV_SEARCH_URL = 'ajax/delivery/search.php';
</script>
<script src="assets/js/delivery.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/delivery.js') ?>"></script>
</body>
</html>
