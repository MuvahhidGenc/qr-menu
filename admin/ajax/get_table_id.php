<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!hasPermission('payments.manage')) {
        throw new Exception('Bu işlem için yetkiniz yok!');
    }

    $tableNo = $_GET['table_no'] ?? null;
    
    if (!$tableNo) {
        throw new Exception('Masa numarası gerekli');
    }

    $db = new Database();
    $table = $db->query("SELECT id FROM tables WHERE table_no = ?", [$tableNo])->fetch();

    if (!$table) {
        throw new Exception('Masa bulunamadı');
    }

    echo json_encode(['success' => true, 'table_id' => $table['id']]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}