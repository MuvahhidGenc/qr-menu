<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
include 'navbar.php';

$db = new Database();

// Filtreleme
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Değerlendirmeleri çek
$query = "SELECT r.*, o.table_id, t.table_no 
          FROM reviews r 
          LEFT JOIN orders o ON r.order_id = o.id 
          LEFT JOIN tables t ON o.table_id = t.id 
          WHERE 1=1";

if ($status != 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

if ($date) {
    $query .= " AND DATE(r.created_at) = ?";
    $params[] = $date;
}

$query .= " ORDER BY r.created_at DESC";

$reviews = $db->query($query, $params ?? [])->fetchAll();
?>

    <div class="container-fluid p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Müşteri Değerlendirmeleri</h2>
        </div>

        <!-- Filtreler -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label>Tarih</label>
                        <input type="date" name="date" class="form-control" value="<?= $date ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Durum</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Tümü</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                            <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Onaylı</option>
                            <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Reddedilmiş</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrele</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Değerlendirme Listesi -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Masa</th>
                                <th>Müşteri</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reviews as $review): ?>
                                <tr>
                                    <td>Masa <?= $review['table_no'] ?></td>
                                    <td><?= htmlspecialchars($review['customer_name']) ?></td>
                                    <td>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?= htmlspecialchars($review['comment']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($review['status']) ?>">
                                            <?= getStatusText($review['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewReview(<?= $review['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($review['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-success" 
                                                        onclick="updateStatus(<?= $review['id'] ?>, 'approved')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="updateStatus(<?= $review['id'] ?>, 'rejected')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

<?php
function getStatusBadgeClass($status) {
    return [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger'
    ][$status] ?? 'secondary';
}

function getStatusText($status) {
    return [
        'pending' => 'Bekliyor',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi'
    ][$status] ?? $status;
}
?>

<script>
function viewReview(id) {
    fetch(`ajax/get_review.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                let productReviews = '';
                if(data.product_reviews.length > 0) {
                    productReviews = '<hr><h6>Ürün Değerlendirmeleri:</h6>';
                    data.product_reviews.forEach(pr => {
                        productReviews += `
                            <p><strong>${pr.product_name}:</strong> 
                            ${getStarRating(pr.rating)}
                            <br>${pr.comment || ''}</p>
                        `;
                    });
                }

                Swal.fire({
                    title: 'Değerlendirme Detayları',
                    html: `
                        <div class="text-start">
                            <p><strong>Müşteri:</strong> ${data.review.customer_name}</p>
                            <p><strong>Masa:</strong> ${data.review.table_no}</p>
                            <p><strong>Genel Puan:</strong> ${getStarRating(data.review.rating)}</p>
                            <p><strong>Yorum:</strong> ${data.review.comment || '-'}</p>
                            <p><strong>Tarih:</strong> ${data.review.created_at}</p>
                            ${productReviews}
                        </div>
                    `,
                    confirmButtonText: 'Kapat'
                });
            }
        });
}

function getStarRating(rating) {
    let stars = '';
    for(let i = 1; i <= 5; i++) {
        stars += `<i class="fas fa-star ${i <= rating ? 'text-warning' : 'text-muted'}"></i>`;
    }
    return stars;
}

function updateStatus(id, status) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Değerlendirme durumunu güncellemek istediğinize emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/update_review_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Değerlendirme durumu güncellendi.',
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: data.message || 'Bir hata oluştu!',
                        confirmButtonText: 'Tamam'
                    });
                }
            });
        }
    });
}
</script> 