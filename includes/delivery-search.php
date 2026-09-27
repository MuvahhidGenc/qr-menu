<?php
/**
 * Web Adrese Sipariş - Ürün araması (PAYLAŞILAN)
 *
 * Bu dosyayı hem siparis.php (ilk sayfa yükleme) hem ajax/delivery/search.php
 * (canlı/arama-sız yenileme) YÜKLER. Tek bir kaynak olması kasıtlıdır:
 *   "Başlıkta N sonuç yazıyor ama ekranda M kart var" hatasının kök nedeni
 *   iki yerde ayrı ayrı mantık yazmaktı. Artık ikisi de buradan beslenir.
 *
 * Güvenlik:
 *   - Tüm çıktılar htmlspecialchars() / dvHighlightMatch() ile kaçırılır.
 *   - Arama terimi hazırlanmış DEĞİLDİR ama SQL'e hiç girmiyor; eşleştirme
 *     PHP tarafında yapıldığı için enjeksiyon yüzeyi oluşmuyor.
 *   - Uzunluk sınırları: terim 60 karakter, en fazla 6 kelime, en fazla
 *     60 sonuç. Aday kümesi 500 satırla sınırlı.
 */

if (!function_exists('deliveryStatusList')) {
    // siparis.php bu dosyayı config.php sonrası include ediyor; yine de
    // bağımsız kullanımda tanımsız olmasın diye include ediliyor.
    require_once __DIR__ . '/delivery.php';
}

/**
 * Canlı arama için ayrı hız sınırlama kovası
 *
 * DİKKAT: trackOrderCheckRateLimit() endpoint adını 'delivery_track_order'
 * olarak SABİTLER. Onu burada kullanmak iki kotayı birleştirir ve
 * sipariş numarası tahminine karşı korumayı zayıflatır. Bu yüzden
 * ayrı bir kova açılır: RateLimit zaten (ip, endpoint) çiftine göre
 * sayar, dolayısıyla 'delivery_live_search' kovası sipariş sorgulamadan
 * tamamen bağımsızdır.
 *
 * @param Database $db
 * @param int      $maxAttempts
 * @param int      $window
 * @return bool
 */
function dvSearchCheckRateLimit($db, $maxAttempts = 60, $window = 120) {
    $limit = new RateLimit($db);
    $limit->setWindow($window);
    $limit->setMaxAttempts($maxAttempts);
    return $limit->check('delivery_live_search');
}

/**
 * Arama terimini normalize eder, adayları süzer ve puanlar
 *
 * @param Database $db
 * @param string   $rawTerm
 * @param int      $limit
 * @param int      $candidateLimit
 * @return array [ 'term' => string, 'terms' => string[], 'results' => array[], 'total' => int ]
 */
function dvSearchExecute($db, $rawTerm, $limit = 60, $candidateLimit = 500) {
    $term  = trim((string)$rawTerm);
    $term  = mb_substr($term, 0, 60);
    $terms = $term === '' ? [] : dvSearchTerms($term);

    $out = ['term' => $term, 'terms' => $terms, 'results' => [], 'total' => 0];
    if (!$terms) {
        return $out;      // boş/joker-only arama: tüm ürünler DEĞİL, hiçbir şey dönmez
    }

    $candidates = $db->query(
        "SELECT p.id, p.name, p.description, p.price, p.image, p.stock,
                p.special, p.category_id, p.sort_order, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.status = 1
         ORDER BY p.sort_order ASC, p.id ASC
         LIMIT " . (int)$candidateLimit
    )->fetchAll();

    $scored = [];
    foreach ($candidates as $row) {
        $score = dvSearchScore($row, $terms);
        if ($score === null) continue;
        $row['_score'] = $score;
        $scored[] = $row;
    }

    // Puan küçük = daha iyi eşleşme
    usort($scored, function ($a, $b) {
        return [$a['_score'], (int)$a['sort_order'], (int)$a['id']]
             <=> [$b['_score'], (int)$b['sort_order'], (int)$b['id']];
    });

    $out['total']   = count($scored);
    $out['results'] = array_slice($scored, 0, (int)$limit);
    return $out;
}

/**
 * Tek bir ürün kartı (arama sonucu ve kategori listesi için ortak)
 *
 * @param array  $product
 * @param array  $terms  vurgulanacak terimler (boş olabilir)
 * @param int    $inCart sepetteki adet
 * @param string $basePath
 * @return string HTML
 */
