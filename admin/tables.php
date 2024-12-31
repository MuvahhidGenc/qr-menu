<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
checkAuth();
include 'navbar.php';

$db = new Database();
$tables = $db->query("SELECT * FROM tables ORDER BY table_no")->fetchAll();

// Kategorileri çek
$categoriesQuery = "SELECT * FROM categories WHERE status = 1 ORDER BY name";
$categories = $db->query($categoriesQuery)->fetchAll();

// Ürünleri çek
$productsQuery = "SELECT * FROM products WHERE status = 1 ORDER BY name";
$products = $db->query($productsQuery)->fetchAll();

// Debug bilgileri
error_log('Categories Query: ' . $categoriesQuery);
error_log('Products Query: ' . $productsQuery);

// Veritabanı durumunu kontrol et
$dbCheck = $db->query("SELECT 
    (SELECT COUNT(*) FROM categories WHERE status = 1) as active_categories,
    (SELECT COUNT(*) FROM products WHERE status = 1) as active_products
")->fetch();

error_log('Active Categories: ' . $dbCheck['active_categories']);
error_log('Active Products: ' . $dbCheck['active_products']);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masalar - QR Menü Admin</title>
    
    <!-- CSS Dosyaları -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    
    
</head>
<style>
.nav-link {
    display: -webkit-box !important;
    color: #ffff;
}</style>
<div class="category-content">
    <div class="container-fluid p-3">
        <!-- Başlık ve Yeni Masa Butonu -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-chair me-2"></i>Masalar
            </h2>
            <button type="button" class="btn btn-primary" onclick="showAddTableModal()">
                <i class="fas fa-plus"></i> Yeni Masa
            </button>
        </div>

        <!-- Masalar Grid -->
        <div class="row g-4">
            <?php foreach($tables as $table): 
                // Masa durumunu ve toplam tutarı çek
                $tableInfo = $db->query(
                    "SELECT 
                        CASE WHEN EXISTS (
                            SELECT 1 FROM orders 
                            WHERE table_id = ? AND payment_id IS NULL
                        ) THEN 'occupied' ELSE 'empty' END as status,
                        COALESCE((
                            SELECT SUM(total_amount) 
                            FROM orders 
                            WHERE table_id = ? AND payment_id IS NULL
                        ), 0) as total",
                    [$table['id'], $table['id']]
                )->fetch();
            ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 <?= $tableInfo['status'] === 'occupied' ? 'border-warning' : '' ?>">
                        <div class="card-header bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chair me-2"></i>Masa <?= $table['table_no'] ?>
                                </h5>
                                <span class="badge <?= $tableInfo['status'] === 'occupied' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= $tableInfo['status'] === 'occupied' ? 'Dolu' : 'Boş' ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <small class="text-muted">Kapasite:</small>
                                    <div><?= $table['capacity'] ?? 4 ?> Kişilik</div>
                                </div>
                                <?php if($tableInfo['status'] === 'occupied'): ?>
                                    <div class="text-end">
                                        <small class="text-muted">Toplam Tutar:</small>
                                        <div class="fw-bold text-warning"><?= number_format($tableInfo['total'], 2) ?> ₺</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="openPaymentModal(<?= $table['id'] ?>)">
                                    <i class="fas fa-cash-register me-2"></i>Satış Ekranı
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm" onclick="showQRCode(<?= $table['id'] ?>)">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="editTable(<?= $table['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteTable(<?= $table['id'] ?>)">
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

<!-- Satış Ekranı Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cash-register me-2"></i>
                    <span id="paymentTableInfo">Masa</span>
                    <span class="badge bg-light text-primary ms-2" id="tableStatus">-</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Sol Taraf: Kategoriler ve Ürünler -->
                    <div class="col-md-7 border-end">
                        <!-- Kategori Butonları -->
                        <div class="p-2 border-bottom bg-light">
                            <div class="d-flex gap-2 overflow-auto categories-wrapper">
                                <?php foreach($categories as $category): ?>
                                    <button type="button" 
                                            class="btn btn-outline-primary payment-category-btn flex-shrink-0" 
                                            data-category="<?= $category['id'] ?>">
                                        <?php if($category['image']): ?>
                                            <img src="../uploads/<?= $category['image'] ?>" 
                                                 class="category-img me-1" 
                                                 alt="<?= htmlspecialchars($category['name']) ?>">
                                        <?php endif; ?>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Ürün Listesi -->
                        <div class="products-wrapper" style="height: calc(100vh - 250px); overflow-y: auto;">
                            <?php foreach($categories as $category): ?>
                                <div class="category-products p-2" data-category="<?= $category['id'] ?>" style="display: none;">
                                    <div class="row g-2">
                                        <?php 
                                        $categoryProducts = array_filter($products, function($p) use ($category) {
                                            return $p['category_id'] == $category['id'];
                                        });
                                        
                                        foreach($categoryProducts as $product): 
                                        ?>
                                            <div class="col-lg-3 col-md-4 col-sm-6">
                                                <div class="card product-card h-100">
                                                    <?php if($product['image']): ?>
                                                        <img src="../uploads/<?= $product['image'] ?>" 
                                                             class="card-img-top product-img" 
                                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                                    <?php endif; ?>
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-2 text-truncate">
                                                            <?= htmlspecialchars($product['name']) ?>
                                                        </h6>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="text-primary fw-bold">
                                                                <?= number_format($product['price'], 2) ?> ₺
                                                            </span>
                                                            <button type="button" 
                                                                    class="btn btn-primary btn-sm"
                                                                    onclick="addToPaymentOrder(
                                                                        <?= $product['id'] ?>, 
                                                                        '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', 
                                                                        <?= $product['price'] ?>
                                                                    )">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sağ Taraf: Sipariş Detayları -->
                    <div class="col-md-5">
                        <div class="d-flex flex-column h-100">
                            <!-- Sipariş Başlığı -->
                            <div class="p-3 bg-light border-bottom">
                                <h6 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Sipariş Detayları
                                </h6>
                            </div>
                            
                            <!-- Siparişler -->
                            <div class="flex-grow-1 order-items-wrapper" style="height: calc(100vh - 400px); overflow-y: auto;">
                                <div id="paymentOrderDetails" class="p-3"></div>
                                <div id="newOrderItems" class="p-3 border-top"></div>
                            </div>

                            <!-- Ödeme Seçenekleri ve Toplam -->
                            <div class="border-top p-3">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">Toplam</h5>
                                        <h5 class="mb-0 text-primary" id="paymentTotal">0.00 ₺</h5>
                                    </div>
                                    
                                    <!-- Ödeme Yöntemi Seçimi -->
                                    <div class="payment-methods mb-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="radio" class="btn-check" name="payment_method" id="cash" value="cash" checked>
                                                <label class="btn btn-outline-success w-100" for="cash">
                                                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                                    <span class="d-block">Nakit</span>
                                                </label>
                                            </div>
                                            <div class="col-6">
                                                <input type="radio" class="btn-check" name="payment_method" id="pos" value="pos">
                                                <label class="btn btn-outline-success w-100" for="pos">
                                                    <i class="fas fa-credit-card fa-2x mb-2"></i>
                                                    <span class="d-block">POS</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-success" onclick="completePayment()">
                                            <i class="fas fa-check-circle me-2"></i>Ödemeyi Tamamla
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="saveNewItems()">
                                            <i class="fas fa-save me-2"></i>Siparişi Kaydet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Masa Ekleme Modalı -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Masa Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTableForm">
                    <div class="mb-3">
                        <label for="tableNo" class="form-label">Masa Numarası</label>
                        <input type="text" class="form-control" id="tableNo" name="table_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Kapasite</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" value="4" min="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="addTable()">Masa Ekle</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-xl {
    max-width: 1200px;
}

.category-img {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
}

.product-img {
    height: 120px;
    object-fit: cover;
}

.payment-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 4px;
}

