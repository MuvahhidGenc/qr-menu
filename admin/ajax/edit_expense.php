<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        
        $id = intval($_POST['id']);
        $category_id = intval($_POST['category_id']);
        $amount = floatval($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $description = $_POST['description'];
        
        $sql = "UPDATE expenses SET 
                category_id = ?, 
                amount = ?, 
                description = ?, 
                expense_date = ? 
                WHERE id = ?";
        
        $params = [$category_id, $amount, $description, $expense_date, $id];
        
        $db->query($sql, $params);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log('Gider Güncelleme Hatası: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} 