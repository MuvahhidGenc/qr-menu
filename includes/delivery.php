<?php
/**
 * Web Adrese Sipariş (Online Delivery) Servis Katmanı
 *
 * Bu dosya tüm adres-sipariş iş kurallarını tek yerden toplar:
 *   - Parametre okuma (mevcut `settings` tablosu, system_delivery_* / delivery_*)
 *   - Form doğrulama
 *   - Fiyat / toplam hesaplama (yalnızca backend)
 *   - Sipariş oluşturma (stok düşümü dahil)
 *   - Adres metni oluşturma (admin panelinde gösterim için)
 *
 * Bağımlılıklar: includes/config.php (Database, fonksiyonlar) + includes/cart.php
 */

if (!defined('DELIVERY_CART_KEY')) {
    define('DELIVERY_CART_KEY', 'delivery_cart');
}

/** Adres siparişi durumları (mevcut enum ile uyumlu) */
if (!function_exists('deliveryStatusList')) {
    function deliveryStatusList() {
        return [
            'pending'    => 'Yeni Sipariş',
            'confirmed'  => 'Onaylandı',
            'preparing'  => 'Hazırlanıyor',
            'ready'      => 'Hazır',
            'on_the_way' => 'Yola Çıktı',
            'delivered'  => 'Teslim Edildi',
            'completed'  => 'Tamamlandı',
            'cancelled'  => 'İptal Edildi',
        ];
    }
}

/** Durum etiketi (masa + adres siparişleri ortak kullanım) */
if (!function_exists('deliveryStatusLabel')) {
    /**
     * Sipariş durumunun okunabilir etiketini döner
     *
     * @param string $status
     * @return string
     */
    function deliveryStatusLabel($status) {
        $map = [
            'pending'      => 'Beklemede',
            'confirmed'    => 'Onaylandı',
            'preparing'    => 'Hazırlanıyor',
            'ready'        => 'Hazır',
            'on_the_way'   => 'Yola Çıktı',
            'delivered'    => 'Teslim Edildi',
            'completed'    => 'Tamamlandı',
            'cancelled'    => 'İptal Edildi',
            'partial_paid' => 'Kısmi Ödeme',
        ];
        return $map[$status] ?? ucfirst((string)$status);
    }
}

/** Ödeme yöntemleri */
if (!function_exists('deliveryPaymentMethods')) {
    function deliveryPaymentMethods() {
        return [
            'cash' => 'Kapıda Nakit',
            'card' => 'Kapıda Kart',
            'online' => 'Online Ödeme',
        ];
    }
}

/** Adres formu alanları (required_fields ayarı ile zorunluluk değiştirilir) */
if (!function_exists('deliveryFieldList')) {
    function deliveryFieldList() {
        return [
            'name'        => 'Ad',
            'surname'     => 'Soyad',
            'phone'       => 'Telefon',
            'city'        => 'İl',
            'district'    => 'İlçe',
            'neighborhood'=> 'Mahalle',
            'address'     => 'Adres',
            'building_no' => 'Bina No',
            'apartment_no'=> 'Daire No',
            'note'        => 'Adres Tarifi',
        ];
    }
}

/** Türkiye illeri (teslimat formu için) */
if (!function_exists('deliveryCityList')) {
    function deliveryCityList() {
        return [
            'Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara','Antalya','Ardahan',
            'Artvin','Aydın','Balıkesir','Bartın','Batman','Bayburt','Bilecik','Bingöl','Bitlis','Bolu',
            'Burdur','Bursa','Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne','Elazığ',
            'Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun','Gümüşhane','Hakkâri','Hatay','Iğdır',
            'Isparta','İstanbul','İzmir','Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri',
            'Kırıkkale','Kırklareli','Kırşehir','Kilis','Kocaeli','Konya','Kütahya','Malatya','Manisa',
            'Mardin','Mersin','Muğla','Muş','Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun',
            'Siirt','Sinop','Sivas','Şanlıurfa','Şırnak','Tekirdağ','Tokat','Trabzon','Tunceli','Uşak',
            'Van','Yalova','Yozgat','Zonguldak',
        ];
    }
}

/**
 * Teslimat ayarlarını yükler (varsayılanlarla birleştirilmiş)
 *
 * @param Database $db
 * @return array
 */
