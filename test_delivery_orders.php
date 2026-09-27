<?php
/**
 * Web Adrese Sipariş - Geçici test betiği
 * (Dağıtımdan sonra silinebilir)
 *
 * GÜVENLİK: Bu betik canlı veritabanını DEĞİŞTİRİR ve web kökünde duruyor.
 * Yalnızca CLI üzerinden çalıştırılabilir; tarayıcıdan erişim engellenir.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Bu test betiği sadece komut satirindan calistirilabilir.');
}

require_once __DIR__ . '/includes/config.php';

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

// ---------------------------------------------------------------- 1. Ayarlar
section('1. Parametreler ve Ayarlar');

$savedEnabled = null;
$row = $db->query("SELECT setting_value FROM settings WHERE setting_key='system_delivery_order_enabled'")->fetch();
$savedEnabled = $row ? $row['setting_value'] : null;

$db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('system_delivery_order_enabled','1')
            ON DUPLICATE KEY UPDATE setting_value='1'");

$db2 = new Database();
$s = getDeliverySettings($db2);
check('delivery_getter anahtar okuyor', $s['system_delivery_order_enabled'] === '1');
check('isDeliveryEnabled() true', isDeliveryEnabled($db2) === true);
check('required fields: ad/soyad/telefon zorunlu', array_intersect(['name','surname','phone'], getRequiredDeliveryFields($db2)) === ['name','surname','phone']);
check('ödeme yöntemleri cash+card', array_keys(getAvailablePaymentMethods($db2)) === ['cash','card']);
check('deliveryCityList 81 il', count(deliveryCityList()) === 81);
check('deliveryStatusLabel(confirmed)', deliveryStatusLabel('confirmed') === 'Onaylandı');
check('deliveryStatusLabel(on_the_way)', deliveryStatusLabel('on_the_way') === 'Yola Çıktı');

// ---------------------------------------------------------------- 2. Sepet
section('2. Sepet yardımcıları');

$_SESSION['cart'] = [];
$_SESSION[DELIVERY_CART_KEY] = [];

addToCart(1, 2);                       // mevcut imza -> 'cart'
addToCart(1, 1);
check('geriye uyumlu addToCart($id,$qty)', (int)$_SESSION['cart'][1]['quantity'] === 3, json_encode($_SESSION['cart']));
check('getCartCount() varsayılan', getCartCount() === 3);

addToCart(2, 1, DELIVERY_CART_KEY);
check('delivery sepeti ayrı', (int)$_SESSION[DELIVERY_CART_KEY][2]['quantity'] === 1);
check('masa sepeti etkilenmedi', (int)$_SESSION['cart'][1]['quantity'] === 3);
check('getCartCount($key)', getCartCount(DELIVERY_CART_KEY) === 1);

$checkCartAddError = false;
try { addToCart(0, 1); } catch (Exception $e) { $checkCartAddError = true; }
check('geçersiz ürün id reddedilir', $checkCartAddError);

$checkQty = changeCartQuantity(2, 1, DELIVERY_CART_KEY);
check('changeCartQuantity artırır', $checkQty === 2);
$checkQty = changeCartQuantity(2, -5, DELIVERY_CART_KEY);
check('0 olunca sepetten siler', $checkQty === 0 && !isset($_SESSION[DELIVERY_CART_KEY][2]));
removeFromCart(1);
check('removeFromCart varsayılan', !isset($_SESSION['cart'][1]));
clearCart();
check('clearCart boşaltır', empty($_SESSION['cart']));
clearCart(DELIVERY_CART_KEY);

// ---------------------------------------------------------------- 3. Fiyat/Toplam
section('3. Backend fiyat doğrulama');

$product = $db->query("SELECT id, name, price FROM products WHERE status=1 ORDER BY id LIMIT 1")->fetch();
check('test ürünü bulundu', $product !== false);

$_SESSION[DELIVERY_CART_KEY] = [$product['id'] => ['quantity' => 3]];

$cart = buildDeliveryCart($db2);
check('cart boş değil', !empty($cart['items']));
check('fiyat DB den okundu', (float)$cart['items'][0]['price'] === (float)$product['price']);
check('satır toplamı doğru', (float)$cart['items'][0]['line_total'] === round((float)$product['price'] * 3, 2));
check('ara toplam doğru', (float)$cart['subtotal'] === round((float)$product['price'] * 3, 2));

// Frontend fiyat manipülasyonu: session'a sahte fiyat yaz
$_SESSION[DELIVERY_CART_KEY][$product['id']]['price'] = 0.01;
$cart2 = buildDeliveryCart($db2);
check('frontend fiyatı YOK SAYILIR', (float)$cart2['items'][0]['price'] === (float)$product['price']);

// Pasif / olmayan ürün
$offProduct = $db->query("SELECT id FROM products WHERE status=0 LIMIT 1")->fetch();
if ($offProduct) {
    $_SESSION[DELIVERY_CART_KEY][$offProduct['id']] = ['quantity' => 1];
    $cart3 = buildDeliveryCart($db2);
    check('pasif ürün sepetten atılır', count($cart3['items']) === 1);
}

// Stoksuz ürün
$outProduct = $db->query("SELECT id FROM products WHERE stock = 0 AND status = 1 LIMIT 1")->fetch();
if ($outProduct) {
    $_SESSION[DELIVERY_CART_KEY][$outProduct['id']] = ['quantity' => 1];
    $cart4 = buildDeliveryCart($db2);
    check('stok 0 ürün sepetten atılır', !in_array($outProduct['id'], array_column($cart4['items'], 'product_id')));
}

check('calculateDeliveryFee normal', calculateDeliveryFee(['delivery_fee' => '15.00', 'delivery_free_over' => '0'], 100) === 15.0);
check('calculateDeliveryFee ücretsiz eşik', calculateDeliveryFee(['delivery_fee' => '15.00', 'delivery_free_over' => '100'], 150) === 0.0);
check('calculateDeliveryFee eşik altı', calculateDeliveryFee(['delivery_fee' => '15.00', 'delivery_free_over' => '100'], 50) === 15.0);
check('calculateDeliveryTotal', calculateDeliveryTotal(100, 10, 15) === 105.0);
check('indirim subtotal üstüne taşmaz', calculateDeliveryTotal(100, 500, 15) === 15.0);

// ---------------------------------------------------------------- 4. Telefon
section('4. Telefon normalizasyonu & Form doğrulama');

check('5XXXXXXXXX -> 055XXXXXXXXX', normalizePhone('5551234567') === '05551234567');
check('0555... aynı kalır', normalizePhone('05551234567') === '05551234567');
check('90555... -> 0555...', normalizePhone('905551234567') === '05551234567');
check('formatlı numara', normalizePhone('0555 123 45 67') === '05551234567');
check('boş telefon', normalizePhone('') === '');

$req = getRequiredDeliveryFields($db2);
$v = validateDeliveryForm([], $req);
check('boş form -> hata döner', !empty($v['errors']['name']) && !empty($v['errors']['phone']));

$v = validateDeliveryForm([
    'name' => 'Ahmet', 'surname' => 'Yılmaz', 'phone' => '05551234567',
    'city' => 'İstanbul', 'district' => 'Kadıköy', 'neighborhood' => 'Caferağa',
    'address' => 'Moda Caddesi No:123 Kat:4 Daire:7', 'building_no' => '123', 'apartment_no' => '7',
    'note' => 'Zili çalmayın',
], $req);
check('geçerli form -> hata yok', empty($v['errors']), json_encode($v['errors']));
check('veri normalize edildi', $v['data']['phone'] === '05551234567');

$v = validateDeliveryForm([
    'name' => 'Ahmet', 'surname' => 'Yılmaz', 'phone' => '123',
], $req);
check('geçersiz telefon yakalanır', isset($v['errors']['phone']));

// ---------------------------------------------------------------- 5. Adres metni
section('5. Adres biçimlendirme');

$addr = formatDeliveryAddress([
    'delivery_neighborhood' => 'Caferağa', 'delivery_district' => 'Kadıköy',
    'delivery_city' => 'İstanbul', 'delivery_address' => 'Moda Cad. No:1',
    'delivery_building_no' => '5', 'delivery_apartment_no' => '9',
]);
check('adres içerir', strpos($addr, 'Moda Cad. No:1') !== false && strpos($addr, 'Kadıköy') !== false);
check('bina/daire içerir', strpos($addr, 'Bina No: 5') !== false && strpos($addr, 'Daire No: 9') !== false);

// ---------------------------------------------------------------- 6. Stok
section('6. Stok düşme / iade (transaction testi)');

$pid = $product['id'];
$before = (int)$db->query("SELECT stock FROM products WHERE id = ?", [$pid])->fetchColumn();
$db->beginTransaction();
$db->query("UPDATE products SET stock = stock + 10 WHERE id = ?", [$pid]);
$db->commit();

$stockItem = ['product_id' => $pid, 'name' => $product['name'], 'quantity' => 4, 'price' => 1.0];
$db->beginTransaction();
deductDeliveryStock($db, $stockItem, 'TEST');
$mid = (int)$db->query("SELECT stock FROM products WHERE id = ?", [$pid])->fetchColumn();
$db->rollBack();
$after = (int)$db->query("SELECT stock FROM products WHERE id = ?", [$pid])->fetchColumn();

check('deductDeliveryStock stok düşürür', $mid === $after - 4, "mid=$mid after=$after");
check('rollback stok eski haline döner', $after === $before + 10);
check('stok_hareketi rollback ile silindi',
    (int)$db->query("SELECT COUNT(*) FROM stock_movements WHERE note='TEST'")->fetchColumn() === 0);

$err = false;
$db->beginTransaction();
try { deductDeliveryStock($db, ['product_id' => $pid, 'name' => 'x', 'quantity' => 999999, 'price' => 1], 'TEST2'); }
catch (Exception $e) { $err = true; }
$db->rollBack();
check('stok yetersizse hata fırlatır', $err);

// ---------------------------------------------------------------- 7. Sipariş oluşturma
section('7. Sipariş oluşturma (entegrasyon)');

$beforeOrders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$beforeItems = (int)$db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();

$_SESSION[DELIVERY_CART_KEY] = [$pid => ['quantity' => 2]];
$cart = buildDeliveryCart($db2);
$subtotal = $cart['subtotal'];
$fee = calculateDeliveryFee(getDeliverySettings($db2), $subtotal);
$total = calculateDeliveryTotal($subtotal, 0, $fee);

$db->beginTransaction();
$db->query("INSERT INTO orders (order_type, table_id, status, subtotal, discount_amount, delivery_fee,
            total_amount, payment_method, delivery_token, note, created_at)
            VALUES ('delivery', NULL, 'pending', ?, 0, ?, ?, 'cash', ?, 'test siparisi', NOW())",
           [$subtotal, $fee, $total, generateDeliveryToken()]);
$newOrderId = (int)$db->lastInsertId();
$db->query("UPDATE orders SET customer_name='Ahmet', customer_surname='Yılmaz', customer_phone='05551234567',
            delivery_city='İstanbul', delivery_district='Kadıköy', delivery_neighborhood='Caferağa',
            delivery_address='Moda Cad. No:1', delivery_building_no='5', delivery_apartment_no='9',
            delivery_note='Zili çalmayın' WHERE id = ?", [$newOrderId]);
foreach ($cart['items'] as $it) {
    $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)",
               [$newOrderId, $it['product_id'], $it['quantity'], $it['price']]);
}
$db->query("INSERT INTO notifications (order_id, type, message) VALUES (?, 'new_delivery_order', 'test')", [$newOrderId]);
$db->commit();

check('orders kaydı oluştu', (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn() === $beforeOrders + 1);
check('order_items kaydı oluştu', (int)$db->query("SELECT COUNT(*) FROM order_items")->fetchColumn() === $beforeItems + 1);

$o = $db->query("SELECT * FROM orders WHERE id = ?", [$newOrderId])->fetch();
check('order_type = delivery', $o['order_type'] === 'delivery');
check('table_id NULL', $o['table_id'] === null);
check('adres snapshot kaydedildi', $o['delivery_city'] === 'İstanbul' && $o['delivery_apartment_no'] === '9');
check('ödeme yöntemi kaydedildi', $o['payment_method'] === 'cash');
check('token üretildi', strlen((string)$o['delivery_token']) === 64);
check('toplam backend hesaplandı', (float)$o['total_amount'] === $total);
check('notification oluştu',
    (int)$db->query("SELECT COUNT(*) FROM notifications WHERE order_id=? AND type='new_delivery_order'", [$newOrderId])->fetchColumn() === 1);

$oi = $db->query("SELECT price FROM order_items WHERE order_id=?", [$newOrderId])->fetch();
check('order_items fiyatı DB fiyatı', (float)$oi['price'] === (float)$product['price']);

// Durum beyaz listesi testi
$enumValues = $db->query("SHOW COLUMNS FROM orders LIKE 'status'")->fetch();
$statusEnum = $enumValues['Type'];
check('enum confirmed içeriyor', strpos($statusEnum, 'confirmed') !== false);
check('enum on_the_way içeriyor', strpos($statusEnum, 'on_the_way') !== false);
check('enum eski değerler korundu',
    strpos($statusEnum, "'pending'") !== false && strpos($statusEnum, "'partial_paid'") !== false
    && strpos($statusEnum, "'delivered'") !== false && strpos($statusEnum, "'completed'") !== false);

// Durum güncelleme
$db->query("UPDATE orders SET status='cancelled', cancelled_at=NOW() WHERE id=?", [$newOrderId]);
check('durum iptal edilebiliyor', $db->query("SELECT status FROM orders WHERE id=?", [$newOrderId])->fetchColumn() === 'cancelled');
$db->query("UPDATE orders SET status='cancelled', cancelled_at=NOW() WHERE id=?", [$newOrderId]);
$db->query("UPDATE orders SET status='on_the_way' WHERE id=?", [$newOrderId]);
check('durum yola çıktı yapılabiliyor', $db->query("SELECT status FROM orders WHERE id=?", [$newOrderId])->fetchColumn() === 'on_the_way');

// ---------------------------------------------------------------- 8. Temizlik
section('8. Temizlik');

$db->query("DELETE FROM notifications WHERE order_id = ?", [$newOrderId]);
$db->query("DELETE FROM order_items WHERE order_id = ?", [$newOrderId]);
$db->query("DELETE FROM orders WHERE id = ?", [$newOrderId]);
$db->query("UPDATE products SET stock = ? WHERE id = ?", [$before, $pid]);
$db->query("DELETE FROM stock_movements WHERE note IN ('TEST','TEST2')");
$_SESSION['cart'] = [];
$_SESSION[DELIVERY_CART_KEY] = [];

check('test kaydı silindi', (int)$db->query("SELECT COUNT(*) FROM orders WHERE id=?", [$newOrderId])->fetchColumn() === 0);
check('stok eski haline döndü', (int)$db->query("SELECT stock FROM products WHERE id=?", [$pid])->fetchColumn() === $before);
check('sipariş sayısı eski halinde', (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn() === $beforeOrders);

// Parametre eski haline
if ($savedEnabled !== null) {
    $db->query("UPDATE settings SET setting_value=? WHERE setting_key='system_delivery_order_enabled'", [$savedEnabled]);
} else {
    $db->query("DELETE FROM settings WHERE setting_key='system_delivery_order_enabled'");
}

echo "\n===============================\n";
echo "PASS: $pass   FAIL: $fail\n";
echo "===============================\n";
exit($fail > 0 ? 1 : 0);
