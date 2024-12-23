
<?php
require_once '../includes/config.php';

$file = isset($_GET['file']) ? $_GET['file'] : '';
$backup_file = '../backups/' . basename($file);

if(file_exists($backup_file)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($backup_file));
    readfile($backup_file);
    exit;
}

header('Location: backup.php');
?>