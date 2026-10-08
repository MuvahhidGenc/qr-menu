<?php
/**
 * Kanonik satış / ciro katmanı.
 * ---------------------------------------------------------------------
 * Bu dosya, dashboard + raporlar + finans sayfalarının HANGİ ciroyu
 * göstereceğini tek bir yerde tanımlar. Önceden her sayfa kendi sorgusunu
 * yazıyordu ve hepsi farklı (ve çoğu yanlış) sonuç veriyordu.
 *
 * Veri modeli gerçeği
 * -------------------
 * Uygulamada üç ayrı satış kanalı vardır ve HER BİRİ FARKLI TABLOYU yazar:
 *
 *   Kanal     | Ne yazar                              | Nasıl ayırt edilir
 *   ----------+---------------------------------------+-----------------------------
 *   Masa      | orders(order_type='table')            | orders.table_id IS NOT NULL
 *   POS/Kasa  | YALNIZCA payments (orders yazılmaz)  | payments.table_id IS NULL
 *   Web/Online| orders(order_type='delivery')        | order_type = 'delivery'
 *
 * Çift sayım neden eskiden oluyordu?
 *   Tamamlanmış bir masa siparişi hem `orders` hem de `payments` tablosunda
 *   tutar ve `orders.total_amount`, bağlı `payments.total_amount` DEĞERİNİN
 *   kopyasıdır. Bu yüzden `SUM(orders.total_amount) + SUM(payments.total_amount)`
 *   ifadesi aynı parayı iki kez sayar. Ölçülen çift sayım: 960 + 500 = 1460 TL
 *   (gerçek ciro 1460 DEĞİL, tamamlanan kanalların gerçek toplamı 3075 TL).
 *
 *   Ayrıca bir masanın ödemesi kısmi kısmi birden çok `payments` satırına
 *   bölünebiliyor ve bu satırların çoğu `orders.payment_id` ile BAĞLI DEĞİL.
 *   Bu yüzden "orders.payment_id ile eşleşmeyen payment = POS" sezgisi YANLIŞTIR
 *   (kısmi masa tahsilatlarını POS sanırdı). Doğru ayrım `table_id` üzerinedir.
 *
 * Tek kural (her kanal tam olarak bir kez sayılır)
 *   Ciro = SUM(tamamlanmış payments.total_amount)          [masa + POS]
 *        + SUM(tamamlanmış delivery orders.total_amount)    [web]
 *
 *   Gerekçe: tamamlanmış her masa siparişinin EN AZ BİR tamamlanmış
 *   `payments` satırı vardır ve web siparişlerinin HİÇ `payments` satırı yoktur
 *   (teslimat akışı ödeme kaydı üretmez). Bu yüzden iki küme birbirini dışlar.
 *
 * Ödeme yöntemi sözlüğü
 * ---------------------
 * Kanallar farklı değerler yazıyor: `payments` ENUM(cash|pos), teslimat ise
 * serbest metin olarak `cash`/`card` yazıyor. Raporlama tek sözlüğe indirger:
 *   cash -> Nakit, pos|card -> Kart, online -> Online, diğer -> Diğer
 * `method_group()` hem ciro hem de yöntem kırılımı için tek doğruluk kaynağıdır.
 *
 * Bilinçli bir sınır: maliyet
 * ---------------------------
 * Şemada HİÇBİR yerde ürün alış/maliyet fiyatı tutulmuyor (products'ta
 * cost/purchase alanı yok; şema genelinde arama yapıldı, sonuç boş).
 * Bu yüzden burada KÂR hesaplanmaz. Kâr için önce ürün maliyeti tutulmalıdır;
 * uydurma bir kâr rakamı göstermek yanlış olur.
 */

// ---------------------------------------------------------------------
// Kanal sabitleri
// ---------------------------------------------------------------------

/**
 * Raporlanabilir satış kanalları.
 * order_type değeri POS için 'pos' olsa da orders tablosunda POS satırı
 * bulunmadığı için POS kanalı doğrudan payments'ten okunur.
 */
