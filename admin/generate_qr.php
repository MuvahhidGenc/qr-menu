<?php
require_once '../includes/config.php';

// QR kod için phpqrcode kütüphanesini include edin
include('../libs/phpqrcode/qrlib.php');

// Menü URL'sini oluştur
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$menu_url = $actual_link . dirname($_SERVER['PHP_SELF'], 2);
$menu_url = str_replace('\\', '/', $menu_url);

// Geçici QR kod dosyası için klasör
if (!file_exists('../temp')) {
    mkdir('../temp', 0777, true);
}

// QR kod dosya yolu
$qr_file = '../temp/qr_' . time() . '.png';

if(isset($_GET['download'])) {
    // QR kodu oluştur
    QRcode::png($menu_url, $qr_file, QR_ECLEVEL_L, 10);
    
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="qr-menu.png"');
    readfile($qr_file);
    unlink($qr_file); // Geçici dosyayı sil
    exit;
} else {
    // Önizleme için QR kod
    QRcode::png($menu_url);
}