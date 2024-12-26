<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>

/* Topbar her zaman görünsün */
.mobile-nav {
   display: flex;
   background: #1a1c23;
   padding: 1rem;
   position: fixed;
   top: 0;
   left: 0;
   right: 0;
   z-index: 1001;
   color: white;
   justify-content: space-between;
   align-items: center;
}

.mobile-nav .brand-text {
   font-size: 1.2rem;
   font-weight: 500;
}

/* Sidebar */
.sidebar {
   width: 280px;
   height: 100vh;
   position: fixed;
   left: -280px; /* Varsayılan olarak gizli */
   top: 0;
   background: #1a1c23;
   padding: 1.5rem;
   padding-top: 70px;
   transition: 0.3s;
   z-index: 1000;
   overflow-y: auto;
}

.sidebar.active {
   left: 0;
}

/* Ana içerik */
.main-content {
   padding: 20px;
   padding-top: 70px;
   transition: 0.3s;
   width: 100%;
}

.btn-toggle {
    cursor: pointer;
    padding: 0.5rem;
    background: transparent;
    border: none;
    color: white;
}

.btn-toggle:hover {
   background: rgba(255,255,255,0.1);
   border-radius: 4px;
}

/* Logo ve profil dropdown stilleri */
.nav-logo img {
   height: 40px;
   width: auto;
}

.profile-dropdown .dropdown-menu {
   margin-top: 10px;
   border: none;
   box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-dropdown .dropdown-item {
   padding: 8px 20px;
}

.profile-dropdown .dropdown-item i {
   margin-right: 10px;
   width: 20px;
   text-align: center;
}

/* Sidebar içi stiller */
.nav-link {
   color: rgba(255,255,255,0.8);
   padding: 12px 15px;
   border-radius: 8px;
   margin-bottom: 5px;
   transition: all 0.3s;
   display: flex;
   align-items: center;
   text-decoration: none;
}

.nav-link i {
   margin-right: 10px;
   width: 20px;
   text-align: center;
}

.nav-link:hover, .nav-link.active {
   color: #fff;
   background: rgba(255,255,255,0.1);
}

/* Masaüstü */
@media (min-width: 769px) {
    .sidebar {
        left: 0;
        transition: transform 0.3s ease;
    }
    
    .sidebar.closed {
        transform: translateX(-280px);
    }
    
    .main-content {
        margin-left: 280px;
        width: calc(100% - 280px);
        transition: all 0.3s ease;
    }

    /* Toggle butonu stilini düzelt */
    .btn-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .btn-toggle:hover {
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
    }
}

/* Mobil */
@media (max-width: 768px) {
    .sidebar {
        left: -280px;
        transform: none;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}

/* Toggle buton animasyonu */
.btn-toggle {
    transition: transform 0.3s ease;
}


.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 10px;
    display: none;
}

.notification-dropdown .dropdown-menu {
    width: 300px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.notification-list .notification-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.notification-list .notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #f0f7ff;
}

    </style>

    <!-- Diğer head içerikleri -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="assets/js/tables.js"></script>
</head>
<body>
<!-- navbar.php -->
<div class="mobile-nav">
    <button class="btn-toggle" id="sidebarToggle">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    
    <span class="brand-text">QR Menü Admin</span>
    <div class="d-flex align-items-center">
    <!-- Bildirim Ikonu -->
    <div class="nav-item dropdown notification-dropdown me-3">
        <a class="nav-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notificationCount">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
            <div class="notification-header">
                <h6 class="mb-0">Bildirimler</h6>
                <button class="btn btn-sm btn-link mark-all-read">Tümünü Okundu İşaretle</button>
            </div>
            <div class="notification-list" id="notificationList">
                <!-- Bildirimler AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
    <div class="dropdown profile-dropdown">
        <button class="btn-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-user fa-lg"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end">
            
            <li>
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user-cog"></i> Profil
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="settings.php">
                    <i class="fas fa-cog"></i> Ayarlar
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </li>
        </ul>



   
    </div>
</div>
</div>

    <div class="wrapper">
        <div class="sidebar">
            <!--<h4 class="mb-4">QR Menü Admin</h4>-->
            
            <div class="nav flex-column">
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i>Ana Sayfa
                </a>
                <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> Siparişler
                </a>
                    <a href="tables.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'active' : '' ?>">
                    <i class="fas fa-chair"></i> Masalar
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
       
        <script>
document.addEventListener('DOMContentLoaded', function() {
   const sidebarToggle = document.getElementById('sidebarToggle');
   const sidebar = document.querySelector('.sidebar');

   function handleSidebarToggle(e) {
       e.stopPropagation();
       
       if (window.innerWidth > 768) {
           // Masaüstü
           sidebar.classList.toggle('closed');
       } else {
           // Mobil
           sidebar.classList.toggle('active');
       }
   }

   // Tüm toggle butonları için event listener ekle
   document.querySelectorAll('.btn-toggle').forEach(button => {
       button.addEventListener('click', handleSidebarToggle);
   });

   // Mobilde dışarı tıklama
   document.addEventListener('click', function(e) {
       if (window.innerWidth <= 768) {
           if (!sidebar.contains(e.target) && 
               !e.target.classList.contains('btn-toggle') && 
               sidebar.classList.contains('active')) {
               sidebar.classList.remove('active');
           }
       }
   });
});
</script>