function dvRenderProductCard(array $product, array $terms, $inCart = 0, $basePath = '') {
    $pid      = (int)$product['id'];
    $price    = (float)($product['price'] ?? 0);
    $soldOut  = (int)($product['stock'] ?? 0) <= 0;
    $image    = (string)($product['image'] ?? '');
    $catName  = (string)($product['category_name'] ?? '');
    $name     = (string)($product['name'] ?? '');
    $desc     = (string)($product['description'] ?? '');
    $special  = !empty($product['special']);
    $q        = $inCart > 0 ? $inCart : 1;

    $h = '<div class="dv-product"'
       . ' data-name="' . htmlspecialchars(dvSearchFold($name), ENT_QUOTES, 'UTF-8') . '"'
       . ' data-desc="' . htmlspecialchars(dvSearchFold($desc), ENT_QUOTES, 'UTF-8') . '"'
       . ' data-cat="'  . htmlspecialchars(dvSearchFold($catName), ENT_QUOTES, 'UTF-8') . '">';

    if ($image !== '') {
        $h .= '<img class="dv-product-img" src="' . $basePath . 'uploads/'
           . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '"'
           . ' alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    } else {
        $h .= '<div class="dv-product-img placeholder"><i class="fas fa-utensils"></i></div>';
    }

    $h .= '<div class="dv-product-body">';

    if ($catName !== '') {
        $h .= '<span class="dv-search-cat">' . htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    $h .= '<h3 class="dv-product-name">' . dvHighlightMatch($name, $terms) . '</h3>';

    if ($desc !== '') {
        $h .= '<p class="dv-product-desc">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $h .= '<div class="dv-product-foot"><div class="dv-price">'
       . number_format($price, 2, ',', '.') . ' ₺'
       . ($special ? '<small>ÖZEL</small>' : '')
       . '</div>';

    if ($soldOut) {
        $h .= '<button class="dv-btn-add" disabled>Tükendi</button>';
    } else {
        $h .= '<div class="d-flex align-items-center gap-2">'
           . '<div class="dv-qty">'
           . '<button type="button" data-dv-dec="' . $pid . '" aria-label="Azalt">&minus;</button>'
           . '<span data-dv-qty="' . $pid . '">' . $q . '</span>'
           . '<button type="button" data-dv-inc="' . $pid . '" aria-label="Arttır">+</button>'
           . '</div>'
           . '<button type="button" class="dv-btn-add" data-dv-add="' . $pid . '">'
           . '<i class="fas fa-cart-plus"></i> Ekle</button>'
           . '</div>';
    }

    $h .= '</div></div></div>';
    return $h;
}

/**
 * Arama sonuçlarının TAMAMINI (başlık + kartlar + boş durum) üretir
 *
 * Çıktının tamamı #dvSearchResults konteynerinin İÇERİĞİDİR; AJAX ile
 * geldiğinde istemci bu parçayı doğrudan innerHTML ile yerine koyar.
 *
 * @param array  $payload  dvSearchExecute() çıktısı
 * @param string $basePath
 * @return string HTML
 */
function dvRenderSearchResults(array $payload, $basePath = '') {
    $term    = (string)($payload['term'] ?? '');
    $terms   = (array)($payload['terms'] ?? []);
    $results = (array)($payload['results'] ?? []);
    $total   = (int)($payload['total'] ?? count($results));
    $count   = count($results);

    $h  = '<div class="dv-search-head"'
        . ' data-server-search="1"'
        . ' data-folded-term="' . htmlspecialchars(implode(' ', $terms), ENT_QUOTES, 'UTF-8') . '"'
        . ' data-result-count="' . $count . '">';
    $h .= '<h2 class="h6 fw-bold mb-0">'
        . '<i class="fas fa-magnifying-glass me-1"></i>Arama Sonuçları</h2>';
    $h .= '<span class="dv-search-count">' . $count . ' sonuç'
        . ($term !== '' ? ' &middot; &quot;' . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . '&quot;' : '')
        . '</span></div>';

    if (!$results) {
        $h .= '<div class="dv-empty"><i class="fas fa-magnifying-glass"></i>'
           . '&quot;' . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . '&quot; ile eşleşen ürün bulunamadı.</div>'
           . '<a href="siparis.php" class="btn btn-sm btn-outline-secondary">'
           . '<i class="fas fa-arrow-left me-1"></i>Tüm ürünlere dön</a>';
        return $h;
    }

    $h .= '<div class="dv-result-list">';
    foreach ($results as $product) {
        $pid     = (int)$product['id'];
        $inCart  = isset($_SESSION[DELIVERY_CART_KEY][$pid])
                 ? (int)$_SESSION[DELIVERY_CART_KEY][$pid]['quantity'] : 0;
        $h .= dvRenderProductCard($product, $terms, $inCart, $basePath);
    }
    $h .= '</div>';

    if ($total > $count) {
        $h .= '<p class="text-muted small text-center mt-2 mb-0">'
           . 'İlk ' . $count . ' sonuç gösteriliyor; aramayı daraltarak liste kısalmalısınız.</p>';
    }

    return $h;
}

/**
 * Aramarsız (normal) menü listesini üretir: kategori ızgarası + Hızlı Sipariş
 * veya tek bir kategorinin ürünleri.
 *
 * Arama kutusu TEMİZLENİNCE delivery.js bu HTML'i AJAX ile isteyerek menüye
 * döner; böylece "tüm ürünler" linki ve arama kutusu boşaltma işlemi de tam
 * sayfa yenilemesi yapmaz.
 *
 * @param array $categories      Tüm aktif kategoriler (rows)
 * @param array $quickProducts   Hızlı Sipariş ürünleri (rows)
 * @param int   $categoryId      0 = ana ekran
 * @param array|null $currentCategory Seçili kategori satırı
 * @param array $products        Seçili kategorinin ürünleri
 * @param string $basePath       Asset yol öneki
 * @return string HTML
 */
function dvRenderMenuListing(array $categories, array $quickProducts, $categoryId = 0,
                            $currentCategory = null, array $products = [], $basePath = '')
{
    $cart = $_SESSION[DELIVERY_CART_KEY] ?? [];
    $inCartOf = static function ($pid) use ($cart) {
        return isset($cart[$pid]) ? (int)$cart[$pid]['quantity'] : 0;
    };

    // ---------- Ana ekran: kategori ızgarası + hızlı sipariş ----------
    if ((int)$categoryId === 0) {
        if (!$categories) {
            return '<div class="dv-empty"><i class="fas fa-utensils"></i>'
                 . 'Menüde henüz kategori bulunmuyor.</div>';
        }

        $h = '<div class="dv-cat-grid">';
        foreach ($categories as $cat) {
            $h .= '<a class="dv-cat-card" href="siparis.php?category=' . (int)$cat['id'] . '">';
            if (!empty($cat['image'])) {
                $h .= '<img src="' . $basePath . 'uploads/'
                    . htmlspecialchars($cat['image'], ENT_QUOTES, 'UTF-8')
                    . '" alt="' . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') . '">';
            } else {
                $h .= '<div class="dv-product-img placeholder" style="width:100%;height:132px;border-radius:0;">'
                    . '<i class="fas fa-utensils"></i></div>';
            }
            $h .= '<span class="dv-cat-name">'
                . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') . '</span></a>';
        }
        $h .= '</div>';

        if ($quickProducts) {
            $h .= '<div class="dv-quick-head"><h2 class="h6 fw-bold mb-0">'
                . '<i class="fas fa-bolt me-1"></i>Hızlı Sipariş</h2>'
                . '<span class="dv-search-count">Stokta olan ürünler</span></div>';
            foreach ($quickProducts as $product) {
                $h .= dvRenderProductCard($product, [], $inCartOf((int)$product['id']), $basePath);
            }
        }
        return $h;
    }

    // ---------- Kategori ekranı ----------
    $h = '<a href="siparis.php" class="btn btn-sm btn-outline-secondary mb-3">'
       . '<i class="fas fa-arrow-left me-1"></i>Kategoriler</a>';
    $name = (string)($currentCategory['name'] ?? '');
    $h .= '<h2 class="h5 fw-bold mb-1">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h2>';
    if (!empty($currentCategory['description'])) {
        $h .= '<p class="text-muted small mb-3">'
            . htmlspecialchars($currentCategory['description'], ENT_QUOTES, 'UTF-8') . '</p>';
    }

    if (!$products) {
        $h .= '<div class="dv-empty"><i class="fas fa-box-open"></i>'
            . 'Bu kategoride ürün bulunmuyor.</div>';
        return $h;
    }

    foreach ($products as $product) {
        // Kategori adı aramada da eşleşme alanıdır.
        $product['category_name'] = $name;
        $h .= dvRenderProductCard($product, [], $inCartOf((int)$product['id']), $basePath);
    }
    return $h;
}
