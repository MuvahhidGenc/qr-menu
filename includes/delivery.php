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
 * Bu anahtar SADECE web adres siparişini (siparis.php, siparis-tamamla.php,
 * siparis-takip.php) kontrol eder. QR masa menüsünden (index.php) tamamen
 * bağımsızdır; biri kapatıldığında diğeri etkilenmez.
 *
 * @param Database $db
 * @return bool
 */
function isDeliveryEnabled($db) {
    $s = getDeliverySettings($db);
    return isset($s['system_delivery_order_enabled']) && $s['system_delivery_order_enabled'] == '1';
}

/**
 * QR üzerinden masa siparişi (index.php?table=N) açık mı?
 *
 * Bu anahtar SADECE QR/masa akışını kontrol eder; web adres siparişini
 * (siparis.php) etkilemez.
 *
 * @param Database $db
 * @return bool
 */
function isTableQrOrderEnabled($db) {
    $s = getSystemSettings($db);
    return isset($s['system_table_qr_order_enabled'])
        && $s['system_table_qr_order_enabled'] == '1';
}

/**
 * Yönetim panelindeki sipariş listesinin "kaynak" değerini tek yerden çözer.
 *
 * İki menü öğesi (Masa Siparişleri / Web Siparişleri) bu SONUCU kullanmalıdır;
 * aksi halde menüde vurgulanan öğe ile listede gösterilen kaynak tutmaz.
 * Örn. QR kapalı + web açıkken kaynak 'delivery' olur; menüde "Web Siparişleri"
 * vurgulanmalıdır, verilen 'source' parametresine bakmadan.
 *
 * @param string|null $requested  URL'den gelen kaynak (all|table|delivery|null)
 * @param bool $tableQrOn         QR/masa siparişi anahtarı
 * @param bool $webOrderOn        Web adres siparişi anahtarı
 * @return string all|table|delivery
 */
function resolveOrdersSource($requested, $tableQrOn, $webOrderOn) {
    if (in_array($requested, ['all', 'table', 'delivery'], true)) {
        return $requested;
    }
    if (!$tableQrOn && $webOrderOn) {
        return 'delivery';
    }
    if ($tableQrOn && !$webOrderOn) {
        return 'table';
    }
    return 'all';
}

/**
 * Yönetim paneli sipariş menüsü için hangi öğelerin görüneceğini belirler.
 *
 * @param bool $tableQrOn
 * @param bool $webOrderOn
 * @return array ['table' => bool, 'delivery' => bool]
 */
function visibleOrdersMenus($tableQrOn, $webOrderOn) {
    return [
        'table'    => (bool)$tableQrOn,
        'delivery' => (bool)$webOrderOn,
    ];
}

/**
 * Tüm sistem_* anahtarlarını tek seferde okur (istek başına önbellekli).
 *
 * @param Database $db
 * @return array
 */