function sales_channels(): array
{
    return [
        'table' => [
            'label'    => 'Masa',
            'icon'     => 'bi-shop-window',
            'color'    => '#0d6efd',
            'source'   => 'payments',
        ],
        'pos' => [
            'label'    => 'POS / Kasa',
            'icon'     => 'bi-upc-scan',
            'color'    => '#6f42c1',
            'source'   => 'payments',
        ],
        'delivery' => [
            'label'    => 'Web / Online',
            'icon'     => 'bi-globe2',
            'color'    => '#198754',
            'source'   => 'orders',
        ],
    ];
}

/** Geçerli kanal anahtarları listesi. */
function sales_channel_keys(): array
{
    return array_keys(sales_channels());
}

/**
 * Kanal anahtarı beyaz listesi. Geçersiz/boş girdide tüm kanallar döner.
 * @return array{0:string,1:bool} [filtrelenmiş anahtarlar, filtre var mı]
 */
function sales_filter_channels($requested): array
{
    $keys = sales_channel_keys();
    if ($requested === null || $requested === '' || $requested === 'all') {
        return [$keys, false];
    }
    $req = is_array($requested) ? $requested : explode(',', (string)$requested);
    $req = array_map('trim', $req);
    $out = array_values(array_intersect($req, $keys));
    if (!$out) {
        return [$keys, false];
    }
    return [$out, true];
}

/**
 * Kanal anahtarını sistem ayarlarına bağlar; kanal kapalıysa ciroya
 * katılmaz. Böylece kapatılan bir sistem ciroyu düşürmez.
 * @param array<string,bool> $flags
 */
function sales_active_channels(array $flags): array
{
    return [
        'table'    => !empty($flags['tableOrders']),
        'pos'      => !empty($flags['posSales']),
        'delivery' => !empty($flags['webOrders']),
    ];
}

// ---------------------------------------------------------------------
// Ödeme yöntemi sözlüğü
// ---------------------------------------------------------------------

/** Ham yöntem -> normalize grup. */
function sales_method_group(?string $method): string
{
    $m = strtolower(trim((string)$method));
    switch ($m) {
        case 'cash':
            return 'cash';
        case 'pos':
        case 'card':
            return 'card';
        case 'online':
            return 'online';
        default:
            return 'other';
    }
}

/** Normalize grup -> görünen etiket. */
function sales_method_label(string $group): string
{
    switch ($group) {
        case 'cash':
            return 'Nakit';
        case 'card':
            return 'Kart';
        case 'online':
            return 'Online';
        default:
            return 'Diğer';
    }
}

// ---------------------------------------------------------------------
// Filtreler
// ---------------------------------------------------------------------

/**
 * Tarih aralığını [from, to_exclusive) half-open biçime çevirir.
 * 'today' | 'week' | 'month' | '7d' | '30d' | '90d' | 'YYYY-MM-DD..YYYY-MM-DD'
 *
 * @return array{0:?string,1:?string} [from, to_exclusive]
 */
function sales_date_range(?string $preset, ?string $custom = null): array
{
    $preset = strtolower(trim((string)$preset));

    // Özel aralık: 2025-10-01..2025-10-31
    if (strpos($preset, '..') !== false) {
        [$a, $b] = explode('..', $preset, 2);
        $a = trim($a);
        $b = trim($b);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $a)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $b)) {
                // Son gün dahil -> ertesi gün exclusive
                return [$a . ' 00:00:00', date('Y-m-d 23:59:59', strtotime($b))];
            }
            return [$a . ' 00:00:00', null];
        }
    }

    switch ($preset) {
        case 'today':
            return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
        case 'yesterday':
            return [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))];
        case 'week':
            // Pazartesi başlangıçlı hafta
            $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
            return [$start, date('Y-m-d 23:59:59', strtotime('+6 days', strtotime($start)))];
        case 'month':
            return [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')];
        case 'last_month':
            return [
                date('Y-m-01 00:00:00', strtotime('first day of last month')),
                date('Y-m-t 23:59:59', strtotime('last day of last month')),
            ];
        case '7d':
            return [date('Y-m-d 00:00:00', strtotime('-6 days')), date('Y-m-d 23:59:59')];
        case '30d':
            return [date('Y-m-d 00:00:00', strtotime('-29 days')), date('Y-m-d 23:59:59')];
        case '90d':
            return [date('Y-m-d 00:00:00', strtotime('-89 days')), date('Y-m-d 23:59:59')];
        case 'all':
        case '':
        default:
            return [null, null];
    }
}