.order-item {
    padding: 10px;
    border-radius: 6px;
    background: #f8f9fa;
    margin-bottom: 10px;
}

.order-item:hover {
    background: #f0f1f2;
}

.order-item .quantity-controls {
    visibility: hidden;
    opacity: 0;
    transition: all 0.2s;
}

.order-item:hover .quantity-controls {
    visibility: visible;
    opacity: 1;
}

/* Scrollbar Stilleri */
.products-wrapper::-webkit-scrollbar,
.order-items-wrapper::-webkit-scrollbar {
    width: 6px;
}

.products-wrapper::-webkit-scrollbar-track,
.order-items-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.products-wrapper::-webkit-scrollbar-thumb,
.order-items-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.products-wrapper::-webkit-scrollbar-thumb:hover,
.order-items-wrapper::-webkit-scrollbar-thumb:hover {
    background: #666;
}
</style>

<script>
// Global değişkenler
let paymentModal = null;
let paymentItems = {};
let currentTotal = 0;
let currentTableOrders = [];
let currentTableId = null;

// Fiyat formatlama fonksiyonu
function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ₺';
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Modal nesnesini başlat
    const modalElement = document.getElementById('paymentModal');
    if (modalElement) {
        paymentModal = new bootstrap.Modal(modalElement);
    }

    // Kategori butonlarına tıklama olayı
    document.querySelectorAll('.payment-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Aktif kategori butonunu güncelle
            document.querySelectorAll('.payment-category-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            // Ürünleri göster/gizle
            const selectedCategory = this.dataset.category;
            document.querySelectorAll('.category-products').forEach(group => {
                group.style.display = group.dataset.category === selectedCategory ? 'block' : 'none';
            });
        });
    });
});

