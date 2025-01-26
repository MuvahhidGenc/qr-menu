<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Süper Admin ve İşletme Sahibi kontrolü
if (!hasPermission('payments.view') || !hasPermission('payments.manage')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Tamamlanmış ödemeleri çek - düzeltilmiş sorgu
$payments = $db->query("
    SELECT 
        p.id as payment_id,
        p.table_id,
        p.payment_method,
        p.total_amount,
        p.paid_amount,
        p.payment_note,
        p.status,
        p.created_at,
        t.table_no,
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', pr.name) SEPARATOR ', ') as order_details
    FROM payments p
    LEFT JOIN tables t ON p.table_id = t.id
    LEFT JOIN orders o ON p.id = o.payment_id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products pr ON oi.product_id = pr.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alınmış Ödemeler - QR Menü Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Alınmış Ödemeler</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ödeme ID</th>
                                        <th>Masa No</th>
                                        <th>Toplam Tutar</th>
                                        <th>Ödenen Tutar</th>
                                        <th>Ödeme Yöntemi</th>
                                        <th>Sipariş Detayları</th>
                                        <th>Durum</th>
                                        <th>Not</th>
                                        <th>Tarih</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>#<?= $payment['payment_id'] ?></td>
                                        <td>Masa <?= $payment['table_no'] ?></td>
                                        <td><?= number_format($payment['total_amount'], 2) ?> ₺</td>
                                        <td><?= number_format($payment['paid_amount'], 2) ?> ₺</td>
                                        <td><?= $payment['payment_method'] == 'cash' ? 'Nakit' : 'POS' ?></td>
                                        <td><?= $payment['order_details'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $payment['status'] == 'completed' ? 'success' : 'danger' ?>">
                                                <?= $payment['status'] == 'completed' ? 'Tamamlandı' : 'İptal Edildi' ?>
                                            </span>
                                        </td>
                                        <td><?= $payment['payment_note'] ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></td>
                                        <td>
                                            <?php if ($payment['status'] == 'completed'): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="cancelPayment(<?= $payment['payment_id'] ?>)">
                                                <i class="fas fa-times"></i> İptal Et
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($payment['status'] == 'cancelled'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="reorderToTable(<?= $payment['payment_id'] ?>, <?= $payment['table_id'] ?>, '<?= $payment['table_no'] ?>')">
                                                <i class="fas fa-redo"></i> Masaya Ekle
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    // Ödeme İptali
    function cancelPayment(paymentId) {
        Swal.fire({
            title: 'Ödeme İptal',
            html: `
                <div class="mb-3">
                    <label for="cancelNote" class="form-label">İptal Nedeni</label>
                    <textarea id="cancelNote" class="form-control" rows="3" placeholder="İptal nedenini açıklayın"></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, İptal Et',
            cancelButtonText: 'Vazgeç',
            preConfirm: () => {
                const note = document.getElementById('cancelNote').value;
                if (!note.trim()) {
                    Swal.showValidationMessage('İptal nedeni girmelisiniz');
                    return false;
                }
                return note;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/cancel_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        cancel_note: result.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Başarılı!', 'Ödeme iptal edildi.', 'success')
                        .then(() => location.reload());
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    Swal.fire('Hata!', error.message, 'error');
                });
            }
        });
    }

    // Masaya Tekrar Ekleme
    function reorderToTable(paymentId, tableId, tableNo) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: `Masa ${tableNo}'e iptal edilmiş siparişler tekrar eklenecek. Onaylıyor musunuz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Ekle',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/reorder_to_table.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_id: paymentId,
                        table_id: tableId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Başarılı!', 'Siparişler masaya eklendi.', 'success')
                        .then(() => location.reload());
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    Swal.fire('Hata!', error.message, 'error');
                });
            }
        });
    }
    </script>
</body>
</html> 