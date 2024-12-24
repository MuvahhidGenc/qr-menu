
<?php
require_once 'includes/config.php';
$db = new Database();

// Kategorileri çek
$stmt = $db->query("SELECT * FROM categories WHERE status = 1");
$categories = $stmt->fetchAll();

// Ayarları çek
$settingsResult = $db->query("SELECT * FROM settings");
$settings = array();
foreach($settingsResult->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Eğer kategori seçilmişse ürünleri çek
if(isset($_GET['category'])) {
   $category_id = (int)$_GET['category'];
   $stmt = $db->query("SELECT * FROM products WHERE category_id = ? AND status = 1", [$category_id]);
   $products = $stmt->fetchAll();
}

include 'includes/customer-header.php';
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('assets/images/restaurant-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .category-section {
            padding: 30px 0;
        }

        .category-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
            cursor: pointer;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px;
            color: white;
        }

        .category-overlay h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .menu-section {
            padding: 50px 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .menu-item {
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .menu-item:hover {
            transform: translateY(-3px);
        }

        .menu-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }

        .menu-item-content {
            padding: 15px;
            flex: 1;
        }

        .menu-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .menu-item-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .menu-item-price {
            font-weight: 600;
            color: #e74c3c;
            font-size: 1.1rem;
        }

        .back-button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .back-button:hover {
            background: #c0392b;
            color: white;
        }
    </style>
</head>
<body>
<?php if(!isset($_GET['category'])): ?>
    <div class="category-section">
        <!-- Ana Sayfa -->
       

        <div class="category-section">
        <div class="container">
                <h2 class="section-title">Menü Kategorileri</h2>
                <div class="row">
                    <?php foreach($categories as $category): ?>
                        <div class="col-md-4">
                            <a href="?category=<?= $category['id'] ?>" class="text-decoration-none">
                                <div class="category-card">
                                    <img src="uploads/<?= $category['image'] ?>" class="category-image" alt="<?= $category['name'] ?>">
                                    <div class="category-overlay">
                                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        </div>
        <?php else: ?>
        <!-- Kategori Ürünleri -->
        <div class="container mt-4">
            <a href="index.php" class="back-button text-decoration-none">
                <i class="fas fa-arrow-left"></i> Kategorilere Dön
            </a>

            <div class="menu-section">
                <?php
                $category_id = (int)$_GET['category'];
                $stmt = $db->query("SELECT * FROM products WHERE category_id = ? AND status = 1", [$category_id]);
                $products = $stmt->fetchAll();
                
                foreach($products as $product):
                ?>
                 <div class="category-section">
                    <div class="menu-item">
                        <img src="uploads/<?= $product['image'] ?>" class="menu-item-image" alt="<?= $product['name'] ?>">
                        <div class="menu-item-content">
                            <h5 class="menu-item-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="menu-item-description"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="menu-item-price"><?= number_format($product['price'], 2) ?> TL</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
                </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>