<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../includes/config.php';

try {
    if(empty($_POST['filename'])) {
        throw new Exception('Dosya adı belirtilmedi');
    }

    $filename = basename($_POST['filename']);
    $filepath = "../uploads/" . $filename;

    // Dosya var mı kontrol et
    if(!file_exists($filepath)) {
        throw new Exception('Dosya bulunamadı');
    }

    // Dosyayı sil
    if(unlink($filepath)) {
        die(json_encode([
            'success' => true,
            'message' => 'Dosya başarıyla silindi'
        ]));
    } else {
        throw new Exception('Dosya silinirken bir hata oluştu');
    }

} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
}