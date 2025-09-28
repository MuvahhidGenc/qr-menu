<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sadece super admin çalıştırabilir
if (!isLoggedIn() || !isSuperAdmin()) {
    die('Bu işlem için yetkiniz bulunmuyor.');
}

try {
    $db = new Database();
    
    echo "<h3>Masa Kategorileri Kurulumu Başlatılıyor...</h3>";
    
    // 1. table_categories tablosunu oluştur
    echo "<p>1. Kategori tablosu oluşturuluyor...</p>";
    $db->query("
        CREATE TABLE IF NOT EXISTS `table_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `description` text,
          `sort_order` int(11) DEFAULT 0,
          `status` tinyint(1) DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "<span style='color: green;'>✓ Tablo oluşturuldu</span><br>";
    
    // 2. Varsayılan kategorileri ekle
    echo "<p>2. Varsayılan kategoriler ekleniyor...</p>";
    $categories = [
        [1, 'Salon', 'Ana salon masaları', 1],
        [2, 'Bahçe', 'Bahçe alanı masaları', 2],
        [3, 'Salon 2', 'İkinci salon masaları', 3]
    ];
    
    foreach ($categories as $cat) {
        $check = $db->query("SELECT id FROM table_categories WHERE id = ?", [$cat[0]])->fetch();
        if (!$check) {
            $db->query("INSERT INTO table_categories (id, name, description, sort_order) VALUES (?, ?, ?, ?)", $cat);
            echo "<span style='color: green;'>✓ {$cat[1]} kategorisi eklendi</span><br>";
        } else {
            echo "<span style='color: orange;'>- {$cat[1]} kategorisi zaten var</span><br>";
        }
    }
    
    // 3. Tables tablosuna category_id ekle
    echo "<p>3. Tables tablosu güncelleniyor...</p>";
    try {
        $db->query("ALTER TABLE `tables` ADD COLUMN `category_id` int(11) DEFAULT 1");
        echo "<span style='color: green;'>✓ category_id kolonu eklendi</span><br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<span style='color: orange;'>- category_id kolonu zaten var</span><br>";
        } else {
            throw $e;
        }
    }
    
    // 4. Index ekle
    try {
        $db->query("ALTER TABLE `tables` ADD INDEX `idx_category_id` (`category_id`)");
        echo "<span style='color: green;'>✓ Index eklendi</span><br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<span style='color: orange;'>- Index zaten var</span><br>";
        } else {
            throw $e;
        }
    }
    
    // 5. Mevcut masaları varsayılan kategoriye ata
    echo "<p>4. Mevcut masalar güncelleniyor...</p>";
    $updated = $db->query("UPDATE `tables` SET `category_id` = 1 WHERE `category_id` IS NULL OR `category_id` = 0")->rowCount();
    echo "<span style='color: green;'>✓ {$updated} masa Salon kategorisine atandı</span><br>";
    
    echo "<h3 style='color: green;'>✅ Kurulum Tamamlandı!</h3>";
    echo "<p><a href='tables.php' class='btn btn-primary'>Masalar sayfasına git</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Hata:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
