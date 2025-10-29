<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
    exit();
}

try {
    $db = new Database();
    
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['action']; // 'add' or 'remove'
    $user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('Kullanıcı bilgisi bulunamadı');
    }
    
    if ($action === 'add') {
        // Favoriye ekle
        $existing = $db->query(
            "SELECT id FROM pos_favorites WHERE product_id = ? AND user_id = ?",
            [$product_id, $user_id]
        )->fetch();
        
        if ($existing) {
            // Zaten varsa usage_count artır
            $db->query(
                "UPDATE pos_favorites SET usage_count = usage_count + 1, last_used = NOW() WHERE id = ?",
                [$existing['id']]
            );
            $favorite_id = $existing['id'];
        } else {
            // Yeni favori ekle
            $db->query(
                "INSERT INTO pos_favorites (product_id, user_id, usage_count, last_used) VALUES (?, ?, 1, NOW())",
                [$product_id, $user_id]
            );
            $favorite_id = $db->lastInsertId();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Favorilere eklendi',
            'favorite_id' => $favorite_id
        ]);
        
    } elseif ($action === 'remove') {
        // Favoriden çıkar
        $db->query(
            "DELETE FROM pos_favorites WHERE product_id = ? AND user_id = ?",
            [$product_id, $user_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Favorilerden çıkarıldı'
        ]);
    } else {
        throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

