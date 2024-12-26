<?php
require_once '../includes/config.php';
include 'navbar.php';

$db = new Database();
$tables = $db->query("SELECT * FROM tables ORDER BY table_no")->fetchAll();
?>

<div class="main-content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Masalar</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
                <i class="fas fa-plus"></i> Yeni Masa
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($tables as $table): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($table['table_no']) ?></h5>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input table-status" 
                                               data-id="<?= $table['id'] ?>" 
                                               <?= $table['status'] ? 'checked' : '' ?>>
                                        <label class="form-check-label">Aktif</label>
                                    </div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info generate-qr" 
                                                data-id="<?= $table['id'] ?>" 
                                                data-table="<?= htmlspecialchars($table['table_no']) ?>">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning edit-table" 
                                                data-id="<?= $table['id'] ?>"
                                                data-table="<?= htmlspecialchars($table['table_no']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-table" 
                                                data-id="<?= $table['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Masa Modal -->
<div class="modal fade" id="addTableModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Masa Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTableForm">
                    <div class="mb-3">
                        <label>Masa Adı</label>
                        <input type="text" class="form-control" name="table_no" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- QR Kod Modal -->
<div class="modal fade" id="qrModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Masa QR Kodu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCode"></div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary download-qr">
                        <i class="fas fa-download"></i> İndir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