function getDeliverySettings($db) {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $defaults = [
        'system_delivery_order_enabled' => '0',
        'delivery_fee'                 => '0.00',
        'delivery_free_over'           => '0.00',
        'delivery_min_order'           => '0.00',
        'delivery_payment_methods'     => 'cash,card',
        'delivery_required_fields'     => 'name,surname,phone,city,district,neighborhood,address',
        'delivery_prepare_minutes'     => '45',
        'delivery_active_days'         => '1,2,3,4,5,6,7',
        'delivery_open_time'           => '10:00',
        'delivery_close_time'          => '23:00',
        'delivery_hour_check'          => '0',
    ];

    try {
        $rows = $db->query(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key LIKE 'delivery%' OR setting_key = 'system_delivery_order_enabled'"
        )->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }

    $settings = $defaults;
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    foreach ($defaults as $k => $v) {
        if (!isset($settings[$k]) || $settings[$k] === '') {
            $settings[$k] = $v;
        }
    }

    $cache = $settings;
    return $cache;
}

/**
 * Web Adrese Sipariş açık mı?
 *
 * @param Database $db
 * @return bool
 */
function isDeliveryEnabled($db) {
    $s = getDeliverySettings($db);
    return isset($s['system_delivery_order_enabled']) && $s['system_delivery_order_enabled'] == '1';
}

/**
 * Müşteriye sunulabilir ödeme yöntemleri (parametreye göre filtrelenmiş)
 *
 * @param Database $db
 * @return array slug => label
 */
function getAvailablePaymentMethods($db) {
    $s = getDeliverySettings($db);
    $all = deliveryPaymentMethods();

    $enabled = array_filter(array_map('trim', explode(',', (string)$s['delivery_payment_methods'])));
    $available = [];
    foreach ($all as $slug => $label) {
        if (in_array($slug, $enabled, true)) {
            $available[$slug] = $label;
        }
    }
    if (empty($available)) {
        $available['cash'] = $all['cash'];
    }
    return $available;
}

/**
 * Zorunlu form alanları listesi
 *
 * @param Database $db
 * @return array
 */
function getRequiredDeliveryFields($db) {
    $s = getDeliverySettings($db);
    $all = array_keys(deliveryFieldList());
    $req = array_filter(array_map('trim', explode(',', (string)$s['delivery_required_fields'])));

    // Sadece tanımlı alanları kabul et
    $req = array_values(array_intersect($req, $all));

    // Ad/Soyad/Telefon her zaman gerekli (teslimat için asgari kimlik)
    foreach (['name', 'surname', 'phone'] as $mandatory) {
        if (!in_array($mandatory, $req, true)) {
            $req[] = $mandatory;
        }
    }
    return $req;
}

/**
 * Çalışma saatleri / gün kontrolü
 *
 * @param Database $db
 * @return array ['open' => bool, 'message' => string]
 */
function checkDeliveryHours($db) {
    $s = getDeliverySettings($db);

    if ((string)$s['delivery_hour_check'] !== '1') {
        return ['open' => true, 'message' => ''];
    }

    $days = array_filter(array_map('trim', explode(',', (string)$s['delivery_active_days'])));
    $today = (int)date('N'); // 1=Pzt ... 7=Paz
    if (!empty($days) && !in_array((string)$today, $days, true)) {
        return ['open' => false, 'message' => 'Bugün teslimat siparişi kabul edilmiyor.'];
    }

    $open = $s['delivery_open_time'] ?? '00:00';
    $close = $s['delivery_close_time'] ?? '23:59';
    $now = date('H:i');

    // Gece yarısını geçen aralık (örn. 18:00 - 02:00) desteklenir
    if ($open <= $close) {
        if ($now < $open || $now > $close) {
            return ['open' => false, 'message' => 'Teslimat siparişleri ' . $open . ' - ' . $close . ' saatleri arasında alınmaktadır.'];
        }
    } else {
        if ($now < $open && $now > $close) {
            return ['open' => false, 'message' => 'Teslimat siparişleri ' . $open . ' - ' . $close . ' saatleri arasında alınmaktadır.'];
        }
    }

    return ['open' => true, 'message' => ''];
}

/**
 * Telefon numarasını normalize eder (Türkiye formatı)
 *
 * @param string $phone
 * @return string
 */
function normalizePhone($phone) {
    $digits = preg_replace('/\D/', '', (string)$phone);
    if ($digits === '') {
        return '';
    }
    // 10 hane: 5XXXXXXXXX -> 055XXXXXXXXX
    if (strlen($digits) === 10 && $digits[0] === '5') {
        $digits = '0' . $digits;
    }
    // 905XXXXXXXXX -> 055XXXXXXXXX
    if (strlen($digits) === 12 && strpos($digits, '90') === 0) {
        $digits = '0' . substr($digits, 2);
    }
    return substr($digits, 0, 15);
}

/**
 * Metin uzunluk sınırı + güvenli temizleme
 *
 * @param string $value
 * @param int    $max
 * @return string
 */
