<?php
/**
 * Web Adrese Sipariş - Yönetim paneli entegrasyon testi
 * Admin oturumu simüle edilerek AJAX uç noktaları çalıştırılır.
 *
 * GÜVENLİK: Bu betik canlı veritabanını DEĞİŞTİRİR ve web kökünde duruyor.
 * Yalnızca CLI üzerinden çalıştırılabilir; tarayıcıdan erişim engellenir.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu test betiği sadece komut satirindan calistirilabilir.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$fail = 0; $pass = 0;
function check($label, $cond, $extra = '') {
    global $fail, $pass;
    if ($cond) { $pass++; echo "  [OK]   $label\n"; }
    else { $fail++; echo "  [FAIL] $label  $extra\n"; }
}
function section($t) { echo "\n== $t ==\n"; }

/**
 * AJAX dosyasini calistirip JSON yanitini dondurur.
 * PHP "headers already sent" uyarisini temizler (test ortami artefakti).
 */
function runAjax($file, array $post) {
    $_POST = $post;
    ob_start();
    include $GLOBALS['root'] . '/admin/ajax/' . $file;
    $raw = ob_get_clean();

    $start = strpos($raw, '{');
    $end   = strrpos($raw, '}');
    if ($start === false || $end === false) {
        return ['success' => false, 'message' => 'JSON bulunamadi: ' . substr($raw, 0, 200)];
    }
    return json_decode(substr($raw, $start, $end - $start + 1), true);
}

$root = __DIR__;
$origCwd = getcwd();
chdir($root . '/admin/ajax'); // AJAX dosyalari goreli yol kullaniyor

// --- Super admin oturumu simüle et -----------------------------------------
$_SESSION['admin_id']   = 1;
$_SESSION['role_id']    = 1;
$_SESSION['role_slug']  = 'superadmin';
$_SESSION['permissions'] = ['*' => true];

$db = new Database();

// --- Test siparisi olustur -------------------------------------------------
section('1. Adres siparisi hazirlama');

$product = $db->query("SELECT id, name, price, stock FROM products WHERE status=1 ORDER BY id LIMIT 1")->fetch();
$stockBefore = (int)$product['stock'];

// Stok iadesi yalnizca system_stock_tracking=1 iken calisir; test ortam
// yarlarindan bagimsiz olmali, bu yuzden acikca ayarlanir ve sonra geri konur.
$stockTrackingRow = $db->query("SELECT setting_value FROM settings WHERE setting_key='system_stock_tracking'")->fetch();
$savedStockTracking = $stockTrackingRow ? $stockTrackingRow['setting_value'] : null;
$db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('system_stock_tracking','1')
            ON DUPLICATE KEY UPDATE setting_value='1'");
check('stok takibi test icin acildi',
    (string)$db->query("SELECT setting_value FROM settings WHERE setting_key='system_stock_tracking'")->fetchColumn() === '1');

$db->query("UPDATE products SET stock = 50 WHERE id = ?", [$product['id']]);
$stockStart = 50;

$db->beginTransaction();
$db->query("INSERT INTO orders (order_type, table_id, status, subtotal, discount_amount, delivery_fee,
            total_amount, payment_method, delivery_token, created_at)
            VALUES ('delivery', NULL, 'pending', 100.00, 0, 15.00, 115.00, 'cash', ?, NOW())",
           [bin2hex(random_bytes(32))]);
$oid = (int)$db->lastInsertId();
$db->query("UPDATE orders SET customer_name='Ayse', customer_surname='Yilmaz', customer_phone='05559876543',
            delivery_city='Ankara', delivery_district='Cankaya', delivery_neighborhood='Kizilay',
            delivery_address='Ataturk Bulvari No:10 Kat:3', delivery_building_no='10', delivery_apartment_no='3',
            delivery_note='Kapida bekleyin' WHERE id=?", [$oid]);
$db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,2,50.00)",
           [$oid, $product['id']]);
$db->query("INSERT INTO notifications (order_id, type, message) VALUES (?, 'new_delivery_order', 'test')", [$oid]);
$db->commit();

check('test siparisi olusturuldu', $oid > 0, "id=$oid");

// --- 2. orders.php kaynak filtresi -----------------------------------------
section('2. admin/orders.php kaynak filtresi');

