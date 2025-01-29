<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('categories.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $db = new Database();
    
    // Kategori durumu güncelleme için
    if (isset($input['field']) && $input['field'] === 'status') {
        $value = (int)$input['value'];
        if (!in_array($value, [0, 1])) {
            throw new Exception('Geçersiz durum değeri');
        }
        
        $db->query(
            "UPDATE categories SET status = ? WHERE id = ?",
            [$value, $input['category_id']]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Kategori durumu güncellendi'
        ]);
        exit; // Status güncellemesi için burada çıkış yapıyoruz
    }

    // Sıralama güncelleme işlemi
    if (isset($input['action']) && $input['action'] === 'update_order' && isset($input['categories'])) {
        foreach ($input['categories'] as $category) {
            $id = (int)$category['id'];
            $sort_order = (int)$category['sort_order'];
            
            $db->query(
                "UPDATE categories SET sort_order = ? WHERE id = ?",
                [$sort_order, $id]
            );
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Kategori sıralaması güncellendi'
        ]);
        exit;
    }

    // Normal kategori güncelleme işlemi
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
    $name = cleanInput($input['name'] ?? '');
    $image = $input['image'] ?? '';
    
    if (!$category_id) {
        throw new Exception('Kategori ID gerekli');
    }
    
    if (empty($name)) {
        throw new Exception('Kategori adı gerekli');
    }
    
    $db->query(
        "UPDATE categories SET name = ?, image = ? WHERE id = ?",
        [$name, $image, $category_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategori başarıyla güncellendi'
    ]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 