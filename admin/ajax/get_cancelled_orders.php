<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!hasPermission('reports.view')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    $db = new Database();
    
    $orders = $db->query("
        SELECT o.id, o.created_at, o.total_amount, o.cancel_reason, t.table_no
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.status = 'cancelled'
        AND DATE(o.created_at) BETWEEN ? AND ?
        ORDER BY o.created_at DESC", 
        [$startDate, $endDate]
    )->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 