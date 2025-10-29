<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('products.edit')) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

try {
    $db = new Database();
    
    $product_id = (int)$_POST['product_id'];
    $old_stock = (int)$_POST['old_stock'];
    $new_stock = (int)$_POST['new_stock'];
    $operation = $_POST['operation']; // IN, OUT, ADJUSTMENT
    $amount = (int)$_POST['amount'];
    $note = $_POST['note'] ?? '';
    $user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1;
    
    if ($new_stock < 0) {
        throw new Exception('Stok negatif olamaz');
    }
    
    $db->beginTransaction();
    
    // Ürün stoğunu güncelle
    $db->query(
        "UPDATE products SET stock = ? WHERE id = ?",
        [$new_stock, $product_id]
    );
    
    // Stok hareketini kaydet
    $movement_type = '';
    $movement_note = '';
    
    if ($operation === 'IN') {
        $movement_type = 'in';
        $movement_note = "Stok girişi: +{$amount} adet";
    } elseif ($operation === 'OUT') {
        $movement_type = 'out';
        $movement_note = "Stok çıkışı: -{$amount} adet";
    } elseif ($operation === 'ADJUSTMENT') {
        $movement_type = 'adjustment';
        $movement_note = "Stok düzeltme: {$old_stock} → {$new_stock}";
    }
    
    if (!empty($note)) {
        $movement_note .= " | " . $note;
    }
    
    // Hareketi kaydet
    $db->query(
        "INSERT INTO stock_movements (product_id, movement_type, quantity, old_stock, new_stock, note, created_by) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$product_id, $movement_type, $amount, $old_stock, $new_stock, $movement_note, $user_id]
    );
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stok başarıyla güncellendi',
        'new_stock' => $new_stock
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

