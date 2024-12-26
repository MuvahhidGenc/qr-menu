<?php
require_once '../includes/config.php';
include 'navbar.php';

$db = new Database();

// Filtreleme parametreleri
$status = $_GET['status'] ?? 'all';
$table_id = $_GET['table'] ?? 'all';
$date = $_GET['date'] ?? date('Y-m-d');

// Sorgu oluştur
$query = "SELECT o.*, t.table_no 
          FROM orders o 
          LEFT JOIN tables t ON o.table_id = t.id 
          WHERE 1=1";

$params = [];

if($status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if($table_id != 'all') {
    $query .= " AND o.table_id = ?";
    $params[] = $table_id;
}

if($date) {
    $query .= " AND DATE(o.created_at) = ?";
    $params[] = $date;
}

$query .= " ORDER BY o.created_at DESC";

$orders = $db->query($query, $params)->fetchAll();
$tables = $db->query("SELECT * FROM tables")->fetchAll();
?>

<div class="main-content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Siparişler</h5>
        </div>
        <div class="card-body">
            <!-- Filtreler -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Tüm Durumlar</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Beklemede</option>
                        <option value="preparing" <?= $status == 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                        <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                        <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>İptal Edildi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="tableFilter">
                        <option value="all">Tüm Masalar</option>
                        <?php foreach($tables as $table): ?>
                            <option value="<?= $table['id'] ?>" <?= $table_id == $table['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($table['table_no']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateFilter" value="<?= $date ?>">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary" id="applyFilters">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>
            </div>

            <!-- Siparişler Tablosu -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Masa</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['table_no']) ?></td>
                                <td><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                <td>
                                    <select class="form-select form-select-sm status-select" 
                                            data-order-id="<?= $order['id'] ?>"
                                            style="width: 150px;">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Beklemede</option>
                                        <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>İptal Edildi</option>
                                    </select>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm view-order" data-order-id="<?= $order['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sipariş Detay Modal -->
<div class="modal fade" id="orderModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sipariş Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Sipariş detayları AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<!-- Sayfanın en altında, body kapanmadan önce -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/orders.js"></script>