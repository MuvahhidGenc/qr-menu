<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Mevcut ayarları getir - Bu kısmı değiştirin
$printerSettings = [];
$settingsQuery = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'printer_%'");
$results = $settingsQuery->fetchAll(PDO::FETCH_ASSOC);

// Sonuçları düzenle
foreach ($results as $row) {
    $printerSettings[$row['setting_key']] = $row['setting_value'];
}

// Ayarları kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'printer_') === 0) {
                $db->query(
                    "INSERT INTO settings (setting_key, setting_value) 
                     VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?",
                    [$key, $value, $value]
                );
            }
        }
        $_SESSION['success'] = 'Yazıcı ayarları başarıyla güncellendi.';
        header('Location: printer_settings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Ayarlar kaydedilirken bir hata oluştu.';
    }
}

require_once 'navbar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Yazıcı Ayarları</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Genel Yazıcı Ayarları -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="mb-3">Genel Ayarlar</h6>
                                <div class="mb-3">
                                    <label class="form-label">Varsayılan Yazıcı</label>
                                    <select name="printer_default" class="form-select">
                                        <option value="">Yazıcı Seçin</option>
                                        <?php 
                                        $printers = getPrinterList();
                                        if (empty($printers)) {
                                            echo '<option value="" disabled>Yazıcı bulunamadı</option>';
                                        } else {
                                            foreach ($printers as $printer): 
                                        ?>
                                            <option value="<?= htmlspecialchars($printer) ?>" 
                                                    <?= ($printerSettings['printer_default'] ?? '') == $printer ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($printer) ?>
                                            </option>
                                        <?php 
                                            endforeach;
                                        }
                                        ?>
                                    </select>
                                    <?php if (empty($printers)): ?>
                                    <div class="alert alert-warning mt-2">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Sistemde kurulu yazıcı bulunamadı. Lütfen bir yazıcı kurulu olduğundan emin olun.
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kağıt Genişliği (mm)</label>
                                    <input type="number" name="printer_paper_width" class="form-control" 
                                           value="<?= $printerSettings['printer_paper_width'] ?? '80' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Fiş Ayarları</h6>
                                <div class="mb-3">
                                    <label class="form-label">Fiş Başlığı</label>
                                    <input type="text" name="printer_header" class="form-control" 
                                           value="<?= $printerSettings['printer_header'] ?? '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fiş Altı Notu</label>
                                    <textarea name="printer_footer" class="form-control" rows="2"><?= $printerSettings['printer_footer'] ?? '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Yazdırma Seçenekleri -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="mb-3">Yazdırma Seçenekleri</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input type="checkbox" class="form-check-input" name="printer_auto_cut" 
                                                   value="1" <?= ($printerSettings['printer_auto_cut'] ?? '') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label">Otomatik Kağıt Kesme</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input type="checkbox" class="form-check-input" name="printer_open_drawer" 
                                                   value="1" <?= ($printerSettings['printer_open_drawer'] ?? '') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label">Çekmece Açma</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch mb-3">
                                            <input type="checkbox" class="form-check-input" name="printer_logo_enabled" 
                                                   value="1" <?= ($printerSettings['printer_logo_enabled'] ?? '') == '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label">Logo Yazdırma</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Test Yazdırma -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="mb-3">Test Yazdırma</h6>
                                <button type="button" class="btn btn-info" id="testPrint">
                                    <i class="fas fa-print"></i> Test Fişi Yazdır
                                </button>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Test yazdırma
    $('#testPrint').on('click', function() {
        $.ajax({
            url: 'ajax/test_print.php',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Test fişi yazdırıldı.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error || 'Yazdırma sırasında bir hata oluştu!'
                    });
                }
            }
        });
    });
});
</script>

<?php
// Sistemdeki yazıcıları listele
function getPrinterList() {
    if (PHP_OS === 'WINNT') {
        $printers = [];
        
        // PowerShell kullanarak yazıcıları listele
        $cmd = 'powershell.exe -Command "Get-Printer | Select-Object Name | Format-Table -HideTableHeaders"';
        $output = shell_exec($cmd);
        
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                $printer = trim($line);
                if (!empty($printer)) {
                    $printers[] = $printer;
                }
            }
        }
        
        // Eğer PowerShell çalışmazsa, WMIC kullan
        if (empty($printers)) {
            $output = shell_exec('wmic printer get name');
            if ($output) {
                $lines = explode("\n", trim($output));
                // İlk satırı (başlık) atla
                array_shift($lines);
                foreach ($lines as $line) {
                    $printer = trim($line);
                    if (!empty($printer)) {
                        $printers[] = $printer;
                    }
                }
            }
        }
        
        return array_unique($printers);
    } else {
        // Linux için
        $output = [];
        exec('lpstat -a | cut -d " " -f1', $output);
        return array_filter($output);
    }
}

// Debug bilgisi ekle
if (isset($_GET['debug'])) {
    echo '<pre>';
    echo "İşletim Sistemi: " . PHP_OS . "\n";
    echo "Bulunan Yazıcılar:\n";
    print_r(getPrinterList());
    echo '</pre>';
}

// Hata mesajı göster
/*if (!extension_loaded('com_dotnet')): ?>
<div class="alert alert-warning mt-2">
    <i class="fas fa-exclamation-triangle"></i> 
    PHP COM uzantısı yüklü değil. Yazıcı listesi için bu uzantının yüklenmesi gerekiyor.
    <br>
    <small>php.ini dosyasında extension=com_dotnet satırının aktif olduğundan emin olun.</small>
</div>
<?php endif; ?>*/
?> 