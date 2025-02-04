<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

$db = new Database();

try {
    // Tüm kategorileri ve gider toplamlarını getir
    $categoryData = $db->query("
        SELECT 
            ec.name,
            ec.color,
            COALESCE(SUM(e.amount), 0) as total
        FROM expense_categories ec
        LEFT JOIN expenses e ON e.category_id = ec.id
        WHERE e.expense_date IS NOT NULL
        GROUP BY ec.id, ec.name, ec.color
        HAVING total > 0
        ORDER BY total DESC
    ")->fetchAll();

    $labels = [];
    $values = [];
    $colors = [];
    $hasData = false;

    foreach ($categoryData as $data) {
        if ($data['total'] > 0) {
            $labels[] = $data['name'];
            $values[] = floatval($data['total']);
            $colors[] = $data['color'] ?: '#' . substr(md5($data['name']), 0, 6);
            $hasData = true;
        }
    }

    header('Content-Type: application/json');
    if ($hasData) {
        echo json_encode([
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors
        ]);
    } else {
        // Veri yoksa boş dizi döndür
        echo json_encode([
            'labels' => [],
            'values' => [],
            'colors' => []
        ]);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'labels' => [],
        'values' => [],
        'colors' => []
    ]);
} 