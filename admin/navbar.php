<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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
   z-index: 1030;
   color: white;
   justify-content: space-between;
   align-items: center;
   height: 60px;
}

.mobile-nav .brand-text {
   font-size: 1.2rem;
   font-weight: 500;
}

/* Sidebar */
.sidebar {
   width: 250px;
   height: 100vh;
   position: fixed;
   left: 0;
   top: 0;
   background: #1a1c23;
   color: #fff;
   padding: 60px 1rem 1rem;
   z-index: 1020;
   overflow-y: auto;
   transition: all 0.3s ease-in-out;
}

.sidebar-header {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
}

.sidebar-nav .nav-link {
    color: rgba(255,255,255,0.7);
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    transition: all 0.3s;
}

.sidebar-nav .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.sidebar-nav .nav-link.active {
    color: #fff;
    background: #0d6efd;
}

.main-content {
    margin-left: 250px;
    padding: 2rem;
    margin-top: 60px;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        z-index: 1040;
        background: #1a1c23;
        width: 250px;
        transition: transform 0.3s ease-in-out;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}

.btn-toggle {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    padding: 0.5rem;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.btn-toggle:hover {
    transform: scale(1.1);
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
        margin-left: 250px;
        width: calc(100% - 250px);
        margin-top: 60px;
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

    .btn-toggle:active {
        transform: translateY(1px);
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

.notification-dropdown {
    position: relative;
}

.notification-dropdown .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;  /* Sağa hizala */
    top: 100%; /* Butonun altına hizala */
    width: 350px;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
    z-index: 1000;
    margin-top: 0.5rem;
    transform-origin: top right;
}

.notification-dropdown .dropdown-menu.show {
    display: block;
}

/* Bootstrap'in varsayılan dropdown davranışını override et */
.dropdown-menu[data-bs-popper] {
    right: 0;
    left: auto;
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

/* Modal için z-index düzenlemesi */
.modal-backdrop {
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

/* Navbar z-index düzenlemesi */
.mobile-nav {
    z-index: 1030;
}

.sidebar {
    z-index: 1020;
}

/* Overlay efekti (opsiyonel) */
.sidebar.show::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: -1;
}

/* Sidebar temel stilleri */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #1a1c23;
    color: #fff;
    padding: 60px 1rem 1rem;
    z-index: 1020;
    overflow-y: auto;
    transition: all 0.3s ease-in-out;
}

/* Main content temel stilleri */
.main-content {
    margin-left: 250px;
    transition: all 0.3s ease-in-out;
}

/* Masaüstü için kapalı sidebar durumu */
.sidebar.closed {
    transform: translateX(-250px);
}

.main-content.expanded {
    margin-left: 0;
}

/* Toggle buton animasyonu */
.btn-toggle {
    transition: transform 0.3s ease;
}

.btn-toggle:hover {
    transform: scale(1.1);
}

.btn-toggle.active {
    transform: rotate(180deg);
}

/* Mobil görünüm */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }

    /* Mobilde aktif sidebar overlay efekti */
    .sidebar.active::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.5);
        z-index: -1;
        animation: fadeIn 0.3s ease-in-out;
    }
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Navbar modern stil güncellemeleri */
.mobile-nav {
    background: linear-gradient(to right, #1a1c23, #2c3e50);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: 60px;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Brand/Logo alanı */
.brand-text {
    font-size: 1.25rem;
    font-weight: 600;
    background: linear-gradient(45deg, #fff, #e0e0e0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Toggle buton modernizasyonu */
.btn-toggle {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.3s ease;
}

.btn-toggle:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-1px);
}

.btn-toggle:active {
    transform: translateY(1px);
}

/* Bildirim ikonu stil güncellemesi */
.notification-dropdown .nav-link {
    position: relative;
    padding: 8px;
    color: rgba(255,255,255,0.8);
    transition: all 0.3s ease;
}

.notification-dropdown .nav-link:hover {
    color: #fff;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid #1a1c23;
    transform: translate(25%, -25%);
}

/* Profil dropdown modernizasyonu */
.profile-dropdown .btn-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 8px 12px;
    border-radius: 8px;
    color: rgba(255,255,255,0.9);
}

.profile-dropdown .dropdown-menu {
    margin-top: 0.5rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 0.5rem;
    min-width: 200px;
    background: #fff;
    animation: dropdownFade 0.2s ease-in-out;
}

.profile-dropdown .dropdown-item {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #1a1c23;
    transition: all 0.2s ease;
}

.profile-dropdown .dropdown-item:hover {
    background: rgba(0,0,0,0.05);
    transform: translateX(4px);
}

.profile-dropdown .dropdown-item i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
    color: #2c3e50;
}

