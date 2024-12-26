<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(empty($_POST['table_no'])) {
        throw new Exception('Masa adı gerekli');
    }

    $db = new Database();
    $table_no = trim($_POST['table_no']);

    // Aynı isimde masa var mı kontrol et
    $check = $db->query("SELECT id FROM tables WHERE table_no = ?", [$table_no])->fetch();
    if($check) {
        throw new Exception('Bu isimde bir masa zaten var');
    }

    // Yeni masa ekle
    $db->query("INSERT INTO tables (table_no, status) VALUES (?, 1)", [$table_no]);

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