// Satış ekranını aç
window.openPaymentModal = function(tableId) {
    currentTableId = parseInt(tableId);
    console.log('Opening modal for table:', currentTableId);

    // Masa bilgisini güncelle
    document.getElementById('paymentTableInfo').textContent = `Masa ${currentTableId}`;
    
    // Modal'ı aç
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
    
    // İlk kategoriyi seç
    const firstCategoryBtn = document.querySelector('.payment-category-btn');
    if (firstCategoryBtn) {
        selectCategory(firstCategoryBtn.dataset.category);
    }
    
    // Mevcut siparişleri yükle
    loadTableOrders(currentTableId);
}

// Ürün ekle
function addToPaymentOrder(productId, productName, productPrice) {
    console.log('Adding Product:', { productId, productName, productPrice });

    if (!paymentItems[productId]) {
        paymentItems[productId] = {
            product_id: productId,
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            quantity: 1
        };
    } else {
        paymentItems[productId].quantity++;
    }
    updateNewOrderItems();
}

// Ürün çıkar
function removeFromPaymentOrder(productId) {
    if (paymentItems[productId] && paymentItems[productId].quantity > 0) {
        paymentItems[productId].quantity--;
        if (paymentItems[productId].quantity === 0) {
            delete paymentItems[productId];
        }
        updateNewOrderItems();
    }
}

// Yeni siparişleri güncelle
function updateNewOrderItems() {
    const container = document.getElementById('newOrderItems');
    let html = '';
    let total = 0;

    for (const [productId, item] of Object.entries(paymentItems)) {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        html += `
            <div class="order-item mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${item.name}</div>
                        <div class="text-muted small">
                            ${item.quantity} x ${formatPrice(item.price)}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateNewItemQuantity('${productId}', -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateNewItemQuantity('${productId}', 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="removeNewItem('${productId}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <span class="text-primary fw-bold">
                            ${formatPrice(itemTotal)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    // Sipariş yoksa mesaj göster
    if (!html) {
        html = '<div class="text-center text-muted p-3">Henüz ürün eklenmedi</div>';
    }

    container.innerHTML = html;
    document.getElementById('paymentTotal').textContent = formatPrice(total);
    currentTotal = total;
}

// Masa siparişlerini yükle
function loadTableOrders(tableId) {
    fetch(`../ajax/get_table_orders.php?table_id=${tableId}`)
        .then(response => response.json())
        .then(orders => {
            const container = document.getElementById('paymentOrderDetails');
            let html = '';
            let total = 0;

            if (orders && orders.length > 0) {
                orders.forEach(order => {
                    if (order && order.items && order.items.length > 0) {
                        order.items.forEach(item => {
                            const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                            total += itemTotal;

                            html += `
                                <div class="order-item mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">${item.name}</div>
                                            <div class="text-muted small">
                                                ${item.quantity} x ${formatPrice(item.price)}
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="updateOrderQuantity(${order.id}, ${item.id}, -1)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="updateOrderQuantity(${order.id}, ${item.id}, 1)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="removeOrderItem(${order.id}, ${item.id})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <span class="text-primary fw-bold">
                                                ${formatPrice(itemTotal)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                });
            }

            // Sipariş yoksa mesaj göster
            if (!html) {
                html = '<div class="text-center text-muted p-3">Henüz sipariş bulunmuyor</div>';
            }

            container.innerHTML = html;
            document.getElementById('paymentTotal').textContent = formatPrice(total);
            currentTotal = total;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('paymentOrderDetails').innerHTML = 
                '<div class="alert alert-danger">Siparişler yüklenirken bir hata oluştu</div>';
        });
}

