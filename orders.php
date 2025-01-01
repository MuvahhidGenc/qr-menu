<?php
require_once 'includes/config.php';
require_once 'includes/cart.php';
$db = new Database();

// Masa ID'sini al
$table_id = $_SESSION['table_id'] ?? null;
if (!$table_id) {
    header('Location: index.php');
    exit;
}

// Debug - Tüm siparişleri göster
echo "<pre style='display:none;'>";
echo "Checking all orders for table $table_id:\n";
$all_orders = $db->query("SELECT id, status FROM orders WHERE table_id = ?", [$table_id])->fetchAll();
print_r($all_orders);
echo "</pre>";

// Masanın tüm ödenmemiş siparişlerini getir (DESC sıralama)
$orders = $db->query(
    "SELECT DISTINCT o.id, o.created_at, o.status, o.total_amount
     FROM orders o
     WHERE o.table_id = ? 
     AND o.status NOT IN ('paid', 'completed')
     GROUP BY o.id
     ORDER BY o.id DESC",
    [$table_id]
)->fetchAll();

// Debug - Filtrelenmiş siparişleri göster
echo "<pre style='display:none;'>";
echo "Filtered orders (Descending order):\n";
print_r($orders);
echo "</pre>";

// Her sipariş için detayları getir
$filtered_orders = [];
foreach ($orders as $order) {
    // Sipariş ürünlerini kontrol et
    $items = $db->query(
        "SELECT COUNT(*) as count 
         FROM order_items 
         WHERE order_id = ?",
        [$order['id']]
    )->fetch();

    // Eğer siparişte ürün varsa listeye ekle
    if ($items['count'] > 0) {
        $order['status_text'] = match($order['status']) {
            'pending' => 'Sipariş Alındı',
            'preparing' => 'Hazırlanıyor',
            'ready' => 'Servise Hazır',
            'delivered' => 'Servis Edildi',
            'paid' => 'Ödeme Alındı',
            'completed' => 'Tamamlandı',
            default => $order['status']
        };

        $order['status_class'] = match($order['status']) {
            'pending' => 'bg-info',
            'preparing' => 'bg-warning',
            'ready' => 'bg-success',
            'delivered' => 'bg-primary',
            'paid' => 'bg-secondary',
            'completed' => 'bg-dark',
            default => 'bg-light'
        };

        // Sipariş ürünlerini getir
        $order['items'] = $db->query(
            "SELECT oi.*, p.name as product_name,
                    (oi.quantity * oi.price) as item_total
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC",
            [$order['id']]
        )->fetchAll();

        // Sipariş toplamını hesapla
        $order['total_amount'] = array_sum(array_column($order['items'], 'item_total'));
        
        $filtered_orders[] = $order;
    }
}

// Debug - Son filtrelenmiş siparişleri göster
echo "<pre style='display:none;'>";
echo "Final filtered orders:\n";
foreach ($filtered_orders as $order) {
    echo "Order ID: {$order['id']}, Status: {$order['status']}, Items: " . count($order['items']) . "\n";
}
echo "</pre>";

$orders = $filtered_orders;

// Geri dönüş URL'sini belirle
$back_url = $_SERVER['HTTP_REFERER'] ?? 'menu.php'; // Eğer referrer yoksa menu.php'ye git

include 'includes/customer-header.php';
?>

<!-- Siparişler Listesi -->
<div class="container mt-4 mb-5">
    <!-- Başlık ve Geri Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Siparişlerim (<?= count($orders) ?>)</h4>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            Henüz aktif siparişiniz bulunmuyor.
        </div>
    <?php else: ?>
        <?php 
        $grand_total = 0;
        foreach ($orders as $order): 
            $grand_total += $order['total_amount'];
        ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Sipariş #<?= $order['id'] ?></strong>
                        <br>
                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                    </div>
                    <span class="badge <?= $order['status_class'] ?>"><?= $order['status_text'] ?></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th class="text-end">Adet</th>
                                    <th class="text-end">Fiyat</th>
                                    <th class="text-end">Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td class="text-end"><?= $item['quantity'] ?></td>
                                        <td class="text-end"><?= number_format($item['price'], 2) ?> ₺</td>
                                        <td class="text-end"><?= number_format($item['item_total'], 2) ?> ₺</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Sipariş Toplamı</strong></td>
                                    <td class="text-end"><strong><?= number_format($order['total_amount'], 2) ?> ₺</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Genel Toplam Kartı -->
        <div class="card bg-light">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Genel Toplam</h5>
                    <h4 class="mb-0 text-primary"><?= number_format($grand_total, 2) ?> ₺</h4>
                </div>
            </div>
        </div>

        <!-- Alt Geri Butonu -->
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Sabit Alt Geri Butonu (Mobil için) -->
<div class="position-fixed bottom-0 start-0 w-100 p-3 bg-white border-top d-md-none">
    <a href="javascript:history.back()" class="btn btn-outline-secondary w-100">
        <i class="fas fa-arrow-left"></i> Geri Dön
    </a>
</div>

<!-- Mobil için alt padding -->
<div class="d-md-none" style="height: 80px;"></div>

<!-- JavaScript ile geri dönüş kontrolü -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tüm geri butonlarını seç
    const backButtons = document.querySelectorAll('a[href="javascript:history.back()"]');
    
    backButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (document.referrer) {
                // Eğer önceki sayfa varsa oraya dön
                window.history.back();
            } else {
                // Yoksa menüye yönlendir
                window.location.href = 'menu.php';
            }
        });
    });
});
</script>