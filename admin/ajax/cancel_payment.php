<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Sadece payments.cancel yetkisi kontrolü yeterli
if (!hasPermission('payments.cancel')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $paymentId = $data['payment_id'] ?? null;
    $cancelNote = $data['cancel_note'] ?? null;

    if (!$paymentId) {
        throw new Exception('Geçersiz ödeme ID');
    }

    if (!$cancelNote || trim($cancelNote) === '') {
        throw new Exception('İptal nedeni girilmesi zorunludur');
    }

    $db = new Database();
    
    // Transaction başlat
    $db->beginTransaction();

    // Ödeme durumunu güncelle ve iptal notunu ekle
    $db->query("
        UPDATE payments 
        SET status = 'cancelled', 
            payment_note = CONCAT(COALESCE(payment_note, ''), '\nİptal Nedeni: ', ?) 
        WHERE id = ?", 
        [$cancelNote, $paymentId]
    );
    
    // İlgili siparişleri güncelle
    $db->query("
        UPDATE orders 
        SET status = 'cancelled' 
        WHERE payment_id = ?", 
        [$paymentId]
    );

    // Bildirim ekle
    $paymentInfo = $db->query("
        SELECT t.table_no 
        FROM payments p 
        JOIN tables t ON p.table_id = t.id 
        WHERE p.id = ?", 
        [$paymentId]
    )->fetch();

    $db->query("
        INSERT INTO notifications (type, message, is_read) 
        VALUES ('payment_cancelled', ?, 0)",
        ["Masa {$paymentInfo['table_no']}'ın ödemesi iptal edildi. Neden: {$cancelNote}"]
    );

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 