.profile-dropdown .dropdown-divider {
    margin: 0.5rem 0;
    border-color: rgba(0,0,0,0.08);
}

/* Bildirim dropdown modernizasyonu */
.notification-dropdown .dropdown-menu {
    margin-top: 0.5rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 0;
    min-width: 300px;
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-list .notification-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}

.notification-list .notification-item:hover {
    background: rgba(0,0,0,0.02);
}

.notification-list .notification-item.unread {
    background: rgba(13,110,253,0.05);
}

/* Animasyonlar */
@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .mobile-nav {
        padding: 0 1rem;
    }
    
    .brand-text {
        font-size: 1.1rem;
    }
    
    .notification-dropdown .dropdown-menu {
        position: fixed;
        top: 60px;
        left: 0;
        right: 0;
        width: 100%;
        margin: 0;
        border-radius: 0;
        max-height: calc(100vh - 60px);
        overflow-y: auto;
    }
}

/* Sidebar modern stil güncellemeleri */
.sidebar {
    background: linear-gradient(180deg, #1a1c23 0%, #2c3e50 100%);
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

/* Sidebar header */
.sidebar-header {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 1rem;
}

.sidebar-header h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
    background: linear-gradient(45deg, #fff, #e0e0e0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Sidebar menü öğeleri */
.nav-link {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    color: rgba(255,255,255,0.7);
    border-radius: 8px;
    margin: 0.2rem 0;
    transition: all 0.3s ease;
}

.nav-link i {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
    transform: translateX(5px);
}

.nav-link.active {
    color: #fff;
    background: linear-gradient(45deg, #4CAF50, #45a049);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.nav-link.active i {
    transform: scale(1.1);
}

/* Bildirim dropdown modernizasyonu */
.notification-dropdown .dropdown-menu {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    padding: 0;
    max-width: 320px;
}

.notification-header {
    padding: 1rem;
    background: linear-gradient(to right, #f8f9fa, #fff);
    border-bottom: 1px solid rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
    color: #2c3e50;
}

.notification-list {
    max-height: 360px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.notification-item:hover {
    background: rgba(0,0,0,0.02);
}

.notification-item.unread {
    background: rgba(13,110,253,0.04);
}

.notification-item .notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #2c3e50;
}

.notification-item .notification-content {
    flex: 1;
}

.notification-item .notification-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.notification-item .notification-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.notification-item .notification-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

/* Scrollbar stilleri */
.sidebar::-webkit-scrollbar,
.notification-list::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track,
.notification-list::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.sidebar::-webkit-scrollbar-thumb,
.notification-list::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
}

/* Hover efektleri */
.sidebar::-webkit-scrollbar-thumb:hover,
.notification-list::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: rgba(0,0,0,0.2);
}

/* Mobil düzenlemeler */
@media (max-width: 768px) {
    .notification-dropdown .dropdown-menu {
        max-width: 100%;
        border-radius: 0;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .sidebar {
        box-shadow: 2px 0 20px rgba(0,0,0,0.2);
    }
}

/* Navbar için düşük z-index */
.navbar {
    z-index: 1 !important;
}

#statisticsPanel {
    position: static !important;
    padding-left: 1rem;
    border-left: 3px solid rgba(255,255,255,0.1);
    margin-left: 1rem;
}

#statisticsPanel .nav-link {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

#statisticsPanel .nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 4px;
}

/* Bildirim dropdown stilleri */
.notification-dropdown .dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
    width: 350px;
    padding: 0;
}

.notification-dropdown .dropdown-menu.show {
    display: block !important;
    transform: translate3d(0px, 40px, 0px) !important;
}

.notification-header {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.unread-indicator {
    width: 8px;
    height: 8px;
    background: #3498db;
    border-radius: 50%;
    margin-left: 10px;
}

/* Bildirim badge stilleri */
.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Ortak Dropdown Stilleri */
.dropdown-menu {
    display: none;
    position: absolute;
    z-index: 1000;
}

.dropdown-menu.show {
    display: block !important;
}

/* Profil Dropdown Stilleri */
.profile-dropdown .dropdown-menu {
    min-width: 200px;
    padding: 8px;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    background: white;
    margin-top: 10px;
    right: 0;
}

.profile-dropdown .dropdown-item {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.profile-dropdown .dropdown-item:hover {
    background: #f8f9fa;
    transform: translateX(4px);
}

.profile-dropdown .dropdown-item i {
    width: 20px;
    text-align: center;
    color: #6c757d;
}

.profile-dropdown .dropdown-divider {
    margin: 8px 0;
    border-color: rgba(0,0,0,0.08);
}

/* Animasyon */
@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu.show {
    animation: dropdownFade 0.2s ease;
}

/* Bildirim header butonları için stil */
.notification-actions .btn-link {
    color: #6c757d;
    text-decoration: none;
    transition: color 0.2s;
}

.notification-actions .btn-link:hover {
    color: #495057;
}

.notification-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

/* Dropdown menü stilleri */
.navbar .dropdown-menu {
    border: none;
    border-radius: 8px;
    margin-top: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
}

.navbar .dropdown-item {
    padding: 8px 20px;
    color: rgba(255,255,255,.8);
    transition: all 0.2s;
}

.navbar .dropdown-item:hover {
    background: rgba(255,255,255,.1);
    color: #fff;
}

.navbar .dropdown-item i {
    width: 20px;
    margin-right: 10px;
    text-align: center;
}

/* Dropdown ok işareti */
.navbar .dropdown-menu:before {
    content: '';
    position: absolute;
    top: -5px;
    left: 20px;
    width: 10px;
    height: 10px;
    background: #343a40;
    transform: rotate(45deg);
}

/* Aktif menü öğesi */
.navbar .dropdown-item.active {
    background-color: rgba(255,255,255,.1);
}

/* Mobil görünüm için ek stiller */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -250px;
        transition: all 0.3s ease;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .btn-toggle {
        cursor: pointer;
    }
    
    .mobile-nav {
        z-index: 1040;
    }
    
    .sidebar {
        z-index: 1030;
    }
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
        <i class="fas fa-bars"></i>
    </button>
    
    <span class="brand-text">QR Menü Admin</span>
    <div class="d-flex align-items-center">
    <!-- Bildirim Dropdown -->
    <div class="nav-item dropdown notification-dropdown me-3">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notificationCount"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end notification-menu">
            <div class="notification-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Bildirimler</h6>
                <div class="notification-actions">
                    <button class="btn btn-sm btn-link text-muted p-0 me-2" id="toggleSound">
                        <i class="fas fa-volume-up" id="soundIcon"></i>
                    </button>
                    <button class="btn btn-sm btn-link text-muted p-0 mark-all-read">
                        <i class="fas fa-check-double"></i>
                    </button>
                </div>
            </div>
            <div class="notification-list" id="notificationList">
                <!-- Bildirimler AJAX ile yüklenecek -->
            </div>
        </div>
    </div>

    <!-- Profil Dropdown -->
    <div class="nav-item dropdown profile-dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user"></i>
        </a>
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
            <div class="sidebar-header">
                <h3 class="mb-0">QR Menü Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                <?php if (hasPermission('dashboard.view')): ?>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('products.view')): ?>   
                    <li class="nav-item">
                        <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                            <i class="fas fa-utensils me-2"></i>Ürünler & Kategoriler
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('orders.view')): ?>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                            <i class="fas fa-shopping-cart me-2"></i>Siparişler
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('tables.view')): ?>
                    <li class="nav-item">
                        <a href="tables.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'active' : '' ?>">
                            <i class="fas fa-chair me-2"></i>Masalar
                        </a>
                    </li>
                    <?php endif; ?>
               
                    <?php if (hasPermission('payments.view') /*&& hasPermission('payments.manage')*/): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="completed_payments.php">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Alınmış Ödemeler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="expenses.php">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Gider Yönetimi
                            </a>
                        </li>
                      
                    <?php endif; ?>
                    <!-- İstatistikler Menüsü -->
                    <?php if (hasPermission('reports.view')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#statisticsPanel" role="button" aria-expanded="false" aria-controls="statisticsPanel">
                            <i class="fas fa-chart-bar me-2"></i>
                            Genel İstatistikler
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down ms-auto" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                        <div class="collapse" id="statisticsPanel">
                            <ul class="navbar-nav ps-4 ms-3 border-start">
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center" href="reports.php">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Raporlar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center" href="financial.php">
                                        <i class="fas fa-box me-2"></i>
                                        Finansal Raporlar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('kitchen.view')): ?>
                    <li class="nav-item">
                        <a href="kitchen.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : '' ?>">
                            <i class="fas fa-fire me-2"></i>Mutfak
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('reservations.view')): ?>
                    <li class="nav-item">
                        <a href="reservations.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt me-2"></i>Rezervasyonlar
                        </a>
                    </li>
                    <?php endif; ?>
                       <!-- Kullanıcı Yönetimi Menüsü -->
                       <?php if (hasPermission('users.view')): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#userManagementPanel" role="button" aria-expanded="false" aria-controls="userManagementPanel">
                            <i class="fas fa-users me-2"></i>
                            Kullanıcı Yönetimi
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down ms-auto" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                        <div class="collapse" id="userManagementPanel">
                            <ul class="navbar-nav ps-4 ms-3 border-start">
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center" href="admins.php">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Personel Yönetimi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center" href="roles.php">
                                        <i class="fas fa-user-shield me-2"></i>
                                        Kullanıcı Rolleri
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                     <!-- Ayarlar Dropdown Menüsü -->
                <?php if (hasPermission('settings.view')): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" data-bs-toggle="collapse" href="#settingsPanel" role="button" aria-expanded="false" aria-controls="settingsPanel">
                        <i class="fas fa-cog me-2"></i>
                        Ayarlar
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down ms-auto" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </a>
                    <div class="collapse" id="settingsPanel">
                        <ul class="navbar-nav ps-4 ms-3 border-start">
                            <li class="nav-item">
                                <a class="nav-link text-white d-flex align-items-center" href="settings.php">
                                    <i class="fas fa-sliders-h me-2"></i>
                                    Site Ayarları
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white d-flex align-items-center" href="printer_settings.php">
                                    <i class="fas fa-print me-2"></i>
                                    Yazıcı Ayarları
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white d-flex align-items-center" href="order_settings.php">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Sipariş Ayarları
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
              <?php endif; ?>
                </ul>
            </nav>
        </div>
        <div class="main-content">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Ses objesi oluştur