function getSystemSettings($db) {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    try {
        $rows = $db->query(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'system_%'"
        )->fetchAll();
        foreach ($rows as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    } catch (Exception $e) {
        error_log('getSystemSettings hatası: ' . $e->getMessage());
    }
    return $cache;
}

/**
 * QR menüsünde sipariş butonu açık mı?
 *
 * @param Database $db
 * @return bool
 */
function isAcceptQrOrders($db) {
    $s = getSystemSettings($db);
    return isset($s['system_accept_qr_orders']) && $s['system_accept_qr_orders'] == '1';
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
 * Arama için Türkçe duyarsız katlama (fold)
 *
 * MySQL'in utf8mb4_general_ci collation'ı ASCII'de büyük/küçük harf
 * ayrımını yapmaz ama TÜRKÇE harfleri birbirinden AYIRIR:
 *   'I' (I) , 'ı' (i) , 'İ' (İ) , 'i' (i)  ->  birbirinden farklıdır
 * Bu yüzden saf SQL LIKE ile "büyük küçük harf dikkat edilmesin"
 * garantisi VERİLEMEZ; normalize edilmiş metin üzerinde çalışmak gerekir.
 *
 * Kritik özellik: fonksiyon GİRİŞTEKİ HER KARAKTERE TAM OLARAK BİR
 * KARAKTER DÖNDÜRÜR (1:1). Böylece katlanmış metindeki karakter
 * konumları özgün metinle birebir hizalıdır ve dvHighlightMatch()
 * güvenle çalışabilir.
 *
 * @param string $value
 * @return string
 */
function dvSearchFold($value) {
    static $map = [
        // ı İ I i  -> i      (Türkçe I/ı ayrımı tamamen ortadan kalkar)
        "\u{0131}" => 'i', "\u{0130}" => 'i', 'I' => 'i', 'i' => 'i',
        // ş Ş S s  -> s
        "\u{015F}" => 's', "\u{015E}" => 's', 'S' => 's', 's' => 's',
        // ğ Ğ G g  -> g
        "\u{011F}" => 'g', "\u{011E}" => 'g', 'G' => 'g', 'g' => 'g',
        // ü Ü U u  -> u
        "\u{00FC}" => 'u', "\u{00DC}" => 'u', 'U' => 'u', 'u' => 'u',
        // ö Ö O o  -> o
        "\u{00F6}" => 'o', "\u{00D6}" => 'o', 'O' => 'o', 'o' => 'o',
        // ç Ç C c  -> c
        "\u{00E7}" => 'c', "\u{00C7}" => 'c', 'C' => 'c', 'c' => 'c',
        // ğ/ş dışında kalan yaygın aksanlı harfler
        "\u{015F}" => 's', 'a' => 'a', "\u{00E0}" => 'a', 'A' => 'a',
        'e' => 'e', "\u{00E8}" => 'e', 'E' => 'e',
        'n' => 'n', "\u{00F1}" => 'n', 'N' => 'n',
        'r' => 'r', "\u{0159}" => 'r', 'R' => 'r',
        'y' => 'y', 'Y' => 'y', 'z' => 'z', 'Z' => 'z',
        'b' => 'b', 'B' => 'b', 'd' => 'd', 'D' => 'd',
        'f' => 'f', 'F' => 'f', 'h' => 'h', 'H' => 'h',
        'j' => 'j', 'J' => 'j', 'k' => 'k', 'K' => 'k',
        'l' => 'l', 'L' => 'l', 'm' => 'm', 'M' => 'm',
        'p' => 'p', 'P' => 'p', 'q' => 'q', 'Q' => 'q',
        't' => 't', 'T' => 't', 'v' => 'v', 'V' => 'v',
        'w' => 'w', 'W' => 'w', 'x' => 'x', 'X' => 'x',
    ];

    $s = (string)$value;
    $out = '';
    $len = function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);

    for ($i = 0; $i < $len; $i++) {
        $ch = function_exists('mb_substr') ? mb_substr($s, $i, 1, 'UTF-8') : $s[$i];
        if (isset($map[$ch])) {
            $out .= $map[$ch];                 // 1 karakter -> 1 karakter
        } else {
            // Aksansız kalanlar: sadece küçült, uzunluk DEĞİŞMEZ
            $out .= function_exists('mb_strtolower')
                ? mb_strtolower($ch, 'UTF-8')
                : strtolower($ch);
        }
    }

    return $out;
}

/**
 * Arama terimini kelimelere ayırır (çoklu terim = VE mantığı)
 *
 * @param string $value
 * @param int    $maxTerim
 * @return string[] Her biri dvSearchFold() uygulanmış, 1+ karakter
 */
function dvSearchTerms($value, $maxTerim = 6) {
    $folded = dvSearchFold($value);
    $parts  = preg_split('/\s+/u', trim($folded), -1, PREG_SPLIT_NO_EMPTY);
    $out    = [];
    foreach ((array)$parts as $p) {
        // Joker karakterler aramada anlamsız; LIKE'a girmeden atılır.
        $p = str_replace(['%', '_'], '', $p);
        if ($p !== '' && !in_array($p, $out, true)) {
            $out[] = $p;
        }
        if (count($out) >= $maxTerim) break;
    }
    return $out;
}

/**
 * Ürünü arama terimlerine göre puanlar ( relevance )
 *
 * Puanlaması küçükten büyüğe:  en iyi eşleşme = 0
 *   0 = ürün adı TAMAMEN terim
 *   1 = ürün adı terimle BAŞLIYOR
 *   2 = ürün adı terimi İÇERİYOR
 *   3 = kategori adı terimi içeriyor
 *   4 = açıklama terimi içeriyor
 *
 * Çoklu terimlerde (VE mantığı) EN ZAYIF alan esas alınır: ürün adında
 * hem de açıklamada geçen bir arama, yalnızca açıklamada geçene göre öne geçer.
 *
 * @param array    $product  'name', 'description', 'category_name' içermeli
 * @param string[] $terms    dvSearchTerms() çıktısı
 * @return int|null null = eşleşme yok
 */
function dvSearchScore(array $product, array $terms) {
    if (!$terms) return null;

    $name = dvSearchFold($product['name'] ?? '');
    $desc = dvSearchFold($product['description'] ?? '');
    $cat  = dvSearchFold($product['category_name'] ?? '');

    $worst = 0;
    foreach ($terms as $t) {
        $posName = mb_strpos($name, $t, 0, 'UTF-8');
        if ($posName === 0) {
            $score = 0;                                   // tam ad
        } elseif ($posName !== false) {
            $score = 1;                                   // ön ek
        } elseif (mb_strpos($name, $t, 0, 'UTF-8') !== false) {
            $score = 2;                                   // ad içinde
        } elseif ($cat !== '' && mb_strpos($cat, $t, 0, 'UTF-8') !== false) {
            $score = 3;                                   // kategoride
        } elseif ($desc !== '' && mb_strpos($desc, $t, 0, 'UTF-8') !== false) {
            $score = 4;                                   // açıklamada
        } else {
            return null;                                  // bu terim hiçbir yerde yok
        }
        if ($score > $worst) $worst = $score;
    }
    return $worst;
}

/**
 * Özgün metin içinde eşleşen terimleri <mark> ile vurgular
 *
 * dvSearchFold() 1:1 karakter koruduğu için katlanmış metindeki
 * konumlar özgün metinle hizalıdır; güvenli biçimde vurgulayabiliriz.
 *
 * @param string   $text  Özgün (vurgulanacak) metin
 * @param string[] $terms dvSearchTerms() çıktısı
 * @return string HTML-escape edilmiş ve vurgulanmış metin
 */
function dvHighlightMatch($text, array $terms) {
    $text = (string)$text;
    if (!$terms || $text === '') {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    $folded = dvSearchFold($text);
    $len    = function_exists('mb_strlen') ? mb_strlen($folded, 'UTF-8') : strlen($folded);

    // Katlanmış koordinattaki eşleşme aralıklarını topla
    $marks = [];
    foreach ($terms as $t) {
        $offset = 0;
        while ($offset < $len) {
            $p = mb_strpos($folded, $t, $offset, 'UTF-8');
            if ($p === false) break;
            $marks[] = [$p, $p + mb_strlen($t, 'UTF-8')];
            $offset = $p + max(1, mb_strlen($t, 'UTF-8'));
        }
    }
    if (!$marks) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    usort($marks, function ($a, $b) { return $a[0] <=> $b[0]; });

    // Çakışan/yan yana aralıkları birleştir
    $merged = [];
    foreach ($marks as $m) {
        $last = count($merged) - 1;
        if ($last >= 0 && $m[0] <= $merged[$last][1]) {
            $merged[$last][1] = max($merged[$last][1], $m[1]);
        } else {
            $merged[] = $m;
        }
    }

    $out = '';
    $cur = 0;
    foreach ($merged as $m) {
        if ($m[0] > $cur) {
            $out .= htmlspecialchars(mb_substr($text, $cur, $m[0] - $cur, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        }
        $out .= '<mark class="dv-hl">'
              . htmlspecialchars(mb_substr($text, $m[0], $m[1] - $m[0], 'UTF-8'), ENT_QUOTES, 'UTF-8')
              . '</mark>';
        $cur = $m[1];
    }
    if ($cur < mb_strlen($text, 'UTF-8')) {
        $out .= htmlspecialchars(mb_substr($text, $cur, null, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    }
    return $out;
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
