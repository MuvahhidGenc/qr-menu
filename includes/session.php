<?php
// Oturum güvenliği için
function regenerateSession() {
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else {
        $regeneration_time = 3600; // 1 saat
        if (time() - $_SESSION['last_regeneration'] > $regeneration_time) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Oturum kontrolü
function checkAuth() {
    regenerateSession();
    if (!isset($_SESSION['admin'])) {
        header('Location: login.php');
        exit();
    }
}

// Her sayfa yüklendiğinde son aktivite zamanını güncelle
if (isset($_SESSION['admin'])) {
    $_SESSION['last_activity'] = time();
}