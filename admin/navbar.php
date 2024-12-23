<?php
// admin/navbar.php
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
       :root {
           --primary: #5469d4;
           --secondary: #ff7a59;
           --sidebar-width: 280px;
       }
       
       .admin-sidebar {
           width: var(--sidebar-width);
           height: 100vh;
           position: fixed;
           left: 0;
           top: 0;
           background: #1a1c23;
           padding: 1.5rem;
           color: white;
           transition: all 0.3s;
           z-index: 1000;
       }
       
       .admin-content {
           margin-left: var(--sidebar-width);
           padding: 2rem;
           background: #f7fafc;
           min-height: 100vh;
       }
       
       .brand-logo {
           padding: 1rem 0;
           margin-bottom: 2rem;
           border-bottom: 1px solid rgba(255,255,255,0.1);
           text-align: center;
       }
       
       .nav-link {
           color: rgba(255,255,255,0.7);
           padding: 0.8rem 1rem;
           margin: 0.2rem 0;
           border-radius: 0.5rem;
           transition: all 0.3s;
       }
       
       .nav-link:hover, .nav-link.active {
           background: rgba(255,255,255,0.1);
           color: white;
       }
       
       .nav-link i {
           width: 20px;
           text-align: center;
           margin-right: 10px;
       }
       
       .admin-header {
           background: white;
           padding: 1rem 2rem;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
           margin-bottom: 2rem;
           border-radius: 0.5rem;
       }
       
       .card {
           border: none;
           border-radius: 0.5rem;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
       }
       
       .stats-card {
           background: white;
           padding: 1.5rem;
           border-radius: 0.5rem;
           box-shadow: 0 2px 4px rgba(0,0,0,0.1);
           margin-bottom: 1rem;
       }
       
       .stats-card i {
           font-size: 2rem;
           color: var(--primary);
           margin-bottom: 1rem;
       }
       
       .stats-card .number {
           font-size: 1.5rem;
           font-weight: bold;
       }
       
       .btn-primary {
           background: var(--primary);
           border-color: var(--primary);
       }
       
       .btn-primary:hover {
           background: #4559b3;
           border-color: #4559b3;
       }
       
       .table th {
           font-weight: 600;
           background: #f8fafc;
       }
   </style>
</head>
<body>
   <div class="admin-sidebar">
       <div class="brand-logo">
           <h4>QR Menü Admin</h4>
       </div>
       <nav>
           <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
               <i class="fas fa-home"></i> Ana Sayfa
           </a>
           <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
               <i class="fas fa-list"></i> Kategoriler
           </a>
           <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
               <i class="fas fa-utensils"></i> Ürünler
           </a>
           <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
               <i class="fas fa-cog"></i> Ayarlar
           </a>
           <a href="backup.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : '' ?>">
               <i class="fas fa-database"></i> Yedekleme
           </a>
           <div class="mt-auto">
               <hr style="border-color: rgba(255,255,255,0.1)">
               <a href="logout.php" class="nav-link">
                   <i class="fas fa-sign-out-alt"></i> Çıkış
               </a>
           </div>
       </nav>
   </div>
   
   <div class="admin-content">
       <div class="admin-header d-flex justify-content-between align-items-center">
           <h4 class="mb-0">
               <?php
               $page = basename($_SERVER['PHP_SELF'], '.php');
               echo ucfirst($page);
               ?>
           </h4>
           <div class="d-flex align-items-center">
               <span class="me-3">Admin</span>
               <a href="logout.php" class="btn btn-outline-danger btn-sm">
                   <i class="fas fa-sign-out-alt"></i> Çıkış
               </a>
           </div>
       </div>

       <!-- Sayfa içeriği için bildirimler -->
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <!-- Sayfa içeriği buraya gelecek -->
</div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Mobil menü toggle
    document.querySelector('.navbar-toggler').addEventListener('click', () => {
        document.querySelector('.admin-sidebar').classList.toggle('show');
    });

    // Alert otomatik kapatma
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        });
    }, 5000);

    // Responsive ayarlar
    function checkWidth() {
        if (window.innerWidth < 768) {
            document.querySelector('.admin-sidebar').classList.add('collapsed');
            document.querySelector('.admin-content').style.marginLeft = '0';
        } else {
            document.querySelector('.admin-sidebar').classList.remove('collapsed');
            document.querySelector('.admin-content').style.marginLeft = 'var(--sidebar-width)';
        }
    }

    window.addEventListener('resize', checkWidth);
    checkWidth();
</script>
</body>
</html>