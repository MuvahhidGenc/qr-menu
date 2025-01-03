<?php
require_once '../includes/config.php';
$db = new Database();

if(isset($_POST['save_settings'])) {
    $settings = [
        'restaurant_name' => cleanInput($_POST['restaurant_name']),
        'logo' => $_POST['logo'] ?? $settings['logo'] ?? '', // Logo için hidden input'tan al
        'header_bg' => $_POST['header_bg'] ?? $settings['header_bg'] ?? '', // Header bg için hidden input'tan al
        'theme_color' => $_POST['theme_color'],
        'currency' => $_POST['currency']
    ];
    
    foreach($settings as $key => $value) {
        $db->query("INSERT INTO settings (setting_key, setting_value) 
                   VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", 
                   [$key, $value, $value]);
    }
 
    
    $_SESSION['message'] = 'Ayarlar kaydedildi.';
    $_SESSION['message_type'] = 'success';
    header('Location: settings.php');
    exit;
}

$result = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$settings = array();
foreach($result as $row) {
   $settings[$row['setting_key']] = $row['setting_value'];
}

// Ayarları getir
$settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch();

// Varsayılan değerleri ayarla
$settings = array_merge([
    'order_code_required' => 0,
    'order_code_length' => '4'
], $settings ?: []);

include 'navbar.php';
?>

