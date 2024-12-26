<?php
// Session aktif değilse başlat
if (session_status() === PHP_SESSION_NONE) {
    // Session'ı başlat
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qr_menu');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ratelimit.php';
?>