// Global değişkenler
const notificationSound = new Audio('/qr-menu/admin/assets/sounds/notification.mp3');
let lastNotificationCount = null; // İlk kontrolde ses çalmasın diye null başlatıyoruz
let soundEnabled = localStorage.getItem('notificationSound') !== 'false';

$('#toggleSound').click(function() {
    soundEnabled = !soundEnabled;
    $('#soundIcon').toggleClass('fa-volume-up fa-volume-mute');
    
    // Tercihi localStorage'a kaydet
    localStorage.setItem('notificationSound', soundEnabled);
});

function checkNotifications() {
    $.ajax({
        url: 'ajax/get_notifications.php',
        type: 'GET',
        success: function(response) {
            if(response.success) {
                $('#notificationList').html(response.html);
                
                // İlk yükleme değilse ve yeni bildirim varsa ses çal
                if(lastNotificationCount !== null && 
                   response.unread_count > lastNotificationCount && 
                   soundEnabled) {
                    notificationSound.play().catch(e => console.log('Ses çalma hatası:', e));
                }
                
                // Bildirim sayısını güncelle
                lastNotificationCount = response.unread_count;
                
                if(response.unread_count > 0) {
                    $('#notificationCount').text(response.unread_count).show();
                } else {
                    $('#notificationCount').hide();
                }
            }
        }
    });
}

