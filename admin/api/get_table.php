<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Yetki kontrolü
if (!isLoggedIn() || !hasPermission('tables.manage')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Input validation
$tableId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$tableId || $tableId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz masa ID']);
    exit;
}

try {
    $db = new Database();
    
    // Masa bilgilerini çek
    $table = $db->query("
        SELECT t.*, tc.name as category_name 
        FROM tables t 
        LEFT JOIN table_categories tc ON t.category_id = tc.id 
        WHERE t.id = ?
    ", [$tableId])->fetch();
    
    if (!$table) {
        echo json_encode(['success' => false, 'message' => 'Masa bulunamadı']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'table' => $table
    ]);

} catch (Exception $e) {
    error_log("Table get error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
