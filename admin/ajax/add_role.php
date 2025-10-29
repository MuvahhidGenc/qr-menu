<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Slug oluşturma fonksiyonu
function generateSlug($text) {
    // Türkçe karakterleri değiştir
    $turkishChars = array('ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü');
    $englishChars = array('c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u');
    $text = str_replace($turkishChars, $englishChars, $text);
    
    // Küçük harfe çevir
    $text = strtolower($text);
    
    // Özel karakterleri kaldır ve boşlukları tire ile değiştir
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Başındaki ve sonundaki tireleri kaldır
    return trim($text, '-');
}

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Hata raporlamayı aç (debug için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// POST verilerini logla
error_log("Add Role POST Data: " . print_r($_POST, true));

// Oturum kontrolü
if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'error' => 'Yetkisiz erişim']));
}

try {
    // Yetki kontrolü
    if (!hasPermission('roles.add')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $db = new Database();
    
    // Form verilerini al
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissions = $_POST['permissions'] ?? [];
    
    // Validasyonlar
    if (empty($name)) {
        throw new Exception('Rol adı zorunludur.');
    }
    
    if (strlen($name) < 2 || strlen($name) > 50) {
        throw new Exception('Rol adı 2-50 karakter arasında olmalıdır.');
    }
    
    // Slug oluştur (Türkçe karakter desteği ile)
    $slug = generateSlug($name);
    
    // Rol adı benzersizlik kontrolü
    $existingRole = $db->query("SELECT id FROM roles WHERE name = ? OR slug = ?", [$name, $slug])->fetch();
    if ($existingRole) {
        throw new Exception('Bu rol adı zaten kullanılıyor.');
    }
    
    // Yetkileri JSON formatına çevir
    $permissionsJson = json_encode($permissions);
    
    // Rol ekle
    $stmt = $db->query(
        "INSERT INTO roles (name, slug, description, permissions, is_system) VALUES (?, ?, ?, ?, 0)",
        [$name, $slug, $description, $permissionsJson]
    );
    
    $roleId = $db->getConnection()->lastInsertId();
    
    error_log("Role added successfully. ID: " . $roleId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Rol başarıyla eklendi.',
        'role_id' => $roleId
    ]);

} catch (Exception $e) {
    error_log("Add Role Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