// Bildirime tıklama
$(document).on('click', '.notification-item', function() {
    let notificationId = $(this).data('id');
    let tableId = $(this).data('table-id');
    
    // Önce bildirimi okundu olarak işaretle
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        type: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            if(response.success) {
                // Bildirimi okundu yap ve masalar sayfasına yönlendir
                window.location.href = 'orders.php?table=' + tableId + '&highlight=true';
            }
        }
    });
});

// Sayfa yüklendiğinde başlangıç kontrolü
$(document).ready(function() {
    soundEnabled = localStorage.getItem('notificationSound') !== 'false';
       // Ses icon durumunu ayarla
       $('#soundIcon').toggleClass('fa-volume-up', soundEnabled)
                  .toggleClass('fa-volume-mute', !soundEnabled);
    
    // İlk kontrol
    checkNotifications();
    
    // Periyodik kontrol
    setInterval(checkNotifications, 10000);
});


// Bildirimi okundu olarak işaretle
$(document).on('click', '.notification-item', function() {
    let notificationId = $(this).data('id');
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        type: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            if(response.success) {
                checkNotifications();
            }
        }
    });
});

// Tüm bildirimleri okundu olarak işaretle
$(document).on('click', '.mark-all-read', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'ajax/mark_all_read.php',
        type: 'POST',
        success: function(response) {
            if(response.success) {
                checkNotifications();
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle && sidebar) {
        // Önceki tercihi kontrol et
        const isSidebarClosed = localStorage.getItem('sidebarClosed') === 'true';
        if (isSidebarClosed && window.innerWidth > 768) {
            sidebar.classList.add('closed');
            mainContent.classList.add('expanded');
            sidebarToggle.classList.add('active');
        }

        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (window.innerWidth <= 768) {
                // Mobil davranış
                sidebar.classList.toggle('active');
                sidebarToggle.classList.toggle('active');
            } else {
                // Masaüstü davranış
                sidebar.classList.toggle('closed');
                mainContent.classList.toggle('expanded');
                sidebarToggle.classList.toggle('active');
                
                // Tercihi kaydet
                localStorage.setItem('sidebarClosed', sidebar.classList.contains('closed'));
            }
        });

        // Dışarı tıklandığında sidebar'ı kapat (sadece mobil)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                sidebarToggle.classList.remove('active');
            }
        });

        // Link tıklamalarında mobilde sidebar'ı kapat
        const sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    sidebarToggle.classList.remove('active');
                }
            });
        });

        // Pencere boyutu değiştiğinde kontrol
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });
    }
});

