<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('settings.view')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();

// Tema ekleme/güncelleme işlemi
if(isset($_POST['save_theme'])) {
    $theme_data = [
        'name' => cleanInput($_POST['name']),
        'concept' => cleanInput($_POST['concept']),
        'primary_color' => $_POST['primary_color'],
        'secondary_color' => $_POST['secondary_color'],
        'accent_color' => $_POST['accent_color'],
        'background_color' => $_POST['background_color'],
        'text_color' => $_POST['text_color'],
        'font_family' => $_POST['font_family'],
        'category_style' => $_POST['category_style'],
        'product_layout' => $_POST['product_layout'],
        'header_style' => $_POST['header_style'],
        'description' => cleanInput($_POST['description'])
    ];
    
    if(isset($_POST['theme_id']) && !empty($_POST['theme_id'])) {
        // Güncelleme
        $db->query("UPDATE customer_themes SET 
                   name = ?, concept = ?, primary_color = ?, secondary_color = ?, 
                   accent_color = ?, background_color = ?, text_color = ?, 
                   font_family = ?, category_style = ?, product_layout = ?, header_style = ?, description = ?
                   WHERE id = ?", 
                   array_merge(array_values($theme_data), [$_POST['theme_id']]));
        $message = 'Tema güncellendi.';
    } else {
        // Yeni ekleme
        $db->query("INSERT INTO customer_themes 
                   (name, concept, primary_color, secondary_color, accent_color, 
                    background_color, text_color, font_family, category_style, 
                    product_layout, header_style, description, is_active) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)", 
                   array_values($theme_data));
        $message = 'Yeni tema eklendi.';
    }
    
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = 'success';
    header('Location: themes.php');
    exit;
}

// Tema silme
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $db->query("DELETE FROM customer_themes WHERE id = ?", [$_GET['delete']]);
    $_SESSION['message'] = 'Tema silindi.';
    $_SESSION['message_type'] = 'success';
    header('Location: themes.php');
    exit;
}

// Tema aktivasyon
if(isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    // Önce tüm temaları pasif yap
    $db->query("UPDATE customer_themes SET is_active = 0");
    // Seçilen temayı aktif yap
    $db->query("UPDATE customer_themes SET is_active = 1 WHERE id = ?", [$_GET['activate']]);
    
    // Aktif temanın renklerini settings tablosuna kopyala
    $active_theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$_GET['activate']])->fetch();
    if($active_theme) {
        $db->query("INSERT INTO settings (setting_key, setting_value) 
                   VALUES ('theme_color', ?) ON DUPLICATE KEY UPDATE setting_value = ?", 
                   [$active_theme['primary_color'], $active_theme['primary_color']]);
        
        $db->query("INSERT INTO settings (setting_key, setting_value) 
                   VALUES ('active_theme_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?", 
                   [$_GET['activate'], $_GET['activate']]);
    }
    
    $_SESSION['message'] = 'Tema aktif edildi.';
    $_SESSION['message_type'] = 'success';
    header('Location: themes.php');
    exit;
}

// Temaları listele
$themes = $db->query("SELECT * FROM customer_themes ORDER BY is_active DESC, created_at DESC")->fetchAll();

