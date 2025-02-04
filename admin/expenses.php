<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Para birimi ayarını al
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'currency'")->fetch();
$currency = $settingsQuery['setting_value'] ?? '₺';

// Gider kategorilerini getir
$categories = $db->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();

// Yöneticileri getir
$admins = $db->query("SELECT id, name FROM admins ORDER BY name")->fetchAll();

// Giderleri getir (kategori ve admin bilgileriyle birlikte)
$expenses = $db->query("
    SELECT e.*, 
           ec.name as category_name, 
           ec.color as category_color,
           a.name as admin_name,
           DATE_FORMAT(e.expense_date, '%d.%m.%Y') as formatted_date
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    LEFT JOIN admins a ON e.admin_id = a.id
    ORDER BY e.expense_date DESC
")->fetchAll();

require_once 'navbar.php';
?>

<!-- Custom CSS -->
<style>
.expense-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,.05);
    transition: transform 0.3s;
}

.expense-card:hover {
    transform: translateY(-5px);
}

.category-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}

.stats-card {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.date-filter {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 0 15px rgba(0,0,0,.05);
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 0 20px rgba(0,0,0,.05);
    min-height: 400px; /* Minimum yükseklik */
    position: relative; /* Chart.js için gerekli */
}

.chart-wrapper {
    position: relative;
    height: 350px; /* Sabit yükseklik */
    width: 100%;
}

@media (max-width: 768px) {
    .chart-wrapper {
        height: 300px;
    }
}

.expense-table th {
    background: #f8f9fa;
    border: none;
}

.expense-table td {
    vertical-align: middle;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: #f8f9fa;
    border-radius: 15px 15px 0 0;
}

.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 15px;
}

.btn {
    border-radius: 10px;
    padding: 8px 20px;
}
</style>

<div class="container-fluid py-4">
    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h6 class="text-white mb-3">Bu Ay Toplam Gider</h6>
                <h3 class="text-white mb-0" id="monthlyTotal">Yükleniyor...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #2ecc71, #27ae60);">
                <h6 class="text-white mb-3">Yıllık Toplam Gider</h6>
                <h3 class="text-white mb-0" id="yearlyTotal">Yükleniyor...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                <h6 class="text-white mb-3">En Yüksek Gider Kategorisi</h6>
                <h3 class="text-white mb-0" id="topCategory">Yükleniyor...</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
                <h6 class="text-white mb-3">Ortalama Günlük Gider</h6>
                <h3 class="text-white mb-0" id="dailyAverage">Yükleniyor...</h3>
            </div>
        </div>
    </div>

    <!-- Filtreler ve Grafikler -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="chart-container">
                <h5 class="mb-4">Aylık Gider Analizi</h5>
                <div class="chart-wrapper">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5 class="mb-4">Kategori Dağılımı</h5>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarih Filtresi -->
    <div class="date-filter mb-4">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="startDate">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="endDate">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <select class="form-select" id="categoryFilter">
                    <option value="">Tümü</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" id="filterButton">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                    <button class="btn btn-secondary" id="clearFilter">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Gider Listesi -->
    <div class="card expense-card">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Gider Listesi</h5>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fas fa-plus"></i> Yeni Gider Ekle
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table expense-table" id="expenseTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kategori</th>
                            <th>Açıklama</th>
                            <th>Tutar</th>
                            <th>Ekleyen</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?= $expense['formatted_date'] ?></td>
                            <td>
                                <span class="category-badge" 
                                      style="background-color: <?= $expense['category_color'] ?>"
                                      data-category-id="<?= $expense['category_id'] ?>">
                                    <?= htmlspecialchars($expense['category_name']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($expense['description']) ?></td>
                            <td><?= number_format($expense['amount'], 2) . ' ' . $currency ?></td>
                            <td><?= htmlspecialchars($expense['admin_name']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-expense" 
                                        data-id="<?= $expense['id'] ?>"
                                        data-amount="<?= $expense['amount'] ?>"
                                        data-category="<?= $expense['category_id'] ?>"
                                        data-description="<?= htmlspecialchars($expense['description']) ?>"
                                        data-date="<?= $expense['expense_date'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-expense" data-id="<?= $expense['id'] ?>">
                                    <i class="fas fa-trash"></i>
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

<!-- Gider Ekleme Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Gider Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addExpenseForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id" id="expense_category" required>
                            <option value="">Kategori Seçin</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Personel Seçimi (Başlangıçta gizli) -->
                    <div class="mb-3" id="staff_selection" style="display:none;">
                        <label class="form-label">Personel Seçimi</label>
                        <select class="form-select" name="staff_id" id="staff_id">
                            <option value="">Personel Seçin</option>
                            <?php 
                            // Maaşı olan personelleri getir
                            $staff = $db->query("SELECT id, name, salary FROM admins WHERE salary IS NOT NULL AND salary > 0 ORDER BY name")->fetchAll();
                            foreach ($staff as $person): 
                            ?>
                                <option value="<?= $person['id'] ?>" data-salary="<?= $person['salary'] ?>">
                                    <?= htmlspecialchars($person['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="text" class="form-control" name="amount" id="expense_amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" class="form-control" name="expense_date" id="expense_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="expense_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gider Düzenleme Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gider Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExpenseForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id" id="edit_category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="text" class="form-control" name="amount" id="edit_amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" class="form-control" name="expense_date" id="edit_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Önce jQuery yüklenecek -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Sonra jQuery Mask Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<!-- DataTables CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

<!-- DataTables ve Export Scriptleri -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables-buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- Export için gerekli kütüphaneler -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- En son custom JS -->
<script src="assets/js/expenses.js"></script>

<?php include 'footer.php'; ?> 