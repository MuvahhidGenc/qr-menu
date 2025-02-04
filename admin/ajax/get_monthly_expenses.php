<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$db = new Database();

try {
    // Son 12 ayın verilerini getir
    $monthlyData = $db->query("
        SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            COALESCE(SUM(amount), 0) as total
        FROM expenses
        WHERE expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ")->fetchAll();

    $labels = [];
    $values = [];

    // Son 12 ay için veri hazırla
    $end = new DateTime();
    $start = (new DateTime())->modify('-11 months')->modify('first day of this month');
    
    while ($start <= $end) {
        $monthKey = $start->format('Y-m');
        $found = false;
        
        foreach ($monthlyData as $data) {
            if ($data['month'] === $monthKey) {
                $labels[] = $start->format('F Y');
                $values[] = floatval($data['total']);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $labels[] = $start->format('F Y');
            $values[] = 0;
        }
        
        $start->modify('+1 month');
    }

    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'labels' => [],
        'values' => []
    ]);
} 