function sales_date_presets(): array
{
    return [
        'today'      => 'Bugün',
        'yesterday'  => 'Dün',
        'week'       => 'Bu Hafta',
        'month'      => 'Bu Ay',
        'last_month' => 'Geçen Ay',
        '7d'         => 'Son 7 Gün',
        '30d'        => 'Son 30 Gün',
        '90d'        => 'Son 90 Gün',
        'all'        => 'Tüm Zamanlar',
    ];
}

// ---------------------------------------------------------------------
// Birleşik satış kaynağı (tek doğruluk kaynağı)
// ---------------------------------------------------------------------

/**
 * Her satış kanalını TEK satır uzayında birleştiren türetilmiş tablo.
 * Her satır tam olarak bir satış olayıdır ve birden fazla kanal yer almaz.
 *
 * @return string SQL
 */
function sales_union_sql(): string
{
    return "(
        SELECT
            p.id                AS ref_id,
            'pos'               AS channel,
            'POS / Kasa'        AS channel_label,
            p.payment_method    AS payment_method,
            p.total_amount      AS net_amount,
            COALESCE(p.subtotal, p.total_amount) AS gross_amount,
            COALESCE(p.discount_amount, 0)      AS discount_amount,
            p.paid_amount       AS collected_amount,
            p.table_id          AS table_id,
            t.table_no          AS table_no,
            p.created_at        AS created_at
          FROM payments p
          LEFT JOIN tables t ON t.id = p.table_id
         WHERE p.status = 'completed' AND p.table_id IS NULL

        UNION ALL

        SELECT
            p.id,
            'table',
            'Masa',
            p.payment_method,
            p.total_amount,
            COALESCE(p.subtotal, p.total_amount),
            COALESCE(p.discount_amount, 0),
            p.paid_amount,
            p.table_id,
            t.table_no,
            p.created_at
          FROM payments p
          LEFT JOIN tables t ON t.id = p.table_id
         WHERE p.status = 'completed' AND p.table_id IS NOT NULL

        UNION ALL

        SELECT
            o.id,
            'delivery',
            'Web / Online',
            o.payment_method,
            o.total_amount,
            COALESCE(o.subtotal, o.total_amount),
            0,
            o.total_amount,
            NULL,
            NULL,
            COALESCE(o.completed_at, o.created_at)
          FROM orders o
         WHERE o.status = 'completed' AND o.order_type = 'delivery'
    )";
}

/**
 * Filtre koşullarının SQL parçası + parametre listesi üretir.
 * @return array{0:string,1:array}
 */
function sales_where(array $channels, ?string $from, ?string $to): array
{
    $sql = '';
    $params = [];

    if ($channels) {
        $in = implode(',', array_fill(0, count($channels), '?'));
        $sql .= " AND channel IN ($in)";
        $params = array_merge($params, $channels);
    }
    if ($from !== null) {
        $sql .= ' AND created_at >= ?';
        $params[] = $from;
    }
    if ($to !== null) {
        $sql .= ' AND created_at <= ?';
        $params[] = $to;
    }

    return [$sql, $params];
}

// ---------------------------------------------------------------------
// Sorgu fonksiyonları
// ---------------------------------------------------------------------

/**
 * Kanal bazlı ciro / tahsilat özeti.
 *
 * @param Database $db
 * @param array    $opts channels[], from, to
 * @return array{channels:array, totals:array}
 */
