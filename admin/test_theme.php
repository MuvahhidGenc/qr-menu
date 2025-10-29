<?php
require_once '../includes/config.php';

$db = new Database();

// Aktif temayı göster
$active_theme = $db->query("SELECT * FROM customer_themes WHERE is_active = 1 LIMIT 1")->fetch();

echo "<h1>🎨 Tema Test Sayfası</h1>";

if ($active_theme) {
    echo "<h2>✅ Aktif Tema Bulundu</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Özellik</th><th>Değer</th></tr>";
    echo "<tr><td><strong>Tema Adı</strong></td><td>" . htmlspecialchars($active_theme['name']) . "</td></tr>";
    echo "<tr><td><strong>Konsept</strong></td><td>" . htmlspecialchars($active_theme['concept']) . "</td></tr>";
    echo "<tr><td><strong>Primary Color</strong></td><td><div style='background:" . $active_theme['primary_color'] . "; width:100px; height:30px; border:1px solid #000;'></div>" . $active_theme['primary_color'] . "</td></tr>";
    echo "<tr><td><strong>Secondary Color</strong></td><td><div style='background:" . $active_theme['secondary_color'] . "; width:100px; height:30px; border:1px solid #000;'></div>" . $active_theme['secondary_color'] . "</td></tr>";
    echo "<tr><td><strong>Accent Color</strong></td><td><div style='background:" . $active_theme['accent_color'] . "; width:100px; height:30px; border:1px solid #000;'></div>" . $active_theme['accent_color'] . "</td></tr>";
    echo "<tr><td><strong>Background Color</strong></td><td><div style='background:" . $active_theme['background_color'] . "; width:100px; height:30px; border:1px solid #000;'></div>" . $active_theme['background_color'] . "</td></tr>";
    echo "<tr><td><strong>Text Color</strong></td><td><div style='background:" . $active_theme['text_color'] . "; width:100px; height:30px; border:1px solid #000;'></div>" . $active_theme['text_color'] . "</td></tr>";
    echo "<tr><td><strong>Font Family</strong></td><td>" . htmlspecialchars($active_theme['font_family']) . "</td></tr>";
    echo "<tr><td><strong>Category Style</strong></td><td><strong style='color:red; font-size:18px;'>" . htmlspecialchars($active_theme['category_style']) . "</strong></td></tr>";
    echo "<tr><td><strong>Product Layout</strong></td><td><strong style='color:blue; font-size:18px;'>" . htmlspecialchars($active_theme['product_layout']) . "</strong></td></tr>";
    echo "<tr><td><strong>Header Style</strong></td><td>" . htmlspecialchars($active_theme['header_style']) . "</td></tr>";
    echo "</table>";
    
    echo "<h2>🔗 Test Linkleri</h2>";
    echo "<ul>";
    echo "<li><a href='../index.php?table=1' target='_blank'>👉 Müşteri Sayfasını Aç (Kategoriler)</a></li>";
    echo "<li><a href='theme.css.php?theme=" . $active_theme['id'] . "' target='_blank'>👉 Tema CSS'ini Görüntüle</a></li>";
    echo "<li><a href='themes.php'>👉 Tema Yönetimine Dön</a></li>";
    echo "</ul>";
    
    echo "<h2>📋 Beklenen Sonuçlar</h2>";
    echo "<div style='background:#fffbeb; padding:15px; border-left:4px solid #f59e0b; margin:20px 0;'>";
    echo "<strong>Category Style: " . $active_theme['category_style'] . "</strong><br>";
    
    switch($active_theme['category_style']) {
        case 'grid':
            echo "✅ Kategoriler otomatik sütun sayısında (280px minimum) görünmeli<br>";
            echo "✅ Gradient arka plan: " . $active_theme['primary_color'];
            break;
        case 'grid-2col':
            echo "✅ Kategoriler 2 sütunda görünmeli<br>";
            echo "✅ Gradient arka plan: " . $active_theme['primary_color'] . "<br>";
            echo "✅ Mobile'da 1 sütuna geçmeli";
            break;
        case 'list':
            echo "✅ Kategoriler liste halinde (resim sol, metin sağ)<br>";
            echo "✅ Başlık rengi: " . $active_theme['primary_color'] . "<br>";
            echo "✅ Beyaz arka plan";
            break;
        case 'list-2col':
            echo "✅ Kategoriler 2 sütunlu liste halinde<br>";
            echo "✅ Başlık rengi: " . $active_theme['primary_color'] . "<br>";
            echo "✅ Beyaz arka plan<br>";
            echo "✅ Mobile'da 1 sütuna geçmeli";
            break;
        case 'masonry':
            echo "✅ Kategoriler duvar taşı düzeninde (3 sütun)<br>";
            echo "✅ Gradient arka plan: " . $active_theme['primary_color'];
            break;
    }
    echo "</div>";
    
    echo "<div style='background:#eff6ff; padding:15px; border-left:4px solid #3b82f6; margin:20px 0;'>";
    echo "<strong>Product Layout: " . $active_theme['product_layout'] . "</strong><br>";
    
    switch($active_theme['product_layout']) {
        case 'grid':
            echo "✅ Ürünler otomatik sütun sayısında (250px minimum)<br>";
            echo "✅ Fiyat rengi: " . $active_theme['primary_color'];
            break;
        case 'grid-2col':
            echo "✅ Ürünler 2 sütunda<br>";
            echo "✅ Fiyat rengi: " . $active_theme['primary_color'] . "<br>";
            echo "✅ Mobile'da 1 sütuna geçmeli";
            break;
        case 'list':
            echo "✅ Ürünler liste halinde (resim sol, bilgi sağ)<br>";
            echo "✅ Fiyat rengi: " . $active_theme['primary_color'];
            break;
        case 'list-2col':
            echo "✅ Ürünler 2 sütunlu liste<br>";
            echo "✅ Fiyat rengi: " . $active_theme['primary_color'] . "<br>";
            echo "✅ Mobile'da yatay liste (1 sütun)";
            break;
    }
    echo "</div>";
    
} else {
    echo "<h2>❌ Aktif Tema Bulunamadı</h2>";
    echo "<p>Lütfen <a href='themes.php'>Tema Yönetimi</a> sayfasından bir tema aktif edin.</p>";
}

echo "<hr>";
echo "<h2>🔍 CSS Değişkenleri Kontrolü</h2>";
echo "<p>Aşağıdaki kutular tema renklerini göstermeli:</p>";
echo "<div style='display:flex; gap:20px; margin:20px 0;'>";
echo "<div style='width:100px; height:100px; background:var(--theme-primary); border:2px solid #000;'><small>Primary</small></div>";
echo "<div style='width:100px; height:100px; background:var(--theme-secondary); border:2px solid #000;'><small>Secondary</small></div>";
echo "<div style='width:100px; height:100px; background:var(--theme-accent); border:2px solid #000;'><small>Accent</small></div>";
echo "</div>";

if ($active_theme) {
    echo "<link href='theme.css.php?theme=" . $active_theme['id'] . "' rel='stylesheet'>";
}
?>

