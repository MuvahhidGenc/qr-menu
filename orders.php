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

// Sadece masanın en son aktif siparişini getir
$orders = $db->query(
    "SELECT o.*, 
            t.table_no,
            COUNT(oi.id) as total_items,
            CASE 
                WHEN o.status = 'pending' THEN 'Hazırlanıyor'
                WHEN o.status = 'preparing' THEN 'Mutfakta'
                WHEN o.status = 'ready' THEN 'Servise Hazır'
                ELSE o.status 
            END as status_text
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN tables t ON o.table_id = t.id
     WHERE o.table_id = ? 
     AND o.status = 'pending'  /* Sadece bekleyen sipariş */
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 1",  /* Sadece en son sipariş */
    [$table_id]
)->fetchAll();

// Sipariş detaylarını getir
if (!empty($orders)) {
    foreach ($orders as &$order) {
        $items = $db->query(
            "SELECT oi.*, p.name as product_name 
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?
             ORDER BY oi.id DESC",
            [$order['id']]
        )->fetchAll();
        
        $order['items'] = $items;
    }
}

// Debug bilgileri
/*echo "<pre style='display:none;'>";
echo "Table ID: " . $table_id . "\n";
echo "Orders Query Result: ";
print_r($orders);
echo "</pre>";*/

// Masa durumu kontrolü
$tableStatus = $db->query(
    "SELECT status FROM tables WHERE id = ?", 
    [$table_id]
)->fetch();

// Sadece masa kapalıysa yönlendir
if ($tableStatus && $tableStatus['status'] == 'closed') {
    header('Location: index.php');
    exit;
}

include 'includes/customer-header.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktif Siparişim - Restaurant QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .order-page {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(var(--primary-red-rgb), 0.1);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .back-btn {
            background: white;
            color: var(--primary-red);
            border: 2px solid var(--primary-red);
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: var(--primary-red);
            color: white;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .order-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .order-body {
            padding: 20px;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge i {
            font-size: 0.8rem;
        }

        .status-pending { 
            background-color: #fff3cd; 
            color: #856404;
        }
        
        .status-preparing { 
            background-color: #cce5ff; 
            color: #004085;
        }
        
        .status-ready { 
            background-color: #d4edda; 
            color: #155724;
        }

        .order-items {
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .order-items table {
            margin: 0;
        }

        .order-items th {
            background: rgba(var(--primary-red-rgb), 0.05);
            font-weight: 600;
            padding: 15px;
        }

        .order-items td {
            padding: 15px;
            vertical-align: middle;
        }

        .total-row {
            background: rgba(var(--primary-red-rgb), 0.02);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-red);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        .create-order-btn {
            background: var(--primary-red);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .create-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(var(--primary-red-rgb), 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="order-page">
        <div class="page-header">
            <h1 class="page-title">Aktif Siparişim</h1>
            <a href="index.php?table=<?= $table_id ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Menüye Dön
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>Henüz Siparişiniz Bulunmuyor</h3>
                <p>Hemen yeni bir sipariş oluşturarak lezzetli yemeklerimizi tadabilirsiniz.</p>
                <a href="index.php?table=<?= $table_id ?>" class="create-order-btn">
                    <i class="fas fa-plus"></i>
                    Sipariş Oluştur
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Sipariş #<?= $order['id'] ?></h5>
                                <small class="text-muted">
                                    <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                </small>
                            </div>
                            <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                <i class="fas fa-clock"></i>
                                <?= $order['status_text'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-items">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th class="text-center">Adet</th>
                                        <th class="text-end">Fiyat</th>
                                        <th class="text-end">Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items = $db->query(
                                        "SELECT oi.*, p.name as product_name 
                                         FROM order_items oi
                                         LEFT JOIN products p ON oi.product_id = p.id
                                         WHERE oi.order_id = ?
                                         ORDER BY oi.id DESC",
                                        [$order['id']]
                                    )->fetchAll();
                                    
                                    foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td class="text-center"><?= $item['quantity'] ?></td>
                                            <td class="text-end"><?= number_format($item['price'], 2) ?> ₺</td>
                                            <td class="text-end"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₺</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="3" class="text-end">Toplam Tutar:</td>
                                        <td class="text-end"><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 