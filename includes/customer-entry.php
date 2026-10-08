<?php
/**
 * Müşteri giriş (gateway) ekranı.
 *
 * Yalnızca index.php'de paramsız girişte VE hem QR menü hem web/online
 * sipariş AÇIKKEN dahil edilir. Kullanıcı burada hangi sistemi kullanacağını
 * seçer.
 *
 * DAVRANŞ KURALI: Bu dosya index.php içinde include edilir, bu yüzden $db,
 * $menuOpen, $webOpen, $flags ve $tableRow değişkenleri hazırdır.
 *
 * Erişilebilirlik notu: kartlar <a> öğeleridir (tıklanabilir), ek olarak
 * klave/fare olmayanlar için gerçek bağlantıdır; yalnız buton değil.
 */

$__themeColor = $_SESSION['theme_color'] ?? '#343a40';

// Ana menüde en az bir hizmette masa var mı? (Menü kartının açılacağı hedef)
$__menuTable = null;
if ($menuOpen) {
    if (!empty($tableRow['id'])) {
        $__menuTable = $tableRow;
    } else {
        $__menuTable = $db->query(
            "SELECT id, table_no FROM tables WHERE status = 'active'
             ORDER BY table_no ASC, id ASC LIMIT 1"
        )->fetch();
    }
}
$__menuHref = $__menuTable ? ('index.php?table=' . (int)$__menuTable['id']) : '';
$__menuTableNo = $__menuTable['table_no'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Seçin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --entry: <?= htmlspecialchars($__themeColor, ENT_QUOTES, 'UTF-8') ?>; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px 16px;
        }
        .entry-wrap { width: 100%; max-width: 760px; }
        .entry-head { text-align: center; color: #fff; margin-bottom: 28px; }
        .entry-head h1 { font-weight: 700; font-size: 1.8rem; margin-bottom: 6px; }
        .entry-head p { opacity: .85; margin: 0; }
        .entry-card {
            display: block;
            background: #fff;
            border-radius: 20px;
            text-decoration: none;
            color: #2c3e50;
            padding: 30px 26px;
            text-align: center;
            box-shadow: 0 14px 40px rgba(0, 0, 0, .22);
            transition: transform .18s ease, box-shadow .18s ease;
            height: 100%;
        }
        .entry-card:hover, .entry-card:focus-visible {
            transform: translateY(-4px);
            box-shadow: 0 22px 55px rgba(0, 0, 0, .3);
            color: #2c3e50;
        }
        .entry-card:focus-visible { outline: 3px solid var(--entry); outline-offset: 3px; }
        .entry-icon {
            width: 84px; height: 84px;
            margin: 0 auto 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.4rem; color: #fff;
        }
        .entry-icon.menu   { background: linear-gradient(135deg, #667eea, #764ba2); }
        .entry-icon.online { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .entry-card h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 8px; }
        .entry-card p  { color: #6c757d; font-size: .95rem; margin: 0 0 14px; }
        .entry-go {
            display: inline-block;
            padding: 9px 22px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            font-size: .92rem;
            background: var(--entry);
        }
        .entry-note { text-align: center; color: #fff; opacity: .8; font-size: .85rem; margin-top: 24px; }
        .entry-card.is-disabled {
            background: #f1f2f6; color: #adb5bd; cursor: not-allowed;
            box-shadow: none;
        }
        .entry-card.is-disabled:hover { transform: none; box-shadow: none; }
        .entry-card.is-disabled .entry-icon { background: #ced4da; }
        .entry-card.is-disabled .entry-go { background: #ced4da; }
    </style>
</head>
<body>
<div class="entry-wrap">
    <div class="entry-head">
        <h1>Sipariş Nasıl Almak İstersiniz?</h1>
        <p>Sipariş vereceğiniz kanalı seçin</p>
    </div>

    <div class="row g-4">
        <?php // ---- QR Masa Menüsü kartı ---- ?>
        <div class="col-md-6">
            <?php if ($menuOpen && $__menuHref !== ''): ?>
                <a href="<?= htmlspecialchars($__menuHref, ENT_QUOTES, 'UTF-8') ?>" class="entry-card">
                    <div class="entry-icon menu"><i class="fas fa-qrcode"></i></div>
                    <h2>Masa Menüsü</h2>
                    <p>Masanızın QR kodundan menüyü görüntüleyin ve siparişinizi oluşturun.</p>
                    <span class="entry-go">Masa Menüsünü Aç</span>
                </a>
            <?php else: ?>
                <div class="entry-card is-disabled" aria-disabled="true">
                    <div class="entry-icon menu"><i class="fas fa-qrcode"></i></div>
                    <h2>Masa Menüsü</h2>
                    <p>
                        <?= $menuOpen
                            ? 'Şu anda hizmette açık masa bulunmuyor.'
                            : 'QR ile masa siparişi şu anda kapalı.' ?>
                    </p>
                    <span class="entry-go">Kullanılamıyor</span>
                </div>
            <?php endif; ?>
        </div>

        <?php // ---- Web / Online Sipariş kartı ---- ?>
        <div class="col-md-6">
            <?php if ($webOpen): ?>
                <a href="siparis.php" class="entry-card">
                    <div class="entry-icon online"><i class="fas fa-truck"></i></div>
                    <h2>Online Sipariş</h2>
                    <p>Adresinize gönderim için siparişinizi oluşturun ve siparişinizi takip edin.</p>
                    <span class="entry-go">Online Sipariş Ver</span>
                </a>
            <?php else: ?>
                <div class="entry-card is-disabled" aria-disabled="true">
                    <div class="entry-icon online"><i class="fas fa-truck"></i></div>
                    <h2>Online Sipariş</h2>
                    <p>Web / online sipariş sistemi şu anda kapalı.</p>
                    <span class="entry-go">Kullanılamıyor</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <p class="entry-note">
        <i class="fas fa-circle-info me-1"></i>
        Masadan sipariş veriyorsanız masanızın QR kodunu okutmanız yeterli.
    </p>
</div>
</body>
</html>