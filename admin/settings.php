
<?php
require_once '../includes/config.php';
$db = new Database();

if(isset($_POST['save_settings'])) {
    $settings = [
        'restaurant_name' => cleanInput($_POST['restaurant_name']),
        'theme_color' => $_POST['theme_color'],
        'currency' => $_POST['currency']
    ];
    
    // Logo yüklemesi
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logo = secureUpload($_FILES['logo']);
        $settings['logo'] = $logo;
    }

     // Header background için
     if(!empty($_POST['header_bg'])) {
        $settings['header_bg'] = $_POST['header_bg'];
    }

    // Debug için
    echo "<pre>";
    print_r($_POST);
    print_r($settings);
    echo "</pre>";
    
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

include 'navbar.php';
?>

<div class="main-content">
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
                       <label>Mevcut Logo</label>
                       <?php if(isset($settings['logo']) && $settings['logo']): ?>
                           <div class="mb-2">
                               <img src="../uploads/<?= $settings['logo'] ?>" style="max-height:100px">
                           </div>
                       <?php endif; ?>
                       <input type="file" name="logo" class="form-control">
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
</script>
<?php include '../includes/media-modal.php'; ?>
</body>
</html>