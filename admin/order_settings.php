<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolleri
$canViewSettings = hasPermission('order_settings.view');
$canEditSettings = hasPermission('order_settings.edit');
$canManagePayments = hasPermission('order_settings.payment_methods');
$canManageDiscounts = hasPermission('order_settings.discount_rules');
$canManageTaxes = hasPermission('order_settings.tax_settings');

// Sayfa erişim kontrolü
if (!$canViewSettings) {
    header('Location: dashboard.php');
    exit();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Ayarları getir, yoksa varsayılan değerleri kullan
$settings = $db->query("SELECT * FROM order_settings ORDER BY id DESC LIMIT 1")->fetch();

// Varsayılan değerleri ayarla
$settings = array_merge([
    'code_required' => 0,
    'code_length' => '4'
], $settings ?: []);

// Debug için
error_log('Settings loaded: ' . print_r($settings, true));

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Ayarları - QR Menü Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .nav-link {
            display: -webkit-box !important;
        }

        /* Ana konteyner stilleri */
        .settings-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Kart stilleri */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: #fff;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        /* Kart başlık stilleri */
        .card-header {
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .card-header h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .settings-icon {
            font-size: 1.8rem;
            margin-right: 1rem;
            color: #3498db;
            transition: all 0.3s ease;
        }

        .card:hover .settings-icon {
            transform: rotate(15deg);
        }

        /* Kart içerik stilleri */
        .card-body {
            padding: 2rem;
        }

        /* Form elemanları */
        .form-switch {
            padding-left: 3rem;
        }

        .form-switch .form-check-input {
            width: 3.5em;
            height: 1.75em;
            margin-left: -3rem;
            background-color: #e9ecef;
            border-color: #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-switch .form-check-input:checked {
            background-color: #2ecc71;
            border-color: #27ae60;
        }

        .form-check-label {
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
        }

        .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border-color: #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.1);
        }

        /* Bilgi metinleri */
        .text-muted {
            color: #7f8c8d !important;
        }

        .text-muted i {
            color: #95a5a6;
        }

        /* Kod gösterim alanı */
        .display-4 {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2c3e50;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 0.1em;
        }

        /* Buton stilleri */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #2980b9, #2573a7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #3498db;
            color: #3498db;
        }

        .btn-outline-primary:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }

        .btn-outline-success {
            border: 2px solid #2ecc71;
            color: #2ecc71;
        }

        .btn-outline-success:hover {
            background: #2ecc71;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2);
        }

        .btn-outline-dark {
            border: 2px solid #34495e;
            color: #34495e;
        }

        .btn-outline-dark:hover {
            background: #34495e;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 73, 94, 0.2);
        }

        /* Animasyonlar */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeIn 0.5s ease-out;
        }

        /* Responsive düzenlemeler */
        @media (max-width: 768px) {
            .settings-container {
                margin: 1rem auto;
            }

            .card-header {
                padding: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .display-4 {
                font-size: 2.5rem;
            }

            .settings-icon {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container settings-container">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-cog settings-icon"></i>
                    <h5 class="mb-0">Sipariş Kodu Ayarları</h5>
                </div>
                <div class="card-body">
                    <form id="orderCodeSettingsForm">
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="codeRequired" 
                                       name="code_required" <?php echo $settings['code_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="codeRequired">
                                    Sipariş Kodu Zorunluluğu
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Müşterilerin sipariş vermeden önce kod girmesi gerekir
                            </small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-hashtag me-1"></i>
                                Kod Uzunluğu
                            </label>
                            <select class="form-select" name="code_length" id="codeLength">
                                <option value="4" <?php echo $settings['code_length'] == '4' ? 'selected' : ''; ?>>4 Haneli</option>
                                <option value="6" <?php echo $settings['code_length'] == '6' ? 'selected' : ''; ?>>6 Haneli</option>
                            </select>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Üretilecek sipariş kodlarının uzunluğunu belirler
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Ayarları Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-key settings-icon"></i>
                    <h5 class="mb-0">Sipariş Kodu Üretme</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div id="currentCode" class="text-center mb-3 mb-md-0">
                                <?php
                                $currentCode = $db->query("SELECT code, expires_at FROM order_codes WHERE active = 1 ORDER BY id DESC LIMIT 1")->fetch();
                                if ($currentCode): 
                                ?>
                                    <h4 class="mb-2">Mevcut Kod</h4>
                                    <div class="display-4 mb-2"><?php echo $currentCode['code']; ?></div>
                                    <small class="text-muted">
                                        Geçerlilik: <?php echo date('d.m.Y H:i', strtotime($currentCode['expires_at'])); ?>
                                    </small>
                                    
                                    <!-- Yazdırma butonları -->
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-outline-primary me-2" onclick="printCodes('pdf')">
                                            <i class="fas fa-file-pdf me-2"></i>PDF İndir
                                        </button>
                                        <button type="button" class="btn btn-outline-success me-2" onclick="printCodes('excel')">
                                            <i class="fas fa-file-excel me-2"></i>Excel İndir
                                        </button>
                                        <button type="button" class="btn btn-outline-dark" onclick="printDirectly()">
                                            <i class="fas fa-print me-2"></i>Yazdır
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Aktif kod bulunmuyor</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <button type="button" class="btn btn-primary btn-lg" onclick="generateNewCode()">
                                <i class="fas fa-sync-alt me-2"></i>
                                Yeni Kod Üret
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// PHP'den yetki değişkenlerini JS'e aktar
const permissions = {
    canView: <?php echo $canViewSettings ? 'true' : 'false' ?>,
    canEdit: <?php echo $canEditSettings ? 'true' : 'false' ?>,
    canManagePayments: <?php echo $canManagePayments ? 'true' : 'false' ?>,
    canManageDiscounts: <?php echo $canManageDiscounts ? 'true' : 'false' ?>,
    canManageTaxes: <?php echo $canManageTaxes ? 'true' : 'false' ?>
};

// Form gönderimi için yetki kontrolü
document.getElementById('orderCodeSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!permissions.canEdit) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Hatası',
            text: 'Ayarları düzenleme yetkiniz bulunmamaktadır!',
            confirmButtonText: 'Tamam'
        });
        return;
    }

    const formData = {
        code_required: document.getElementById('codeRequired').checked ? 1 : 0,
        code_length: document.getElementById('codeLength').value
    };
    
    fetch('ajax/save_settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Ayarlar kaydedildi',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            throw new Error(data.message || 'Bir hata oluştu');
        }
    })
    .catch(error => {
        Swal.fire('Hata!', error.message, 'error');
    });
});

