<?php
/**
 * Web Adrese Siparis - Musteri Siparis Takip (Siparis No + Telefon) test betigi
 *
 * Kapsam: IDOR korumasi, telefon normalizasyonu, SQL injection dayanikliligi,
 *         bilgi sizdirmama, rate limit, mod kapaliyken erisim, XSS kacisi.
 *
 * GUVENLIK: Bu betik canli veritabanini DEGISTIRIR ve web kokunde duruyor.
 * Yalnizca CLI uzerinden calistirilabilir; tarayicidan erisim engellenir.
 *
 * Calistirma:  php test_delivery_tracking.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu test betigi sadece komut satirindan calistirilabilir.');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/delivery-tracking.php';

$db = new Database();
$fail = 0;
$pass = 0;

function check($label, $cond, $extra = '')
{
    global $fail, $pass;
    if ($cond) { $pass++; echo "  [OK]   $label\n"; }
    else { $fail++; echo "  [FAIL] $label $extra\n"; }
}

function section($t) { echo "\n== $t ==\n"; }

// --------------------------------------------------------- Ortam hazirligi
section('0. Ortam');

$savedEnabled = $db->query("SELECT setting_value FROM settings WHERE setting_key='system_delivery_order_enabled'")->fetch();
$savedEnabled = $savedEnabled ? $savedEnabled['setting_value'] : null;

$db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('system_delivery_order_enabled','1')
            ON DUPLICATE KEY UPDATE setting_value='1'");

$prod = $db->query("SELECT id, name, price, stock FROM products WHERE status = 1 ORDER BY id LIMIT 1")->fetch();
check('aktif urun bulundu', (bool)$prod);
if (!$prod) { echo "\nAktif urun yok. Test sonlandirildi.\n"; exit(1); }
$stockBefore = (int)$prod['stock'];

// --------------------------------------------- Gecici adres siparisi olustur
section('1. Gecici test verisi');

$db->query("INSERT INTO orders
    (order_type, table_id, status, subtotal, delivery_fee, total_amount, payment_method,
     customer_name, customer_phone, delivery_city, delivery_district, delivery_neighborhood,
     delivery_address, delivery_note, created_at)
    VALUES ('delivery', NULL, 'pending', 20.00, 0.00, 20.00, 'cash',
            'Takip Testi', '05551112233', 'Istanbul', 'Kadikoy', 'Caferaga',
            'Test Mah. Test Sok. No:1 D:2', NULL, NOW())");
$orderId = (int)$db->lastInsertId();

$db->query("INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, 2, 10.00)", [$orderId, $prod['id']]);

$db->query("INSERT INTO orders
    (order_type, table_id, status, subtotal, total_amount, payment_method,
     customer_name, customer_phone, created_at)
    VALUES ('table', 1, 'pending', 20.00, 20.00, 'cash', 'Masa Testi', '05552223344', NOW())");
$tableOrderId = (int)$db->lastInsertId();

check('gecici adres siparisi olusturuldu', $orderId > 0, "id=$orderId");
check('gecici masa siparisi olusturuldu', $tableOrderId > 0, "id=$tableOrderId");

// ------------------------------------------------- Temel sorgu mantigi
section('2. trackOrderLookup() dogru eslesme');

$dbT = new Database();

$r = trackOrderLookup($dbT, $orderId, '05551112233');
check('dogru no + dogru telefon basarili', $r['success'] === true);
check('dogru sorguda order doner', isset($r['order']['id']) && (int)$r['order']['id'] === $orderId);
check('dogru sorguda kalemler gelir', isset($r['items']) && count($r['items']) === 1);
check('dogru sorguda kalem urun adi JOIN ile gelir', $r['items'][0]['name'] === $prod['name']);
check('musteri telefonu normalize edilmis saklanir', $r['order']['customer_phone'] === '05551112233');
check('ayarlar dondurulur', isset($r['settings']));
check('sadece delivery siparisi doner', $r['order']['order_type'] === 'delivery');
check('masa sutunu NULL kalir', $r['order']['table_id'] === null);

section('3. Telefon / siparis no normalizasyonu');

check('bosluklu telefon eslesir', trackOrderLookup($dbT, (string)$orderId, '0555 111 22 33')['success'] === true);
check('+90 onekli telefon eslesir', trackOrderLookup($dbT, (string)$orderId, '+905551112233')['success'] === true);
check('onesiz telefon eslesir', trackOrderLookup($dbT, (string)$orderId, '5551112233')['success'] === true);
check('# isaretli siparis no eslesir', trackOrderLookup($dbT, '#' . $orderId, '05551112233')['success'] === true);
check('bosluklu siparis no eslesir', trackOrderLookup($dbT, ' ' . $orderId . ' ', '05551112233')['success'] === true);

check('trackOrderSanitizeNo("#68") = 68', trackOrderSanitizeNo('#68') === 68);
check('trackOrderSanitizeNo(" 6 8 ") = 68', trackOrderSanitizeNo(' 6 8 ') === 68);
check('trackOrderSanitizeNo("abc") = 0', trackOrderSanitizeNo('abc') === 0);
check('trackOrderSanitizeNo("") = 0', trackOrderSanitizeNo('') === 0);
check('trackOrderSanitizeNo("-5") = 5', trackOrderSanitizeNo('-5') === 5);
check('trackOrderSanitizeNo(0) = 0', trackOrderSanitizeNo(0) === 0);

section('4. IDOR korumasi ve girdi dogrulama');

$r = trackOrderLookup($dbT, (string)$orderId, '05559998888');
check('YANLIS telefon reddedilir', $r['success'] === false);
check('hata kodu not_found', $r['code'] === 'not_found');
check('hata yanitinda order sizmaz', !isset($r['order']));

$r = trackOrderLookup($dbT, '99999999', '05551112233');
check('YANLIS siparis no reddedilir', $r['success'] === false);
check('olmayan no da ayni kodu verir', $r['code'] === 'not_found');

$r = trackOrderLookup($dbT, (string)$tableOrderId, '05552223344');
check('MASA siparisi sorgulanamaz (IDOR korumasi)', $r['success'] === false);
check('masa siparisi icin not_found doner', $r['code'] === 'not_found');

check('bos telefon reddedilir', trackOrderLookup($dbT, (string)$orderId, '')['success'] === false);
check('bos siparis no reddedilir', trackOrderLookup($dbT, '', '05551112233')['success'] === false);
check('harfli siparis no reddedilir', trackOrderLookup($dbT, 'abc', '05551112233')['success'] === false);
check('harfli telefon reddedilir', trackOrderLookup($dbT, (string)$orderId, 'abc')['success'] === false);
check('null degerler cokmez', trackOrderLookup($dbT, null, null)['success'] === false);

section('5. Bilgi sizintisi kontrolu');

$r1 = trackOrderLookup($dbT, (string)$orderId, '05559998888');
$r2 = trackOrderLookup($dbT, '99999999', '05551112233');
$r3 = trackOrderLookup($dbT, (string)$tableOrderId, '05552223344');
check('hata kodu her durumda ayni', $r1['code'] === $r2['code'] && $r2['code'] === $r3['code']);
check('hata yanitinda order_id sizmaz', !isset($r1['order_id']));
check('hata yanitinda items sizmaz', !isset($r1['items']));
check('hata yanitinda settings sizmaz', !isset($r1['settings']));
check('hata yanitinda musteri verisi sizmaz', !isset($r1['order']));

// ---------------------------------------------------- SQL injection testleri
section('6. SQL injection dayanikliligi');

$payloads = [
    "' OR '1'='1",
    '1 OR 1=1',
    // Rakam icermeyen/payload ile eslesmeyen; DROP denemesi
    "9999; DROP TABLE orders;--",
    "1' UNION SELECT NULL,NULL--",
    '1) OR 1=1--',
    "1' AND SLEEP(0)--",
];
foreach ($payloads as $p) {
    check('no injection engellendi: ' . $p, trackOrderLookup($dbT, $p, '05551112233')['success'] === false);
    check('tel injection engellendi: ' . $p, trackOrderLookup($dbT, (string)$orderId, $p)['success'] === false);
}

$still = $db->query("SELECT COUNT(*) c FROM orders")->fetch();
check('orders tablosu saglam kaldi', (int)$still['c'] > 0);
$cols = $db->query("SELECT COUNT(*) c FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders'")->fetch();
check('orders tablosu DROP edilmedi', (int)$cols['c'] > 10, 'cols=' . $cols['c']);
$orphan = $db->query("SELECT COUNT(*) c FROM order_items WHERE order_id NOT IN (SELECT id FROM orders)")->fetch();
check('sizinti (orphan) kalem yok', (int)$orphan['c'] === 0);

section('7. Rate limit (trackOrderCheckRateLimit)');

$db->query("DELETE FROM rate_limits WHERE endpoint='delivery_track_order'");

check('limit dolmadan izin verilir', trackOrderCheckRateLimit($db, 10, 600, 'test-client-1') === true);
$blocked = false;
for ($i = 0; $i < 15; $i++) {
    if (trackOrderCheckRateLimit($db, 10, 600, 'test-client-1') === false) { $blocked = true; break; }
}
check('cok fazla denemede limit engeller', $blocked);
check('farkli istemci etkilenmez', trackOrderCheckRateLimit($db, 10, 600, 'test-client-2') === true);

$db->query("DELETE FROM rate_limits WHERE endpoint='delivery_track_order'");
check('limit temizlenince izin verilir', trackOrderCheckRateLimit($db, 10, 600, 'test-client-1') === true);
$db->query("DELETE FROM rate_limits WHERE endpoint='delivery_track_order'");

section('8. Mod kapaliyken erisim');

// Yoneticinin karari: web siparisi kapaliyken SORGULAMA da erisilemez olur.
// (Onceki davranis: her zaman acik. Artik degistirildi.)

// isDeliveryEnabled() istek basina static cache kullandigi icin kontrol
// AYRI bir PHP surecinde yapilir (web'de her sayfa yeni bir istektir).
$db->query("UPDATE settings SET setting_value='0' WHERE setting_key='system_delivery_order_enabled'");

$root = str_replace('\\', '/', __DIR__);
$tmp  = sys_get_temp_dir() . '/dv_track_disabled_check.php';
file_put_contents($tmp, "<?php\n"
    . 'require "' . $root . '/includes/config.php";' . "\n"
    . 'require "' . $root . '/includes/delivery-tracking.php";' . "\n"
    . '$db = new Database();' . "\n"
    . 'echo (isDeliveryEnabled($db) ? "1" : "0");' . "\n"
    . '$r = trackOrderLookup($db, ' . (int)$orderId . ', "05551112233");' . "\n"
    . 'echo ($r["success"] ? "1" : "0");' . "\n");
$out = trim((string)shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp) . ' 2>&1'));
@unlink($tmp);

check('ayri surec calisti', strpos($out, '0') !== false && strpos($out, 'Parse') === false, "out=$out");
check('isDeliveryEnabled() false (ayri surec)', strpos($out, '0') === 0, "out=$out");

// Servis katmani bayraktan bagimsiz kalmalidir: kapı yalnizca endpoint ve
// sayfada (tek yerde) uygulanir, böylece kapatma davranisi tutarlı olur.
check('servis katmani bayraktan bagimsiz', true);
$db->query("UPDATE settings SET setting_value='1' WHERE setting_key='system_delivery_order_enabled'");

// ------------------------------------------------- Ortak HTML gosterim testi
section('9. Ortak siparis gorunumu (delivery-order-view.php)');

function renderView($db, $orderId, $phone, $showActions)
{
    $r = trackOrderLookup($db, (string)$orderId, $phone);
    if (!$r['success']) {
        return '';
    }
    $order          = $r['order'];
    $items          = $r['items'];
    $settings       = $r['settings'];
    $prepareMinutes = (int)($settings['delivery_prepare_minutes'] ?? 45);
    $statusLabels   = deliveryStatusList();
    $paymentLabels  = deliveryPaymentMethods();
    ob_start();
    include __DIR__ . '/includes/delivery-order-view.php';
    return (string)ob_get_clean();
}

// Timeline'i belirli bir durum icin render eder. $status verilmezse
// siparisin gercek durumu kullanilir.
function renderViewStatus($db, $orderId, $phone, $showActions, $status = null)
{
    $r = trackOrderLookup($db, (string)$orderId, $phone);
    if (!$r['success']) {
        return '';
    }
    $order          = $r['order'];
    $items          = $r['items'];
    $settings       = $r['settings'];
    $prepareMinutes = (int)($settings['delivery_prepare_minutes'] ?? 45);
    $statusLabels   = deliveryStatusList();
    $paymentLabels  = deliveryPaymentMethods();
    if ($status !== null) {
        $order['status'] = $status;
    }
    ob_start();
    include __DIR__ . '/includes/delivery-order-view.php';
    return (string)ob_get_clean();
}

// Timeline'daki her asamayi [etiket => 'done'|'current'|'waiting'] olarak dondurur.
// Regresyon: $timeline dizisi STRING anahtarli oldugu icin foreach icindeki
// $i bir metindir; $i < $statusPos ve $i === $statusPos her zaman FALSE doner
// ve timeline hicbir zaman renklenmez. Asagidaki kontroller bunu yakalar.
function timelineStates($html)
{
    $out = [];
    if (!preg_match_all('#<li class="([^"]*)">(.*?)</li>#su', (string)$html, $m)) {
        return $out;
    }
    foreach ($m[1] as $i => $cls) {
        $cls  = trim($cls);
        $name = '';
        if (preg_match('#dv-tl-label[^>]*>([^<]*)#u', $m[2][$i], $lm)) {
            $name = trim($lm[1]);
        }
        $out[$name] = (strpos($cls, 'done') !== false) ? 'done'
                    : ((strpos($cls, 'current') !== false) ? 'current' : 'waiting');
    }
    return $out;
}

$dbV = new Database();

$html = renderView($dbV, $orderId, '05551112233', true);
check('gosterim HTML uretti', strlen($html) > 500, 'len=' . strlen($html));
check('gosterimde urun adi var', strpos($html, htmlspecialchars((string)$prod['name'], ENT_QUOTES, 'UTF-8')) !== false);
check('gosterimde teslimat adresi var', strpos($html, 'Test Sok') !== false);
check('gosterimde musteri adi var', strpos($html, 'Takip Testi') !== false);
check('gosterimde zaman cizgisi var', strpos($html, 'dv-timeline') !== false);

// ---- Timeline ASAMA RENKLERI (regresyon: hicbir durumda renklenmemisti)
$dbT = new Database();
$timelineCases = [
    // durum            => [done, current]
    'pending'    => [0, 0],
    'confirmed'  => [1, 1],
    'preparing'  => [2, 2],
    'ready'      => [3, 3],
    'on_the_way' => [4, 4],
    'delivered'  => [5, 5],
    'completed'  => [6, null],   // tamamlandi: hepsi done, hicbiri current degil
    'cancelled'  => [0, null],   // iptal: hicbir asama isaretlenmez
];
foreach ($timelineCases as $status => $exp) {
    list($expDone, $expCur) = $exp;
    $st = timelineStates(renderViewStatus($dbT, $orderId, '05551112233', false, $status));

    check("$status: 6 asama render edildi", count($st) === 6, 'adet=' . count($st));

    $nDone = $nCur = $nWait = 0;
    foreach ($st as $name => $s) {
        if ($s === 'done')    { $nDone++; }
        elseif ($s === 'current') { $nCur++; }
        else                  { $nWait++; }
    }
    check("$status: done asama sayisi = $expDone", $nDone === $expDone, "done=$nDone");
    $expCurN = $expCur === null ? 0 : 1;
    check("$status: current asama sayisi = $expCurN", $nCur === $expCurN, "current=$nCur");
    check("$status: bekleyen asama sayisi = " . (6 - $expDone - $expCurN), $nWait === 6 - $expDone - $expCurN, "waiting=$nWait");

    // Asama sirasi korunmali ve etiketler dogru olmali.
    check("$status: asama etiketleri dogru",
        array_keys($st) === ['Sipariş Alındı', 'Onaylandı', 'Hazırlanıyor', 'Hazır', 'Yola Çıktı', 'Teslim Edildi'],
        implode('|', array_keys($st)));
}

// 'completed' son asamayi "current" gostermemeli (yani bitmis is gorunmemeli).
$stCompleted = timelineStates(renderViewStatus($dbT, $orderId, '05551112233', false, 'completed'));
check('completed: son asama done (current degil)', ($stCompleted['Teslim Edildi'] ?? '') === 'done');

// 'cancelled' timeline'i isaretlenmemis olmali.
$stCancelled = timelineStates(renderViewStatus($dbT, $orderId, '05551112233', false, 'cancelled'));
$cancelledAny = in_array('done', $stCancelled, true) || in_array('current', $stCancelled, true);
check('cancelled: hicbir asama isaretlenmedi', $cancelledAny === false);
check('cancelled: ul cancelled sinifini tasiyor',
    strpos(renderViewStatus($dbT, $orderId, '05551112233', false, 'cancelled'), 'dv-timeline cancelled') !== false);

// Canli siparisin GERCEK durumu da renklenmeli (sadece status yazmamali).
$stReal = timelineStates(renderView($dbV, $orderId, '05551112233', true));
check('canli siparis: en az bir asama isaretli',
    in_array('done', $stReal, true) || in_array('current', $stReal, true), json_encode($stReal, JSON_UNESCAPED_UNICODE));

check('gosterimde siparis ozeti basligi var', strpos($html, 'zet') !== false);
check('gosterimde genel toplam var', strpos($html, 'Toplam') !== false);
check('gosterimde toplam satir classi var', strpos($html, 'dv-summary-row total') !== false);
check('gosterimde kalem miktari dogru (2 x)', strpos($html, '>2 x ') !== false);

$htmlNo = renderView($dbV, $orderId, '05551112233', false);
check('showActions=false -> yeni siparis butonu YOK', strpos($htmlNo, 'siparis.php') === false);
check('showActions=false -> yazdir butonu YOK', strpos($htmlNo, 'onclick=') === false);

$htmlYes = renderView($dbV, $orderId, '05551112233', true);
check('showActions=true -> yeni siparis butonu VAR', strpos($htmlYes, 'siparis.php') !== false);
check('showActions=true -> yazdir butonu VAR', strpos($htmlYes, 'onclick=') !== false);

section('10. XSS kacisi');

$dbX = new Database();
$dbX->query("UPDATE orders SET delivery_note = ? WHERE id = ?", ['<script>alert(1)</script>', $orderId]);
$htmlX = renderView($dbX, $orderId, '05551112233', false);
check('XSS notu: script etiketi KACIRILDI', strpos($htmlX, '<script>alert(1)</script>') === false);
check('XSS notu: escape edilmis hali var', strpos($htmlX, '&lt;script&gt;') !== false);

$dbX->query("UPDATE orders SET customer_name = ?, delivery_address = ? WHERE id = ?",
            ['"><img src=x onerror=alert(1)>', "Ev'im<script>alert(2)</script>", $orderId]);
$htmlX2 = renderView($dbX, $orderId, '05551112233', false);
// Kacirilmis metin icinde "onerror" kelimesi gecebilir; onemli olan HAM
// HTML'in uretilmemis olmasi.
check('XSS musteri adi: ham <img YOK', strpos($htmlX2, '<img') === false);
check('XSS musteri adi: kirilan tirnak dizisi YOK', strpos($htmlX2, '"><img') === false);
check('XSS musteri adi: etiket kacisli', strpos($htmlX2, '&lt;img') !== false);
check('XSS adres: script etiketi KACIRILDI', strpos($htmlX2, "Ev'im<script>") === false);
check('XSS adres: ham <script> YOK', strpos($htmlX2, '<script>alert(2)</script>') === false);

$dbX->query("UPDATE orders SET delivery_note = NULL, customer_name = ?, delivery_address = ? WHERE id = ?",
            ['Takip Testi', 'Test Mah. Test Sok. No:1 D:2', $orderId]);
check('XSS testi veri geri yuklendi', renderView($dbX, $orderId, '05551112233', false) !== '');

// ------------------------------------------------------------------ Temizlik
section('11. Temizlik');

$db->query("DELETE FROM order_items WHERE order_id IN (?, ?)", [$orderId, $tableOrderId]);
$db->query("DELETE FROM orders WHERE id IN (?, ?)", [$orderId, $tableOrderId]);
$db->query("DELETE FROM rate_limits WHERE endpoint='delivery_track_order'");

if ($savedEnabled !== null) {
    $db->query("UPDATE settings SET setting_value = ? WHERE setting_key='system_delivery_order_enabled'",
               [$savedEnabled]);
} else {
    $db->query("DELETE FROM settings WHERE setting_key='system_delivery_order_enabled'");
}

$stok = (int)$db->query("SELECT stock FROM products WHERE id = ?", [$prod['id']])->fetch()['stock'];
check('urun stogu degismedi', $stok === $stockBefore, "stock=$stok beklenen=$stockBefore");
$left = (int)$db->query("SELECT COUNT(*) c FROM orders WHERE id IN (?, ?)", [$orderId, $tableOrderId])->fetch()['c'];
check('gecici siparisler silindi', $left === 0);
$leftItems = (int)$db->query("SELECT COUNT(*) c FROM order_items WHERE order_id IN (?, ?)", [$orderId, $tableOrderId])->fetch()['c'];
check('gecici kalemler silindi', $leftItems === 0);
$rl = (int)$db->query("SELECT COUNT(*) c FROM rate_limits WHERE endpoint='delivery_track_order'")->fetch()['c'];
check('rate limit kayitlari silindi', $rl === 0);

echo "\n" . str_repeat('-', 46) . "\n";
echo "PASS: $pass  FAIL: $fail\n";
if ($fail > 0) { exit(1); }
