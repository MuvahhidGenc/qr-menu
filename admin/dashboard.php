<?php
require_once '../includes/config.php';
$db = new Database();

if(!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$total_categories = $db->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'];
$total_products = $db->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
$total_views = $db->query("SELECT SUM(view_count) as total FROM products")->fetch()['total'] ?? 0;
$recent_products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="main-content">
<div class="container mt-4">
    
    <!-- İstatistik Kartları -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Toplam Kategori</h6>
                            <h2 class="mb-0"><?= $total_categories ?></h2>
                        </div>
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Toplam Ürün</h6>
                            <h2 class="mb-0"><?= $total_products ?></h2>
                        </div>
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Toplam Görüntülenme</h6>
                            <h2 class="mb-0"><?= $total_views ?></h2>
                        </div>
                        <i class="fas fa-eye fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Eklenen Ürünler -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Son Eklenen Ürünler</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Kategori</th>
                            <th>Fiyat</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/<?= $product['image'] ?>" 
                                         style="width:40px;height:40px;object-fit:cover;border-radius:5px"
                                         class="me-2">
                                    <?= htmlspecialchars($product['name']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td><?= number_format($product['price'], 2) ?> TL</td>
                            <td>
                                <?php if($product['status']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pasif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>