// Sipariş kaydedildikten sonra modalı güncelle
function updateModalAfterSave() {
    // Mevcut siparişleri yeniden yükle
    loadTableOrders(currentTableId);
    
    // Yeni sipariş listesini temizle
    paymentItems = {};
    updateNewOrderItems();
    
    // Başarı mesajı göster
    Swal.fire({
        icon: 'success',
        title: 'Başarılı!',
        text: 'Sipariş kaydedildi',
        showConfirmButton: false,
        timer: 1500
    });
}

// Siparişi kaydet
function saveNewItems() {
    if (Object.keys(paymentItems).length === 0) {
        Swal.fire('Uyarı!', 'Lütfen sipariş ekleyin', 'warning');
        return;
    }

    // Debug için
    console.log('Current Table ID:', currentTableId);
    console.log('Payment Items:', paymentItems);

    // Sipariş verilerini hazırla
    const items = Object.values(paymentItems).map(item => ({
        product_id: parseInt(item.product_id || item.id),
        quantity: parseInt(item.quantity)
    }));

    const orderData = {
        table_id: parseInt(currentTableId),
        items: items
    };

    // Debug için
    console.log('Sending Order Data:', orderData);

    // URL yolunu düzelttik
    fetch('../ajax/save_order.php', {  // URL yolunu düzelttik
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(async response => {
        const text = await response.text();
        console.log('Raw Response:', text);

        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server yanıtı JSON formatında değil: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            updateModalAfterSave();  // Yeni fonksiyonu çağır
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Hata!', error.message, 'error');
    });
}

// Ödemeyi tamamla
function completePayment() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    if (currentTotal <= 0) {
        Swal.fire('Uyarı!', 'Ödenecek tutar bulunamadı', 'warning');
        return;
    }

    Swal.fire({
        title: 'Ödeme Onayı',
        text: `Toplam ${currentTotal.toFixed(2)} ₺ tutarındaki ödemeyi ${paymentMethod === 'cash' ? 'nakit' : 'POS'} ile tamamlamak istiyor musunuz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Tamamla',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../ajax/complete_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    table_id: currentTableId,
                    payment_method: paymentMethod,
                    total_amount: currentTotal
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Ödeme Tamamlandı!',
                        text: 'Ödeme başarıyla kaydedildi',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#paymentModal').modal('hide');
                        location.reload();
                    });
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

// Sipariş öğesi HTML'i
function getOrderItemHtml(order, item) {
    return `
        <div class="order-item" data-order-id="${order.id}" data-item-id="${item.id}">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${item.name}</div>
                    <div class="text-muted small">
                        <span class="item-quantity">${item.quantity}</span> x 
                        ${parseFloat(item.price).toFixed(2)} ₺
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="quantity-controls btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="updateOrderQuantity(${order.id}, ${item.id}, -1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="updateOrderQuantity(${order.id}, ${item.id}, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="removeOrderItem(${order.id}, ${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <span class="item-price text-primary fw-bold">
                        ${(item.quantity * item.price).toFixed(2)} ₺
                    </span>
                </div>
            </div>
        </div>
    `;
}

// Sipariş öğesi sil
function removeOrderItem(orderId, itemId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu ürünü sipariş listesinden silmek istediğinize emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../ajax/remove_order_item.php', {  // URL yolunu düzelttik
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // UI'ı güncelle
                    loadTableOrders(currentTableId);
                    
                    // Başarı mesajı göster
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Eğer son ürün silindiyse modalı kapat
                    if (data.remaining_count === 0) {
                        $('#paymentModal').modal('hide');
                        location.reload(); // Masa durumunu güncellemek için
                    }
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Kategori seçimi
function selectCategory(categoryId) {
    // Tüm kategori butonlarını pasif yap
    document.querySelectorAll('.payment-category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Seçili kategoriyi aktif yap
    const selectedBtn = document.querySelector(`.payment-category-btn[data-category="${categoryId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }
    
    // Tüm ürün listelerini gizle
    document.querySelectorAll('.category-products').forEach(div => {
        div.style.display = 'none';
    });
    
    // Seçili kategorinin ürünlerini göster
    const productDiv = document.querySelector(`.category-products[data-category="${categoryId}"]`);
    if (productDiv) {
        productDiv.style.display = 'block';
    }
}

// Mevcut sipariş miktarını güncelle
function updateOrderQuantity(orderId, itemId, change) {
    console.log('Updating order quantity:', { orderId, itemId, change }); // Debug için

    fetch('../ajax/update_order_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            item_id: itemId,
            change: change
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Siparişleri yeniden yükle
            loadTableOrders(currentTableId);
        } else {
            throw new Error(data.message || 'Miktar güncellenirken bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Hata!', error.message, 'error');
    });
}

// Yeni sipariş miktarını güncelle
function updateNewItemQuantity(productId, change) {
    console.log('Updating new item quantity:', { productId, change }); // Debug için

    if (paymentItems[productId]) {
        const newQuantity = Math.max(1, paymentItems[productId].quantity + change);
        
        if (newQuantity === 0) {
            // Miktar 0 ise ürünü sil
            delete paymentItems[productId];
        } else {
            paymentItems[productId].quantity = newQuantity;
        }
        
        updateNewOrderItems();
    }
}

// Yeni ürünü sil
function removeNewItem(productId) {
    console.log('Removing new item:', productId); // Debug için

    if (paymentItems[productId]) {
        delete paymentItems[productId];
        updateNewOrderItems();
    }
}

// Yeni masa modalını göster
function showAddTableModal() {
    const modalElement = document.getElementById('addTableModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Yeni masa kaydet
function saveTable() {
    const tableNumber = document.getElementById('tableNumber').value;
    
    if (!tableNumber) {
        Swal.fire('Hata!', 'Masa numarası giriniz', 'error');
        return;
    }

    fetch('api/add_table.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            table_number: tableNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            Swal.fire('Başarılı!', 'Masa eklendi', 'success')
            .then(() => location.reload());
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', error.message, 'error');
    });
}

// QR kod oluştur
function showQRCode(tableId) {
    if (typeof qrcode === 'undefined') {
        console.error('QR Code kütüphanesi yüklenemedi!');
        return;
    }
    
    const url = `${window.location.origin}/menu.php?table=${tableId}`;
    const qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();
    
    Swal.fire({
        title: 'Masa QR Kodu',
        html: `
            <div class="text-center">
                ${qr.createImgTag(5)}
                <div class="mt-3">
                    <button class="btn btn-primary me-2" onclick="downloadQR('png', '${tableId}')">
                        <i class="fas fa-download"></i> PNG İndir
                    </button>
                    <button class="btn btn-success" onclick="downloadQR('svg', '${tableId}')">
                        <i class="fas fa-download"></i> SVG İndir
                    </button>
                </div>
            </div>
        `,
        showConfirmButton: false,
        width: 400
    });
}

// QR kodu indir
function downloadQR(format, tableId) {
    const url = `${window.location.origin}/menu.php?table=${tableId}`;
    const qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();

    if (format === 'svg') {
        // SVG indir
        // SVG indir
const svgString = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' + qr.createSvgTag(5, 0);
        
        const blob = new Blob([svgString], { type: 'image/svg+xml' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `masa_${tableId}_qr.svg`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } else {
        // PNG indir
        const canvas = document.createElement('canvas');
        const qrImage = new Image();
        // SVG indir
const svgString = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' + qr.createSvgTag(5, 0);
            
        
        qrImage.src = 'data:image/svg+xml;base64,' + btoa(svgString);
        qrImage.onload = function() {
            canvas.width = qrImage.width;
            canvas.height = qrImage.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(qrImage, 0, 0);
            
            canvas.toBlob(function(blob) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `masa_${tableId}_qr.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 'image/png');
        };
    }
}

// Masa düzenle
function editTable(tableId) {
    Swal.fire({
        title: 'Masa Numarası',
        input: 'number',
        inputLabel: 'Yeni masa numarasını giriniz',
        showCancelButton: true,
        confirmButtonText: 'Güncelle',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            fetch('api/update_table.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: tableId,
                    table_number: result.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Başarılı!', 'Masa güncellendi', 'success')
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

// Masa sil
function deleteTable(tableId) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu masa silinecek!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/delete_table.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: tableId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Silindi!', 'Masa silindi', 'success')
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

<!-- QR Code kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

<!-- JavaScript Dosyaları -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>

<!-- Bootstrap ve diğer gerekli scriptler -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/tables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>

</body>
</html>

