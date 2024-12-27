// Bildirim sesi
const notificationSound = new Audio('assets/sounds/notification.mp3');

// Bildirimleri kontrol et
function checkNotifications() {
    $.ajax({
        url: 'ajax/check_notifications.php',
        type: 'GET',
        success: function(response) {
            if(response.success) {
                // Okunmamış bildirim sayısı
                if(response.count > 0) {
                    $('#notificationCount').text(response.count).show();
                    // Yeni bildirim varsa ses çal
                    if(response.new_notifications) {
                        notificationSound.play();
                    }
                } else {
                    $('#notificationCount').hide();
                }
                
                // Bildirim listesini güncelle
                updateNotificationList();
            }
        }
    });
}

// Bildirim listesini güncelle
function updateNotificationList() {
    $.ajax({
        url: 'ajax/get_notifications.php',
        type: 'GET',
        success: function(response) {
            if(response.success) {
                $('#notificationList').html(response.html);
            }
        }
    });
}

// Bildirimi okundu olarak işaretle
function markAsRead(notificationId) {
    $.ajax({
        url: 'ajax/mark_notification_read.php',
        type: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            if(response.success) {
                checkNotifications();
            }
        }
    });
}

// Tüm bildirimleri okundu olarak işaretle
$('.mark-all-read').click(function() {
    $.ajax({
        url: 'ajax/mark_all_read.php',
        type: 'POST',
        success: function(response) {
            if(response.success) {
                checkNotifications();
            }
        }
    });
});

// Her 30 saniyede bir bildirimleri kontrol et
//setInterval(checkNotifications, 30000);

// Sayfa yüklendiğinde bildirimleri kontrol et
$(document).ready(function() {
    //checkNotifications();
    updateNotificationList();
    setInterval(updateNotificationList, 10000);
    
});