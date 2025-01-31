<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Yetki kontrolü
$status = $_POST['status'] ?? '';
switch($status) {
    case 'confirmed':
        if (!hasPermission('reservations.approve')) {
            echo json_encode(['success' => false, 'message' => 'Onaylama yetkiniz bulunmamaktadır.']);
            exit;
        }
        break;
    case 'cancelled':
        if (!hasPermission('reservations.reject')) {
            echo json_encode(['success' => false, 'message' => 'İptal etme yetkiniz bulunmamaktadır.']);
            exit;
        }
        break;
    case 'completed':
        if (!hasPermission('reservations.edit')) {
            echo json_encode(['success' => false, 'message' => 'Tamamlama yetkiniz bulunmamaktadır.']);
            exit;
        }
        break;
    default:
        if (!hasPermission('reservations.edit')) {
            echo json_encode(['success' => false, 'message' => 'Düzenleme yetkiniz bulunmamaktadır.']);
            exit;
        }
}

// Masa değişikliği yapılıyorsa ek kontrol
if (isset($_POST['table_id']) && $_POST['table_id'] != null) {
    // Eğer kullanıcının rezervasyon onaylama yetkisi varsa masa atamasına da izin ver
    if (!hasPermission('tables.manage') && !hasPermission('reservations.approve')) {
        echo json_encode(['success' => false, 'message' => 'Masa atama yetkiniz bulunmamaktadır.']);
        exit;
    }
}

// Debug için gelen verileri logla
error_log("POST Verileri: " . print_r($_POST, true));

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Gerekli parametreler eksik']);
    exit;
}

$id = $_POST['id'];
$status = $_POST['status'];
$tableId = $_POST['table_id'] ?? null;

try {
    $db = new Database();
    
    // Önce pre_orders tablosundan siparişleri al
    $preOrders = $db->query(
        "SELECT po.*, p.name as product_name 
        FROM pre_orders po 
        JOIN products p ON p.id = po.item_id 
        WHERE po.reservation_id = ?", 
        [$id]
    )->fetchAll();

    error_log("Bulunan Ön Siparişler: " . print_r($preOrders, true));

    // Eğer onaylama işlemi ve masa seçimi yapıldıysa
    if ($status === 'confirmed' && $tableId && !empty($preOrders)) {
        // Toplam tutarı hesapla
        $totalAmount = array_reduce($preOrders, function($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);

        // Yeni sipariş oluştur
        $db->query(
            "INSERT INTO orders (
                reservation_id, 
                table_id, 
                status, 
                total_amount, 
                created_at
            ) VALUES (?, ?, 'pending', ?, NOW())",
            [$id, $tableId, $totalAmount]
        );
        
        $orderId = $db->lastInsertId();
        error_log("Oluşturulan Sipariş ID: " . $orderId);

        // Sipariş ürünlerini ekle
        foreach ($preOrders as $item) {
            $db->query(
                "INSERT INTO order_items (
                    order_id, 
                    product_id, 
                    quantity, 
                    price, 
                    created_at
                ) VALUES (?, ?, ?, ?, NOW())",
                [
                    $orderId, 
                    $item['item_id'], 
                    $item['quantity'], 
                    $item['price']
                ]
            );
        }

        // Bildirim oluştur
        $tableNo = $db->query(
            "SELECT table_no FROM tables WHERE id = ?", 
            [$tableId]
        )->fetch()['table_no'];

        $db->query(
            "INSERT INTO notifications (
                order_id, 
                type, 
                message, 
                created_at
            ) VALUES (?, 'new_order', ?, NOW())",
            [
                $orderId,
                "Masa {$tableNo}'dan yeni sipariş geldi!"
            ]
        );
    }

    // Eğer iptal işlemi ise ve masaya aktarılmış siparişler varsa
    if ($status === 'cancelled') {
        // Aktif siparişleri bul
        $activeOrders = $db->query(
            "SELECT id FROM orders 
            WHERE reservation_id = ? 
            AND status NOT IN ('cancelled', 'completed')",
            [$id]
        )->fetchAll();

        // Varsa siparişleri iptal et
        foreach ($activeOrders as $order) {
            // Siparişi iptal et
            $db->query(
                "UPDATE orders SET status = 'cancelled' WHERE id = ?",
                [$order['id']]
            );

            // Bildirim oluştur
            $db->query(
                "INSERT INTO notifications (
                    order_id, type, message, created_at
                ) VALUES (?, 'order_status', ?, NOW())",
                [
                    $order['id'],
                    "Sipariş #" . $order['id'] . " rezervasyon iptali nedeniyle iptal edildi"
                ]
            );
        }
    }

    // Rezervasyonu güncelle
    $db->query(
        "UPDATE reservations SET status = ?, table_id = ? WHERE id = ?",
        [$status, $tableId, $id]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Rezervasyon başarıyla güncellendi' . 
            (!empty($preOrders) ? ' ve siparişler masaya aktarıldı' : ''),
        'debug' => [
            'reservation_id' => $id,
            'table_id' => $tableId,
            'order_count' => count($preOrders),
            'total_amount' => $totalAmount ?? 0
        ]
    ]);

} catch (PDOException $e) {
    error_log("PDO Hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı hatası: ' . $e->getMessage(),
        'sql_error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Genel Hata: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Bir hata oluştu: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
} 