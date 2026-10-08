<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Europe/Istanbul');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');

// Bildirim listesi -> dashboard.view. Önceden auth yoktu ve bildirim metni
// data-* attribute'larına kaçırılmadan basılıyordu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('dashboard.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

try {
    $db = new Database();

    // Bildirimleri getir
    $notifications = $db->query(
        "SELECT n.*, o.table_id, t.table_no
         FROM notifications n
         LEFT JOIN orders o ON n.order_id = o.id
         LEFT JOIN tables t ON o.table_id = t.id
         ORDER BY n.created_at DESC LIMIT 50"
    )->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    $unread_count = 0;

    foreach ($notifications as $notification) {
        $timeStr = formatTurkishDate($notification['created_at']);

        // data-id / data-table-id attribute'ları da kaçırılmalı
        $html .= '
        <div class="notification-item '.($notification['is_read'] ? '' : 'unread').'"
             data-id="'.(int)$notification['id'].'"
             data-table-id="'.($notification['table_id'] !== null ? (int)$notification['table_id'] : '').'"
             style="cursor: pointer">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="mb-1">'.htmlspecialchars((string)$notification['message'], ENT_QUOTES, 'UTF-8').'</div>
                    <small class="text-muted">'.htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8').'</small>
                </div>
                '.(!$notification['is_read'] ? '<div class="unread-indicator"></div>' : '').'
            </div>
        </div>';

        if (!$notification['is_read']) {
            $unread_count++;
        }
    }

    if (empty($html)) {
        $html = '<div class="p-3 text-center text-muted">Bildirim bulunmuyor</div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'unread_count' => $unread_count
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_notifications hatası: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bildirimler yüklenemedi.'
    ], JSON_UNESCAPED_UNICODE);
}

function formatTurkishDate($datetime) {
    try {
        $now = new DateTime();
        $date = new DateTime((string)$datetime);

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
        }

        $aylar = [
            'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
        ];

        return $date->format('j ') . $aylar[(int)$date->format('n') - 1] . $date->format(' Y H:i');
    } catch (Throwable $e) {
        return '';
    }
}