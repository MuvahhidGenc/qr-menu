<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('tables.manage')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }
    
    // JSON input'u al
    $input = json_decode(file_get_contents('php://input'), true);
    
    // POST'dan da almaya çalış (geriye uyumluluk)
    if (!$input) {
        $input = $_POST;
    }
    
    if(empty($input['table_no'])) {
        throw new Exception('Masa adı gerekli');
    }

    $db = new Database();
    $table_no = trim($input['table_no']);
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 1; // Varsayılan kategori
    $capacity = isset($input['capacity']) ? (int)$input['capacity'] : 4; // Varsayılan kapasite

    // Masa adı validasyonu
    if (strlen($table_no) < 1 || strlen($table_no) > 50) {
        throw new Exception('Masa adı 1-50 karakter arasında olmalıdır');
    }
    
    // Güvenli karakterler kontrolü (Türkçe destekli)
    if (!preg_match('/^[\p{L}\p{N}\s\-_\.]+$/u', $table_no)) {
        throw new Exception('Masa adında sadece harf, rakam, boşluk, tire ve nokta kullanılabilir');
    }

    // Input validation
    if ($category_id <= 0) {
        $category_id = 1;
    }
    if ($capacity <= 0) {
        $capacity = 4;
    }

    // Aynı isimde masa var mı kontrol et
    $check = $db->query("SELECT id FROM tables WHERE table_no = ?", [$table_no])->fetch();
    if($check) {
        throw new Exception('Bu isimde bir masa zaten var');
    }

    // Kategori var mı kontrol et
    $categoryCheck = $db->query("SELECT id FROM table_categories WHERE id = ?", [$category_id])->fetch();
    if (!$categoryCheck) {
        throw new Exception('Geçersiz kategori');
    }

    // Yeni masa ekle
    $db->query("INSERT INTO tables (table_no, category_id, capacity, status) VALUES (?, ?, ?, 'active')", 
        [$table_no, $category_id, $capacity]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla eklendi'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 