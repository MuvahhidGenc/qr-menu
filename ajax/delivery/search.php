<?php
/**
 * Web Adrese Sipariş - Canlı ürün araması (BACKEND)
 *
 * Sayfa yenilemeden çalışan arama için JSON döner. Mantık ve HTML
 * üretimi siparis.php ile AYNI dosyadan gelir (includes/delivery-search.php);
 * böylece "N sonuç" yazısı ile ekrandaki kart sayısı asla ayrışamaz.
 *
 * Güvenlik:
 *   - Erişim web sipariş anahtarına bağlıdır (QR anahtarından bağımsız).
 *   - GET + yalnızca okuma; hazırlanmış ifade YOK (arama PHP'de yapılır),
 *     dolayısıyla SQL enjeksiyon yüzeyi yoktur.
 *   - Girdi uzunluğu sınırlıdır (60 karakter / 6 kelime).
 *   - Sonuç sayısı 60 ile sınırlıdır; aday kümesi 500 satır.
 *   - Hız sınırlama ile kötüye kullanım engellenir.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/delivery-search.php';
require_once __DIR__ . '/../../includes/delivery-tracking.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = new Database();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
        exit;
    }

    if (!isDeliveryEnabled($db)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Web üzerinden sipariş alma hizmeti şu anda kapalıdır.',
        ]);
        exit;
    }

    // Hız sınırlama: 2 dakikada en fazla 60 istek. AYRI kovadadır;
    // sipariş sorgulama kovasıyla karışmaz (bkz. dvSearchCheckRateLimit).
    if (!dvSearchCheckRateLimit($db, 60, 120)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Arama istekleri çok sık. Lütfen yazmaya devam edin.',
        ]);
        exit;
    }

    $raw  = (string)($_POST['q'] ?? '');
    $term = mb_substr(trim($raw), 0, 60);

    // ---- Temizleme (reset) ----
    // Arama kutusu boşaltıldığında menüye SADECE sayfa yenilemeden dönmek
    // için. reset=1 gönderilirse terim yok sayılır ve normal menü listesi
    // (kategori ızgarası + hızlı sipariş, ya da kategori ürünleri) döner.
    $isReset = (!empty($_POST['reset']) && $term === '');

    if ($isReset) {
        $categories = $db->query(
            "SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC"
        )->fetchAll();
        // Kategori id'si guvenli sekilde normalize edilir: negatif, ondalikli
        // veya devasa bir deger 0'a duser. Aksi halde -5 gibi bir deger
        // "bu kategoride urun yok" basligi olan BOZUK bir kategori ekrani
        // uretirdi (ve $quickProducts hic yuklenmezdi).
        $categoryId = max(0, (int)($_POST['category_id'] ?? 0));
        $currentCategory = null;
        $products = [];
        $quickProducts = [];

        if ($categoryId > 0) {
            $currentCategory = $db->query(
                "SELECT * FROM categories WHERE id = ? AND status = 1",
                [$categoryId]
            )->fetch();
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

        if ($categoryId === 0) {
            // siparis.php ile AYNI sorgu (hızlı sipariş: stokta olan ilk 6 ürün).
            $quickProducts = $db->query(
                "SELECT p.* FROM products p
                 WHERE p.status = 1 AND p.stock > 0
                 ORDER BY p.sort_order ASC, p.id ASC
                 LIMIT 6"
            )->fetchAll();
        }

        echo json_encode([
            'success'  => true,
            'term'     => '',
            'terms'    => [],
            'count'    => 0,
            'total'    => 0,
            'reset'    => true,
            'category' => $categoryId,
            'html'     => dvRenderMenuListing($categories, $quickProducts, $categoryId,
                                              $currentCategory, $products, ''),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = dvSearchExecute($db, $term, 60);
    $html    = dvRenderSearchResults($payload, '');

    echo json_encode([
        'success' => true,
        'term'    => $payload['term'],
        'terms'   => $payload['terms'],
        'count'   => count($payload['results']),
        'total'   => $payload['total'],
        'html'    => $html,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Delivery live search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Arama şu anda gerçekleştirilemedi.',
    ]);
}
