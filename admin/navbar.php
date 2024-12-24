<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #1a1c23; color: white; position: fixed; height: 100vh; padding: 20px; }
        .main-content { margin-left: 280px; flex: 1; padding: 20px; }
        .nav-link { color: rgba(255,255,255,.8); padding: 10px; margin: 5px 0; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,.1); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <h4 class="mb-4">QR Menü Admin</h4>
            <div class="nav flex-column">
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i>Ana Sayfa
                </a>
                <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                    <i class="fas fa-list me-2"></i>Kategoriler
                </a>
                <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                    <i class="fas fa-utensils me-2"></i>Ürünler
                </a>
                <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog me-2"></i>Ayarlar
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                </a>
            </div>
        </div>