<?php
require_once '../../includes/config.php';


$db = new Database();

try {
    $id = $_POST['id'] ?? null;
    $data = [
        'table_no' => $_POST['table_no'],
        'capacity' => $_POST['capacity'],
        'status' => $_POST['status']
    ];

    // Masa numarası kontrolü
    $existing = $db->query(
        "SELECT id FROM tables WHERE table_no = ? AND id != ?",
        [$data['table_no'], $id ?: 0]
    )->fetch();

    if ($existing) {
        throw new Exception('Bu masa numarası zaten kullanımda!');
    }

    if ($id) {
        // Güncelleme
        $db->query(
            "UPDATE tables SET table_no = ?, capacity = ?, status = ? WHERE id = ?",
            [...array_values($data), $id]
        );
    } else {
        // Yeni ekle
        $db->query(
            "INSERT INTO tables (table_no, capacity, status) VALUES (?, ?, ?)",
            array_values($data)
        );
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
} 