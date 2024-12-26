<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(empty($_POST['table_id']) || empty($_POST['table_no'])) {
        throw new Exception('Eksik bilgi');
    }

    $db = new Database();
    $table_id = (int)$_POST['table_id'];
    $table_no = trim($_POST['table_no']);

    $db->query("UPDATE tables SET table_no = ? WHERE id = ?", [$table_no, $table_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa güncellendi'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}