<?php
function secureUpload($file) {
    // Güvenlik kontrolleri
    if (!isset($file) || !is_array($file)) {
        throw new Exception('Geçersiz dosya');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yükleme hatası: ' . $file['error']);
    }
    
    // Dosya boyutu kontrolü (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Dosya boyutu çok büyük (max 5MB)');
    }
    
    // İzin verilen dosya türleri
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = basename($file['name']); // Path traversal koruması
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        throw new Exception('Geçersiz dosya türü. İzin verilen: ' . implode(', ', $allowed));
    }
    
    // MIME type kontrolü (double check)
    $allowedMimes = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('Geçersiz dosya içeriği');
    }
    
    // Güvenli dosya adı oluştur
    $newName = uniqid() . '_' . time() . '.' . $ext;
    $uploadDir = '../uploads/';
    
    // Upload dizinini kontrol et
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Upload dizini oluşturulamadı');
        }
    }
    
    $path = $uploadDir . $newName;
    
    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $path)) {
        // Dosya izinlerini güvenli hale getir
        chmod($path, 0644);
        return $newName;
    }
    
    throw new Exception('Dosya kaydedilemedi');
}