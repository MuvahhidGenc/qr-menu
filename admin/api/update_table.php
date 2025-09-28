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

    // Masa adı validasyonu
    if (strlen($table_no) < 1 || strlen($table_no) > 50) {
        throw new Exception('Masa adı 1-50 karakter arasında olmalıdır');
    }
    
    // Güvenli karakterler kontrolü (Türkçe destekli)
    if (!preg_match('/^[\p{L}\p{N}\s\-_\.]+$/u', $table_no)) {
        throw new Exception('Masa adında sadece harf, rakam, boşluk, tire ve nokta kullanılabilir');
    }

    $db = new Database();

    // Aynı isimde başka masa var mı kontrol et (kendisi hariç)
    $check = $db->query("SELECT id FROM tables WHERE table_no = ? AND id != ?", [$table_no, $table_id])->fetch();
    if($check) {
        throw new Exception('Bu isimde başka bir masa zaten var');
    }

    $result = $db->query("UPDATE tables SET table_no = ? WHERE id = ?", 
        [$table_no, $table_id]
    );

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