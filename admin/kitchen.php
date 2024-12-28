<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();
include 'navbar.php';

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

<div class="main-content">
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
.order-card {
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.order-items {
    font-size: 1.1rem;
    line-height: 1.8;
}

@media (max-width: 768px) {
    .order-items {
        font-size: 1rem;
        line-height: 1.6;
    }
}
</style> 