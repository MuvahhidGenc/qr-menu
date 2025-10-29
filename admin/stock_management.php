<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// İzin kontrolü
if (!hasPermission('products.view')) {
    header('Location: dashboard.php?error=unauthorized');
    exit;
}

$db = new Database();
$pageTitle = "Stok Yönetimi";

// İstatistikler
$stats = $db->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(stock) as total_stock,
        SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_count,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count
    FROM products 
    WHERE status = 1
")->fetch();

// Son stok hareketleri
$recent_movements = $db->query("
    SELECT 
        sm.*,
        p.name as product_name,
        p.barcode,
        p.image
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    ORDER BY sm.created_at DESC
    LIMIT 20
")->fetchAll();

// Düşük stoklu ürünler
$low_stock_products = $db->query("
    SELECT 
        p.*,
        c.name as category_name,
        COALESCE(COUNT(sm.id), 0) as total_movements
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN stock_movements sm ON p.id = sm.product_id AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    WHERE p.status = 1 AND p.stock <= 10
    GROUP BY p.id
    ORDER BY p.stock ASC, p.name ASC
")->fetchAll();

// Stoksuz ürünler
$out_of_stock = $db->query("
    SELECT 
        p.*,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.stock = 0
    ORDER BY p.name ASC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .modern-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .modern-table thead th {
            padding: 18px 15px;
            font-weight: 600;
            border: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modern-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .modern-table tbody tr:hover {
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .product-thumb {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stock-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .stock-badge.high {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .stock-badge.medium {
            background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
            color: white;
        }
        
        .stock-badge.low {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stock-badge.out {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .movement-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .movement-badge.in {
            background: #d4edda;
            color: #155724;
        }
        
        .movement-badge.out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .movement-badge.adjustment {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .nav-tabs-modern {
            border: none;
            gap: 10px;
        }
        
        .nav-tabs-modern .nav-link {
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            color: #667eea;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .nav-tabs-modern .nav-link:hover {
            background: #e9ecef;
        }
        
        .nav-tabs-modern .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h5 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-boxes"></i>
            Stok Yönetimi
        </h1>
        <p><i class="fas fa-info-circle me-1"></i> Ürün stoklarını takip edin, düşük stoklu ürünleri görün ve stok hareketlerini yönetin</p>
    </div>
    
    <!-- İstatistikler -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['total_products'] ?? 0) ?></div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['total_stock'] ?? 0) ?></div>
                <div class="stat-label">Toplam Stok Adedi</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['low_stock_count'] ?? 0) ?></div>
                <div class="stat-label">Düşük Stoklu Ürün</div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['out_of_stock_count'] ?? 0) ?></div>
                <div class="stat-label">Stokta Yok</div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs-modern mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#movements">
                <i class="fas fa-history me-2"></i>Stok Hareketleri
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#low-stock">
                <i class="fas fa-exclamation-triangle me-2"></i>Düşük Stok (<?= count($low_stock_products) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#out-of-stock">
                <i class="fas fa-times-circle me-2"></i>Stokta Yok (<?= count($out_of_stock) ?>)
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Stok Hareketleri -->
        <div class="tab-pane fade show active" id="movements">
            <div class="modern-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ürün</th>
                            <th>Ürün Adı</th>
                            <th style="width: 120px;">İşlem</th>
                            <th style="width: 100px;">Miktar</th>
                            <th style="width: 120px;">Önceki Stok</th>
                            <th style="width: 120px;">Yeni Stok</th>
                            <th>Not</th>
                            <th style="width: 150px;">Kullanıcı</th>
                            <th style="width: 150px;">Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_movements)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <h5>Henüz Stok Hareketi Yok</h5>
                                        <p>İlk stok hareketi oluşturulduğunda burada görünecek</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_movements as $movement): ?>
                                <tr>
                                    <td>
                                        <?php if ($movement['image']): ?>
                                            <img src="../<?= htmlspecialchars($movement['image']) ?>" class="product-thumb" alt="">
                                        <?php else: ?>
                                            <div class="product-thumb" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?= mb_substr($movement['product_name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($movement['product_name']) ?></div>
                                        <?php if ($movement['barcode']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-barcode"></i> <?= htmlspecialchars($movement['barcode']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $type = strtoupper($movement['movement_type']);
                                        $badgeClass = 'adjustment';
                                        if ($type === 'IN') $badgeClass = 'in';
                                        if ($type === 'OUT' || $type === 'SALE') $badgeClass = 'out';
                                        
                                        $operationText = [
                                            'IN' => 'Giriş',
                                            'OUT' => 'Çıkış',
                                            'SALE' => 'Satış',
                                            'ADJUSTMENT' => 'Düzeltme'
                                        ][$type] ?? ucfirst(strtolower($type));
                                        ?>
                                        <span class="movement-badge <?= $badgeClass ?>">
                                            <?= $operationText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $quantity = (int)$movement['quantity'];
                                        if ($type === 'OUT' || $type === 'SALE') {
                                            $quantity = -$quantity;
                                        }
                                        ?>
                                        <span class="fw-bold" style="color: <?= $quantity > 0 ? '#28a745' : '#dc3545' ?>;">
                                            <?= $quantity > 0 ? '+' : '' ?><?= $quantity ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= $movement['old_stock'] ?></td>
                                    <td class="fw-bold"><?= $movement['new_stock'] ?></td>
                                    <td>
                                        <small class="text-muted"><?= $movement['note'] ?: '-' ?></small>
                                    </td>
                                    <td>
                                        <small><i class="fas fa-user"></i> Sistem</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> <?= date('d.m.Y H:i', strtotime($movement['created_at'])) ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Düşük Stoklu Ürünler -->
        <div class="tab-pane fade" id="low-stock">
            <div class="modern-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ürün</th>
                            <th>Ürün Adı</th>
                            <th style="width: 150px;">Kategori</th>
                            <th style="width: 120px;">Barkod</th>
                            <th style="width: 100px;">Stok</th>
                            <th style="width: 120px;">Fiyat</th>
                            <th style="width: 120px;">Son 30 Gün</th>
                            <th style="width: 100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stock_products)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <h5>Düşük Stoklu Ürün Yok</h5>
                                        <p>Tüm ürünlerinizin stoğu yeterli seviyede</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($low_stock_products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="../<?= htmlspecialchars($product['image']) ?>" class="product-thumb" alt="">
                                        <?php else: ?>
                                            <div class="product-thumb" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?= mb_substr($product['name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($product['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($product['barcode']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-barcode"></i> <?= htmlspecialchars($product['barcode']) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stock-badge <?= $product['stock'] == 0 ? 'out' : 'low' ?>">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= number_format($product['price'], 2) ?> ₺</td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-chart-line"></i> <?= abs($product['total_movements']) ?> hareket
                                        </small>
                                    </td>
                                    <td>
                                        <button class="action-btn btn-primary" onclick="quickStockUpdate(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['stock'] ?>)" title="Stok Güncelle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Stokta Yok -->
        <div class="tab-pane fade" id="out-of-stock">
            <div class="modern-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ürün</th>
                            <th>Ürün Adı</th>
                            <th style="width: 150px;">Kategori</th>
                            <th style="width: 120px;">Barkod</th>
                            <th style="width: 120px;">Fiyat</th>
                            <th style="width: 100px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($out_of_stock)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <h5>Stokta Olmayan Ürün Yok</h5>
                                        <p>Tüm ürünleriniz stokta mevcut</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($out_of_stock as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="../<?= htmlspecialchars($product['image']) ?>" class="product-thumb" alt="">
                                        <?php else: ?>
                                            <div class="product-thumb" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?= mb_substr($product['name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($product['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($product['barcode']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-barcode"></i> <?= htmlspecialchars($product['barcode']) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= number_format($product['price'], 2) ?> ₺</td>
                                    <td>
                                        <button class="action-btn btn-success" onclick="quickStockUpdate(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['stock'] ?>)" title="Stok Ekle">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hızlı Stok Güncelleme Modal -->
<div class="modal fade" id="quickStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>
                    Hızlı Stok Güncelleme
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="quickProductId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Ürün</label>
                    <input type="text" class="form-control" id="quickProductName" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mevcut Stok</label>
                    <input type="number" class="form-control" id="quickCurrentStock" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">İşlem Türü</label>
                    <select class="form-select" id="quickOperation">
                        <option value="IN">Stok Girişi (+)</option>
                        <option value="OUT">Stok Çıkışı (-)</option>
                        <option value="ADJUSTMENT">Stok Düzeltme</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Miktar</label>
                    <input type="number" class="form-control" id="quickQuantity" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Not (Opsiyonel)</label>
                    <textarea class="form-control" id="quickNote" rows="2" placeholder="Stok hareketi hakkında not..."></textarea>
                </div>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Yeni Stok:</strong> <span id="previewNewStock" class="fw-bold">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>İptal
                </button>
                <button type="button" class="btn btn-primary" onclick="saveQuickStock()">
                    <i class="fas fa-save me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Yeni stok önizlemesi
function updateStockPreview() {
    const currentStock = parseInt($('#quickCurrentStock').val()) || 0;
    const operation = $('#quickOperation').val();
    const quantity = parseInt($('#quickQuantity').val()) || 0;
    
    let newStock = currentStock;
    
    if (operation === 'IN') {
        newStock = currentStock + quantity;
    } else if (operation === 'OUT') {
        newStock = currentStock - quantity;
    } else if (operation === 'ADJUSTMENT') {
        newStock = quantity;
    }
    
    $('#previewNewStock').text(newStock + ' adet');
    
    if (newStock < 0) {
        $('#previewNewStock').css('color', '#dc3545');
    } else if (newStock <= 10) {
        $('#previewNewStock').css('color', '#ffc107');
    } else {
        $('#previewNewStock').css('color', '#28a745');
    }
}

$('#quickOperation, #quickQuantity').on('change input', updateStockPreview);

// Hızlı stok güncelleme modalını aç
function quickStockUpdate(productId, productName, currentStock) {
    $('#quickProductId').val(productId);
    $('#quickProductName').val(productName);
    $('#quickCurrentStock').val(currentStock);
    $('#quickQuantity').val(1);
    $('#quickNote').val('');
    $('#quickOperation').val('IN');
    
    updateStockPreview();
    
    const modal = new bootstrap.Modal(document.getElementById('quickStockModal'));
    modal.show();
}

// Hızlı stok kaydet
function saveQuickStock() {
    const productId = $('#quickProductId').val();
    const currentStock = parseInt($('#quickCurrentStock').val());
    const operation = $('#quickOperation').val();
    const quantity = parseInt($('#quickQuantity').val());
    const note = $('#quickNote').val();
    
    if (!quantity || quantity < 1) {
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: 'Lütfen geçerli bir miktar girin'
        });
        return;
    }
    
    // Yeni stoğu hesapla
    let newStock = currentStock;
    let quantityChange = 0;
    
    if (operation === 'IN') {
        newStock = currentStock + quantity;
        quantityChange = quantity;
    } else if (operation === 'OUT') {
        newStock = currentStock - quantity;
        quantityChange = -quantity;
        
        if (newStock < 0) {
            Swal.fire({
                icon: 'error',
                title: 'Yetersiz Stok',
                text: 'Stok miktarı negatif olamaz'
            });
            return;
        }
    } else if (operation === 'ADJUSTMENT') {
        newStock = quantity;
        quantityChange = quantity - currentStock;
    }
    
    // AJAX ile kaydet
    $.ajax({
        url: 'ajax/update_stock.php',
        method: 'POST',
        data: {
            product_id: productId,
            old_stock: currentStock,
            new_stock: newStock,
            operation: operation,
            amount: Math.abs(quantityChange),
            note: note
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: 'Stok güncellendi',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: response.message || 'Stok güncellenemedi'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: 'Bir hata oluştu'
            });
        }
    });
}
</script>

</body>
</html>