// Düzenleme için tema getir
$edit_theme = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_theme = $db->query("SELECT * FROM customer_themes WHERE id = ?", [$_GET['edit']])->fetch();
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tema Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .theme-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .theme-preview {
            height: 120px;
            position: relative;
            overflow: hidden;
        }
        
        .theme-active {
            border: 3px solid #28a745;
        }
        
        .theme-active .card-header {
            background: #28a745 !important;
        }
        
        .color-palette {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        
        .color-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .concept-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-palette me-2"></i>Tema Yönetimi</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#themeModal">
            <i class="fas fa-plus me-2"></i>Yeni Tema Ekle
        </button>
    </div>

    <!-- Tema Listesi -->
    <div class="row">
        <?php foreach($themes as $theme): ?>
            <div class="col-md-4 mb-4">
                <div class="card theme-card <?= $theme['is_active'] ? 'theme-active' : '' ?>">
                    <div class="theme-preview" style="background: linear-gradient(135deg, <?= $theme['primary_color'] ?>, <?= $theme['secondary_color'] ?>);">
                        <div class="concept-badge bg-white text-dark"><?= ucfirst($theme['concept']) ?></div>
                        <?php if($theme['is_active']): ?>
                            <div class="position-absolute top-0 start-0 bg-success text-white px-2 py-1" style="border-radius: 0 0 10px 0;">
                                <i class="fas fa-check me-1"></i>Aktif
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($theme['name']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($theme['description']) ?></p>
                        
                        <div class="color-palette">
                            <div class="color-circle" style="background: <?= $theme['primary_color'] ?>;" title="Ana Renk"></div>
                            <div class="color-circle" style="background: <?= $theme['secondary_color'] ?>;" title="İkincil Renk"></div>
                            <div class="color-circle" style="background: <?= $theme['accent_color'] ?>;" title="Vurgu Rengi"></div>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <a href="theme_preview.php?theme=<?= $theme['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                                <i class="fas fa-eye me-1"></i>Önizle
                            </a>
                            
                            <?php if(!$theme['is_active']): ?>
                                <a href="?activate=<?= $theme['id'] ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-check me-1"></i>Aktif Et
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-primary btn-sm" onclick="editTheme(<?= $theme['id'] ?>)">
                                <i class="fas fa-edit me-1"></i>Düzenle
                            </button>
                            
                            <?php if(!$theme['is_active']): ?>
                                <a href="?delete=<?= $theme['id'] ?>" class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Bu temayı silmek istediğinizden emin misiniz?')">
                                    <i class="fas fa-trash me-1"></i>Sil
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tema Ekleme/Düzenleme Modal -->
<div class="modal fade" id="themeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-palette me-2"></i>
                    <span id="modalTitle">Yeni Tema Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" id="themeForm">
                <input type="hidden" name="theme_id" id="theme_id">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tema Adı</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konsept</label>
                                <select name="concept" id="concept" class="form-select" required>
                                    <option value="modern">Modern</option>
                                    <option value="classic">Klasik</option>
                                    <option value="elegant">Zarif</option>
                                    <option value="casual">Günlük</option>
                                    <option value="luxury">Lüks</option>
                                    <option value="minimal">Minimal</option>
                                    <option value="vintage">Vintage</option>
                                    <option value="corporate">Kurumsal</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ana Renk</label>
                                <input type="color" name="primary_color" id="primary_color" class="form-control form-control-color" value="#e74c3c">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">İkincil Renk</label>
                                <input type="color" name="secondary_color" id="secondary_color" class="form-control form-control-color" value="#c0392b">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Vurgu Rengi</label>
                                <input type="color" name="accent_color" id="accent_color" class="form-control form-control-color" value="#f39c12">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Arkaplan Rengi</label>
                                <input type="color" name="background_color" id="background_color" class="form-control form-control-color" value="#f8f9fa">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Metin Rengi</label>
                                <input type="color" name="text_color" id="text_color" class="form-control form-control-color" value="#2c3e50">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Font Ailesi</label>
                                <select name="font_family" id="font_family" class="form-select">
                                    <option value="Poppins">Poppins (Modern)</option>
                                    <option value="Roboto">Roboto (Temiz)</option>
                                    <option value="Open Sans">Open Sans (Klasik)</option>
                                    <option value="Playfair Display">Playfair Display (Zarif)</option>
                                    <option value="Montserrat">Montserrat (Güçlü)</option>
                                    <option value="Lato">Lato (Dostça)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Kategori Stili</label>
                                <select name="category_style" id="category_style" class="form-select">
                                    <option value="grid">Izgara</option>
                                    <option value="grid-2col">2'li Izgara</option>
                                    <option value="masonry">Masonry</option>
                                    <option value="carousel">Karusel</option>
                                    <option value="list">Liste</option>
                                    <option value="list-2col">2'li Liste</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ürün Düzeni</label>
                                <select name="product_layout" id="product_layout" class="form-select">
                                    <option value="grid">Izgara</option>
                                    <option value="grid-2col">2'li Izgara</option>
                                    <option value="list">Liste</option>
                                    <option value="list-2col">2'li Liste</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Header Stili</label>
                                <select name="header_style" id="header_style" class="form-select">
                                    <option value="modern">Modern</option>
                                    <option value="classic">Klasik</option>
                                    <option value="minimal">Minimal</option>
                                    <option value="full">Tam Genişlik</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="save_theme" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editTheme(themeId) {
    // AJAX ile tema bilgilerini getir ve formu doldur
    fetch(`get_theme.php?id=${themeId}`)
        .then(response => response.json())
        .then(theme => {
            document.getElementById('theme_id').value = theme.id;
            document.getElementById('name').value = theme.name;
            document.getElementById('concept').value = theme.concept;
            document.getElementById('description').value = theme.description;
            document.getElementById('primary_color').value = theme.primary_color;
            document.getElementById('secondary_color').value = theme.secondary_color;
            document.getElementById('accent_color').value = theme.accent_color;
            document.getElementById('background_color').value = theme.background_color;
            document.getElementById('text_color').value = theme.text_color;
            document.getElementById('font_family').value = theme.font_family;
            document.getElementById('category_style').value = theme.category_style;
            document.getElementById('product_layout').value = theme.product_layout;
            document.getElementById('header_style').value = theme.header_style;
            
            document.getElementById('modalTitle').textContent = 'Tema Düzenle';
            
            new bootstrap.Modal(document.getElementById('themeModal')).show();
        });
}

// Modal temizleme
document.getElementById('themeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('themeForm').reset();
    document.getElementById('theme_id').value = '';
    document.getElementById('modalTitle').textContent = 'Yeni Tema Ekle';
});
</script>

</body>
</html>