<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if(isset($_FILES['file'])) {
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
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dosya seçilmedi'
    ]);
}