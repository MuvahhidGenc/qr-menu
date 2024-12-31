<?php
// Hata raporlamayı açık tutalım ama loglayalım
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

header('Content-Type: application/json');

try {
    // Dosya yollarını kontrol et
    if (!file_exists('../includes/db.php') || !file_exists('../includes/functions.php')) {
        throw new Exception('Gerekli dosyalar bulunamadı');
    }

    require_once '../includes/db.php';
    require_once '../includes/functions.php';

    // Database bağlantı kontrolü
    if (!isset($db)) {
        throw new Exception('Veritabanı bağlantısı bulunamadı');
    }

    if (!isset($_GET['table_id'])) {
        throw new Exception('Masa ID gerekli');
    }

    $tableId = intval($_GET['table_id']);

    // Log ekleyelim
    error_log("Masa ID: " . $tableId);

    // Önce masanın varlığını kontrol edelim
    $tableCheck = $db->prepare("SELECT id FROM tables WHERE id = ?");
    $tableCheck->execute([$tableId]);
    if (!$tableCheck->fetch()) {
        throw new Exception('Masa bulunamadı');
    }

    // Aktif siparişleri al
    $query = "SELECT 
                o.id as order_id,
                oi.id as item_id,
                oi.quantity,
                p.name as product_name,
                p.price,
                (p.price * oi.quantity) as total_price
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN products p ON oi.product_id = p.id
              WHERE o.table_id = ? 
              AND o.status = 'pending'
              ORDER BY o.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$tableId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sorgu sonucunu logla
    error_log("Bulunan sipariş sayısı: " . count($orders));

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'table_id' => $tableId,
        'count' => count($orders)
    ]);

} catch (PDOException $e) {
    error_log("PDO Hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Genel Hata: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit; 