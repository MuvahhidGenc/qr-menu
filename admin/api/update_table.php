<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('tables.manage')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['table_number'])) {
        throw new Exception('Gerekli bilgiler eksik');
    }

    $table_no = trim($data['table_number']);
    $table_id = (int)$data['id'];
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
    $capacity = isset($data['capacity']) ? (int)$data['capacity'] : null;

    // Masa adı validasyonu
    if (strlen($table_no) < 1 || strlen($table_no) > 50) {
        throw new Exception('Masa adı 1-50 karakter arasında olmalıdır');
    }
    
    // Güvenli karakterler kontrolü (Türkçe destekli)
    if (!preg_match('/^[\p{L}\p{N}\s\-_\.]+$/u', $table_no)) {
        throw new Exception('Masa adında sadece harf, rakam, boşluk, tire ve nokta kullanılabilir');
    }

    // Kapasite validasyonu
    if ($capacity !== null && ($capacity < 1 || $capacity > 50)) {
        throw new Exception('Kapasite 1-50 kişi arasında olmalıdır');
    }

    $db = new Database();

    // Aynı isimde başka masa var mı kontrol et (kendisi hariç)
    $check = $db->query("SELECT id FROM tables WHERE table_no = ? AND id != ?", [$table_no, $table_id])->fetch();
    if($check) {
        throw new Exception('Bu isimde başka bir masa zaten var');
    }

    // Kategori var mı kontrol et
    if ($category_id) {
        $categoryCheck = $db->query("SELECT id FROM table_categories WHERE id = ?", [$category_id])->fetch();
        if (!$categoryCheck) {
            throw new Exception('Seçilen kategori bulunamadı');
        }
    }

    // Güncelleme SQL'i oluştur
    $updateFields = ['table_no = ?'];
    $updateValues = [$table_no];
    
    if ($category_id !== null) {
        $updateFields[] = 'category_id = ?';
        $updateValues[] = $category_id;
    }
    
    if ($capacity !== null) {
        $updateFields[] = 'capacity = ?';
        $updateValues[] = $capacity;
    }
    
    $updateValues[] = $table_id; // WHERE condition için
    
    $sql = "UPDATE tables SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $result = $db->query($sql, $updateValues);

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla güncellendi'
    ]);
} catch(Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 