<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
checkAuth();

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

// HTML oluştur
ob_start();
foreach($orders as $order): ?>
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
<?php endforeach;
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html
]); 