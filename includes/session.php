<?php
session_start();
session_regenerate_id(true);

function checkAuth() {
    if (!isset($_SESSION['admin'])) {
        header('Location: login.php');
        exit();
    }
}

function secureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
}