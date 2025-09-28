<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
header('Content-Type: application/json');

// Çoklu dosya yükleme desteği
if(isset($_FILES['files'])) {
    try {
        $uploadedFiles = [];
        $errors = [];
        
        // Her dosyayi ayrı ayrı yükle
        foreach($_FILES['files']['name'] as $index => $name) {
            if($_FILES['files']['error'][$index] === UPLOAD_ERR_OK) {
                // Geçici dosya oluştur
                $file = [
                    'name' => $_FILES['files']['name'][$index],
                    'type' => $_FILES['files']['type'][$index],
                    'tmp_name' => $_FILES['files']['tmp_name'][$index],
                    'error' => $_FILES['files']['error'][$index],
                    'size' => $_FILES['files']['size'][$index]
                ];
                
                $filename = secureUpload($file);
                
                if($filename) {
                    $uploadedFiles[] = $filename;
                } else {
                    $errors[] = "Dosya yüklenemedi: " . $name;
                }
            } else {
                $errors[] = "Dosya hatası: " . $name;
            }
        }
        
        if(count($uploadedFiles) > 0) {
            echo json_encode([
                'success' => true,
                'files' => $uploadedFiles,
                'message' => count($uploadedFiles) . ' dosya başarıyla yüklendi',
                'errors' => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Hiçbir dosya yüklenemedi',
                'errors' => $errors
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 
// Eski tek dosya desteği (geriye uyumluluk)
else if(isset($_FILES['file'])) {
    try {
        $filename = secureUpload($_FILES['file']);
        
        if($filename) {
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'message' => 'Dosya başarıyla yüklendi'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Dosya yüklenirken bir hata oluştu'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 
else {
    echo json_encode([
        'success' => false,
        'message' => 'Dosya seçilmedi'
    ]);
}