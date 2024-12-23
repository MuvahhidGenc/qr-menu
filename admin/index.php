<?php
require_once '../includes/config.php';
checkAuth();
require_once '../includes/navbar.php';

// Rate limit kontrolü
checkRateLimit($_SERVER['REMOTE_ADDR']);
?>