function deliveryCleanText($value, $max = 255) {
    $value = trim(strip_tags((string)$value));
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }
    return substr($value, 0, $max);
}

/**
 * Teslimat formunu doğrular ve normalize eder
 *
 * @param array $raw  Gelen veri
 * @param array $requiredFields getRequiredDeliveryFields() çıktısı
 * @return array [ 'data' => [...], 'errors' => [...] ]
 */
function validateDeliveryForm($raw, $requiredFields) {
    $errors = [];
    $labels = deliveryFieldList();

    $name = deliveryCleanText($raw['name'] ?? '', 100);
    $surname = deliveryCleanText($raw['surname'] ?? '', 100);
    $phone = normalizePhone($raw['phone'] ?? '');
    $city = deliveryCleanText($raw['city'] ?? '', 50);
    $district = deliveryCleanText($raw['district'] ?? '', 50);
    $neighborhood = deliveryCleanText($raw['neighborhood'] ?? '', 100);
    $address = deliveryCleanText($raw['address'] ?? '', 500);
    $buildingNo = deliveryCleanText($raw['building_no'] ?? '', 20);
    $apartmentNo = deliveryCleanText($raw['apartment_no'] ?? '', 20);
    $note = deliveryCleanText($raw['note'] ?? '', 1000);

    $isRequired = function ($field) use ($requiredFields) {
        return in_array($field, $requiredFields, true);
    };

    if ($isRequired('name') && mb_strlen($name) < 2) {
        $errors['name'] = $labels['name'] . ' en az 2 karakter olmalıdır.';
    }
    if ($isRequired('surname') && mb_strlen($surname) < 2) {
        $errors['surname'] = $labels['surname'] . ' en az 2 karakter olmalıdır.';
    }
    if ($isRequired('phone')) {
        if ($phone === '') {
            $errors['phone'] = $labels['phone'] . ' zorunludur.';
        } elseif (!preg_match('/^0[2-5][0-9]{9}$/', $phone) && !preg_match('/^9[0-9]{10}$/', $phone)) {
            $errors['phone'] = 'Geçerli bir telefon numarası giriniz (örn. 05551234567).';
        }
    }
    if ($isRequired('city') && mb_strlen($city) < 2) {
        $errors['city'] = $labels['city'] . ' seçilmelidir.';
    }
    if ($isRequired('district') && mb_strlen($district) < 2) {
        $errors['district'] = $labels['district'] . ' zorunludur.';
    }
    if ($isRequired('neighborhood') && mb_strlen($neighborhood) < 2) {
        $errors['neighborhood'] = $labels['neighborhood'] . ' zorunludur.';
    }
    if ($isRequired('address') && mb_strlen($address) < 10) {
        $errors['address'] = $labels['address'] . ' en az 10 karakter olmalıdır.';
    }

    return [
        'data' => [
            'name'         => $name,
            'surname'      => $surname,
            'phone'        => $phone,
            'city'         => $city,
            'district'     => $district,
            'neighborhood' => $neighborhood,
            'address'      => $address,
            'building_no'  => $buildingNo,
            'apartment_no' => $apartmentNo,
            'note'         => $note,
        ],
        'errors' => $errors,
    ];
}

/**
 * Sepet ürünlerini DB'den doğrulayarak fiyatlandırır (frontend fiyatlarına güvenilmez)
 *
 * @param Database $db
 * @param string   $cartKey
 * @return array ['items' => [...], 'subtotal' => float, 'error' => string|null]
 */
