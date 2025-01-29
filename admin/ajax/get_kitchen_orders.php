<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('kitchen.view')) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}

$db = new Database();

// İşlem yetki kontrolü
$canManage = hasPermission('kitchen.manage');

// Aktif siparişleri çek
$orders = $db->query("
    SELECT o.*, t.table_no, o.notes, o.note,
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
                <?php if(!empty($order['notes']) || !empty($order['note'])): ?>
                    <div class="alert alert-info mt-2 mb-0 p-2 small">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Notlar:</strong><br>
                        <?php if(!empty($order['notes'])): ?>
                            <?= htmlspecialchars($order['notes']) ?><br>
                        <?php endif; ?>
                        <?php if(!empty($order['note'])): ?>
                            <?= nl2br(htmlspecialchars($order['note'])) ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <?php if ($canManage): ?>
                <div class="order-actions">
                    <button type="button" class="btn btn-success prepare-order" data-id="<?= $order['id'] ?>">
                        <i class="fas fa-check"></i> Hazırla
                    </button>
                    
                    <button type="button" class="btn btn-warning cancel-order" data-id="<?= $order['id'] ?>">
                        <i class="fas fa-times"></i> İptal
                    </button>
                </div>
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