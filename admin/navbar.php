<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/assets/css/style.css">

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
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar me-2"></i>Raporlar
                        </a>
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
</script>
</script>