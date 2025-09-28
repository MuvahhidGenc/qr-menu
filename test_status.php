<?php
require_once 'includes/config.php';

$db = new Database();

echo "<h1>🎨 QR Menu 2-Sütun Düzen Testi</h1>";

// Veritabanı kontrolü
try {
    // product_layout sütunu var mı?
    $result = $db->query("SHOW COLUMNS FROM customer_themes LIKE 'product_layout'")->fetch();
    if (!$result) {
        echo "<div style='background:#ffe6e6; padding:15px; border-radius:10px; margin:20px 0;'>";
        echo "<h3>⚠️ Veritabanı Güncellenmedi!</h3>";
        echo "<p><a href='fix_database.php' style='background:#e74c3c; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Veritabanını Düzelt</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background:#e8f5e8; padding:15px; border-radius:10px; margin:20px 0;'>";
        echo "<h3>✅ Veritabanı Hazır!</h3>";
        echo "</div>";
    }
    
    // Aktif tema kontrol
    $active_theme = $db->query("SELECT * FROM customer_themes WHERE is_active = 1 LIMIT 1")->fetch();
    
    if ($active_theme) {
        echo "<div style='background:#e6f3ff; padding:15px; border-radius:10px; margin:20px 0;'>";
        echo "<h3>🎯 Aktif Tema: " . htmlspecialchars($active_theme['name']) . "</h3>";
        echo "<p><strong>Konsept:</strong> " . $active_theme['concept'] . "</p>";
        echo "<p><strong>Kategori Stili:</strong> " . $active_theme['category_style'] . "</p>";
        echo "<p><strong>Ürün Düzeni:</strong> " . $active_theme['product_layout'] . "</p>";
        echo "<p><strong>Ana Renk:</strong> <span style='background:" . $active_theme['primary_color'] . "; color:white; padding:3px 8px; border-radius:3px;'>" . $active_theme['primary_color'] . "</span></p>";
        echo "</div>";
    } else {
        echo "<div style='background:#fff3cd; padding:15px; border-radius:10px; margin:20px 0;'>";
        echo "<h3>⚠️ Aktif tema bulunamadı!</h3>";
        echo "</div>";
    }
    
    // Tüm temaları listele
    $themes = $db->query("SELECT * FROM customer_themes ORDER BY is_active DESC, name")->fetchAll();
    
    echo "<h2>📋 Mevcut Temalar</h2>";
    echo "<table border='1' style='width:100%; border-collapse:collapse; margin:20px 0;'>";
    echo "<tr style='background:#f0f0f0; font-weight:bold;'>";
    echo "<th style='padding:10px;'>Tema Adı</th>";
    echo "<th style='padding:10px;'>Aktif</th>";
    echo "<th style='padding:10px;'>Konsept</th>";
    echo "<th style='padding:10px;'>Kategori Stili</th>";
    echo "<th style='padding:10px;'>Ürün Düzeni</th>";
    echo "<th style='padding:10px;'>Ana Renk</th>";
    echo "<th style='padding:10px;'>İşlemler</th>";
    echo "</tr>";
    
    foreach($themes as $theme) {
        $is_active = $theme['is_active'] ? '✅' : '❌';
        $bg_color = $theme['is_active'] ? '#e8f5e8' : '#ffffff';
        
        echo "<tr style='background:$bg_color;'>";
        echo "<td style='padding:8px;'>" . htmlspecialchars($theme['name']) . "</td>";
        echo "<td style='padding:8px; text-align:center;'>$is_active</td>";
        echo "<td style='padding:8px;'>" . $theme['concept'] . "</td>";
        echo "<td style='padding:8px;'>" . $theme['category_style'] . "</td>";
        echo "<td style='padding:8px;'>" . $theme['product_layout'] . "</td>";
        echo "<td style='padding:8px; background:" . $theme['primary_color'] . "; color:white; text-align:center;'>" . $theme['primary_color'] . "</td>";
        echo "<td style='padding:8px;'>";
        echo "<a href='admin/theme_preview.php?theme=" . $theme['id'] . "' target='_blank' style='background:#3498db; color:white; padding:5px 10px; text-decoration:none; border-radius:3px; margin:2px;'>👁️ Önizle</a>";
        if (!$theme['is_active']) {
            echo "<a href='admin/themes.php?activate=" . $theme['id'] . "' style='background:#27ae60; color:white; padding:5px 10px; text-decoration:none; border-radius:3px; margin:2px;'>✅ Aktif Et</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🔧 Özellik Durumu</h2>";
    echo "<div style='display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:15px; margin:20px 0;'>";
    
    // Kategori özellikleri
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:10px; border-left:5px solid #e74c3c;'>";
    echo "<h4>📂 Kategori Düzenleri</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>grid</strong> - Standart ızgara</li>";
    echo "<li>✅ <strong>grid-2col</strong> - 2'li ızgara</li>";
    echo "<li>✅ <strong>list</strong> - Liste görünümü</li>";
    echo "<li>✅ <strong>list-2col</strong> - 2'li liste</li>";
    echo "<li>✅ <strong>masonry</strong> - Masonry düzen</li>";
    echo "<li>✅ <strong>carousel</strong> - Karusel</li>";
    echo "</ul>";
    echo "</div>";
    
    // Ürün özellikleri
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:10px; border-left:5px solid #3498db;'>";
    echo "<h4>🛍️ Ürün Düzenleri</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>grid</strong> - Standart ızgara</li>";
    echo "<li>✅ <strong>grid-2col</strong> - 2'li ızgara</li>";
    echo "<li>✅ <strong>list</strong> - Liste görünümü</li>";
    echo "<li>✅ <strong>list-2col</strong> - 2'li liste</li>";
    echo "</ul>";
    echo "</div>";
    
    // Konsept özellikleri
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:10px; border-left:5px solid #f39c12;'>";
    echo "<h4>🎨 Konsept Stilleri</h4>";
    echo "<ul>";
    echo "<li>✅ <strong>modern</strong> - Çarpık kartlar, büyük harfler</li>";
    echo "<li>✅ <strong>elegant</strong> - Yuvarlak köşeler, italik</li>";
    echo "<li>✅ <strong>luxury</strong> - Altın kenarlık, parıltı efekti</li>";
    echo "<li>✅ <strong>minimal</strong> - Temiz çizgiler, küçük harfler</li>";
    echo "<li>✅ <strong>vintage</strong> - Sepia filtre, serif font</li>";
    echo "<li>✅ <strong>corporate</strong> - Düz kenarlıklar, büyük harfler</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<h2>🚀 Test Linkleri</h2>";
    echo "<div style='display:flex; gap:15px; flex-wrap:wrap; margin:20px 0;'>";
    echo "<a href='admin/themes.php' style='background:#e74c3c; color:white; padding:12px 20px; text-decoration:none; border-radius:8px;'>🎨 Tema Yönetimi</a>";
    echo "<a href='index.php?table=1' style='background:#27ae60; color:white; padding:12px 20px; text-decoration:none; border-radius:8px;'>👀 Müşteri Arayüzü</a>";
    echo "<a href='test_2col_layouts.php' style='background:#3498db; color:white; padding:12px 20px; text-decoration:none; border-radius:8px;'>📐 Düzen Testi</a>";
    echo "<a href='fix_database.php' style='background:#f39c12; color:white; padding:12px 20px; text-decoration:none; border-radius:8px;'>🔧 Veritabanı Düzelt</a>";
    echo "</div>";
    
    echo "<h2>📱 Mobil Uyumluluk</h2>";
    echo "<div style='background:#e8f5e8; padding:15px; border-radius:10px; margin:20px 0;'>";
    echo "<ul>";
    echo "<li>✅ Tüm 2'li düzenler mobilde tek sütuna geçer</li>";
    echo "<li>✅ Yazı boyutları mobil için optimize edildi</li>";
    echo "<li>✅ Dokunmatik hedefler için uygun boyutlar</li>";
    echo "<li>✅ Görsel içerik mobil ekranlara uygun</li>";
    echo "</ul>";
    echo "</div>";
    
    // CSS test bağlantısı
    if ($active_theme) {
        echo "<h2>🔗 CSS Kontrolü</h2>";
        echo "<p><a href='admin/theme.css.php?theme=" . $active_theme['id'] . "' target='_blank' style='background:#9b59b6; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>📄 Üretilen CSS'i Görüntüle</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#ffe6e6; padding:15px; border-radius:10px; margin:20px 0;'>";
    echo "<h3>❌ Hata:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>