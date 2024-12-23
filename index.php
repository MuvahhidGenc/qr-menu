<?php
require_once 'includes/config.php';
$db = new Database();

// Kategorileri çek
$stmt = $db->query("SELECT * FROM categories WHERE status = 1");
$categories = $stmt->fetchAll();

// Seçili kategori
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>QR Menü</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <style>
       .category-card {
           position: relative;
           margin-bottom: 20px;
           cursor: pointer;
       }
       
       .category-image {
           width: 100%;
           height: 200px;
           object-fit: cover;
           border-radius: 8px;
       }
       
       .category-overlay {
           position: absolute;
           bottom: 0;
           left: 0;
           right: 0;
           background: rgba(0,0,0,0.6);
           padding: 10px;
           color: white;
           border-bottom-left-radius: 8px;
           border-bottom-right-radius: 8px;
       }

       .product-list {
           display: flex;
           align-items: center;
           margin-bottom: 15px;
           padding: 10px;
           background: white;
           border-radius: 8px;
           box-shadow: 0 2px 5px rgba(0,0,0,0.1);
       }

       .product-image {
           width: 50px;
           height: 50px;
           object-fit: cover;
           border-radius: 5px;
           margin-right: 15px;
       }

       .product-info {
           flex-grow: 1;
       }

       .product-price {
           font-weight: bold;
           color: #e74c3c;
       }
   </style>
</head>
<body class="bg-light">
   <div class="container mt-4">
       <!-- Kategoriler -->
       <?php if(!$selected_category): ?>
           <div class="row">
               <?php foreach($categories as $category): ?>
                   <div class="col-md-4">
                       <a href="?category=<?= $category['id'] ?>" class="text-decoration-none">
                           <div class="category-card">
                               <img src="uploads/<?= $category['image'] ?>" class="category-image">
                               <div class="category-overlay">
                                   <h5 class="m-0"><?= htmlspecialchars($category['name']) ?></h5>
                               </div>
                           </div>
                       </a>
                   </div>
               <?php endforeach; ?>
           </div>
       <?php else: ?>
           <!-- Ürünler -->
           <a href="index.php" class="btn btn-outline-dark mb-4">← Kategorilere Dön</a>
           <?php
           $stmt = $db->query("SELECT * FROM products WHERE category_id = ? AND status = 1", [$selected_category]);
           $products = $stmt->fetchAll();
           
           foreach($products as $product): ?>
               <div class="product-list">
                   <img src="uploads/<?= $product['image'] ?>" class="product-image">
                   <div class="product-info">
                       <h5><?= htmlspecialchars($product['name']) ?></h5>
                       <p class="text-muted mb-0"><?= htmlspecialchars($product['description']) ?></p>
                   </div>
                   <div class="product-price">
                       <?= number_format($product['price'], 2) ?> TL
                   </div>
               </div>
           <?php endforeach; ?>
       <?php endif; ?>
   </div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>