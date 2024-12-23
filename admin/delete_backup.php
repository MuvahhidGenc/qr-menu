<?php
require_once '../includes/config.php';

$file = isset($_GET['file']) ? $_GET['file'] : '';
$backup_file = '../backups/' . basename($file);

if(file_exists($backup_file)) {
    unlink($backup_file);
    $_SESSION['message'] = 'Yedek silindi.';
    $_SESSION['message_type'] = 'success';
}

header('Location: backup.php');
?>