function buildDeliveryCart($db, $cartKey = DELIVERY_CART_KEY) {
    initCart($cartKey);
    $cart = $_SESSION[$cartKey] ?? [];

    $items = [];
    $subtotal = 0.0;
    $invalidIds = [];

    foreach ($cart as $productId => $row) {
        $productId = (int)$productId;
        $quantity = isset($row['quantity']) ? (int)$row['quantity'] : 0;
        if ($productId <= 0 || $quantity <= 0) {
            $invalidIds[] = $productId;
            continue;
        }
        $quantity = min($quantity, 99);

        $product = $db->query(
            "SELECT id, name, price, image, status, stock
             FROM products WHERE id = ?",
            [$productId]
        )->fetch();

        if (!$product || (int)$product['status'] !== 1) {
            $invalidIds[] = $productId;
            continue;
        }

        $price = (float)$product['price'];
        $lineTotal = round($price * $quantity, 2);
        $subtotal += $lineTotal;

        $items[] = [
            'product_id' => (int)$product['id'],
            'name'       => $product['name'],
            'image'      => $product['image'],
            'price'      => $price,
            'quantity'   => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    foreach ($invalidIds as $id) {
        if ($id > 0) {
            unset($_SESSION[$cartKey][$id]);
        }
    }

    return [
        'items'    => $items,
        'subtotal' => round($subtotal, 2),
        'error'    => empty($items) ? 'Sepetinizde satın alınabilir ürün bulunmuyor.' : null,
    ];
}

/**
 * Teslimat ücretini hesaplar
 *
 * @param array $settings getDeliverySettings() çıktısı
 * @param float $subtotal
 * @return float
 */
function calculateDeliveryFee($settings, $subtotal) {
    $fee = (float)($settings['delivery_fee'] ?? 0);
    $freeOver = (float)($settings['delivery_free_over'] ?? 0);

    if ($freeOver > 0 && $subtotal >= $freeOver) {
        return 0.0;
    }
    if ($subtotal <= 0) {
        return 0.0;
    }
    return round(max(0, $fee), 2);
}

/**
 * Toplam tutarı hesaplar
 *
 * @param float $subtotal
 * @param float $discount
 * @param float $deliveryFee
 * @return float
 */
function calculateDeliveryTotal($subtotal, $discount = 0.0, $deliveryFee = 0.0) {
    $discount = max(0.0, min((float)$discount, (float)$subtotal));
    return round((float)$subtotal - $discount + (float)$deliveryFee, 2);
}

/**
 * Adresi okunabilir tek satıra çevirir (admin paneli / fiş)
 *
 * @param array $order orders satırı
 * @return string
 */
function formatDeliveryAddress($order) {
    $parts = array_filter([
        trim((string)($order['delivery_neighborhood'] ?? '')),
        trim((string)($order['delivery_district'] ?? '')),
        trim((string)($order['delivery_city'] ?? '')),
    ]);

    $address = trim((string)($order['delivery_address'] ?? ''));

    $line = trim($address . ' ' . implode(' / ', $parts));

    $extra = [];
    if (!empty($order['delivery_building_no'])) {
        $extra[] = 'Bina No: ' . $order['delivery_building_no'];
    }
    if (!empty($order['delivery_apartment_no'])) {
        $extra[] = 'Daire No: ' . $order['delivery_apartment_no'];
    }
    if (!empty($extra)) {
        $line .= ' (' . implode(', ', $extra) . ')';
    }

    return $line;
}

/**
 * Müşteri takip token'ı üretir (siparişe bağlı benzersiz)
 *
 * @return string
 */
function generateDeliveryToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Stok hareketi kaydeder (stok takibi açıksa stok düşer)
 *
 * @param Database $db
 * @param array    $item  buildDeliveryCart() çıktısındaki ürün satırı
 * @param string   $note  stok_movements.note
 * @return void
 * @throws Exception Stok yetersizse
 */
function deductDeliveryStock($db, $item, $note) {
    $product = $db->query(
        "SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE",
        [$item['product_id']]
    )->fetch();

    if (!$product) {
        throw new Exception($item['name'] . ' bulunamadı.');
    }

    $oldStock = (int)$product['stock'];
    $newStock = $oldStock - (int)$item['quantity'];

    if ($newStock < 0) {
        throw new Exception($product['name'] . ' stokta yok (Kalan: ' . $oldStock . ').');
    }

    $db->query("UPDATE products SET stock = ? WHERE id = ?", [$newStock, $item['product_id']]);

    $db->query(
        "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by, created_at)
         VALUES (?, 'out', ?, ?, ?, ?, NULL, NOW())",
        [$item['product_id'], (int)$item['quantity'], $oldStock, $newStock, $note]
    );
}

/**
 * Adres siparişi iptal edildiğinde stoğu geri alır
 *
 * @param Database $db
 * @param int      $orderId
 * @return void
 */
function restoreDeliveryStock($db, $orderId) {
    $items = $db->query(
        "SELECT product_id, quantity FROM order_items WHERE order_id = ?",
        [$orderId]
    )->fetchAll();

    foreach ($items as $item) {
        $product = $db->query(
            "SELECT stock FROM products WHERE id = ? FOR UPDATE",
            [(int)$item['product_id']]
        )->fetch();
        if (!$product) {
            continue;
        }
        $oldStock = (int)$product['stock'];
        $newStock = $oldStock + (int)$item['quantity'];
        $db->query("UPDATE products SET stock = ? WHERE id = ?", [$newStock, (int)$item['product_id']]);
        $db->query(
            "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by, created_at)
             VALUES (?, 'in', ?, ?, ?, ?, NULL, NOW())",
            [
                (int)$item['product_id'],
                (int)$item['quantity'],
                $oldStock,
                $newStock,
                'Web Adres Siparişi #' . $orderId . ' iptal - stok iadesi',
            ]
        );
    }
}