// Yeni kod üretme fonksiyonu için yetki kontrolü
function generateNewCode() {
    if (!permissions.canEdit) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Hatası',
            text: 'Yeni kod üretme yetkiniz bulunmamaktadır!',
            confirmButtonText: 'Tamam'
        });
        return;
    }

    Swal.fire({
        title: 'Yeni Kod Üretilecek',
        text: 'Mevcut kod varsa pasif hale getirilecektir. Devam etmek istiyor musunuz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Üret',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/generate_order_code.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Kod alanını güncelle
                    document.getElementById('currentCode').innerHTML = `
                        <h4 class="mb-2">Mevcut Kod</h4>
                        <div class="display-4 mb-2">${data.code}</div>
                        <small class="text-muted">
                            Geçerlilik: ${data.expires_at}
                        </small>
                    `;

                    Swal.fire({
                        title: 'Kod Üretildi!',
                        html: `
                            <div class="text-center">
                                <h3 class="mb-4">${data.code}</h3>
                                <p class="text-muted">Bu kod 24 saat geçerlidir</p>
                            </div>
                        `,
                        icon: 'success'
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire('Hata!', error.message, 'error');
            });
        }
    });
}

// Yazdırma fonksiyonu için yetki kontrolü
function printCodes(format) {
    if (!permissions.canView) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Hatası',
            text: 'Kodları görüntüleme yetkiniz bulunmamaktadır!',
            confirmButtonText: 'Tamam'
        });
        return;
    }

    // Aktif kod kontrolü
    const currentCode = document.querySelector('.display-4');
    if (!currentCode) {
        Swal.fire('Hata!', 'Aktif kod bulunamadı', 'error');
        return;
    }

    // Yazdırma işlemi başladı bildirimi
    Swal.fire({
        title: 'Hazırlanıyor...',
        text: format.toUpperCase() + ' dosyası oluşturuluyor',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Dosyayı indir
    fetch(`ajax/print_codes.php?format=${format}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Dosya oluşturulamadı');
            }
            return response.blob();
        })
        .then(blob => {
            // Dosyayı indir
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `siparis_kodlari.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // Başarılı bildirimi
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Dosya indiriliyor...',
                showConfirmButton: false,
                timer: 1500
            });
        })
        .catch(error => {
            console.error('Print error:', error);
            Swal.fire('Hata!', error.message, 'error');
        });
}