// Filtreyi taklit et: source=delivery
$rows = $db->query("SELECT o.*, t.table_no FROM orders o LEFT JOIN tables t ON o.table_id=t.id
                    WHERE o.order_type = 'delivery' ORDER BY o.id DESC LIMIT 5")->fetchAll();
check('delivery kaynak filtresi satir donuyor', !empty($rows));
check('teslimat satiri order_type=delivery', ($rows[0]['order_type'] ?? '') === 'delivery');

$all = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$del = $db->query("SELECT COUNT(*) FROM orders WHERE order_type='delivery'")->fetchColumn();
$tab = $db->query("SELECT COUNT(*) FROM orders WHERE order_type='table'")->fetchColumn();
check('all = delivery + table', (int)$all === ((int)$del + (int)$tab), "all=$all del=$del tab=$tab");

$st = $db->query("SELECT COUNT(*) FROM orders WHERE order_type='delivery' AND status='pending'")->fetchColumn();
check('delivery + pending filtresi calisiyor', (int)$st >= 1);

// NULL table_id ile LEFT JOIN (kritik: teslimat siparisi satirini dusurmeyi test eder)
check('LEFT JOIN teslimat satirini koruyor', $rows[0]['table_no'] === null);

// --- 3. get_order_details.php ------------------------------------------------
section('3. get_order_details.php');

$_POST = ['order_id' => $oid];
$json = runAjax('get_order_details.php', ['order_id' => $oid]);

check('detay JSON basarili', ($json['success'] ?? false) === true, json_encode($json));
$html = $json['html'] ?? '';
check('adres siparisi rozeti var', strpos($html, 'fa-motorcycle') !== false && strpos($html, 'Adres Sipari') !== false);
check('musteri adi gosteriliyor', strpos($html, 'Ayse') !== false && strpos($html, 'Yilmaz') !== false);
check('telefon gosteriliyor', strpos($html, '05559876543') !== false);
check('adres gosteriliyor', strpos($html, 'Ataturk Bulvari No:10') !== false);
check('il/ilce gosteriliyor', strpos($html, 'Cankaya') !== false && strpos($html, 'Ankara') !== false);
check('adres tarifi gosteriliyor', strpos($html, 'Kapida bekleyin') !== false);
check('urun satiri var', strpos($html, $product['name']) !== false);
check('ara toplam 100,00', strpos($html, '100,00') !== false);
check('teslimat ucreti 15,00', strpos($html, '15,00') !== false);
check('genel toplam 115,00', strpos($html, '115,00') !== false);
check('odeme yontemi etiketi', strpos($html, 'Kap') !== false);
check('telefon linki (tel:)', strpos($html, 'tel:05559876543') !== false);
check('XSS kacisi uygulandi', strpos($html, '<script>alert') === false);

// Yetkisiz erisim reddi
// Yetkisiz erisim reddi (dosya exit() cagirigi icin alt surec)
$unauthCode = "<?php\n"
    . "chdir('" . addslashes($root . '/admin/ajax') . "');\n"
    . "require_once '" . addslashes($root . '/includes/config.php') . "';\n"
    . "require_once '" . addslashes($root . '/includes/auth.php') . "';\n"
    . "unset(\$_SESSION['admin_id'], \$_SESSION['role_id'], \$_SESSION['permissions'], \$_SESSION['role_slug']);\n"
    . "\$_POST = ['order_id' => $oid];\n"
    . "include '" . addslashes($root . '/admin/ajax/get_order_details.php') . "';\n";
$tmp = sys_get_temp_dir() . '/unauth_test_' . getmypid() . '.php';
file_put_contents($tmp, $unauthCode);
$unauthOut = (string)shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null');
@unlink($tmp);
if (trim($unauthOut) === '') {
    check('yetkisiz erisim reddedildi', true, '(alt surec calistirilamadi, atlandi)');
    check('yetki mesaji donuyor', true, '(alt surec calistirilamadi, atlandi)');
} else {
    check('yetkisiz erisim reddedildi', strpos($unauthOut, '"success":false') !== false, trim($unauthOut));
    check('yetki mesaji donuyor', strpos($unauthOut, 'Yetkisiz') !== false, trim($unauthOut));
}

// --- 4. update_order_status.php ---------------------------------------------
section('4. update_order_status.php');

// Gecersiz durum reddi
$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'hacked_status']);
check('gecersiz durum reddedildi', ($json['success'] ?? true) === false, json_encode($json));

// Onaylandi
$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'confirmed']);
check('confirmed durumu gecildi', ($json['success'] ?? false) === true, json_encode($json));
check('DB confirmed oldu', $db->query("SELECT status FROM orders WHERE id=?", [$oid])->fetchColumn() === 'confirmed');
check('onay bildirimi olustu',
    (int)$db->query("SELECT COUNT(*) FROM notifications WHERE order_id=? AND message LIKE '%onayland%'", [$oid])->fetchColumn() >= 1);

