<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Oturum ve yetki kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Oturum zaman aşımına uğradı.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        
        // Gelen verileri al
        $category_id = intval($_POST['category_id']);
        $amount = floatval($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $description = $_POST['description'];
        $admin_id = $_SESSION['admin_id'];
        
        // Personel maaşı ise staff_id'yi al
        $staff_id = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
        
        // Personel maaşı ödemesi ise personel bilgilerini kontrol et
        if ($staff_id) {
            $staff = $db->query("SELECT name, salary FROM admins WHERE id = ?", [$staff_id])->fetch();
            if (!$staff) {
                throw new Exception("Personel bulunamadı.");
            }
        }
        
        // Gider kaydını ekle
        if ($staff_id) {
            // Personel maaşı için
            $sql = "INSERT INTO expenses (category_id, admin_id, amount, description, expense_date, staff_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$category_id, $admin_id, $amount, $description, $expense_date, $staff_id];
        } else {
            // Normal gider için
            $sql = "INSERT INTO expenses (category_id, admin_id, amount, description, expense_date) 
                    VALUES (?, ?, ?, ?, ?)";
            $params = [$category_id, $admin_id, $amount, $description, $expense_date];
        }
        
        $db->query($sql, $params);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        error_log('Gider Ekleme Hatası: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} 