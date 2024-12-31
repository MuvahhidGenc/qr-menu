<?php
require_once '../includes/config.php';
checkAuth();
require_once 'navbar.php';

// Rate limit kontrolü
checkRateLimit($_SERVER['REMOTE_ADDR']);
?>