// Doğrudan yazdırma fonksiyonu için yetki kontrolü
function printDirectly() {
    if (!permissions.canView) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Hatası',
            text: 'Kodları görüntüleme yetkiniz bulunmamaktadır!',
            confirmButtonText: 'Tamam'
        });
        return;
    }

    const currentCode = document.querySelector('.display-4');
    if (!currentCode) {
        Swal.fire('Hata!', 'Aktif kod bulunamadı', 'error');
        return;
    }

    // Geçerlilik tarihini doğru şekilde al
    const expiresText = document.querySelector('#currentCode .text-muted').textContent;

    // Önce masa sayısını al
    fetch('ajax/get_table_count.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message);
            }

            const tableCount = data.count;
            if (tableCount === 0) {
                throw new Error('Sistemde kayıtlı masa bulunamadı');
            }

            // Yazdırma penceresi içeriğini oluştur
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Sipariş Kodları</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            padding: 20px;
                        }
                        .code-container {
                            page-break-inside: avoid;
                            margin-bottom: 30px;
                            text-align: center;
                            padding: 20px;
                            border: 2px solid #ddd;
                            border-radius: 8px;
                            background: #f9f9f9;
                        }
                        .code {
                            font-size: 36px;
                            font-weight: bold;
                            color: #333;
                            margin: 10px 0;
                        }
                        .table-no {
                            font-size: 18px;
                            color: #666;
                            margin-bottom: 5px;
                        }
                        .expires {
                            font-size: 14px;
                            color: #888;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                        }
                        .header h1 {
                            color: #333;
                            margin-bottom: 10px;
                        }
                        @media print {
                            .code-container {
                                break-inside: avoid;
                            }
                            @page {
                                margin: 0.5cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Sipariş Kodları</h1>
                        <p>${expiresText}</p>
                        <p>Toplam Masa: ${tableCount}</p>
                    </div>
            `);

            // Her masa için kod kartı oluştur
            for (let i = 1; i <= tableCount; i++) {
                printWindow.document.write(`
                    <div class="code-container">
                        <div class="table-no">Masa ${i}</div>
                        <div class="code">${currentCode.textContent}</div>
                        <div class="expires">
                            ${expiresText}
                        </div>
                    </div>
                `);
            }

            printWindow.document.write(`
                </body>
                </html>
            `);

            printWindow.document.close();

            // Yazdırma işlemini başlat
            printWindow.onload = function() {
                printWindow.print();
                // Yazdırma tamamlandıktan sonra pencereyi kapat
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            };
        })
        .catch(error => {
            Swal.fire('Hata!', error.message, 'error');
        });
}

// Sayfa yüklendiğinde form elemanlarını yetkilere göre devre dışı bırak
document.addEventListener('DOMContentLoaded', function() {
    if (!permissions.canEdit) {
        // Form elemanlarını devre dışı bırak
        document.getElementById('codeRequired').disabled = true;
        document.getElementById('codeLength').disabled = true;
        
        // Kaydet butonunu gizle
        document.querySelector('#orderCodeSettingsForm button[type="submit"]').style.display = 'none';
        
        // Yeni kod üretme butonunu gizle
        document.querySelector('button[onclick="generateNewCode()"]').style.display = 'none';
    }

    if (!permissions.canView) {
        // Yazdırma butonlarını gizle
        document.querySelectorAll('button[onclick^="printCodes"]').forEach(btn => btn.style.display = 'none');
        document.querySelector('button[onclick="printDirectly()"]').style.display = 'none';
    }
});
</script>

</body>
</html> 