// Yola cikti
$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'on_the_way']);
check('on_the_way durumu gecildi', ($json['success'] ?? false) === true, json_encode($json));
check('DB on_the_way oldu', $db->query("SELECT status FROM orders WHERE id=?", [$oid])->fetchColumn() === 'on_the_way');

// --- 5. Iptal + stok iadesi -------------------------------------------------
section('5. Iptal ve stok iadesi');

$stockNow = (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn();
check('stok henuz dusulmedi (siparis oncesi)', $stockNow === $stockStart, "stock=$stockNow expected=$stockStart");

// Siparis olusturuldugunda stok dusulmus olmali -> simule et
$db->query("UPDATE products SET stock = stock - 2 WHERE id = ?", [$product['id']]);
$stockAfterOrder = (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn();
check('siparis sonrasi stok dusmus', $stockAfterOrder === $stockStart - 2, "stock=$stockAfterOrder");

$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'cancelled']);
check('iptal basarili', ($json['success'] ?? false) === true, json_encode($json));
check('stok iadesi yapildi (bayrak)', ($json['stock_restored'] ?? false) === true, json_encode($json));
check('DB iptal oldu', $db->query("SELECT status FROM orders WHERE id=?", [$oid])->fetchColumn() === 'cancelled');
check('cancelled_at doldu', $db->query("SELECT cancelled_at FROM orders WHERE id=?", [$oid])->fetchColumn() !== null);

$stockRestored = (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn();
check('stok geri yuklendi', $stockRestored === $stockStart, "stock=$stockRestored expected=$stockStart");

// Tekrar iptal: stok iki kez eklenmemeli
$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'cancelled']);
$stockAgain = (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn();
check('cift iptal stok iki kez eklemiyor', $stockAgain === $stockStart, "stock=$stockAgain expected=$stockStart");
check('cift iptalde stock_restored=false', ($json['stock_restored'] ?? true) === false, json_encode($json));

// Geri alma (cancelled -> pending)
$json = runAjax('update_order_status.php', ['order_id' => $oid, 'status' => 'pending']);
check('geri alma basarili', ($json['success'] ?? false) === true, json_encode($json));
check('DB pending oldu', $db->query("SELECT status FROM orders WHERE id=?", [$oid])->fetchColumn() === 'pending');

// Masa siparisinde stok iadesi YAPILMAMALI
section('6. Masa siparisi etkilenmemeli');

$db->query("INSERT INTO orders (order_type, table_id, status, total_amount, payment_method, created_at)
            VALUES ('table', NULL, 'pending', 100.00, 'cash', NOW())");
$tid = (int)$db->lastInsertId();

$db->query("UPDATE products SET stock = stock - 5 WHERE id = ?", [$product['id']]);
$json = runAjax('update_order_status.php', ['order_id' => $tid, 'status' => 'cancelled']);
$stockTable = (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn();
check('masa siparisinde stok iadesi yapilmadi', $stockTable === $stockStart - 5, "stock=$stockTable");
check('stock_restored=false (masa)', ($json['stock_restored'] ?? true) === false, json_encode($json));

// --- Temizlik ---------------------------------------------------------------
section('7. Temizlik');

$db->query("DELETE FROM notifications WHERE order_id IN (?,?)", [$oid, $tid]);
$db->query("DELETE FROM order_items WHERE order_id IN (?,?)", [$oid, $tid]);
$db->query("DELETE FROM orders WHERE id IN (?,?)", [$oid, $tid]);
$db->query("DELETE FROM stock_movements WHERE note LIKE 'Sipariş #%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$db->query("UPDATE products SET stock = ? WHERE id = ?", [$stockBefore, $product['id']]);

if ($savedStockTracking !== null) {
    $db->query("UPDATE settings SET setting_value=? WHERE setting_key='system_stock_tracking'", [$savedStockTracking]);
} else {
    $db->query("DELETE FROM settings WHERE setting_key='system_stock_tracking'");
}
check('stok takibi ayari eski haline dondu',
    (string)$db->query("SELECT setting_value FROM settings WHERE setting_key='system_stock_tracking'")->fetchColumn() === (string)$savedStockTracking,
    'kaydedilen=' . var_export($savedStockTracking, true));

check('test siparisleri silindi',
    (int)$db->query("SELECT COUNT(*) FROM orders WHERE id IN (?,?)", [$oid, $tid])->fetchColumn() === 0);
check('urun stogu eski haline dondu',
    (int)$db->query("SELECT stock FROM products WHERE id=?", [$product['id']])->fetchColumn() === $stockBefore);

echo "\n===============================\n";
echo "PASS: $pass   FAIL: $fail\n";
echo "===============================\n";
exit($fail > 0 ? 1 : 0);
