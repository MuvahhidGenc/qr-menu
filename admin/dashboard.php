<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$db = new Database();

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug için session durumunu logla
error_log("Dashboard.php - Session Data: " . print_r($_SESSION, true));

// Oturum kontrolü
if (!isLoggedIn()) {
    error_log("Dashboard.php - User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit();
}

// Yetki kontrolü - süper admin veya admin ise devam et
if (!isAdmin() && !isSuperAdmin()) {
    error_log("Dashboard.php - User does not have required permissions");
    header('Location: login.php');
    exit();
}

// Session'ı yenile
$_SESSION['last_activity'] = time();

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
    <style>
    /* Ana Container */
    .dashboard-container {
        padding: 2rem;
        background: #f8f9fa;
        min-height: 100vh;
    }

    /* İstatistik Kartları */
    .stat-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card.primary {
        background: linear-gradient(45deg, #4e54c8, #8f94fb);
    }

    .stat-card.success {
        background: linear-gradient(45deg, #11998e, #38ef7d);
    }

    .stat-card.info {
        background: linear-gradient(45deg, #2193b0, #6dd5ed);
    }

    .stat-card .card-body {
        padding: 1.8rem;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .stat-card h6 {
        opacity: 0.8;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .stat-card h2 {
        font-size: 2.2rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    .stat-card i {
        opacity: 0.8;
        font-size: 2.5rem;
    }

    /* Son Ürünler Kartı */
    .products-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        background: white;
        margin-top: 2rem;
        overflow: hidden;
    }

    .products-card .card-header {
        background: linear-gradient(45deg, #2c3e50, #3498db);
        color: white;
        padding: 1.5rem;
        border: none;
    }

    .products-card .card-header h5 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 600;
    }

    /* Tablo Tasarımı */
    .table {
        margin: 0;
    }

    .table th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        border: none;
        padding: 1.2rem 1rem;
    }

    .table td {
        vertical-align: middle;
        border-color: #f1f1f1;
        padding: 1rem;
    }

    /* Ürün Görseli */
    .product-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .product-img:hover {
        transform: scale(1.1);
    }

    /* Badge Tasarımı */
    .badge {
        padding: 0.6rem 1rem;
        border-radius: 10px;
        font-weight: 500;
    }

    /* Animasyonlar */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stat-card, .products-card {
        animation: fadeIn 0.5s ease-out;
    }

    /* Responsive Düzenlemeler */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card h2 {
            font-size: 1.8rem;
        }
        
        .table td {
            padding: 0.75rem;
        }
    }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="category-content">
<div class="container mt-4">
    
    <!-- İstatistik Kartları -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card stat-card primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Toplam Kategori</h6>
                            <h2><?= $total_categories ?></h2>
                        </div>
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card stat-card success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Toplam Ürün</h6>
                            <h2><?= $total_products ?></h2>
                        </div>
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card stat-card info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Toplam Görüntülenme</h6>
                            <h2><?= $total_views ?></h2>
                        </div>
                        <i class="fas fa-eye"></i>
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