function sales_summary($db, array $opts = []): array
{
    $channels = $opts['channels'] ?? sales_channel_keys();
    $from     = $opts['from'] ?? null;
    $to       = $opts['to'] ?? null;

    $u   = sales_union_sql();
    [$w, $p] = sales_where($channels, $from, $to);

    $rows = $db->query(
        "SELECT channel,
                MAX(channel_label) AS channel_label,
                COUNT(*)                       AS sale_count,
                COALESCE(SUM(net_amount), 0)   AS revenue,
                COALESCE(SUM(gross_amount), 0) AS gross,
                COALESCE(SUM(discount_amount), 0) AS discount,
                COALESCE(SUM(collected_amount), 0) AS collected
           FROM $u AS sl
          WHERE 1 = 1 $w
          GROUP BY channel",
        $p
    )->fetchAll(PDO::FETCH_ASSOC);

    $meta  = sales_channels();
    $out   = [];
    foreach ($channels as $key) {
        $out[$key] = [
            'channel'    => $key,
            'label'      => $meta[$key]['label'],
            'icon'       => $meta[$key]['icon'],
            'color'      => $meta[$key]['color'],
            'sale_count' => 0,
            'revenue'    => 0.0,
            'gross'      => 0.0,
            'discount'   => 0.0,
            'collected'  => 0.0,
            'avg_basket' => 0.0,
        ];
    }

    $totals = ['sale_count' => 0, 'revenue' => 0.0, 'gross' => 0.0, 'discount' => 0.0, 'collected' => 0.0];

    foreach ($rows as $r) {
        $key = $r['channel'];
        if (!isset($out[$key])) {
            continue;
        }
        $out[$key]['sale_count'] = (int)$r['sale_count'];
        $out[$key]['revenue']    = (float)$r['revenue'];
        $out[$key]['gross']      = (float)$r['gross'];
        $out[$key]['discount']   = (float)$r['discount'];
        $out[$key]['collected']  = (float)$r['collected'];
        $out[$key]['avg_basket'] = $out[$key]['sale_count'] > 0
            ? round($out[$key]['revenue'] / $out[$key]['sale_count'], 2)
            : 0.0;

        $totals['sale_count'] += $out[$key]['sale_count'];
        $totals['revenue']    += $out[$key]['revenue'];
        $totals['gross']      += $out[$key]['gross'];
        $totals['discount']   += $out[$key]['discount'];
        $totals['collected']  += $out[$key]['collected'];
    }

    $totals['avg_basket'] = $totals['sale_count'] > 0
        ? round($totals['revenue'] / $totals['sale_count'], 2)
        : 0.0;
    $totals['revenue']  = round($totals['revenue'], 2);
    $totals['gross']    = round($totals['gross'], 2);
    $totals['discount'] = round($totals['discount'], 2);
    $totals['collected'] = round($totals['collected'], 2);

    return ['channels' => $out, 'totals' => $totals];
}

/**
 * Ödeme yöntemi kırılımı (tüm kanallar birleşik).
 * @return array<int,array{method:string,label:string,total:float,count:int}>
 */
function sales_method_breakdown($db, array $opts = []): array
{
    $channels = $opts['channels'] ?? sales_channel_keys();
    $from     = $opts['from'] ?? null;
    $to       = $opts['to'] ?? null;

    $u   = sales_union_sql();
    [$w, $p] = sales_where($channels, $from, $to);

    $rows = $db->query(
        "SELECT payment_method,
                COUNT(*)                     AS c,
                COALESCE(SUM(net_amount), 0) AS total
           FROM $u AS sl
          WHERE 1 = 1 $w
          GROUP BY payment_method",
        $p
    )->fetchAll(PDO::FETCH_ASSOC);

    $agg = [];
    foreach ($rows as $r) {
        $g = sales_method_group($r['payment_method']);
        if (!isset($agg[$g])) {
            $agg[$g] = ['method' => $g, 'label' => sales_method_label($g), 'total' => 0.0, 'count' => 0];
        }
        $agg[$g]['total'] += (float)$r['total'];
        $agg[$g]['count'] += (int)$r['c'];
    }

    $order = ['cash' => 0, 'card' => 1, 'online' => 2, 'other' => 3];
    // uasort: karsilastiriciya DEGER gecer (uksort anahtar gecirirdi).
    uasort($agg, function ($a, $b) use ($order) {
        $x = $order[$a['method']] ?? 9;
        $y = $order[$b['method']] ?? 9;
        return $x === $y ? $b['total'] <=> $a['total'] : $x <=> $y;
    });

    foreach ($agg as &$a) {
        $a['total'] = round($a['total'], 2);
    }
    unset($a);

    return array_values($agg);
}