<style>
/* Ana Container */
.settings-container {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* Kartlar */
.card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    background: white;
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(45deg, #2c3e50, #3498db);
    color: white;
    padding: 1.5rem;
    border: none;
}

.card-header h5 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.card-body {
    padding: 2rem;
}

/* Form Elemanları */
.form-control {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    background: white;
}

/* Renk Seçici */
.form-control-color {
    width: 60px;
    height: 40px;
    padding: 0.3rem;
    border-radius: 10px;
    cursor: pointer;
}

/* Resim Önizleme */
.img-thumbnail {
    border-radius: 12px;
    border: 2px solid #e0e0e0;
    padding: 0.5rem;
    transition: all 0.3s ease;
}

.img-thumbnail:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Dosya Seçme Butonu */
.btn-file-select {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 0.8rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-file-select:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

/* QR Kod Kartı */
.qr-card {
    text-align: center;
}

.qr-card img {
    max-width: 200px;
    margin: 1.5rem 0;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.qr-card img:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* İndirme Butonları */
.btn-download {
    width: 100%;
    padding: 0.8rem;
    margin-bottom: 0.5rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-download.primary {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    border: none;
}

.btn-download.outline {
    background: white;
    border: 2px solid #3498db;
    color: #3498db;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Para Birimi Seçici */
.currency-select {
    background-image: url('data:image/svg+xml,...');
    background-position: right 1rem center;
    background-repeat: no-repeat;
    background-size: 1em;
}

/* Kaydet Butonu */
.btn-save {
    background: linear-gradient(45deg, #2ecc71, #27ae60);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
}

/* Responsive Düzenlemeler */
@media (max-width: 768px) {
    .settings-container {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .btn-save {
        width: 100%;
        padding: 0.8rem;
    }
}

/* Animasyonlar */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}
</style>

<div class="row">
   <div class="col-md-8">
       <div class="card">
           <div class="card-header">
               <h5 class="mb-0">Site Ayarları</h5>
           </div>
           <div class="card-body">
               <form method="POST" enctype="multipart/form-data">
                   <div class="mb-3">
                       <label>Restaurant Adı</label>
                       <input type="text" name="restaurant_name" 
                              value="<?= $settings['restaurant_name'] ?? '' ?>" 
                              class="form-control">
                   </div>
                   
                   <div class="mb-3">
                        <label>Logo</label>
                        <div class="mb-2">
                            <img id="logoPreview" src="<?= !empty($settings['logo']) ? '../uploads/'.$settings['logo'] : '' ?>" 
                                    style="max-height:100px;<?= empty($settings['logo']) ? 'display:none' : '' ?>" class="img-thumbnail">
                        </div>
                        <div class="input-group">
                            <input type="hidden" id="logo" name="logo" value="<?= $settings['logo'] ?? '' ?>">
                            <input type="text" class="form-control" id="logoDisplay" 
                                    value="<?= $settings['logo'] ?? '' ?>" readonly>
                            <button type="button" class="btn btn-primary" onclick="openMediaModal('logo')">
                                <i class="fas fa-image"></i> Dosya Seç
                            </button>
                        </div>
                    </div>
                   <div class="mb-3">
                        <label>Header Arkaplan Resmi</label>
                        <div class="mb-2">
                            <img id="headerBgPreview" src="<?= !empty($settings['header_bg']) ? '../uploads/'.$settings['header_bg'] : '' ?>" 
                                style="max-height:100px;<?= empty($settings['header_bg']) ? 'display:none' : '' ?>" class="img-thumbnail">
                        </div>
                        <div class="input-group">
                            <input type="hidden" id="headerBg" name="header_bg" value="<?= $settings['header_bg'] ?? '' ?>">
                            <input type="text" class="form-control" id="headerBgDisplay" 
                                value="<?= $settings['header_bg'] ?? '' ?>" readonly>
                            <button type="button" class="btn btn-primary" onclick="openMediaModal('headerBg')">
                                <i class="fas fa-image"></i> Dosya Seç
                            </button>
                        </div>
                    </div>
                   <div class="mb-3">
                        <label>Tema Rengi</label>
                        <div class="d-flex align-items-center">
                            <input type="color" name="theme_color" 
                                value="<?= $settings['theme_color'] ?? '#e74c3c' ?>" 
                                class="form-control form-control-color me-2"
                                onchange="updateColorPreview(this.value)">
                            <span id="colorPreview" class="text-muted">
                                Seçilen renk: <?= $settings['theme_color'] ?? '#e74c3c' ?>
                            </span>
                        </div>
                        <small class="text-muted">Bu renk sitenin genel tema rengi olarak kullanılacaktır.</small>
                    </div>
                   
                   <div class="mb-3">
                       <label>Para Birimi</label>
                       <select name="currency" class="form-control">
                           <option value="TL" <?= ($settings['currency'] ?? '') == 'TL' ? 'selected' : '' ?>>TL</option>
                           <option value="USD" <?= ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD</option>
                           <option value="EUR" <?= ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR</option>
                       </select>
                   </div>
                   
                   <button type="submit" name="save_settings" class="btn btn-primary">
                       <i class="fas fa-save"></i> Kaydet
                   </button>
               </form>
           </div>
       </div>
   </div>
   
   <div class="col-md-4">
       <div class="card">
           <div class="card-header">
               <h5 class="mb-0">QR Kod</h5>
           </div>
           <div class="card-body text-center">
               <img src="generate_qr.php" class="img-fluid mb-3">
               <div class="d-grid gap-2">
                   <a href="generate_qr.php?download=1" class="btn btn-primary">
                       <i class="fas fa-download"></i> PNG İndir
                   </a>
                   <a href="generate_qr.php?download=1&type=svg" class="btn btn-outline-primary">
                       <i class="fas fa-download"></i> SVG İndir
                   </a>
               </div>
           </div>
       </div>
   </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function updateColorPreview(color) {
    document.getElementById('colorPreview').textContent = 'Seçilen renk: ' + color;
}
document.querySelector('input[name="theme_color"]').addEventListener('input', function(e) {
   document.querySelector('.text-muted').textContent = 'Seçilen renk: ' + e.target.value;
});

document.getElementById('orderCodeSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        order_code_required: document.getElementById('orderCodeRequired').checked ? 1 : 0,
        order_code_length: document.getElementById('orderCodeLength').value
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
</script>
<?php include '../includes/media-modal.php'; ?>
</body>
</html>