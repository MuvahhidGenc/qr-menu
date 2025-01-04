<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yetki kontrolü
if (!isAdmin() && !isSuperAdmin()) {
    die(json_encode(['error' => 'Yetkisiz erişim']));
}
header('Content-Type: application/json');

try {
    $db = new Database();
    
    if (!isset($_GET['table_id'])) {
        throw new Exception('Masa ID gerekli!');
    }
    
    $table_id = intval($_GET['table_id']);
    
    // Masa bilgilerini ve durumunu çek
    $query = "
        SELECT 
            t.*,
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM orders o 
                    WHERE o.table_id = t.id 
                    AND o.payment_id IS NULL
                ) THEN 'occupied'
                ELSE 'empty'
            END as current_status,
            COALESCE(
                (SELECT SUM(total_amount) 
                 FROM orders 
                 WHERE table_id = t.id 
                 AND payment_id IS NULL
                ), 0
            ) as unpaid_total
        FROM tables t
        WHERE t.id = ?
    ";
    
    $table = $db->query($query, [$table_id])->fetch();
    
    if (!$table) {
        throw new Exception('Masa bulunamadı!');
    }
    
    // Yanıt formatını düzenle
    echo json_encode([
        'success' => true,
        'table' => [
            'id' => $table['id'],
            'table_no' => $table['table_no'],
            'status' => $table['status'],
            'current_status' => $table['current_status'],
            'unpaid_total' => floatval($table['unpaid_total'])
        ]
    ]);

} catch (Exception $e) {
    error_log('Table Info Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Masa bilgileri yüklenirken bir hata oluştu: ' . $e->getMessage()
    ]);
} 