/**
 * Günlük ciro serisi (trend grafiği için).
 * @return array<int,array{date:string,revenue:float,count:int}>
 */
function sales_daily_series($db, array $opts = []): array
{
    $channels = $opts['channels'] ?? sales_channel_keys();
    $from     = $opts['from'] ?? null;
    $to       = $opts['to'] ?? null;
    $days     = max(1, min(365, (int)($opts['days'] ?? 30)));

    $u   = sales_union_sql();

    // Tarih filtresi verilmediyse pencereyi SON SATIŞ TARİHİNE çivile.
    // Aksi halde "Tüm Zamanlar" seçilmişken grafik bugünden geriye doğru
    // çizilir ve en eski satışın olduğu dönem tamamen boş görünür.
    if ($from === null && $to === null) {
        $max = $db->query("SELECT MAX(created_at) AS m FROM $u AS sl")->fetch(PDO::FETCH_ASSOC);
        if (!empty($max['m'])) {
            $anchor  = strtotime(substr($max['m'], 0, 10));
            $from    = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days', $anchor));
            $to      = date('Y-m-d 23:59:59', $anchor);
        } else {
            $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
            $to   = date('Y-m-d 23:59:59');
        }
    }

    [$w, $p] = sales_where($channels, $from, $to);

    $rows = $db->query(
        "SELECT DATE(created_at) AS d,
                COUNT(*)                     AS c,
                COALESCE(SUM(net_amount), 0) AS total
           FROM $u AS sl
          WHERE 1 = 1 $w
          GROUP BY DATE(created_at)
          ORDER BY d ASC",
        $p
    )->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) {
        $map[$r['d']] = ['date' => $r['d'], 'revenue' => round((float)$r['total'], 2), 'count' => (int)$r['c']];
    }

    // Eksik günleri 0 ile doldur (grafikte boşluk oluşmasın)
    $end   = $to !== null ? strtotime(substr($to, 0, 10)) : time();
    $start = $from !== null ? strtotime(substr($from, 0, 10)) : strtotime('-' . ($days - 1) . ' days');
    if ($start > $end) {
        return array_values($map);
    }

    $series = [];
    for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
        $k = date('Y-m-d', $t);
        $series[] = $map[$k] ?? ['date' => $k, 'revenue' => 0.0, 'count' => 0];
    }

    return $series;
}

/**
 * Satış kalemleri (ürün kırılımı için) — kanal + ürün bazında.
 *
 * order_items iki farklı şekilde satışa bağlıdır, bu yüzden dört kol gerekir:
 *
 *  1) POS satışı        -> order_items.payment_id = payments.id
 *  2) Kısmi masa tahsilatı -> order_items.payment_id = payments.id
 *  3) Masa kapanışı      -> order_items.order_id = orders.id (payment_id NULL,
 *                           yani kapanış ödemesinden sonra masada kalan kalem)
 *  4) Web siparişi       -> order_items.order_id = orders.id
 *  5) Teslim edilmiş ama henüz ödenmemiş masa siparişi
 *                         -> orders.status = 'delivered' olan masa siparişinin
 *                            tamamlanmış ödemeye BAĞLI OLMAYAN kalemleri.
 *                            (Ürün masaya teslim edilince satılmış sayılır;
 *                            ciro yine ödemeden gelir, burası yalnızca adet/
 *                            ürün kırılımı içindir.)
 *
 * (2) ve (3) örtüşmez: kısmi ödemede kalemin payment_id'si doludur, tam
 * kapanışta ise boştur ve o satır ancak 3. kolda yakalanır.
 * (5), (2)/(3) ile örtüşmez: tamamlanmış ödemeye bağlı kalemler hariçtir;
 * ödeme alınınca sipariş 'completed' olur ve 5. koldan düşer.
 *
 * Her kol ayrıca `sale_ref` (satış kaydının id'si) ve `sale_at` (satış
 * tarihi) sütunlarını taşır; böylece kanal ve tarih filtresi tüm birleşim
 * üzerinde tek bir WHERE ile uygulanabilir.
 *
 * @return string SQL
 */