// Sayfa yüklendiğinde ve URL değiştiğinde menü durumunu kontrol et
document.addEventListener('DOMContentLoaded', function() {
    if(window.location.href.includes('reports.php')) {
        var statisticsPanel = document.getElementById('statisticsPanel');
        if(statisticsPanel) {
            statisticsPanel.classList.add('show');
            statisticsPanel.previousElementSibling.setAttribute('aria-expanded', 'true');
        }
    }
});

// Bootstrap dropdown'ı initialize et
document.addEventListener('DOMContentLoaded', function() {
    const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const notificationToggle = document.querySelector('.notification-dropdown .nav-link');
    const notificationMenu = document.querySelector('.notification-dropdown .dropdown-menu');
    
    if (notificationToggle && notificationMenu) {
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!notificationMenu.contains(e.target) && !notificationToggle.contains(e.target)) {
                notificationMenu.classList.remove('show');
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Profil dropdown kontrolü
    const profileToggle = document.querySelector('.profile-dropdown .nav-link');
    const profileMenu = document.querySelector('.profile-dropdown .dropdown-menu');
    
    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown
            profileMenu.classList.toggle('show');
            
            // Dropdown'ı konumlandır
            const rect = profileToggle.getBoundingClientRect();
            profileMenu.style.position = 'absolute';
            profileMenu.style.top = `0px`; // 10px offset
            profileMenu.style.right = '0';
            profileMenu.style.zIndex = '1000';
        });
        
        // Dışarı tıklandığında kapat
        document.addEventListener('click', function(e) {
            if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });
    }
});

$(document).ready(function() {
    // Bootstrap dropdown'ı initialize et
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Mevcut bildirim kontrolü fonksiyonu
    function checkNotifications() {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'GET',
            success: function(response) {
                if(response.success) {
                    $('#notificationList').html(response.html);
                    if(response.unread_count > 0) {
                        $('#notificationCount').text(response.unread_count).show();
                    } else {
                        $('#notificationCount').hide();
                    }
                }
            }
        });
    }

    // İlk yükleme ve periyodik kontrol
    checkNotifications();
    setInterval(checkNotifications, 10000);
});
</script>
</script>
<!-- Bootstrap JS - Sayfanın en altına ekleyin -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap JS ve Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

<script>
// Dropdown'ı etkinleştir
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>

<!-- Mevcut scriptlerden sonra ekleyin -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar ve toggle elementlerini seç
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.btn-toggle');
    const mobileToggle = document.querySelector('.mobile-nav .btn-toggle');
    const mainContent = document.querySelector('.main-content');

    // Sidebar toggle fonksiyonu
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        if (window.innerWidth <= 768) {
            localStorage.setItem('sidebarMobile', sidebar.classList.contains('active'));
        }
    }

    // Toggle butonlarına click event ekle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Açılır menüler için
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('href');
            const targetCollapse = document.querySelector(targetId);
            
            // Diğer menüleri kapat
            collapseButtons.forEach(otherButton => {
                if (otherButton !== this) {
                    const otherId = otherButton.getAttribute('href');
                    const otherCollapse = document.querySelector(otherId);
                    if (otherCollapse && otherCollapse.classList.contains('show')) {
                        new bootstrap.Collapse(otherCollapse).hide();
                    }
                }
            });
            
            // Tıklanan menüyü aç/kapat
            new bootstrap.Collapse(targetCollapse).toggle();
        });
    });

    // Sadece main content'e tıklandığında sidebar'ı kapat
    if (mainContent) {
        mainContent.addEventListener('click', function(e) {
            const clickedElement = e.target;
            // Eğer tıklanan element sidebar veya içindeki bir element değilse
            if (!sidebar.contains(clickedElement) && window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                localStorage.removeItem('sidebarMobile');
            }
        });
    }
});
</script>

</body>
</html>