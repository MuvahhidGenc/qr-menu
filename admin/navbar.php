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

/* Modern Bildirim Dropdown Stilleri */
.notification-dropdown .dropdown-menu {
    min-width: 320px;
    padding: 0;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    background: #fff;
    margin-top: 12px;
}

/* Bildirim Header */
.notification-dropdown .dropdown-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: linear-gradient(to right, #f8f9fa, #fff);
}

.notification-dropdown .dropdown-header h6 {
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

/* Bildirim Listesi */
.notification-list {
    max-height: 360px;
    overflow-y: auto;
    padding: 8px;
}

/* Bildirim Öğesi */
.notification-item {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 4px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.notification-item:hover {
    background: #f8f9fa;
    border-color: rgba(0,0,0,0.05);
    transform: translateX(4px);
}

.notification-item.unread {
    background: rgba(13,110,253,0.04);
    border-left: 3px solid #0d6efd;
}

/* Bildirim İçeriği */
.notification-item .mb-1 {
    font-size: 0.9rem;
    color: #2c3e50;
    font-weight: 500;
}

.notification-item small {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Okunmamış Göstergesi */
.unread-indicator {
    width: 8px;
    height: 8px;
    background: #0d6efd;
    border-radius: 50%;
    margin-left: 8px;
}

/* Tümünü Okundu İşaretle Butonu */
.mark-all-read {
    color: #0d6efd;
    font-size: 0.85rem;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.mark-all-read:hover {
    background: rgba(13,110,253,0.1);
    color: #0b5ed7;
}

/* Ses Kontrol Butonu */
#toggleSound {
    padding: 4px 8px;
    color: #6c757d;
    border-radius: 4px;
    transition: all 0.2s ease;
}

#toggleSound:hover {
    background: rgba(108,117,125,0.1);
    color: #495057;
}

/* Scrollbar Stilleri */
.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: transparent;
}

.notification-list::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: rgba(0,0,0,0.2);
}

/* Dropdown Animasyonu */
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

.notification-dropdown .dropdown-menu.show {
    animation: dropdownFade 0.2s ease;
}

/* Bildirim Badge */
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
    <!-- Bildirim Ikonu -->
    <!-- Navbar içinde bildirim dropdown'ı -->
    <div class="nav-item dropdown notification-dropdown me-3">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notificationCount"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="dropdown-header d-flex justify-content-between align-items-center p-3">
                <h6 class="mb-0">Bildirimler</h6>
                <div>
                    <button type="button" class="btn btn-sm btn-link" id="toggleSound">
                        <i class="fas fa-volume-up" id="soundIcon"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link mark-all-read">
                        Tümünü Okundu İşaretle
                    </button>
                </div>
            </div>

            <div class="notification-list" id="notificationList">
                <!-- Bildirimler buraya gelecek -->
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
            <div class="sidebar-header">
                <h3 class="mb-0">QR Menü Admin</h3>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                            <i class="fas fa-shopping-cart me-2"></i>Siparişler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="tables.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'active' : '' ?>">
                            <i class="fas fa-chair me-2"></i>Masalar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                            <i class="fas fa-tags me-2"></i>Kategoriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                            <i class="fas fa-utensils me-2"></i>Ürünler
                        </a>
                    </li>
                    <!-- Genel İstatistikler panel menüsü -->
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?= strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'active show' : '' ?>" 
                           data-bs-toggle="collapse" 
                           href="#statisticsPanel" 
                           role="button"
                           aria-expanded="<?= strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'true' : 'false' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up me-2" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07Z"/>
                            </svg>
                            Genel İstatistikler 
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down ms-auto" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                        <div class="collapse navbar-collapse bg-dark <?= strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'show' : '' ?>" 
                             id="statisticsPanel">
                            <ul class="navbar-nav ps-3">
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" 
                                       href="reports.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-bar-graph me-2" viewBox="0 0 16 16">
                                            <path d="M10 13.5a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-6a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v6zm-2.5.5a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-1zm-3 0a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5h-1z"/>
                                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                        </svg>
                                        Raporlar
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'financial.php' ? 'active' : '' ?>" 
                                       href="financial.php">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash-stack me-2" viewBox="0 0 16 16">
                                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                                        </svg>
                                        Finansal Raporlar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                   
                    <li class="nav-item">
                        <a href="kitchen.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : '' ?>">
                            <i class="fas fa-fire me-2"></i>Mutfak
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reservations.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt me-2"></i>Rezervasyonlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                            <i class="fas fa-cog me-2"></i>Ayarlar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">
                            <i class="fas fa-star me-2"></i>Değerlendirmeler
                        </a>
                    </li>
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
</script>
</script>
<!-- Bootstrap JS - Sayfanın en altına ekleyin -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>