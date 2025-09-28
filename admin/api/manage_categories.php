<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!hasPermission('tables.manage')) {
    echo json_encode([
        'success' => false,
        'message' => 'Bu işlem için yetkiniz bulunmuyor'
    ]);
    exit();
}

try {
    // JSON input'u al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri');
    }
    
    $action = $input['action'] ?? '';
    $db = new Database();
    
    switch($action) {
        case 'add':
            $name = cleanInput($input['name'] ?? '');
            $description = cleanInput($input['description'] ?? '');
            $sort_order = (int)($input['sort_order'] ?? 0);
            
            if (empty($name)) {
                throw new Exception('Kategori adı gerekli');
            }
            
            // Aynı isimde kategori var mı?
            $check = $db->query("SELECT id FROM table_categories WHERE name = ?", [$name])->fetch();
            if ($check) {
                throw new Exception('Bu isimde bir kategori zaten var');
            }
            
            $db->query("INSERT INTO table_categories (name, description, sort_order) VALUES (?, ?, ?)", 
                [$name, $description, $sort_order]);
                
            echo json_encode([
                'success' => true,
                'message' => 'Kategori başarıyla eklendi'
            ]);
            break;
            
        case 'edit':
            $id = (int)($input['id'] ?? 0);
            $name = cleanInput($input['name'] ?? '');
            $description = cleanInput($input['description'] ?? '');
            $sort_order = (int)($input['sort_order'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Geçersiz kategori ID');
            }
            
            if (empty($name)) {
                throw new Exception('Kategori adı gerekli');
            }
            
            // Kategori var mı?
            $categoryCheck = $db->query("SELECT id FROM table_categories WHERE id = ?", [$id])->fetch();
            if (!$categoryCheck) {
                throw new Exception('Kategori bulunamadı');
            }
            
            // Aynı isimde başka kategori var mı?
            $check = $db->query("SELECT id FROM table_categories WHERE name = ? AND id != ?", [$name, $id])->fetch();
            if ($check) {
                throw new Exception('Bu isimde başka bir kategori zaten var');
            }
            
            $db->query("UPDATE table_categories SET name = ?, description = ?, sort_order = ? WHERE id = ?", 
                [$name, $description, $sort_order, $id]);
                
            echo json_encode([
                'success' => true,
                'message' => 'Kategori başarıyla güncellendi'
            ]);
            break;
            
        case 'delete':
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Geçersiz kategori ID');
            }
            
            // Kategori var mı?
            $categoryCheck = $db->query("SELECT id FROM table_categories WHERE id = ?", [$id])->fetch();
            if (!$categoryCheck) {
                throw new Exception('Kategori bulunamadı');
            }
            
            // Bu kategoride masa var mı kontrol et
            $tableCount = $db->query("SELECT COUNT(*) as count FROM tables WHERE category_id = ?", [$id])->fetch();
            if ($tableCount['count'] > 0) {
                throw new Exception('Bu kategoride masalar bulunuyor. Önce masaları başka kategoriye taşıyın.');
            }
            
            $db->query("DELETE FROM table_categories WHERE id = ?", [$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Kategori başarıyla silindi'
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
