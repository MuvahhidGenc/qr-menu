<?php
require_once 'includes/config.php';

$db = new Database();

echo "<h1>Database Fix - Adding product_layout Column</h1>";

try {
    // Check if product_layout column exists
    $result = $db->query("SHOW COLUMNS FROM customer_themes LIKE 'product_layout'")->fetch();
    
    if (!$result) {
        echo "<p>Adding product_layout column to customer_themes table...</p>";
        
        // Add the column
        $db->query("ALTER TABLE customer_themes ADD COLUMN product_layout enum('grid','list','grid-2col','list-2col') DEFAULT 'grid' AFTER category_style");
        
        // Update category_style enum to include new options
        $db->query("ALTER TABLE customer_themes MODIFY COLUMN category_style enum('grid','masonry','carousel','list','grid-2col','list-2col') DEFAULT 'grid'");
        
        echo "<p style='color: green;'>✓ Product layout column added successfully!</p>";
        
        // Update existing themes with default values
        $db->query("UPDATE customer_themes SET product_layout = 'grid' WHERE product_layout IS NULL OR product_layout = ''");
        
        // Varsayılan product_layout değerlerini konseptlere göre güncelle
        echo "<p>Updating existing themes with default product_layout values based on concepts...</p>";
        $db->query("UPDATE customer_themes SET product_layout = 'grid' WHERE concept = 'modern'");
        $db->query("UPDATE customer_themes SET product_layout = 'grid-2col' WHERE concept = 'elegant'");
        $db->query("UPDATE customer_themes SET product_layout = 'list' WHERE concept = 'luxury'");
        $db->query("UPDATE customer_themes SET product_layout = 'list-2col' WHERE concept = 'minimal'");
        $db->query("UPDATE customer_themes SET product_layout = 'grid' WHERE concept = 'vintage'");
        $db->query("UPDATE customer_themes SET product_layout = 'grid-2col' WHERE concept = 'corporate'");
        $db->query("UPDATE customer_themes SET product_layout = 'grid' WHERE concept = 'classic'");
        $db->query("UPDATE customer_themes SET product_layout = 'list' WHERE concept = 'casual'");
        
        echo "<p style='color: green;'>✓ Existing themes updated with default product_layout values!</p>";
        
    } else {
        echo "<p style='color: blue;'>✓ Product layout column already exists!</p>";
    }
    
    // Verify the column exists now
    $verify = $db->query("SHOW COLUMNS FROM customer_themes LIKE 'product_layout'")->fetch();
    if ($verify) {
        echo "<p style='color: green;'>✓ Database is now ready!</p>";
        echo "<p><strong>You can now access:</strong></p>";
        echo "<ul>";
        echo "<li><a href='index.php?table=1'>Customer Interface</a></li>";
        echo "<li><a href='admin/themes.php'>Theme Management</a></li>";
        echo "<li><a href='test_theme_layouts.php'>Test Layouts</a></li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Column verification failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please make sure your database connection is working and you have the necessary permissions.</p>";
}
?>