function sales_items_sql(): string
{
    return "(
        SELECT 'pos' AS channel, oi.product_id, oi.quantity,
               (oi.quantity * oi.price) AS item_total,
               p.id AS sale_ref, p.created_at AS sale_at
          FROM payments p
          JOIN order_items oi ON oi.payment_id = p.id
         WHERE p.status = 'completed' AND p.table_id IS NULL

        UNION ALL

        SELECT 'table', oi.product_id, oi.quantity, (oi.quantity * oi.price),
               p.id, p.created_at
          FROM payments p
          JOIN order_items oi ON oi.payment_id = p.id
         WHERE p.status = 'completed' AND p.table_id IS NOT NULL

        UNION ALL

        SELECT 'table', oi.product_id, oi.quantity, (oi.quantity * oi.price),
               p.id, p.created_at
          FROM payments p
          JOIN orders o ON o.payment_id = p.id AND o.status = 'completed'
          JOIN order_items oi ON oi.order_id = o.id AND oi.payment_id IS NULL
         WHERE p.status = 'completed' AND p.table_id IS NOT NULL

        UNION ALL

        SELECT 'delivery', oi.product_id, oi.quantity, (oi.quantity * oi.price),
               o.id, COALESCE(o.completed_at, o.created_at)
          FROM orders o
          JOIN order_items oi ON oi.order_id = o.id
         WHERE o.status IN ('completed', 'delivered') AND o.order_type = 'delivery'

        UNION ALL

        SELECT 'table', oi.product_id, oi.quantity, (oi.quantity * oi.price),
               o.id, COALESCE(o.updated_at, o.created_at)
          FROM orders o
          JOIN order_items oi ON oi.order_id = o.id
         WHERE o.status = 'delivered'
           AND (o.order_type IS NULL OR o.order_type = 'table')
           AND NOT EXISTS (
               SELECT 1 FROM payments p
                WHERE p.id = oi.payment_id AND p.status = 'completed'
           )
           AND NOT EXISTS (
               SELECT 1 FROM payments p
                WHERE p.id = o.payment_id AND p.status = 'completed'
           )
    )";
}

/**
 * En çok satan ürünler (ciro ve adet ile birlikte).
 * @return array<int,array>
 */
function sales_top_products($db, array $opts = []): array
{
    $channels = $opts['channels'] ?? sales_channel_keys();
    $from     = $opts['from'] ?? null;
    $to       = $opts['to'] ?? null;
    $limit    = max(1, min(50, (int)($opts['limit'] ?? 5)));

    $i   = sales_items_sql();
    [$w, $p] = sales_where_alias($i, 'it', $channels, $from, $to);

    $rows = $db->query(
        "SELECT it.channel, it.product_id, pr.name,
                SUM(it.quantity)       AS qty,
                SUM(it.item_total)     AS total
           FROM $i AS it
           LEFT JOIN products pr ON pr.id = it.product_id
          WHERE 1 = 1 $w
          GROUP BY it.channel, it.product_id, pr.name
          ORDER BY total DESC, qty DESC
          LIMIT $limit",
        $p
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['qty']   = (int)$r['qty'];
        $r['total'] = round((float)$r['total'], 2);
    }
    unset($r);

    return $rows;
}

/**
 * `sales_union_sql()` / `sales_items_sql()` gibi bir türetilmiş tablo için
 * kanal + tarih filtresi üretir.
 *
 * @param string $sql   türetilmiş tablo ifadesi
 * @param string $alias dış sorgudaki takma ad
 * @return array{0:string,1:array}
 */
function sales_where_alias(string $sql, string $alias, array $channels, ?string $from, ?string $to): array
{
    $dateCol = $alias === 'it' ? 'sale_at' : 'created_at';

    $w = '';
    $params = [];

    if ($channels) {
        $in = implode(',', array_fill(0, count($channels), '?'));
        $w .= " AND $alias.channel IN ($in)";
        $params = array_merge($params, $channels);
    }
    if ($from !== null) {
        $w .= " AND $dateCol >= ?";
        $params[] = $from;
    }
    if ($to !== null) {
        $w .= " AND $dateCol <= ?";
        $params[] = $to;
    }

    return [$w, $params];
}


