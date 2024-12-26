<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

try {
    if(!isset($_POST['table_id'])) {
        throw new Exception('Masa ID gerekli');
    }

    $db = new Database();
    $table_id = (int)$_POST['table_id'];

    // Masaya ait aktif sipariş var mı kontrol et
    $active_orders = $db->query("SELECT COUNT(*) as count FROM orders WHERE table_id = ? AND status IN ('pending', 'preparing')", 
                               [$table_id])->fetch();

    if($active_orders['count'] > 0) {
        throw new Exception('Bu masaya ait aktif siparişler var. Önce siparişleri tamamlayın.');
    }

    // Masayı sil
    $db->query("DELETE FROM tables WHERE id = ?", [$table_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Masa başarıyla silindi'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}