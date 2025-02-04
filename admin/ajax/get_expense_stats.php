<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$db = new Database();

try {
    // Aylık toplam
    $monthlyTotal = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(expense_date) = YEAR(CURRENT_DATE())
    ")->fetch()['total'];

    // Yıllık toplam
    $yearlyTotal = $db->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE YEAR(expense_date) = YEAR(CURRENT_DATE())
    ")->fetch()['total'];

    // En çok harcama yapılan kategori
    $topCategory = $db->query("
        SELECT ec.name, COALESCE(SUM(e.amount), 0) as total
        FROM expense_categories ec
        LEFT JOIN expenses e ON e.category_id = ec.id
        GROUP BY ec.id, ec.name
        ORDER BY total DESC
        LIMIT 1
    ")->fetch();

    // Günlük ortalama
    $dailyAverage = $db->query("
        SELECT COALESCE(AVG(daily_total), 0) as average
        FROM (
            SELECT DATE(expense_date) as date, SUM(amount) as daily_total
            FROM expenses
            GROUP BY DATE(expense_date)
        ) as daily_expenses
    ")->fetch()['average'];

    header('Content-Type: application/json');
    echo json_encode([
        'monthlyTotal' => number_format($monthlyTotal, 2, ',', '.') . ' ₺',
        'yearlyTotal' => number_format($yearlyTotal, 2, ',', '.') . ' ₺',
        'topCategory' => $topCategory ? $topCategory['name'] : 'Henüz gider yok',
        'dailyAverage' => number_format($dailyAverage, 2, ',', '.') . ' ₺'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'monthlyTotal' => '0,00 ₺',
        'yearlyTotal' => '0,00 ₺',
        'topCategory' => 'Veri yok',
        'dailyAverage' => '0,00 ₺'
    ]);
} 