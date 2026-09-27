<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Bu uç masayı kapatıp siparişleri 'completed' yapıyordu ve HİÇBİR oturum
// kontrolü yoktu. Siparişleri ödemesiz 'completed' yapmak ciro raporlarını
// da şişirdiği için yetki zorunlu.
if (!isLoggedIn() || !hasPermission('tables.manage')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['table_id'])) {
        throw new Exception('Masa ID gerekli');
    }

    $tableId = (int)$data['table_id'];
    $db = new Database();
    
    // Masanın durumunu güncelle
    $result = $db->query(
        "UPDATE tables SET status = 'inactive' WHERE id = ?",
        [$tableId]
    );
    
    // Masaya ait siparişleri 'completed' yap.
    // Yalnızca GERÇEKTEN tahsil edilmiş siparişler tamamlanmış sayılır;
    // aksi halde raporlarda parayı almamış satışlar ciro olarak görünürdü.
    $paidOrders = $db->query(
        "SELECT o.id
         FROM orders o
         INNER JOIN payments p ON p.id = o.payment_id
         WHERE o.table_id = ?
           AND o.status != 'completed'
           AND p.status = 'completed'",
        [$tableId]
    )->fetchAll();
    foreach ($paidOrders as $row) {
        $db->query(
            "UPDATE orders SET status = 'completed', completed_at = NOW() WHERE id = ?",
            [$row['id']]
        );
    }
    $db->query(
        "UPDATE orders SET status = 'cancelled', cancelled_at = NOW()
         WHERE table_id = ? AND status NOT IN ('completed', 'cancelled')",
        [$tableId]
    );

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Masa durumu güncellendi',
            'completed_orders' => count($paidOrders)
        ]);
    } else {
        throw new Exception('Masa durumu güncellenemedi');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 