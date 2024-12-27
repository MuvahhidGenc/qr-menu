<?php
require_once '../../includes/config.php';
header('Content-Type: application/json');

date_default_timezone_set('Europe/Istanbul');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');

try {
    $db = new Database();

    // Debug için bildirim zamanlarını kontrol edelim
    $debug = $db->query("SELECT created_at, NOW() as server_time FROM notifications")->fetch();
    error_log("Bildirim zamanı kontrolü: " . print_r($debug, true));

    // Bildirimleri getir
    $notifications = $db->query(
        "SELECT n.*, o.table_id, t.table_no 
         FROM notifications n 
         LEFT JOIN orders o ON n.order_id = o.id 
         LEFT JOIN tables t ON o.table_id = t.id 
         ORDER BY n.created_at DESC LIMIT 50"
    )->fetchAll();

    $html = '';
    $unread_count = 0;

    foreach($notifications as $notification) {
        $timeStr = formatTurkishDate($notification['created_at']);
        
        $html .= '
        <div class="notification-item '.($notification['is_read'] ? '' : 'unread').'" 
             data-id="'.$notification['id'].'" 
             data-table-id="'.$notification['table_id'].'" 
             style="cursor: pointer">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="mb-1">'.htmlspecialchars($notification['message']).'</div>
                    <small class="text-muted">'.$timeStr.'</small>
                </div>
                '.(!$notification['is_read'] ? '<div class="unread-indicator"></div>' : '').'
            </div>
        </div>';

        if(!$notification['is_read']) {
            $unread_count++;
        }
    }

    if(empty($html)) {
        $html = '<div class="p-3 text-center text-muted">Bildirim bulunmuyor</div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'unread_count' => $unread_count
    ]);

} catch(Exception $e) {
    error_log('Bildirim hatası: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function formatTurkishDate($datetime) {
    $now = new DateTime();
    $date = new DateTime($datetime);
    
    error_log("Şu an: " . $now->format('Y-m-d H:i:s'));
    error_log("Bildirim zamanı: " . $date->format('Y-m-d H:i:s'));
    
    $diff = $date->diff($now);
    $minutes = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

    if ($minutes < 1) {
        return "Az önce";
    } elseif ($minutes < 60) {
        return $minutes . " dakika önce";
    } elseif ($minutes < 24 * 60) {
        $hours = floor($minutes / 60);
        return $hours . " saat önce";
    } elseif ($minutes < 48 * 60) {
        return "Dün " . $date->format('H:i');
    } elseif ($minutes < 7 * 24 * 60) {
        return floor($minutes / (24 * 60)) . " gün önce";
    } else {
        $aylar = array(
            'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
        );
        
        return $date->format('j ') . $aylar[$date->format('n') - 1] . $date->format(' Y H:i');
    }
}