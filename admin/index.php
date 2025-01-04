<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Debug için session durumunu logla
error_log("Index.php - Session Status: " . print_r($_SESSION, true));

// Oturum kontrolü
if (!isLoggedIn()) {
    error_log("Index.php - User not logged in, redirecting to login.php");
    header('Location: login.php');
    exit();
}

// Buraya gelindiyse kullanıcı giriş yapmış demektir
error_log("Index.php - User is logged in, proceeding...");

require_once 'navbar.php';
?>

<!-- Sayfanın geri kalanı -->