/**
 * Tahsil edilmemiş / bekleyen masa siparişleri.
 * Bir sipariş ancak tamamlanmış `payments` satırı varsa tahsil edilmiş sayılır.
 *
 * @return array<int,array>
 */
function sales_open_orders($db, array $opts = []): array
{
    $from = $opts['from'] ?? null;
    $to   = $opts['to'] ?? null;

    $params = [];
    $w = '';
    if ($from !== null) { $w .= ' AND o.created_at >= ?'; $params[] = $from; }
    if ($to   !== null) { $w .= ' AND o.created_at <= ?'; $params[] = $to; }

    return $db->query(
        "SELECT o.id, o.order_code, o.total_amount, o.status, o.created_at,
                t.id AS table_id, t.table_no
           FROM orders o
           LEFT JOIN tables t ON t.id = o.table_id
           LEFT JOIN (SELECT DISTINCT id AS payment_id FROM payments
                       WHERE status = 'completed') p
                  ON p.payment_id = o.payment_id
          WHERE o.order_type = 'table'
            AND o.status NOT IN ('completed', 'cancelled')
            AND p.payment_id IS NULL $w
          ORDER BY o.created_at DESC",
        $params
    )->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Tahsil bekleyen teslimatlar: status='delivered' olan masa siparişlerinden
 * tamamlanmış ödemeye bağlı OLMAYANLAR.
 *
 * Ürün teslim edilince satış gerçekleşmiş sayılır (stok düşer, adet kırılımına
 * girer) ancak para henüz alınmamıştır. Bu fonksiyon ciroya EKLENMEZ; yalnızca
 * "bekleyen tahsilat" göstergesi ve Alınmış Ödemeler sayfasındaki liste için
 * kullanılır. Ödeme alınınca (complete_payment) veya iptalde satır kendiliğinden
 * düşer.
 *
 * @return array{count:int,total:float,orders:array<int,array>}
 */
function sales_pending_collection($db): array
{
    $orders = $db->query(
        "SELECT o.id, o.order_code, o.table_id, o.total_amount, o.created_at, o.updated_at,
                t.table_no,
                GROUP_CONCAT(DISTINCT CONCAT(oi.quantity, 'x ', pr.name) SEPARATOR '||') AS items
           FROM orders o
           LEFT JOIN tables t ON t.id = o.table_id
           LEFT JOIN order_items oi ON oi.order_id = o.id
           LEFT JOIN products pr ON pr.id = oi.product_id
          WHERE o.status = 'delivered'
            AND (o.order_type IS NULL OR o.order_type = 'table')
            AND NOT EXISTS (
                SELECT 1 FROM payments p
                 WHERE p.id = o.payment_id AND p.status = 'completed'
            )
          GROUP BY o.id
          ORDER BY o.updated_at DESC, o.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $total = 0.0;
    foreach ($orders as $o) {
        $total += (float)($o['total_amount'] ?? 0);
    }

    return ['count' => count($orders), 'total' => round($total, 2), 'orders' => $orders];
}

/**
 * Tamamlanmış web/adres siparişleri (cirodaki delivery bacağının karşılığı).
 *
 * Bu siparişlerin `payments` satırı OLMADIĞI için Alınmış Ödemeler listesinde
 * görünmezler. Bu fonksiyon ciroyu DEĞİŞTİRMEZ; yalnızca sayfada ayrı bir
 * bölüm olarak listelenmeleri için satırları döndürür.
 *
 * Kapsam: 'completed' (ciroya girer) + 'delivered' (teslim edildi, para
 * henüz işlenmedi; ciroya GİRMEZ, yalnızca görünürlük için listelenir).
 *
 * @return array{count:int,total:float,orders:array<int,array>}
 */
function sales_web_completed($db): array
{
    $orders = $db->query(
        "SELECT o.id, o.order_code, o.total_amount, o.subtotal, o.discount_amount,
                o.delivery_fee, o.payment_method, o.status, o.created_at,
                o.customer_name, o.customer_surname, o.customer_phone,
                GROUP_CONCAT(DISTINCT CONCAT(oi.quantity, 'x ', pr.name) SEPARATOR '||') AS items
           FROM orders o
           LEFT JOIN order_items oi ON oi.order_id = o.id
           LEFT JOIN products pr ON pr.id = oi.product_id
          WHERE o.order_type = 'delivery'
            AND o.status IN ('completed', 'delivered')
          GROUP BY o.id
          ORDER BY COALESCE(o.completed_at, o.updated_at, o.created_at) DESC, o.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $total = 0.0;
    foreach ($orders as $o) {
        $total += (float)($o['total_amount'] ?? 0);
    }

    return ['count' => count($orders), 'total' => round($total, 2), 'orders' => $orders];
}

/**
 * Ciro ile tahsilat arasındaki farkı açıklayan veri kalitesi uyarıları.
 *
 * Teslimat akışı `payments` satırı üretmediği için web kanalında ciro ile
 * tahsilat ayrımı YAPILAMAZ; bu bir veri eksiği olarak açıkça raporlanır.
 *
 * @return array<int,array{level:string,message:string}>
 */
function sales_data_quality($db, array $opts = []): array
{
    $channels = $opts['channels'] ?? sales_channel_keys();
    $from     = $opts['from'] ?? null;
    $to       = $opts['to'] ?? null;

    $u   = sales_union_sql();
    [$w, $p] = sales_where($channels, $from, $to);

    $warnings = [];

    // 1) Bozuk enum kalıntıları (geçersiz ödeme yöntemi)
    $bad = $db->query(
        "SELECT COUNT(*) AS c FROM payments
          WHERE status = 'completed'
            AND (payment_method IS NULL OR payment_method NOT IN ('cash','pos'))"
    )->fetch(PDO::FETCH_ASSOC);
    if ((int)$bad['c'] > 0) {
        $warnings[] = [
            'level' => 'danger',
            'message' => $bad['c'] . ' ödeme kaydında geçersiz ödeme yöntemi var. '
                . 'Bu kayıtlar raporlarda "Diğer" olarak görünür; '
                . 'database_migrations/fix_enum_corruption.sql ile onarılabilir.',
        ];
    }

    // 2) İlişkisiz tamamlanmış sipariş -> ciro var, tahsilat kaydı yok
    $orphan = $db->query(
        "SELECT COUNT(*) AS c FROM orders o
          WHERE o.status = 'completed'
            AND o.payment_id IS NULL"
    )->fetch(PDO::FETCH_ASSOC);
    if ((int)$orphan['c'] > 0) {
        $warnings[] = [
            'level' => 'warning',
            'message' => $orphan['c'] . ' tamamlanmış siparişin tahsilat kaydı yok. '
                . 'Bu siparişlerin cirosu hesaplanıyor ancak nakit girişi doğrulanamıyor.',
        ];
    }

    // 3) Teslimat kanalında ödeme kaydı yok -> ciro/tahsilat ayrımı yapılamıyor
    if (in_array('delivery', $channels, true)) {
        $deliv = $db->query(
            "SELECT COUNT(*) AS c
               FROM $u AS sl
              WHERE 1 = 1 $w AND channel = 'delivery'",
            $p
        )->fetch(PDO::FETCH_ASSOC);
        if ((int)$deliv['c'] > 0) {
            $warnings[] = [
                'level' => 'info',
                'message' => 'Web/Online siparişleri için tahsilat kaydı tutulmuyor; '
                    . 'bu kanalda ciro ile tahsilat aynı kabul edilir. '
                    . 'Tahsilatı ayrı izlemek için teslimat akışına ödeme kaydı eklenmelidir.',
            ];
        }
    }

    return $warnings;
}

/**
 * Tüm satış paneli verisini tek çağrıda döndürür.
 */
function sales_dashboard_data($db, array $opts = []): array
{
    $summary   = sales_summary($db, $opts);
    return [
        'summary'   => $summary,
        'methods'   => sales_method_breakdown($db, $opts),
        'daily'     => sales_daily_series($db, $opts),
        'top'       => sales_top_products($db, $opts),
        'open'      => sales_open_orders($db, $opts),
        'warnings'  => sales_data_quality($db, $opts),
    ];
}
