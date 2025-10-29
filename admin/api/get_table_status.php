<?php
// Error reporting'i kapat (JSON bozmasın)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Debug için
error_log("Table status API called");

// Yetki kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapılmamış', 'debug' => 'no_session']);
    exit;
}

if (!hasPermission('tables.view')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim', 'debug' => 'no_permission']);
    exit;
}

try {
    $db = new Database();
    
    // Masaları kategoriler ile birlikte çek - basit sıralama
    $tables = $db->query("
        SELECT t.*, tc.name as category_name 
        FROM tables t 
        LEFT JOIN table_categories tc ON t.category_id = tc.id 
        ORDER BY t.table_no
    ")->fetchAll();
    
    $tableStatuses = [];
    
    foreach ($tables as $table) {
        // Her masa için güncel durum bilgilerini hesapla
        $tableInfo = $db->query("
            SELECT 
                CASE 
                    WHEN COUNT(CASE WHEN o.status IN ('pending', 'preparing') THEN 1 END) > 0 THEN 'occupied'
                    ELSE 'empty'
                END as status,
                COALESCE(SUM(CASE 
                    WHEN o.status IN ('pending', 'preparing') AND o.payment_id IS NULL AND oi.payment_id IS NULL
                    THEN oi.quantity * oi.price 
                    ELSE 0 
                END), 0) as total
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.table_id = ? 
                AND o.status IN ('pending', 'preparing')
                AND o.payment_id IS NULL
        ", [$table['id']])->fetch();
        
        // Sadece ödenmemiş ürünlerin toplamı
        $totalAmount = (float)$tableInfo['total'];
        $tableStatuses[] = [
            'id' => $table['id'],
            'table_no' => $table['table_no'],
            'category_id' => $table['category_id'],
            'category_name' => $table['category_name'] ?? 'Kategorisiz',
            'capacity' => $table['capacity'] ?? 4,
            'status' => $tableInfo['status'],
            'total' => $totalAmount,
            'formatted_total' => number_format($totalAmount, 2) . ' ₺',
            'debug_info' => [
                'raw_total' => $tableInfo['total'],
                'table_status' => $tableInfo['status']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $tableStatuses,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    error_log("Table status API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası: ' . $e->getMessage(),
        'debug' => 'exception'
    ]);
}
?>
