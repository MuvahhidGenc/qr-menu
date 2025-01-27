<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // Yetki kontrolü
    if (!hasPermission('products.edit')) {
        throw new Exception('Bu işlem için yetkiniz bulunmuyor.');
    }

    // JSON verisini al
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz veri formatı');
    }

    $db = new Database();

    // Ekleme işlemi için
    if (isset($input['action']) && $input['action'] === 'add') {
        if (!isset($input['category_id']) || !isset($input['name']) || !isset($input['price'])) {
            throw new Exception('Gerekli alanlar eksik');
        }

        // Veri temizleme ve doğrulama
        $categoryId = (int)$input['category_id'];
        $name = cleanInput($input['name']);
        $price = (float)$input['price'];
        $status = isset($input['status']) ? (int)$input['status'] : 1;
        $image = isset($input['image']) ? cleanInput($input['image']) : '';

        if (empty($name)) {
            throw new Exception('Ürün adı boş olamaz');
        }

        if ($price < 0) {
            throw new Exception('Fiyat negatif olamaz');
        }

        // Yeni ürün ekle
        $db->query(
            "INSERT INTO products (category_id, name, price, status, image) VALUES (?, ?, ?, ?, ?)",
            [$categoryId, $name, $price, $status, $image]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Ürün başarıyla eklendi'
        ]);
        exit;
    }

    // Güncelleme işlemi için
    if (isset($input['action']) && $input['action'] === 'update') {
        if (!isset($input['id']) || !isset($input['name']) || !isset($input['price'])) {
            throw new Exception('Gerekli alanlar eksik');
        }

        $id = (int)$input['id'];
        $name = cleanInput($input['name']);
        $price = (float)$input['price'];
        $status = isset($input['status']) ? (int)$input['status'] : 1;

        if (empty($name)) {
            throw new Exception('Ürün adı boş olamaz');
        }

        if ($price < 0) {
            throw new Exception('Fiyat negatif olamaz');
        }

        // Ürünü güncelle
        $db->query(
            "UPDATE products SET name = ?, price = ?, status = ? WHERE id = ?",
            [$name, $price, $status, $id]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Ürün başarıyla güncellendi'
        ]);
        exit;
    }

    // Tek alan güncelleme için (mevcut yapı)
    if (isset($input['id']) && isset($input['field']) && isset($input['value'])) {
        $id = (int)$input['id'];
        $field = $input['field'];
        $value = $input['value'];

        // İzin verilen alanları kontrol et
        $allowedFields = ['name', 'description', 'price', 'image', 'status'];
        if (!in_array($field, $allowedFields)) {
            throw new Exception('Geçersiz alan');
        }

        // Veri temizleme ve doğrulama
        switch ($field) {
            case 'price':
                $value = (float)$value;
                if ($value < 0) {
                    throw new Exception('Fiyat negatif olamaz');
                }
                break;
            case 'name':
                $value = cleanInput($value);
                if (empty($value)) {
                    throw new Exception('Ürün adı boş olamaz');
                }
                break;
            case 'description':
                $value = cleanInput($value);
                break;
            case 'status':
                $value = (int)$value;
                if (!in_array($value, [0, 1])) {
                    throw new Exception('Geçersiz durum değeri');
                }
                break;
        }

        // Tek alan güncelleme
        $query = "UPDATE products SET {$field} = ? WHERE id = ?";
        $db->query($query, [$value, $id]);

        echo json_encode([
            'success' => true,
            'message' => 'Alan başarıyla güncellendi'
        ]);
        exit;
    }

    throw new Exception('Geçersiz işlem');

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 