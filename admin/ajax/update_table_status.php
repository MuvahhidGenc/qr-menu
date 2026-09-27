<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Bu uç oturum/izin kontrolü olmadan herhangi bir masa durumunu değiştirebiliyordu.
if (!isLoggedIn() || !hasPermission('tables.manage')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

try {
    if (!isset($_POST['table_id'], $_POST['status'])) {
        throw new Exception('Eksik parametreler');
    }

    $db = new Database();
    $table_id = (int)$_POST['table_id'];

    if ($table_id <= 0) {
        throw new Exception('Geçersiz masa');
    }

    // tables.status bir ENUM('active','inactive') sütunudur. Frontend 1/0
    // gönderiyordu ve burada doğrudan (int) ile yazılıyordu; strict mod
    // kapalı olduğu için MySQL geçersiz değeri sessizce boş string'e
    // çeviriyor ve 'status = active' sorguları boş dönüyordu.
    $rawStatus = $_POST['status'];
    $status = in_array((string)$rawStatus, ['1', 'active', 'true'], true) ? 'active' : 'inactive';

    $stmt = $db->query("UPDATE tables SET status = ? WHERE id = ?", [$status, $table_id]);

    if ($stmt->rowCount() === 0) {
        $exists = $db->query("SELECT id FROM tables WHERE id = ?", [$table_id])->fetch();
        if (!$exists) {
            throw new Exception('Masa bulunamadı');
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Masa durumu güncellendi',
        'status'  => $status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
