<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['table_id']) || !isset($_POST['status'])) {
        throw new Exception('Eksik parametreler');
    }

    $db = new Database();
    $table_id = (int)$_POST['table_id'];
    $status = (int)$_POST['status'];

    $db->query("UPDATE tables SET status = ? WHERE id = ?", [$status, $table_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa durumu güncellendi'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}