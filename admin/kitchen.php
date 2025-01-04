<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
include 'navbar.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Aktif siparişleri çek
$orders = $db->query("
    SELECT o.*, t.table_no, 
    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR '<br>') as items
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.status IN ('new', 'preparing')
    GROUP BY o.id
    ORDER BY o.created_at ASC
")->fetchAll();
?>

    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Mutfak Ekranı</h2>
            <button class="btn btn-primary" onclick="refreshOrders()">
                <i class="fas fa-sync-alt"></i> Yenile
            </button>
        </div>

        <div class="row g-3" id="ordersContainer">
            <?php foreach($orders as $order): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 order-card" data-order-id="<?= $order['id'] ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Masa <?= $order['table_no'] ?></h5>
                            <span class="badge bg-<?= $order['status'] == 'new' ? 'danger' : 'warning' ?>">
                                <?= $order['status'] == 'new' ? 'Yeni' : 'Hazırlanıyor' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-2">
                                Sipariş No: #<?= $order['id'] ?><br>
                                Sipariş Zamanı: <?= date('H:i', strtotime($order['created_at'])) ?>
                            </p>
                            <div class="order-items">
                                <?= $order['items'] ?>
                            </div>
                            <?php if($order['notes']): ?>
                                <div class="alert alert-info mt-2 mb-0 p-2 small">
                                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($order['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <?php if($order['status'] == 'new'): ?>
                                <button class="btn btn-warning btn-sm w-100" 
                                        onclick="updateStatus(<?= $order['id'] ?>, 'preparing')">
                                    <i class="fas fa-clock"></i> Hazırlanıyor
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success btn-sm w-100" 
                                        onclick="updateStatus(<?= $order['id'] ?>, 'ready')">
                                    <i class="fas fa-check"></i> Hazır
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Ses efekti için
const newOrderSound = new Audio('/qr-menu/admin/assets/sounds/notification.mp3');

// Her 30 saniyede bir siparişleri güncelle
setInterval(refreshOrders, 30000);

function refreshOrders() {
    fetch('ajax/get_kitchen_orders.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const container = document.getElementById('ordersContainer');
                if(data.html !== container.innerHTML) {
                    // Yeni sipariş varsa ses çal
                    if(data.html.includes('new') && container.innerHTML !== '') {
                        newOrderSound.play().catch(e => console.log('Ses çalma hatası:', e));
                    }
                    container.innerHTML = data.html;
                }
            }
        });
}

function updateStatus(orderId, status) {
    fetch('ajax/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            refreshOrders();
        } else {
            alert('Durum güncellenirken bir hata oluştu!');
        }
    });
}
</script>

<style>
/* Ana Container */
.container-fluid {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem !important;
}

/* Başlık Alanı */
.header-section {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.header-section h2 {
    color: white;
    margin: 0;
    font-size: 1.8rem;
    font-weight: 600;
}

.btn-refresh {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.btn-refresh:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

/* Sipariş Kartları */
.order-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    background: white;
    overflow: hidden;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Kart Başlığı */
.card-header {
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.2rem;
}

.card-header h5 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Durum Badge'leri */
.badge {
    padding: 0.6rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
}

.bg-danger {
    background: linear-gradient(45deg, #e74c3c, #c0392b) !important;
}

.bg-warning {
    background: linear-gradient(45deg, #f1c40f, #f39c12) !important;
}

/* Kart İçeriği */
.card-body {
    padding: 1.5rem;
}

.order-items {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #2c3e50;
}

/* Sipariş Notları */
.alert-info {
    background: #f1f9ff;
    border: none;
    border-radius: 10px;
    color: #3498db;
}

/* Aksiyon Butonları */
.card-footer {
    background: #fff;
    border-top: 1px solid rgba(0,0,0,0.05);
    padding: 1.2rem;
}

.btn-warning {
    background: linear-gradient(45deg, #f1c40f, #f39c12);
    border: none;
    color: white;
}

.btn-success {
    background: linear-gradient(45deg, #2ecc71, #27ae60);
    border: none;
}

.btn-sm {
    padding: 0.7rem 1rem;
    font-weight: 500;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Sipariş Zamanı */
.text-muted {
    color: #7f8c8d !important;
}

/* Animasyonlar */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.order-card {
    animation: slideIn 0.3s ease-out;
}

/* Responsive Düzenlemeler */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .header-section {
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .order-items {
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .card-header h5 {
        font-size: 1.1rem;
    }
}

/* Yeni Sipariş Animasyonu */
@keyframes highlight {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.order-card[data-status="new"] {
    animation: highlight 1s ease infinite;
    border-left: 4px solid #e74c3c;
}
</style> 